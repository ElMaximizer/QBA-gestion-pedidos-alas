<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/soporte.php';

use App\Models\ConsultaPedidos;

$entorno = null;
$comprobaciones = 0;
$correcto = true;
try {
    $entorno = new EntornoPruebas('equipo');
    $db = $entorno->db;
    $modelo = new ConsultaPedidos($db);
    $vacio = ['hoy' => 0, 'pendientes_hoy' => 0, 'por_entregar' => 0];
    comprobar($modelo->resumenPanel() === $vacio, 'Sin pedidos muestra ceros enteros');

    $hoy = new DateTimeImmutable('today');
    $crear = $db->prepare('INSERT INTO pedidos (cliente_nombre,telefono,fecha_retiro,hora_retiro,notas,estado,total,token_confirmacion,creado_en)
        VALUES (?,?,?,\'16:00\',\'\',?,100,?,?)');
    $ids = [];
    foreach ([
        [0, 'Pendiente'], [0, 'Pendiente'], [0, 'En preparación'], [0, 'Listo para retirar'], [0, 'Entregado'], [0, 'Cancelado'],
        [1, 'Pendiente'], [1, 'Listo para retirar'], [-1, 'Pendiente'], [-1, 'En preparación'], [-1, 'Entregado'], [-1, 'Cancelado'],
    ] as [$dias, $estado]) {
        $crear->execute(['Cliente de prueba', '099123456', $hoy->modify(sprintf('%+d days', $dias))->format('Y-m-d'), $estado,
            bin2hex(random_bytes(32)), '2001-01-01 10:00:00']);
        $ids[] = (int) $db->lastInsertId();
    }
    $esperado = ['hoy' => 5, 'pendientes_hoy' => 2, 'por_entregar' => 8];
    comprobar($modelo->resumenPanel() === $esperado, 'Cuenta por fecha de retiro, conserva entregados de hoy y excluye cancelados');
    $detalle = $db->prepare("INSERT INTO pedido_detalles (id_pedido,id_producto,nombre_producto,cantidad,precio_unitario) VALUES (?,1,'Producto de prueba',?,10)");
    $detalle->execute([$ids[0], 2]);
    $detalle->execute([$ids[0], 5]);
    comprobar($modelo->resumenPanel() === $esperado, 'Varias líneas y unidades no duplican el número de pedidos');
    $db->exec("SET timestamp = UNIX_TIMESTAMP('2000-01-01 12:00:00')");
    comprobar($modelo->resumenPanel() === $esperado, 'El resumen usa el día de la aplicación aunque MySQL tenga otro día');
    $db->exec('SET timestamp = 0');

    $entorno->iniciarServidor();
    $solicitar = $entorno->solicitar(...);
    comprobar($solicitar('/equipo/panel')[0] === 303, 'El resumen sigue exigiendo sesión del equipo');
    $crearUsuario = $db->prepare("INSERT INTO usuarios_equipo (nombre,cedula,email,password_hash,estado,rol) VALUES (?,?,?,?, 'aprobado',?)");
    $hash = password_hash('ResumenPanel28!', PASSWORD_DEFAULT);
    $leerPanel = static function () use ($solicitar): array {
        [$estado, $html] = $solicitar('/equipo/panel');
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);
        $valores = [];
        foreach ($xpath->query('//div[@class="panel__estadisticas"]/article') as $tarjeta) {
            $nombre = trim($xpath->evaluate('string(p[@class="panel-estadistica__descripcion"])', $tarjeta));
            $valores[$nombre] = [
                trim($xpath->evaluate('string(strong)', $tarjeta)),
                trim($xpath->evaluate('string(p[@class="panel-estadistica__detalle"])', $tarjeta)),
            ];
        }
        return [$estado, $html, $valores];
    };
    foreach (['empleado', 'administrador', 'dueno'] as $i => $rol) {
        $crearUsuario->execute(['Equipo de prueba', 12345670 + $i, $rol . '@example.test', $hash, $rol]);
        $id = (int) $db->lastInsertId();
        $entorno->sesion(static fn (array $sesion): array => array_replace($sesion, ['equipo_id' => $id, 'equipo_ultimo_acceso' => time()]));
        [$estado, $html, $valores] = $leerPanel();
        comprobar($estado === 200 && $valores['Pedidos de hoy'] === ['5', '2 pendientes de preparar']
            && $valores['Por entregar'][0] === '8', 'El rol ' . $rol . ' ve el mismo resumen real');
        comprobar(str_contains($html, 'Cache-Control: no-store') && $valores['Productos activos'] === ['3', '3 categorías disponibles'],
            'El panel conserva el resumen del catálogo y evita mostrar datos en caché');
    }
    $actualizar = $db->prepare('UPDATE pedidos SET estado = ? WHERE id_pedido = ?');
    $actualizar->execute(['En preparación', $ids[0]]);
    [, , $valores] = $leerPanel();
    comprobar($valores['Pedidos de hoy'] === ['5', '1 pendiente de preparar'] && $valores['Por entregar'][0] === '8',
        'Empezar la preparación actualiza el pendiente y mantiene el total por entregar');
    $actualizar->execute(['Entregado', $ids[3]]);
    [, , $valores] = $leerPanel();
    comprobar($valores['Pedidos de hoy'][0] === '5' && $valores['Por entregar'][0] === '7',
        'Entregar mantiene el total del día y reduce los pedidos por entregar');
    $actualizar->execute(['Cancelado', $ids[1]]);
    [, , $valores] = $leerPanel();
    comprobar($valores['Pedidos de hoy'] === ['4', '0 pendientes de preparar'] && $valores['Por entregar'][0] === '6',
        'Cancelar reduce los totales y muestra cero pendientes');
    $db->exec("UPDATE pedidos SET estado = 'Cancelado'");
    [, , $valores] = $leerPanel();
    comprobar($modelo->resumenPanel() === $vacio && $valores['Pedidos de hoy'][0] === '0' && $valores['Por entregar'][0] === '0',
        'El resumen vacío también se presenta correctamente en la pantalla');
    comprobar(!preg_match('/Fatal error:|Warning:|Notice:|Deprecated:/', $entorno->errores()), 'El resumen no genera errores PHP');
    echo "Correcto: $comprobaciones comprobaciones del resumen del panel.\n";
} catch (Throwable $error) {
    $correcto = false;
    fwrite(STDERR, $error->getMessage() . "\n" . $error->getTraceAsString() . "\n");
    fwrite(STDERR, $entorno?->errores() ?? '');
} finally {
    $entorno?->cerrar();
}
exit($correcto ? 0 : 1);
