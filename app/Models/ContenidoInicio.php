<?php
declare(strict_types=1);

namespace App\Models;

use DomainException;
use PDO;
use Throwable;

final class ContenidoInicio
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function seleccion(): array
    {
        $filas = $this->filas();
        $favoritos = [];
        $portada = [];
        foreach ($filas as $fila) {
            if (!(int) $fila['activo'] || $fila['eliminado_en'] !== null) continue;
            if ((int) $fila['destacado'] === 1) $favoritos[] = (int) $fila['id_producto'];
            if ($fila['portada_orden'] !== null) $portada[(int) $fila['portada_orden']] = (int) $fila['id_producto'];
        }
        ksort($portada, SORT_NUMERIC);
        return ['favoritos' => $favoritos, 'portada' => array_values($portada), 'version' => self::version($filas)];
    }

    public function guardar(array $favoritos, array $portada, string $version): void
    {
        foreach ([[$favoritos, 3], [$portada, 5]] as [$ids, $maximo]) {
            if (count($ids) > $maximo || count($ids) !== count(array_unique($ids, SORT_REGULAR))) {
                throw new DomainException('Elegí hasta 3 favoritos y 5 productos distintos para el carrusel.');
            }
            foreach ($ids as $id) {
                if (!is_int($id) || $id < 1) throw new DomainException('Revisá los productos seleccionados.');
            }
        }
        $this->db->beginTransaction();
        try {
            // Bloqueamos siempre en el mismo orden: ambas selecciones se guardan juntas.
            $filas = $this->filas(true);
            if (!hash_equals(self::version($filas), $version)) {
                throw new DomainException('Los productos o la selección cambiaron. Recargá la selección antes de guardar.');
            }
            $activos = array_map(static fn (array $fila): int => (int) $fila['id_producto'],
                array_filter($filas, static fn (array $fila): bool => (int) $fila['activo'] === 1 && $fila['eliminado_en'] === null));
            if (array_diff(array_merge($favoritos, $portada), $activos)) {
                throw new DomainException('Seleccioná solamente productos activos del catálogo.');
            }
            $this->db->exec('UPDATE productos SET destacado = 0, portada_orden = NULL
                WHERE destacado <> 0 OR portada_orden IS NOT NULL');
            $favorito = $this->db->prepare('UPDATE productos SET destacado = 1 WHERE id_producto = ?');
            foreach ($favoritos as $id) $favorito->execute([$id]);
            $diapositiva = $this->db->prepare('UPDATE productos SET portada_orden = ? WHERE id_producto = ?');
            foreach (array_values($portada) as $orden => $id) $diapositiva->execute([$orden + 1, $id]);
            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    private function filas(bool $bloquear = false): array
    {
        return $this->db->query('SELECT id_producto, nombre, precio, imagen, activo, eliminado_en, destacado, portada_orden
            FROM productos ORDER BY id_producto' . ($bloquear ? ' FOR UPDATE' : ''))->fetchAll();
    }

    private static function version(array $filas): string
    {
        return hash('sha256', serialize($filas));
    }
}
