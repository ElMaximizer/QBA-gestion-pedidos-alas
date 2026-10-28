<?php
declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;

final class Rutas
{
    private static ?array $rutas = null;

    public static function todas(): array
    {
        return self::$rutas ??= require dirname(__DIR__, 2) . '/config/rutas.php';
    }

    public static function url(string $nombre, array $parametros = []): string
    {
        $ruta = self::todas()[$nombre][0] ?? throw new InvalidArgumentException('Ruta desconocida.');
        unset($parametros['ruta']);
        $consulta = http_build_query($parametros, '', '&', PHP_QUERY_RFC3986);
        return self::base() . '/' . $ruta . ($consulta === '' ? '' : '?' . $consulta);
    }

    public static function recurso(string $archivo): string
    {
        if (!preg_match('~^(?:css|js|images)/[a-zA-Z0-9._/-]+$~D', $archivo)
            || in_array('..', explode('/', $archivo), true)) {
            throw new InvalidArgumentException('Recurso inválido.');
        }
        return self::base() . '/' . $archivo;
    }

    public static function resolver(): ?string
    {
        $camino = self::camino();
        if ($camino === null) {
            return null;
        }
        if (array_key_exists('ruta', $_GET)) {
            // Compatibilidad solo en el punto de entrada anterior; nunca cambia una URL amigable.
            $nombre = $_GET['ruta'];
            return in_array($camino, ['', 'index.php'], true) && is_string($nombre)
                && isset(self::todas()[$nombre]) ? $nombre : null;
        }
        if ($camino === 'index.php') {
            return 'inicio';
        }
        foreach (self::todas() as $nombre => [$direccion]) {
            if ($camino === $direccion) {
                return $nombre;
            }
        }
        return null;
    }

    public static function necesitaRedireccion(string $nombre): bool
    {
        $camino = explode('?', $_SERVER['REQUEST_URI'] ?? '/', 2)[0];
        return array_key_exists('ruta', $_GET) || $camino !== self::url($nombre);
    }

    private static function camino(): ?string
    {
        $camino = explode('?', $_SERVER['REQUEST_URI'] ?? '/', 2)[0];
        $base = self::base();
        if (!str_starts_with($camino, $base . '/')) {
            return null;
        }
        return rtrim(substr($camino, strlen($base) + 1), '/');
    }

    private static function base(): string
    {
        // SCRIPT_NAME es el archivo ejecutado por el servidor, no el Host enviado por el navegador.
        $directorio = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        if ($directorio === '.' || $directorio === '/') {
            return '';
        }
        return '/' . implode('/', array_map('rawurlencode', explode('/', trim(rawurldecode($directorio), '/'))));
    }
}
