<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\CantidadStock;
use DomainException;
use PDO;
use Throwable;

final class StockMateriaPrima
{
    public const UNIDADES = ['kg', 'g', 'l', 'ml', 'unidad', 'paquete'];

    public function __construct(private readonly PDO $db)
    {
    }

    public function disponibles(): array
    {
        return $this->db->query('SELECT id_materia, nombre, unidad FROM materias_primas WHERE activo = 1 ORDER BY nombre')->fetchAll();
    }

    public function listar(string $busqueda = '', string $estado = '', int $pagina = 1): array
    {
        if (!mb_check_encoding($busqueda, 'UTF-8') || mb_strlen($busqueda) > 100
            || !in_array($estado, ['', 'bajo', 'correcto'], true) || $pagina < 1) {
            throw new DomainException('Revisá la búsqueda, el filtro y la página seleccionada.');
        }
        $condiciones = ['activo = 1'];
        $parametros = [];
        if ($busqueda !== '') {
            $condiciones[] = 'nombre LIKE ?';
            $parametros[] = '%' . $busqueda . '%';
        }
        if ($estado === 'bajo') {
            $condiciones[] = 'stock_actual <= stock_minimo';
        } elseif ($estado === 'correcto') {
            $condiciones[] = 'stock_actual > stock_minimo';
        }
        $where = implode(' AND ', $condiciones);
        $consulta = $this->db->prepare('SELECT COUNT(*) FROM materias_primas WHERE ' . $where);
        $consulta->execute($parametros);
        $total = (int) $consulta->fetchColumn();
        $paginas = max(1, (int) ceil($total / 25));
        $pagina = min($pagina, $paginas);
        $consulta = $this->db->prepare('SELECT id_materia, nombre, unidad, stock_actual, stock_minimo,
            (stock_actual <= stock_minimo) AS stock_bajo, actualizado_en
            FROM materias_primas WHERE ' . $where . ' ORDER BY (stock_actual <= stock_minimo) DESC, nombre LIMIT 25 OFFSET ?');
        foreach ($parametros as $indice => $valor) {
            $consulta->bindValue($indice + 1, $valor);
        }
        $consulta->bindValue(count($parametros) + 1, ($pagina - 1) * 25, PDO::PARAM_INT);
        $consulta->execute();
        return ['materias' => $consulta->fetchAll(), 'total' => $total, 'pagina' => $pagina, 'paginas' => $paginas];
    }

    public function agregar(array $datos): void
    {
        $validos = self::validar($datos);
        try {
            $consulta = $this->db->prepare('INSERT INTO materias_primas (nombre, unidad, stock_actual, stock_minimo)
                VALUES (?, ?, ?, ?)');
            $consulta->execute([$validos['nombre'], $validos['unidad'], $validos['stock_actual'], $validos['stock_minimo']]);
        } catch (\PDOException $error) {
            if ((int) ($error->errorInfo[1] ?? 0) === 1062) {
                throw new DomainException('Ya existe una materia prima con ese nombre.');
            }
            throw $error;
        }
    }

    public function actualizar(int $id, array $datos, string $version): void
    {
        $validos = self::validar($datos, false);
        $this->db->beginTransaction();
        try {
            $consulta = $this->db->prepare('SELECT id_materia, nombre, unidad, stock_actual, stock_minimo
                FROM materias_primas WHERE id_materia = ? AND activo = 1 FOR UPDATE');
            $consulta->execute([$id]);
            $materia = $consulta->fetch();
            if (!$materia || !hash_equals(self::version($materia), $version)) {
                throw new DomainException('La materia prima cambió. Recargá la pantalla antes de actualizarla.');
            }
            $consulta = $this->db->prepare('UPDATE materias_primas SET stock_actual = ?, stock_minimo = ? WHERE id_materia = ?');
            $consulta->execute([$validos['stock_actual'], $validos['stock_minimo'], $id]);
            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }

    public static function version(array $materia): string
    {
        return hash('sha256', serialize(array_intersect_key($materia, array_flip([
            'id_materia', 'nombre', 'unidad', 'stock_actual', 'stock_minimo',
        ]))));
    }

    private static function validar(array $datos, bool $incluyeIdentidad = true): array
    {
        $nombre = $datos['nombre'] ?? null;
        $unidad = $datos['unidad'] ?? null;
        if ($incluyeIdentidad && (!is_string($nombre) || !mb_check_encoding($nombre, 'UTF-8')
            || trim($nombre) === '' || mb_strlen(trim($nombre)) > 100
            || !is_string($unidad) || !in_array($unidad, self::UNIDADES, true))) {
            throw new DomainException('Ingresá un nombre de hasta 100 caracteres y seleccioná una unidad válida.');
        }
        return [
            'nombre' => $incluyeIdentidad ? trim($nombre) : '',
            'unidad' => $incluyeIdentidad ? $unidad : '',
            'stock_actual' => CantidadStock::validar($datos['stock_actual'] ?? null, 'stock actual'),
            'stock_minimo' => CantidadStock::validar($datos['stock_minimo'] ?? null, 'stock mínimo'),
        ];
    }
}
