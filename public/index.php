<?php
declare(strict_types=1);

use App\Controllers\InicioController;

header('Content-Type: text/html; charset=UTF-8');

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($metodo, ['GET', 'HEAD'], true)) {
    http_response_code(405);
    header('Allow: GET, HEAD');
    exit('Método no permitido.');
}

// Las pantallas se seleccionan por rutas permitidas, nunca por nombres de archivo.
$ruta = $_GET['ruta'] ?? 'inicio';
$rutaAdicional = $_SERVER['PATH_INFO'] ?? '';
if (!is_string($ruta) || $ruta !== 'inicio'
    || !in_array($rutaAdicional, ['', '/'], true)) {
    http_response_code(404);
    exit('Página no encontrada.');
}

require_once dirname(__DIR__) . '/app/Controllers/InicioController.php';

(new InicioController())->index();
