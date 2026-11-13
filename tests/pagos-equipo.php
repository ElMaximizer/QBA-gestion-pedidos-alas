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
    $entorno = new EntornoPruebas('hu05');
    $db = $entorno->db;
    $db->exec(file_get_contents(dirname(__DIR__) . '/database/comprobantes-transferencia.sql'));
    $password = 'PagosEquipoPrueba05!';
    $cuentas = [];
    foreach (['administrador', 'empleado', 'dueno'] as $indice => $rol) {
        $cuentas[$rol] = (new UsuarioEquipo($db))->solicitar(
            $rol,
            (string) (7800001 + $indice),
            $rol . '@example.test',
            $password,
            $password,
        );
        $db->prepare(
            "UPDATE usuarios_equipo SET estado = 'aprobado', rol = ? WHERE id_usuario = ?",
        )->execute([$rol, $cuentas[$rol]['id_usuario']]);
    }
    $casos = [
        'efectivo' => ['local', 'Pendiente', 'pendiente', null],
        'efectivo_empleado' => ['local', 'Pendiente', 'pendiente', null],
        'efectivo_cancelado' => ['local', 'Cancelado', 'pendiente', null],
        'efectivo_pagado' => ['local', 'Pendiente', 'pagado', null],
        'aprobar' => ['transferencia', 'Pendiente', 'pendiente', 'pendiente'],
        'rechazar' => ['transferencia', 'Pendiente', 'pendiente', 'pendiente'],
        'solo_consulta' => ['transferencia', 'Pendiente', 'pendiente', 'pendiente'],
        'transferencia_cancelada' => ['transferencia', 'Cancelado', 'pendiente', 'pendiente'],
        'sin_imagen' => ['transferencia', 'Pendiente', 'pendiente', null],
        'sin_envio' => ['transferencia', 'Pendiente', 'pendiente', null],
        'rechazado' => ['transferencia', 'Pendiente', 'pendiente', 'rechazada'],
        'aprobado' => ['transferencia', 'Pendiente', 'pagado', 'aprobada'],
    ];
    $crear = $db->prepare("INSERT INTO pedidos (cliente_nombre,telefono,fecha_retiro,hora_retiro,notas,estado,total,token_confirmacion,metodo_pago,estado_pago,transferencia_enviada_en,pago_confirmado_en)
        VALUES (?, '', ?, '10:00', '', ?, 105.50, ?, ?, ?, ?, ?)");
    $imagen = base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
    );
    $adjuntar = $db->prepare(
        "INSERT INTO comprobantes_transferencia (id_pedido,tipo_mime,contenido,estado_revision) VALUES (?, 'image/png', ?, ?)",
    );
    $ids = [];
    foreach ($casos as $clave => [$metodo, $estado, $pago, $revision]) {
        $crear->execute([
            'Cliente ficticio ' . $clave,
            date('Y-m-d', strtotime('+1 day')),
            $estado,
            bin2hex(random_bytes(32)),
            $metodo,
            $pago,
            $metodo === 'transferencia' && $clave !== 'sin_envio' ? date('Y-m-d H:i:s') : null,
            $pago === 'pagado' ? date('Y-m-d H:i:s') : null,
        ]);
        $ids[$clave] = (int) $db->lastInsertId();
        if ($revision !== null) {
            $adjuntar->execute([$ids[$clave], $imagen, $revision]);
        }
    }
    $leer = static fn(string $clave): array => $db
        ->query('SELECT * FROM pedidos WHERE id_pedido = ' . $ids[$clave])
        ->fetch();
    $entorno->iniciarServidor();
    $solicitar = $entorno->solicitar(...);
    $entrar = static function (string $cedula) use ($solicitar, $password): array {
        [, $html] = $solicitar('/equipo/login');
        comprobar(
            $solicitar('/equipo/login', [
                'csrf' => oculto($html, 'csrf'),
                'cedula' => $cedula,
                'password' => $password,
            ])[0] === 303,
            'Ingresa la cuenta del equipo',
        );
        [, $html] = $solicitar('/equipo/pedidos');
        return [oculto($html, 'csrf'), $html];
    };
    $articulo = static function (string $html, int $id): array {
        $doc = new DOMDocument();
        @$doc->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($doc);
        $nodo = $xpath->query('//article[.//h2[normalize-space()="Pedido #' . $id . '"]]')->item(0);
        return [$xpath, $nodo];
    };
    [$csrf, $html] = $entrar('7800001');
    foreach (
        [
            'efectivo_cancelado',
            'efectivo_pagado',
            'transferencia_cancelada',
            'sin_imagen',
            'sin_envio',
            'rechazado',
            'aprobado',
        ]
        as $clave
    ) {
        [$x, $nodo] = $articulo($html, $ids[$clave]);
        comprobar(
            $x->query('.//form[@data-pago-equipo]', $nodo)->length === 0,
            'No ofrece una acción de pago inválida en ' . $clave,
        );
    }
    [$x, $nodo] = $articulo($html, $ids['efectivo']);
    comprobar(
        str_contains($nodo->textContent, 'Pago pendiente') &&
            str_contains($nodo->textContent, 'total del pedido en efectivo') &&
            $x->query('.//form[@data-pago-equipo]', $nodo)->length === 1,
        'El efectivo distingue el estado e indica cuándo confirmar el cobro',
    );
    [$x, $nodo] = $articulo($html, $ids['aprobar']);
    comprobar(
        str_contains($nodo->textContent, 'Comprobante por revisar') &&
            str_contains($nodo->textContent, 'verificá el ingreso') &&
            $x->query('.//form[@data-pago-equipo]', $nodo)->length === 2,
        'Administración puede aprobar o rechazar un comprobante pendiente',
    );
    $enlace = $x->query('.//a[@target="_blank"]', $nodo)->item(0);
    comprobar(
        str_contains($enlace->getAttribute('aria-label'), 'pestaña nueva'),
        'El enlace al comprobante anticipa la apertura de otra pestaña',
    );
    $urlComprobante = $enlace->getAttribute('href');
    [$codigo, $respuesta] = $solicitar($urlComprobante);
    comprobar(
        $codigo === 200 && str_contains($respuesta, 'Content-Type: image/png'),
        'El equipo puede consultar el comprobante real de prueba',
    );
    $campos = [
        'csrf' => $csrf,
        'accion' => 'registrar_pago_local',
        'id_pedido' => (string) $ids['efectivo'],
    ];
    comprobar(
        $solicitar('/equipo/pedidos', array_replace($campos, ['csrf' => 'incorrecto']))[0] ===
            422 && $leer('efectivo')['estado_pago'] === 'pendiente',
        'CSRF inválido no registra un cobro',
    );
    comprobar(
        $solicitar(
            '/equipo/pedidos',
            array_replace($campos, ['id_pedido' => $ids['efectivo_cancelado']]),
        )[0] === 422,
        'El servidor también rechaza efectivo en un pedido cancelado',
    );
    comprobar(
        $solicitar('/equipo/pedidos', $campos)[0] === 303 &&
            $leer('efectivo')['estado_pago'] === 'pagado',
        'Registra el efectivo mediante POST',
    );
    [, $html] = $solicitar('/equipo/pedidos');
    comprobar(
        str_contains($html, 'Pedido #' . $ids['efectivo'] . ': efectivo recibido registrado.') &&
            str_contains($html, 'Pago realizado'),
        'La confirmación y el estado de pago identifican el resultado',
    );
    $pagoOriginal = $leer('efectivo');
    comprobar(
        $solicitar('/equipo/pedidos', $campos)[0] === 303 && $leer('efectivo') === $pagoOriginal,
        'Reenviar el efectivo no modifica un pago ya confirmado',
    );
    foreach (['aprobar' => 'pagado', 'rechazar' => 'pendiente'] as $decision => $pagoEsperado) {
        $datos = [
            'csrf' => $csrf,
            'accion' => 'revisar_transferencia',
            'decision' => $decision,
            'id_pedido' => (string) $ids[$decision],
        ];
        comprobar(
            $solicitar('/equipo/pedidos', $datos)[0] === 303 &&
                $leer($decision)['estado_pago'] === $pagoEsperado,
            'Guarda la revisión ' . $decision,
        );
        [, $html] = $solicitar('/equipo/pedidos');
        comprobar(
            str_contains(
                $html,
                'Pedido #' .
                    $ids[$decision] .
                    ': ' .
                    ($decision === 'aprobar' ? 'transferencia aprobada' : 'comprobante rechazado'),
            ),
            'La revisión identifica el pedido y la decisión',
        );
        comprobar(
            $solicitar('/equipo/pedidos', $datos)[0] === 422,
            'Una revisión confirmada no se guarda nuevamente',
        );
    }
    $revision = ['csrf' => $csrf, 'accion' => 'revisar_transferencia', 'decision' => 'aprobar'];
    foreach (['transferencia_cancelada', 'sin_imagen'] as $clave) {
        [$codigo, $error] = $solicitar(
            '/equipo/pedidos',
            $revision + ['id_pedido' => $ids[$clave]],
        );
        comprobar(
            $codigo === 422 && str_contains($error, 'Pedido #' . $ids[$clave] . ':'),
            'Informa el pedido de una revisión inválida en ' . $clave,
        );
    }
    $solicitar('/equipo/salir', ['csrf' => $csrf]);
    [$csrf, $html] = $entrar('7800002');
    [$x, $nodo] = $articulo($html, $ids['solo_consulta']);
    comprobar(
        $x->query('.//form[@data-pago-equipo]', $nodo)->length === 0 &&
            $x->query('.//a[@target="_blank"]', $nodo)->length === 1,
        'El empleado conserva la consulta del comprobante sin acciones de revisión',
    );
    comprobar(
        $solicitar(
            '/equipo/pedidos',
            array_replace($revision, [
                'csrf' => $csrf,
                'id_pedido' => $ids['solo_consulta'],
                'rol' => 'dueno',
            ]),
        )[0] === 403,
        'El rol enviado por el navegador no permite al empleado revisar pagos',
    );
    comprobar(
        $solicitar(
            '/equipo/pedidos',
            array_replace($campos, ['csrf' => $csrf, 'id_pedido' => $ids['efectivo_empleado']]),
        )[0] === 303 && $leer('efectivo_empleado')['estado_pago'] === 'pagado',
        'El empleado conserva el registro de efectivo permitido',
    );
    $solicitar('/equipo/salir', ['csrf' => $csrf]);
    [$csrf] = $entrar('7800003');
    comprobar(
        $solicitar(
            '/equipo/pedidos',
            array_replace($revision, ['csrf' => $csrf, 'id_pedido' => $ids['solo_consulta']]),
        )[0] === 303 && $leer('solo_consulta')['estado_pago'] === 'pagado',
        'El dueño conserva la aprobación de transferencias',
    );
    comprobar(
        $solicitar('/equipo/pedidos', array_replace($campos, ['csrf' => $csrf]))[0] === 303 &&
            $leer('efectivo') === $pagoOriginal,
        'El reenvío del dueño conserva el responsable y la fecha del cobro original',
    );
    comprobar(
        !preg_match('/Fatal error:|Warning:|Notice:|Deprecated:/', $entorno->errores()),
        'El recorrido no genera errores PHP',
    );
    echo "Correcto: $comprobaciones comprobaciones de pagos de HU05.\n";
} catch (Throwable $error) {
    $correcto = false;
    fwrite(STDERR, $error->getMessage() . "\n" . $error->getTraceAsString() . "\n");
    fwrite(STDERR, $entorno?->errores() ?? '');
} finally {
    $entorno?->cerrar();
}
exit($correcto ? 0 : 1);
