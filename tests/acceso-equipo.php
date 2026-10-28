<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/soporte.php';

use App\Models\AccesoCliente;
use App\Models\AccesoEquipo;
use App\Models\Cliente;
use App\Models\UsuarioEquipo;

$entorno = null;
$comprobaciones = 0;
$correcto = true;

try {
    $entorno = new EntornoPruebas('equipo');
    $db = $entorno->db;
    $usuarios = new UsuarioEquipo($db);
    $acceso = new AccesoEquipo($db);
    $password = ' ClaveEquipo24! ';
    $cedula = '1234567';
    $empleado = $usuarios->solicitar('Equipo <uno>', $cedula, 'equipo@example.test', $password, $password);
    $pendiente = $usuarios->solicitar('Pendiente', '2345678', 'pendiente@example.test', $password, $password);
    $rechazado = $usuarios->solicitar('Rechazado', '3456789', 'rechazado@example.test', $password, $password);
    $db->exec("UPDATE usuarios_equipo SET estado = 'aprobado' WHERE id_usuario = " . $empleado['id_usuario']);
    $db->exec("UPDATE usuarios_equipo SET estado = 'rechazado' WHERE id_usuario = " . $rechazado['id_usuario']);
    $mensaje = static function (callable $operacion): string {
        try {
            $operacion();
        } catch (DomainException $error) {
            return $error->getMessage();
        }
        throw new RuntimeException('La operación debía rechazar el acceso.');
    };
    $limite = static function (string $numero) use ($db): array|false {
        $consulta = $db->prepare('SELECT * FROM intentos_acceso_equipo WHERE clave_cedula = ?');
        $consulta->execute([hash('sha256', (string) (int) $numero)]);
        return $consulta->fetch();
    };
    $usuario = $acceso->autenticar(' 01234567 ', $password);
    comprobar((int) $usuario['id_usuario'] === $empleado['id_usuario'] && !isset($usuario['password_hash']), 'Normaliza la cédula sin exponer el hash');
    $generico = $mensaje(fn () => $acceso->autenticar($cedula, 'Incorrecta24!'));
    comprobar($generico === $mensaje(fn () => $acceso->autenticar('99999999', 'Incorrecta24!')), 'Una cuenta inexistente y una contraseña incorrecta reciben el mismo mensaje');
    comprobar($generico === $mensaje(fn () => $acceso->autenticar('1.234.567', $password)), 'No acepta formato inválido de cédula');
    rechaza(fn () => $acceso->autenticar($cedula, trim($password)), 'La contraseña conserva sus espacios');
    rechaza(fn () => $acceso->autenticar($cedula, "Clave\0Invalida"), 'Rechaza bytes nulos');
    $acceso->autenticar($cedula, $password);
    comprobar($limite($cedula) === false, 'Un acceso correcto elimina los intentos anteriores');
    for ($i = 0; $i < 6; $i++) {
        rechaza(fn () => (new AccesoEquipo($db))->autenticar($i % 2 ? $cedula : '0' . $cedula, 'Incorrecta24!'), 'El contador persiste y no se elude con ceros iniciales');
    }
    comprobar((int) $limite($cedula)['intentos'] === 6 && $limite($cedula)['bloqueado_hasta'] !== null, 'Seis fallos bloquean el acceso');
    comprobar(str_contains($mensaje(fn () => $acceso->autenticar($cedula, $password)), 'Esperá un minuto'), 'El bloqueo rechaza incluso la contraseña correcta');
    $db->exec('UPDATE intentos_acceso_equipo SET bloqueado_hasta = DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 SECOND)');
    rechaza(fn () => $acceso->autenticar($cedula, 'Incorrecta24!'), 'El bloqueo vencido permite un intento nuevo');
    comprobar((int) $limite($cedula)['intentos'] === 1, 'Reinicia el contador después de un bloqueo');
    $db->exec('UPDATE intentos_acceso_equipo SET actualizado_en = DATE_SUB(UTC_TIMESTAMP(), INTERVAL 11 MINUTE)');
    rechaza(fn () => $acceso->autenticar($cedula, 'Incorrecta24!'), 'Rechaza la contraseña incorrecta en una ventana nueva');
    comprobar((int) $limite($cedula)['intentos'] === 1, 'Los intentos aislados vencen a los diez minutos');
    $acceso->autenticar($cedula, $password);
    $consulta = $db->prepare('UPDATE usuarios_equipo SET password_hash = ? WHERE id_usuario = ?');
    $consulta->execute([password_hash($password, PASSWORD_BCRYPT, ['cost' => 4]), $empleado['id_usuario']]);
    $acceso->autenticar($cedula, $password);
    $hash = $db->query('SELECT password_hash FROM usuarios_equipo WHERE id_usuario = ' . $empleado['id_usuario'])->fetchColumn();
    comprobar(!password_needs_rehash($hash, PASSWORD_DEFAULT), 'Actualiza el hash antiguo de una cuenta aprobada');
    $larga = str_repeat('a', 72);
    $usuarios->solicitar('Contraseña límite', '4567890', 'larga@example.test', $larga, $larga);
    rechaza(fn () => $acceso->autenticar('4567890', $larga . 'extra'), 'Rechaza el truncamiento de bcrypt a 72 bytes');
    comprobar($acceso->buscarAprobado($pendiente['id_usuario']) === null && $acceso->buscarAprobado($rechazado['id_usuario']) === null, 'Las cuentas pendientes y rechazadas no son identidades aprobadas');

    $clientes = new Cliente($db);
    $cliente = $clientes->registrar('Cliente aparte', $empleado['email'], $password, $password);
    $clientes->verificar($cliente['email'], $cliente['codigo']);
    rechaza(fn () => (new AccesoCliente($db))->autenticar($cliente['email'], 'Incorrecta24!'), 'Registra un fallo de cliente por separado');
    comprobar($acceso->autenticar($cedula, $password)['rol'] === 'empleado'
        && (int) $db->query('SELECT SUM(intentos) FROM intentos_acceso_cliente')->fetchColumn() === 1, 'El acceso del equipo no modifica los contadores de clientes');

    $entorno->iniciarServidor();
    $solicitar = $entorno->solicitar(...);
    comprobar($solicitar('/equipo/panel')[0] === 303, 'El panel exige una sesión del equipo');
    [$estado, $html] = $solicitar('/equipo/login');
    comprobar($estado === 200 && str_contains($html, 'js/registro.js') && str_contains($html, 'autocomplete="current-password"'), 'El acceso reutiliza la vista de contraseña y sus componentes');
    comprobar(str_contains($html, 'Cache-Control: no-store') && str_contains($html, '/equipo/registro'), 'El acceso no se almacena en caché y enlaza la solicitud');
    $anonima = $entorno->idSesion();
    $csrf = oculto($html, 'csrf');
    $campos = ['csrf' => $csrf, 'cedula' => $cedula, 'password' => $password];
    comprobar($solicitar('/equipo/salir')[0] === 405, 'Cerrar sesión exige POST');
    comprobar($solicitar('/equipo/panel', $campos)[0] === 405, 'El panel no acepta escrituras');
    comprobar($solicitar('/equipo/login', array_replace($campos, ['csrf' => 'incorrecto']))[0] === 422, 'El acceso exige CSRF');
    foreach (['cedula', 'password'] as $campo) {
        comprobar($solicitar('/equipo/login', array_replace($campos, [$campo => ['x']]))[0] === 422, 'Rechaza un campo malformado: ' . $campo);
    }
    [, $htmlError] = $solicitar('/equipo/login', array_replace($campos, ['cedula' => '<script>x</script>']));
    comprobar(str_contains($htmlError, '&lt;script&gt;x&lt;/script&gt;') && !str_contains($htmlError, '<script>x</script>'), 'Escapa la cédula recuperada en un error');
    comprobar(!str_contains($htmlError, $password), 'No devuelve la contraseña en la respuesta');
    [, $htmlError] = $solicitar('/equipo/login', array_replace($campos, ['cedula' => '2345678', 'password' => 'Incorrecta24!']));
    comprobar(str_contains($htmlError, $generico) && !str_contains($htmlError, 'Tu solicitud'), 'Una contraseña incorrecta no revela el estado de aprobación');
    foreach (['2345678', '3456789'] as $numero) {
        [$estado, $htmlError] = $solicitar('/equipo/login', array_replace($campos, ['cedula' => $numero]));
        comprobar($estado === 422 && str_contains($htmlError, 'Tu solicitud no está aprobada'), 'Rechaza el acceso de una solicitud no aprobada');
        comprobar(!isset($entorno->sesion()['equipo_id']), 'No concede sesión a una solicitud no aprobada');
    }
    $solicitar('/carrito', ['csrf' => $csrf, 'accion' => 'agregar', 'id_producto' => '1', 'cantidad' => '2']);
    [$estado, $checkout] = $solicitar('/pedidos/realizar');
    comprobar($estado === 303 && str_contains($checkout, 'Location: /clientes/login')
        && !isset($entorno->sesion()['formularios']), 'Un visitante debe ingresar como cliente antes de abrir el pedido');
    [$estado, $respuesta] = $solicitar('/equipo/login', $campos + ['rol' => 'dueno', 'estado' => 'aprobado', 'id_usuario' => '999', 'continuar' => 'https://example.test']);
    comprobar($estado === 303 && str_contains($respuesta, 'Location: /equipo/panel'), 'El acceso correcto redirige solo al panel local');
    $autenticada = $entorno->idSesion();
    comprobar($autenticada !== $anonima, 'Renueva la cookie al autenticar');
    comprobar(str_contains(strtolower($respuesta), 'httponly') && str_contains(strtolower($respuesta), 'samesite=lax'), 'La cookie mantiene sus protecciones');
    $sesion = $entorno->sesion();
    comprobar($sesion['equipo_id'] === $empleado['id_usuario'] && $sesion['csrf'] !== $csrf, 'La identidad viene del servidor y el CSRF se renueva');
    comprobar($solicitar('/pedidos/realizar')[0] === 303
        && !isset($entorno->sesion()['formularios']), 'La sesión del equipo no habilita el formulario de pedidos del cliente');
    comprobar(!isset($sesion['rol']) && !isset($sesion['password']) && !isset($sesion['formularios']) && !isset($sesion['cliente_id']), 'No conserva privilegios, contraseñas ni formularios anteriores en sesión');
    [$estado, $panel] = $solicitar('/equipo/panel');
    comprobar($estado === 200 && str_contains($panel, 'Equipo &lt;uno&gt;') && !str_contains($panel, 'Equipo <uno>'), 'La pantalla protegida escapa el nombre del usuario');
    comprobar(str_contains($panel, 'Tu rol: Empleado.') && str_contains($panel, 'Cache-Control: no-store'), 'El rol real se consulta en la base y el panel no se guarda en caché');
    comprobar(!str_contains($panel, 'Administrar personal') && !str_contains($panel, 'Solicitar alta de personal')
        && !str_contains($panel, 'Bandeja de correo local'), 'El panel de empleado no muestra los accesos exclusivos de otros roles');
    $documento = new DOMDocument();
    @$documento->loadHTML('<?xml encoding="UTF-8">' . $panel);
    $xpath = new DOMXPath($documento);
    comprobar($xpath->query('//a[@aria-disabled="true"]')->length === 0
        && $xpath->query('//form[not(contains(@action,"/equipo/salir"))]')->length === 0,
        'El panel muestra solo accesos disponibles y no agrega formularios de operación');
    comprobar($solicitar('/equipo/login')[0] === 303, 'Una sesión del equipo ya iniciada vuelve al panel');
    comprobar($solicitar('/equipo/panel', null, $anonima)[0] === 303, 'La cookie anterior no obtiene acceso al panel');
    comprobar($solicitar('/equipo/salir', ['csrf' => $csrf])[0] === 422, 'El CSRF anterior no puede cerrar la sesión nueva');
    [, $carrito] = $solicitar('/carrito');
    comprobar(str_contains($carrito, 'Carrito <span class="carrito__cantidad">2</span>'), 'El acceso conserva el carrito');
    comprobar(str_contains($carrito, 'cuenta__enlace') && str_contains($carrito, '/equipo/panel'), 'El menú público permite volver al panel');
    $db->exec("UPDATE usuarios_equipo SET rol = 'administrador' WHERE id_usuario = " . $empleado['id_usuario']);
    [, $panelAdmin] = $solicitar('/equipo/panel');
    comprobar(str_contains($panelAdmin, 'Tu rol: Administrador.'), 'Revalida un cambio de rol en la siguiente petición');
    comprobar(str_contains($panelAdmin, '+ Agregar producto') && str_contains($panelAdmin, '+ Registrar mercadería')
        && !str_contains($panelAdmin, 'Administrar personal') && !str_contains($panelAdmin, 'aria-disabled="true"'),
        'El panel de administrador conserva sus acciones disponibles sin accesos del dueño ni módulos futuros');
    $db->exec("UPDATE usuarios_equipo SET rol = 'dueno' WHERE id_usuario = " . $empleado['id_usuario']);
    [, $panelDueno] = $solicitar('/equipo/panel');
    comprobar(str_contains($panelDueno, 'Tu rol: Dueño.'), 'Reconoce el rol de dueño guardado en la base');
    comprobar(str_contains($panelDueno, 'href="/equipo/permisos">Administrar personal</a>') && str_contains($panelDueno, 'Solicitudes de acceso')
        && !str_contains($panelDueno, 'aria-disabled="true"'),
        'El panel del dueño permite administrar personal y solicitudes sin módulos futuros');
    $csrfNuevo = $entorno->sesion()['csrf'];
    comprobar($solicitar('/equipo/salir', ['csrf' => $csrfNuevo])[0] === 303, 'Cierra la sesión con POST y CSRF');
    comprobar($entorno->idSesion() !== $autenticada && !isset($entorno->sesion()['equipo_id']), 'Salir renueva la cookie y elimina la identidad');
    comprobar($solicitar('/equipo/panel', null, $autenticada)[0] === 303, 'Una cookie cerrada no recupera el acceso');

    $entrar = static function () use ($solicitar, $cedula, $password): array {
        $csrf = oculto($solicitar('/equipo/login')[1], 'csrf');
        return $solicitar('/equipo/login', ['csrf' => $csrf, 'cedula' => $cedula, 'password' => $password]);
    };
    comprobar($entrar()[0] === 303, 'Un dueño aprobado puede iniciar sesión');
    $entorno->sesion(static fn (array $datos): array => array_replace($datos, ['equipo_ultimo_acceso' => time() - 1801]));
    comprobar($solicitar('/equipo/panel')[0] === 303 && !isset($entorno->sesion()['equipo_id']), 'La sesión vence tras treinta minutos de inactividad');
    $entrar();
    $db->exec("UPDATE usuarios_equipo SET estado = 'rechazado' WHERE id_usuario = " . $empleado['id_usuario']);
    comprobar($solicitar('/equipo/panel')[0] === 303 && !isset($entorno->sesion()['equipo_id']), 'Revocar la aprobación invalida inmediatamente el acceso');
    $db->exec("UPDATE usuarios_equipo SET estado = 'aprobado', rol = 'administrador' WHERE id_usuario = " . $empleado['id_usuario']);
    comprobar($entrar()[0] === 303, 'Un administrador aprobado puede iniciar sesión');
    $csrfCliente = oculto($solicitar('/clientes/login')[1], 'csrf');
    comprobar($solicitar('/clientes/login', ['csrf' => $csrfCliente, 'email' => $cliente['email'], 'password' => $password])[0] === 303, 'Se puede pasar del equipo a una cuenta de cliente');
    comprobar(isset($entorno->sesion()['cliente_id']) && !isset($entorno->sesion()['equipo_id']), 'La identidad de cliente sustituye la identidad del equipo');
    comprobar($solicitar('/equipo/panel')[0] === 303, 'Una sesión de cliente no accede al panel del equipo');
    comprobar($entrar()[0] === 303 && !isset($entorno->sesion()['cliente_id']), 'Volver al equipo sustituye la identidad de cliente');
    [, $carrito] = $solicitar('/carrito');
    comprobar(str_contains($carrito, 'Carrito <span class="carrito__cantidad">2</span>'), 'Los cambios de identidad conservan el carrito');
    comprobar((int) $db->query("SELECT COUNT(*) FROM usuarios_equipo WHERE estado = 'pendiente'")->fetchColumn() === 2, 'El acceso no aprueba solicitudes pendientes');
    comprobar((int) $db->query('SELECT COUNT(*) FROM correos_locales')->fetchColumn() === 0, 'El acceso no envía correos ni genera códigos');
    comprobar(!str_contains($entorno->errores(), $password) && !preg_match('/Fatal error:|Warning:|Notice:|Deprecated:/', $entorno->errores()), 'El flujo no registra contraseñas ni errores PHP');
    echo "Correcto: $comprobaciones comprobaciones del acceso del equipo.\n";
} catch (Throwable $error) {
    $correcto = false;
    fwrite(STDERR, $error->getMessage() . "\n" . $error->getTraceAsString() . "\n");
    fwrite(STDERR, $entorno?->errores() ?? '');
} finally {
    $entorno?->cerrar();
}
exit($correcto ? 0 : 1);
