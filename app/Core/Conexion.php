<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

final class Conexion
{
    private static ?PDO $conexion = null;

    public static function obtener(): PDO
    {
        if (self::$conexion === null) {
            $configuracion = require dirname(__DIR__, 2) . '/config/database.php';
            self::$conexion = new PDO(
                "mysql:host={$configuracion['host']};port={$configuracion['port']};dbname={$configuracion['database']};charset=utf8mb4",
                $configuracion['user'],
                $configuracion['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ],
            );
        }

        return self::$conexion;
    }
}
