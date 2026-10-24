<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Conexion;
use PDO;

final class Producto
{
    public const CATEGORIAS = ['Panes', 'Bizcochos', 'Tortas'];

    public function __construct(private readonly PDO $db)
    {
    }

    public static function catalogo(): self
    {
        return new self(Conexion::obtener());
    }

    public function listar(string $busqueda = '', string $categoria = '', bool $destacados = false): array
    {
        $sql = 'SELECT * FROM productos WHERE activo = 1 AND eliminado_en IS NULL';
        $parametros = [];
        if ($busqueda !== '') {
            $sql .= ' AND (nombre LIKE ? OR descripcion LIKE ? OR ingredientes LIKE ?)';
            $parametros = array_fill(0, 3, '%' . $busqueda . '%');
        }
        if (in_array($categoria, self::CATEGORIAS, true)) {
            $sql .= ' AND categoria = ?';
            $parametros[] = $categoria;
        }
        if ($destacados) {
            $sql .= ' AND destacado = 1';
        }
        $consulta = $this->db->prepare($sql . ' ORDER BY destacado DESC, id_producto' . ($destacados ? ' LIMIT 3' : ''));
        $consulta->execute($parametros);
        return array_map(self::normalizar(...), $consulta->fetchAll());
    }

    /** Una sola consulta para todas las líneas; el bloqueo se usa dentro de la transacción. */
    public function buscarVarios(array $ids, bool $bloquear = false): array
    {
        if (!$ids) {
            return [];
        }
        sort($ids, SORT_NUMERIC);
        $marcadores = implode(',', array_fill(0, count($ids), '?'));
        $consulta = $this->db->prepare("SELECT * FROM productos WHERE id_producto IN ($marcadores)
            AND activo = 1 AND eliminado_en IS NULL ORDER BY id_producto" . ($bloquear ? ' FOR UPDATE' : ''));
        $consulta->execute($ids);
        $productos = [];
        foreach ($consulta->fetchAll() as $fila) {
            $producto = self::normalizar($fila);
            $productos[$producto['id_producto']] = $producto;
        }
        return $productos;
    }

    private static function normalizar(array $producto): array
    {
        // DECIMAL llega como texto: evitamos errores de redondeo con importes en centavos.
        [$entero, $decimal] = array_pad(explode('.', (string) $producto['precio'], 2), 2, '0');
        $producto['precio'] = (int) $entero * 100 + (int) str_pad($decimal, 2, '0');
        $producto['id_producto'] = (int) $producto['id_producto'];
        $producto['imagen'] = in_array($producto['imagen'], ['pan.svg', 'croissant.svg', 'torta.svg'], true)
            ? $producto['imagen'] : match ($producto['categoria']) {
                'Bizcochos' => 'croissant.svg', 'Tortas' => 'torta.svg', default => 'pan.svg',
            };
        return $producto;
    }
}
