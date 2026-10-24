<?php
declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use DomainException;
use PDO;
use Throwable;

final class Pedido
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function crear(array $datos, array $cantidades, string $firmaEsperada, string $token): int
    {
        self::validar($datos);
        if (!$cantidades || count($cantidades) > 50) {
            throw new DomainException('Agregá productos al carrito antes de confirmar.');
        }
        $this->db->beginTransaction();
        try {
            $consulta = $this->db->prepare('SELECT id_pedido FROM pedidos WHERE token_confirmacion = ?');
            $consulta->execute([$token]);
            if ($existente = $consulta->fetchColumn()) {
                $this->db->commit();
                return (int) $existente;
            }
            $productos = (new Producto($this->db))->buscarVarios(array_keys($cantidades), true);
            if (count($productos) !== count($cantidades)) {
                throw new DomainException('Un producto ya no está disponible. Volvé al carrito para revisarlo.');
            }
            $lineas = [];
            $total = 0;
            foreach ($cantidades as $id => $cantidad) {
                if (!is_int($cantidad) || $cantidad < 1 || $cantidad > 99) {
                    throw new DomainException('Las cantidades deben estar entre 1 y 99.');
                }
                $linea = $productos[$id];
                $linea['cantidad'] = $cantidad;
                $total += $linea['precio'] * $cantidad;
                $lineas[] = $linea;
            }
            if (!hash_equals($firmaEsperada, Carrito::firma($lineas))) {
                throw new DomainException('El carrito o sus precios cambiaron. Volvé al carrito para revisarlos antes de confirmar.');
            }
            if ($total > 9999999999) {
                throw new DomainException('El total supera el máximo permitido.');
            }
            $consulta = $this->db->prepare('INSERT INTO pedidos
                (cliente_nombre, telefono, fecha_retiro, hora_retiro, notas, total, token_confirmacion)
                VALUES (?, ?, ?, ?, ?, ?, ?)');
            $consulta->execute([$datos['cliente_nombre'], $datos['telefono'], $datos['fecha_retiro'],
                $datos['hora_retiro'], $datos['notas'], self::decimal($total), $token]);
            $idPedido = (int) $this->db->lastInsertId();
            $detalle = $this->db->prepare('INSERT INTO pedido_detalles
                (id_pedido, id_producto, nombre_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?, ?)');
            foreach ($lineas as $linea) {
                $detalle->execute([$idPedido, $linea['id_producto'], $linea['nombre'], $linea['cantidad'], self::decimal($linea['precio'])]);
            }
            $this->db->commit();
            return $idPedido;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }

    public static function validar(array $datos): void
    {
        foreach (['cliente_nombre' => 120, 'telefono' => 30, 'fecha_retiro' => 10, 'hora_retiro' => 5, 'notas' => 2000] as $campo => $maximo) {
            $texto = $datos[$campo] ?? null;
            if (!is_string($texto) || mb_strlen($texto) > $maximo || ($campo !== 'notas' && trim($texto) === '')) {
                throw new DomainException('Completá los campos obligatorios y respetá sus longitudes máximas.');
            }
        }
        $telefono = $datos['telefono'];
        $digitos = preg_replace('/\D/', '', $telefono);
        if (!preg_match('/^\+?[\d\s()\-]+$/', $telefono) || strlen($digitos) < 6 || strlen($digitos) > 15) {
            throw new DomainException('Ingresá un teléfono de contacto válido.');
        }
        $fechaHora = $datos['fecha_retiro'] . ' ' . $datos['hora_retiro'];
        $retiro = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $fechaHora);
        if (!$retiro || $retiro->format('Y-m-d H:i') !== $fechaHora || $retiro <= new DateTimeImmutable()) {
            throw new DomainException('Ingresá una fecha y hora de retiro futuras y válidas.');
        }
    }

    private static function decimal(int $centavos): string
    {
        return sprintf('%d.%02d', intdiv($centavos, 100), $centavos % 100);
    }
}
