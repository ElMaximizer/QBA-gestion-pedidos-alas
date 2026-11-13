<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\CantidadStock;
use DateTimeImmutable;
use DomainException;
use PDO;
use PDOException;
use Throwable;

final class EntregaMercaderia
{
    public const MAXIMO_LINEAS = 20;

    public function __construct(private readonly PDO $db)
    {
    }

    public function registrar(array $datos, int $idUsuario, string $token): int
    {
        $validos = self::validar($datos);
        if ($idUsuario < 1 || !preg_match('/^[a-f0-9]{64}$/D', $token)) {
            throw new DomainException('El formulario no es válido. Abrí un nuevo registro de entrega.');
        }
        $consulta = $this->db->prepare("SELECT nombre FROM usuarios_equipo WHERE id_usuario = ?
            AND estado = 'aprobado' AND rol IN ('administrador', 'dueno')");
        $consulta->execute([$idUsuario]);
        $responsable = $consulta->fetchColumn();
        if ($responsable === false) {
            throw new DomainException('Tu cuenta no tiene permiso para registrar entregas de mercadería.');
        }
        $firma = hash('sha256', serialize([$idUsuario, $validos]));
        $this->db->beginTransaction();
        try {
            // La clave única hace que un doble envío se confirme una sola vez.
            $consulta = $this->db->prepare('INSERT INTO entregas_mercaderia
                (proveedor, fecha_recepcion, comprobante, observaciones, id_usuario, responsable, token_formulario, firma, creado_en)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())');
            $consulta->execute([$validos['proveedor'], $validos['fecha_recepcion'], $validos['comprobante'],
                $validos['observaciones'], $idUsuario, $responsable, $token, $firma]);
            $idEntrega = (int) $this->db->lastInsertId();
            $lineas = $validos['lineas'];
            $marcadores = implode(',', array_fill(0, count($lineas), '?'));
            // Mismo orden en todas las entregas para no cruzar los bloqueos de stock.
            $consulta = $this->db->prepare("SELECT id_materia, nombre, unidad, stock_actual FROM materias_primas
                WHERE id_materia IN ($marcadores) AND activo = 1 ORDER BY id_materia FOR UPDATE");
            $consulta->execute(array_keys($lineas));
            $materias = $consulta->fetchAll();
            if (count($materias) !== count($lineas)) {
                throw new DomainException('Una materia prima ya no está disponible. Revisá los insumos de la entrega.');
            }
            $detalle = $this->db->prepare('INSERT INTO entrega_mercaderia_detalles
                (id_entrega, id_materia, nombre_materia, unidad, cantidad, stock_anterior, stock_resultante)
                VALUES (?, ?, ?, ?, ?, ?, ?)');
            $actualizar = $this->db->prepare('UPDATE materias_primas SET stock_actual = ? WHERE id_materia = ?');
            foreach ($materias as $materia) {
                $cantidad = $lineas[(int) $materia['id_materia']];
                $nuevoStock = CantidadStock::sumar($materia['stock_actual'], $cantidad);
                $detalle->execute([$idEntrega, $materia['id_materia'], $materia['nombre'], $materia['unidad'],
                    $cantidad, $materia['stock_actual'], $nuevoStock]);
                $actualizar->execute([$nuevoStock, $materia['id_materia']]);
            }
            $this->db->commit();
            return $idEntrega;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            if ($error instanceof PDOException && (int) ($error->errorInfo[1] ?? 0) === 1062) {
                $consulta = $this->db->prepare('SELECT id_entrega, firma FROM entregas_mercaderia WHERE token_formulario = ?');
                $consulta->execute([$token]);
                $anterior = $consulta->fetch();
                if ($anterior) {
                    if (!hash_equals($anterior['firma'], $firma)) {
                        throw new DomainException('Esta entrega ya fue confirmada. Abrí un nuevo formulario para registrar otra.');
                    }
                    return (int) $anterior['id_entrega'];
                }
            }
            throw $error;
        }
    }

    private static function validar(array $datos): array
    {
        $validos = [];
        foreach (['proveedor' => 120, 'comprobante' => 80, 'observaciones' => 1000] as $campo => $maximo) {
            $valor = $datos[$campo] ?? '';
            if (!is_string($valor) || !mb_check_encoding($valor, 'UTF-8') || mb_strlen(trim($valor)) > $maximo
                || ($campo === 'proveedor' && trim($valor) === '')) {
                throw new DomainException('Ingresá el proveedor y respetá las longitudes máximas del formulario.');
            }
            $validos[$campo] = trim($valor);
        }
        $fecha = $datos['fecha_recepcion'] ?? null;
        $dia = is_string($fecha) && preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $fecha)
            ? DateTimeImmutable::createFromFormat('!Y-m-d', $fecha) : false;
        if (!$dia || $dia->format('Y-m-d') !== $fecha || $fecha < '1000-01-01' || $fecha > date('Y-m-d')) {
            throw new DomainException('Ingresá una fecha de recepción válida que no sea posterior a hoy.');
        }
        $validos['fecha_recepcion'] = $fecha;
        $lineas = $datos['lineas'] ?? null;
        if (!is_array($lineas) || !array_is_list($lineas) || count($lineas) < 1 || count($lineas) > self::MAXIMO_LINEAS) {
            throw new DomainException('La entrega debe incluir entre 1 y ' . self::MAXIMO_LINEAS . ' materias primas.');
        }
        $validos['lineas'] = [];
        foreach ($lineas as $linea) {
            $id = is_array($linea) && is_string($linea['id_materia'] ?? null)
                ? filter_var($linea['id_materia'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 4294967295]]) : false;
            if (!$id || isset($validos['lineas'][$id])) {
                throw new DomainException('Seleccioná una materia prima válida en cada fila, sin repetirla.');
            }
            $validos['lineas'][$id] = CantidadStock::validar($linea['cantidad'] ?? null, 'cantidad recibida', true);
        }
        ksort($validos['lineas'], SORT_NUMERIC);
        return $validos;
    }

    public function listar(string $busqueda = '', int $pagina = 1): array
    {
        if (!mb_check_encoding($busqueda, 'UTF-8') || mb_strlen($busqueda) > 120 || $pagina < 1) {
            throw new DomainException('Revisá la búsqueda y la página seleccionada.');
        }
        $where = $busqueda === '' ? '' : ' WHERE (e.proveedor LIKE ? OR e.comprobante LIKE ? OR EXISTS
            (SELECT 1 FROM entrega_mercaderia_detalles d WHERE d.id_entrega = e.id_entrega AND d.nombre_materia LIKE ?))';
        $parametros = $busqueda === '' ? [] : array_fill(0, 3, '%' . $busqueda . '%');
        $consulta = $this->db->prepare('SELECT COUNT(*) FROM entregas_mercaderia e' . $where);
        $consulta->execute($parametros);
        $total = (int) $consulta->fetchColumn();
        $paginas = max(1, (int) ceil($total / 25));
        $pagina = min($pagina, $paginas);
        $consulta = $this->db->prepare('SELECT e.id_entrega, e.proveedor, e.fecha_recepcion, e.comprobante,
            e.observaciones, e.responsable, e.creado_en FROM entregas_mercaderia e' . $where
            . ' ORDER BY e.fecha_recepcion DESC, e.id_entrega DESC LIMIT 25 OFFSET ?');
        foreach ($parametros as $indice => $valor) {
            $consulta->bindValue($indice + 1, $valor);
        }
        $consulta->bindValue(count($parametros) + 1, ($pagina - 1) * 25, PDO::PARAM_INT);
        $consulta->execute();
        $entregas = [];
        foreach ($consulta->fetchAll() as $entrega) {
            $entrega['detalles'] = [];
            $entregas[(int) $entrega['id_entrega']] = $entrega;
        }
        if ($entregas) {
            $marcadores = implode(',', array_fill(0, count($entregas), '?'));
            $consulta = $this->db->prepare("SELECT id_entrega, nombre_materia, unidad, cantidad, stock_anterior, stock_resultante
                FROM entrega_mercaderia_detalles WHERE id_entrega IN ($marcadores) ORDER BY id_detalle");
            $consulta->execute(array_keys($entregas));
            foreach ($consulta->fetchAll() as $detalle) {
                $entregas[(int) $detalle['id_entrega']]['detalles'][] = $detalle;
            }
        }
        return ['entregas' => array_values($entregas), 'total' => $total, 'pagina' => $pagina, 'paginas' => $paginas];
    }
}
