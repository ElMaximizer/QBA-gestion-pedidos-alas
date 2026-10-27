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

    public const MAXIMO_PRODUCTOS = 50;
    public const MAXIMA_CANTIDAD = 99;

    public function crear(array $datos, array $cantidades, string $firmaEsperada, string $token,
        ?int $idCliente = null, ?int $idUsuarioRegistro = null): int
    {
        self::validar($datos);
        if (!$cantidades || count($cantidades) > self::MAXIMO_PRODUCTOS) {
            throw new DomainException('Agregá entre 1 y 50 productos antes de confirmar el pedido.');
        }
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)
            || ($idCliente !== null && $idUsuarioRegistro !== null)) {
            throw new DomainException('Volvé a abrir el formulario para registrar el pedido.');
        }
        $this->db->beginTransaction();
        try {
            if ($idUsuarioRegistro !== null) {
                $consulta = $this->db->prepare("SELECT id_usuario FROM usuarios_equipo WHERE id_usuario = ?
                    AND estado = 'aprobado' AND rol IN ('administrador', 'dueno') LOCK IN SHARE MODE");
                $consulta->execute([$idUsuarioRegistro]);
                if (!$consulta->fetchColumn()) {
                    throw new DomainException('Tu cuenta no tiene permiso para registrar pedidos del equipo.');
                }
            }
            $consulta = $this->db->prepare('SELECT id_pedido, id_cliente, id_usuario_registro FROM pedidos WHERE token_confirmacion = ?');
            $consulta->execute([$token]);
            if ($existente = $consulta->fetch()) {
                $propietario = $existente['id_cliente'] === null ? null : (int) $existente['id_cliente'];
                $responsable = $existente['id_usuario_registro'] === null ? null : (int) $existente['id_usuario_registro'];
                if ($propietario !== $idCliente || $responsable !== $idUsuarioRegistro) {
                    throw new DomainException('El formulario pertenece a otra cuenta. Volvé a abrir el pedido.');
                }
                $this->db->commit();
                return (int) $existente['id_pedido'];
            }
            if ($idCliente !== null) {
                $consulta = $this->db->prepare('SELECT id_cliente FROM clientes
                    WHERE id_cliente = ? AND verificado = 1 LOCK IN SHARE MODE');
                $consulta->execute([$idCliente]);
                if (!$consulta->fetchColumn()) {
                    throw new DomainException('La cuenta ya no está disponible. Volvé a iniciar sesión.');
                }
            }
            $productos = (new Producto($this->db))->buscarVarios(array_keys($cantidades), true);
            if (count($productos) !== count($cantidades)) {
                throw new DomainException('Un producto ya no está disponible. Revisá los productos del pedido.');
            }
            $lineas = [];
            $total = 0;
            foreach ($cantidades as $id => $cantidad) {
                if (!is_int($cantidad) || $cantidad < 1 || $cantidad > self::MAXIMA_CANTIDAD) {
                    throw new DomainException('Las cantidades deben estar entre 1 y 99.');
                }
                $linea = $productos[$id];
                $linea['cantidad'] = $cantidad;
                $total += $linea['precio'] * $cantidad;
                $lineas[] = $linea;
            }
            if (!hash_equals($firmaEsperada, Carrito::firma($lineas))) {
                throw new DomainException('Los productos o sus precios cambiaron. Revisá el pedido antes de confirmar nuevamente.');
            }
            if ($total > 9999999999) {
                throw new DomainException('El total supera el máximo permitido.');
            }
            $consulta = $this->db->prepare('INSERT INTO pedidos
                (id_cliente, id_usuario_registro, cliente_nombre, telefono, fecha_retiro, hora_retiro, notas, total, token_confirmacion)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $consulta->execute([$idCliente, $idUsuarioRegistro, $datos['cliente_nombre'], $datos['telefono'], $datos['fecha_retiro'],
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

    /** Normaliza las líneas del formulario interno sin depender del carrito de la sesión. */
    public static function cantidadesDesdeLineas(array $lineas): array
    {
        if (!array_is_list($lineas) || !$lineas || count($lineas) > self::MAXIMO_PRODUCTOS) {
            throw new DomainException('El pedido admite entre 1 y 50 productos distintos.');
        }
        $cantidades = [];
        foreach ($lineas as $linea) {
            if (!is_array($linea)) throw new DomainException('Revisá los productos del pedido.');
            $id = filter_var($linea['id_producto'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $cantidad = filter_var($linea['cantidad'] ?? null, FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1, 'max_range' => self::MAXIMA_CANTIDAD]]);
            if (!$id || !$cantidad) {
                throw new DomainException('Seleccioná un producto por línea y una cantidad entre 1 y 99.');
            }
            if (isset($cantidades[$id])) {
                throw new DomainException('Hay un producto repetido. Usá una sola línea por producto y ajustá su cantidad.');
            }
            $cantidades[$id] = $cantidad;
        }
        return $cantidades;
    }

    public static function validar(array $datos): void
    {
        foreach (['cliente_nombre' => 120, 'telefono' => 30, 'fecha_retiro' => 10, 'hora_retiro' => 5, 'notas' => 2000] as $campo => $maximo) {
            $texto = $datos[$campo] ?? null;
            if (!is_string($texto) || !mb_check_encoding($texto, 'UTF-8') || mb_strlen($texto) > $maximo
                || ($campo !== 'notas' && trim($texto) === '')) {
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
