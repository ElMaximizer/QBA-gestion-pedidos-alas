<?php
declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;

final class Rutas
{
    private static ?array $rutas = null;

    public static function todas(): array
    {
        if (self::$rutas === null) {
            self::$rutas = require dirname(__DIR__, 2) . '/config/rutas.php';
        }

        return self::$rutas;
    }

    public static function url(string $nombre, array $parametros = []): string
    {
        $rutas = self::todas();
        if (!isset($rutas[$nombre][0])) {
            throw new InvalidArgumentException('Ruta desconocida.');
        }

        $ruta = $rutas[$nombre][0];
        unset($parametros['ruta']);
        $consulta = http_build_query($parametros, '', '&', PHP_QUERY_RFC3986);
        $direccion = self::base() . '/' . $ruta;

        if ($consulta !== '') {
            $direccion .= '?' . $consulta;
        }

        return $direccion;
    }

    public static function recurso(string $archivo): string
    {
        if (
            !preg_match('~^(?:css|js|images)/[a-zA-Z0-9._/-]+$~D', $archivo) ||
            in_array('..', explode('/', $archivo), true)
        ) {
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
            $esEntradaAnterior = in_array($camino, ['', 'index.php'], true);
            if ($esEntradaAnterior && is_string($nombre) && isset(self::todas()[$nombre])) {
                return $nombre;
            }

            return null;
        }
        if ($camino === 'index.php') {
            return 'inicio';
        }
        foreach (self::todas() as $nombre => $configuracionRuta) {
            if ($camino === $configuracionRuta[0]) {
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
        $partes = explode('/', trim(rawurldecode($directorio), '/'));
        $partesCodificadas = [];
        foreach ($partes as $parte) {
            $partesCodificadas[] = rawurlencode($parte);
        }

        return '/' . implode('/', $partesCodificadas);
    }
}
