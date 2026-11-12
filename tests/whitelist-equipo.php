<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/soporte.php';

use App\Models\WhitelistEquipo;
use App\Services\RegistroEquipo;
use App\Services\Correo;

$entorno = null;
$comprobaciones = 0;
$correcto = true;
$archivo = tempnam(sys_get_temp_dir(), 'qba-whitelist-test-');
$anterior = getenv('QBA_EQUIPO_WHITELIST');
try {
    file_put_contents($archivo, '{"cedulas":[]}');
    putenv('QBA_EQUIPO_WHITELIST=' . $archivo);
    $lista = new WhitelistEquipo();
    $entorno = new EntornoPruebas('equipo', ['QBA_EQUIPO_WHITELIST' => $archivo]);
    $db = $entorno->db;
    $registro = new RegistroEquipo($db, new Correo($db, ['transport' => 'local']));
    $password = 'WhitelistPrueba!';
    comprobar($lista->listar() === [], 'La lista vacía no habilita a nadie');
    rechaza(fn() => $registro->solicitar('Solicitante', '12345678', 'no@example.test', $password, $password), 'No registra cédulas no habilitadas');
    comprobar((int) $db->query('SELECT COUNT(*) FROM usuarios_equipo')->fetchColumn() === 0 &&
        (int) $db->query('SELECT COUNT(*) FROM correos_locales')->fetchColumn() === 0, 'El rechazo no crea usuarios ni envía correos');
    $lista->modificar('01234567', true);
    comprobar($lista->listar() === ['1234567'], 'Normaliza con la misma identidad numérica del equipo');
    rechaza(fn() => $lista->modificar('1234567', true), 'No duplica cédulas con ceros iniciales');
    foreach (['abc12345', '12345', '1.234.567', '../1234567'] as $invalido) {
        rechaza(fn() => $lista->modificar($invalido, true), 'Valida las cédulas de la whitelist');
    }
    $usuario = $registro->solicitar('Solicitante', '01234567', 'permitida@example.test', $password, $password);
    $lista->modificar('1234567', false);
    comprobar((int) $db->query('SELECT COUNT(*) FROM usuarios_equipo')->fetchColumn() === 1, 'Quitar una cédula conserva su solicitud existente');
    rechaza(fn() => $registro->solicitar('Solicitante', '1234567', 'otra@example.test', $password, $password), 'Una cédula retirada no vuelve a solicitar');
    foreach (['{', '{"cedulas":[1234567]}', '{"cedulas":["<script>"]}'] as $corrupto) {
        file_put_contents($archivo, $corrupto);
        $rechazado = false;
        try {
            $registro->solicitar('Solicitante', '7654321', 'corrupta@example.test', $password, $password);
        } catch (Throwable) {
            $rechazado = true;
        }
        comprobar($rechazado && (int) $db->query('SELECT COUNT(*) FROM usuarios_equipo')->fetchColumn() === 1, 'JSON inválido no permite solicitudes');
    }
    unlink($archivo);
    comprobar($lista->listar() === [], 'Archivo ausente equivale a lista vacía');
    $lista->modificar('8765432', true);
    $crear = $db->prepare('INSERT INTO usuarios_equipo(nombre,cedula,email,password_hash,estado,rol) VALUES(?,?,?,?,?,?)');
    foreach (['empleado', 'administrador', 'dueno'] as $indice => $rol) {
        $crear->execute([$rol, '900000' . $indice, $rol . '@example.test', password_hash($password, PASSWORD_DEFAULT), 'aprobado', $rol]);
        $ids[$rol] = (int) $db->lastInsertId();
    }
    $entorno->iniciarServidor();
    $solicitar = $entorno->solicitar(...);
    comprobar($solicitar('/equipo/solicitudes')[0] === 303, 'La lista exige sesión de equipo');
    $csrf = oculto($solicitar('/equipo/login')[1], 'csrf');
    comprobar($solicitar('/equipo/login', ['csrf' => $csrf, 'cedula' => '9000000', 'password' => $password])[0] === 303, 'Inicia empleado de prueba');
    $csrf = oculto($solicitar('/equipo/panel')[1], 'csrf');
    comprobar($solicitar('/equipo/solicitudes')[0] === 403 &&
        $solicitar('/equipo/solicitudes', ['csrf' => $csrf, 'accion' => 'habilitar-cedula', 'cedula' => '7654321', 'rol' => 'dueno'])[0] === 403,
        'Un empleado no ve ni modifica la lista aunque falsifique un rol');
    $entorno->sesion(static fn(array $s): array => array_replace($s, ['equipo_id' => $ids['administrador'], 'equipo_ultimo_acceso' => time()]));
    [, $html] = $solicitar('/equipo/solicitudes');
    $csrf = oculto($html, 'csrf');
    comprobar(str_contains($html, '8765432') && !str_contains($html, 'name="id_usuario"'), 'El administrador ve whitelist sin datos de solicitudes pendientes');
    $agregar = ['csrf' => $csrf, 'accion' => 'habilitar-cedula', 'cedula' => '7654321'];
    comprobar($solicitar('/equipo/solicitudes', array_replace($agregar, ['csrf' => 'invalido']))[0] === 422 && $lista->listar() === ['8765432'], 'CSRF inválido no modifica JSON');
    comprobar($solicitar('/equipo/solicitudes', array_replace($agregar, ['cedula' => ['x']]))[0] === 422, 'Rechaza campos HTTP malformados');
    comprobar($solicitar('/equipo/solicitudes', $agregar)[0] === 303 && in_array('7654321', $lista->listar(), true), 'Administrador habilita cédula');
    comprobar($solicitar('/equipo/solicitudes', ['csrf' => $csrf, 'accion' => 'aprobar', 'id_usuario' => $usuario['id_usuario']])[0] === 403, 'Administrador no obtiene permiso de aprobación');
    $entorno->sesion(static fn(array $s): array => array_replace($s, ['equipo_id' => $ids['dueno'], 'equipo_ultimo_acceso' => time()]));
    [, $html] = $solicitar('/equipo/solicitudes');
    $csrf = oculto($html, 'csrf');
    comprobar(str_contains($html, 'name="id_usuario"'), 'Dueño mantiene la revisión de pendientes');
    comprobar($solicitar('/equipo/solicitudes', ['csrf' => $csrf, 'accion' => 'quitar-cedula', 'cedula' => '8765432'])[0] === 303 && $lista->listar() === ['7654321'], 'Dueño quita cédulas');
    // Vuelve al registro público sin sesión para validar la ruta completa.
    comprobar($solicitar('/equipo/salir', ['csrf' => $csrf])[0] === 303, 'Cierra la sesión antes del registro público');
    [, $html] = $solicitar('/equipo/registro');
    $csrf = oculto($html, 'csrf');
    $campos = ['csrf' => $csrf, 'nombre' => 'Solicitud web', 'email' => 'web@example.test', 'cedula' => '8765432', 'password' => $password, 'confirm_password' => $password];
    comprobar($solicitar('/equipo/registro', $campos)[0] === 422, 'La ruta pública rechaza la cédula retirada');
    $campos['cedula'] = '7654321';
    comprobar($solicitar('/equipo/registro', $campos)[0] === 303, 'La ruta pública admite la cédula habilitada');
    comprobar($solicitar('/storage/equipo/whitelist.local.json')[0] === 404, 'El servidor público no expone el JSON');
    $procesos = [];
    foreach (['2000001', '2000002'] as $cedulaConcurrente) {
        $codigoConcurrente = 'require ' . var_export(dirname(__DIR__) . '/app/bootstrap.php', true) . ';' .
            '(new App\\Models\\WhitelistEquipo(' . var_export($archivo, true) . '))->modificar(' . var_export($cedulaConcurrente, true) . ',true);';
        $proceso = proc_open([PHP_BINARY, '-r', $codigoConcurrente], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        fclose($pipes[0]);
        $procesos[] = [$proceso, $pipes];
    }
    foreach ($procesos as [$proceso, $pipes]) {
        stream_get_contents($pipes[1]);
        $errorProceso = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        comprobar(proc_close($proceso) === 0, 'La modificación concurrente finaliza correctamente: ' . $errorProceso);
    }
    comprobar(in_array('2000001', $lista->listar(), true) && in_array('2000002', $lista->listar(), true), 'Dos escrituras concurrentes conservan ambos cambios');
    comprobar(!preg_match('/Fatal error:|Warning:|Notice:/', $entorno->errores()), 'Sin errores PHP en el flujo');
    echo "Correcto: whitelist JSON, permisos, CSRF, normalización y solicitudes autorizadas.\n";
} catch (Throwable $error) {
    $correcto = false;
    fwrite(STDERR, $error->getMessage() . "\n" . $error->getTraceAsString() . "\n");
} finally {
    $entorno?->cerrar();
    @unlink($archivo);
    @unlink($archivo . '.lock');
    putenv($anterior === false ? 'QBA_EQUIPO_WHITELIST' : 'QBA_EQUIPO_WHITELIST=' . $anterior);
}
exit($correcto ? 0 : 1);
