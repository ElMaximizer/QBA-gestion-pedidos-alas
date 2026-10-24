<?php
declare(strict_types=1);

use App\Controllers\AccesoController;
use App\Controllers\CarritoController;
use App\Controllers\InicioController;
use App\Controllers\PedidoController;
use App\Controllers\ProductoController;
use App\Controllers\RegistroController;
use App\Core\Sesion;
use App\Core\Vista;

require dirname(__DIR__) . '/app/bootstrap.php';

header('Content-Type: text/html; charset=UTF-8');

// Los nombres de ruta nunca se convierten en rutas de archivos del usuario.
$rutas = [
    'inicio' => [InicioController::class, 'index', ['GET', 'HEAD']],
    'productos' => [ProductoController::class, 'index', ['GET', 'HEAD']],
    'producto' => [ProductoController::class, 'ver', ['GET', 'HEAD']],
    'carrito' => [CarritoController::class, 'index', ['GET', 'HEAD', 'POST']],
    'realizar-pedido' => [PedidoController::class, 'crear', ['GET', 'HEAD', 'POST']],
    'pedido-confirmado' => [PedidoController::class, 'confirmado', ['GET', 'HEAD']],
    'registro' => [RegistroController::class, 'index', ['GET', 'HEAD', 'POST']],
    'verificar-cuenta' => [RegistroController::class, 'verificar', ['GET', 'HEAD', 'POST']],
    'registro-completado' => [RegistroController::class, 'completado', ['GET', 'HEAD']],
    'cliente-login' => [AccesoController::class, 'index', ['GET', 'HEAD', 'POST']],
    'cerrar-sesion' => [AccesoController::class, 'salir', ['POST']],
];
$ruta = $_GET['ruta'] ?? 'inicio';
$rutaAdicional = $_SERVER['PATH_INFO'] ?? '';
if (!is_string($ruta) || !isset($rutas[$ruta])
    || !in_array($rutaAdicional, ['', '/'], true)) {
    http_response_code(404);
    exit('Página no encontrada.');
}

[$controlador, $accion, $metodos] = $rutas[$ruta];
if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', $metodos, true)) {
    http_response_code(405);
    header('Allow: ' . implode(', ', $metodos));
    exit('Método no permitido.');
}

try {
    Sesion::cliente();
    (new $controlador())->$accion();
} catch (DomainException $error) {
    http_response_code(422);
    Vista::mostrar('error', ['tituloPagina' => 'Revisá la solicitud', 'mensaje' => $error->getMessage()]);
} catch (Throwable $error) {
    error_log((string) $error);
    http_response_code(503);
    Vista::mostrar('error', ['tituloPagina' => 'Servicio no disponible',
        'mensaje' => 'No pudimos completar la operación en este momento. Intentá nuevamente en unos minutos.']);
}
