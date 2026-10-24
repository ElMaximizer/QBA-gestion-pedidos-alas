<?php
declare(strict_types=1);

namespace App\Core;

use DomainException;

final class Http
{
    public static function texto(array $origen, string $nombre, string $predeterminado = ''): string
    {
        return trim(self::cadena($origen, $nombre, $predeterminado));
    }

    // Las contraseñas conservan sus espacios; solamente los campos de texto se recortan.
    public static function cadena(array $origen, string $nombre, string $predeterminado = ''): string
    {
        $valor = $origen[$nombre] ?? $predeterminado;
        if (!is_string($valor)) {
            throw new DomainException('Revisá los datos del formulario.');
        }
        return $valor;
    }

    public static function redirigir(string $ruta): never
    {
        header('Location: index.php?' . http_build_query(['ruta' => $ruta]), true, 303);
        exit;
    }
}
