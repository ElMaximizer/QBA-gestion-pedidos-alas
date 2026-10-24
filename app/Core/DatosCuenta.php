<?php
declare(strict_types=1);

namespace App\Core;

use DomainException;

/** Reglas compartidas por el registro de clientes y del equipo. */
final class DatosCuenta
{
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
