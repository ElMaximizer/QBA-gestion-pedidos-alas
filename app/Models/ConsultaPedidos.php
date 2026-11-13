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

    public function __construct(private readonly PDO $db)
    {
    }

    public function resumenPanel(): array
    {
        // Se usa el día de Uruguay configurado por la aplicación, no el reloj de MySQL.
        $hoy = date('Y-m-d');
        $consulta = $this->db->prepare("SELECT
            COALESCE(SUM(fecha_retiro = ? AND estado <> 'Cancelado'), 0) AS hoy,
            COALESCE(SUM(fecha_retiro = ? AND estado = 'Pendiente'), 0) AS pendientes_hoy,
            COALESCE(SUM(estado NOT IN ('Entregado', 'Cancelado')), 0) AS por_entregar
            FROM pedidos");
        $consulta->execute([$hoy, $hoy]);
        return array_map('intval', $consulta->fetch(PDO::FETCH_ASSOC));
    }

    public function listar(string $busqueda = '', string $estado = '', string $fecha = '', int $pagina = 1): array
    {
        if (!mb_check_encoding($busqueda, 'UTF-8') || mb_strlen($busqueda) > 100
            || !in_array($estado, self::ESTADOS, true) || !in_array($fecha, self::FECHAS, true) || $pagina < 1) {
            throw new DomainException('Revisá la búsqueda y los filtros seleccionados.');
        }

        $where = [];
        $parametros = [];
        if ($busqueda !== '') {
            $where[] = '(CAST(p.id_pedido AS CHAR) LIKE ? OR p.cliente_nombre LIKE ? OR p.telefono LIKE ?
                OR EXISTS (SELECT 1 FROM pedido_detalles d2 WHERE d2.id_pedido = p.id_pedido AND d2.nombre_producto LIKE ?))';
            $coincidencia = '%' . $busqueda . '%';
            array_push($parametros, $coincidencia, $coincidencia, $coincidencia, $coincidencia);
        }
        if ($estado !== '') {
            $where[] = 'p.estado = ?';
            $parametros[] = $estado;
        }
        if ($fecha === 'hoy') {
            $where[] = 'p.fecha_retiro = CURDATE()';
        } elseif ($fecha === 'proximos') {
            $where[] = "p.fecha_retiro >= CURDATE() AND p.estado NOT IN ('Entregado', 'Cancelado')";
        } elseif ($fecha === 'anteriores') {
            $where[] = 'p.fecha_retiro < CURDATE()';
        }
        $condicion = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $conteo = $this->db->prepare('SELECT COUNT(*) FROM pedidos p' . $condicion);
        $conteo->execute($parametros);
        $total = (int) $conteo->fetchColumn();
        $paginas = max(1, (int) ceil($total / self::POR_PAGINA));
        $pagina = min($pagina, $paginas);

        $consulta = $this->db->prepare('SELECT p.id_pedido, p.cliente_nombre, p.telefono, p.fecha_retiro,
                p.hora_retiro, p.estado, p.total, p.sena_recibida, p.creado_en,
                COALESCE(GROUP_CONCAT(CONCAT(d.nombre_producto, \' × \', d.cantidad)
                    ORDER BY d.id_detalle SEPARATOR \', \'), \'Sin detalle disponible\') AS productos
            FROM pedidos p LEFT JOIN pedido_detalles d ON d.id_pedido = p.id_pedido'
            . $condicion . ' GROUP BY p.id_pedido ORDER BY p.creado_en DESC, p.id_pedido DESC LIMIT ? OFFSET ?');
        foreach ($parametros as $indice => $valor) {
            $consulta->bindValue($indice + 1, $valor, PDO::PARAM_STR);
        }
        $consulta->bindValue(count($parametros) + 1, self::POR_PAGINA, PDO::PARAM_INT);
        $consulta->bindValue(count($parametros) + 2, ($pagina - 1) * self::POR_PAGINA, PDO::PARAM_INT);
        $consulta->execute();
        $pedidos = $consulta->fetchAll();
        foreach ($pedidos as &$pedido) {
            $pedido['id_pedido'] = (int) $pedido['id_pedido'];
            $pedido['sena_recibida'] = (bool) $pedido['sena_recibida'];
            $pedido['total_centavos'] = self::aCentavos((string) $pedido['total']);
        }
        unset($pedido);

        return ['pedidos' => $pedidos, 'total' => $total, 'pagina' => $pagina, 'paginas' => $paginas];
    }

    public function registrarSena(int $idPedido, bool $recibida): void
    {
        if ($idPedido < 1) {
            throw new DomainException('Seleccioná un pedido válido.');
        }

        $consulta = $this->db->prepare('SELECT id_pedido FROM pedidos WHERE id_pedido = ?');
        $consulta->execute([$idPedido]);
        if (!$consulta->fetchColumn()) {
            throw new DomainException('El pedido ya no está disponible. Actualizá el listado.');
        }

        $consulta = $this->db->prepare('UPDATE pedidos SET sena_recibida = ? WHERE id_pedido = ?');
        $consulta->execute([$recibida ? 1 : 0, $idPedido]);
    }

    private static function aCentavos(string $importe): int
    {
        [$entero, $decimal] = array_pad(explode('.', $importe, 2), 2, '0');
        return (int) $entero * 100 + (int) str_pad(substr($decimal, 0, 2), 2, '0');
    }
}
