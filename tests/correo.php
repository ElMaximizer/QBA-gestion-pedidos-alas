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

$entorno = null;
$servidor = null;
$directorio = sys_get_temp_dir() . '/qba-smtp-test-' . bin2hex(random_bytes(6));
$comprobaciones = 0;
$correcto = true;

try {
    mkdir($directorio);
    $servidor = proc_open([PHP_BINARY, __DIR__ . '/smtp-servidor.php', $directorio],
        [0 => ['pipe', 'r'], 1 => ['file', $directorio . '/salida.log', 'a'],
            2 => ['file', $directorio . '/errores.log', 'a']], $tuberias, null, null, ['bypass_shell' => true]);
    if (!is_resource($servidor)) {
        throw new RuntimeException('No se pudo iniciar el receptor SMTP de pruebas.');
    }
    fclose($tuberias[0]);
    $inicio = microtime(true);
    while (!is_file($directorio . '/direccion') && microtime(true) - $inicio < 5) {
        usleep(50000);
    }
    $direccion = file_get_contents($directorio . '/direccion');
    $puerto = (int) substr(strrchr($direccion, ':'), 1);
    $entorno = new EntornoPruebas('hu16', [
        'QBA_MAIL_TRANSPORT' => 'smtp', 'QBA_SMTP_HOST' => '127.0.0.1',
        'QBA_SMTP_PORT' => (string) $puerto, 'QBA_SMTP_ENCRYPTION' => 'none',
        'QBA_SMTP_USERNAME' => '', 'QBA_SMTP_PASSWORD' => '',
        'QBA_MAIL_FROM' => 'panaderia@example.test', 'QBA_MAIL_FROM_NAME' => 'Panadería Alas', 'QBA_SMTP_TIMEOUT' => '2',
    ]);
    $db = $entorno->db;
    $entorno->iniciarServidor();
    $solicitar = $entorno->solicitar(...);
    $buscar = static function (string $email) use ($db): array {
        $consulta = $db->prepare('SELECT * FROM clientes WHERE email = ?');
        $consulta->execute([$email]);
        return $consulta->fetch();
    };
    $leer = static function (int $numero) use ($directorio): array {
        if (!is_file($directorio . '/mensaje-' . $numero . '.eml')) {
            throw new RuntimeException('El receptor SMTP no recibió el mensaje esperado.');
        }
        $mensaje = file_get_contents($directorio . '/mensaje-' . $numero . '.eml');
        [$cabeceras, $cuerpo] = explode("\r\n\r\n", $mensaje, 2);
        $cuerpo = base64_decode($cuerpo, true);
        if ($cuerpo === false || !preg_match('/es: ([0-9]{6})/', $cuerpo, $codigo)) {
            throw new RuntimeException('El mensaje SMTP no contiene un código válido.');
        }
        return [$cabeceras, $cuerpo, $codigo[1]];
    };
    [, $html] = $solicitar('index.php?ruta=registro');
    $csrf = oculto($html, 'csrf');
    $campos = ['csrf' => $csrf, 'nombre' => 'Cliente Ñandú', 'email' => 'smtp@example.test',
        'password' => 'ClaveSegura16!', 'confirm_password' => 'ClaveSegura16!'];
    comprobar($solicitar('index.php?ruta=registro', $campos)[0] === 303, 'Registra mediante el transporte SMTP');
    [$cabeceras, $cuerpo, $codigoInicial] = $leer(1);
    comprobar(str_contains($cabeceras, 'smtp@example.test') && str_contains($cabeceras, 'panaderia@example.test'), 'Entrega al destinatario y con el remitente configurados');
    comprobar((bool) preg_match('/charset="?utf-8"?/i', $cabeceras) && str_contains($cuerpo, 'Hola Cliente Ñandú,'), 'Conserva acentos y codificación UTF-8');
    comprobar(password_verify($codigoInicial, $buscar('smtp@example.test')['codigo_verificacion_hash']), 'El código recibido coincide con el hash guardado');
    comprobar((int) $db->query('SELECT COUNT(*) FROM correos_locales')->fetchColumn() === 0, 'SMTP no guarda copias de los códigos en la bandeja local');
    [, $html] = $solicitar('index.php?ruta=verificar-cuenta');
    comprobar(!str_contains($html, $codigoInicial), 'El código enviado no aparece en la pantalla');
    comprobar($solicitar('index.php?ruta=registro', $campos)[0] === 422 && count(glob($directorio . '/mensaje-*.eml')) === 1, 'Un registro duplicado no envía otro correo');
    $reenvio = ['csrf' => $csrf, 'email' => 'smtp@example.test', 'accion' => 'reenviar'];
    comprobar($solicitar('index.php?ruta=verificar-cuenta', $reenvio)[0] === 422, 'SMTP respeta el tiempo entre reenvíos');
    $db->exec('UPDATE clientes SET ultimo_codigo_en = DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 MINUTE)');
    comprobar($solicitar('index.php?ruta=verificar-cuenta', $reenvio)[0] === 303, 'Envía el nuevo código mediante PHPMailer');
    [, , $codigoNuevo] = $leer(2);
    comprobar(password_verify($codigoNuevo, $buscar('smtp@example.test')['codigo_verificacion_hash']), 'Actualiza el código válido después de reenviar');
    $solicitar('index.php?ruta=verificar-cuenta');
    $solicitar('index.php?ruta=verificar-cuenta');
    comprobar(count(glob($directorio . '/mensaje-*.eml')) === 2, 'Recargar la página no repite el envío SMTP');
    comprobar($solicitar('index.php?ruta=verificar-cuenta', $reenvio + ['codigo' => $codigoNuevo])[0] === 422, 'Mantiene la espera aunque se adjunte un código');
    comprobar($solicitar('index.php?ruta=verificar-cuenta', array_replace($reenvio, ['accion' => 'verificar', 'codigo' => $codigoNuevo]))[0] === 303, 'Activa la cuenta con el código entregado por SMTP');
    comprobar((int) $buscar('smtp@example.test')['verificado'] === 1, 'Persiste la cuenta verificada');

    $campos['email'] = 'fallo@example.test';
    comprobar($solicitar('index.php?ruta=registro', $campos)[0] === 303, 'Un rechazo SMTP redirige a la cuenta pendiente');
    comprobar((int) $buscar('fallo@example.test')['verificado'] === 0, 'Un fallo de envío no borra ni activa la cuenta');
    [, $html] = $solicitar('index.php?ruta=verificar-cuenta');
    comprobar(str_contains($html, 'Creamos tu cuenta, pero no pudimos enviar el código'), 'Explica el fallo y cómo pedir otro código');
    comprobar(!str_contains($html, '550') && !str_contains($html, 'sin-datos-privados'), 'No expone el error interno SMTP');
    comprobar((int) $db->query('SELECT COUNT(*) FROM correos_locales')->fetchColumn() === 0, 'Un fallo SMTP no cambia silenciosamente a la bandeja local');
    $db->exec("UPDATE clientes SET ultimo_codigo_en = DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 MINUTE) WHERE email = 'fallo@example.test'");
    $reenvio['email'] = 'fallo@example.test';
    comprobar($solicitar('index.php?ruta=verificar-cuenta', $reenvio)[0] === 303, 'Un reenvío fallido también usa redirección');
    [, $html] = $solicitar('index.php?ruta=verificar-cuenta');
    comprobar(str_contains($html, 'No pudimos enviar el código. Tu cuenta sigue pendiente'), 'Informa el reenvío fallido sin afirmar una entrega');
    file_put_contents($directorio . '/recuperado', '1');
    $db->exec("UPDATE clientes SET ultimo_codigo_en = DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 MINUTE) WHERE email = 'fallo@example.test'");
    comprobar($solicitar('index.php?ruta=verificar-cuenta', $reenvio)[0] === 303, 'Recupera el envío cuando vuelve a funcionar SMTP');
    [, , $codigoRecuperado] = $leer(3);
    (new Cliente($db))->verificar('fallo@example.test', $codigoRecuperado);
    comprobar((int) $buscar('fallo@example.test')['verificado'] === 1, 'La cuenta que tuvo un fallo puede completar el registro');
    $reenvio['email'] = 'inexistente@example.test';
    comprobar($solicitar('index.php?ruta=verificar-cuenta', $reenvio)[0] === 303
        && count(glob($directorio . '/mensaje-*.eml')) === 3, 'No envía a cuentas inexistentes');

    $config = ['transport' => 'smtp', 'host' => 'smtp.gmail.com', 'port' => 587, 'encryption' => 'tls',
        'username' => '', 'password' => '', 'from_email' => 'panaderia@example.test', 'from_name' => 'Alas', 'timeout' => 1];
    foreach ([$config, array_replace($config, ['username' => 'usuario', 'password' => 'secreto', 'encryption' => '']),
        array_replace($config, ['host' => 'smtp.gmail.com;otro.host'])] as $invalida) {
        try {
            (new CorreoVerificacion($db, $invalida))->enviar('cliente@example.test', 'Cliente', '123456');
            throw new LogicException('Debió rechazar la configuración SMTP');
        } catch (RuntimeException $error) {
            comprobar($error->getMessage() === 'La configuración SMTP está incompleta o no es válida.', 'Rechaza una configuración incompleta o sin cifrado antes de conectarse');
        }
    }
    comprobar(!preg_match('/Fatal error:|Warning:|Notice:|sin-datos-privados/', $entorno->errores()), 'El flujo no expone errores de SMTP en el registro del servidor');
    echo "Correcto: $comprobaciones comprobaciones de correo SMTP.\n";
} catch (Throwable $error) {
    $correcto = false;
    fwrite(STDERR, $error->getMessage() . "\n" . $error->getTraceAsString() . "\n");
    fwrite(STDERR, $entorno?->errores() ?? '');
} finally {
    $entorno?->cerrar();
    if (is_resource($servidor)) {
        proc_terminate($servidor);
        proc_close($servidor);
    }
    foreach (glob($directorio . '/*') as $archivo) {
        unlink($archivo);
    }
    if (is_dir($directorio)) {
        rmdir($directorio);
    }
}
exit($correcto ? 0 : 1);
