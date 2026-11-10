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
    $columnaModalidad = $conexion->query("SHOW COLUMNS FROM pedidos LIKE 'modalidad_entrega'")->fetch();
    if ($columnaModalidad === false) {
        $conexion->exec(
            "ALTER TABLE pedidos ADD COLUMN modalidad_entrega VARCHAR(20) NOT NULL DEFAULT 'retiro' AFTER hora_retiro",
        );
    }
    $columnaDireccion = $conexion->query("SHOW COLUMNS FROM pedidos LIKE 'direccion_entrega'")->fetch();
    if ($columnaDireccion === false) {
        $conexion->exec(
            "ALTER TABLE pedidos ADD COLUMN direccion_entrega VARCHAR(250) NOT NULL DEFAULT '' AFTER modalidad_entrega",
        );
    }
    $columnasPedido = [
        'metodo_pago' => "ALTER TABLE pedidos ADD COLUMN metodo_pago VARCHAR(20) NOT NULL DEFAULT 'local' AFTER direccion_entrega",
        'estado_pago' => "ALTER TABLE pedidos ADD COLUMN estado_pago VARCHAR(20) NOT NULL DEFAULT 'pendiente' AFTER metodo_pago",
        'pago_confirmado_en' => 'ALTER TABLE pedidos ADD COLUMN pago_confirmado_en DATETIME NULL AFTER estado_pago',
        'id_usuario_pago' => 'ALTER TABLE pedidos ADD COLUMN id_usuario_pago INT UNSIGNED NULL AFTER pago_confirmado_en',
        'transferencia_enviada_en' => 'ALTER TABLE pedidos ADD COLUMN transferencia_enviada_en DATETIME NULL AFTER id_usuario_pago',
        'entregado_en' => 'ALTER TABLE pedidos ADD COLUMN entregado_en DATETIME NULL AFTER id_usuario_pago',
        'id_usuario_entrega' => 'ALTER TABLE pedidos ADD COLUMN id_usuario_entrega INT UNSIGNED NULL AFTER entregado_en',
    ];
    foreach ($columnasPedido as $nombre => $sql) {
        if ($conexion->query("SHOW COLUMNS FROM pedidos LIKE '{$nombre}'")->fetch() === false) {
            $conexion->exec($sql);
        }
    }
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
    $conexion->exec(file_get_contents(__DIR__ . '/comprobantes-transferencia.sql'));
    $estadoEquipo = $conexion->query("SHOW COLUMNS FROM usuarios_equipo LIKE 'estado'")->fetch(PDO::FETCH_ASSOC);
    if ($estadoEquipo !== false && !str_contains((string) $estadoEquipo['Type'], 'desactivado')) {
        $conexion->exec("ALTER TABLE usuarios_equipo MODIFY estado ENUM('pendiente', 'aprobado', 'rechazado', 'desactivado') NOT NULL DEFAULT 'pendiente'");
    }
    foreach ([
        'fk_pedidos_usuario_pago' => 'id_usuario_pago',
        'fk_pedidos_usuario_entrega' => 'id_usuario_entrega',
    ] as $claveForanea => $columna) {
        $consultaForanea = $conexion->prepare('SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?');
        $consultaForanea->execute(['pedidos', $claveForanea]);
        if ((int) $consultaForanea->fetchColumn() === 0) {
            $conexion->exec("ALTER TABLE pedidos ADD CONSTRAINT {$claveForanea}
                FOREIGN KEY ({$columna}) REFERENCES usuarios_equipo (id_usuario) ON DELETE SET NULL");
        }
    }
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
