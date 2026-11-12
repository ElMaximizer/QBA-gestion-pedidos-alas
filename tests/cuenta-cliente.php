<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/soporte.php';

use App\Models\AccesoCliente;
use App\Core\Sesion;
use App\Models\Carrito;
use App\Models\Cliente;
use App\Models\CuentaCliente;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\UsuarioEquipo;
use App\Services\ConfiguracionCliente;

$entorno = null;
$comprobaciones = 0;
$correcto = true;
putenv('QBA_MAIL_TRANSPORT=local');

try {
    $entorno = new EntornoPruebas('hu17');
    $db = $entorno->db;
    $db->exec('ALTER TABLE clientes DROP COLUMN version_sesion');
    $db->exec(file_get_contents(dirname(__DIR__) . '/database/comprobantes-transferencia.sql'));
    $clientes = new Cliente($db);
    $cuentas = new CuentaCliente($db);
    $acceso = new AccesoCliente($db);
    $password = ' ClaveCuentaPrueba! ';
    $email = 'cuenta@example.test';
    $primera = $clientes->registrar('Cliente <prueba>', $email, $password, $password);
    $clientes->verificar($email, $primera['codigo']);
    $id = $primera['id_cliente'];
    $segunda = $clientes->registrar('Otra cuenta', 'otra@example.test', $password, $password);
    $clientes->verificar($segunda['email'], $segunda['codigo']);
    $otroId = $segunda['id_cliente'];
    $columnas = array_column($db->query('SHOW COLUMNS FROM clientes')->fetchAll(), 'Field');
    $antes = $db->query('SELECT * FROM clientes ORDER BY id_cliente')->fetchAll();
    $migracion = file_get_contents(dirname(__DIR__) . '/database/cuenta-clientes.sql');
    $db->exec($migracion);
    $db->exec($migracion);
    comprobar(
        $db->query('SELECT ' . implode(',', $columnas) . ' FROM clientes ORDER BY id_cliente')->fetchAll() === $antes,
        'La migración incorpora la versión de sesión, se puede repetir y conserva las cuentas existentes',
    );
    $version = $acceso->buscarVerificado($id)['version_acceso'];
    $otraVersion = $acceso->buscarVerificado($otroId)['version_acceso'];
    rechaza(
        fn() => $cuentas->actualizarNombre($otroId, $version, 'Ajeno'),
        'Una versión de otra identidad no autoriza cambios',
    );
    rechaza(fn() => $cuentas->actualizarNombre($id, $version, ''), 'El nombre no puede quedar vacío');
    rechaza(
        fn() => $cuentas->solicitarCorreo($id, $version, $password, $segunda['email']),
        'No permite un correo ya registrado',
    );
    for ($intento = 0; $intento < 6; $intento++) {
        rechaza(
            fn() => $cuentas->solicitarCorreo($otroId, $otraVersion, 'incorrecta', 'nuevo@example.test'),
            'Los fallos de contraseña actual se conservan en la base temporal',
        );
    }
    rechaza(
        fn() => $cuentas->solicitarCorreo($otroId, $otraVersion, $password, 'nuevo@example.test'),
        'El bloqueo también impide nuevos intentos con contraseña correcta',
    );
    $db->prepare('DELETE FROM intentos_acceso_cliente WHERE clave_email = ?')
        ->execute([hash('sha256', 'cuenta:' . $otroId)]);
    $cambio = $cuentas->solicitarCorreo($otroId, $otraVersion, $password, 'nuevo@example.test');
    comprobar($cuentas->datos($otroId)['email'] === $segunda['email'], 'Conserva el correo sin verificar');
    rechaza(
        fn() => $cuentas->verificarCorreo($id, $version, $cambio['codigo']),
        'El código de otra cuenta no permite cambiar el correo propio',
    );
    $incorrecto = $cambio['codigo'] === '100000' ? '100001' : '100000';
    for ($intento = 0; $intento < 5; $intento++) {
        rechaza(
            fn() => $cuentas->verificarCorreo($otroId, $otraVersion, $incorrecto),
            'Los intentos incorrectos del código se conservan',
        );
    }
    rechaza(
        fn() => $cuentas->verificarCorreo($otroId, $otraVersion, $cambio['codigo']),
        'Cinco errores invalidan el código correcto',
    );
    $cuentas->cancelarCorreo($otroId, $otraVersion);
    rechaza(
        fn() => $cuentas->solicitarCorreo($otroId, $otraVersion, $password, 'nuevo@example.test'),
        'Cancelar no permite eludir el minuto de espera',
    );
    $enfriar = static function (int $cliente) use ($db): void {
        $db->prepare('UPDATE cambios_correo_cliente SET enviado_en = DATE_SUB(UTC_TIMESTAMP(),
            INTERVAL 2 MINUTE) WHERE id_cliente = ?')->execute([$cliente]);
    };
    $enfriar($otroId);
    $cambio = $cuentas->solicitarCorreo($otroId, $otraVersion, $password, 'nuevo@example.test');
    $db->prepare('UPDATE cambios_correo_cliente SET vence_en = DATE_SUB(UTC_TIMESTAMP(),
        INTERVAL 1 SECOND) WHERE id_cliente = ?')->execute([$otroId]);
    rechaza(
        fn() => $cuentas->verificarCorreo($otroId, $otraVersion, $cambio['codigo']),
        'Un código vencido no cambia la cuenta',
    );
    $enfriar($otroId);
    $cambio = $cuentas->solicitarCorreo($otroId, $otraVersion, $password, 'nuevo@example.test');
    $ocupante = $clientes->registrar('Registro simultáneo', 'nuevo@example.test', $password, $password);
    rechaza(
        fn() => $cuentas->verificarCorreo($otroId, $otraVersion, $cambio['codigo']),
        'Un registro simultáneo no permite duplicar el correo al confirmar',
    );
    comprobar($cuentas->datos($otroId)['email'] === $segunda['email'], 'El conflicto conserva el correo anterior');
    $cuentas->cancelarCorreo($otroId, $otraVersion);
    $enfriar($otroId);
    $cambio = $cuentas->solicitarCorreo($otroId, $otraVersion, $password, 'temporal@example.test');
    $versionTemporal = $cuentas->verificarCorreo($otroId, $otraVersion, $cambio['codigo']);
    $enfriar($otroId);
    $cambio = $cuentas->solicitarCorreo($otroId, $versionTemporal, $password, $segunda['email']);
    $versionRestaurada = $cuentas->verificarCorreo($otroId, $versionTemporal, $cambio['codigo']);
    rechaza(
        fn() => $cuentas->actualizarNombre($otroId, $otraVersion, 'Sesión antigua'),
        'Volver al correo anterior no reactiva sesiones previamente revocadas',
    );
    $autenticada = $acceso->autenticar($segunda['email'], $password);
    $otraPassword = 'OtraClaveSimultanea!';
    $codigoPassword = $cuentas->solicitarCodigoPassword($otroId, $versionRestaurada, $password, $otraPassword, $otraPassword)['codigo'];
    $cuentas->verificarPassword($otroId, $versionRestaurada, $codigoPassword);
    putenv('QBA_DB_NAME=' . $entorno->base);
    rechaza(
        fn() => Sesion::iniciarCliente($otroId, $autenticada['version_acceso']),
        'Un acceso comprobado antes de un cambio de contraseña no adopta las nuevas credenciales',
    );
    $enfriar($otroId);
    $versionActual = $acceso->buscarVerificado($otroId)['version_acceso'];
    $db->exec("CREATE TRIGGER fallo_correo_cuenta BEFORE INSERT ON correos_locales
        FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fallo de correo ficticio'");
    $enviado = (new ConfiguracionCliente($db))->solicitarCorreo(
        $otroId, $versionActual, $otraPassword, 'fallo@example.test',
    );
    $db->exec('DROP TRIGGER fallo_correo_cuenta');
    comprobar(
        !$enviado && $cuentas->datos($otroId)['email'] === $segunda['email'] &&
            $cuentas->datos($otroId)['email_pendiente'] === 'fallo@example.test',
        'Un fallo de correo mantiene el acceso vigente y permite recuperar la solicitud pendiente',
    );

    $carrito = new Carrito(new Producto($db));
    $carrito->actualizar('agregar', '1', '1');
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+iG0sAAAAASUVORK5CYII=');
    $pedido = (new Pedido($db))->crear(
        ['cliente_nombre' => 'Cliente histórico', 'telefono' => '099123456',
            'fecha_retiro' => date('Y-m-d', strtotime('+2 days')), 'hora_retiro' => '10:00',
            'notas' => '', 'metodo_pago' => 'transferencia'],
        Carrito::cantidades(),
        Carrito::firma($carrito->articulos()),
        bin2hex(random_bytes(32)),
        $id,
        null,
        ['mime' => 'image/png', 'contenido' => $png],
    );
    $historico = [];
    foreach (['pedidos', 'pedido_detalles', 'comprobantes_transferencia'] as $tabla) {
        $historico[$tabla] = $db->query('SELECT * FROM ' . $tabla . ' WHERE id_pedido = ' . $pedido)->fetchAll();
    }
    $entorno->iniciarServidor();
    $solicitar = $entorno->solicitar(...);
    comprobar($solicitar('/clientes/cuenta')[0] === 303, 'Un visitante debe iniciar sesión');
    comprobar(
        $solicitar('/clientes/cuenta/eliminar', ['confirmar' => '1', 'password' => $password])[0] === 303,
        'Un visitante no puede eliminar cuentas',
    );
    $entrar = static function (string $correo, string $clave) use ($solicitar): void {
        $csrf = oculto($solicitar('/clientes/login')[1], 'csrf');
        comprobar(
            $solicitar('/clientes/login', ['csrf' => $csrf, 'email' => $correo, 'password' => $clave])[0] === 303,
            'Inicia sesión con las credenciales vigentes',
        );
    };
    $entrar($email, $password);
    [, $pagina] = $solicitar('/clientes/cuenta');
    comprobar(
        str_contains($pagina, 'Configurar mi cuenta') && str_contains($pagina, 'Cliente &lt;prueba&gt;'),
        'El menú muestra el acceso y escapa los datos del perfil',
    );
    $csrf = oculto($pagina, 'csrf');
    comprobar(
        $solicitar('/clientes/cuenta', ['accion' => 'nombre', 'nombre' => 'No guardar', 'csrf' => 'incorrecto'])[0] === 422 &&
            $cuentas->datos($id)['nombre'] === 'Cliente <prueba>',
        'CSRF inválido no modifica el perfil',
    );
    comprobar(
        $solicitar('/clientes/cuenta', ['accion' => 'nombre', 'nombre' => ['x'], 'csrf' => $csrf])[0] === 422,
        'Rechaza estructuras inesperadas en el nombre',
    );
    comprobar(
        $solicitar('/clientes/cuenta', ['accion' => 'nombre', 'nombre' => 'Nuevo <nombre>',
            'id_cliente' => $otroId, 'rol' => 'dueno', 'csrf' => $csrf])[0] === 303 &&
            $cuentas->datos($id)['nombre'] === 'Nuevo <nombre>' && $cuentas->datos($otroId)['nombre'] === 'Otra cuenta',
        'La identidad sale de la sesión y no de los campos enviados',
    );
    [, $pagina] = $solicitar('/clientes/cuenta');
    comprobar(str_contains($pagina, 'Nuevo &lt;nombre&gt;'), 'El nombre nuevo se refleja en la cuenta');

    $cookie = static function (string $respuesta): string {
        if (!preg_match('/Set-Cookie: QBA_SESSION=([^;\r\n]+)/i', $respuesta, $coincidencia)) {
            throw new RuntimeException('No se emitió una cookie de prueba.');
        }
        return $coincidencia[1];
    };
    $otraSesion = static function (string $correo, string $clave) use ($entorno, $cookie): string {
        [, $login] = $entorno->solicitar('/clientes/login', null, bin2hex(random_bytes(16)));
        [, $respuesta] = $entorno->solicitar('/clientes/login', [
            'csrf' => oculto($login, 'csrf'), 'email' => $correo, 'password' => $clave,
        ], $cookie($login));
        return $cookie($respuesta);
    };
    $sesionAjena = $otraSesion($email, $password);
    $nueva = ' OtraClaveCuenta! ';
    $formPassword = ['accion' => 'password', 'csrf' => $csrf, 'password' => 'Incorrecta',
        'new_password' => $nueva, 'confirm_password' => $nueva];
    comprobar($solicitar('/clientes/cuenta', $formPassword)[0] === 422, 'Exige la contraseña actual');
    $formPassword['password'] = $password;
    comprobar($solicitar('/clientes/cuenta', $formPassword)[0] === 422, 'No cambia contraseña sin código');
    comprobar($solicitar('/clientes/cuenta', ['accion' => 'codigo-password', 'password' => $password, 'new_password' => $nueva, 'confirm_password' => $nueva, 'csrf' => 'invalido'])[0] === 422, 'Pedir código exige CSRF');
    comprobar($solicitar('/clientes/cuenta', ['accion' => 'codigo-password', 'password' => ['x'], 'csrf' => $csrf])[0] === 422, 'Rechaza contraseña HTTP malformada al pedir código');
    comprobar($solicitar('/clientes/cuenta', ['accion' => 'codigo-password', 'password' => $password, 'new_password' => $nueva, 'confirm_password' => $nueva, 'csrf' => $csrf])[0] === 303, 'Pide código al correo actual');
    $correoCodigo = $db->query("SELECT cuerpo FROM correos_locales ORDER BY id_correo DESC LIMIT 1")->fetchColumn();
    preg_match('/es: ([0-9]{6})/', $correoCodigo, $codigoRecibido);
    $formPassword = ['accion' => 'password', 'csrf' => $csrf, 'codigo' => $codigoRecibido[1]];
    comprobar($solicitar('/clientes/cuenta', $formPassword)[0] === 303, 'Cambia la contraseña con código válido');
    comprobar(
        $entorno->solicitar('/clientes/cuenta', null, $sesionAjena)[0] === 303,
        'Cambiar contraseña invalida una segunda sesión autenticada',
    );
    rechaza(fn() => $acceso->autenticar($email, $password), 'La contraseña anterior deja de servir');
    comprobar((int) $acceso->autenticar($email, $nueva)['id_cliente'] === $id, 'Conserva espacios de la nueva contraseña');
    comprobar($solicitar('/clientes/cuenta', $formPassword)[0] === 422, 'La renovación también invalida el CSRF anterior');
    [, $pagina] = $solicitar('/clientes/cuenta');
    $csrf = oculto($pagina, 'csrf');
    $sesionAjena = $otraSesion($email, $nueva);
    comprobar(
        $solicitar('/clientes/cuenta', ['accion' => 'correo', 'csrf' => $csrf,
            'password' => $nueva, 'email' => 'confirmada@example.test'])[0] === 303 &&
            $cuentas->datos($id)['email'] === $email,
        'El envío del código no reemplaza el correo actual',
    );
    $consulta = $db->prepare('SELECT cuerpo FROM correos_locales WHERE destinatario = ? ORDER BY id_correo DESC LIMIT 1');
    $consulta->execute(['confirmada@example.test']);
    comprobar(
        (bool) preg_match('/es: ([0-9]{6})/', $consulta->fetchColumn(), $coincidencia),
        'La prueba entrega el código exclusivamente en la bandeja local',
    );
    comprobar(
        $solicitar('/clientes/cuenta', ['accion' => 'verificar-correo', 'csrf' => $csrf,
            'codigo' => $coincidencia[1]])[0] === 303 &&
            $cuentas->datos($id)['email'] === 'confirmada@example.test',
        'El código correcto confirma el nuevo correo',
    );
    comprobar(
        $entorno->solicitar('/clientes/cuenta', null, $sesionAjena)[0] === 303,
        'El cambio de correo también invalida las otras sesiones',
    );
    rechaza(fn() => $acceso->autenticar($email, $nueva), 'El correo anterior deja de permitir el acceso');
    $sesionAjena = $otraSesion('confirmada@example.test', $nueva);
    [, $confirmacion] = $solicitar('/clientes/cuenta/eliminar');
    $csrf = oculto($confirmacion, 'csrf');
    comprobar(
        $solicitar('/clientes/cuenta/eliminar', ['csrf' => $csrf, 'password' => $nueva])[0] === 422,
        'Eliminar exige la confirmación explícita además de la contraseña',
    );
    comprobar(
        $solicitar('/clientes/cuenta/eliminar', ['csrf' => $csrf, 'password' => 'Incorrecta', 'confirmar' => '1'])[0] === 422,
        'Una contraseña incorrecta no elimina la cuenta',
    );
    comprobar(
        $solicitar('/clientes/cuenta/eliminar', ['csrf' => $csrf, 'password' => $nueva, 'confirmar' => '1'])[0] === 303 &&
            $acceso->buscarVerificado($id) === null,
        'Elimina el acceso de la cuenta confirmada',
    );
    foreach ($historico as $tabla => $filas) {
        comprobar(
            $db->query('SELECT * FROM ' . $tabla . ' WHERE id_pedido = ' . $pedido)->fetchAll() === $filas,
            'La eliminación conserva los datos y referencias de ' . $tabla,
        );
    }
    comprobar(
        $entorno->solicitar('/clientes/cuenta', null, $sesionAjena)[0] === 303 &&
            $solicitar('/clientes/cuenta')[0] === 303,
        'Una cuenta eliminada pierde todas sus sesiones',
    );
    $reutilizada = $clientes->registrar('Nueva persona', 'confirmada@example.test', $password, $password);
    comprobar($reutilizada['id_cliente'] !== $id, 'Reutilizar el correo crea otra identidad sin los pedidos antiguos');
    $equipo = (new UsuarioEquipo($db))->solicitar(
        'Empleado de prueba', '6000031', 'equipo@example.test', $password, $password,
    );
    $db->prepare("UPDATE usuarios_equipo SET estado = 'aprobado' WHERE id_usuario = ?")
        ->execute([$equipo['id_usuario']]);
    $csrf = oculto($solicitar('/equipo/login')[1], 'csrf');
    comprobar(
        $solicitar('/equipo/login', ['csrf' => $csrf, 'cedula' => '6000031', 'password' => $password])[0] === 303 &&
            $solicitar('/clientes/cuenta')[0] === 303 &&
            !str_contains($solicitar('/')[1], 'Configurar mi cuenta'),
        'Las cuentas del equipo no obtienen la configuración de clientes',
    );
    comprobar(
        !preg_match('/Fatal error:|Warning:|Notice:|Deprecated:/', $entorno->errores()),
        'El recorrido no genera errores PHP',
    );
    echo "Correcto: perfil, contraseña, correo verificado, sesiones, eliminación e historial.\n";
} catch (Throwable $error) {
    $correcto = false;
    fwrite(STDERR, $error->getMessage() . "\n" . $error->getTraceAsString() . "\n");
    fwrite(STDERR, $entorno?->errores() ?? '');
} finally {
    $entorno?->cerrar();
}
exit($correcto ? 0 : 1);
