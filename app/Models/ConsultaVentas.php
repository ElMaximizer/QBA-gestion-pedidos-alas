<?php
declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use DomainException;
use PDO;

/** Resumen de cobros confirmados y pedidos pendientes para HU08 y HU24. */
final class ConsultaVentas
{
    public function __construct(private readonly PDO $conexion)
    {
    }

    public function consultar(string $desde, string $hasta): array
    {
        if (!$this->fechaValida($desde) || !$this->fechaValida($hasta) || $desde > $hasta) {
            throw new DomainException('Elegí un período de fechas válido.');
        }

        $consulta = $this->conexion->prepare("SELECT
            COALESCE(SUM(CASE WHEN estado_pago = 'pagado' THEN total ELSE 0 END), 0) AS ingresos,
            COALESCE(SUM(estado_pago = 'pagado'), 0) AS pedidos_pagados,
            COALESCE(SUM(CASE WHEN estado_pago = 'pendiente' THEN total ELSE 0 END), 0) AS saldo_pendiente,
            COALESCE(SUM(estado_pago = 'pendiente'), 0) AS pedidos_pendientes,
            COALESCE(SUM(estado_pago = 'pagado' AND metodo_pago = 'transferencia'), 0) AS transferencias,
            COALESCE(SUM(estado_pago = 'pagado' AND metodo_pago = 'local'), 0) AS pagos_local
            FROM pedidos
            WHERE estado <> 'Cancelado'
                AND ((estado_pago = 'pagado' AND DATE(pago_confirmado_en) BETWEEN ? AND ?)
                    OR (estado_pago = 'pendiente' AND DATE(creado_en) BETWEEN ? AND ?))");
        $consulta->execute([$desde, $hasta, $desde, $hasta]);
        $resumen = $consulta->fetch(PDO::FETCH_ASSOC) ?: [];
        foreach (['ingresos', 'saldo_pendiente'] as $campo) {
            $resumen[$campo . '_centavos'] = self::aCentavos((string) ($resumen[$campo] ?? '0'));
        }
        foreach (['pedidos_pagados', 'pedidos_pendientes', 'transferencias', 'pagos_local'] as $campo) {
            $resumen[$campo] = (int) ($resumen[$campo] ?? 0);
        }

        $detalle = $this->conexion->prepare("SELECT p.id_pedido, p.cliente_nombre,
            p.metodo_pago, p.pago_confirmado_en, p.total,
            COALESCE(GROUP_CONCAT(CONCAT(d.nombre_producto, ' × ', d.cantidad)
                ORDER BY d.id_detalle SEPARATOR ', '), 'Sin detalle disponible') AS productos
            FROM pedidos p
            LEFT JOIN pedido_detalles d ON d.id_pedido = p.id_pedido
            WHERE p.estado <> 'Cancelado' AND p.estado_pago = 'pagado'
                AND DATE(p.pago_confirmado_en) BETWEEN ? AND ?
            GROUP BY p.id_pedido
            ORDER BY p.pago_confirmado_en DESC, p.id_pedido DESC
            LIMIT 100");
        $detalle->execute([$desde, $hasta]);
        $pedidos = $detalle->fetchAll(PDO::FETCH_ASSOC);
        foreach ($pedidos as &$pedido) {
            $pedido['total_centavos'] = self::aCentavos((string) $pedido['total']);
        }
        unset($pedido);

        return ['resumen' => $resumen, 'pedidos' => $pedidos];
    }

    private function fechaValida(string $fecha): bool
    {
        $objeto = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        return $objeto !== false && $objeto->format('Y-m-d') === $fecha;
    }

    private static function aCentavos(string $importe): int
    {
        [$entero, $decimal] = array_pad(explode('.', $importe, 2), 2, '0');
        return (int) $entero * 100 + (int) str_pad(substr($decimal, 0, 2), 2, '0');
    }
}
