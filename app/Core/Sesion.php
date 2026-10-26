<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\AccesoCliente;
use App\Models\AccesoEquipo;
use DomainException;
use RuntimeException;

final class Sesion
{
    private static bool $clienteConsultado = false;
    private static ?array $clienteActual = null;
    private static bool $equipoConsultado = false;
    private static ?array $equipoActual = null;

    public static function equipo(): ?array
    {
        if (self::$equipoConsultado) {
            return self::$equipoActual;
        }
        self::$equipoConsultado = true;
        $idUsuario = $_SESSION['qba']['equipo_id'] ?? null;
        if (!is_int($idUsuario) || $idUsuario < 1) {
            return null;
        }
        if (($_SESSION['qba']['equipo_ultimo_acceso'] ?? 0) < time() - 1800) {
            self::cerrarEquipo();
            return null;
        }
        self::$equipoActual = (new AccesoEquipo(Conexion::obtener()))->buscarAprobado($idUsuario);
        if (self::$equipoActual === null) {
            self::cerrarEquipo();
            return null;
        }
        $_SESSION['qba']['equipo_ultimo_acceso'] = time();
        return self::$equipoActual;
    }

    public static function requerirEquipo(): array
    {
        return self::equipo() ?? Http::redirigir('equipo-login');
    }

    public static function administraProductos(): bool
    {
        return in_array(self::equipo()['rol'] ?? null, ['administrador', 'dueno'], true);
    }

    public static function iniciarEquipo(int $idUsuario): void
    {
        self::renovarIdentidad();
        $_SESSION['qba']['equipo_id'] = $idUsuario;
        $_SESSION['qba']['equipo_ultimo_acceso'] = time();
    }

    public static function cerrarEquipo(): void
    {
        self::renovarIdentidad();
    }

    public static function cliente(): ?array
    {
        if (self::$clienteConsultado) {
            return self::$clienteActual;
        }
        self::$clienteConsultado = true;
        $idCliente = $_SESSION['qba']['cliente_id'] ?? null;
        if (!is_int($idCliente) || $idCliente < 1) {
            return null;
        }
        if (($_SESSION['qba']['cliente_ultimo_acceso'] ?? 0) < time() - 1800) {
            self::cerrarCliente();
            return null;
        }
        self::$clienteActual = (new AccesoCliente(Conexion::obtener()))->buscarVerificado($idCliente);
        if (self::$clienteActual === null) {
            self::cerrarCliente();
            return null;
        }
        $_SESSION['qba']['cliente_ultimo_acceso'] = time();
        return self::$clienteActual;
    }

    public static function iniciarCliente(int $idCliente): void
    {
        self::renovarIdentidad();
        $_SESSION['qba']['cliente_id'] = $idCliente;
        $_SESSION['qba']['cliente_ultimo_acceso'] = time();
    }

    public static function cerrarCliente(): void
    {
        self::renovarIdentidad();
    }

    private static function renovarIdentidad(): void
    {
        if (!session_regenerate_id(true)) {
            throw new RuntimeException('No se pudo renovar la sesión.');
        }
        $carrito = $_SESSION['qba']['carrito'] ?? [];
        // Solo el carrito pasa a la identidad nueva; formularios y confirmaciones se invalidan.
        $_SESSION['qba'] = ['carrito' => $carrito, 'csrf' => bin2hex(random_bytes(32))];
        self::$clienteActual = null;
        self::$clienteConsultado = false;
        self::$equipoActual = null;
        self::$equipoConsultado = false;
    }

    public static function csrf(): string
    {
        return $_SESSION['qba']['csrf'] ??= bin2hex(random_bytes(32));
    }

    public static function validarCsrf(mixed $token): void
    {
        if (!is_string($token) || !hash_equals(self::csrf(), $token)) {
            throw new DomainException('La sesión del formulario venció. Recargá la página e intentá nuevamente.');
        }
    }

    public static function nuevoFormulario(string $firma): string
    {
        $formularios = array_filter($_SESSION['qba']['formularios'] ?? [],
            static fn (array $formulario): bool => $formulario['creado'] >= time() - 1800);
        $formularios = array_slice($formularios, -9, null, true);
        $token = bin2hex(random_bytes(32));
        $formularios[$token] = ['creado' => time(), 'firma' => $firma, 'pedido' => null];
        $_SESSION['qba']['formularios'] = $formularios;
        return $token;
    }

    public static function formulario(string $token): array
    {
        $formulario = $_SESSION['qba']['formularios'][$token] ?? null;
        if (!$formulario || $formulario['creado'] < time() - 1800) {
            throw new DomainException('El formulario venció. Volvé al carrito para revisar y confirmar el pedido.');
        }
        return $formulario;
    }

    public static function confirmar(string $token, int $idPedido): void
    {
        $_SESSION['qba']['formularios'][$token]['pedido'] = $idPedido;
        $_SESSION['qba']['pedido_confirmado'] = $idPedido;
    }
}
