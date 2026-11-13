<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}

$configuracion = require dirname(__DIR__) . '/config/database.php';
if (!preg_match('/^[a-zA-Z0-9_]+$/', (string) ($configuracion['database'] ?? ''))) {
    fwrite(STDERR, "Nombre de base inválido. Revisá config/database.php.\n");
    exit(1);
}

try {
    $conexion = new PDO(
        "mysql:host={$configuracion['host']};port={$configuracion['port']};charset=utf8mb4",
        $configuracion['user'],
        $configuracion['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
    );
    $base = $configuracion['database'];
    $conexion->exec("USE `{$base}`");
    $conexion->exec(file_get_contents(__DIR__ . '/comprobantes-transferencia.sql'));

    $columna = $conexion->query("SHOW COLUMNS FROM comprobantes_transferencia LIKE 'estado_revision'")->fetch();
    if ($columna === false) {
        $conexion->exec("ALTER TABLE comprobantes_transferencia
            ADD COLUMN estado_revision ENUM('pendiente', 'aprobada', 'rechazada')
            NOT NULL DEFAULT 'pendiente' AFTER enviado_en");
    }

    $conexion->exec("UPDATE comprobantes_transferencia c
        INNER JOIN pedidos p ON p.id_pedido = c.id_pedido
        SET c.estado_revision = 'rechazada'
        WHERE p.metodo_pago = 'transferencia' AND p.estado_pago = 'pendiente'
            AND p.transferencia_enviada_en IS NULL AND c.estado_revision = 'pendiente'");
    $conexion->exec("UPDATE comprobantes_transferencia c
        INNER JOIN pedidos p ON p.id_pedido = c.id_pedido
        SET c.estado_revision = 'aprobada'
        WHERE p.metodo_pago = 'transferencia' AND p.estado_pago = 'pagado'
            AND c.estado_revision = 'pendiente'");

    echo "Estado de revisión de transferencias preparado.\n";
} catch (Throwable $error) {
    fwrite(STDERR, "No se pudo actualizar la base: {$error->getMessage()}\n");
    exit(1);
}
