<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/soporte.php';

use App\Models\Producto;
use App\Models\UsuarioEquipo;
use App\Services\GestionProducto;

$entorno = null;
$comprobaciones = 0;
$correcto = true;

try {
    $entorno = new EntornoPruebas('hu06');
    $db = $entorno->db;
    $productos = new Producto($db);
    $id = (new GestionProducto($db))->guardar([
        'nombre' => 'Producto de prueba <HU06>',
        'descripcion' => 'Datos que deben conservarse',
        'precio' => '24.17',
        'categoria' => 'Panes',
        'presentacion' => 'Unidad',
        'ingredientes' => 'Harina y agua',
        'alergenos' => 'Gluten',
        'activo' => '1',
    ]);
    $original = $productos->buscarGestion($id);
    $version = Producto::version($original);
    $productos->cambiarEstado($id, '0', $version);
    $inactivo = $productos->buscarGestion($id);
    $sinEstado = static function (array $producto): array {
        unset($producto['activo']);
        return $producto;
    };
    comprobar(
        (int) $inactivo['activo'] === 0 && $sinEstado($inactivo) === $sinEstado($original),
        'Desactivar conserva los otros datos, la imagen y la identidad del producto',
    );
    comprobar(
        $productos->buscarVarios([$id]) === [] &&
            !in_array($id, array_column($productos->listar(), 'id_producto'), true),
        'El producto desactivado deja de estar disponible para catálogo y carrito',
    );
    rechaza(
        fn() => $productos->cambiarEstado($id, '1', $version),
        'Un formulario anterior no sobrescribe el estado actualizado',
    );
    foreach (['2', '', 'true'] as $estado) {
        rechaza(
            fn() => $productos->cambiarEstado($id, $estado, Producto::version($inactivo)),
            'Rechaza estados fuera de activo e inactivo',
        );
    }
    $productos->cambiarEstado($id, '1', Producto::version($inactivo));

    $password = 'EstadoProductoPrueba06!';
    foreach (
        ['empleado' => '6000011', 'administrador' => '6000012', 'dueno' => '6000013']
        as $rol => $cedula
    ) {
        $cuenta = (new UsuarioEquipo($db))->solicitar(
            $rol,
            $cedula,
            $rol . '@example.test',
            $password,
            $password,
        );
        $consulta = $db->prepare(
            "UPDATE usuarios_equipo SET estado = 'aprobado', rol = ? WHERE id_usuario = ?",
        );
        $consulta->execute([$rol, $cuenta['id_usuario']]);
    }
    $entorno->iniciarServidor();
    $solicitar = $entorno->solicitar(...);
    $codigo = 'PRD-' . str_pad((string) $id, 3, '0', STR_PAD_LEFT);
    $filtros = http_build_query(['q' => $codigo, 'estado' => '', 'pagina' => 1]);
    $ruta = '/equipo/productos/estado?id=' . $id . '&' . $filtros;
    $listado = '/equipo/productos?' . $filtros;
    $campos = [
        'activo' => '0',
        'version' => Producto::version($productos->buscarGestion($id)),
    ];
    comprobar($solicitar($ruta)[0] === 405, 'La lectura no modifica el estado');
    comprobar($solicitar($ruta, $campos)[0] === 303, 'El visitante debe iniciar sesión');
    $entrar = static function (string $cedula) use ($solicitar, $password): void {
        $csrf = oculto($solicitar('/equipo/login')[1], 'csrf');
        comprobar(
            $solicitar('/equipo/login', [
                'csrf' => $csrf,
                'cedula' => $cedula,
                'password' => $password,
            ])[0] === 303,
            'Inicia la cuenta de prueba del equipo',
        );
    };
    $salir = static function () use ($solicitar, $entorno): void {
        $solicitar('/equipo/salir', ['csrf' => $entorno->sesion()['csrf']]);
    };
    $entrar('6000011');
    comprobar(
        $solicitar($ruta, $campos + [
            'csrf' => $entorno->sesion()['csrf'],
            'rol' => 'dueno',
        ])[0] === 403 &&
            (int) $productos->buscarGestion($id)['activo'] === 1,
        'Un empleado no cambia el estado aunque envíe un rol falso',
    );
    $salir();
    $entrar('6000012');
    [, $lista] = $solicitar($listado);
    comprobar(
        str_contains($lista, '>Estado</summary>') &&
            str_contains($lista, 'Producto de prueba &lt;HU06&gt;'),
        'El listado ofrece Estado y escapa el nombre del producto',
    );
    $campos['csrf'] = oculto($lista, 'csrf');
    $campos['version'] = oculto($lista, 'version');
    comprobar(
        $solicitar($ruta, array_replace($campos, ['csrf' => 'incorrecto']))[0] === 422 &&
            (int) $productos->buscarGestion($id)['activo'] === 1,
        'CSRF inválido no modifica el producto',
    );
    foreach (['2', ['1']] as $valor) {
        comprobar(
            $solicitar($ruta, array_replace($campos, ['activo' => $valor]))[0] === 422 &&
                (int) $productos->buscarGestion($id)['activo'] === 1,
            'Un estado inválido o malformado no modifica el producto',
        );
    }
    [$estado, $respuesta] = $solicitar($ruta, $campos + [
        'nombre' => 'No modificar',
        'precio' => '1',
    ]);
    comprobar(
        $estado === 303 && str_contains($respuesta, 'Location: ' . $listado),
        'El administrador cambia el estado y regresa con búsqueda, filtro y página',
    );
    comprobar(
        $solicitar('/producto?id=' . $id)[0] === 404 &&
            $sinEstado($productos->buscarGestion($id)) === $sinEstado($original),
        'El cambio se refleja en la tienda e ignora campos ajenos al estado',
    );
    comprobar(
        $solicitar($ruta, $campos)[0] === 422 && (int) $productos->buscarGestion($id)['activo'] === 0,
        'Reenviar una versión anterior no alterna de nuevo el estado',
    );
    [, $lista] = $solicitar($listado);
    comprobar(
        str_contains($lista, 'Producto desactivado correctamente'),
        'El listado confirma el cambio',
    );
    $salir();
    $entrar('6000013');
    [, $lista] = $solicitar($listado);
    $campos = [
        'csrf' => oculto($lista, 'csrf'),
        'version' => oculto($lista, 'version'),
        'activo' => '1',
    ];
    comprobar(
        $solicitar($ruta, $campos)[0] === 303 && $solicitar('/producto?id=' . $id)[0] === 200,
        'El dueño reactiva el producto desde el listado',
    );
    $campos['version'] = Producto::version($productos->buscarGestion($id));
    $campos['activo'] = '0';
    $db->exec("CREATE TRIGGER fallo_estado_hu06 BEFORE UPDATE ON productos
        FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fallo interno de prueba'");
    [$estado, $respuesta] = $solicitar($ruta, $campos);
    $db->exec('DROP TRIGGER fallo_estado_hu06');
    comprobar(
        $estado === 503 && !str_contains($respuesta, 'SQLSTATE') &&
            !str_contains($respuesta, 'Fallo interno de prueba') &&
            (int) $productos->buscarGestion($id)['activo'] === 1,
        'Un fallo de la base conserva el estado e informa un error sin detalles internos',
    );
    $productos->archivar($id, Producto::version($productos->buscarGestion($id)));
    comprobar(
        $solicitar($ruta, $campos)[0] === 404,
        'El cambio de estado no recupera productos eliminados',
    );
    comprobar(
        !preg_match('/Fatal error:|Warning:|Notice:|Deprecated:/', $entorno->errores()),
        'El recorrido no genera errores PHP',
    );
    echo "Correcto: cambio de estado, permisos, CSRF, concurrencia y conservación de datos.\n";
} catch (Throwable $error) {
    $correcto = false;
    fwrite(STDERR, $error->getMessage() . "\n" . $error->getTraceAsString() . "\n");
    fwrite(STDERR, $entorno?->errores() ?? '');
} finally {
    $entorno?->cerrar();
}
exit($correcto ? 0 : 1);
