<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Conexion;
use DomainException;
use PDO;
use Throwable;

final class Producto
{
    public const CATEGORIAS = ['Panes', 'Bizcochos', 'Tortas', 'Especiales'];

    public function __construct(private readonly PDO $db)
    {
    }

    public static function catalogo(): self
    {
        return new self(Conexion::obtener());
    }

    public function resumenActivos(): array
    {
        $resumen = $this->db->query(
            'SELECT COUNT(*) AS productos, COUNT(DISTINCT categoria) AS categorias
            FROM productos
            WHERE activo = 1 AND eliminado_en IS NULL'
        )->fetch();

        return [
            'productos' => (int) $resumen['productos'],
            'categorias' => (int) $resumen['categorias'],
        ];
    }

    public function listarGestion(string $busqueda = '', string $estado = '', int $pagina = 1): array
    {
        if (
            !mb_check_encoding($busqueda, 'UTF-8')
            || mb_strlen($busqueda) > 120
            || !in_array($estado, ['', '0', '1'], true)
            || $pagina < 1
        ) {
            throw new DomainException('Revisá la búsqueda, el estado y la página seleccionada.');
        }

        $condiciones = 'eliminado_en IS NULL';
        $parametros = [];

        if ($busqueda !== '') {
            $condiciones .= " AND (nombre LIKE ? OR CONCAT('PRD-', LPAD(id_producto,
                GREATEST(3, CHAR_LENGTH(id_producto)), '0')) LIKE ?)";
            $parametros = ['%' . $busqueda . '%', '%' . $busqueda . '%'];
        }

        if ($estado !== '') {
            $condiciones .= ' AND activo = ?';
            $parametros[] = $estado;
        }

        $consulta = $this->db->prepare(
            'SELECT COUNT(*) FROM productos WHERE ' . $condiciones
        );
        $consulta->execute($parametros);

        $total = (int) $consulta->fetchColumn();
        $paginas = max(1, (int) ceil($total / 25));
        $pagina = min($pagina, $paginas);

        $consulta = $this->db->prepare(
            'SELECT * FROM productos WHERE ' . $condiciones .
            ' ORDER BY id_producto LIMIT 25 OFFSET ?'
        );

        foreach ($parametros as $indice => $valor) {
            $consulta->bindValue($indice + 1, $valor);
        }

        $consulta->bindValue(
            count($parametros) + 1,
            ($pagina - 1) * 25,
            PDO::PARAM_INT
        );
        $consulta->execute();

        return [
            'productos' => array_map(self::normalizar(...), $consulta->fetchAll()),
            'total' => $total,
            'pagina' => $pagina,
            'paginas' => $paginas,
        ];
    }

    public function buscarGestion(int $id, bool $bloquear = false): ?array
    {
        $consulta = $this->db->prepare(
            'SELECT * FROM productos
            WHERE id_producto = ? AND eliminado_en IS NULL' .
            ($bloquear ? ' FOR UPDATE' : '')
        );
        $consulta->execute([$id]);

        $producto = $consulta->fetch();

        return $producto ? self::normalizar($producto) : null;
    }

    public static function validar(array $datos): array
    {
        $validos = [];

        foreach ([
            'nombre' => 120,
            'descripcion' => 280,
            'presentacion' => 80,
            'ingredientes' => 5000,
            'alergenos' => 500,
        ] as $campo => $maximo) {
            $texto = $datos[$campo] ?? null;

            if (
                !is_string($texto)
                || !mb_check_encoding($texto, 'UTF-8')
                || mb_strlen($texto) > $maximo
                || (in_array($campo, ['nombre', 'presentacion'], true) && trim($texto) === '')
            ) {
                throw new DomainException(
                    'Completá nombre y presentación y respetá las longitudes máximas de los campos.'
                );
            }

            $validos[$campo] = trim($texto);
        }

        // Si el formulario no lo envía, el producto queda sin destacar.
        $destacado = (string) ($datos['destacado'] ?? '0');

        if (
            !in_array($datos['categoria'] ?? null, self::CATEGORIAS, true)
            || !in_array($datos['activo'] ?? null, ['0', '1'], true)
            || !in_array($destacado, ['0', '1'], true)
        ) {
            throw new DomainException(
                'Seleccioná una categoría, un estado y una opción de destacado válidos.'
            );
        }

        $precio = $datos['precio'] ?? null;
        $precio = is_string($precio) ? str_replace(',', '.', trim($precio)) : '';

        if (!preg_match('/^([0-9]{1,8})(?:\.([0-9]{1,2}))?$/D', $precio, $partes)) {
            throw new DomainException(
                'Ingresá un precio mayor que cero, con hasta dos decimales.'
            );
        }

        $centavos = (int) $partes[1] * 100
            + (int) str_pad($partes[2] ?? '', 2, '0');

        if ($centavos < 1) {
            throw new DomainException(
                'Ingresá un precio mayor que cero, con hasta dos decimales.'
            );
        }

        return $validos + [
            'categoria' => $datos['categoria'],
            'activo' => (int) $datos['activo'],
            'precio' => sprintf('%d.%02d', intdiv($centavos, 100), $centavos % 100),
            'destacado' => (int) $destacado,
        ];
    }

    /** Huella del estado leído: evita sobrescribir cambios de otro formulario. */
    public static function version(array $producto): string
    {
        return hash('sha256', serialize(array_intersect_key($producto, array_flip([
            'id_producto',
            'nombre',
            'descripcion',
            'precio',
            'categoria',
            'presentacion',
            'ingredientes',
            'alergenos',
            'activo',
            'destacado',
            'imagen_guardada',
        ]))));
    }

    /** Recibe exclusivamente el resultado de validar(), coordinado por GestionProducto. */
    public function guardarValidado(
        array $datos,
        ?string $imagen,
        ?int $id = null,
        string $version = ''
    ): array {
        $this->db->beginTransaction();

        try {
            $anterior = $id === null ? null : $this->exigirVersion($id, $version);

            if ($datos['destacado'] === 1 && $datos['activo'] === 1) {
                $consulta = $this->db->prepare(
                    'SELECT COUNT(*) FROM productos
                    WHERE destacado = 1
                    AND activo = 1
                    AND eliminado_en IS NULL
                    AND id_producto <> ?'
                );
                $consulta->execute([$id ?? 0]);

                if ((int) $consulta->fetchColumn() >= 3) {
                    throw new DomainException(
                        'Ya hay tres favoritos activos. Desmarcá uno antes de elegir otro.'
                    );
                }
            }

            $imagenGuardada = $imagen ?? ($anterior['imagen_guardada'] ?? '');

            if (in_array($imagenGuardada, ['pan.svg', 'croissant.svg', 'torta.svg'], true)) {
                $imagenGuardada = '';
            }

            $parametros = [
                $datos['nombre'],
                $datos['descripcion'],
                $datos['precio'],
                $datos['categoria'],
                $datos['presentacion'],
                $datos['ingredientes'],
                $datos['alergenos'],
                $datos['activo'],
                $datos['destacado'],
                $imagenGuardada,
            ];

            if ($id === null) {
                $consulta = $this->db->prepare(
                    'INSERT INTO productos
                    (nombre, descripcion, precio, categoria, presentacion, ingredientes,
                    alergenos, activo, destacado, imagen)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
            } else {
                $consulta = $this->db->prepare(
                    'UPDATE productos
                    SET nombre = ?, descripcion = ?, precio = ?, categoria = ?,
                    presentacion = ?, ingredientes = ?, alergenos = ?, activo = ?,
                    destacado = ?, imagen = ?
                    WHERE id_producto = ?'
                );
                $parametros[] = $id;
            }

            $consulta->execute($parametros);
            $id ??= (int) $this->db->lastInsertId();
            $this->db->commit();

            return [
                'id_producto' => $id,
                'imagen_anterior' => $anterior['imagen_guardada'] ?? '',
            ];
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $error;
        }
    }

    public function archivar(int $id, string $version): string
    {
        $this->db->beginTransaction();

        try {
            $producto = $this->exigirVersion($id, $version);
            $consulta = $this->db->prepare(
                'UPDATE productos
                SET activo = 0, eliminado_en = UTC_TIMESTAMP()
                WHERE id_producto = ?'
            );
            $consulta->execute([$id]);
            $this->db->commit();

            return $producto['imagen_guardada'];
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $error;
        }
    }

    private function exigirVersion(int $id, string $version): array
    {
        $producto = $this->buscarGestion($id, true);

        if (!$producto || !hash_equals(self::version($producto), $version)) {
            throw new DomainException(
                'El producto cambió o fue eliminado. Volvé al listado y abrilo nuevamente.'
            );
        }

        return $producto;
    }

    public static function imagen(array $producto): string
    {
        $imagen = $producto['imagen'] ?? '';

        if (
            in_array($imagen, ['pan.svg', 'croissant.svg', 'torta.svg'], true)
            || (
                is_string($imagen)
                && preg_match('~^productos/[a-f0-9]{32}\.(?:jpg|png)$~D', $imagen)
            )
        ) {
            return $imagen;
        }

        return match ($producto['categoria'] ?? '') {
            'Bizcochos' => 'croissant.svg',
            'Tortas', 'Especiales' => 'torta.svg',
            default => 'pan.svg',
        };
    }

    public function listarGestionInicio(): array
    {
        $consulta = $this->db->query(
            'SELECT * FROM productos
            WHERE activo = 1 AND eliminado_en IS NULL
            ORDER BY nombre, id_producto'
        );

        return array_map(self::normalizar(...), $consulta->fetchAll());
    }

    public function listarPortada(): array
    {
        $consulta = $this->db->query(
            'SELECT * FROM productos
            WHERE activo = 1
            AND eliminado_en IS NULL
            AND portada_orden IS NOT NULL
            ORDER BY portada_orden, id_producto
            LIMIT 5'
        );

        $productos = array_map(self::normalizar(...), $consulta->fetchAll());

        return $productos !== [] ? $productos : $this->listar('', '', true);
    }

    public function guardarSeleccionInicio(array $favoritos, array $portada): void
    {
        if (
            count($favoritos) > 3
            || count($portada) > 5
            || count(array_unique($favoritos)) !== count($favoritos)
            || count(array_unique($portada)) !== count($portada)
        ) {
            throw new DomainException(
                'Elegí hasta 3 favoritos y hasta 5 imágenes para el carrusel.'
            );
        }

        foreach (array_merge($favoritos, $portada) as $id) {
            if (!is_int($id) || $id < 1) {
                throw new DomainException('Revisá la selección de productos.');
            }
        }

        $this->db->beginTransaction();

        try {
            $disponibles = $this->db->query(
                'SELECT id_producto FROM productos
                WHERE activo = 1 AND eliminado_en IS NULL
                FOR UPDATE'
            )->fetchAll(PDO::FETCH_COLUMN);

            $idsDisponibles = array_map('intval', $disponibles);

            if (array_diff(array_unique(array_merge($favoritos, $portada)), $idsDisponibles)) {
                throw new DomainException(
                    'Un producto seleccionado ya no está disponible. Recargá la página.'
                );
            }

            $this->db->exec(
                'UPDATE productos SET destacado = 0, portada_orden = NULL'
            );

            if ($favoritos !== []) {
                $consulta = $this->db->prepare(
                    'UPDATE productos SET destacado = 1 WHERE id_producto = ?'
                );

                foreach ($favoritos as $id) {
                    $consulta->execute([$id]);
                }
            }

            if ($portada !== []) {
                $consulta = $this->db->prepare(
                    'UPDATE productos SET portada_orden = ? WHERE id_producto = ?'
                );

                foreach ($portada as $orden => $id) {
                    $consulta->execute([$orden + 1, $id]);
                }
            }

            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $error;
        }
    }

    public function listar(
        string $busqueda = '',
        string $categoria = '',
        bool $destacados = false
    ): array {
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

        $consulta = $this->db->prepare(
            $sql . ' ORDER BY destacado DESC, id_producto'
            . ($destacados ? ' LIMIT 3' : '')
        );
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
        $consulta = $this->db->prepare(
            "SELECT * FROM productos
            WHERE id_producto IN ($marcadores)
            AND activo = 1
            AND eliminado_en IS NULL
            ORDER BY id_producto" . ($bloquear ? ' FOR UPDATE' : '')
        );
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
        [$entero, $decimal] = array_pad(
            explode('.', (string) $producto['precio'], 2),
            2,
            '0'
        );
        $producto['precio'] = (int) $entero * 100 + (int) str_pad($decimal, 2, '0');
        $producto['id_producto'] = (int) $producto['id_producto'];
        $producto['imagen_guardada'] = $producto['imagen'];
        $producto['imagen'] = self::imagen($producto);

        return $producto;
    }
}