<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/soporte.php';

use App\Models\UsuarioEquipo;

$entorno = null;
$comprobaciones = 0;
$correcto = true;
try {
    $entorno = new EntornoPruebas('hu02');
    $db = $entorno->db;
    $db->exec(file_get_contents(dirname(__DIR__) . '/database/comprobantes-transferencia.sql'));
    $password = 'EstadoPedidoPrueba02!';
    foreach (['administrador', 'empleado', 'dueno'] as $indice => $rol) {
        $cuenta = (new UsuarioEquipo($db))->solicitar(
            $rol,
            (string) (7500001 + $indice),
            $rol . '@example.test',
            $password,
            $password,
        );
        $db->prepare(
            "UPDATE usuarios_equipo SET estado = 'aprobado', rol = ? WHERE id_usuario = ?",
        )->execute([$rol, $cuenta['id_usuario']]);
    }
    $db->prepare(
        "INSERT INTO pedidos (cliente_nombre,telefono,fecha_retiro,hora_retiro,notas,estado,total,token_confirmacion)
        VALUES ('Cliente ficticio','099123456',?,'10:00','','Pendiente',100,?)",
    )->execute([date('Y-m-d', strtotime('+1 day')), bin2hex(random_bytes(32))]);
    $id = (int) $db->lastInsertId();
    $estadoActual = static fn(): string => $db
        ->query('SELECT estado FROM pedidos WHERE id_pedido = ' . $id)
        ->fetchColumn();
    $historial = static fn(): int => (int) $db
        ->query('SELECT COUNT(*) FROM pedido_cambios WHERE id_pedido = ' . $id)
        ->fetchColumn();
    $entorno->iniciarServidor();
    $solicitar = $entorno->solicitar(...);
    comprobar($solicitar('/equipo/pedidos')[0] === 303, 'Exige la sesión del equipo');
    $entrar = static function (string $cedula) use ($solicitar, $password): string {
        [, $html] = $solicitar('/equipo/login');
        comprobar(
            $solicitar('/equipo/login', [
                'csrf' => oculto($html, 'csrf'),
                'cedula' => $cedula,
                'password' => $password,
            ])[0] === 303,
            'Ingresa la cuenta de prueba',
        );
        return oculto($solicitar('/equipo/pedidos')[1], 'csrf');
    };
    $csrf = $entrar('7500001');
    $campos = [
        'csrf' => $csrf,
        'accion' => 'actualizar_estado',
        'id_pedido' => (string) $id,
        'estado_nuevo' => 'En preparación',
    ];
    comprobar(
        $solicitar('/equipo/pedidos', array_replace($campos, ['csrf' => 'incorrecto']))[0] ===
            422 && $estadoActual() === 'Pendiente',
        'CSRF inválido no modifica el pedido',
    );
    [$estado, $html] = $solicitar(
        '/equipo/pedidos',
        array_replace($campos, ['estado_nuevo' => 'Inventado']),
    );
    comprobar(
        $estado === 422 &&
            str_contains($html, 'id="pedido-estado-' . $id . '-error"') &&
            str_contains($html, 'aria-invalid="true"') &&
            $estadoActual() === 'Pendiente',
        'El estado no permitido se señala en el control sin modificar el pedido',
    );
    $db->exec(
        "CREATE TRIGGER fallo_hu02 BEFORE UPDATE ON pedidos FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fallo simulado'",
    );
    [$estado, $html] = $solicitar('/equipo/pedidos', $campos);
    comprobar(
        $estado === 503 &&
            !str_contains($html, 'SQLSTATE') &&
            str_contains($html, 'value="En preparación" selected') &&
            $estadoActual() === 'Pendiente' &&
            $historial() === 0,
        'Un fallo conserva la selección, informa el error y revierte la operación',
    );
    $db->exec('DROP TRIGGER fallo_hu02');
    comprobar(
        $solicitar('/equipo/pedidos', $campos)[0] === 303 &&
            $estadoActual() === 'En preparación' &&
            $historial() === 1,
        'Guarda el cambio y su historial mediante POST',
    );
    [, $html] = $solicitar('/equipo/pedidos');
    comprobar(
        str_contains($html, 'Pedido #' . $id . ': estado guardado como «En preparación».') &&
            str_contains($html, 'role="status" tabindex="-1" data-resultado-pedidos'),
        'La confirmación accesible identifica el pedido y el nuevo estado',
    );
    comprobar(
        $solicitar('/equipo/pedidos', $campos)[0] === 303 && $historial() === 1,
        'Reenviar el mismo estado no duplica el historial',
    );
    comprobar(
        $solicitar(
            '/equipo/pedidos',
            array_replace($campos, ['estado_nuevo' => 'Entregado']),
        )[0] === 303,
        'El administrador puede cerrar un pedido activo',
    );
    comprobar(
        $solicitar('/equipo/pedidos', $campos)[0] === 422 && $estadoActual() === 'Entregado',
        'El administrador no puede reabrir un pedido cerrado',
    );
    $solicitar('/equipo/salir', ['csrf' => $csrf]);
    $campos['csrf'] = $entrar('7500003');
    comprobar(
        $solicitar('/equipo/pedidos', $campos)[0] === 303 && $estadoActual() === 'En preparación',
        'El dueño conserva la corrección de pedidos cerrados',
    );
    $solicitar('/equipo/salir', ['csrf' => $campos['csrf']]);
    $campos['csrf'] = $entrar('7500002');
    comprobar(
        $solicitar(
            '/equipo/pedidos',
            array_replace($campos, ['estado_nuevo' => 'Cancelado']),
        )[0] === 422 && $estadoActual() === 'En preparación',
        'El empleado no puede forzar un estado fuera de su flujo',
    );
    comprobar(
        $solicitar(
            '/equipo/pedidos',
            array_replace($campos, ['estado_nuevo' => 'Listo para retirar']),
        )[0] === 303 && $estadoActual() === 'Listo para retirar',
        'El empleado conserva el avance permitido del pedido',
    );
    comprobar(
        !preg_match('/Fatal error:|Warning:|Notice:|Deprecated:/', $entorno->errores()),
        'El recorrido no genera advertencias PHP',
    );
    echo "Correcto: $comprobaciones comprobaciones de HU02.\n";
} catch (Throwable $error) {
    $correcto = false;
    fwrite(STDERR, $error->getMessage() . "\n" . $error->getTraceAsString() . "\n");
    fwrite(STDERR, $entorno?->errores() ?? '');
} finally {
    $entorno?->cerrar();
}
exit($correcto ? 0 : 1);
