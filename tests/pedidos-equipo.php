<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/soporte.php';

use App\Models\Carrito;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\UsuarioEquipo;

$entorno = null;
$comprobaciones = 0;
$correcto = true;
try {
    $entorno = new EntornoPruebas('hu01');
    $db = $entorno->db;
    $modelo = new Pedido($db);
    $usuarios = new UsuarioEquipo($db);
    $password = 'PedidoEquipoPrueba01!';
    $cuentas = [];
    foreach (['administrador' => '7200001', 'dueno' => '7200002', 'empleado' => '7200003'] as $rol => $cedula) {
        $cuentas[$rol] = $usuarios->solicitar($rol, $cedula, $rol . '@example.test', $password, $password);
        $db->prepare("UPDATE usuarios_equipo SET rol = ?, estado = 'aprobado' WHERE id_usuario = ?")
            ->execute([$rol, $cuentas[$rol]['id_usuario']]);
    }
    $admin = $cuentas['administrador']['id_usuario'];
    $datos = ['cliente_nombre' => 'Cliente de mostrador HU01', 'telefono' => '099123456',
        'fecha_retiro' => date('Y-m-d', strtotime('+2 days')), 'hora_retiro' => '10:00', 'notas' => 'Sin cuenta web'];
    $lineas = [['id_producto' => '1', 'cantidad' => '2'], ['id_producto' => '2', 'cantidad' => '1']];
    $cantidades = Pedido::cantidadesDesdeLineas($lineas);
    $productos = (new Producto($db))->buscarVarios([1, 2]);
    foreach ($cantidades as $id => $cantidad) $productos[$id]['cantidad'] = $cantidad;
    $firma = Carrito::firma($productos);
    foreach ([[], [['id_producto' => '1', 'cantidad' => '0']], [['id_producto' => '1', 'cantidad' => '100']],
        [['id_producto' => '1', 'cantidad' => '1.5']], [['id_producto' => ['1'], 'cantidad' => '1']],
        [$lineas[0], $lineas[0]], array_fill(0, 51, $lineas[0])] as $invalidas) {
        rechaza(fn () => Pedido::cantidadesDesdeLineas($invalidas), 'Rechaza cantidades, duplicados y detalles inválidos');
    }
    $token = bin2hex(random_bytes(32));
    $id = $modelo->crear($datos, $cantidades, $firma, $token, null, $admin);
    $guardado = $db->query('SELECT * FROM pedidos WHERE id_pedido = ' . $id)->fetch();
    comprobar($guardado['total'] === '255.00' && $guardado['estado'] === 'Pendiente'
        && $guardado['id_cliente'] === null && (int) $guardado['id_usuario_registro'] === $admin,
        'Guarda el pedido interno con total del catálogo, sin cuenta de cliente y con responsable');
    comprobar($modelo->crear($datos, $cantidades, $firma, $token, null, $admin) === $id,
        'Reintentar el mismo formulario devuelve el pedido existente');
    rechaza(fn () => $modelo->crear($datos, $cantidades, $firma, $token, null, $cuentas['dueno']['id_usuario']),
        'Otra cuenta no puede reutilizar el token guardado');
    rechaza(fn () => $modelo->crear($datos, $cantidades, $firma, bin2hex(random_bytes(32)), null, $cuentas['empleado']['id_usuario']),
        'HU01 no concede a empleados los permisos pendientes de HU11');
    rechaza(fn () => $modelo->crear($datos, $cantidades, $firma, bin2hex(random_bytes(32)), 1, $admin),
        'No mezcla identidades de cliente y de equipo');
    $db->exec("UPDATE usuarios_equipo SET estado = 'pendiente' WHERE id_usuario = " . $admin);
    rechaza(fn () => $modelo->crear($datos, $cantidades, $firma, bin2hex(random_bytes(32)), null, $admin),
        'El modelo vuelve a comprobar la aprobación del responsable');
    $db->exec("UPDATE usuarios_equipo SET estado = 'aprobado' WHERE id_usuario = " . $admin);
    [$codigo] = $entorno->cli('database/instalar.php', []);
    comprobar($codigo === 0 && (int) $db->query('SELECT COUNT(*) FROM pedidos')->fetchColumn() === 1,
        'La instalación y la migración se pueden repetir sin modificar los pedidos');

    $entorno->iniciarServidor();
    $solicitar = $entorno->solicitar(...);
    [$estado, $respuesta] = $solicitar('/equipo/pedidos/registrar');
    comprobar($estado === 303 && str_contains($respuesta, 'Location: /equipo/login'), 'Exige una sesión del equipo');
    comprobar($solicitar('/equipo/pedidos/registrar', $datos + ['rol' => 'administrador'])[0] === 303,
        'Un POST anónimo no registra pedidos aunque envíe un rol');
    $clientes = new Cliente($db);
    $cliente = $clientes->registrar('Cliente web', 'cliente@example.test', $password, $password);
    $clientes->verificar($cliente['email'], $cliente['codigo']);
    $entorno->sesion(static fn (array $sesion): array => $sesion + ['cliente_id' => $cliente['id_cliente'], 'cliente_ultimo_acceso' => time()]);
    comprobar($solicitar('/equipo/pedidos/registrar')[0] === 303, 'La cuenta de cliente no habilita el formulario interno');
    $entrar = static function (string $cedula) use ($solicitar, $password): void {
        $csrf = oculto($solicitar('/equipo/login')[1], 'csrf');
        comprobar($solicitar('/equipo/login', ['csrf' => $csrf, 'cedula' => $cedula, 'password' => $password])[0] === 303,
            'Ingresa la cuenta del equipo de prueba');
    };
    $salir = static fn () => $solicitar('/equipo/salir', ['csrf' => $entorno->sesion()['csrf']]);
    $entrar('7200003');
    comprobar($solicitar('/equipo/pedidos/registrar')[0] === 403
        && $solicitar('/equipo/pedidos/registrar', $datos + ['rol' => 'dueno'])[0] === 403,
        'El empleado no abre ni envía el formulario de HU01');
    comprobar(!str_contains($solicitar('/equipo/panel')[1], '/equipo/pedidos/registrar')
        && !str_contains($solicitar('/equipo/pedidos')[1], '/equipo/pedidos/registrar'), 'El empleado no ve acciones que no puede utilizar');
    $salir();
    $entrar('7200001');
    $entorno->sesion(static fn (array $sesion): array => array_replace($sesion, ['carrito' => [3 => 4]]));
    [$estado, $html] = $solicitar('/equipo/pedidos/registrar');
    comprobar($estado === 200 && str_contains($html, 'Cache-Control: no-store')
        && str_contains($html, '/css/equipo-pedido-form.css') && str_contains($html, '/js/pedido-equipo.js'),
        'Carga el formulario con los recursos del equipo y sin caché');
    comprobar(str_contains($solicitar('/equipo/panel')[1], '/equipo/pedidos/registrar')
        && str_contains($solicitar('/equipo/pedidos')[1], '/equipo/pedidos/registrar'), 'El panel y el listado enlazan el registro interno');
    $campos = $datos + ['lineas' => $lineas, 'csrf' => oculto($html, 'csrf'),
        'pedido_token' => oculto($html, 'pedido_token'), 'accion' => 'registrar'];
    comprobar($solicitar('/equipo/pedidos/registrar', array_replace($campos, ['csrf' => 'incorrecto']))[0] === 422, 'Exige CSRF');
    comprobar($solicitar('/equipo/pedidos/registrar', array_replace($campos, ['pedido_token' => 'ajeno']))[0] === 422, 'Exige un formulario de esta sesión');
    foreach (['lineas' => 'incorrecto', 'cliente_nombre' => ['array'], 'accion' => 'eliminar'] as $campo => $invalido) {
        comprobar($solicitar('/equipo/pedidos/registrar', array_replace($campos, [$campo => $invalido]))[0] === 422,
            'Rechaza el campo HTTP malformado ' . $campo);
    }
    comprobar($solicitar('/equipo/pedidos/registrar', array_replace($campos, ['lineas' => [$lineas[0], $lineas[0]]]))[0] === 422,
        'El servidor rechaza productos repetidos sin depender de JavaScript');
    [$estado, $html] = $solicitar('/equipo/pedidos/registrar', array_replace($campos, ['accion' => 'agregar-linea']));
    comprobar($estado === 200 && str_contains($html, 'name="lineas[2][id_producto]"')
        && str_contains($html, '$ 190') && str_contains($html, '$ 65') && str_contains($html, $datos['cliente_nombre']),
        'Sin JavaScript agrega líneas, conserva los datos y calcula el resumen');
    [$estado, $html] = $solicitar('/equipo/pedidos/registrar', $campos + ['quitar_linea' => '0']);
    comprobar($estado === 200 && !str_contains($html, 'name="lineas[1][id_producto]"') && str_contains($html, '$ 65'),
        'Sin JavaScript quita una línea y actualiza el total');
    [$estado, $html] = $solicitar('/equipo/pedidos/registrar', array_replace($campos,
        ['cliente_nombre' => '<script>Cliente</script>', 'telefono' => 'inválido', 'notas' => '<img src=x onerror=alert(1)>']));
    comprobar($estado === 422 && str_contains($html, '&lt;script&gt;Cliente&lt;/script&gt;')
        && str_contains($html, '&lt;img src=x onerror=alert(1)&gt;') && !str_contains($html, '<script>Cliente</script>')
        && str_contains($html, 'value="inválido"'), 'Un error conserva los datos y escapa el contenido recibido');
    $db->exec('UPDATE productos SET precio = 95.25 WHERE id_producto = 1');
    [$estado, $html] = $solicitar('/equipo/pedidos/registrar', $campos);
    comprobar($estado === 422 && str_contains($html, 'precios cambiaron') && str_contains($html, '$ 255,50'),
        'Rechaza un precio cambiado y muestra el nuevo total para revisarlo');
    $db->exec('UPDATE productos SET activo = 0 WHERE id_producto = 2');
    [$estado, $html] = $solicitar('/equipo/pedidos/registrar', $campos);
    comprobar($estado === 422 && str_contains($html, 'Producto no disponible'), 'Identifica el producto retirado sin perder las líneas');
    $db->exec('UPDATE productos SET activo = 1 WHERE id_producto = 2');
    [, $html] = $solicitar('/equipo/pedidos/registrar');
    $campos['pedido_token'] = oculto($html, 'pedido_token');
    $db->exec("CREATE TRIGGER fallo_hu01 BEFORE INSERT ON pedido_detalles FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fallo simulado'");
    [$estado, $html] = $solicitar('/equipo/pedidos/registrar', $campos);
    comprobar($estado === 503 && !str_contains($html, 'SQLSTATE') && str_contains($html, $datos['cliente_nombre'])
        && (int) $db->query('SELECT COUNT(*) FROM pedidos')->fetchColumn() === 1, 'Un fallo de detalle revierte todo el pedido y permite reintentar');
    $db->exec('DROP TRIGGER fallo_hu01');
    $campos += ['total' => '0.01', 'precio' => '1', 'estado' => 'Entregado', 'id_cliente' => (string) $cliente['id_cliente'],
        'id_usuario_registro' => (string) $cuentas['dueno']['id_usuario'], 'sena_recibida' => '1'];
    [$estado, $respuesta] = $solicitar('/equipo/pedidos/registrar', $campos);
    comprobar($estado === 303 && str_contains($respuesta, 'Location: /equipo/pedidos'), 'Guarda y redirige al listado');
    $ultimo = $db->query('SELECT * FROM pedidos ORDER BY id_pedido DESC LIMIT 1')->fetch();
    comprobar($ultimo['total'] === '255.50' && $ultimo['estado'] === 'Pendiente' && !$ultimo['sena_recibida']
        && $ultimo['id_cliente'] === null && (int) $ultimo['id_usuario_registro'] === $admin,
        'El servidor decide el precio, estado, responsable e identidad, sin alterar la seña de HU05');
    comprobar($entorno->sesion()['carrito'] === [3 => 4], 'El pedido interno conserva el carrito público');
    comprobar($solicitar('/equipo/pedidos/registrar', $campos)[0] === 303
        && (int) $db->query('SELECT COUNT(*) FROM pedidos')->fetchColumn() === 2, 'El doble envío no duplica pedidos ni detalles');
    [, $listado] = $solicitar('/equipo/pedidos');
    comprobar(str_contains($listado, 'Pedido #' . $ultimo['id_pedido'] . ' registrado correctamente')
        && str_contains($listado, $datos['cliente_nombre']) && str_contains($listado, 'Pan integral × 2'),
        'HU04 muestra la confirmación y los productos del pedido nuevo');
    comprobar(!str_contains($solicitar('/equipo/pedidos')[1], 'registrado correctamente'), 'Recargar el listado no repite la confirmación');
    [, $html] = $solicitar('/equipo/pedidos/registrar');
    $campos['pedido_token'] = oculto($html, 'pedido_token');
    $entorno->sesion(static function (array $sesion) use ($campos): array {
        $sesion['formularios_pedido_equipo'][$campos['pedido_token']]['creado'] = time() - 1801;
        return $sesion;
    });
    comprobar($solicitar('/equipo/pedidos/registrar', $campos)[0] === 422, 'Un formulario vencido no crea pedidos');
    $db->exec("UPDATE usuarios_equipo SET rol = 'empleado' WHERE id_usuario = " . $admin);
    comprobar($solicitar('/equipo/pedidos/registrar', $campos)[0] === 403, 'Un cambio de rol revoca el permiso de un formulario abierto');
    $salir();
    $entrar('7200002');
    [, $html] = $solicitar('/equipo/pedidos/registrar');
    $campos['pedido_token'] = oculto($html, 'pedido_token');
    $campos['csrf'] = oculto($html, 'csrf');
    comprobar($solicitar('/equipo/pedidos/registrar', $campos)[0] === 303, 'El dueño también puede registrar un pedido');
    $db->exec('UPDATE productos SET activo = 0');
    [$estado, $html] = $solicitar('/equipo/pedidos/registrar');
    comprobar($estado === 200 && str_contains($html, 'No hay productos disponibles')
        && !str_contains($html, 'data-pedido-equipo'), 'El catálogo vacío ofrece administrar productos sin un formulario inutilizable');
    comprobar(!preg_match('/Fatal error:|Warning:|Notice:|Deprecated:/', $entorno->errores()), 'El recorrido no genera errores PHP');
    echo "Correcto: $comprobaciones comprobaciones de HU01.\n";
} catch (Throwable $error) {
    $correcto = false;
    fwrite(STDERR, $error->getMessage() . "\n" . $error->getTraceAsString() . "\n");
    fwrite(STDERR, $entorno?->errores() ?? '');
} finally {
    $entorno?->cerrar();
}
exit($correcto ? 0 : 1);
