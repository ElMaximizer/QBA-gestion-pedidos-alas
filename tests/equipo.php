<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/soporte.php';

use App\Models\Cliente;
use App\Services\Correo;
use App\Services\RegistroEquipo;

$entorno = null;
$comprobaciones = 0;
$correcto = true;

try {
    $entorno = new EntornoPruebas('equipo');
    $db = $entorno->db;
    $registro = new RegistroEquipo($db, new Correo($db, ['transport' => 'local']));
    $password = ' ClaveEquipo17! ';
    $email = 'equipo.modelo@example.test';
    $buscar = static function (string $correo) use ($db): array|false {
        $consulta = $db->prepare('SELECT * FROM usuarios_equipo WHERE email = ?');
        $consulta->execute([$correo]);
        return $consulta->fetch();
    };
    foreach ([
        ['', '87654321', $email, $password, $password],
        [str_repeat('a', 121), '87654321', $email, $password, $password],
        ["Nombre\xFF", '87654321', $email, $password, $password],
        ['Equipo', '12345', $email, $password, $password],
        ['Equipo', '123456789', $email, $password, $password],
        ['Equipo', '1.234.567', $email, $password, $password],
        ['Equipo', '123456-7', $email, $password, $password],
        ['Equipo', "123456\n7", $email, $password, $password],
        ['Equipo', '1234567a', $email, $password, $password],
        ['Equipo', '12345678', 'invalido', $password, $password],
        ['Equipo', '12345678', str_repeat('a', 180) . '@example.test', $password, $password],
        ['Equipo', '12345678', $email, 'corta', 'corta'],
        ['Equipo', '12345678', $email, str_repeat('a', 73), str_repeat('a', 73)],
        ['Equipo', '12345678', $email, str_repeat('á', 37), str_repeat('á', 37)],
        ['Equipo', '12345678', $email, "Clave\0Invalida", "Clave\0Invalida"],
        ['Equipo', '12345678', $email, $password, 'Distinta17!'],
    ] as $datos) {
        rechaza(fn () => $registro->solicitar(...$datos), 'Rechaza datos de registro inválidos');
    }
    comprobar((int) $db->query('SELECT COUNT(*) FROM usuarios_equipo')->fetchColumn() === 0, 'Los datos inválidos no crean solicitudes');
    comprobar((int) $db->query('SELECT COUNT(*) FROM correos_locales')->fetchColumn() === 0, 'Los datos inválidos no envían avisos');
    $nuevo = $registro->solicitar('  Equipo Ñandú  ', ' 08765432 ', '  EQUIPO.MODELO@EXAMPLE.TEST ', $password, $password);
    $usuario = $buscar($email);
    comprobar($usuario['nombre'] === 'Equipo Ñandú' && $nuevo['email'] === $email, 'Normaliza nombre y correo');
    comprobar($usuario['estado'] === 'pendiente' && $usuario['rol'] === 'empleado', 'Cada solicitud requiere aprobación y comienza como empleado');
    comprobar(password_verify($password, $usuario['password_hash']) && !password_verify(trim($password), $usuario['password_hash']), 'Protege la contraseña y conserva sus espacios');
    comprobar(!isset($nuevo['password_hash']) && !isset($nuevo['password']) && $nuevo['correo_enviado'], 'Devuelve el resultado sin contraseñas');
    $correo = $db->query('SELECT * FROM correos_locales')->fetch();
    comprobar($correo['destinatario'] === $email && str_contains($correo['cuerpo'], 'debe aprobarla'), 'Envía un aviso de recepción que explica la aprobación');
    comprobar(!str_contains($correo['cuerpo'], $password) && !str_contains($correo['cuerpo'], '8765432'), 'El aviso no incluye contraseña ni cédula');
    rechaza(fn () => $registro->solicitar('Duplicado', '8765432', 'otra@example.test', $password, $password), 'Los ceros a la izquierda no permiten duplicar una cédula');
    rechaza(fn () => $registro->solicitar('Duplicado', '76543210', strtoupper($email), $password, $password), 'El correo es único sin distinguir mayúsculas');
    comprobar($buscar($email)['password_hash'] === $usuario['password_hash'], 'Un duplicado no reemplaza la contraseña');
    comprobar((int) $db->query('SELECT COUNT(*) FROM correos_locales')->fetchColumn() === 1, 'Un duplicado no vuelve a enviar el aviso');
    $clientes = new Cliente($db);
    $cliente = $clientes->registrar('Cliente de prueba', $email, $password, $password);
    $clientes->verificar($email, $cliente['codigo']);
    comprobar((int) $db->query('SELECT COUNT(*) FROM clientes')->fetchColumn() === 1, 'Cliente y equipo mantienen cuentas independientes aunque compartan correo');

    $db->exec("CREATE TRIGGER fallo_aviso BEFORE INSERT ON correos_locales FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fallo de entrega de prueba'");
    $fallido = $registro->solicitar('Solicitud con aviso fallido', '76543210', 'fallo.aviso@example.test', $password, $password);
    comprobar(!$fallido['correo_enviado'] && $buscar('fallo.aviso@example.test')['estado'] === 'pendiente', 'Un fallo de correo conserva la solicitud pendiente');
    $db->exec('DROP TRIGGER fallo_aviso');

    $entorno->iniciarServidor();
    $solicitar = $entorno->solicitar(...);
    [, $inicio] = $solicitar('index.php');
    comprobar(str_contains($inicio, 'href="index.php?ruta=equipo-login">Acceso al equipo'), 'El pie permite abrir el acceso del equipo');
    comprobar($solicitar('index.php?ruta=equipo-solicitud-enviada')[0] === 303, 'La confirmación exige una solicitud de esta sesión');
    comprobar($solicitar('index.php?ruta=equipo-solicitud-enviada', [])[0] === 405, 'La confirmación solo admite lectura');
    [$estado, $html] = $solicitar('index.php?ruta=equipo-registro');
    comprobar($estado === 200 && str_contains($html, 'Solicitar usuario'), 'Muestra el formulario del equipo');
    comprobar(str_contains($html, 'registro.css') && str_contains($html, 'js/registro.js') && str_contains($html, 'Cache-Control: no-store'), 'Reutiliza los estilos y evita almacenar el formulario en caché');
    $csrf = oculto($html, 'csrf');
    $emailWeb = 'equipo.web@example.test';
    $campos = ['csrf' => $csrf, 'nombre' => 'Equipo web', 'cedula' => '65432109', 'email' => $emailWeb,
        'password' => $password, 'confirm_password' => $password];
    comprobar($solicitar('index.php?ruta=equipo-registro', array_replace($campos, ['csrf' => 'incorrecto']))[0] === 422, 'Exige CSRF al registrar');
    foreach (['nombre', 'cedula', 'email', 'password', 'confirm_password'] as $campo) {
        comprobar($solicitar('index.php?ruta=equipo-registro', array_replace($campos, [$campo => ['x']]))[0] === 422, 'Rechaza un campo malformado: ' . $campo);
    }
    [, $htmlError] = $solicitar('index.php?ruta=equipo-registro', array_replace($campos,
        ['nombre' => '<script>prueba</script>', 'confirm_password' => 'Distinta17!']));
    comprobar(str_contains($htmlError, '&lt;script&gt;prueba&lt;/script&gt;') && !str_contains($htmlError, '<script>prueba</script>'), 'Escapa los valores recuperados tras un error');
    comprobar(!str_contains($htmlError, $password), 'No devuelve contraseñas en el formulario');
    comprobar($buscar($emailWeb) === false, 'Los formularios rechazados no crean solicitudes');
    $solicitar('index.php?ruta=carrito', ['csrf' => $csrf, 'accion' => 'agregar', 'id_producto' => '1', 'cantidad' => '2']);
    [$estado, $respuesta] = $solicitar('index.php?ruta=equipo-registro', $campos + ['rol' => 'dueno', 'estado' => 'aprobado', 'id_usuario' => '1']);
    comprobar($estado === 303 && str_contains($respuesta, 'ruta=equipo-solicitud-enviada'), 'Registra y redirige a la confirmación');
    $usuarioWeb = $buscar($emailWeb);
    comprobar($usuarioWeb['rol'] === 'empleado' && $usuarioWeb['estado'] === 'pendiente', 'Ignora un rol y una aprobación enviados por el navegador');
    $sesion = $entorno->sesion();
    comprobar(!isset($sesion['cliente_id']) && !isset($sesion['usuario_id']) && !isset($sesion['equipo_id']), 'Solicitar acceso no inicia ninguna sesión autenticada');
    comprobar(!str_contains(serialize($sesion), $password) && !str_contains(serialize($sesion), $usuarioWeb['password_hash']), 'La sesión no guarda contraseñas ni hashes del equipo');
    [$estado, $html] = $solicitar('index.php?ruta=equipo-solicitud-enviada');
    comprobar($estado === 200 && str_contains($html, 'Pendiente de aprobación'), 'La confirmación explica que la solicitud todavía no habilita acceso');
    comprobar(!str_contains($html, $campos['cedula']) && !str_contains($html, $password), 'La confirmación no expone datos de identidad ni contraseñas');
    $cantidad = (int) $db->query('SELECT COUNT(*) FROM usuarios_equipo')->fetchColumn();
    $mensajes = (int) $db->query('SELECT COUNT(*) FROM correos_locales')->fetchColumn();
    $solicitar('index.php?ruta=equipo-solicitud-enviada');
    comprobar((int) $db->query('SELECT COUNT(*) FROM usuarios_equipo')->fetchColumn() === $cantidad
        && (int) $db->query('SELECT COUNT(*) FROM correos_locales')->fetchColumn() === $mensajes, 'Recargar no crea otra solicitud ni otro correo');
    comprobar($solicitar('index.php?ruta=equipo-solicitud-enviada&id_usuario=' . $usuarioWeb['id_usuario'], null, str_repeat('a', 32))[0] === 303, 'Otra sesión no obtiene una confirmación indicando el id');
    comprobar($solicitar('index.php?ruta=equipo-registro', $campos)[0] === 422, 'Un doble envío no duplica la solicitud');
    [, $carrito] = $solicitar('index.php?ruta=carrito');
    comprobar(str_contains($carrito, 'Carrito <span class="carrito__cantidad">2</span>'), 'El registro conserva el carrito');

    $db->exec("CREATE TRIGGER fallo_aviso BEFORE INSERT ON correos_locales FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fallo de entrega de prueba'");
    $camposFallo = array_replace($campos, ['cedula' => '54321098', 'email' => 'fallo.web@example.test']);
    comprobar($solicitar('index.php?ruta=equipo-registro', $camposFallo)[0] === 303, 'Un fallo de aviso permite continuar a la confirmación');
    [, $html] = $solicitar('index.php?ruta=equipo-solicitud-enviada');
    comprobar(str_contains($html, 'Tu solicitud quedó guardada, pero no pudimos enviar el correo'), 'Explica el fallo de correo sin afirmar una entrega');
    comprobar($buscar('fallo.web@example.test')['estado'] === 'pendiente', 'Conserva en la base la solicitud con aviso fallido');
    $db->exec('DROP TRIGGER fallo_aviso');
    $db->exec("CREATE TRIGGER fallo_guardado BEFORE INSERT ON usuarios_equipo FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fallo de guardado de prueba'");
    $camposFallo = array_replace($campos, ['cedula' => '43210987', 'email' => 'fallo.guardado@example.test']);
    comprobar($solicitar('index.php?ruta=equipo-registro', $camposFallo)[0] === 503, 'Informa un fallo de base sin mostrar éxito');
    comprobar($buscar('fallo.guardado@example.test') === false && $solicitar('index.php?ruta=equipo-solicitud-enviada')[0] === 303, 'Un guardado fallido no conserva una confirmación anterior');
    $db->exec('DROP TRIGGER fallo_guardado');
    [, $htmlLogin] = $solicitar('index.php?ruta=cliente-login');
    $csrfCliente = oculto($htmlLogin, 'csrf');
    comprobar($solicitar('index.php?ruta=cliente-login', ['csrf' => $csrfCliente, 'email' => $email, 'password' => $password])[0] === 303, 'La cuenta de cliente conserva su inicio de sesión');
    [, $html] = $solicitar('index.php?ruta=equipo-registro');
    $camposCliente = array_replace($campos, ['csrf' => oculto($html, 'csrf'), 'cedula' => '32109876', 'email' => 'cliente.solicita@example.test']);
    comprobar($solicitar('index.php?ruta=equipo-registro', $camposCliente)[0] === 303
        && $entorno->sesion()['cliente_id'] === $cliente['id_cliente'], 'Pedir acceso al equipo no reemplaza la identidad de cliente');
    comprobar((int) $db->query('SELECT COUNT(*) FROM clientes')->fetchColumn() === 1, 'El registro del equipo no crea cuentas de cliente adicionales');
    comprobar(!str_contains($entorno->errores(), $password) && !preg_match('/Fatal error:|Warning:|Notice:|Deprecated:/', $entorno->errores()), 'No registra contraseñas ni errores PHP');
    echo "Correcto: $comprobaciones comprobaciones del registro del equipo.\n";
} catch (Throwable $error) {
    $correcto = false;
    fwrite(STDERR, $error->getMessage() . "\n" . $error->getTraceAsString() . "\n");
    fwrite(STDERR, $entorno?->errores() ?? '');
} finally {
    $entorno?->cerrar();
}
exit($correcto ? 0 : 1);
