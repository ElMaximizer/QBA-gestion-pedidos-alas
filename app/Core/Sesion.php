<?php
declare(strict_types=1);

namespace App\Core;

use DomainException;

final class Sesion
{
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
