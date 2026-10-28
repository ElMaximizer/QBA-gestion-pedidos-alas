<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Http;
use App\Core\Sesion;
use App\Core\Vista;
use App\Models\PermisosEquipo;
use DomainException;
use Throwable;

final class PermisosEquipoController
{
    public function index(): void
    {
        header('Cache-Control: no-store');
        $dueno = Sesion::requerirEquipo();
        if ($dueno['rol'] !== 'dueno') {
            http_response_code(403);
            Vista::mostrar('error', [
                'tituloPagina' => 'Acceso no permitido',
                'mensaje' => 'Solo el dueño puede administrar los roles y permisos del equipo.',
                'layoutEquipo' => true,
                'rutaVolver' => 'equipo-panel',
                'textoVolver' => 'Volver al panel',
            ]);
            return;
        }

        $busqueda = '';
        $rol = '';
        $aviso = '';
        $error = '';

        try {
            $busqueda = Http::texto($_GET, 'q');
            $rol = Http::texto($_GET, 'rol');
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                Sesion::validarCsrf($_POST['csrf'] ?? null);
                $idUsuario = filter_var(Http::cadena($_POST, 'id_usuario'), FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1]]);
                $rolNuevo = Http::texto($_POST, 'rol');
                if ($idUsuario === false) {
                    throw new DomainException('Seleccioná una cuenta válida.');
                }

                PermisosEquipo::gestion()->cambiarRol($idUsuario, (int) $dueno['id_usuario'], $rolNuevo);
                $_SESSION['qba']['aviso_permisos'] = 'Rol actualizado correctamente. El nuevo permiso se aplica desde la siguiente petición.';
                Http::redirigir('equipo-permisos');
            }
        } catch (DomainException $excepcion) {
            http_response_code(422);
            $error = $excepcion->getMessage();
        } catch (Throwable $excepcion) {
            error_log('HU22: falló la gestión de permisos (' . $excepcion::class . ').');
            http_response_code(503);
            $error = 'No pudimos actualizar los permisos en este momento. Intentá nuevamente.';
        }

        try {
            $usuarios = PermisosEquipo::gestion()->listar($busqueda, $rol);
        } catch (DomainException $excepcion) {
            http_response_code(422);
            $error = $error !== '' ? $error : $excepcion->getMessage();
            $usuarios = [];
        } catch (Throwable $excepcion) {
            error_log('HU22: falló la consulta de cuentas (' . $excepcion::class . ').');
            http_response_code(503);
            $error = 'No pudimos cargar las cuentas en este momento. Intentá nuevamente.';
            $usuarios = [];
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $aviso = (string) ($_SESSION['qba']['aviso_permisos'] ?? '');
            unset($_SESSION['qba']['aviso_permisos']);
        }

        Vista::mostrar('equipo-permisos', [
            'tituloPagina' => 'Roles y permisos | Panadería Rustic',
            'seccionActiva' => 'permisos',
            'busqueda' => $busqueda,
            'rol' => $rol,
            'usuarios' => $usuarios,
            'aviso' => $aviso,
            'error' => $error,
        ]);
    }
}
