<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/soporte.php';

use App\Models\Cliente;
use App\Services\CorreoVerificacion;
use App\Services\RegistroCliente;

$entorno = null;
$comprobaciones = 0;
$correcto = true;

try {
    $entorno = new EntornoPruebas('hu16');
    $db = $entorno->db;
    $clientes = new Cliente($db);
    $registroClientes = new RegistroCliente($db, new CorreoVerificacion($db, ['transport' => 'local']));
    $password = ' ClaveSegura16! ';
    $email = 'registro.modelo@example.test';
    $buscar = static function (string $correo) use ($db): array|false {
        $consulta = $db->prepare('SELECT * FROM clientes WHERE email = ?');
        $consulta->execute([$correo]);
        return $consulta->fetch();
    };
    $codigo = static function (string $correo) use ($db): string {
        $consulta = $db->prepare('SELECT cuerpo FROM correos_locales WHERE destinatario = ? ORDER BY id_correo DESC LIMIT 1');
        $consulta->execute([$correo]);
        if (!preg_match('/es: ([0-9]{6})/', (string) $consulta->fetchColumn(), $coincidencia)) {
            throw new RuntimeException('No se generó el mensaje de verificación.');
        }
        return $coincidencia[1];
    };
    foreach ([
        ['', $email, $password, $password],
        [str_repeat('a', 121), $email, $password, $password],
        ['Cliente', 'correo-invalido', $password, $password],
        ['Cliente', str_repeat('a', 180) . '@ejemplo.test', $password, $password],
        ['Cliente', $email, 'corta', 'corta'],
        ['Cliente', $email, str_repeat('a', 73), str_repeat('a', 73)],
        ['Cliente', $email, str_repeat('á', 37), str_repeat('á', 37)],
        ['Cliente', $email, "ClaveValida\0", "ClaveValida\0"],
        ['Cliente', $email, $password, 'Diferente16!'],
    ] as $datos) {
        rechaza(fn () => $registroClientes->registrar(...$datos), 'Rechaza un registro inválido');
    }
    comprobar((int) $db->query('SELECT COUNT(*) FROM clientes')->fetchColumn() === 0, 'Los datos inválidos no crean cuentas');
    comprobar((int) $db->query('SELECT COUNT(*) FROM correos_locales')->fetchColumn() === 0, 'Los datos inválidos no generan mensajes');
    $nuevo = $registroClientes->registrar('  Cliente de prueba  ', '  REGISTRO.MODELO@EXAMPLE.TEST ', $password, $password);
    $registro = $buscar($email);
    comprobar($nuevo['email'] === $email && $registro['nombre'] === 'Cliente de prueba', 'Normaliza correo y nombre');
    comprobar(password_verify($password, $registro['password_hash']) && $registro['password_hash'] !== $password, 'Guarda una contraseña protegida y conserva sus espacios');
    comprobar(!password_verify(trim($password), $registro['password_hash']), 'No recorta la contraseña');
    comprobar((int) $registro['verificado'] === 0, 'La cuenta queda pendiente de activación');
    $primerCodigo = $codigo($email);
    comprobar($registro['codigo_verificacion_hash'] !== $primerCodigo && password_verify($primerCodigo, $registro['codigo_verificacion_hash']), 'Protege el código de activación');
    $consulta = $db->query('SELECT TIMESTAMPDIFF(SECOND, UTC_TIMESTAMP(), codigo_verificacion_vence) FROM clientes');
    comprobar((int) $consulta->fetchColumn() > 590, 'El código tiene una vigencia de diez minutos');
    rechaza(fn () => $registroClientes->registrar('Otro nombre', strtoupper($email), 'OtraClave16!', 'OtraClave16!'), 'El correo es único sin distinguir mayúsculas');
    comprobar($buscar($email)['password_hash'] === $registro['password_hash'], 'Un registro repetido no reemplaza la contraseña');
    comprobar((int) $db->query('SELECT COUNT(*) FROM correos_locales')->fetchColumn() === 1, 'Un duplicado no genera otro mensaje');
    rechaza(fn () => $registroClientes->reenviar($email), 'Respeta el minuto de espera entre códigos');
    rechaza(fn () => $clientes->verificar($email, '12345'), 'Exige un código de seis dígitos');
    for ($intento = 1; $intento <= 5; $intento++) {
        rechaza(fn () => $clientes->verificar($email, '000000'), 'Rechaza un código incorrecto');
        comprobar((int) $buscar($email)['intentos_verificacion'] === $intento, 'Conserva los intentos fallidos');
    }
    comprobar($buscar($email)['codigo_verificacion_hash'] === null, 'Invalida el código al quinto error');
    rechaza(fn () => $clientes->verificar($email, $primerCodigo), 'Un código bloqueado no activa la cuenta');
    $db->exec('UPDATE clientes SET ultimo_codigo_en = DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 MINUTE)');
    $registroClientes->reenviar($email);
    $segundoCodigo = $codigo($email);
    comprobar((int) $buscar($email)['intentos_verificacion'] === 0, 'El reenvío reinicia los intentos');
    comprobar((int) $db->query('SELECT COUNT(*) FROM correos_locales')->fetchColumn() === 2, 'Registra el nuevo mensaje');
    $db->exec('UPDATE clientes SET codigo_verificacion_vence = DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 SECOND)');
    rechaza(fn () => $clientes->verificar($email, $segundoCodigo), 'Rechaza códigos vencidos');
    $db->exec('UPDATE clientes SET ultimo_codigo_en = DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 MINUTE)');
    $registroClientes->reenviar($email);
    $clientes->verificar($email, $codigo($email));
    $verificado = $buscar($email);
    comprobar((int) $verificado['verificado'] === 1 && $verificado['codigo_verificacion_hash'] === null, 'Activa la cuenta y consume el código');
    rechaza(fn () => $clientes->verificar($email, $segundoCodigo), 'No reutiliza un código en una cuenta activada');
    rechaza(fn () => $clientes->verificar('nadie@example.test', '123456'), 'Rechaza una cuenta inexistente');
    $mensajes = (int) $db->query('SELECT COUNT(*) FROM correos_locales')->fetchColumn();
    $registroClientes->reenviar($email);
    $registroClientes->reenviar('nadie@example.test');
    comprobar((int) $db->query('SELECT COUNT(*) FROM correos_locales')->fetchColumn() === $mensajes, 'No reenvía a cuentas activas o inexistentes');

    $db->exec("CREATE TRIGGER fallo_correo BEFORE INSERT ON correos_locales FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fallo de prueba'");
    $fallido = $registroClientes->registrar('Cuenta fallida', 'fallo@example.test', $password, $password);
    comprobar(!$fallido['correo_enviado'] && (int) $buscar('fallo@example.test')['verificado'] === 0,
        'Un fallo de entrega conserva la cuenta pendiente e informa el resultado');
    comprobar(!isset($fallido['codigo']), 'No devuelve el código desde el servicio de registro');
    $db->exec('DROP TRIGGER fallo_correo');
    $db->exec("UPDATE clientes SET ultimo_codigo_en = DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 MINUTE) WHERE email = 'fallo@example.test'");
    comprobar($registroClientes->reenviar('fallo@example.test'), 'Puede reenviar después de recuperar la bandeja local');
    $clientes->verificar('fallo@example.test', $codigo('fallo@example.test'));

    $entorno->iniciarServidor();
    $solicitar = $entorno->solicitar(...);
    [$estado, $html] = $solicitar('/clientes/registro');
    comprobar($estado === 200 && str_contains($html, 'Crear cuenta de cliente'), 'Muestra el formulario de registro');
    comprobar(str_contains($html, 'componentes.css') && str_contains($html, 'registro.css'), 'Carga los componentes compartidos y el estilo de registro');
    $csrf = oculto($html, 'csrf');
    $emailWeb = 'registro.web@example.test';
    $campos = ['csrf' => $csrf, 'nombre' => 'Cliente web', 'email' => $emailWeb,
        'password' => $password, 'confirm_password' => $password];
    comprobar($solicitar('/clientes/registro-completado')[0] === 303, 'Protege la confirmación sin un registro completado');
    comprobar($solicitar('/clientes/login')[0] === 200, 'El registro permite continuar hacia el inicio de sesión');
    comprobar($solicitar('/clientes/registro', array_replace($campos, ['csrf' => 'incorrecto']))[0] === 422, 'Exige CSRF en el registro');
    comprobar($solicitar('/clientes/registro', array_replace($campos, ['nombre' => ['x']]))[0] === 422, 'Rechaza nombres malformados');
    comprobar($solicitar('/clientes/registro', array_replace($campos, ['password' => ['x']]))[0] === 422, 'Rechaza contraseñas malformadas');
    [, $htmlError] = $solicitar('/clientes/registro', array_replace($campos,
        ['nombre' => '<script>prueba</script>', 'confirm_password' => 'OtraClave16!']));
    comprobar(str_contains($htmlError, '&lt;script&gt;prueba&lt;/script&gt;') && !str_contains($htmlError, '<script>prueba</script>'), 'Escapa los datos recuperados tras un error');
    comprobar(!str_contains($htmlError, $password), 'No devuelve contraseñas en el HTML');
    comprobar($buscar($emailWeb) === false, 'Los formularios rechazados no crean cuentas');
    $solicitar('/carrito', ['csrf' => $csrf, 'accion' => 'agregar', 'id_producto' => '1', 'cantidad' => '2']);
    [$estado, $respuesta] = $solicitar('/clientes/registro', $campos);
    comprobar($estado === 303 && str_contains($respuesta, '/clientes/verificar'), 'Registra y redirige a la verificación');
    comprobar((int) $buscar($emailWeb)['verificado'] === 0, 'El formulario no activa la cuenta automáticamente');
    comprobar($solicitar('/clientes/registro', $campos)[0] === 422, 'Rechaza el doble envío sin crear otra cuenta');
    [, $html] = $solicitar('/clientes/verificar');
    comprobar(str_contains($html, $emailWeb), 'Recuerda el correo pendiente en la sesión');
    comprobar(!str_contains($html, $codigo($emailWeb)), 'No muestra el código en la página de verificación');
    [$salida, $correo] = $entorno->cli('database/ver-correo.php', [$emailWeb]);
    comprobar($salida === 0 && str_contains($correo, $codigo($emailWeb)), 'Permite consultar la bandeja de desarrollo por CLI');
    comprobar($entorno->cli('database/ver-correo.php', ['invalido'])[0] === 1, 'Valida el correo del comando local');
    comprobar($solicitar('/clientes/verificar', ['csrf' => 'incorrecto', 'email' => $emailWeb, 'accion' => 'verificar', 'codigo' => $codigo($emailWeb)])[0] === 422, 'Exige CSRF en la activación');
    $verificar = ['csrf' => $csrf, 'email' => $emailWeb, 'accion' => 'verificar', 'codigo' => $codigo($emailWeb)];
    comprobar($solicitar('/clientes/verificar', array_replace($verificar, ['codigo' => ['123456']]))[0] === 422, 'Rechaza códigos malformados');
    comprobar($solicitar('/clientes/verificar', array_replace($verificar, ['accion' => 'desconocida']))[0] === 422, 'Valida la acción solicitada');
    comprobar($solicitar('/clientes/verificar', array_replace($verificar, ['codigo' => '000000']))[0] === 422, 'Informa un código incorrecto por HTTP');
    comprobar((int) $buscar($emailWeb)['intentos_verificacion'] === 1, 'Conserva el intento fallido del formulario');
    $reenvio = ['csrf' => $csrf, 'email' => $emailWeb, 'accion' => 'reenviar'];
    comprobar($solicitar('/clientes/verificar', $reenvio)[0] === 422, 'Limita el reenvío desde el formulario');
    $db->exec("UPDATE clientes SET ultimo_codigo_en = DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 MINUTE) WHERE email = '$emailWeb'");
    comprobar($solicitar('/clientes/verificar', $reenvio)[0] === 303, 'Redirige después de reenviar');
    $mensajes = (int) $db->query('SELECT COUNT(*) FROM correos_locales')->fetchColumn();
    $solicitar('/clientes/verificar');
    $solicitar('/clientes/verificar');
    comprobar((int) $db->query('SELECT COUNT(*) FROM correos_locales')->fetchColumn() === $mensajes, 'Recargar la página no reenvía mensajes');
    $verificar['codigo'] = $codigo($emailWeb);
    comprobar($solicitar('/clientes/verificar', $verificar)[0] === 303, 'Activa la cuenta y redirige');
    comprobar((int) $buscar($emailWeb)['verificado'] === 1, 'Persiste la activación en MySQL');
    [$estado, $html] = $solicitar('/clientes/registro-completado');
    comprobar($estado === 200 && str_contains($html, '¡Tu cuenta está lista!'), 'Muestra la confirmación del registro');
    comprobar($solicitar('/clientes/registro-completado', [])[0] === 405, 'La confirmación admite solo lectura');
    [, $html] = $solicitar('/carrito');
    comprobar(str_contains($html, 'Carrito <span class="carrito__cantidad">2</span>'), 'El registro y la activación conservan el carrito');
    comprobar(!preg_match('/Fatal error:|Warning:|Notice:/', $entorno->errores()), 'El flujo no emite errores PHP');
    echo "Correcto: $comprobaciones comprobaciones de HU16.\n";
} catch (Throwable $error) {
    $correcto = false;
    fwrite(STDERR, $error->getMessage() . "\n" . $error->getTraceAsString() . "\n");
    fwrite(STDERR, $entorno?->errores() ?? '');
} finally {
    $entorno?->cerrar();
}
exit($correcto ? 0 : 1);
