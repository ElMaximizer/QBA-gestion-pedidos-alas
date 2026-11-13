<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/soporte.php';

$entorno = null;
$comprobaciones = 0;
$correcto = true;

try {
    $entorno = new EntornoPruebas('hu18');
    $entorno->iniciarServidor();
    $solicitar = static function (?array $campos = null) use ($entorno): array {
        $curl = curl_init($entorno->url() . '/carrito');
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => 5, CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_COOKIE => 'QBA_SESSION=' . $entorno->idSesion()]);
        if ($campos !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($campos));
        }
        $respuesta = curl_exec($curl);
        $estado = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $longitud = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        curl_close($curl);
        if (!is_string($respuesta)) throw new RuntimeException('No respondió el carrito.');
        return [$estado, json_decode(substr($respuesta, $longitud), true, 512, JSON_THROW_ON_ERROR),
            substr($respuesta, 0, $longitud)];
    };

    [$estado, $datos, $cabeceras] = $solicitar();
    comprobar($estado === 200 && $datos['unidades'] === 0
        && str_contains($datos['html'], 'Tu carrito está vacío') && !str_contains($datos['html'], '<html'),
        'El carrito vacío devuelve únicamente su contenido');
    comprobar(str_contains($cabeceras, 'Content-Type: application/json')
        && str_contains($cabeceras, 'Cache-Control: no-store') && str_contains($cabeceras, 'Vary: Accept'),
        'La respuesta de sesión no se almacena en caché y distingue HTML de JSON');
    $accion = ['csrf' => $datos['csrf'], 'accion' => 'agregar', 'id_producto' => '1', 'cantidad' => '2'];
    [$estado, $datos] = $solicitar(array_replace($accion, ['csrf' => 'incorrecto']));
    comprobar($estado === 422 && $datos['unidades'] === 0 && $datos['error'] !== '', 'JSON mantiene la protección CSRF');
    [$estado, $datos] = $solicitar($accion);
    comprobar($estado === 200 && $datos['unidades'] === 2 && str_contains($datos['html'], '$ 190'),
        'Agregar devuelve cantidades y total sin redirección');
    [$estado, $datos] = $solicitar(array_replace($accion, ['cantidad' => ['2']]));
    comprobar($estado === 422 && $datos['unidades'] === 2, 'Un dato malformado no altera el carrito');
    [$estado, $datos] = $solicitar(array_replace($accion, ['accion' => 'actualizar', 'cantidad' => '4', 'precio' => '1']));
    comprobar($estado === 200 && $datos['unidades'] === 4 && str_contains($datos['html'], '$ 380'),
        'Actualizar calcula el precio en el servidor');
    $nombre = '<img src=x onerror=alert(1)>';
    $entorno->db->prepare('UPDATE productos SET nombre = ? WHERE id_producto = 1')->execute([$nombre]);
    [, $datos] = $solicitar();
    comprobar(str_contains($datos['html'], '&lt;img src=x onerror=alert(1)&gt;')
        && !str_contains($datos['html'], $nombre), 'El contenido del panel escapa nombres de productos');
    $entorno->db->exec('UPDATE productos SET activo = 0 WHERE id_producto = 1');
    [, $datos] = $solicitar();
    comprobar($datos['error'] !== '' && str_contains($datos['html'], 'Producto no disponible')
        && !str_contains($datos['html'], 'href="/pedidos/realizar"'), 'Un producto retirado bloquea continuar');
    [$estado, $datos] = $solicitar(array_replace($accion, ['accion' => 'quitar']));
    comprobar($estado === 200 && $datos['unidades'] === 0, 'Se puede quitar un producto retirado');
    $entorno->db->exec('UPDATE productos SET activo = 1 WHERE id_producto = 1');
    $solicitar($accion);
    [$estado, $datos] = $solicitar(['csrf' => $accion['csrf'], 'accion' => 'vaciar']);
    comprobar($estado === 200 && $datos['unidades'] === 0 && str_contains($datos['html'], 'Tu carrito está vacío'),
        'Vaciar recupera el estado vacío');
    comprobar($entorno->solicitar('/carrito', $accion)[0] === 303, 'El formulario sin JavaScript conserva su redirección');
    [$estado, $respuesta] = $entorno->solicitar('/pedidos/realizar');
    comprobar($estado === 303 && str_contains($respuesta, 'Location: /clientes/login')
        && $entorno->sesion()['carrito'] === [1 => 2], 'Continuar sigue exigiendo acceso y conserva los productos');
    comprobar(!preg_match('/Fatal error:|Warning:|Notice:|Deprecated:/', $entorno->errores()), 'No genera errores PHP');
    echo "Correcto: $comprobaciones comprobaciones del carrito en panel.\n";
} catch (Throwable $error) {
    $correcto = false;
    fwrite(STDERR, $error->getMessage() . "\n" . $error->getTraceAsString() . "\n");
    fwrite(STDERR, $entorno?->errores() ?? '');
} finally {
    $entorno?->cerrar();
}
exit($correcto ? 0 : 1);
