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
    $entorno = new EntornoPruebas('hu16');
    $db = $entorno->db;
    $clientes = new Cliente($db);
    $cuentas = new CuentaCliente($db);
    $acceso = new AccesoCliente($db);
    $password = ' ClaveEliminarPrueba! ';
    $crear = static function (string $email) use ($clientes, $password): array {
        $c = $clientes->registrar('Cuenta de prueba', $email, $password, $password);
        $clientes->verificar($email, $c['codigo']);
        return $c;
    };
    $cliente = $crear('eliminar@example.test');
    $otro = $crear('otra@example.test');
    $id = $cliente['id_cliente'];
    $otroId = $otro['id_cliente'];
    $version = $acceso->buscarVerificado($id)['version_acceso'];
    $otraVersion = $acceso->buscarVerificado($otroId)['version_acceso'];
    $enfriar = static function () use ($db, $id): void {
        $db->exec('UPDATE codigos_eliminacion_cliente SET enviado_en = DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 MINUTE) WHERE id_cliente = ' . $id);
    };
    rechaza(fn() => $cuentas->eliminar($id, $version, ''), 'No permite eliminar sin código');
    rechaza(fn() => $cuentas->solicitarCodigoEliminacion($id, $version, 'Incorrecta'), 'Solicitar exige contraseña actual');
    $codigo = $cuentas->solicitarCodigoEliminacion($id, $version, $password)['codigo'];
    $fila = $db->query('SELECT * FROM codigos_eliminacion_cliente WHERE id_cliente = ' . $id)->fetch();
    comprobar($fila['codigo_hash'] !== $codigo && password_verify($codigo, $fila['codigo_hash']), 'El código solo se guarda como hash');
    comprobar($acceso->buscarVerificado($id) !== null, 'Pedir código no elimina ni desactiva');
    rechaza(fn() => $cuentas->eliminar($otroId, $otraVersion, $codigo), 'Un código ajeno no elimina');
    rechaza(fn() => $cuentas->solicitarCodigoEliminacion($id, $version, $password), 'Limita reenvíos durante un minuto');
    $incorrecto = $codigo === '100000' ? '100001' : '100000';
    for ($i = 0; $i < 5; $i++) {
        rechaza(fn() => $cuentas->eliminar($id, $version, $incorrecto), 'Conserva fallos del código');
    }
    comprobar((int) $db->query('SELECT intentos FROM codigos_eliminacion_cliente WHERE id_cliente = ' . $id)->fetchColumn() === 5, 'Cinco fallos persistentes');
    rechaza(fn() => $cuentas->eliminar($id, $version, $codigo), 'No admite código después de cinco fallos');
    $cuentas->cancelarEliminacion($id, $version);
    comprobar(!$cuentas->eliminacionPendiente($id, $version), 'Cancelar retira la solicitud');
    rechaza(fn() => $cuentas->eliminar($id, $version, $codigo), 'Cancelar invalida el código');
    rechaza(fn() => $cuentas->solicitarCodigoEliminacion($id, $version, $password), 'Cancelar conserva el enfriamiento');
    $enfriar();
    $codigo = $cuentas->solicitarCodigoEliminacion($id, $version, $password)['codigo'];
    $db->exec('UPDATE codigos_eliminacion_cliente SET vence_en = DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 SECOND) WHERE id_cliente = ' . $id);
    rechaza(fn() => $cuentas->eliminar($id, $version, $codigo), 'Código vencido no elimina');
    $enfriar();
    $codigo = $cuentas->solicitarCodigoEliminacion($id, $version, $password)['codigo'];
    $correo = $cuentas->solicitarCorreo($id, $version, $password, 'nuevo@example.test');
    $version = $cuentas->verificarCorreo($id, $version, $correo['codigo']);
    rechaza(fn() => $cuentas->eliminar($id, $version, $codigo), 'Cambiar correo invalida el código anterior');
    $enfriar();
    $db->exec("CREATE TRIGGER fallo_eliminar_correo BEFORE INSERT ON correos_locales FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fallo ficticio'");
    comprobar(!(new ConfiguracionCliente($db))->solicitarCodigoEliminacion($id, $version, $password), 'Informa fallo de entrega');
    $db->exec('DROP TRIGGER fallo_eliminar_correo');
    comprobar($acceso->buscarVerificado($id) !== null, 'Fallo de entrega conserva acceso');
    $enfriar();
    comprobar((new ConfiguracionCliente($db))->solicitarCodigoEliminacion($id, $version, $password), 'Entrega por bandeja local');
    $mail = $db->query('SELECT * FROM correos_locales ORDER BY id_correo DESC LIMIT 1')->fetch();
    comprobar($mail['destinatario'] === 'nuevo@example.test' && preg_match('/es: ([0-9]{6})/', $mail['cuerpo'], $m), 'Solo envía al correo vigente');
    $db->exec(file_get_contents(dirname(__DIR__) . '/database/codigo-eliminacion-clientes.sql'));
    $cuentas->eliminar($id, $version, $m[1]);
    comprobar($acceso->buscarVerificado($id) === null && $acceso->buscarVerificado($otroId) !== null, 'El código válido elimina únicamente su cuenta');
    rechaza(fn() => $cuentas->eliminar($id, $version, $m[1]), 'No reutiliza código consumido');
    echo "Correcto: eliminación con código, propiedad, vigencia, límites, cancelación y correo.\n";
} catch (Throwable $error) {
    $correcto = false;
    fwrite(STDERR, $error->getMessage() . "\n" . $error->getTraceAsString() . "\n");
} finally {
    $entorno?->cerrar();
}
exit($correcto ? 0 : 1);
