<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/soporte.php';

use App\Models\EntregaMercaderia;
use App\Models\StockMateriaPrima;
use App\Models\UsuarioEquipo;

$entorno = null;
$comprobaciones = 0;
$correcto = true;
try {
    $entorno = new EntornoPruebas('hu09');
    $db = $entorno->db;
    $stock = new StockMateriaPrima($db);
    $modelo = new EntregaMercaderia($db);
    foreach (['Harina' => '1.250', 'Azúcar' => '2.000', 'Inactivo' => '0', 'Al límite' => '999999999.999'] as $nombre => $cantidad) {
        $stock->agregar(['nombre' => $nombre, 'unidad' => 'kg', 'stock_actual' => $cantidad, 'stock_minimo' => '2']);
    }
    $db->exec('UPDATE materias_primas SET activo = 0 WHERE id_materia = 3');
    $usuarios = new UsuarioEquipo($db);
    $cuentas = [];
    $password = 'MercaderiaPrueba09!';
    foreach (['administrador' => '7100001', 'dueno' => '7100002', 'empleado' => '7100003'] as $rol => $cedula) {
        $cuentas[$rol] = $usuarios->solicitar($rol, $cedula, $rol . '@example.test', $password, $password);
        $consulta = $db->prepare("UPDATE usuarios_equipo SET rol = ?, estado = 'aprobado' WHERE id_usuario = ?");
        $consulta->execute([$rol, $cuentas[$rol]['id_usuario']]);
    }
    $admin = $cuentas['administrador']['id_usuario'];
    $datos = ['proveedor' => 'Molino de prueba', 'fecha_recepcion' => date('Y-m-d'), 'comprobante' => 'REM-09',
        'observaciones' => 'Recepción completa', 'lineas' => [
            ['id_materia' => '1', 'cantidad' => '0,125'], ['id_materia' => '2', 'cantidad' => '3.001'],
        ]];
    $cantidades = static fn (): array => $db->query('SELECT stock_actual FROM materias_primas ORDER BY id_materia')->fetchAll(PDO::FETCH_COLUMN);
    $antes = $cantidades();
    $vieja = $db->query('SELECT id_materia, nombre, unidad, stock_actual, stock_minimo FROM materias_primas WHERE id_materia = 1')->fetch();
    $token = bin2hex(random_bytes(32));
    $id = $modelo->registrar($datos + ['id_usuario' => 999, 'stock_actual' => 99], $admin, $token);
    comprobar(array_slice($cantidades(), 0, 2) === ['1.375', '5.001'], 'Suma varias materias primas con tres decimales exactos');
    $entrega = $modelo->listar()['entregas'][0];
    comprobar($entrega['responsable'] === 'administrador' && $entrega['detalles'][0]['stock_anterior'] === '1.250'
        && $entrega['detalles'][0]['stock_resultante'] === '1.375', 'Conserva quién recibió y el movimiento de stock de cada insumo');
    comprobar($modelo->registrar($datos, $admin, $token) === $id && $cantidades()[0] === '1.375', 'Repetir la confirmación no suma stock ni crea otra entrega');
    rechaza(fn () => $modelo->registrar(array_replace($datos, ['proveedor' => 'Otro']), $admin, $token), 'No cambia una entrega ya confirmada reutilizando el formulario');
    rechaza(fn () => $stock->actualizar(1, ['stock_actual' => '0', 'stock_minimo' => '2'], StockMateriaPrima::version($vieja)), 'Un formulario de HU07 anterior a la entrega no sobrescribe el stock recibido');
    foreach (['0', '-1', '1e3', '0.0001', '1000000000', ['x']] as $invalida) {
        $malos = array_replace($datos, ['lineas' => [['id_materia' => '1', 'cantidad' => $invalida]]]);
        rechaza(fn () => $modelo->registrar($malos, $admin, bin2hex(random_bytes(32))), 'Rechaza cantidades nulas, negativas o fuera de precisión');
    }
    foreach (['2025-02-30', date('Y-m-d', strtotime('+1 day')), "2025-01-01\0", ['x']] as $fecha) {
        rechaza(fn () => $modelo->registrar(array_replace($datos, ['fecha_recepcion' => $fecha]), $admin, bin2hex(random_bytes(32))), 'Valida fechas de recepción, incluidas fechas futuras y datos mal formados');
    }
    foreach ([[], array_fill(0, 21, ['id_materia' => '1', 'cantidad' => '1']),
        [['id_materia' => '1', 'cantidad' => '1'], ['id_materia' => '1', 'cantidad' => '2']],
        [['id_materia' => '9999', 'cantidad' => '1']], [['id_materia' => '3', 'cantidad' => '1']],
        [['id_materia' => ['x'], 'cantidad' => '1']]] as $lineas) {
        rechaza(fn () => $modelo->registrar(array_replace($datos, ['lineas' => $lineas]), $admin, bin2hex(random_bytes(32))), 'Valida insumos, duplicados y límites de filas');
    }
    $sinCambio = $cantidades();
    $desborde = array_replace($datos, ['lineas' => [['id_materia' => '1', 'cantidad' => '1'], ['id_materia' => '4', 'cantidad' => '0.001']]]);
    rechaza(fn () => $modelo->registrar($desborde, $admin, bin2hex(random_bytes(32))), 'Impide superar la capacidad DECIMAL del stock');
    comprobar($cantidades() === $sinCambio && $modelo->listar()['total'] === 1, 'Si falla una línea revierte también el resto del stock y el historial');
    $db->exec("CREATE TRIGGER fallo_hu09 BEFORE UPDATE ON materias_primas FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fallo simulado'");
    try { $modelo->registrar($datos, $admin, bin2hex(random_bytes(32))); throw new RuntimeException('Debía fallar el guardado'); }
    catch (PDOException) { comprobar($cantidades() === $sinCambio && $modelo->listar()['total'] === 1, 'Un fallo de MySQL no deja una recepción parcial'); }
    $db->exec('DROP TRIGGER fallo_hu09');
    rechaza(fn () => $modelo->registrar($datos, $cuentas['empleado']['id_usuario'], bin2hex(random_bytes(32))), 'El modelo no registra entregas con una cuenta sin permiso');
    $db->exec("UPDATE materias_primas SET nombre = 'Harina renombrada', unidad = 'g' WHERE id_materia = 1");
    comprobar($modelo->listar('Harina')['entregas'][0]['detalles'][0]['nombre_materia'] === 'Harina'
        && $modelo->listar()['entregas'][0]['detalles'][0]['unidad'] === 'kg', 'El historial conserva el nombre y la unidad originales');
    comprobar($modelo->listar('REM-09')['total'] === 1 && $modelo->listar('Molino')['total'] === 1, 'Busca por proveedor, comprobante y materia prima');

    $entorno->iniciarServidor();
    $solicitar = $entorno->solicitar(...);
    foreach (['/equipo/stock/entregas', '/equipo/stock/entregas/registrar'] as $ruta) comprobar($solicitar($ruta)[0] === 303, 'Exige acceso al equipo');
    $entrar = static function (string $cedula) use ($solicitar, $password): void {
        $csrf = oculto($solicitar('/equipo/login')[1], 'csrf');
        comprobar($solicitar('/equipo/login', ['csrf' => $csrf, 'cedula' => $cedula, 'password' => $password])[0] === 303, 'Ingresa una cuenta de prueba');
    };
    $salir = static fn () => $solicitar('/equipo/salir', ['csrf' => $entorno->sesion()['csrf']]);
    $entrar('7100003');
    comprobar($solicitar('/equipo/stock/entregas')[0] === 403 && $solicitar('/equipo/stock/entregas/registrar', $datos + ['rol' => 'dueno'])[0] === 403, 'Un empleado no consulta ni registra entregas aunque falsifique el rol');
    $salir();
    $entrar('7100001');
    [$estado, $html] = $solicitar('/equipo/stock/entregas/registrar');
    comprobar($estado === 200 && str_contains($html, 'Cache-Control: no-store') && str_contains($html, '/js/mercaderia.js'), 'Carga el formulario interno y no lo guarda en caché');
    $campos = $datos + ['csrf' => oculto($html, 'csrf'), 'token_formulario' => oculto($html, 'token_formulario'), 'accion' => 'registrar'];
    comprobar($solicitar('/equipo/stock/entregas', [])[0] === 405, 'El historial es de solo lectura');
    comprobar($solicitar('/equipo/stock/entregas/registrar', array_replace($campos, ['csrf' => 'otro']))[0] === 422, 'Exige CSRF antes de modificar stock');
    comprobar($solicitar('/equipo/stock/entregas/registrar', array_replace($campos, ['token_formulario' => str_repeat('a', 64)]))[0] === 422, 'Exige un formulario emitido a la sesión actual');
    [$estado, $html] = $solicitar('/equipo/stock/entregas/registrar', array_replace($campos, ['accion' => 'agregar-linea']));
    comprobar($estado === 200 && str_contains($html, 'name="lineas[2][id_materia]"'), 'Permite agregar filas sin JavaScript conservando los datos');
    comprobar($solicitar('/equipo/stock/entregas/registrar', array_replace($campos, ['lineas' => 'incorrecto']))[0] === 422, 'Rechaza el detalle HTTP mal formado');
    [$estado, $html] = $solicitar('/equipo/stock/entregas/registrar', array_replace($campos, ['proveedor' => '<script>proveedor</script>', 'fecha_recepcion' => 'invalida']));
    comprobar($estado === 422 && str_contains($html, '&lt;script&gt;proveedor&lt;/script&gt;') && !str_contains($html, '<script>proveedor</script>'), 'Conserva los datos con error y escapa el texto recibido');
    $web = array_replace($campos, ['proveedor' => 'Proveedor <web>', 'observaciones' => '<img src=x onerror=alert(1)>', 'lineas' => [['id_materia' => '2', 'cantidad' => '0.999']]]);
    $db->exec("CREATE TRIGGER fallo_hu09 BEFORE UPDATE ON materias_primas FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fallo simulado'");
    [$estado, $html] = $solicitar('/equipo/stock/entregas/registrar', $web);
    comprobar($estado === 503 && !str_contains($html, 'SQLSTATE') && $cantidades() === $sinCambio, 'Informa un fallo sin exponer MySQL ni modificar el stock');
    $db->exec('DROP TRIGGER fallo_hu09');
    [$estado, $html] = $solicitar('/equipo/stock/entregas/registrar', $web);
    comprobar($estado === 303 && str_contains($html, 'Location: /equipo/stock/entregas') && $cantidades()[1] === '6.000', 'Confirma la recepción y redirige al historial');
    comprobar($solicitar('/equipo/stock/entregas/registrar', $web)[0] === 303 && $cantidades()[1] === '6.000', 'El doble POST HTTP suma stock una sola vez');
    [, $historial] = $solicitar('/equipo/stock/entregas');
    comprobar(str_contains($historial, 'El stock quedó actualizado') && str_contains($historial, 'Proveedor &lt;web&gt;')
        && !str_contains($historial, '<img src=x'), 'El historial muestra la recepción escapando proveedor y observaciones');
    comprobar(!str_contains($solicitar('/equipo/stock/entregas')[1], 'El stock quedó actualizado'), 'Actualizar el historial no repite la confirmación');
    comprobar(str_contains($solicitar('/equipo/stock')[1], '6.000') && str_contains($solicitar('/equipo/panel')[1], '/equipo/stock/entregas/registrar'), 'Integra la recepción con HU07 y con el panel');
    $db->exec("UPDATE usuarios_equipo SET rol = 'empleado' WHERE id_usuario = " . $admin);
    comprobar($solicitar('/equipo/stock/entregas/registrar', $web)[0] === 403, 'Revoca permisos de un formulario abierto si cambia el rol');
    $salir();
    $entrar('7100002');
    comprobar($solicitar('/equipo/stock/entregas')[0] === 200, 'El dueño también consulta las recepciones');
    $soloAzucar = array_replace($datos, ['lineas' => [['id_materia' => '2', 'cantidad' => '0.001']]]);
    // Dos conexiones independientes esperan el mismo insumo: ninguna suma debe perderse.
    $trabajo = 'require $argv[1] . "/app/bootstrap.php";
        $modelo = new App\\Models\\EntregaMercaderia(App\\Core\\Conexion::obtener());
        echo $modelo->registrar(json_decode($argv[3], true, 512, JSON_THROW_ON_ERROR), (int) $argv[2], $argv[4]);';
    foreach ([false, true] as $mismoFormulario) {
        $db->beginTransaction();
        $db->query('SELECT id_materia FROM materias_primas WHERE id_materia = 2 FOR UPDATE')->fetch();
        $procesos = [];
        $tokenConcurrente = bin2hex(random_bytes(32));
        for ($i = 0; $i < 2; $i++) {
            $proceso = proc_open([PHP_BINARY, '-r', $trabajo, dirname(__DIR__), (string) $cuentas['dueno']['id_usuario'],
                json_encode($soloAzucar, JSON_THROW_ON_ERROR), $mismoFormulario ? $tokenConcurrente : bin2hex(random_bytes(32))],
                [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $tuberias, dirname(__DIR__),
                array_replace(getenv(), ['QBA_DB_NAME' => $entorno->base]), ['bypass_shell' => true]);
            if (!is_resource($proceso)) throw new RuntimeException('No se pudo iniciar la recepción concurrente.');
            fclose($tuberias[0]);
            $procesos[] = [$proceso, $tuberias];
        }
        usleep(150000);
        $db->commit();
        $idsConcurrentes = [];
        foreach ($procesos as [$proceso, $tuberias]) {
            $idsConcurrentes[] = trim(stream_get_contents($tuberias[1]));
            $error = stream_get_contents($tuberias[2]);
            fclose($tuberias[1]); fclose($tuberias[2]);
            comprobar(proc_close($proceso) === 0, 'La recepción concurrente termina correctamente: ' . $error);
        }
        comprobar($cantidades()[1] === ($mismoFormulario ? '6.003' : '6.002')
            && (count(array_unique($idsConcurrentes)) === ($mismoFormulario ? 1 : 2)),
            $mismoFormulario ? 'Dos confirmaciones simultáneas del mismo formulario se registran una vez'
                : 'Dos recepciones simultáneas suman ambas cantidades sin sobrescribirse');
    }
    for ($i = 0; $i < 25; $i++) $modelo->registrar($soloAzucar, $cuentas['dueno']['id_usuario'], bin2hex(random_bytes(32)));
    comprobar(count($modelo->listar()['entregas']) === 25 && count($modelo->listar('', 2)['entregas']) === 5, 'Pagina el historial sin cargar todas las entregas');
    $resultado = $entorno->cli('database/instalar.php', []);
    comprobar($resultado[0] === 0 && $modelo->listar()['total'] === 30 && $cantidades()[1] === '6.028', 'El instalador se puede repetir sin borrar entregas ni alterar el stock');
    comprobar(!preg_match('/Fatal error:|Warning:|Notice:|Deprecated:/', $entorno->errores()), 'El recorrido no genera errores PHP');
    echo "Correcto: $comprobaciones comprobaciones de HU09.\n";
} catch (Throwable $error) {
    $correcto = false;
    fwrite(STDERR, $error->getMessage() . "\n" . $error->getTraceAsString() . "\n");
    fwrite(STDERR, $entorno?->errores() ?? '');
} finally {
    if ($entorno?->db->inTransaction()) $entorno->db->rollBack();
    $entorno?->cerrar();
}
exit($correcto ? 0 : 1);
