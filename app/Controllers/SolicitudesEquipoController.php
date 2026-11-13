<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Conexion;
use App\Core\Http;
use App\Core\Rutas;
use App\Core\Sesion;
use App\Core\Vista;
use App\Models\SolicitudesEquipo;
use App\Models\WhitelistEquipo;
use DomainException;
use Throwable;

final class SolicitudesEquipoController
{
    public function index(): void
    {
        header('Cache-Control: no-store');
        $usuario = Sesion::requerirEquipo();
        if (!in_array($usuario['rol'], ['administrador', 'dueno'], true)) {
            http_response_code(403);
            self::error('Solo administradores y dueños pueden acceder a esta sección.');
            return;
        }
        try {
            $pagina = filter_var(Http::texto($_GET, 'pagina', '1'), FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]);
            if ($pagina === false) {
                throw new DomainException('Elegí una página válida.');
            }
            $modelo = new SolicitudesEquipo(Conexion::obtener());
            $whitelist = new WhitelistEquipo();
            $error = '';
            $cedulaWhitelist = '';
            $aviso = '';
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    Sesion::validarCsrf($_POST['csrf'] ?? null);
                    $accion = Http::texto($_POST, 'accion');
                    if (in_array($accion, ['habilitar-cedula', 'quitar-cedula'], true)) {
                        $cedulaWhitelist = Http::texto($_POST, 'cedula');
                        $whitelist->modificar($cedulaWhitelist, $accion === 'habilitar-cedula');
                        $_SESSION['qba']['aviso_solicitudes'] = $accion === 'habilitar-cedula'
                            ? 'Cédula habilitada para solicitar acceso.'
                            : 'Cédula retirada de la whitelist. Las cuentas y solicitudes existentes se conservan.';
                        Http::redirigir('equipo-solicitudes');
                    }
                    if ($usuario['rol'] !== 'dueno') {
                        http_response_code(403);
                        self::error('Solo el dueño puede aprobar o rechazar solicitudes.');
                        return;
                    }
                    $id = filter_var(Http::texto($_POST, 'id_usuario'), FILTER_VALIDATE_INT, [
                        'options' => ['min_range' => 1],
                    ]);
                    if ($id === false) {
                        throw new DomainException('Elegí una solicitud válida.');
                    }
                    $modelo->resolver($id, (int) $usuario['id_usuario'], $accion);
                    $_SESSION['qba']['aviso_solicitudes'] =
                        $accion === 'aprobar'
                            ? 'Solicitud aprobada. La persona ya puede ingresar como empleado.'
                            : 'Solicitud rechazada. La cuenta continúa sin acceso al equipo.';
                    header(
                        'Location: ' . Rutas::url('equipo-solicitudes', ['pagina' => $pagina]),
                        true,
                        303,
                    );
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
                'tituloPagina' => 'Solicitudes de acceso | Panadería Rustic',
                'layoutEquipo' => true,
                'seccionActiva' => 'solicitudes',
                'bandeja' => $usuario['rol'] === 'dueno' ? $modelo->listar($pagina) : null,
                'esDueno' => $usuario['rol'] === 'dueno',
                'cedulasHabilitadas' => $whitelist->listar(),
                'cedulaWhitelist' => $cedulaWhitelist,
                'error' => $error,
                'aviso' => $aviso,
                'rutaBandeja' => 'equipo-solicitudes',
                'parametrosBandeja' => [],
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
        Vista::mostrar('error', [
            'tituloPagina' => 'Solicitudes de acceso',
            'mensaje' => $mensaje,
            'layoutEquipo' => true,
            'rutaVolver' => 'equipo-panel',
            'textoVolver' => 'Volver al panel',
        ]);
    }
}
