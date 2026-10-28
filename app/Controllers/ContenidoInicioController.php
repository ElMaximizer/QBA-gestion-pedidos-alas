<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Conexion;
use App\Core\Http;
use App\Core\Sesion;
use App\Core\Vista;
use App\Models\ContenidoInicio;
use App\Models\Producto;
use DomainException;
use Throwable;

final class ContenidoInicioController
{
    public function index(): void
    {
        header('Cache-Control: no-store');
        Sesion::requerirEquipo();
        if (!Sesion::administraProductos()) {
            http_response_code(403);
            Vista::mostrar('error', ['tituloPagina' => 'Acceso no permitido', 'layoutEquipo' => true,
                'mensaje' => 'Solo administrador y dueño pueden elegir el contenido del inicio.',
                'rutaVolver' => 'equipo-panel', 'textoVolver' => 'Volver al panel']);
            return;
        }
        $modelo = new ContenidoInicio(Conexion::obtener());
        $seleccion = $modelo->seleccion();
        $error = '';
        $aviso = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Sesion::validarCsrf($_POST['csrf'] ?? null);
                $seleccion['favoritos'] = self::ids($_POST['favoritos'] ?? [], 3);
                $seleccion['portada'] = self::ids($_POST['portada'] ?? [], 5);
                $seleccion['version'] = Http::texto($_POST, 'version');
                $modelo->guardar($seleccion['favoritos'], $seleccion['portada'], $seleccion['version']);
                $_SESSION['qba']['aviso_contenido_inicio'] = 'Guardamos los favoritos y el orden del carrusel.';
                Http::redirigir('equipo-contenido-inicio');
            } catch (DomainException $excepcion) {
                http_response_code(422);
                $error = $excepcion->getMessage();
            } catch (Throwable $excepcion) {
                error_log('Contenido del inicio: falló el guardado (' . $excepcion::class . ').');
                http_response_code(503);
                $error = 'No pudimos guardar la selección. Intentá nuevamente.';
            }
        } else {
            $aviso = (string) ($_SESSION['qba']['aviso_contenido_inicio'] ?? '');
            unset($_SESSION['qba']['aviso_contenido_inicio']);
        }
        Vista::mostrar('equipo-contenido-inicio', ['tituloPagina' => 'Contenido del inicio | Panadería Rustic',
            'seccionActiva' => 'contenido', 'seleccion' => $seleccion, 'productos' => Producto::catalogo()->listar(),
            'error' => $error, 'aviso' => $aviso]);
    }

    private static function ids(mixed $entrada, int $maximo): array
    {
        if (!is_array($entrada) || count($entrada) > $maximo) {
            throw new DomainException('Elegí hasta 3 favoritos y 5 productos para el carrusel.');
        }
        $ids = [];
        foreach ($entrada as $valor) {
            if ($valor === '') continue;
            $id = is_string($valor) ? filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
            if (!$id || in_array($id, $ids, true)) throw new DomainException('Elegí productos válidos, sin repetirlos en una misma selección.');
            $ids[] = $id;
        }
        return $ids;
    }
}
