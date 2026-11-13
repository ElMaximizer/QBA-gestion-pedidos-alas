<?php
declare(strict_types=1);

// Enrutador exclusivo del servidor de desarrollo de PHP.
if (PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    exit;
}

$camino = rawurldecode(explode('?', $_SERVER['REQUEST_URI'], 2)[0]);
$archivo = realpath(__DIR__ . '/' . ltrim($camino, '/'));
if ($archivo !== false && str_starts_with($archivo, __DIR__ . DIRECTORY_SEPARATOR) && is_file($archivo)
    && in_array(strtolower(pathinfo($archivo, PATHINFO_EXTENSION)), ['css', 'js', 'svg', 'png', 'jpg', 'jpeg', 'webp', 'ico'], true)) {
    return false;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
