<?php
declare(strict_types=1);

namespace App\Models;

use DomainException;
use PDO;

/** Lectura y filtros del listado interno de pedidos (HU04). */
final class ConsultaPedidos
{
    private const ESTADOS = ['', ...Pedido::ESTADOS];
    private const FECHAS = ['', 'hoy', 'proximos', 'anteriores'];
    private const POR_PAGINA = 20;

    private readonly PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function resumenPanel(): array
    {
        // Se usa el día de Uruguay configurado por la aplicación, no el reloj de MySQL.
        $hoy = date('Y-m-d');
        $consulta = $this->conexion->prepare("SELECT
            COALESCE(SUM(fecha_retiro = ? AND estado <> 'Cancelado'), 0) AS hoy,
            COALESCE(SUM(fecha_retiro = ? AND estado = 'Pendiente'), 0) AS pendientes_hoy,
            COALESCE(SUM(estado NOT IN ('Entregado', 'Cancelado')), 0) AS por_entregar
            FROM pedidos");
        $consulta->execute([$hoy, $hoy]);
        return array_map('intval', $consulta->fetch(PDO::FETCH_ASSOC));
    }

    /** Indicadores de demanda para HU23, basados en pedidos no cancelados. */
    public function productosMasSolicitados(int $limite = 5): array
    {
        $limite = max(1, min($limite, 10));
        $consulta = $this->conexion->query("SELECT d.nombre_producto, MAX(NULLIF(pr.imagen, '')) AS imagen, SUM(d.cantidad) AS unidades,
            COUNT(DISTINCT d.id_pedido) AS pedidos
            FROM pedido_detalles d
            INNER JOIN pedidos p ON p.id_pedido = d.id_pedido
            LEFT JOIN productos pr ON pr.id_producto = d.id_producto
            WHERE p.estado <> 'Cancelado'
            GROUP BY d.nombre_producto
            ORDER BY unidades DESC, d.nombre_producto ASC
            LIMIT " . $limite);
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listar(
        string $busqueda = '',
        string $estado = '',
        string $fecha = '',
        int $pagina = 1,
    ): array {
        if (
            !mb_check_encoding($busqueda, 'UTF-8') ||
            mb_strlen($busqueda) > 100 ||
            !in_array($estado, self::ESTADOS, true) ||
            !in_array($fecha, self::FECHAS, true) ||
            $pagina < 1
        ) {
            throw new DomainException('Revisá la búsqueda y los filtros seleccionados.');
        }

        $condicionesSql = [];
        $parametros = [];
        if ($busqueda !== '') {
            $condicionesSql[] = '(CAST(p.id_pedido AS CHAR) LIKE ? OR p.cliente_nombre LIKE ? OR p.telefono LIKE ?
                OR p.direccion_entrega LIKE ? OR p.notas LIKE ?
                OR EXISTS (SELECT 1 FROM pedido_detalles d2 WHERE d2.id_pedido = p.id_pedido AND d2.nombre_producto LIKE ?))';
            $coincidencia = '%' . $busqueda . '%';
            array_push(
                $parametros,
                $coincidencia,
                $coincidencia,
                $coincidencia,
                $coincidencia,
                $coincidencia,
                $coincidencia,
            );
        }
        if ($estado !== '') {
            $condicionesSql[] = 'p.estado = ?';
            $parametros[] = $estado;
        }
        if ($fecha === 'hoy') {
            $condicionesSql[] = 'p.fecha_retiro = CURDATE()';
        } elseif ($fecha === 'proximos') {
            $condicionesSql[] =
                "p.fecha_retiro >= CURDATE() AND p.estado NOT IN ('Entregado', 'Cancelado')";
        } elseif ($fecha === 'anteriores') {
            $condicionesSql[] = 'p.fecha_retiro < CURDATE()';
        }
        $condicion = $condicionesSql ? ' WHERE ' . implode(' AND ', $condicionesSql) : '';

        $conteo = $this->conexion->prepare('SELECT COUNT(*) FROM pedidos p' . $condicion);
        $conteo->execute($parametros);
        $total = (int) $conteo->fetchColumn();
        $paginas = max(1, (int) ceil($total / self::POR_PAGINA));
        $pagina = min($pagina, $paginas);

        $consulta = $this->conexion->prepare(
            'SELECT p.id_pedido, p.cliente_nombre, p.telefono, p.fecha_retiro,
                p.hora_retiro, p.modalidad_entrega, p.direccion_entrega, p.notas,
                p.estado, p.total, p.metodo_pago, p.estado_pago, p.pago_confirmado_en,
                p.transferencia_enviada_en, MAX(c.estado_revision) AS estado_revision_transferencia,
                p.sena_recibida, p.creado_en,
                COALESCE(GROUP_CONCAT(CONCAT(d.nombre_producto, \' × \', d.cantidad)
                    ORDER BY d.id_detalle SEPARATOR \', \'), \'Sin detalle disponible\') AS productos
            FROM pedidos p LEFT JOIN pedido_detalles d ON d.id_pedido = p.id_pedido
                LEFT JOIN comprobantes_transferencia c ON c.id_pedido = p.id_pedido' .
                $condicion .
                ' GROUP BY p.id_pedido ORDER BY p.creado_en DESC, p.id_pedido DESC LIMIT ? OFFSET ?',
        );
        foreach ($parametros as $indice => $valor) {
            $consulta->bindValue($indice + 1, $valor, PDO::PARAM_STR);
        }
        $consulta->bindValue(count($parametros) + 1, self::POR_PAGINA, PDO::PARAM_INT);
        $consulta->bindValue(
            count($parametros) + 2,
            ($pagina - 1) * self::POR_PAGINA,
            PDO::PARAM_INT,
        );
        $consulta->execute();
        $pedidos = $consulta->fetchAll();
        foreach ($pedidos as $indice => $pedido) {
            $pedidos[$indice]['id_pedido'] = (int) $pedido['id_pedido'];
            $pedidos[$indice]['sena_recibida'] = (bool) $pedido['sena_recibida'];
            $pedidos[$indice]['total_centavos'] = self::aCentavos((string) $pedido['total']);
        }

        return [
            'pedidos' => $pedidos,
            'total' => $total,
            'pagina' => $pagina,
            'paginas' => $paginas,
        ];
    }

    public function registrarSena(int $idPedido, bool $recibida): void
    {
        if ($idPedido < 1) {
            throw new DomainException('Seleccioná un pedido válido.');
        }

        $consulta = $this->conexion->prepare('SELECT id_pedido FROM pedidos WHERE id_pedido = ?');
        $consulta->execute([$idPedido]);
        if (!$consulta->fetchColumn()) {
            throw new DomainException('El pedido ya no está disponible. Actualizá el listado.');
        }

        $consulta = $this->conexion->prepare(
            'UPDATE pedidos SET sena_recibida = ? WHERE id_pedido = ?',
        );
        $consulta->execute([$recibida ? 1 : 0, $idPedido]);
    }

    public function registrarPagoLocal(int $idPedido, int $idUsuario): void
    {
        if ($idPedido < 1 || $idUsuario < 1) {
            throw new DomainException('Seleccioná un pedido y una cuenta válidos.');
        }
        $this->conexion->beginTransaction();
        try {
            $cuenta = $this->conexion->prepare("SELECT id_usuario FROM usuarios_equipo
                WHERE id_usuario = ? AND estado = 'aprobado'
                    AND rol IN ('empleado', 'administrador', 'dueno') FOR UPDATE");
            $cuenta->execute([$idUsuario]);
            if (!$cuenta->fetchColumn()) {
                throw new DomainException('Tu cuenta no puede registrar pagos recibidos en el local.');
            }
            $consulta = $this->conexion->prepare('SELECT estado, estado_pago, metodo_pago
                FROM pedidos WHERE id_pedido = ? FOR UPDATE');
            $consulta->execute([$idPedido]);
            $pedido = $consulta->fetch(PDO::FETCH_ASSOC);
            if ($pedido === false || $pedido['metodo_pago'] !== 'local') {
                throw new DomainException('Solo se puede registrar efectivo para pedidos con pago en el local.');
            }
            if ($pedido['estado'] === 'Cancelado') {
                throw new DomainException('No se puede registrar un pago para un pedido cancelado.');
            }
            if ($pedido['estado_pago'] === 'pagado') {
                $this->conexion->commit();
                return;
            }
            $guardar = $this->conexion->prepare("UPDATE pedidos SET estado_pago = 'pagado',
                pago_confirmado_en = CURRENT_TIMESTAMP, id_usuario_pago = ? WHERE id_pedido = ?");
            $guardar->execute([$idUsuario, $idPedido]);
            $this->conexion->commit();
        } catch (\Throwable $error) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $error;
        }
    }

    public function revisarTransferencia(int $idPedido, bool $aprobada, int $idUsuario): void
    {
        if ($idPedido < 1 || $idUsuario < 1) {
            throw new DomainException('Seleccioná un pedido y una cuenta válidos.');
        }

        $this->conexion->beginTransaction();
        try {
            $cuenta = $this->conexion->prepare("SELECT id_usuario FROM usuarios_equipo
                WHERE id_usuario = ? AND estado = 'aprobado'
                    AND rol IN ('administrador', 'dueno') FOR UPDATE");
            $cuenta->execute([$idUsuario]);
            if (!$cuenta->fetchColumn()) {
                throw new DomainException('Solo administrador y dueño pueden aprobar o rechazar transferencias.');
            }

            $consulta = $this->conexion->prepare('SELECT estado, estado_pago, metodo_pago, transferencia_enviada_en
                FROM pedidos WHERE id_pedido = ? FOR UPDATE');
            $consulta->execute([$idPedido]);
            $pedido = $consulta->fetch(PDO::FETCH_ASSOC);
            if ($pedido === false || $pedido['metodo_pago'] !== 'transferencia'
                || empty($pedido['transferencia_enviada_en']) || $pedido['estado'] === 'Cancelado') {
                throw new DomainException('No hay una transferencia pendiente para revisar en este pedido.');
            }

            $comprobante = $this->conexion->prepare('SELECT estado_revision FROM comprobantes_transferencia
                WHERE id_pedido = ? FOR UPDATE');
            $comprobante->execute([$idPedido]);
            $estadoRevision = $comprobante->fetchColumn();
            if ($estadoRevision === false) {
                throw new DomainException('No encontramos el comprobante adjunto a este pedido.');
            }
            if ($estadoRevision !== 'pendiente' || $pedido['estado_pago'] === 'pagado') {
                throw new DomainException('Este comprobante ya fue revisado.');
            }

            if ($aprobada) {
                $actualizar = $this->conexion->prepare("UPDATE pedidos SET estado_pago = 'pagado',
                    pago_confirmado_en = CURRENT_TIMESTAMP, id_usuario_pago = ? WHERE id_pedido = ?");
                $actualizar->execute([$idUsuario, $idPedido]);
            } else {
                $actualizar = $this->conexion->prepare("UPDATE pedidos SET estado_pago = 'pendiente',
                    pago_confirmado_en = NULL, id_usuario_pago = NULL
                    WHERE id_pedido = ?");
                $actualizar->execute([$idPedido]);
            }

            $marcarRevision = $this->conexion->prepare('UPDATE comprobantes_transferencia
                SET estado_revision = ? WHERE id_pedido = ?');
            $marcarRevision->execute([$aprobada ? 'aprobada' : 'rechazada', $idPedido]);

            $this->conexion->commit();
        } catch (\Throwable $error) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $error;
        }
    }

    private static function aCentavos(string $importe): int
    {
        [$entero, $decimal] = array_pad(explode('.', $importe, 2), 2, '0');
        return (int) $entero * 100 + (int) str_pad(substr($decimal, 0, 2), 2, '0');
    }
}
