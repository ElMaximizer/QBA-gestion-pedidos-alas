<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$config = require dirname(__DIR__) . '/config/database.php';
if (!preg_match('/^[a-zA-Z0-9_]+$/', $config['database'])) {
    fwrite(STDERR, "Nombre de base inválido. Usá letras, números y guion bajo.\n");
    exit(1);
}
try {
    $db = new PDO("mysql:host={$config['host']};port={$config['port']};charset=utf8mb4",
        $config['user'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $db->exec("CREATE DATABASE IF NOT EXISTS `{$config['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $db->exec("USE `{$config['database']}`");
    $db->exec(file_get_contents(__DIR__ . '/estructura.sql'));
    $db->exec(file_get_contents(__DIR__ . '/contacto.sql'));
    $columnaSena = $db->query("SHOW COLUMNS FROM pedidos LIKE 'sena_recibida'")->fetch();
    if ($columnaSena === false) {
        $db->exec('ALTER TABLE pedidos ADD COLUMN sena_recibida TINYINT(1) NOT NULL DEFAULT 0 AFTER estado');
    }
    $db->exec(file_get_contents(__DIR__ . '/clientes.sql'));
    $db->exec(file_get_contents(__DIR__ . '/acceso-clientes.sql'));
    $db->exec(file_get_contents(__DIR__ . '/equipo.sql'));
    $db->exec(file_get_contents(__DIR__ . '/pedidos-equipo.sql'));
    $db->exec(file_get_contents(__DIR__ . '/pedido-cambios.sql'));
    $db->exec(file_get_contents(__DIR__ . '/entregas-mercaderia.sql'));
    $db->exec(file_get_contents(__DIR__ . '/acceso-equipo.sql'));
    if (in_array('--demo', $argv, true)) {
        $db->exec(file_get_contents(__DIR__ . '/productos_demo.sql'));
    }
    echo "Base {$config['database']} preparada.\n";
} catch (Throwable $error) {
    fwrite(STDERR, "No se pudo preparar la base: {$error->getMessage()}\n");
    exit(1);
}
