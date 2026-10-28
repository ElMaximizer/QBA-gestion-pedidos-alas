<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/soporte.php';

use App\Models\Carrito;
use App\Models\Cliente;
use App\Models\ConsultaPedidos;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\UsuarioEquipo;

$entorno = null;
$comprobaciones = 0;
$correcto = true;
try {
    $entorno = new EntornoPruebas('hu23');
    $db = $entorno->db;
    $modelo = new Pedido($db);
    $productos = new Producto($db);
    $password = 'GestionPedidoPrueba23!';
    $cuentas = [];
    foreach (['administrador' => '7400001', 'dueno' => '7400002', 'empleado' => '7400003', 'otro' => '7400004'] as $rol => $cedula) {
        $cuentas[$rol] = (new UsuarioEquipo($db))->solicitar('Cuenta ' . $rol, $cedula, $rol . '@example.test', $password, $password);
        $db->prepare("UPDATE usuarios_equipo SET rol = ?, estado = 'aprobado' WHERE id_usuario = ?")
            ->execute([$rol === 'otro' ? 'dueno' : $rol, $cuentas[$rol]['id_usuario']]);
    }
    $dueno = $cuentas['dueno']['id_usuario'];
    $admin = $cuentas['administrador']['id_usuario'];
    $datos = ['cliente_nombre' => 'Cliente HU23', 'telefono' => '099123456', 'fecha_retiro' => date('Y-m-d', strtotime('+2 days')),
        'hora_retiro' => '10:00', 'notas' => 'Pedido original'];
    $firma = static function (array $cantidades, array $precios): string {
        $lineas = [];
        foreach ($cantidades as $id => $cantidad) $lineas[] = ['id_producto' => $id, 'cantidad' => $cantidad, 'precio' => $precios[$id]];
        return Carrito::firma($lineas);
    };
    $clientes = new Cliente($db);
    $cliente = $clientes->registrar('Cliente web HU23', 'cliente@example.test', $password, $password);
    $clientes->verificar($cliente['email'], $cliente['codigo']);
    $id = $modelo->crear($datos, [1 => 2, 2 => 1], $firma([1 => 2, 2 => 1], [1 => 9500, 2 => 6500]), bin2hex(random_bytes(32)), null, $admin);
    $idWeb = $modelo->crear($datos, [3 => 1], $firma([3 => 1], [3 => 120000]), bin2hex(random_bytes(32)), $cliente['id_cliente']);
    $idAdmin = $modelo->crear($datos, [1 => 1], $firma([1 => 1], [1 => 9500]), bin2hex(random_bytes(32)), null, $admin);
    $db->exec("UPDATE pedidos SET estado = 'Entregado', fecha_retiro = '2025-01-05', sena_recibida = 1 WHERE id_pedido = " . $id);
    $original = $modelo->buscarGestion($id);
    $opciones = array_column(Pedido::opcionesGestion($productos->listar(), $original), null, 'id_producto');
    comprobar(str_contains($opciones[1]['presentacion'], 'Unidad') && $opciones[1]['precio'] === 9500,
        'El selector conserva la presentación del catálogo junto con el precio del pedido');
    $version = Pedido::version($original);
    $nuevosDatos = array_replace($original, ['cliente_nombre' => 'Cliente corregido', 'estado' => 'Cancelado', 'notas' => 'Corrección de un pedido entregado']);
    $cantidades = [1 => 3, 2 => 1, 3 => 1];
    $firmaNueva = $firma($cantidades, [1 => 9500, 2 => 6500, 3 => 120000]);
    foreach (['administrador', 'empleado'] as $rol) {
        rechaza(fn () => $modelo->modificar($id, $nuevosDatos, $cantidades, $firmaNueva, $version, bin2hex(random_bytes(32)), $cuentas[$rol]['id_usuario']),
            'El modelo impide corregir pedidos cerrados a ' . $rol);
    }
    $db->exec("UPDATE productos SET nombre = 'Nombre nuevo', precio = 999.99, activo = 0 WHERE id_producto = 1");
    $db->exec('UPDATE productos SET eliminado_en = NOW() WHERE id_producto = 2');
    $token = bin2hex(random_bytes(32));
    $modelo->modificar($id, $nuevosDatos, $cantidades, $firmaNueva, $version, $token, $dueno, 'Pedido cancelado por el cliente');
    $actual = $modelo->buscarGestion($id);
    comprobar($actual['total'] === '1550.00' && $actual['estado'] === 'Cancelado'
        && $actual['lineas'][0]['nombre'] === 'Pan integral' && $actual['lineas'][0]['precio'] === 9500,
        'Corrige un pedido entregado con precios originales y productos retirados del catálogo');
    comprobar($actual['fecha_retiro'] === '2025-01-05' && $actual['sena_recibida'] === $original['sena_recibida']
        && $actual['id_usuario_registro'] === $original['id_usuario_registro'] && $actual['token_confirmacion'] === $original['token_confirmacion'],
        'Conserva retiro pasado, seña, responsable original y token de creación');
    $historial = $modelo->historial($id);
    comprobar($historial['total'] === 1 && $historial['cambios'][0]['nombre_responsable'] === 'Cuenta dueno'
        && $historial['cambios'][0]['antes']['estado'] === 'Entregado' && $historial['cambios'][0]['despues']['estado'] === 'Cancelado'
        && $historial['cambios'][0]['antes']['total_centavos'] === 25500 && $historial['cambios'][0]['despues']['total_centavos'] === 155000,
        'El historial guarda responsable, motivo, datos y productos anteriores y nuevos');
    comprobar(abs((int) $historial['cambios'][0]['creado_en_unix'] - time()) < 10,
        'La fecha del historial conserva el instante real aunque PHP y MySQL usen zonas horarias distintas');
    $modelo->modificar($id, $nuevosDatos, $cantidades, $firmaNueva, $version, $token, $dueno);
    comprobar($modelo->historial($id)['total'] === 1, 'El reenvío no repite la modificación ni su historial');
    rechaza(fn () => $modelo->modificar($idWeb, $nuevosDatos, $cantidades, $firmaNueva, $version, $token, $dueno), 'El token queda vinculado al pedido');
    rechaza(fn () => $modelo->modificar($id, $nuevosDatos, $cantidades, $firmaNueva, $version, $token, $cuentas['otro']['id_usuario']), 'El token queda vinculado al dueño que lo usó');
    rechaza(fn () => $modelo->modificar($id, $nuevosDatos, $cantidades, $firmaNueva, $version, bin2hex(random_bytes(32)), $dueno), 'Una versión anterior no sobrescribe cambios recientes');
    $versionActual = Pedido::version($actual);
    foreach (['estado' => 'Inventado', 'fecha_retiro' => '0001-01-01', 'telefono' => 'abc'] as $campo => $valor) {
        rechaza(fn () => $modelo->modificar($id, array_replace($actual, [$campo => $valor]), $cantidades,
            $firmaNueva, $versionActual, bin2hex(random_bytes(32)), $dueno), 'Valida el campo editable ' . $campo);
    }
    $modelo->modificar($id, $actual, $cantidades, $firmaNueva, $versionActual, bin2hex(random_bytes(32)), $dueno);
    comprobar($modelo->historial($id)['total'] === 1 && Pedido::version($modelo->buscarGestion($id)) === $versionActual,
        'Guardar sin modificar datos no genera historial ni reemplaza detalles');
    $db->exec("CREATE TRIGGER fallo_hu23 BEFORE INSERT ON pedido_cambios FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fallo simulado'");
    try {
        $modelo->modificar($id, array_replace($actual, ['notas' => 'No debe guardarse']), $cantidades, $firmaNueva, $versionActual, bin2hex(random_bytes(32)), $dueno);
        throw new RuntimeException('Debió fallar el historial');
    } catch (PDOException) {
        comprobar($modelo->buscarGestion($id) === $actual && $modelo->historial($id)['total'] === 1,
            'Un fallo del historial revierte cabecera y detalles, incluyendo sus identificadores');
    }
    $db->exec('DROP TRIGGER fallo_hu23');
    $web = $modelo->buscarGestion($idWeb);
    $db->exec('UPDATE productos SET activo = 1, precio = 96.00 WHERE id_producto = 1');
    rechaza(fn () => $modelo->modificar($idWeb, $web, [3 => 1, 1 => 1], $firma([3 => 1, 1 => 1], [3 => 120000, 1 => 9500]),
        Pedido::version($web), bin2hex(random_bytes(32)), $dueno), 'Un producto nuevo exige revisar su precio actual');
    $db->exec('DELETE FROM productos WHERE id_producto = 3');
    $web = $modelo->buscarGestion($idWeb);
    $claveWeb = $web['lineas'][0]['clave_producto'];
    comprobar(str_starts_with($claveWeb, 'historico:') && Pedido::cantidadesDesdeLineas([['id_producto' => $claveWeb, 'cantidad' => '2']], true) === [$claveWeb => 2],
        'Permite referenciar un producto eliminado conservado en los detalles del pedido');
    rechaza(fn () => Pedido::cantidadesDesdeLineas([['id_producto' => $claveWeb, 'cantidad' => '2']]), 'HU01 no acepta productos históricos al crear pedidos');
    $modelo->modificar($idWeb, $web, [$claveWeb => 2], $firma([$claveWeb => 2], [$claveWeb => 120000]), Pedido::version($web), bin2hex(random_bytes(32)), $dueno);
    $web = $modelo->buscarGestion($idWeb);
    comprobar($web['total'] === '2400.00' && $web['lineas'][0]['id_producto'] === null && (int) $web['id_cliente'] === $cliente['id_cliente'],
        'Corrige un producto eliminado y conserva la cuenta del cliente del pedido web');
    $claveAjena = $modelo->buscarGestion($id)['lineas'][2]['clave_producto'];
    rechaza(fn () => $modelo->modificar($idWeb, $web, [$claveAjena => 1], $firma([$claveAjena => 1], [$claveAjena => 120000]),
        Pedido::version($web), bin2hex(random_bytes(32)), $dueno), 'No admite un detalle histórico de otro pedido');

    $pedidoAdmin = $modelo->buscarGestion($idAdmin);
    $modelo->modificar($idAdmin, array_replace($pedidoAdmin, ['estado' => 'En preparación', 'notas' => 'Edición del administrador']),
        [1 => 2], $firma([1 => 2], [1 => 9500]), Pedido::version($pedidoAdmin), bin2hex(random_bytes(32)), $admin, 'Cambio de cantidad');
    $pedidoAdmin = $modelo->buscarGestion($idAdmin);
    $historialAdmin = $modelo->historial($idAdmin);
    comprobar($pedidoAdmin['estado'] === 'En preparación' && $pedidoAdmin['total'] === '190.00'
        && $historialAdmin['total'] === 1 && $historialAdmin['cambios'][0]['nombre_responsable'] === 'Cuenta administrador'
        && $historialAdmin['cambios'][0]['antes']['lineas'][0]['cantidad'] === 1
        && $historialAdmin['cambios'][0]['despues']['lineas'][0]['cantidad'] === 2,
        'El administrador modifica pedidos abiertos con precios originales e historial propio');
    $db->exec("UPDATE usuarios_equipo SET rol = 'empleado' WHERE id_usuario = " . $admin);
    rechaza(fn () => $modelo->modificar($idAdmin, $pedidoAdmin, [1 => 2], $firma([1 => 2], [1 => 9500]),
        Pedido::version($pedidoAdmin), bin2hex(random_bytes(32)), $admin), 'El guardado vuelve a consultar el rol real del administrador');
    $db->exec("UPDATE usuarios_equipo SET rol = 'administrador' WHERE id_usuario = " . $admin);

    $entorno->iniciarServidor();
    $solicitar = $entorno->solicitar(...);
    $ruta = '/equipo/pedidos/editar?id=' . $id;
    comprobar($solicitar($ruta)[0] === 303 && $solicitar($ruta, ['rol' => 'dueno'])[0] === 303, 'Un visitante no abre ni modifica pedidos');
    $entorno->sesion(static fn (array $sesion): array => $sesion + ['cliente_id' => $cliente['id_cliente'], 'cliente_ultimo_acceso' => time()]);
    comprobar($solicitar($ruta)[0] === 303, 'La sesión de cliente tampoco habilita la edición interna');
    $entrar = static function (string $cedula) use ($solicitar, $password): void {
        $csrf = oculto($solicitar('/equipo/login')[1], 'csrf');
        comprobar($solicitar('/equipo/login', ['csrf' => $csrf, 'cedula' => $cedula, 'password' => $password])[0] === 303, 'Ingresa la cuenta de prueba');
    };
    $salir = static fn () => $solicitar('/equipo/salir', ['csrf' => $entorno->sesion()['csrf']]);
    $camposActuales = static function (int $idPedido, string $html) use ($modelo): array {
        $actual = $modelo->buscarGestion($idPedido);
        return array_intersect_key($actual, array_flip(['cliente_nombre', 'telefono', 'fecha_retiro', 'hora_retiro', 'notas', 'estado']))
            + ['lineas' => array_map(static fn (array $linea): array => ['id_producto' => $linea['clave_producto'], 'cantidad' => (string) $linea['cantidad']], $actual['lineas']),
                'motivo' => 'Corrección HTTP', 'csrf' => oculto($html, 'csrf'), 'pedido_token' => oculto($html, 'pedido_token'), 'accion' => 'registrar'];
    };
    foreach (['7400001', '7400003'] as $cedula) {
        $entrar($cedula);
        comprobar($solicitar($ruta)[0] === 403 && $solicitar($ruta, ['rol' => 'dueno'])[0] === 403,
            'Administrador y empleado no corrigen pedidos cerrados aunque falsifiquen el rol');
        $listado = $solicitar('/equipo/pedidos')[1];
        comprobar($cedula === '7400001'
            ? str_contains($listado, '/equipo/pedidos/editar?id=' . $idAdmin) && !str_contains($listado, '/equipo/pedidos/editar?id=' . $id . '"')
            : !str_contains($listado, '/equipo/pedidos/editar'), 'El listado ofrece la edición según el rol y el estado del pedido');
        $salir();
    }
    $entrar('7400003');
    comprobar($solicitar('/equipo/pedidos/editar?id=' . $idAdmin)[0] === 403
        && $solicitar('/equipo/pedidos/editar?id=' . $idAdmin, ['rol' => 'administrador'])[0] === 403,
        'El empleado tampoco puede abrir ni editar pedidos activos');
    $salir();
    $entrar('7400001');
    $rutaAdmin = '/equipo/pedidos/editar?id=' . $idAdmin;
    [$estado, $htmlAdmin] = $solicitar($rutaAdmin);
    comprobar($estado === 200 && str_contains($htmlAdmin, 'Guardar cambios') && str_contains($htmlAdmin, 'Historial de modificaciones'),
        'El administrador abre el formulario de edición con el historial');
    $camposAdmin = array_replace($camposActuales($idAdmin, $htmlAdmin), ['cliente_nombre' => 'Cliente actualizado por administración',
        'lineas' => [['id_producto' => '1', 'cantidad' => '3']], 'rol' => 'dueno', 'id_usuario_registro' => (string) $dueno]);
    comprobar($solicitar($rutaAdmin, array_replace($camposAdmin, ['csrf' => 'incorrecto']))[0] === 422,
        'La edición del administrador exige CSRF');
    comprobar($solicitar($rutaAdmin, $camposAdmin)[0] === 303, 'El administrador guarda la modificación desde el formulario');
    $pedidoAdmin = $modelo->buscarGestion($idAdmin);
    comprobar($pedidoAdmin['cliente_nombre'] === 'Cliente actualizado por administración' && $pedidoAdmin['total'] === '285.00'
        && (int) $pedidoAdmin['id_usuario_registro'] === $admin && $modelo->historial($idAdmin)['total'] === 2
        && $modelo->historial($idAdmin)['cambios'][0]['nombre_responsable'] === 'Cuenta administrador',
        'El guardado calcula el total y registra al administrador real, ignorando identidades enviadas');
    [, $htmlAdmin] = $solicitar($rutaAdmin);
    $camposAdmin = $camposActuales($idAdmin, $htmlAdmin);
    $db->exec("UPDATE pedidos SET estado = 'Entregado' WHERE id_pedido = " . $idAdmin);
    comprobar($solicitar($rutaAdmin, array_replace($camposAdmin, ['notas' => 'No sobrescribir un pedido cerrado']))[0] === 403
        && $modelo->historial($idAdmin)['total'] === 2, 'Un formulario anterior del administrador no corrige un pedido que ya fue cerrado');
    rechaza(fn () => $modelo->modificar($idAdmin, $pedidoAdmin, [1 => 3], $firma([1 => 3], [1 => 9500]),
        Pedido::version($pedidoAdmin), bin2hex(random_bytes(32)), $admin), 'El modelo comprueba el estado actual al guardar, aunque cambie después de abrir el formulario');
    $db->exec("UPDATE pedidos SET estado = 'En preparación' WHERE id_pedido = " . $idAdmin);
    [, $htmlAdmin] = $solicitar($rutaAdmin);
    $camposAdmin = array_replace($camposActuales($idAdmin, $htmlAdmin), ['estado' => 'Cancelado']);
    comprobar($solicitar($rutaAdmin, $camposAdmin)[0] === 303 && $modelo->buscarGestion($idAdmin)['estado'] === 'Cancelado',
        'El administrador puede cerrar un pedido activo');
    comprobar($solicitar($rutaAdmin, $camposAdmin)[0] === 303 && $modelo->historial($idAdmin)['total'] === 3
        && $solicitar($rutaAdmin)[0] === 403, 'El reenvío de un cierre no repite cambios ni habilita nuevas correcciones del pedido cerrado');
    $salir();
    $entrar('7400002');
    comprobar($solicitar('/equipo/pedidos/editar?id=999999')[0] === 404 && $solicitar('/equipo/pedidos/editar?id[]=1')[0] === 422, 'Controla pedidos inexistentes e identificadores malformados');
    [$estado, $html] = $solicitar($ruta);
    comprobar($estado === 200 && str_contains($html, 'Cache-Control: no-store') && str_contains($html, 'Historial de modificaciones')
        && str_contains($html, 'Cancelar') === false && str_contains($html, 'Cancelado') && str_contains($html, 'min="1000-01-01"'),
        'El dueño ve el detalle, los estados y el historial del pedido cerrado con su fecha original');
    $campos = $camposActuales($id, $html);
    comprobar($solicitar($ruta, array_replace($campos, ['csrf' => 'incorrecto']))[0] === 422, 'Exige CSRF al modificar');
    comprobar($solicitar($ruta, array_replace($campos, ['pedido_token' => 'ajeno']))[0] === 422, 'Exige un formulario propio');
    comprobar($solicitar('/equipo/pedidos/editar?id=' . $idWeb, $campos)[0] === 422
        && $solicitar('/equipo/pedidos/registrar', $campos)[0] === 422, 'El formulario no puede usarse para otro pedido ni para crear uno nuevo');
    [$estado, $html] = $solicitar($ruta, array_replace($campos, ['accion' => 'agregar-linea']));
    comprobar($estado === 200 && str_contains($html, 'name="lineas[3][id_producto]"') && str_contains($html, 'value="Cancelado" selected'),
        'Agregar líneas sin JavaScript conserva los datos y el estado del pedido');
    comprobar($solicitar($ruta, array_replace($campos, ['lineas' => 'malformadas']))[0] === 422, 'Valida las líneas de la corrección');
    $antes = $modelo->historial($id)['total'];
    [, $otraPantalla] = $solicitar($ruta);
    $otraCorreccion = array_replace($camposActuales($id, $otraPantalla), ['notas' => 'Cambio desde otra pantalla']);
    comprobar($solicitar($ruta, $otraCorreccion)[0] === 303, 'Una segunda pantalla puede guardar una corrección');
    [$estado, $html] = $solicitar($ruta, array_replace($campos, ['notas' => 'No sobrescribir el cambio reciente']));
    comprobar($estado === 422 && str_contains($html, 'Volvé a abrirlo') && str_contains($html, 'Volver a abrir el pedido')
        && $modelo->buscarGestion($id)['notas'] === 'Cambio desde otra pantalla' && $modelo->historial($id)['total'] === $antes + 1,
        'La pantalla anterior no sobrescribe cambios ni actualiza silenciosamente su versión');
    [, $html] = $solicitar($ruta);
    $campos = array_replace($camposActuales($id, $html), ['estado' => 'Entregado', 'notas' => '<img src=x onerror=alert(1)>',
        'motivo' => '<script>Motivo</script>', 'total' => '0.01', 'id_cliente' => '999', 'sena_recibida' => '1', 'id_usuario_registro' => (string) $dueno]);
    (new ConsultaPedidos($db))->registrarSena($id, false);
    [$estado, $respuesta] = $solicitar($ruta, $campos);
    comprobar($estado === 303 && str_contains($respuesta, 'Location: /equipo/pedidos'), 'Confirma la corrección y vuelve al listado');
    $guardado = $modelo->buscarGestion($id);
    comprobar($guardado['estado'] === 'Entregado' && $guardado['total'] === '1550.00' && !$guardado['sena_recibida']
        && $guardado['id_cliente'] === null && (int) $guardado['id_usuario_registro'] === $admin,
        'Ignora importes e identidades manipulados y conserva una seña actualizada desde otra pantalla');
    comprobar($solicitar($ruta, $campos)[0] === 303 && $modelo->historial($id)['total'] === $antes + 2, 'El doble POST no duplica el historial');
    [, $html] = $solicitar($ruta);
    comprobar(str_contains($html, '&lt;script&gt;Motivo&lt;/script&gt;') && str_contains($html, '&lt;img src=x onerror=alert(1)&gt;')
        && !str_contains($html, '<script>Motivo</script>') && str_contains($html, 'Productos anteriores'), 'La pantalla muestra el antes y después escapando notas y motivos');
    $campos = $camposActuales($id, $html);
    $db->exec("CREATE TRIGGER fallo_web_hu23 BEFORE INSERT ON pedido_cambios FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fallo simulado'");
    [$estado, $html] = $solicitar($ruta, array_replace($campos, ['notas' => 'Error de prueba']));
    comprobar($estado === 503 && !str_contains($html, 'SQLSTATE') && str_contains($html, 'Error de prueba')
        && $modelo->buscarGestion($id) === $guardado, 'Un fallo HTTP conserva el formulario y revierte todos los cambios');
    $db->exec('DROP TRIGGER fallo_web_hu23');
    $tokenVencido = $campos['pedido_token'];
    $entorno->sesion(static function (array $sesion) use ($tokenVencido): array {
        $sesion['formularios_pedido_equipo'][$tokenVencido]['creado'] = time() - 1801;
        return $sesion;
    });
    comprobar($solicitar($ruta, $campos)[0] === 422, 'Un formulario vencido no modifica el pedido');
    for ($i = 0; $i < 11; $i++) {
        $actual = $modelo->buscarGestion($id);
        $cantidades = array_column($actual['lineas'], 'cantidad', 'clave_producto');
        $modelo->modificar($id, array_replace($actual, ['notas' => 'Cambio de paginación ' . $i]), $cantidades,
            $firma($cantidades, array_column($actual['lineas'], 'precio', 'clave_producto')), Pedido::version($actual), bin2hex(random_bytes(32)), $dueno);
    }
    comprobar(count($modelo->historial($id)['cambios']) === 10 && $modelo->historial($id, 2)['pagina'] === 2,
        'El historial se pagina sin cargar todas las correcciones');
    comprobar(str_contains($solicitar($ruta . '&historial_pagina=2')[1], 'Página 2 de 2'), 'La pantalla conserva el pedido al paginar su historial');
    [$codigo] = $entorno->cli('database/instalar.php', []);
    comprobar($codigo === 0 && $modelo->historial($id)['total'] === $antes + 13, 'Repetir la instalación conserva el historial y los pedidos');
    [, $html] = $solicitar($ruta);
    $campos = $camposActuales($id, $html);
    $db->exec("UPDATE usuarios_equipo SET rol = 'administrador' WHERE id_usuario = " . $dueno);
    comprobar($solicitar($ruta, $campos)[0] === 403, 'Revoca la edición si cambia el rol del dueño');
    comprobar(!preg_match('/Fatal error:|Warning:|Notice:|Deprecated:/', $entorno->errores()), 'El recorrido no genera errores PHP');
    echo "Correcto: $comprobaciones comprobaciones de HU23.\n";
} catch (Throwable $error) {
    $correcto = false;
    fwrite(STDERR, $error->getMessage() . "\n" . $error->getTraceAsString() . "\n");
    fwrite(STDERR, $entorno?->errores() ?? '');
} finally {
    $entorno?->cerrar();
}
exit($correcto ? 0 : 1);
