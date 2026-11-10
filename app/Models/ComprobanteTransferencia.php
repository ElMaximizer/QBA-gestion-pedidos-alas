<?php
declare(strict_types=1);

namespace App\Models;

use DomainException;
use PDO;

final class ComprobanteTransferencia
{
    public function __construct(private readonly PDO $conexion)
    {
    }

    public function guardar(int $idPedido, int $idCliente, string $mime, string $contenido): void
    {
        if ($idPedido < 1 || $idCliente < 1 || strlen($contenido) < 1 || strlen($contenido) > 2 * 1024 * 1024) {
            throw new DomainException('El comprobante no es válido o supera los 2 MB.');
        }
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new DomainException('Adjuntá una imagen JPG, PNG o WebP.');
        }

        $this->conexion->beginTransaction();
        try {
            $pedido = $this->conexion->prepare('SELECT metodo_pago, estado, estado_pago
                FROM pedidos WHERE id_pedido = ? AND id_cliente = ? FOR UPDATE');
            $pedido->execute([$idPedido, $idCliente]);
            $actual = $pedido->fetch(PDO::FETCH_ASSOC);
            if ($actual === false || $actual['metodo_pago'] !== 'transferencia'
                || $actual['estado'] === 'Cancelado' || $actual['estado_pago'] === 'pagado') {
                throw new DomainException('Este pedido no admite un comprobante de transferencia.');
            }

            $guardar = $this->conexion->prepare('INSERT INTO comprobantes_transferencia
                (id_pedido, id_cliente, tipo_mime, contenido, enviado_en, estado_revision)
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, \'pendiente\')
                ON DUPLICATE KEY UPDATE id_cliente = VALUES(id_cliente), tipo_mime = VALUES(tipo_mime),
                    contenido = VALUES(contenido), enviado_en = CURRENT_TIMESTAMP, estado_revision = \'pendiente\'');
            $guardar->bindValue(1, $idPedido, PDO::PARAM_INT);
            $guardar->bindValue(2, $idCliente, PDO::PARAM_INT);
            $guardar->bindValue(3, $mime, PDO::PARAM_STR);
            $guardar->bindValue(4, $contenido, PDO::PARAM_LOB);
            $guardar->execute();

            $actualizar = $this->conexion->prepare("UPDATE pedidos SET transferencia_enviada_en = CURRENT_TIMESTAMP,
                estado_pago = 'pendiente', pago_confirmado_en = NULL, id_usuario_pago = NULL
                WHERE id_pedido = ? AND id_cliente = ?");
            $actualizar->execute([$idPedido, $idCliente]);
            $this->conexion->commit();
        } catch (\Throwable $error) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $error;
        }
    }

    public function obtener(int $idPedido): ?array
    {
        $consulta = $this->conexion->prepare('SELECT tipo_mime, contenido
            FROM comprobantes_transferencia WHERE id_pedido = ?');
        $consulta->execute([$idPedido]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $fila;
    }
}
