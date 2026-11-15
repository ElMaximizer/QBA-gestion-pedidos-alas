<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Importe;
use PDO;

/** Pedidos visibles únicamente para la cuenta de cliente autenticada. */
final class PedidosCliente
{
    private readonly PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function listar(int $idCliente): array
    {
        if ($idCliente < 1) {
            return [];
        }

        $consulta = $this->conexion->prepare('SELECT p.id_pedido, p.cliente_nombre, p.telefono,
            p.fecha_retiro, p.hora_retiro, p.modalidad_entrega, p.direccion_entrega,
            p.metodo_pago, p.estado_pago, p.transferencia_enviada_en, c.estado_revision AS estado_revision_transferencia,
            p.notas, p.estado, p.total, p.creado_en,
            d.nombre_producto, d.cantidad, d.precio_unitario
            FROM pedidos p LEFT JOIN pedido_detalles d ON d.id_pedido = p.id_pedido
            LEFT JOIN comprobantes_transferencia c ON c.id_pedido = p.id_pedido
            WHERE p.id_cliente = ?
            ORDER BY p.creado_en DESC, p.id_pedido DESC, d.id_detalle ASC');
        $consulta->execute([$idCliente]);

        $pedidos = [];
        foreach ($consulta->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $id = (int) $fila['id_pedido'];
            if (!isset($pedidos[$id])) {
                $pedidos[$id] = [
                    'id_pedido' => $id,
                    'cliente_nombre' => $fila['cliente_nombre'],
                    'telefono' => $fila['telefono'],
                    'fecha_retiro' => $fila['fecha_retiro'],
                    'modalidad_entrega' => $fila['modalidad_entrega'],
                    'direccion_entrega' => $fila['direccion_entrega'],
                    'metodo_pago' => $fila['metodo_pago'],
                    'estado_pago' => $fila['estado_pago'],
                    'transferencia_enviada_en' => $fila['transferencia_enviada_en'],
                    'estado_revision_transferencia' => $fila['estado_revision_transferencia'],
                    'hora_retiro' => substr((string) $fila['hora_retiro'], 0, 5),
                    'notas' => $fila['notas'],
                    'estado' => $fila['estado'],
                    'total_centavos' => Importe::aCentavos((string) $fila['total']),
                    'creado_en' => $fila['creado_en'],
                    'lineas' => [],
                ];
            }
            if ($fila['nombre_producto'] !== null) {
                $pedidos[$id]['lineas'][] = [
                    'nombre' => $fila['nombre_producto'],
                    'cantidad' => (int) $fila['cantidad'],
                    'precio_centavos' => Importe::aCentavos((string) $fila['precio_unitario']),
                ];
            }
        }
        return array_values($pedidos);
    }

    public static function puedeInformarTransferencia(array $pedido): bool
    {
        return $pedido['metodo_pago'] === 'transferencia' && $pedido['estado_pago'] !== 'pagado'
            && $pedido['estado'] !== 'Cancelado'
            && (empty($pedido['transferencia_enviada_en'])
                || ($pedido['estado_revision_transferencia'] ?? null) === null
                || ($pedido['estado_revision_transferencia'] ?? null) === 'rechazada');
    }
}
