<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/soporte.php';

use App\Models\AccesoEquipo;
use App\Models\ConsultaContacto;
use App\Models\PersonalEquipo;
use App\Models\PermisosEquipo;
use App\Models\SolicitudesEquipo;

$entorno = null;
$comprobaciones = 0;
$correcto = true;
try {
    $entorno = new EntornoPruebas('equipo');
    $db = $entorno->db;
    $consultas = new ConsultaContacto($db);
    $solicitudes = new SolicitudesEquipo($db);
    comprobar($consultas->listar()['consultas'] === [] && $solicitudes->listar()['solicitudes'] === [], 'Ambas bandejas admiten el estado vacío');
    $crearUsuario = $db->prepare('INSERT INTO usuarios_equipo (nombre,cedula,email,password_hash,estado,rol) VALUES (?,?,?,?,?,?)');
    $hash = password_hash('EquipoAtencion28!', PASSWORD_DEFAULT);
    $usuarios = [];
    foreach (['empleado', 'administrador', 'dueno'] as $indice => $rol) {
        $crearUsuario->execute(['Equipo ' . $rol, 12345670 + $indice, $rol . '@example.test', $hash, 'aprobado', $rol]);
        $usuarios[$rol] = (int) $db->lastInsertId();
    }
    for ($i = 1; $i <= 13; $i++) {
        $crearUsuario->execute([$i === 13 ? '<script>Solicitante</script>' : 'Solicitante ' . $i,
            20000000 + $i, 'solicitud' . $i . '@example.test', $hash, 'pendiente', 'empleado']);
        $pendientes[$i] = (int) $db->lastInsertId();
    }
    $insertar = $db->prepare('INSERT INTO mensajes_contacto (nombre,email,motivo,mensaje,leido,creado_en) VALUES (?,?,?,?,?,?)');
    for ($i = 1; $i <= 13; $i++) {
        $insertar->execute([$i === 13 ? '<script>nombre</script>' : 'Cliente ' . $i, 'cliente' . $i . '@example.test',
            'Otra consulta', $i === 13 ? "<img src=x onerror=alert(1)>\nSegunda línea" : 'Mensaje ' . $i, $i % 4 === 0 ? 1 : 0, '2026-01-01 12:00:00']);
        $ultimoMensaje = (int) $db->lastInsertId();
    }
    $primera = $consultas->listar();
    $segunda = $consultas->listar('', 999);
    comprobar($primera['resumen'] === ['total' => 13, 'nuevas' => 10, 'leidas' => 3] && $consultas->nuevas() === 10, 'Cuenta nuevas y leídas');
    comprobar(count($primera['consultas']) === 10 && (int) $primera['consultas'][0]['id_mensaje'] === $ultimoMensaje
        && $segunda['pagina'] === 2 && count($segunda['consultas']) === 3
        && array_intersect(array_column($primera['consultas'], 'id_mensaje'), array_column($segunda['consultas'], 'id_mensaje')) === [],
        'Pagina consultas recientes sin repetirlas y ajusta páginas fuera de rango');
    comprobar($consultas->listar('nuevas')['total'] === 10 && $consultas->listar('leidas')['total'] === 3, 'Filtra por estado de lectura');
    comprobar($solicitudes->pendientes() === 13 && count($solicitudes->listar()['solicitudes']) === 10
        && count($solicitudes->listar(999)['solicitudes']) === 3, 'Pagina únicamente las solicitudes pendientes');
    foreach ($solicitudes->listar()['solicitudes'] as $solicitud) {
        comprobar(!isset($solicitud['password_hash']), 'La bandeja no recupera contraseñas');
    }
    rechaza(fn () => $consultas->listar('otro'), 'Rechaza filtros desconocidos');
    rechaza(fn () => $solicitudes->listar(0), 'Rechaza páginas inválidas');
    rechaza(fn () => $consultas->marcarLeida(999999), 'No marca consultas inexistentes');
    foreach ([$usuarios['empleado'], $usuarios['administrador'], 999999] as $actor) {
        rechaza(fn () => $solicitudes->resolver($pendientes[1], $actor, 'aprobar'), 'El modelo exige un dueño real y aprobado');
    }
    comprobar($solicitudes->pendientes() === 13 && !$db->inTransaction(), 'Los rechazos de permisos revierten la transacción');

    $entorno->iniciarServidor();
    $solicitar = $entorno->solicitar(...);
    foreach (['consultas', 'solicitudes'] as $ruta) {
        comprobar($solicitar('/equipo/' . $ruta)[0] === 303 && $solicitar('/equipo/' . $ruta, [])[0] === 303, 'Exige sesión para leer o modificar ' . $ruta);
    }
    $identificar = static function (int $id) use ($entorno): void {
        $entorno->sesion(static fn (array $sesion): array => array_replace($sesion,
            ['equipo_id' => $id, 'equipo_ultimo_acceso' => time()]));
    };
    foreach (['empleado', 'administrador'] as $rol) {
        $identificar($usuarios[$rol]);
        comprobar($solicitar('/equipo/consultas')[0] === 200, 'El ' . $rol . ' puede consultar mensajes');
        comprobar($solicitar('/equipo/solicitudes')[0] === 403
            && $solicitar('/equipo/solicitudes', ['id_usuario' => $pendientes[1], 'accion' => 'aprobar'])[0] === 403,
            'El ' . $rol . ' no puede leer ni resolver solicitudes');
        [, $panel] = $solicitar('/equipo/panel');
        comprobar(!str_contains($panel, 'href="/equipo/solicitudes"'), 'Las solicitudes no se ofrecen a ' . $rol);
    }
    $identificar($usuarios['dueno']);
    [, $panel] = $solicitar('/equipo/panel');
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $panel);
    $xpath = new DOMXPath($dom);
    comprobar($xpath->query('//nav[contains(@class,"navegacion-equipo")]//a[@href="/equipo/consultas" or @href="/equipo/solicitudes"]')->length === 0
        && $xpath->query('//section[@class="panel-atencion"]//a[@href="/equipo/consultas" or @href="/equipo/solicitudes"]')->length === 2,
        'Ambos accesos están solamente abajo, en Atención del equipo');
    comprobar(str_contains($panel, 'Consultas recibidas (10 nuevas)') && str_contains($panel, 'Solicitudes de acceso (13)'), 'El panel muestra ambos contadores reales');

    [$estado, $html] = $solicitar('/equipo/consultas');
    comprobar($estado === 200 && str_contains($html, 'Cache-Control: no-store') && str_contains($html, 'bandeja-equipo.css'), 'La bandeja usa el diseño interno sin caché');
    comprobar(str_contains($html, '&lt;script&gt;nombre&lt;/script&gt;') && str_contains($html, '&lt;img src=x onerror=alert(1)&gt;')
        && !str_contains($html, '<img src=x') && str_contains($html, 'Segunda línea'), 'Muestra mensajes completos sin ejecutar HTML');
    $camposConsulta = ['csrf' => oculto($html, 'csrf'), 'id_mensaje' => $ultimoMensaje, 'accion' => 'marcar-leida'];
    comprobar($solicitar('/equipo/consultas', array_replace($camposConsulta, ['csrf' => 'incorrecto']))[0] === 422
        && $consultas->nuevas() === 10, 'Un CSRF inválido no modifica consultas');
    comprobar($solicitar('/equipo/consultas?accion=marcar-leida&id_mensaje=' . $ultimoMensaje)[0] === 200
        && $consultas->nuevas() === 10, 'Leer con GET no modifica el estado');
    comprobar($solicitar('/equipo/consultas?estado=nuevas', $camposConsulta)[0] === 303
        && $consultas->nuevas() === 9, 'Marca la consulta y conserva el filtro en la redirección');
    comprobar($solicitar('/equipo/consultas', $camposConsulta)[0] === 303 && $consultas->nuevas() === 9, 'Repetir la marca es idempotente');
    [, $nuevas] = $solicitar('/equipo/consultas?estado=nuevas');
    [, $leidas] = $solicitar('/equipo/consultas?estado=leidas');
    comprobar(!str_contains($nuevas, 'id="consulta-' . $ultimoMensaje . '"') && str_contains($leidas, 'id="consulta-' . $ultimoMensaje . '"'), 'La consulta cambia de filtro sin borrarse');
    foreach (['estado=invalido', 'estado%5B%5D=nuevas', 'pagina=0', 'pagina%5B%5D=1'] as $invalido) {
        comprobar($solicitar('/equipo/consultas?' . $invalido)[0] === 422, 'Rechaza filtros HTTP malformados');
    }

    [$estado, $html] = $solicitar('/equipo/solicitudes');
    comprobar($estado === 200 && str_contains($html, 'Cache-Control: no-store') && str_contains($html, '&lt;script&gt;Solicitante&lt;/script&gt;')
        && !str_contains($html, $hash), 'El dueño ve solicitudes escapadas sin credenciales');
    $camposSolicitud = ['csrf' => oculto($html, 'csrf'), 'id_usuario' => $pendientes[13], 'accion' => 'aprobar'];
    $antes = $db->query('SELECT nombre,cedula,email,password_hash,rol FROM usuarios_equipo WHERE id_usuario = ' . $pendientes[13])->fetch();
    comprobar($solicitar('/equipo/solicitudes', array_replace($camposSolicitud, ['csrf' => 'incorrecto']))[0] === 422
        && $solicitudes->pendientes() === 13, 'Una aprobación exige CSRF');
    comprobar($solicitar('/equipo/solicitudes?accion=aprobar&id_usuario=' . $pendientes[13])[0] === 200
        && $solicitudes->pendientes() === 13, 'GET no aprueba solicitudes');
    foreach ([['accion' => 'eliminar'], ['id_usuario' => '0'], ['id_usuario' => ['1']], ['id_usuario' => 999999], ['id_usuario' => $usuarios['dueno']]] as $invalido) {
        comprobar($solicitar('/equipo/solicitudes', array_replace($camposSolicitud, $invalido))[0] === 422, 'Rechaza decisiones o cuentas inválidas');
    }
    [$estado, $respuesta] = $solicitar('/equipo/solicitudes?pagina=2', $camposSolicitud + ['rol' => 'dueno', 'estado' => 'rechazado', 'id_dueno' => $usuarios['empleado']]);
    comprobar($estado === 303 && str_contains($respuesta, 'Location: /equipo/solicitudes?pagina=2') && $solicitudes->pendientes() === 12,
        'Aprueba usando la identidad de sesión e ignora privilegios enviados por el navegador');
    comprobar($db->query('SELECT nombre,cedula,email,password_hash,rol FROM usuarios_equipo WHERE id_usuario = ' . $pendientes[13])->fetch() === $antes
        && (new AccesoEquipo($db))->buscarAprobado($pendientes[13])['rol'] === 'empleado', 'La aprobación habilita empleado y conserva sus datos');
    comprobar(in_array($pendientes[13], array_map('intval', array_column((new PermisosEquipo($db))->listar(), 'id_usuario')), true), 'La cuenta aprobada pasa a la gestión existente de roles');
    comprobar($solicitar('/equipo/solicitudes', $camposSolicitud)[0] === 422
        && $solicitar('/equipo/solicitudes', array_replace($camposSolicitud, ['accion' => 'rechazar']))[0] === 422, 'No vuelve a resolver ni revierte una solicitud ya aprobada');
    $rechazar = array_replace($camposSolicitud, ['id_usuario' => $pendientes[12], 'accion' => 'rechazar']);
    comprobar($solicitar('/equipo/solicitudes', $rechazar)[0] === 303 && $solicitudes->pendientes() === 11
        && (new AccesoEquipo($db))->buscarAprobado($pendientes[12]) === null, 'Rechazar conserva la cuenta sin conceder acceso');
    rechaza(fn () => $solicitudes->resolver($pendientes[12], $usuarios['dueno'], 'aprobar'), 'Un formulario viejo no cambia una decisión de rechazo');
    $db->exec("CREATE TRIGGER fallo_solicitud BEFORE UPDATE ON usuarios_equipo FOR EACH ROW
        BEGIN IF NEW.id_usuario = {$pendientes[11]} AND NEW.estado = 'aprobado' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fallo temporal de prueba'; END IF; END");
    try {
        $solicitudes->resolver($pendientes[11], $usuarios['dueno'], 'aprobar');
        throw new RuntimeException('La aprobación debía fallar.');
    } catch (PDOException) {
        comprobar(!$db->inTransaction() && $solicitudes->pendientes() === 11, 'Un fallo al guardar revierte la operación');
    }
    $db->exec('DROP TRIGGER fallo_solicitud');

    $entorno->sesion(static fn (array $sesion): array => array_diff_key($sesion, ['equipo_id' => true]));
    [, $login] = $solicitar('/equipo/login');
    comprobar($solicitar('/equipo/login', ['csrf' => oculto($login, 'csrf'), 'cedula' => '20000012', 'password' => 'EquipoAtencion28!'])[0] === 422,
        'La cuenta rechazada no puede iniciar sesión');
    [, $login] = $solicitar('/equipo/login');
    comprobar($solicitar('/equipo/login', ['csrf' => oculto($login, 'csrf'), 'cedula' => '20000013', 'password' => 'EquipoAtencion28!'])[0] === 303
        && ($entorno->sesion()['equipo_id'] ?? 0) === $pendientes[13], 'La cuenta aprobada puede iniciar sesión con su contraseña original');
    $identificar($usuarios['dueno']);
    [, $panel] = $solicitar('/equipo/panel');
    comprobar(str_contains($panel, 'Consultas recibidas (9 nuevas)') && str_contains($panel, 'Solicitudes de acceso (11)'), 'Los dos contadores se actualizan al volver al panel');

    $personal = new PersonalEquipo($db);
    comprobar($personal->listar('Equipo', 'empleado') === (new PermisosEquipo($db))->listar('Equipo', 'empleado'),
        'La consulta del personal y la gestión de roles comparten los mismos filtros y datos');
    comprobar(count($personal->listar()) === 3 && !isset($personal->listar()[0]['password_hash']),
        'El listado compartido excluye dueño, pendientes y rechazados, y no recupera contraseñas');
    $identificar($usuarios['administrador']);
    [$estado, $soloConsulta] = $solicitar('/equipo/personal');
    comprobar($estado === 200 && str_contains($soloConsulta, 'Funciones del rol')
        && str_contains($soloConsulta, '&lt;script&gt;Solicitante&lt;/script&gt;')
        && !str_contains($soloConsulta, 'name="id_usuario"') && !str_contains($soloConsulta, '/equipo/solicitudes'),
        'La vista compartida mantiene la consulta del administrador sin formularios de edición ni accesos del dueño');
    comprobar($solicitar('/css/equipo-personal.css')[0] === 200 && str_contains($soloConsulta, 'css/equipo-personal.css'),
        'La consulta utiliza la hoja de estilos compartida');
    comprobar($solicitar('/equipo/permisos')[0] === 403 && $solicitar('/equipo/permisos', ['rol' => 'dueno'])[0] === 403
        && $solicitar('/equipo/personal', ['rol' => 'dueno'])[0] === 405,
        'Compartir la vista no permite al administrador cambiar roles por ninguna ruta');
    $identificar($usuarios['empleado']);
    comprobar($solicitar('/equipo/personal')[0] === 403 && $solicitar('/equipo/permisos')[0] === 403,
        'El empleado sigue sin acceso al listado ni a la edición de personal');
    $identificar($usuarios['dueno']);
    [$estado, $redireccion] = $solicitar('/equipo/personal?q=Equipo%20%26%20pan&rol=empleado');
    comprobar($estado === 303 && str_contains($redireccion, 'Location: /equipo/permisos?q=Equipo%20%26%20pan&rol=empleado'),
        'Los enlaces antiguos del dueño llegan a una sola pantalla conservando los filtros');
    [$estado, $gestion] = $solicitar('/equipo/permisos');
    comprobar($estado === 200 && str_contains($gestion, '<h1 class="titulo-pagina__titulo">Administrar personal</h1>')
        && str_contains($gestion, 'name="id_usuario"') && str_contains($gestion, 'css/equipo-personal.css')
        && str_contains($gestion, '&lt;script&gt;Solicitante&lt;/script&gt;'), 'El dueño conserva la edición en la pantalla unificada');
    $cambioRol = ['csrf' => oculto($gestion, 'csrf'), 'id_usuario' => $usuarios['empleado'], 'rol' => 'administrador'];
    comprobar($solicitar('/equipo/permisos', array_replace($cambioRol, ['csrf' => 'incorrecto']))[0] === 422
        && (new AccesoEquipo($db))->buscarAprobado($usuarios['empleado'])['rol'] === 'empleado', 'Cambiar roles sigue exigiendo CSRF');
    comprobar($solicitar('/equipo/permisos', $cambioRol)[0] === 303
        && (new AccesoEquipo($db))->buscarAprobado($usuarios['empleado'])['rol'] === 'administrador',
        'El formulario compartido conserva la actualización de roles del dueño');
    comprobar($solicitar('/equipo/permisos', array_replace($cambioRol, ['id_usuario' => $usuarios['dueno']]))[0] === 422,
        'El dueño no puede cambiar su propia cuenta desde el formulario');
    comprobar($solicitar('/equipo/permisos?rol=invalido')[0] === 422 && $solicitar('/equipo/permisos?q%5B%5D=x')[0] === 422,
        'El listado compartido mantiene la validación de filtros');

    $db->exec("UPDATE usuarios_equipo SET estado = 'pendiente' WHERE id_usuario = " . $usuarios['dueno']);
    rechaza(fn () => $solicitudes->resolver($pendientes[1], $usuarios['dueno'], 'aprobar'), 'El modelo impide guardar si revocaron al dueño');
    comprobar($solicitar('/equipo/solicitudes')[0] === 303, 'La autorización se vuelve a comprobar en cada petición');
    comprobar(!preg_match('/Fatal error:|Warning:|Notice:|Deprecated:/', $entorno->errores()), 'Las bandejas no generan errores PHP');
    echo "Correcto: $comprobaciones comprobaciones de atención del equipo.\n";
} catch (Throwable $error) {
    $correcto = false;
    fwrite(STDERR, $error->getMessage() . "\n" . $error->getTraceAsString() . "\n");
    fwrite(STDERR, $entorno?->errores() ?? '');
} finally {
    $entorno?->cerrar();
}
exit($correcto ? 0 : 1);
