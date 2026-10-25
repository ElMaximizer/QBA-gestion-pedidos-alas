<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/soporte.php';

use App\Core\Rutas;

$entorno = null;
$comprobaciones = 0;
$correcto = true;

try {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_GET = [];
    foreach (Rutas::todas() as $nombre => [$direccion]) {
        $_SERVER['REQUEST_URI'] = '/' . $direccion;
        comprobar(Rutas::resolver() === $nombre && !Rutas::necesitaRedireccion($nombre), 'Resuelve la dirección pública de ' . $nombre);
    }
    $_SERVER['SCRIPT_NAME'] = '/QBA-gestion-pedidos-alas/public/index.php';
    $_SERVER['REQUEST_URI'] = '/QBA-gestion-pedidos-alas/public/equipo/panel';
    comprobar(Rutas::resolver() === 'equipo-panel'
        && Rutas::url('equipo-login') === '/QBA-gestion-pedidos-alas/public/equipo/login', 'Conserva la carpeta de instalación de XAMPP');
    comprobar(Rutas::recurso('css/panel.css') === '/QBA-gestion-pedidos-alas/public/css/panel.css', 'Los recursos no dependen de la profundidad de la página');
    $_SERVER['SCRIPT_NAME'] = '/Mi proyecto/public/index.php';
    comprobar(Rutas::url('equipo-panel') === '/Mi%20proyecto/public/equipo/panel', 'Codifica las carpetas que contienen espacios');
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    comprobar(Rutas::url('productos', ['q' => 'pan & torta', 'ruta' => 'equipo-panel']) === '/productos?q=pan%20%26%20torta', 'Codifica los filtros y no incorpora otro selector de ruta');
    try {
        Rutas::url('../config/database.php');
        throw new RuntimeException('Aceptó una ruta fuera de la lista.');
    } catch (InvalidArgumentException) {
        comprobar(true, 'Solo genera direcciones de la lista permitida');
    }
    try {
        Rutas::recurso('images/../../config/correo.local.php');
        throw new RuntimeException('Aceptó un recurso fuera de public.');
    } catch (InvalidArgumentException) {
        comprobar(true, 'Rechaza recursos con navegación de directorios');
    }

    $entorno = new EntornoPruebas('equipo');
    $entorno->iniciarServidor();
    $solicitar = $entorno->solicitar(...);
    [$estado, $html] = $solicitar('/equipo/login');
    comprobar($estado === 200 && !str_contains($html, '?ruta=') && !str_contains($html, 'index.php'), 'La página anidada genera enlaces y formularios amigables');
    $documento = new DOMDocument();
    @$documento->loadHTML('<?xml encoding="UTF-8">' . $html);
    $xpath = new DOMXPath($documento);
    foreach ($xpath->query('//link[@rel="stylesheet"]/@href | //script/@src | //img/@src') as $recurso) {
        comprobar(str_starts_with($recurso->nodeValue, '/') && $solicitar($recurso->nodeValue)[0] === 200,
            'Carga el recurso de la página anidada: ' . $recurso->nodeValue);
    }
    comprobar($solicitar('/equipo/panel')[0] === 303, 'La dirección nueva del panel sigue exigiendo acceso del equipo');
    comprobar($solicitar('/equipo/panel', [])[0] === 405, 'La ruta nueva no amplía los métodos permitidos');
    [$estado, $respuesta] = $solicitar('/index.php?ruta=equipo-panel');
    comprobar($estado === 302 && str_contains($respuesta, 'Location: /equipo/panel'), 'Un enlace antiguo lleva a la dirección nueva del panel');
    [$estado, $respuesta] = $solicitar('/index.php?ruta=productos&q=pan%20integral&categoria=Panes');
    comprobar($estado === 302 && str_contains($respuesta, 'Location: /productos?q=pan%20integral&categoria=Panes'), 'La compatibilidad conserva los filtros de catálogo');
    comprobar($solicitar('/index.php?ruta=equipo-login', ['csrf' => 'incorrecto'])[0] === 422, 'Un formulario antiguo conserva la validación de CSRF sin repetirse en una redirección');
    comprobar($solicitar('/equipo/login?ruta=inicio')[0] === 404, 'El parámetro anterior no reemplaza una ruta nueva');
    comprobar($solicitar('/equipo/panel/no-existe')[0] === 404, 'No acepta sufijos de una ruta permitida');
    comprobar($solicitar('/index.php/equipo/panel')[0] === 404, 'No crea una segunda forma de acceso mediante PATH_INFO');
    comprobar($solicitar('/config/correo.local.php')[0] === 404 && $solicitar('/router.php')[0] === 404, 'Las rutas no exponen archivos privados ni el enrutador de desarrollo');
    [$estado, $respuesta] = $solicitar('/equipo/login/');
    comprobar($estado === 302 && str_contains($respuesta, 'Location: /equipo/login'), 'Normaliza la barra final sin alterar el destino');
    [$estado, $catalogo] = $solicitar('/productos?q=integral&categoria=Panes');
    comprobar($estado === 200 && substr_count($catalogo, 'class="producto"') === 1
        && str_contains($catalogo, 'action="/productos"') && !str_contains($catalogo, 'name="ruta"'), 'La búsqueda usa la ruta pública y conserva sus filtros');
    $curl = curl_init($entorno->url() . '/equipo/login');
    curl_setopt_array($curl, [CURLOPT_NOBODY => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_TIMEOUT => 5]);
    $cabeceras = curl_exec($curl);
    comprobar(curl_getinfo($curl, CURLINFO_RESPONSE_CODE) === 200 && !str_contains($cabeceras, '<html'), 'HEAD conserva el estado y no entrega el contenido de la página');
    curl_close($curl);
    comprobar(!preg_match('/Fatal error:|Warning:|Notice:|Deprecated:/', $entorno->errores()), 'Las rutas no generan errores PHP');
    echo "Correcto: $comprobaciones comprobaciones de rutas.\n";
} catch (Throwable $error) {
    $correcto = false;
    fwrite(STDERR, $error->getMessage() . "\n" . $error->getTraceAsString() . "\n");
    fwrite(STDERR, $entorno?->errores() ?? '');
} finally {
    $entorno?->cerrar();
}
exit($correcto ? 0 : 1);
