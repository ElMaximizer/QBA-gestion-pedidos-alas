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
use App\Services\ConfiguracionCliente;

$entorno = null;
$comprobaciones = 0;
$correcto = true;
putenv('QBA_MAIL_TRANSPORT=local');
try {
    $entorno = new EntornoPruebas('hu17');
    $db = $entorno->db;
    $cuentas = new CuentaCliente($db);
    $clientes = new Cliente($db);
    $password = ' PasswordCodigoPrueba! ';
    $nueva = ' NuevaPasswordPrueba! ';
    $cliente = $clientes->registrar('Prueba código', 'codigo@example.test', $password, $password);
    $clientes->verificar($cliente['email'], $cliente['codigo']);
    $id = $cliente['id_cliente'];
    $acceso = new AccesoCliente($db);
    $version = $acceso->buscarVerificado($id)['version_acceso'];
    $otra = $clientes->registrar('Otra cuenta', 'otra@example.test', $password, $password);
    $clientes->verificar($otra['email'], $otra['codigo']);
    $otroId = $otra['id_cliente'];
    $otraVersion = $acceso->buscarVerificado($otroId)['version_acceso'];
    rechaza(fn() => $cuentas->verificarPassword($id, $version, ''), 'No cambia sin código');
    rechaza(fn() => $cuentas->solicitarCodigoPassword($id, $version, 'Incorrecta', $nueva, $nueva), 'Pedir código exige contraseña actual');
    $codigo = $cuentas->solicitarCodigoPassword($id, $version, $password, $nueva, $nueva)['codigo'];
    rechaza(fn() => $cuentas->cancelarPassword($otroId, $version), 'Una versión ajena no cancela cambios');
    $cuentas->cancelarPassword($id, $version);
    comprobar(!$cuentas->passwordPendiente($id, $version), 'Cancelar retira la confirmación pendiente');
    rechaza(fn() => $cuentas->verificarPassword($id, $version, $codigo), 'El código cancelado no confirma');
    rechaza(fn() => $cuentas->solicitarCodigoPassword($id, $version, $password, $nueva, $nueva), 'Cancelar conserva el enfriamiento');
    $db->exec('UPDATE codigos_password_cliente SET enviado_en = DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 MINUTE) WHERE id_cliente = ' . $id);
    $codigo = $cuentas->solicitarCodigoPassword($id, $version, $password, $nueva, $nueva)['codigo'];
    $fila = $db->query('SELECT * FROM codigos_password_cliente WHERE id_cliente = ' . $id)->fetch();
    comprobar($fila['codigo_hash'] !== $codigo && password_verify($codigo, $fila['codigo_hash']), 'Guarda solo hash del código');
    comprobar($acceso->autenticar($cliente['email'], $password) !== null, 'La contraseña anterior sigue vigente hasta confirmar');
    rechaza(fn() => $cuentas->solicitarCodigoPassword($id, $version, $password, $nueva, $nueva), 'Controla envíos repetidos');
    rechaza(fn() => $cuentas->verificarPassword($otroId, $otraVersion, $codigo), 'No acepta un código de otra cuenta');
    $incorrecto = $codigo === '100000' ? '100001' : '100000';
    for ($i = 0; $i < 5; $i++) {
        rechaza(fn() => $cuentas->verificarPassword($id, $version, $incorrecto), 'Conserva intentos incorrectos');
    }
    rechaza(fn() => $cuentas->verificarPassword($id, $version, $codigo), 'Cinco fallos bloquean incluso el código correcto');
    $enfriar = static function () use ($db, $id): void {
        $db->exec('UPDATE codigos_password_cliente SET enviado_en = DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 MINUTE) WHERE id_cliente = ' . $id);
    };
    $enfriar();
    $codigo = $cuentas->solicitarCodigoPassword($id, $version, $password, $nueva, $nueva)['codigo'];
    $db->exec('UPDATE codigos_password_cliente SET vence_en = DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 SECOND) WHERE id_cliente = ' . $id);
    rechaza(fn() => $cuentas->verificarPassword($id, $version, $codigo), 'Rechaza código vencido');
    $enfriar();
    $codigo = $cuentas->solicitarCodigoPassword($id, $version, $password, $nueva, $nueva)['codigo'];
    $correo = $cuentas->solicitarCorreo($id, $version, $password, 'nuevo@example.test');
    $version = $cuentas->verificarCorreo($id, $version, $correo['codigo']);
    rechaza(fn() => $cuentas->verificarPassword($id, $version, $codigo), 'Cambiar correo invalida el código anterior');
    $enfriar();
    $db->exec("CREATE TRIGGER fallo_codigo_password BEFORE INSERT ON correos_locales FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fallo ficticio'");
    comprobar(!(new ConfiguracionCliente($db))->solicitarCodigoPassword($id, $version, $password, $nueva, $nueva), 'Informa fallo de envío');
    $db->exec('DROP TRIGGER fallo_codigo_password');
    comprobar($acceso->autenticar('nuevo@example.test', $password) !== null, 'Fallo de correo no cambia la contraseña');
    $enfriar();
    comprobar((new ConfiguracionCliente($db))->solicitarCodigoPassword($id, $version, $password, $nueva, $nueva), 'Entrega el código con transporte local');
    $mail = $db->query('SELECT * FROM correos_locales ORDER BY id_correo DESC LIMIT 1')->fetch();
    comprobar($mail['destinatario'] === 'nuevo@example.test' && preg_match('/es: ([0-9]{6})/', $mail['cuerpo'], $coincidencia), 'Solo se envía al correo vigente');
    $codigo = $coincidencia[1];
    $db->exec(file_get_contents(dirname(__DIR__) . '/database/codigo-password-clientes.sql'));
    comprobar($cuentas->passwordPendiente($id, $version), 'Muestra la confirmación pendiente');
    comprobar($db->query('SELECT password_pendiente_hash FROM codigos_password_cliente WHERE id_cliente = ' . $id)->fetchColumn() !== $nueva, 'No guarda contraseña en claro');
    $nuevaVersion = $cuentas->verificarPassword($id, $version, $codigo);
    comprobar($nuevaVersion !== $version && $acceso->autenticar('nuevo@example.test', $nueva) !== null, 'El código confirma y renueva la versión de sesión');
    rechaza(fn() => $cuentas->verificarPassword($id, $nuevaVersion, $codigo), 'Código consumido no se reutiliza');
    rechaza(fn() => $cuentas->solicitarCodigoPassword($id, $version, $password, $nueva, $nueva), 'Sesión revocada no solicita códigos');
    comprobar(!preg_match('/Fatal error:|Warning:|Notice:/', $entorno->errores()), 'Sin errores PHP');
    echo "Correcto: código de contraseña, caducidad, límites, propiedad, envío y revocación.\n";
} catch (Throwable $error) {
    $correcto = false;
    fwrite(STDERR, $error->getMessage() . "\n" . $error->getTraceAsString() . "\n");
} finally {
    $entorno?->cerrar();
}
exit($correcto ? 0 : 1);
