<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/soporte.php';

use App\Models\Pedido;
use App\Models\UsuarioEquipo;

$entorno = null;
$comprobaciones = 0;
$correcto = true;
try {
    $entorno = new EntornoPruebas('hu01');
    $db = $entorno->db;
    $db->exec(file_get_contents(dirname(__DIR__) . '/database/comprobantes-transferencia.sql'));
    $db->exec('UPDATE productos SET precio = 123.45 WHERE id_producto = 1');
    $db->exec('UPDATE productos SET precio = 65.10 WHERE id_producto = 2');
    $password = 'ValidacionPedidoPrueba01!';
    $cuenta = (new UsuarioEquipo($db))->solicitar(
        'Administración de prueba',
        '7300001',
        'validacion@example.test',
        $password,
        $password,
    );
    $db->prepare(
        "UPDATE usuarios_equipo SET rol = 'administrador', estado = 'aprobado' WHERE id_usuario = ?",
    )->execute([$cuenta['id_usuario']]);
    $entorno->iniciarServidor();
    $solicitar = $entorno->solicitar(...);
    [, $html] = $solicitar('/equipo/login');
    comprobar(
        $solicitar('/equipo/login', [
            'csrf' => oculto($html, 'csrf'),
            'cedula' => '7300001',
            'password' => $password,
        ])[0] === 303,
        'Ingresa el administrador de prueba',
    );
    [, $html] = $solicitar('/equipo/pedidos/registrar');
    $campos = [
        'csrf' => oculto($html, 'csrf'),
        'pedido_token' => oculto($html, 'pedido_token'),
        'accion' => 'registrar',
        'cliente_nombre' => 'Cliente de prueba',
        'telefono' => '099123456',
        'fecha_retiro' => date('Y-m-d', strtotime('+2 days')),
        'hora_retiro' => '10:00',
        'modalidad_entrega' => 'retiro',
        'direccion_entrega' => '',
        'metodo_pago' => 'local',
        'notas' => '<Indicaciones de prueba>',
        'lineas' => [['id_producto' => '1', 'cantidad' => '2']],
    ];
    comprobar(
        str_contains($html, '$ 123,45</option>') &&
            str_contains($html, '$ 65,10</option>') &&
            str_contains($html, '$ 1.200</option>'),
        'El selector muestra precios exactos y conserva los importes enteros sin decimales',
    );
    $camposImportes = array_replace($campos, [
        'telefono' => 'inválido',
        'lineas' => [
            ['id_producto' => '1', 'cantidad' => '2'],
            ['id_producto' => '2', 'cantidad' => '3'],
        ],
    ]);
    [$estado, $htmlImportes] = $solicitar('/equipo/pedidos/registrar', $camposImportes);
    $documentoImportes = new DOMDocument();
    @$documentoImportes->loadHTML('<?xml encoding="UTF-8">' . $htmlImportes);
    $xpathImportes = new DOMXPath($documentoImportes);
    $subtotales = $xpathImportes->query('//*[@data-pedido-resumen]/li/strong');
    comprobar(
        $estado === 422 && $subtotales->length === 2 &&
            trim($subtotales->item(0)->textContent) === '$ 246,90' &&
            trim($subtotales->item(1)->textContent) === '$ 195,30' &&
            trim($xpathImportes->query('//*[@data-pedido-total]')->item(0)->textContent) === '$ 442,20',
        'Un error conserva varios productos y presenta subtotales y total exactos sin JavaScript',
    );
    [$estado, $htmlImportes] = $solicitar(
        '/equipo/pedidos/registrar',
        $camposImportes + ['quitar_linea' => '1'],
    );
    @$documentoImportes->loadHTML('<?xml encoding="UTF-8">' . $htmlImportes);
    $xpathImportes = new DOMXPath($documentoImportes);
    comprobar(
        $estado === 200 &&
            $xpathImportes->query('//*[@data-pedido-resumen]/li')->length === 1 &&
            trim($xpathImportes->query('//*[@data-pedido-total]')->item(0)->textContent) === '$ 246,90',
        'Quitar una línea sin JavaScript recalcula el total sin redondear centavos',
    );
    $comprobarCampos = static function (string $html, array $campos) use (&$comprobaciones): void {
        $documento = new DOMDocument();
        @$documento->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($documento);
        foreach ($campos as $campo) {
            $control = $xpath->query('//*[@data-pedido-campo="' . $campo . '"]')->item(0);
            comprobar(
                $control instanceof DOMElement && $control->getAttribute('aria-invalid') === 'true',
                'Marca el campo inválido ' . $campo,
            );
            $id = $control->getAttribute('id');
            $mensaje = $xpath->query('//*[@id="' . $id . '-error"]')->item(0);
            comprobar(
                $mensaje instanceof DOMElement &&
                    !$mensaje->hasAttribute('hidden') &&
                    trim($mensaje->textContent) !== '' &&
                    in_array(
                        $id . '-error',
                        explode(' ', $control->getAttribute('aria-describedby')),
                        true,
                    ) &&
                    $xpath->query('//a[@href="#' . $id . '"]')->length === 1,
                'Asocia el mensaje y un enlace del resumen con ' . $campo,
            );
        }
    };
    [$estado, $html] = $solicitar(
        '/equipo/pedidos/registrar',
        array_replace($campos, [
            'cliente_nombre' => '',
            'telefono' => 'inválido',
            'modalidad_entrega' => 'domicilio',
            'lineas' => [['id_producto' => '', 'cantidad' => '0']],
        ]),
    );
    comprobar($estado === 422, 'Presenta los errores simultáneos en el formulario');
    $comprobarCampos($html, [
        'cliente_nombre',
        'telefono',
        'direccion_entrega',
        'lineas-0-id_producto',
        'lineas-0-cantidad',
    ]);
    comprobar(
        str_contains($html, '&lt;Indicaciones de prueba&gt;') &&
            str_contains($html, 'value="inválido"') &&
            str_contains($html, 'value="' . $campos['pedido_token'] . '"'),
        'Conserva notas escapadas, teléfono y token para corregir el mismo pedido',
    );
    [$estado, $html] = $solicitar(
        '/equipo/pedidos/registrar',
        array_replace($campos, [
            'fecha_retiro' => '2000-01-01',
            'lineas' => [$campos['lineas'][0], $campos['lineas'][0]],
        ]),
    );
    comprobar($estado === 422, 'Rechaza fecha pasada y productos duplicados en el servidor');
    $comprobarCampos($html, [
        'fecha_retiro',
        'hora_retiro',
        'lineas-0-id_producto',
        'lineas-1-id_producto',
    ]);
    [$estado, $html] = $solicitar(
        '/equipo/pedidos/registrar',
        array_replace($campos, ['metodo_pago' => 'transferencia']),
    );
    comprobar($estado === 422, 'Exige el comprobante de una transferencia');
    $comprobarCampos($html, ['comprobante']);
    [$estado, $html] = $solicitar(
        '/equipo/pedidos/registrar',
        array_replace($campos, ['metodo_pago' => 'transferencia', 'telefono' => 'abc']),
    );
    $comprobarCampos($html, ['telefono', 'comprobante']);
    comprobar(
        str_contains($html, 'Volvé a seleccionar el comprobante'),
        'Explica que se debe volver a seleccionar la imagen después de un error',
    );
    comprobar(
        (int) $db->query('SELECT COUNT(*) FROM pedidos')->fetchColumn() === 0,
        'Las correcciones no crean pedidos parciales',
    );
    $errores = Pedido::erroresValidacion(
        array_replace($campos, [
            'cliente_nombre' => ['array'],
            'telefono' => ['array'],
            'fecha_retiro' => ['array'],
        ]),
    );
    comprobar(
        isset($errores['cliente_nombre'], $errores['telefono'], $errores['fecha_retiro']),
        'El validador compartido rechaza estructuras inesperadas sin advertencias PHP',
    );
    comprobar(
        $solicitar(
            '/equipo/pedidos/registrar',
            array_replace($campos, ['csrf' => 'incorrecto']),
        )[0] === 422,
        'Mantiene la protección CSRF',
    );
    comprobar(
        $solicitar('/equipo/pedidos/registrar', $campos)[0] === 303,
        'Permite corregir y guardar con el mismo token',
    );
    comprobar(
        $db->query('SELECT total FROM pedidos')->fetchColumn() === '246.90',
        'Guarda el mismo total exacto que muestra el formulario',
    );
    comprobar(
        $solicitar('/equipo/pedidos/registrar', $campos)[0] === 303 &&
            (int) $db->query('SELECT COUNT(*) FROM pedidos')->fetchColumn() === 1,
        'Reenviar la confirmación no duplica el pedido',
    );
    comprobar(
        !preg_match('/Fatal error:|Warning:|Notice:|Deprecated:/', $entorno->errores()),
        'El recorrido no genera errores PHP',
    );
    echo "Correcto: $comprobaciones comprobaciones de validación de HU01.\n";
} catch (Throwable $error) {
    $correcto = false;
    fwrite(STDERR, $error->getMessage() . "\n" . $error->getTraceAsString() . "\n");
    fwrite(STDERR, $entorno?->errores() ?? '');
} finally {
    $entorno?->cerrar();
}
exit($correcto ? 0 : 1);
