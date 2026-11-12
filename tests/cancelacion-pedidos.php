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
    $entorno = new EntornoPruebas('hu03');
    $db = $entorno->db;
    $db->exec(file_get_contents(dirname(__DIR__) . '/database/comprobantes-transferencia.sql'));
    $password = 'CancelacionPedidoPrueba03!';
    foreach (['administrador', 'empleado', 'dueno'] as $indice => $rol) {
        $cuenta = (new UsuarioEquipo($db))->solicitar(
            $rol,
            (string) (7600001 + $indice),
            $rol . '@example.test',
            $password,
            $password,
        );
        $db->prepare(
            "UPDATE usuarios_equipo SET estado = 'aprobado', rol = ? WHERE id_usuario = ?",
        )->execute([$rol, $cuenta['id_usuario']]);
    }
    $crear = $db->prepare("INSERT INTO pedidos (cliente_nombre,telefono,fecha_retiro,hora_retiro,notas,estado,total,token_confirmacion)
        VALUES (?, '099123456', ?, '10:00','Nota ficticia','Pendiente',100,?)");
    $ids = [];
    for ($indice = 0; $indice < 3; $indice++) {
        $crear->execute([
            'Cliente <prueba> "HU03"',
            date('Y-m-d', strtotime('+1 day')),
            bin2hex(random_bytes(32)),
        ]);
        $ids[] = (int) $db->lastInsertId();
    }
    $id = $ids[0];
    $db->prepare(
        "INSERT INTO pedido_detalles (id_pedido,id_producto,nombre_producto,cantidad,precio_unitario)
        VALUES (?,1,'Pan ficticio',1,100)",
    )->execute([$id]);
    $leer = static fn(int $id): array => $db
        ->query('SELECT * FROM pedidos WHERE id_pedido = ' . $id)
        ->fetch();
    $original = $leer($id);
    $historial = static fn(int $id): int => (int) $db
        ->query('SELECT COUNT(*) FROM pedido_cambios WHERE id_pedido = ' . $id)
        ->fetchColumn();
    $entorno->iniciarServidor();
    $solicitar = $entorno->solicitar(...);
    comprobar(
        $solicitar('/equipo/pedidos', [
            'accion' => 'cancelar_pedido',
            'id_pedido' => $id,
            'confirmar_cancelacion' => '1',
        ])[0] === 303,
        'La confirmación no habilita a una sesión anónima',
    );
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
    $csrf = $entrar('7600001');
    $campos = ['csrf' => $csrf, 'accion' => 'cancelar_pedido', 'id_pedido' => (string) $id];
    [$estado, $html] = $solicitar('/equipo/pedidos', $campos);
    comprobar(
        $estado === 200 &&
            str_contains($html, '¿Cancelar el pedido #' . $id . '?') &&
            str_contains($html, 'name="confirmar_cancelacion" value="1"') &&
            str_contains($html, 'Cliente &lt;prueba&gt; &quot;HU03&quot;'),
        'PHP muestra la confirmación con el pedido y el cliente escapado',
    );
    comprobar(
        $leer($id) === $original && $historial($id) === 0,
        'Abrir la confirmación conserva todos los datos y el historial',
    );
    comprobar(
        $solicitar('/equipo/pedidos')[0] === 200 && $leer($id) === $original,
        'Volver al listado no cancela el pedido',
    );
    comprobar(
        $solicitar(
            '/equipo/pedidos',
            array_replace($campos, ['csrf' => 'incorrecto', 'confirmar_cancelacion' => '1']),
        )[0] === 422 && $leer($id) === $original,
        'La confirmación mantiene la protección CSRF',
    );
    comprobar(
        $solicitar('/equipo/pedidos', $campos + ['confirmar_cancelacion' => ['1']])[0] === 422,
        'Rechaza una confirmación HTTP malformada',
    );
    comprobar(
        $solicitar('/equipo/pedidos', $campos + ['confirmar_cancelacion' => '1'])[0] === 303 &&
            $leer($id)['estado'] === 'Cancelado' &&
            $historial($id) === 1,
        'Confirmar cancela el pedido y registra el cambio una sola vez',
    );
    $actual = $leer($id);
    unset($original['estado'], $actual['estado']);
    comprobar(
        $actual === $original &&
            (int) $db
                ->query('SELECT COUNT(*) FROM pedido_detalles WHERE id_pedido = ' . $id)
                ->fetchColumn() === 1,
        'La cancelación conserva cliente, importes, fechas y productos',
    );
    [, $html] = $solicitar('/equipo/pedidos');
    comprobar(
        str_contains(
            $html,
            'Pedido #' . $id . ' cancelado. Sus datos y su historial se conservan.',
        ),
        'La confirmación final identifica el pedido cancelado',
    );
    comprobar(
        $solicitar('/equipo/pedidos', $campos + ['confirmar_cancelacion' => '1'])[0] === 422 &&
            $historial($id) === 1,
        'Reenviar la cancelación no duplica el historial',
    );
    $campos['id_pedido'] = (string) $ids[1];
    $estadoCampos = array_replace($campos, [
        'accion' => 'actualizar_estado',
        'estado_nuevo' => 'Cancelado',
    ]);
    comprobar(
        $solicitar('/equipo/pedidos', $estadoCampos)[0] === 200 &&
            $leer($ids[1])['estado'] === 'Pendiente',
        'Elegir Cancelado en el selector también requiere confirmación PHP',
    );
    $db->exec("UPDATE pedidos SET estado = 'Entregado' WHERE id_pedido = " . $ids[1]);
    comprobar(
        $solicitar('/equipo/pedidos', $estadoCampos + ['confirmar_cancelacion' => '1'])[0] ===
            422 && $leer($ids[1])['estado'] === 'Entregado',
        'Se vuelve a comprobar el estado al confirmar un pedido que cambió',
    );
    $solicitar('/equipo/salir', ['csrf' => $csrf]);
    $campos['csrf'] = $entrar('7600002');
    $campos['id_pedido'] = (string) $ids[2];
    comprobar(
        $solicitar(
            '/equipo/pedidos',
            $campos + ['confirmar_cancelacion' => '1', 'rol' => 'dueno'],
        )[0] === 403 && $leer($ids[2])['estado'] === 'Pendiente',
        'Una confirmación y un rol enviados no conceden permisos al empleado',
    );
    comprobar(
        $solicitar(
            '/equipo/pedidos',
            array_replace($campos, [
                'accion' => 'actualizar_estado',
                'estado_nuevo' => 'Cancelado',
            ]),
        )[0] === 422,
        'El empleado tampoco accede a la confirmación a través del selector',
    );
    $solicitar('/equipo/salir', ['csrf' => $campos['csrf']]);
    $campos['csrf'] = $entrar('7600003');
    comprobar(
        $solicitar('/equipo/pedidos', $campos)[0] === 200 &&
            $solicitar('/equipo/pedidos', $campos + ['confirmar_cancelacion' => '1'])[0] === 303,
        'El dueño puede revisar y confirmar la cancelación',
    );
    $estadoCampos['csrf'] = $campos['csrf'];
    comprobar(
        $solicitar('/equipo/pedidos', $estadoCampos)[0] === 200 &&
            $solicitar('/equipo/pedidos', $estadoCampos + ['confirmar_cancelacion' => '1'])[0] ===
                303,
        'El dueño conserva la corrección de pedidos cerrados mediante el selector',
    );
    comprobar(
        !preg_match('/Fatal error:|Warning:|Notice:|Deprecated:/', $entorno->errores()),
        'El recorrido no genera errores PHP',
    );
    echo "Correcto: $comprobaciones comprobaciones de HU03.\n";
} catch (Throwable $error) {
    $correcto = false;
    fwrite(STDERR, $error->getMessage() . "\n" . $error->getTraceAsString() . "\n");
    fwrite(STDERR, $entorno?->errores() ?? '');
} finally {
    $entorno?->cerrar();
}
exit($correcto ? 0 : 1);
