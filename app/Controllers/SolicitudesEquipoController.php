<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Conexion;
use App\Core\Http;
use App\Core\Rutas;
use App\Core\Sesion;
use App\Core\Vista;
use App\Models\SolicitudesEquipo;
use DomainException;
use Throwable;

final class SolicitudesEquipoController
{
    public function index(): void
    {
        header('Cache-Control: no-store');
        $dueno = Sesion::requerirEquipo();
        if ($dueno['rol'] !== 'dueno') {
            http_response_code(403);
            self::error('Solo el dueño puede revisar y resolver las solicitudes de acceso.');
            return;
        }
        try {
            $pagina = filter_var(Http::texto($_GET, 'pagina', '1'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($pagina === false) throw new DomainException('Elegí una página válida.');
            $modelo = new SolicitudesEquipo(Conexion::obtener());
            $error = '';
            $aviso = '';
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    Sesion::validarCsrf($_POST['csrf'] ?? null);
                    $id = filter_var(Http::texto($_POST, 'id_usuario'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                    if ($id === false) throw new DomainException('Elegí una solicitud válida.');
                    $accion = Http::texto($_POST, 'accion');
                    $modelo->resolver($id, (int) $dueno['id_usuario'], $accion);
                    $_SESSION['qba']['aviso_solicitudes'] = $accion === 'aprobar'
                        ? 'Solicitud aprobada. La persona ya puede ingresar como empleado.'
                        : 'Solicitud rechazada. La cuenta continúa sin acceso al equipo.';
                    header('Location: ' . Rutas::url('equipo-solicitudes', ['pagina' => $pagina]), true, 303);
                    return;
                } catch (DomainException $excepcion) {
                    http_response_code(422);
                    $error = $excepcion->getMessage();
                }
            } else {
                $aviso = (string) ($_SESSION['qba']['aviso_solicitudes'] ?? '');
                unset($_SESSION['qba']['aviso_solicitudes']);
            }
            Vista::mostrar('equipo-solicitudes', [
                'tituloPagina' => 'Solicitudes de acceso | Panadería Rustic', 'layoutEquipo' => true, 'seccionActiva' => 'solicitudes',
                'bandeja' => $modelo->listar($pagina), 'error' => $error, 'aviso' => $aviso,
                'rutaBandeja' => 'equipo-solicitudes', 'parametrosBandeja' => [],
            ]);
        } catch (DomainException $excepcion) {
            http_response_code(422);
            self::error($excepcion->getMessage());
        } catch (Throwable $excepcion) {
            error_log('Solicitudes del equipo: falló la operación (' . $excepcion::class . ').');
            http_response_code(503);
            self::error('No pudimos cargar las solicitudes. Intentá nuevamente en unos minutos.');
        }
    }

    private static function error(string $mensaje): void
    {
        Vista::mostrar('error', ['tituloPagina' => 'Solicitudes de acceso', 'mensaje' => $mensaje,
            'layoutEquipo' => true, 'rutaVolver' => 'equipo-panel', 'textoVolver' => 'Volver al panel']);
    }
}
