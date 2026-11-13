<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/soporte.php';

use App\Models\ContenidoInicio;
use App\Models\Producto;

$entorno = null;
$comprobaciones = 0;
$correcto = true;

try {
    $entorno = new EntornoPruebas('equipo');
    $db = $entorno->db;
    $modelo = new ContenidoInicio($db);
    $catalogo = new Producto($db);
    $version = $modelo->seleccion()['version'];
    $modelo->guardar([3, 1], [2, 3, 1], $version);
    comprobar(array_column($catalogo->listarPortada(), 'id_producto') === [2, 3, 1], 'La portada conserva el orden elegido');
    comprobar(array_column($catalogo->listar(destacados: true), 'id_producto') === [1, 3], 'Solo muestra los favoritos elegidos');
    $seleccion = $modelo->seleccion();
    rechaza(fn () => $modelo->guardar([2], [1], $version), 'Un formulario anterior no sobrescribe otra selección');
    foreach ([[[1, 1], []], [[1, 2, 3, 4], []], [[], [1, 1]], [[], [1, 2, 3, 4, 5, 6]], [['1'], []], [[999999], []]] as [$favoritos, $portada]) {
        rechaza(fn () => $modelo->guardar($favoritos, $portada, $seleccion['version']), 'Rechaza selecciones inválidas');
    }
    comprobar($modelo->seleccion() === $seleccion, 'Un error conserva ambas selecciones');
    $db->exec('UPDATE productos SET activo = 0 WHERE id_producto = 3');
    comprobar(array_column($catalogo->listarPortada(), 'id_producto') === [2, 1], 'La portada excluye productos desactivados');
    rechaza(fn () => $modelo->guardar([3], [], $modelo->seleccion()['version']), 'No permite seleccionar un producto inactivo');
    $db->exec('UPDATE productos SET activo = 1 WHERE id_producto = 3');
    $seleccion = $modelo->seleccion();
    $db->exec("CREATE TRIGGER fallo_portada BEFORE UPDATE ON productos FOR EACH ROW
        BEGIN IF NEW.id_producto = 1 AND NEW.portada_orden = 2 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fallo temporal de prueba'; END IF; END");
    try {
        $modelo->guardar([2], [2, 1], $seleccion['version']);
        throw new RuntimeException('Debió fallar el guardado de la segunda posición.');
    } catch (PDOException) {
        comprobar($modelo->seleccion() === $seleccion, 'Revierte favoritos y posiciones si falla parte del guardado');
    }
    $db->exec('DROP TRIGGER fallo_portada');
    $modelo->guardar([2], [], $seleccion['version']);
    comprobar(array_column($catalogo->listarPortada(), 'id_producto') === [2], 'Sin portada explícita utiliza los favoritos');
    $version = $modelo->seleccion()['version'];
    $db->exec("UPDATE productos SET nombre = 'Nombre actualizado' WHERE id_producto = 2");
    rechaza(fn () => $modelo->guardar([1], [], $version), 'Detecta cambios en los productos desde otra pantalla');

    $entorno->iniciarServidor();
    $solicitar = $entorno->solicitar(...);
    comprobar($solicitar('/equipo/contenido-inicio')[0] === 303, 'El editor exige una sesión del equipo');
    $crearUsuario = $db->prepare("INSERT INTO usuarios_equipo (nombre, cedula, email, password_hash, estado, rol)
        VALUES (?, ?, ?, ?, 'aprobado', ?)");
    $hash = password_hash('VistaRustic28!', PASSWORD_DEFAULT);
    $crearUsuario->execute(['Empleado de prueba', 12345678, 'empleado.rustic@example.test', $hash, 'empleado']);
    $empleado = (int) $db->lastInsertId();
    $crearUsuario->execute(['Administrador de prueba', 87654321, 'admin.rustic@example.test', $hash, 'administrador']);
    $administrador = (int) $db->lastInsertId();
    $identificar = static function (int $id) use ($entorno): void {
        $entorno->sesion(static fn (array $sesion): array => array_replace($sesion,
            ['equipo_id' => $id, 'equipo_ultimo_acceso' => time()]));
    };
    $identificar($empleado);
    comprobar($solicitar('/equipo/contenido-inicio')[0] === 403
        && $solicitar('/equipo/contenido-inicio', ['rol' => 'dueno'])[0] === 403, 'Un empleado no puede leer ni modificar el editor');
    $identificar($administrador);
    [$estado, $html] = $solicitar('/equipo/contenido-inicio');
    comprobar($estado === 200 && str_contains($html, 'Cache-Control: no-store')
        && str_contains($html, 'panel.css') && str_contains($html, 'equipo-contenido-inicio.css'), 'El editor usa el layout interno y evita caché');
    $campos = ['csrf' => oculto($html, 'csrf'), 'version' => oculto($html, 'version'), 'favoritos' => ['1'], 'portada' => ['3', '1', '', '', '']];
    comprobar($solicitar('/equipo/contenido-inicio', array_replace($campos, ['csrf' => 'incorrecto']))[0] === 422, 'Exige CSRF para guardar');
    foreach ([['portada' => ['1', '1']], ['favoritos' => '1'], ['portada' => [['1']]], ['version' => 'anterior']] as $invalido) {
        comprobar($solicitar('/equipo/contenido-inicio', array_replace($campos, $invalido))[0] === 422, 'HTTP rechaza datos inválidos sin guardar');
    }
    [$estado, $respuesta] = $solicitar('/equipo/contenido-inicio', $campos);
    comprobar($estado === 303 && str_contains($respuesta, 'Location: /equipo/contenido-inicio'), 'Un guardado válido redirige al editor');
    [, $html] = $solicitar('/equipo/contenido-inicio');
    comprobar(str_contains($html, 'Guardamos los favoritos y el orden del carrusel.')
        && $modelo->seleccion()['portada'] === [3, 1], 'Conserva y vuelve a mostrar la selección guardada');
    comprobar($solicitar('/equipo/contenido-inicio', $campos)[0] === 422, 'No sobrescribe con un formulario anterior');
    [, $inicio] = $solicitar('/');
    comprobar(str_contains($inicio, 'Panadería Rustic') && str_contains($inicio, 'js/carrusel-inicio.js')
        && str_contains($inicio, 'data-carrito-panel') && str_contains($inicio, 'css/tema-rustic.css'), 'Integra marca, carrusel y carrito en el inicio');
    comprobar(str_contains($inicio, 'https://www.facebook.com/p/Panader%C3%ADa-Alas-100063598897018/'),
        'El cambio de marca conserva la dirección de Facebook proporcionada');
    $db->prepare('UPDATE productos SET nombre = ? WHERE id_producto = 3')->execute(['<script>nombre</script>']);
    [, $inicio] = $solicitar('/');
    comprobar(str_contains($inicio, '&lt;script&gt;nombre&lt;/script&gt;')
        && !str_contains($inicio, '<script>nombre</script>'), 'Escapa nombres de productos en la portada');
    $db->exec('UPDATE productos SET activo = 0');
    [$estado, $inicio] = $solicitar('/');
    comprobar($estado === 200 && str_contains($inicio, 'images/panes.png')
        && !str_contains($inicio, 'data-diapositiva'), 'Un catálogo vacío conserva la portada estática');
    $db->exec('UPDATE productos SET activo = 1');
    $db->exec('ALTER TABLE productos DROP COLUMN portada_orden');
    comprobar(count($catalogo->listarPortada()) === 1, 'El inicio sigue disponible antes de aplicar la migración');
    $migracion = file_get_contents(dirname(__DIR__) . '/database/contenido-inicio.sql');
    $db->exec($migracion);
    $db->exec($migracion);
    comprobar(strlen($modelo->seleccion()['version']) === 64 && (int) $db->query('SELECT COUNT(*) FROM productos')->fetchColumn() === 3,
        'La migración se puede repetir sin perder productos');
    comprobar(!preg_match('/Fatal error:|Warning:|Notice:|Deprecated:/', $entorno->errores()), 'No genera errores PHP en las páginas');
    echo "Correcto: $comprobaciones comprobaciones de contenido del inicio.\n";
} catch (Throwable $error) {
    $correcto = false;
    fwrite(STDERR, $error->getMessage() . "\n" . $error->getTraceAsString() . "\n");
    fwrite(STDERR, $entorno?->errores() ?? '');
} finally {
    $entorno?->cerrar();
}
exit($correcto ? 0 : 1);
