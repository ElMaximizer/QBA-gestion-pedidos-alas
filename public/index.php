<?php
declare(strict_types=1);

use App\Core\Rutas;
use App\Core\Sesion;
use App\Core\Vista;

require dirname(__DIR__) . '/app/bootstrap.php';

header('Content-Type: text/html; charset=UTF-8');

// Solo se ejecutan controladores y métodos de la lista cerrada de configuración.
$ruta = Rutas::resolver();
if ($ruta === null) {
    http_response_code(404);
    exit('Página no encontrada.');
}

[, $controlador, $accion, $metodos] = Rutas::todas()[$ruta];
if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', $metodos, true)) {
    http_response_code(405);
    header('Allow: ' . implode(', ', $metodos));
    exit('Método no permitido.');
}

if (in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true) && Rutas::necesitaRedireccion($ruta)) {
    header('Location: ' . Rutas::url($ruta, $_GET), true, 302);
    exit;
}

try {
    Sesion::cliente();
    Sesion::equipo();
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
