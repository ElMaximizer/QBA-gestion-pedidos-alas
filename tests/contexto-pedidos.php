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
    $entorno = new EntornoPruebas('hu04');
    $db = $entorno->db;
    $db->exec(file_get_contents(dirname(__DIR__) . '/database/comprobantes-transferencia.sql'));
    $password = 'ContextoPedidoPrueba04!';
    $cuenta = (new UsuarioEquipo($db))->solicitar(
        'Administración de prueba',
        '7700001',
        'contexto@example.test',
        $password,
        $password,
    );
    $db->prepare(
        "UPDATE usuarios_equipo SET estado = 'aprobado', rol = 'administrador' WHERE id_usuario = ?",
    )->execute([$cuenta['id_usuario']]);
    $entorno->iniciarServidor();
    $solicitar = $entorno->solicitar(...);
    [, $html] = $solicitar('/equipo/login');
    comprobar(
        $solicitar('/equipo/login', [
            'csrf' => oculto($html, 'csrf'),
            'cedula' => '7700001',
            'password' => $password,
        ])[0] === 303,
        'Ingresa el administrador de prueba',
    );
    [, $html] = $solicitar('/equipo/pedidos');
    comprobar(
        str_contains($html, 'Todavía no hay pedidos'),
        'Distingue una lista vacía de una búsqueda sin resultados',
    );
    $crear = $db->prepare("INSERT INTO pedidos (cliente_nombre,telefono,fecha_retiro,hora_retiro,notas,estado,total,token_confirmacion)
        VALUES ('Cliente HU04','',?,'10:00','','Pendiente',100,?)");
    for ($indice = 0; $indice < 45; $indice++) {
        $crear->execute([date('Y-m-d', strtotime('+1 day')), bin2hex(random_bytes(32))]);
    }
    $filtros = [
        'q' => 'Cliente HU04',
        'estado' => 'Pendiente',
        'fecha' => 'proximos',
        'vista' => 'lista',
        'pagina' => '2',
    ];
    $ruta = '/equipo/pedidos?' . http_build_query($filtros);
    $documento = static function (string $html): DOMXPath {
        $doc = new DOMDocument();
        @$doc->loadHTML('<?xml encoding="UTF-8">' . $html);
        return new DOMXPath($doc);
    };
    $parametros = static function (string $url): array {
        parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $datos);
        return $datos;
    };
    [$estado, $html] = $solicitar($ruta);
    $xpath = $documento($html);
    comprobar(
        $estado === 200 &&
            str_contains($html, 'Página 2 de 3') &&
            str_contains($html, 'Filtros aplicados:'),
        'Muestra filtros activos y la página correspondiente',
    );
    comprobar(
        $xpath->query('//form[@method="get"]/input[@name="vista" and @value="lista"]')->length ===
            1,
        'Aplicar filtros conserva la vista y reinicia la página',
    );
    foreach ($xpath->query('//nav[@aria-label="Modo de visualización de pedidos"]//a') as $enlace) {
        $esperados = array_replace($filtros, [
            'vista' => trim($enlace->textContent) === 'Lista' ? 'lista' : 'cuadricula',
        ]);
        comprobar(
            $parametros($enlace->getAttribute('href')) == $esperados,
            'Cambiar de vista conserva la página y los filtros',
        );
    }
    foreach ($xpath->query('//nav[@aria-label="Páginas de pedidos"]//a') as $enlace) {
        $esperados = array_replace($filtros, [
            'pagina' => trim($enlace->textContent) === 'Anterior' ? '1' : '3',
        ]);
        comprobar(
            $parametros($enlace->getAttribute('href')) == $esperados,
            'La paginación conserva la vista y los filtros',
        );
    }
    foreach ($xpath->query('//main//form[@method="post"]') as $formulario) {
        comprobar(
            $parametros($formulario->getAttribute('action')) == $filtros,
            'La acción del pedido conserva el contexto del listado',
        );
    }
    $detalle = $xpath->query('//a[normalize-space()="Ver detalle"]')->item(0)->getAttribute('href');
    $id = (int) $parametros($detalle)['id'];
    [, $htmlDetalle] = $solicitar($detalle);
    $volver = $documento($htmlDetalle)->query('//a[normalize-space()="Volver a pedidos"]')->item(0);
    comprobar(
        $parametros($volver->getAttribute('href')) == $filtros &&
            str_contains($htmlDetalle, 'No informado') &&
            !str_contains($htmlDetalle, 'href="tel:"'),
        'El detalle vuelve al listado original y no genera enlaces telefónicos vacíos',
    );
    [$estado, $htmlError] = $solicitar(
        '/equipo/pedidos/detalle?' . http_build_query(['id' => 999999] + $filtros),
    );
    comprobar(
        $estado === 404 &&
            $parametros(
                $documento($htmlError)
                    ->query('//a[normalize-space()="Volver a pedidos"]')
                    ->item(0)
                    ->getAttribute('href'),
            ) == $filtros,
        'Un detalle inexistente también conserva el enlace de regreso',
    );
    $campos = [
        'csrf' => oculto($html, 'csrf'),
        'accion' => 'actualizar_estado',
        'id_pedido' => (string) $id,
        'estado_nuevo' => 'En preparación',
    ];
    [$estado, $respuesta] = $solicitar($ruta, $campos);
    preg_match('/^Location: (.+)\r?$/mi', $respuesta, $coincidencia);
    comprobar(
        $estado === 303 && $parametros(trim($coincidencia[1])) == $filtros,
        'Guardar un estado redirige al mismo listado',
    );
    $cancelar = array_replace($campos, ['accion' => 'cancelar_pedido']);
    [$estado, $confirmacion] = $solicitar($ruta, $cancelar);
    $xConfirmacion = $documento($confirmacion);
    comprobar(
        $estado === 200 &&
            $parametros($xConfirmacion->query('//main//form')->item(0)->getAttribute('action')) ==
                $filtros &&
            $parametros(
                $xConfirmacion
                    ->query('//main//a[normalize-space()="Volver"]')
                    ->item(0)
                    ->getAttribute('href'),
            ) == $filtros,
        'La confirmación PHP mantiene el contexto al confirmar o volver',
    );
    [$estado, $respuesta] = $solicitar($ruta, $cancelar + ['confirmar_cancelacion' => '1']);
    preg_match('/^Location: (.+)\r?$/mi', $respuesta, $coincidencia);
    comprobar(
        $estado === 303 && $parametros(trim($coincidencia[1])) == $filtros,
        'Cancelar vuelve a los filtros originales',
    );
    $otroId = $id - 1;
    $campos['id_pedido'] = (string) $otroId;
    [$estado, $respuesta] = $solicitar(
        $ruta . '&volver=https%3A%2F%2Fexample.test&rol=dueno',
        $campos,
    );
    preg_match('/^Location: (.+)\r?$/mi', $respuesta, $coincidencia);
    comprobar(
        $estado === 303 &&
            str_starts_with(trim($coincidencia[1]), '/equipo/pedidos?') &&
            $parametros(trim($coincidencia[1])) == $filtros,
        'Solo admite parámetros conocidos y un destino interno',
    );
    $campos['id_pedido'] = (string) ($otroId - 1);
    comprobar(
        $solicitar('/equipo/pedidos?q[]=incorrecto', $campos)[0] === 422 &&
            $db
                ->query('SELECT estado FROM pedidos WHERE id_pedido = ' . ($otroId - 1))
                ->fetchColumn() === 'Pendiente',
        'Un contexto malformado se rechaza antes de modificar pedidos',
    );
    [, $sinResultados] = $solicitar(
        '/equipo/pedidos?q=' . urlencode('<script>HU04</script>') . '&vista=lista',
    );
    comprobar(
        str_contains($sinResultados, 'No encontramos pedidos con estos filtros') &&
            str_contains($sinResultados, '&lt;script&gt;HU04&lt;/script&gt;') &&
            !str_contains($sinResultados, '<script>HU04</script>'),
        'La búsqueda sin resultados muestra los filtros escapados',
    );
    $limpiar = $documento($sinResultados)
        ->query('//a[normalize-space()="Limpiar filtros"]')
        ->item(0);
    comprobar(
        $parametros($limpiar->getAttribute('href')) === ['vista' => 'lista'],
        'Limpiar filtros conserva el modo de vista',
    );
    $db->exec("UPDATE pedidos SET estado = 'Entregado' WHERE id_pedido < 30");
    [, $html] = $solicitar(
        '/equipo/pedidos?' . http_build_query(array_replace($filtros, ['pagina' => '99'])),
    );
    comprobar(
        $documento($html)->query('//main//article')->length === 16 &&
            !isset(
                $parametros(
                    $documento($html)
                        ->query('//main//form[@method="post"]')
                        ->item(0)
                        ->getAttribute('action'),
                )['pagina'],
            ),
        'Si disminuyen los resultados, muestra la página disponible en lugar de una lista vacía',
    );
    comprobar(
        !preg_match('/Fatal error:|Warning:|Notice:|Deprecated:/', $entorno->errores()),
        'El recorrido no genera errores PHP',
    );
    echo "Correcto: $comprobaciones comprobaciones de contexto de HU04.\n";
} catch (Throwable $error) {
    $correcto = false;
    fwrite(STDERR, $error->getMessage() . "\n" . $error->getTraceAsString() . "\n");
    fwrite(STDERR, $entorno?->errores() ?? '');
} finally {
    $entorno?->cerrar();
}
exit($correcto ? 0 : 1);
