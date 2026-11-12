<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/soporte.php';

use App\Models\AccesoCliente;
use App\Models\Cliente;
use App\Models\CuentaCliente;

$entorno = null;
$comprobaciones = 0;
$correcto = true;

try {
    $entorno = new EntornoPruebas('hu17');
    $db = $entorno->db;
    $clientes = new Cliente($db);
    $cuentas = new CuentaCliente($db);
    $acceso = new AccesoCliente($db);
    $password = 'DireccionesPrueba!';
    $cliente = $clientes->registrar('Cliente de prueba', 'direcciones@example.test', $password, $password);
    $clientes->verificar($cliente['email'], $cliente['codigo']);
    $id = $cliente['id_cliente'];
    $version = $acceso->buscarVerificado($id)['version_acceso'];
    $otro = $clientes->registrar('Otra cuenta', 'privada@example.test', $password, $password);
    $clientes->verificar($otro['email'], $otro['codigo']);
    $otraVersion = $acceso->buscarVerificado($otro['id_cliente'])['version_acceso'];
    $cuentas->actualizarPerfil($id, $version, 'Cliente de prueba', '+598 98 123 456');
    comprobar($cuentas->datos($id)['telefono'] === '+598 98 123 456', 'Guarda teléfono internacional con formato legible');
    foreach (['abc123456', '123', str_repeat('9', 16), '<script>'] as $invalido) {
        rechaza(fn() => $cuentas->actualizarPerfil($id, $version, 'Otro nombre', $invalido), 'Rechaza teléfonos inválidos');
    }
    comprobar($cuentas->datos($id)['nombre'] === 'Cliente de prueba', 'Un teléfono inválido no actualiza parcialmente el perfil');
    rechaza(fn() => $cuentas->actualizarPerfil($otro['id_cliente'], $version, 'Ajeno', '099123456'), 'El perfil exige la versión de la identidad correcta');
    $db->exec(file_get_contents(dirname(__DIR__) . '/database/contacto-clientes.sql'));
    comprobar($cuentas->datos($id)['telefono'] === '+598 98 123 456', 'La migración repetida conserva el teléfono');
    $ajena = $cuentas->guardarDireccion($otro['id_cliente'], $otraVersion, 'Privada', 'Dirección ajena 999');
    $casa = $cuentas->guardarDireccion($id, $version, 'Casa', 'Calle de prueba 123, Salto');
    $trabajo = $cuentas->guardarDireccion($id, $version, 'Trabajo', 'Otra calle 456, Salto');
    comprobar(
        count($cuentas->direcciones($id)) === 2 &&
            (int) $cuentas->buscarDireccion($id, $casa)['principal'] === 1,
        'La primera dirección queda como principal y el listado excluye las de otras cuentas',
    );
    foreach ([['', 'Calle'], ['Casa', ''], [str_repeat('a', 51), 'Calle'], ['Casa', str_repeat('a', 251)]] as [$alias, $direccion]) {
        rechaza(
            fn() => $cuentas->guardarDireccion($id, $version, $alias, $direccion),
            'Valida los datos y límites de la dirección',
        );
    }
    rechaza(
        fn() => $cuentas->guardarDireccion($id, $version, 'Ajena', 'Cambio', $ajena),
        'No permite editar la dirección de otra cuenta',
    );
    rechaza(fn() => $cuentas->eliminarDireccion($id, $version, $ajena), 'No elimina direcciones ajenas');
    rechaza(fn() => $cuentas->direccionPrincipal($id, $version, $ajena), 'No elige una dirección ajena como principal');
    rechaza(
        fn() => $cuentas->guardarDireccion($id, str_repeat('a', 64), 'Revocada', 'Calle 1'),
        'Una sesión revocada no puede guardar direcciones',
    );
    $cuentas->direccionPrincipal($id, $version, $trabajo);
    comprobar(
        (int) $cuentas->direcciones($id)[0]['id_direccion'] === $trabajo &&
            array_sum(array_column($cuentas->direcciones($id), 'principal')) === 1,
        'Cambiar la principal conserva una sola y la muestra primero',
    );
    $cuentas->eliminarDireccion($id, $version, $trabajo);
    comprobar((int) $cuentas->buscarDireccion($id, $casa)['principal'] === 1, 'Al borrar la principal se elige otra existente');
    $antes = $cuentas->direcciones($id);
    $db->exec(file_get_contents(dirname(__DIR__) . '/database/direcciones-clientes.sql'));
    comprobar($cuentas->direcciones($id) === $antes, 'Repetir la migración conserva las direcciones');

    $entorno->iniciarServidor();
    $solicitar = $entorno->solicitar(...);
    $ruta = '/clientes/cuenta?seccion=direcciones';
    comprobar($solicitar($ruta)[0] === 303, 'Las direcciones requieren una sesión de cliente');
    $csrf = oculto($solicitar('/clientes/login')[1], 'csrf');
    comprobar(
        $solicitar('/clientes/login', ['csrf' => $csrf, 'email' => $cliente['email'], 'password' => $password])[0] === 303,
        'Inicia la sesión de prueba',
    );
    [, $pagina] = $solicitar($ruta);
    $csrf = oculto($pagina, 'csrf');
    comprobar(
        str_contains($pagina, 'aria-label="Configuración de cuenta"') &&
            str_contains($pagina, 'Mis direcciones') && !str_contains($pagina, 'name="new_password"'),
        'El menú de configuración muestra solo la sección seleccionada',
    );
    $guardar = ['accion' => 'guardar-direccion', 'alias' => 'Casa <nueva>',
        'direccion' => 'Dirección web 789, Salto', 'referencias' => 'Piso 2 <timbre>', 'id_direccion' => '', 'csrf' => $csrf];
    comprobar(
        $solicitar($ruta, array_replace($guardar, ['csrf' => 'incorrecto']))[0] === 422 &&
            count($cuentas->direcciones($id)) === 1,
        'Guardar exige CSRF y no modifica datos al rechazarlo',
    );
    comprobar(
        $solicitar($ruta, array_replace($guardar, ['direccion' => ['x']]))[0] === 422,
        'Rechaza una dirección HTTP malformada',
    );
    comprobar($solicitar($ruta, array_replace($guardar, ['referencias' => ['x']]))[0] === 422, 'Rechaza referencias HTTP malformadas');
    comprobar($solicitar($ruta, array_replace($guardar, ['referencias' => str_repeat('a', 121)]))[0] === 422, 'Valida la longitud de referencias');
    comprobar($solicitar($ruta, array_replace($guardar, ['direccion' => str_repeat('a', 240)]))[0] === 422, 'No guarda una dirección que luego excedería el límite del pedido');
    [$estado, $respuesta] = $solicitar($ruta, $guardar + ['id_cliente' => $otro['id_cliente']]);
    comprobar(
        $estado === 303 && str_contains($respuesta, 'Location: /clientes/cuenta?seccion=direcciones') &&
            count($cuentas->direcciones($id)) === 2 && count($cuentas->direcciones($otro['id_cliente'])) === 1,
        'Guarda en la identidad de sesión e ignora un ID de cliente enviado',
    );
    $nueva = (int) $db->query('SELECT MAX(id_direccion) FROM direcciones_cliente')->fetchColumn();
    [, $pagina] = $solicitar($ruta);
    comprobar(str_contains($pagina, 'Casa &lt;nueva&gt;'), 'Escapa el nombre de la dirección en el listado');
    comprobar(
        $solicitar($ruta . '&editar=' . $ajena)[0] === 404 &&
            $solicitar($ruta, array_replace($guardar, ['id_direccion' => $ajena]))[0] === 422,
        'Ni el formulario de edición ni el guardado exponen direcciones ajenas',
    );
    comprobar(
        $solicitar($ruta, ['accion' => 'direccion-principal', 'csrf' => $csrf, 'id_direccion' => $nueva])[0] === 303,
        'Permite elegir una dirección principal desde la cuenta',
    );
    $agregarCarrito = static function () use ($solicitar, $csrf): void {
        comprobar(
            $solicitar('/carrito', ['accion' => 'agregar', 'id_producto' => '1', 'cantidad' => '1', 'csrf' => $csrf])[0] === 303,
            'Prepara un carrito de prueba',
        );
    };
    $agregarCarrito();
    [, $pedido] = $solicitar('/pedidos/realizar');
    comprobar(
        str_contains($pedido, 'name="id_direccion"') && !str_contains($pedido, 'Dirección ajena 999'),
        'El pedido ofrece únicamente las direcciones propias',
    );
    comprobar(oculto($pedido, 'telefono') === '+598 98 123 456', 'Precarga el teléfono del perfil al realizar un pedido');
    comprobar(str_contains($pagina, 'Piso 2 &lt;timbre&gt;'), 'Escapa las referencias en la cuenta');
    $campos = [
        'csrf' => $csrf, 'pedido_token' => oculto($pedido, 'pedido_token'),
        'cliente_nombre' => 'Cliente de prueba', 'telefono' => '099123456',
        'fecha_retiro' => date('Y-m-d', strtotime('+2 days')), 'hora_retiro' => '10:00',
        'modalidad_entrega' => 'domicilio', 'direccion_entrega' => 'Texto que no decide el destino',
        'id_direccion' => $ajena, 'metodo_pago' => 'local', 'notas' => '',
    ];
    comprobar(
        $solicitar('/pedidos/realizar', $campos)[0] === 422 &&
            (int) $db->query('SELECT COUNT(*) FROM pedidos')->fetchColumn() === 0,
        'Una dirección ajena no permite crear el pedido',
    );
    $campos['id_direccion'] = $nueva;
    comprobar($solicitar('/pedidos/realizar', $campos)[0] === 303, 'Guarda un pedido con una dirección propia');
    $historico = $db->query('SELECT * FROM pedidos ORDER BY id_pedido DESC LIMIT 1')->fetch();
    comprobar(
        $historico['direccion_entrega'] === CuentaCliente::direccionEntrega($guardar) && $historico['telefono'] === '099123456' ,
        'El servidor obtiene la dirección guardada sin confiar en el texto enviado',
    );
    comprobar(
        $solicitar($ruta, array_replace($guardar, ['id_direccion' => $nueva, 'direccion' => 'Nueva calle 321']))[0] === 303,
        'Permite editar la dirección guardada',
    );
    comprobar(
        $solicitar($ruta, ['accion' => 'eliminar-direccion', 'id_direccion' => $nueva, 'csrf' => $csrf])[0] === 303 &&
            $db->query('SELECT * FROM pedidos ORDER BY id_pedido DESC LIMIT 1')->fetch() === $historico,
        'Editar y borrar una dirección no modifica el pedido anterior',
    );
    $agregarCarrito();
    [, $pedido] = $solicitar('/pedidos/realizar');
    $campos['pedido_token'] = oculto($pedido, 'pedido_token');
    comprobar($solicitar('/pedidos/realizar', $campos)[0] === 422, 'Una dirección borrada se rechaza al confirmar');
    $campos['id_direccion'] = '';
    $campos['direccion_entrega'] = 'Dirección manual 456, Salto';
    comprobar($solicitar('/pedidos/realizar', $campos)[0] === 303, 'Conserva la opción de escribir una dirección manual');
    $cuentas->actualizarPerfil($id, $version, 'Cliente de prueba', '');
    comprobar($cuentas->datos($id)['telefono'] === '', 'Permite retirar el teléfono opcional del perfil');
    $cuentas->actualizarPerfil($id, $version, 'Cliente de prueba', '098123456');
    $cuentas->eliminar($id, $version, $password);
    comprobar($db->query('SELECT telefono FROM clientes WHERE id_cliente = ' . $id)->fetchColumn() === '', 'La eliminación de la cuenta retira el teléfono del perfil');
    comprobar(
        $cuentas->direcciones($id) === [] && count($cuentas->direcciones($otro['id_cliente'])) === 1 &&
            $db->query('SELECT * FROM pedidos WHERE id_pedido = ' . $historico['id_pedido'])->fetch() === $historico,
        'Eliminar la cuenta retira sus direcciones y conserva los pedidos y las direcciones ajenas',
    );
    comprobar(
        !preg_match('/Fatal error:|Warning:|Notice:|Deprecated:/', $entorno->errores()),
        'El recorrido no genera errores PHP',
    );
    echo "Correcto: direcciones propias, principal, configuración, pedidos e historial.\n";
} catch (Throwable $error) {
    $correcto = false;
    fwrite(STDERR, $error->getMessage() . "\n" . $error->getTraceAsString() . "\n");
    fwrite(STDERR, $entorno?->errores() ?? '');
} finally {
    $entorno?->cerrar();
}
exit($correcto ? 0 : 1);
