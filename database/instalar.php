<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}

$configuracion = require dirname(__DIR__) . '/config/database.php';
if (!preg_match('/^[a-zA-Z0-9_]+$/', $configuracion['database'])) {
    fwrite(STDERR, "Nombre de base inválido. Usá letras, números y guion bajo.\n");
    exit(1);
}
try {
    $conexion = new PDO(
        "mysql:host={$configuracion['host']};port={$configuracion['port']};charset=utf8mb4",
        $configuracion['user'],
        $configuracion['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
    );
    $conexion->exec(
        "CREATE DATABASE IF NOT EXISTS `{$configuracion['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
    );
    $conexion->exec("USE `{$configuracion['database']}`");
    $conexion->exec(file_get_contents(__DIR__ . '/estructura.sql'));
    $conexion->exec(file_get_contents(__DIR__ . '/contenido-inicio.sql'));
    $conexion->exec(file_get_contents(__DIR__ . '/contacto.sql'));
    $columnaSena = $conexion->query("SHOW COLUMNS FROM pedidos LIKE 'sena_recibida'")->fetch();
    if ($columnaSena === false) {
        $conexion->exec(
            'ALTER TABLE pedidos ADD COLUMN sena_recibida TINYINT(1) NOT NULL DEFAULT 0 AFTER estado',
        );
    }
    $conexion->exec(file_get_contents(__DIR__ . '/clientes.sql'));
    $conexion->exec(file_get_contents(__DIR__ . '/acceso-clientes.sql'));
    $conexion->exec(file_get_contents(__DIR__ . '/equipo.sql'));
    $conexion->exec(file_get_contents(__DIR__ . '/pedidos-equipo.sql'));
    $conexion->exec(file_get_contents(__DIR__ . '/pedido-cambios.sql'));
    $conexion->exec(file_get_contents(__DIR__ . '/entregas-mercaderia.sql'));
    $conexion->exec(file_get_contents(__DIR__ . '/acceso-equipo.sql'));
    if (in_array('--demo', $argv, true)) {
        $conexion->exec(file_get_contents(__DIR__ . '/productos_demo.sql'));
    }
    echo "Base {$configuracion['database']} preparada.\n";
} catch (Throwable $error) {
    fwrite(STDERR, "No se pudo preparar la base: {$error->getMessage()}\n");
    exit(1);
}
