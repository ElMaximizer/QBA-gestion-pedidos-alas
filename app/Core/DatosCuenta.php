<?php
declare(strict_types=1);

namespace App\Core;

use DomainException;

/** Reglas compartidas por el registro de clientes y del equipo. */
final class DatosCuenta
{
    // Evita una salida inmediata al comprobar una identidad inexistente.
    private const HASH_INEXISTENTE = '$2y$10$mE29SiS9Om07yVEj3hV8yOq6ANKnjGXXhghK9a9lzzxIKuy4CoAMW';

    public static function cedula(string $cedula): int
    {
        $cedula = trim($cedula);
        if (!preg_match('/^[0-9]{6,8}$/D', $cedula)) {
            throw new DomainException('Ingresá una cédula de 6 a 8 dígitos, sin puntos ni guiones.');
        }
        return (int) $cedula;
    }

    public static function passwordCorrecto(string $password, ?string $hash): bool
    {
        $valido = $password !== '' && strlen($password) <= 72 && !str_contains($password, "\0");
        $coincide = password_verify($valido ? $password : '', $hash ?? self::HASH_INEXISTENTE);
        return $valido && $hash !== null && $coincide;
    }

    public static function nombre(string $nombre): string
    {
        $nombre = trim($nombre);
        if ($nombre === '' || !mb_check_encoding($nombre, 'UTF-8') || mb_strlen($nombre) > 120) {
            throw new DomainException('Ingresá tu nombre completo (máximo 120 caracteres).');
        }
        return $nombre;
    }

    public static function email(string $email): string
    {
        $email = strtolower(trim($email));
        if (strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('Ingresá un correo electrónico válido.');
        }
        return $email;
    }

    public static function hashPassword(string $password, string $confirmacion): string
    {
        // Bcrypt en PHP 8.2 trunca a 72 bytes: se rechazan valores que excedan ese límite.
        if (mb_strlen($password) < 8 || strlen($password) > 72 || str_contains($password, "\0")) {
            throw new DomainException('Usá una contraseña de al menos 8 caracteres y de un máximo de 72 bytes.');
        }
        if ($password !== $confirmacion) {
            throw new DomainException('Las contraseñas no coinciden. Volvé a confirmarla.');
        }
        return password_hash($password, PASSWORD_DEFAULT);
    }
}
