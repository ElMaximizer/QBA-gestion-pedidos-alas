<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/soporte.php';

use App\Models\Carrito;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\UsuarioEquipo;
use App\Services\GestionProducto;
use App\Services\ImagenProducto;

$entorno = null;
$comprobaciones = 0;
$correcto = true;
$temporales = [];
$imagenes = [];

try {
    $entorno = new EntornoPruebas('hu06');
    $db = $entorno->db;
    $productos = new Producto($db);
    $gestion = new GestionProducto($db);
    $db->beginTransaction();
    $db->exec('UPDATE productos SET activo = 0');
    comprobar($productos->resumenActivos() === ['productos' => 0, 'categorias' => 0], 'El resumen devuelve cero si no hay productos activos');
    $db->exec("UPDATE productos SET activo = 1, categoria = 'Panes' WHERE id_producto IN (1, 2)");
    $db->exec('UPDATE productos SET activo = 1, eliminado_en = UTC_TIMESTAMP() WHERE id_producto = 3');
    comprobar($productos->resumenActivos() === ['productos' => 2, 'categorias' => 1], 'Cuenta las categorías distintas y excluye productos inactivos o eliminados');
    $db->rollBack();
    $datos = ['nombre' => 'Pan de prueba', 'descripcion' => 'Descripción', 'precio' => '24,17', 'categoria' => 'Panes',
        'presentacion' => 'Unidad', 'ingredientes' => 'Harina y agua', 'alergenos' => 'Gluten', 'activo' => '1'];
    $id = $gestion->guardar($datos + ['destacado' => 1, 'eliminado_en' => '2000-01-01', 'imagen' => '../../config/correo.local.php']);
    $producto = $productos->buscarGestion($id);
    comprobar($producto['precio'] === 2417 && (int) $producto['destacado'] === 0 && $producto['imagen_guardada'] === '', 'Guarda centavos exactos e ignora campos que no son editables');
    foreach (['0', '-1', '1.001', '1e3', '100000000', 'NaN', ''] as $precio) {
        rechaza(fn () => $gestion->guardar(array_replace($datos, ['precio' => $precio])), 'Rechaza el precio inválido ' . $precio);
    }
    comprobar(Producto::validar(array_replace($datos, ['precio' => '99999999.99']))['precio'] === '99999999.99'
        && Producto::validar(array_replace($datos, ['precio' => '0.01']))['precio'] === '0.01', 'Admite los límites de DECIMAL sin usar flotantes');
    foreach (['nombre' => '', 'presentacion' => '', 'categoria' => 'Otra', 'activo' => '2', 'descripcion' => str_repeat('a', 281),
        'ingredientes' => str_repeat('a', 5001), 'alergenos' => str_repeat('a', 501), 'precio' => ['x']] as $campo => $valor) {
        rechaza(fn () => $gestion->guardar(array_replace($datos, [$campo => $valor])), 'Valida el campo ' . $campo);
    }
    $version = Producto::version($producto);
    $gestion->guardar(array_replace($datos, ['nombre' => 'Nombre nuevo', 'categoria' => 'Especiales']), null, $id, $version);
    comprobar($productos->buscarGestion($id)['imagen'] === 'torta.svg', 'Sin fotografía utiliza la ilustración de la categoría');
    rechaza(fn () => $gestion->guardar($datos, null, $id, $version), 'Una edición anterior no sobrescribe cambios recientes');
    rechaza(fn () => $gestion->eliminar($id, $version), 'Una confirmación anterior no elimina una versión modificada');
    comprobar($productos->buscarGestion($id)['nombre'] === 'Nombre nuevo', 'Conserva el cambio cuando otro formulario queda desactualizado');
    rechaza(fn () => (new ImagenProducto())->guardar(['error' => UPLOAD_ERR_OK, 'tmp_name' => __FILE__]), 'No mueve archivos que no llegaron mediante una subida HTTP');

    // Un pedido real de la base temporal debe conservar sus líneas después de editar y eliminar.
    $_SESSION = [];
    $carrito = new Carrito($productos);
    $carrito->actualizar('agregar', '1', '2');
    $firma = Carrito::firma($carrito->articulos());
    $datosPedido = ['cliente_nombre' => 'Prueba histórica', 'telefono' => '099123456',
        'fecha_retiro' => date('Y-m-d', strtotime('+2 days')), 'hora_retiro' => '10:00', 'notas' => ''];
    $pedidos = new Pedido($db);
    $pedido = $pedidos->crear($datosPedido, Carrito::cantidades(), $firma, bin2hex(random_bytes(32)));
    $historico = $db->query('SELECT * FROM pedido_detalles WHERE id_pedido = ' . $pedido)->fetchAll();
    $baseProducto = $productos->buscarGestion(1);
    $gestion->guardar(array_replace($datos, ['nombre' => 'Pan actualizado', 'precio' => '97.13']), null, 1, Producto::version($baseProducto));
    rechaza(fn () => $pedidos->crear($datosPedido, Carrito::cantidades(), $firma, bin2hex(random_bytes(32))), 'Actualizar el precio invalida una confirmación anterior del carrito');
    $gestion->eliminar(1, Producto::version($productos->buscarGestion(1)));
    comprobar($db->query('SELECT * FROM pedido_detalles WHERE id_pedido = ' . $pedido)->fetchAll() === $historico, 'Editar y eliminar conserva el nombre, precio y referencia de los pedidos anteriores');
    comprobar($productos->buscarGestion(1) === null && !$productos->buscarVarios([1]), 'La baja deja de estar disponible para administrar y comprar');
    comprobar((int) $db->query('SELECT COUNT(*) FROM productos WHERE id_producto = 1 AND eliminado_en IS NOT NULL AND activo = 0')->fetchColumn() === 1, 'La eliminación conserva la fila histórica');

    for ($i = 0; $i < 26; $i++) {
        $gestion->guardar(array_replace($datos, ['nombre' => 'Lote de prueba ' . $i, 'activo' => '0']));
    }
    $pagina1 = $productos->listarGestion('Lote de prueba', '0');
    $pagina2 = $productos->listarGestion('Lote de prueba', '0', 2);
    comprobar($pagina1['total'] === 26 && count($pagina1['productos']) === 25 && count($pagina2['productos']) === 1, 'Pagina el listado y conserva la cantidad total');
    comprobar(!array_intersect(array_column($pagina1['productos'], 'id_producto'), array_column($pagina2['productos'], 'id_producto')), 'No repite productos entre páginas');
    comprobar($productos->listarGestion('PRD-' . str_pad((string) $id, 3, '0', STR_PAD_LEFT))['total'] === 1, 'La búsqueda admite el código del producto');
    rechaza(fn () => $productos->listarGestion('', 'otro'), 'El filtro de estado debe ser válido');

    $password = 'ProductoPrueba06!';
    $usuarios = new UsuarioEquipo($db);
    $cuentas = [];
    foreach (['empleado' => '6000001', 'administrador' => '6000002', 'dueno' => '6000003'] as $rol => $cedula) {
        $cuentas[$rol] = $usuarios->solicitar($rol, $cedula, $rol . '@example.test', $password, $password);
        $consulta = $db->prepare("UPDATE usuarios_equipo SET estado = 'aprobado', rol = ? WHERE id_usuario = ?");
        $consulta->execute([$rol, $cuentas[$rol]['id_usuario']]);
    }
    $entorno->iniciarServidor();
    $solicitar = $entorno->solicitar(...);
    $entrar = static function (string $cedula) use ($solicitar, $password): void {
        $csrf = oculto($solicitar('/equipo/login')[1], 'csrf');
        comprobar($solicitar('/equipo/login', ['csrf' => $csrf, 'cedula' => $cedula, 'password' => $password])[0] === 303, 'Inicia la sesión de prueba del equipo');
    };
    $salir = static function () use ($entorno, $solicitar): void {
        $solicitar('/equipo/salir', ['csrf' => $entorno->sesion()['csrf']]);
    };
    $rutas = ['/equipo/productos', '/equipo/productos/agregar', '/equipo/productos/editar?id=' . $id, '/equipo/productos/eliminar?id=' . $id];
    foreach ($rutas as $ruta) {
        comprobar($solicitar($ruta)[0] === 303, 'El visitante no puede abrir ' . $ruta);
    }
    $entrar('6000001');
    foreach ($rutas as $ruta) {
        comprobar($solicitar($ruta)[0] === 403, 'El empleado no puede abrir la gestión: ' . $ruta);
    }
    $csrf = $entorno->sesion()['csrf'];
    $cantidadAntes = (int) $db->query('SELECT COUNT(*) FROM productos')->fetchColumn();
    comprobar($solicitar('/equipo/productos/agregar', $datos + ['csrf' => $csrf, 'rol' => 'dueno'])[0] === 403
        && (int) $db->query('SELECT COUNT(*) FROM productos')->fetchColumn() === $cantidadAntes, 'Cambiar el rol en el formulario no permite crear productos');
    $salir();
    $entrar('6000002');
    [, $panel] = $solicitar('/equipo/panel');
    comprobar(str_contains($panel, 'href="/equipo/productos"') && str_contains($panel, 'href="/equipo/productos/agregar"'), 'El panel habilita los accesos de HU06 para el administrador');
    $resumenPanel = static function () use ($solicitar): array {
        $documento = new DOMDocument();
        @$documento->loadHTML('<?xml encoding="UTF-8">' . $solicitar('/equipo/panel')[1]);
        $xpath = new DOMXPath($documento);
        $tarjeta = '//article[p[normalize-space()="Productos activos"]]';
        return [trim($xpath->evaluate('string(' . $tarjeta . '/strong)')),
            trim($xpath->evaluate('string(' . $tarjeta . '/p[contains(@class,"panel-estadistica__detalle")])'))];
    };
    comprobar($resumenPanel() === ['3', '3 categorías disponibles'], 'El panel muestra el resumen real del catálogo activo');
    [, $listaActivos] = $solicitar('/equipo/productos');
    comprobar(str_contains($listaActivos, 'Nombre nuevo') && !str_contains($listaActivos, 'Lote de prueba'), 'El listado abre con los activos y oculta los inactivos');
    comprobar(str_contains($solicitar('/equipo/productos?estado=')[1], 'Lote de prueba'), 'Todos los estados permite consultar también los productos inactivos');
    [, $formulario] = $solicitar('/equipo/productos/agregar');
    $csrf = oculto($formulario, 'csrf');
    $campos = $datos + ['csrf' => $csrf, 'version' => '', 'nombre' => 'ignorado'];
    comprobar($solicitar('/equipo/productos', [])[0] === 405, 'El listado no acepta escrituras');
    comprobar($solicitar('/equipo/productos/agregar', array_replace($campos, ['csrf' => 'incorrecto']))[0] === 422, 'Crear exige CSRF');
    foreach (['nombre', 'precio', 'activo', 'categoria'] as $campo) {
        comprobar($solicitar('/equipo/productos/agregar', array_replace($campos, [$campo => ['x']]))[0] === 422, 'Rechaza un campo HTTP malformado: ' . $campo);
    }
    [$estado, $error] = $solicitar('/equipo/productos/agregar', array_replace($campos, ['nombre' => '<script>prueba</script>', 'precio' => '-1']));
    comprobar($estado === 422 && str_contains($error, '&lt;script&gt;prueba&lt;/script&gt;') && !str_contains($error, '<script>prueba</script>'), 'Conserva y escapa los datos del formulario con error');
    [$estado] = $solicitar('/equipo/productos/agregar', array_replace($campos, ['nombre' => 'Producto <web>', 'destacado' => '1', 'id_producto' => '2', 'imagen' => '../config/database.php']));
    comprobar($estado === 303, 'Agrega un producto desde el formulario');
    $web = $db->query('SELECT * FROM productos ORDER BY id_producto DESC LIMIT 1')->fetch();
    $idWeb = (int) $web['id_producto'];
    comprobar($web['nombre'] === 'Producto <web>' && $web['imagen'] === '' && (int) $web['destacado'] === 0, 'El servidor determina la identidad y la imagen guardadas');
    comprobar($resumenPanel() === ['4', '4 categorías disponibles'], 'El panel actualiza productos y categorías después de un alta');
    [, $publico] = $solicitar('/producto?id=' . $idWeb);
    comprobar(str_contains($publico, 'Producto &lt;web&gt;') && str_contains($publico, '$ 24,17'), 'El alta se refleja en el catálogo con importes exactos y texto escapado');
    $codigoWeb = 'PRD-' . str_pad((string) $idWeb, 3, '0', STR_PAD_LEFT);
    [, $lista] = $solicitar('/equipo/productos?q=' . $codigoWeb);
    comprobar(str_contains($lista, 'Producto agregado correctamente') && str_contains($lista, '/equipo/productos/editar?id=' . $idWeb), 'El listado confirma el alta y muestra los enlaces amigables');
    comprobar(!str_contains($solicitar('/equipo/productos')[1], 'Producto agregado correctamente'), 'Actualizar el listado no repite el aviso ni el guardado');
    comprobar($solicitar('/equipo/productos/editar?id=99999999')[0] === 404, 'Editar un identificador inexistente responde 404');

    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+iG0sAAAAASUVORK5CYII=');
    $crearArchivo = static function (string $contenido, string $extension) use (&$temporales): string {
        $archivo = sys_get_temp_dir() . '/qba-hu06-' . bin2hex(random_bytes(8)) . '.' . $extension;
        file_put_contents($archivo, $contenido);
        $temporales[] = $archivo;
        return $archivo;
    };
    $archivo = $crearArchivo($png, 'png');
    $falso = $crearArchivo('<?php echo "no ejecutar";', 'png');
    $grande = $crearArchivo($png . str_repeat('x', ImagenProducto::MAXIMO_BYTES), 'png');
    $dimensiones = $crearArchivo(substr_replace($png, pack('N', 8001), 16, 4), 'png');
    foreach ([$falso, $grande, $dimensiones] as $invalido) {
        comprobar($solicitar('/equipo/productos/agregar', $campos + ['imagen' => new CURLFile($invalido, 'image/png', 'imagen.png')])[0] === 422, 'Rechaza una imagen inválida, demasiado grande o con dimensiones excesivas');
    }
    comprobar($solicitar('/equipo/productos/agregar', $campos + ['imagen[]' => new CURLFile($archivo, 'image/png', 'imagen.png')])[0] === 422, 'No admite un arreglo de imágenes en un campo individual');
    [, $formulario] = $solicitar('/equipo/productos/editar?id=' . $idWeb);
    $editar = array_replace($campos, ['version' => oculto($formulario, 'version')]);
    comprobar($solicitar('/equipo/productos/editar?id=' . $idWeb, $editar + ['imagen' => new CURLFile($archivo, 'text/plain', '../archivo.php')])[0] === 303, 'Determina el tipo real de la imagen e ignora el nombre enviado');
    $foto = $productos->buscarGestion($idWeb)['imagen_guardada'];
    $imagenes[] = $foto;
    comprobar((bool) preg_match('~^productos/[a-f0-9]{32}\.png$~D', $foto) && is_file(dirname(__DIR__) . '/public/images/' . $foto), 'Guarda la fotografía con un nombre aleatorio y extensión permitida');
    comprobar($solicitar('/images/' . $foto)[0] === 200 && str_contains($solicitar('/producto?id=' . $idWeb)[1], '/images/' . $foto), 'El catálogo público utiliza la fotografía subida');

    [, $formulario] = $solicitar('/equipo/productos/editar?id=' . $idWeb);
    $editar['version'] = oculto($formulario, 'version');
    $cantidadFotos = count(glob(dirname(__DIR__) . '/public/images/productos/*.png'));
    $db->exec("CREATE TRIGGER fallo_hu06 BEFORE UPDATE ON productos FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fallo HU06'");
    [$estado, $error] = $solicitar('/equipo/productos/editar?id=' . $idWeb, $editar + ['imagen' => new CURLFile($archivo, 'image/png', 'imagen.png')]);
    $db->exec('DROP TRIGGER fallo_hu06');
    comprobar($estado === 503 && !str_contains($error, 'SQLSTATE') && !str_contains($error, 'Fallo HU06'), 'Informa un fallo del guardado sin revelar el error de MySQL');
    comprobar($productos->buscarGestion($idWeb)['imagen_guardada'] === $foto && is_file(dirname(__DIR__) . '/public/images/' . $foto)
        && count(glob(dirname(__DIR__) . '/public/images/productos/*.png')) === $cantidadFotos, 'Si falla MySQL retira la imagen nueva y conserva la anterior');
    comprobar($solicitar('/equipo/productos/editar?id=' . $idWeb, $editar + ['imagen' => new CURLFile($archivo, 'image/png', 'segunda.png')])[0] === 303, 'Permite guardar después de recuperarse la base');
    $fotoNueva = $productos->buscarGestion($idWeb)['imagen_guardada'];
    $imagenes[] = $fotoNueva;
    clearstatcache();
    comprobar($fotoNueva !== $foto && !is_file(dirname(__DIR__) . '/public/images/' . $foto), 'Reemplazar la fotografía retira la anterior después de guardar');
    comprobar($solicitar('/equipo/productos/editar?id=' . $idWeb, $editar)[0] === 422, 'Una pestaña anterior no reemplaza la edición recién guardada');
    [, $formulario] = $solicitar('/equipo/productos/editar?id=' . $idWeb);
    $editar = array_replace($editar, ['version' => oculto($formulario, 'version'), 'activo' => '0']);
    comprobar($solicitar('/equipo/productos/editar?id=' . $idWeb, $editar)[0] === 303 && $solicitar('/producto?id=' . $idWeb)[0] === 404, 'Desactivar oculta el producto del catálogo público');
    comprobar(str_contains($solicitar('/equipo/productos?q=' . $codigoWeb . '&estado=0')[1], 'Inactivo'), 'El administrador conserva acceso al producto inactivo');
    comprobar($resumenPanel() === ['3', '3 categorías disponibles'], 'Desactivar actualiza el panel y quita las categorías sin productos activos');

    // La identidad se vuelve a consultar antes de aceptar un formulario abierto.
    $db->exec("UPDATE usuarios_equipo SET rol = 'empleado' WHERE id_usuario = " . $cuentas['administrador']['id_usuario']);
    comprobar($solicitar('/equipo/productos/editar?id=' . $idWeb, $editar)[0] === 403, 'Cambiar el rol en la base revoca el permiso de un formulario abierto');
    $salir();
    $entrar('6000003');
    [, $formulario] = $solicitar('/equipo/productos/eliminar?id=' . $idWeb);
    $eliminar = ['csrf' => oculto($formulario, 'csrf'), 'version' => oculto($formulario, 'version')];
    comprobar($productos->buscarGestion($idWeb) !== null, 'Abrir la confirmación no elimina el producto');
    comprobar($solicitar('/equipo/productos/eliminar?id=' . $idWeb, array_replace($eliminar, ['csrf' => 'incorrecto']))[0] === 422, 'Eliminar exige CSRF');
    comprobar($solicitar('/equipo/productos/eliminar?id=' . $idWeb, $eliminar)[0] === 303, 'El dueño también puede administrar y confirmar la eliminación');
    clearstatcache();
    comprobar($productos->buscarGestion($idWeb) === null && !is_file(dirname(__DIR__) . '/public/images/' . $fotoNueva), 'La baja retira la fotografía y el acceso al producto');
    comprobar($solicitar('/equipo/productos/eliminar?id=' . $idWeb, $eliminar)[0] === 404, 'Repetir la eliminación no vuelve a procesar el producto');
    comprobar((int) $db->query('SELECT COUNT(*) FROM correos_locales')->fetchColumn() === 0, 'La gestión de productos no envía correos');
    $db->exec('UPDATE productos SET activo = 0');
    comprobar($resumenPanel() === ['0', '0 categorías disponibles'], 'El panel muestra cero cuando el catálogo no tiene productos activos');
    $db->exec('UPDATE productos SET activo = 1 WHERE id_producto = 2');
    comprobar($resumenPanel() === ['1', '1 categoría disponible'], 'Reactivar actualiza el contador y muestra el singular de categoría');
    comprobar(!preg_match('/Fatal error:|Warning:|Notice:|Deprecated:/', $entorno->errores()), 'El recorrido no genera errores PHP');
    echo "Correcto: $comprobaciones comprobaciones de HU06.\n";
} catch (Throwable $error) {
    $correcto = false;
    fwrite(STDERR, $error->getMessage() . "\n" . $error->getTraceAsString() . "\n");
    fwrite(STDERR, $entorno?->errores() ?? '');
} finally {
    if ($entorno !== null) {
        foreach ($entorno->db->query("SELECT imagen FROM productos WHERE imagen LIKE 'productos/%'")->fetchAll(PDO::FETCH_COLUMN) as $imagen) {
            $imagenes[] = $imagen;
        }
    }
    foreach (array_unique($imagenes) as $imagen) {
        (new ImagenProducto())->retirar($imagen);
    }
    foreach ($temporales as $archivo) {
        if (is_file($archivo)) unlink($archivo);
    }
    $entorno?->cerrar();
}
exit($correcto ? 0 : 1);
