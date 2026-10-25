<?php
declare(strict_types=1);

// Usa una base y un servidor efímeros. Nunca ejecuta pruebas sobre la base de la aplicación.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Models\Carrito;
use App\Models\Pedido;
use App\Models\Producto;

require __DIR__ . '/soporte.php';

$entorno = null;
$comprobaciones = 0;
$correcto = true;

try {
    $entorno = new EntornoPruebas('hu18');
    $db = $entorno->db;
    $_SESSION = [];
    $productos = new Producto($db);
    $carrito = new Carrito($productos);
    $pedidos = new Pedido($db);
    $datos = ['cliente_nombre' => 'Cliente de prueba HU18', 'telefono' => '099123456',
        'fecha_retiro' => date('Y-m-d', strtotime('+2 days')), 'hora_retiro' => '10:00', 'notas' => 'Prueba automatizada'];

    rechaza(fn () => $carrito->actualizar('agregar', '1', '0'), 'Rechaza cantidad cero');
    rechaza(fn () => $carrito->actualizar('agregar', '1', '1.5'), 'Rechaza cantidad fraccionaria');
    rechaza(fn () => $carrito->actualizar('agregar', '999999', '1'), 'Rechaza producto inexistente');
    $carrito->actualizar('agregar', '1', '2');
    $carrito->actualizar('agregar', '2', '1');
    $firma = Carrito::firma($carrito->articulos());
    comprobar(Carrito::unidades() === 3, 'Suma cantidades del carrito');
    rechaza(fn () => $carrito->actualizar('agregar', '1', '98'), 'Rechaza superar el máximo acumulado');
    foreach (['telefono' => 'abc', 'fecha_retiro' => '2026-02-30', 'hora_retiro' => '25:00', 'cliente_nombre' => '', 'notas' => str_repeat('a', 2001)] as $campo => $valor) {
        rechaza(fn () => Pedido::validar(array_replace($datos, [$campo => $valor])), 'Valida ' . $campo);
    }
    rechaza(fn () => Pedido::validar(array_replace($datos, ['fecha_retiro' => '2000-01-01'])), 'Rechaza retiro pasado');

    $db->exec('UPDATE productos SET precio = 95.25 WHERE id_producto = 1');
    rechaza(fn () => $pedidos->crear($datos, Carrito::cantidades(), $firma, bin2hex(random_bytes(32))), 'Rechaza precio cambiado bajo bloqueo');
    comprobar((int) $db->query('SELECT COUNT(*) FROM pedidos')->fetchColumn() === 0, 'No registra el pedido con precio desactualizado');
    $db->exec('UPDATE productos SET precio = 95.00 WHERE id_producto = 1');
    $db->exec('UPDATE productos SET activo = 0 WHERE id_producto = 2');
    rechaza(fn () => $pedidos->crear($datos, Carrito::cantidades(), $firma, bin2hex(random_bytes(32))), 'Rechaza producto desactivado');
    comprobar($carrito->articulos(false)[1]['disponible'] === false, 'Permite ver y quitar un producto retirado');
    $db->exec('UPDATE productos SET activo = 1 WHERE id_producto = 2');

    $db->exec("CREATE TRIGGER fallo_prueba BEFORE INSERT ON pedido_detalles FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fallo de prueba'");
    try {
        $pedidos->crear($datos, Carrito::cantidades(), $firma, bin2hex(random_bytes(32)));
        throw new RuntimeException('Debió fallar la inserción de detalles');
    } catch (PDOException) {
        comprobar((int) $db->query('SELECT COUNT(*) FROM pedidos')->fetchColumn() === 0, 'Revierte la cabecera si falla un detalle');
    }
    $db->exec('DROP TRIGGER fallo_prueba');
    $token = bin2hex(random_bytes(32));
    $idPedido = $pedidos->crear($datos, Carrito::cantidades(), $firma, $token);
    comprobar($pedidos->crear($datos, Carrito::cantidades(), $firma, $token) === $idPedido, 'Idempotencia del guardado');
    comprobar($db->query('SELECT total FROM pedidos')->fetchColumn() === '255.00', 'Calcula los importes en el servidor');
    comprobar((int) $db->query('SELECT COUNT(*) FROM pedido_detalles')->fetchColumn() === 2, 'Guarda todas las líneas');
    $db->exec("UPDATE productos SET nombre = 'Nombre modificado' WHERE id_producto = 1");
    comprobar($db->query('SELECT nombre_producto FROM pedido_detalles WHERE id_producto = 1')->fetchColumn() === 'Pan integral', 'Conserva el nombre histórico');
    $db->exec("UPDATE productos SET nombre = 'Pan integral' WHERE id_producto = 1");

    $entorno->iniciarServidor();
    $solicitar = $entorno->solicitar(...);
    [$estado] = $solicitar('/');
    comprobar($estado === 200, 'Responde el inicio');
    comprobar($solicitar('index.php?ruta=no-existe')[0] === 404, 'Rechaza rutas inexistentes');
    comprobar($solicitar('index.php?ruta[]=inicio')[0] === 404, 'Rechaza ruta en array');
    comprobar($solicitar('/', [])[0] === 405, 'Rechaza POST sobre el inicio');
    comprobar($solicitar('/pedidos/realizar')[0] === 303, 'No abre el formulario con carrito vacío');
    comprobar($solicitar('/pedidos/confirmado')[0] === 303, 'No muestra confirmaciones ajenas');
    [$estado, $html] = $solicitar('/productos?categoria=Panes&q=integral');
    comprobar($estado === 200 && substr_count($html, 'class="producto"') === 1, 'Filtra productos');
    comprobar($solicitar('/productos?q[]=x')[0] === 422, 'Rechaza búsqueda malformada');
    comprobar($solicitar('/producto?id=999999')[0] === 404, 'Responde 404 para producto inexistente');
    [$estado, $html] = $solicitar('/producto?id=1');
    $csrf = oculto($html, 'csrf');
    $accion = ['csrf' => $csrf, 'accion' => 'agregar', 'id_producto' => '1', 'cantidad' => '2'];
    comprobar($solicitar('/carrito', array_replace($accion, ['csrf' => 'incorrecto']))[0] === 422, 'Exige CSRF al modificar el carrito');
    comprobar($solicitar('/carrito', array_replace($accion, ['cantidad' => ['2']]))[0] === 422, 'Rechaza cantidades malformadas');
    [$estado, $respuesta] = $solicitar('/carrito', $accion);
    comprobar($estado === 303, 'Agrega productos y redirige (HTTP ' . $estado . ')');
    comprobar($solicitar('/carrito', array_replace($accion, ['id_producto' => '2', 'cantidad' => '1']))[0] === 303, 'Agrega un segundo producto');
    comprobar($solicitar('/carrito', array_replace($accion, ['cantidad' => '100']))[0] === 422, 'Valida cantidades en HTTP');
    [, $html] = $solicitar('/pedidos/realizar');
    $campos = $datos + ['csrf' => $csrf, 'pedido_token' => oculto($html, 'pedido_token')];
    comprobar($solicitar('/pedidos/realizar', array_replace($campos, ['csrf' => 'incorrecto']))[0] === 422, 'Exige CSRF al confirmar');
    comprobar($solicitar('/pedidos/realizar', array_replace($campos, ['pedido_token' => 'inexistente']))[0] === 422, 'Exige un formulario de la sesión');
    comprobar($solicitar('/pedidos/realizar', array_replace($campos, ['cliente_nombre' => ['x']]))[0] === 422, 'Rechaza campos en array');
    [, $errorHtml] = $solicitar('/pedidos/realizar', array_replace($campos, ['telefono' => 'abc', 'cliente_nombre' => '<script>prueba</script>']));
    comprobar(str_contains($errorHtml, '&lt;script&gt;prueba&lt;/script&gt;') && !str_contains($errorHtml, '<script>prueba</script>'), 'Conserva y escapa los datos tras un error');
    $antes = (int) $db->query('SELECT COUNT(*) FROM pedidos')->fetchColumn();
    $db->exec('UPDATE productos SET precio = 96.00 WHERE id_producto = 1');
    comprobar($solicitar('/pedidos/realizar', $campos)[0] === 422, 'Detecta un cambio de precio desde otro proceso');
    $db->exec('UPDATE productos SET precio = 95.00 WHERE id_producto = 1');
    $solicitar('/carrito', array_replace($accion, ['accion' => 'actualizar', 'cantidad' => '3']));
    comprobar($solicitar('/pedidos/realizar', $campos)[0] === 422, 'Detecta cambios de carrito entre pestañas');
    $solicitar('/carrito', array_replace($accion, ['accion' => 'actualizar']));
    $db->exec('UPDATE productos SET activo = 0 WHERE id_producto = 2');
    comprobar($solicitar('/pedidos/realizar', $campos)[0] === 422, 'Bloquea productos retirados');
    [, $html] = $solicitar('/carrito');
    comprobar(str_contains($html, 'Producto no disponible'), 'Muestra el producto retirado para quitarlo');
    comprobar($solicitar('/carrito', array_replace($accion, ['accion' => 'quitar', 'id_producto' => '2']))[0] === 303, 'Permite quitar un producto desactivado');
    $db->exec('UPDATE productos SET activo = 1 WHERE id_producto = 2');
    $solicitar('/carrito', array_replace($accion, ['id_producto' => '2', 'cantidad' => '1']));
    comprobar((int) $db->query('SELECT COUNT(*) FROM pedidos')->fetchColumn() === $antes, 'Los intentos inválidos no crean pedidos');
    [, $html] = $solicitar('/pedidos/realizar');
    $campos['pedido_token'] = oculto($html, 'pedido_token');
    $campos += ['total' => '0.01', 'precio' => '1', 'estado' => 'Entregado', 'id_cliente' => '123'];
    comprobar($solicitar('/pedidos/realizar', $campos)[0] === 303, 'Confirma el pedido válido');
    $ultimo = $db->query('SELECT * FROM pedidos ORDER BY id_pedido DESC LIMIT 1')->fetch();
    comprobar($ultimo['total'] === '255.00' && $ultimo['estado'] === 'Pendiente' && $ultimo['id_cliente'] === null, 'Ignora precios, estado e identidad manipulados');
    [$estado, $html] = $solicitar('/pedidos/confirmado');
    comprobar($estado === 200 && str_contains($html, '#' . $ultimo['id_pedido']), 'Muestra el número del pedido guardado');
    comprobar($solicitar('/pedidos/realizar', $campos)[0] === 303, 'Tolera un doble envío del formulario');
    $solicitar('/pedidos/confirmado');
    comprobar((int) $db->query('SELECT COUNT(*) FROM pedidos')->fetchColumn() === $antes + 1, 'No duplica al reenviar o recargar');
    [, $html] = $solicitar('/carrito');
    comprobar(str_contains($html, 'Tu carrito está vacío'), 'Vacía el carrito después de guardar');
    comprobar(!preg_match('/Fatal error:|Warning:|Notice:/', $entorno->errores()), 'No emite errores PHP durante el recorrido');
    echo "Correcto: $comprobaciones comprobaciones de HU18.\n";
} catch (Throwable $error) {
    $correcto = false;
    fwrite(STDERR, $error->getMessage() . "\n" . $error->getTraceAsString() . "\n");
    fwrite(STDERR, $entorno?->errores() ?? '');
} finally {
    $entorno?->cerrar();
}
exit($correcto ? 0 : 1);
