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
        $nuevoNombre = '';
        $nuevaCedula = '';
        $nuevoEmail = '';
        $mostrarAltaEmpleado = false;
        $filtrosPersonal = [];

        try {
            $busqueda = Http::texto($_GET, 'q');
            $rol = Http::texto($_GET, 'rol');
            $filtrosPersonal = array_filter(
                ['q' => $busqueda, 'rol' => $rol],
                static fn(string $valor): bool => $valor !== '',
            );
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $accion = Http::texto($_POST, 'accion', 'cambiar_rol');
                $mostrarAltaEmpleado = $accion === 'crear_empleado';
                Sesion::validarCsrf($_POST['csrf'] ?? null);

                if ($accion === 'crear_empleado') {
                    $nuevoNombre = Http::texto($_POST, 'nombre');
                    $nuevaCedula = Http::texto($_POST, 'cedula');
                    $nuevoEmail = Http::texto($_POST, 'email');
                    PermisosEquipo::gestion()->crearEmpleado(
                        (int) $dueno['id_usuario'],
                        $nuevoNombre,
                        $nuevaCedula,
                        $nuevoEmail,
                        Http::cadena($_POST, 'password'),
                        Http::cadena($_POST, 'confirmacion'),
                    );
                    $_SESSION['qba']['aviso_permisos'] =
                        'La cuenta de empleado fue creada y ya puede iniciar sesión.';
                    Http::redirigir('equipo-permisos');
                }

                $idUsuario = filter_var(Http::cadena($_POST, 'id_usuario'), FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 1],
                ]);
                $rolNuevo = Http::texto($_POST, 'rol');
                if ($idUsuario === false) {
                    throw new DomainException('Seleccioná una cuenta válida.');
                }
                if ($accion === 'cambiar_rol') {
                    PermisosEquipo::gestion()->cambiarRol(
                        $idUsuario,
                        (int) $dueno['id_usuario'],
                        $rolNuevo,
                    );
                    $_SESSION['qba']['aviso_permisos'] =
                        'Rol actualizado correctamente. El nuevo permiso se aplica desde la siguiente petición.';
                } elseif (in_array($accion, ['desactivar', 'reactivar'], true)) {
                    PermisosEquipo::gestion()->cambiarEstado(
                        $idUsuario,
                        (int) $dueno['id_usuario'],
                        $accion,
                    );
                    $_SESSION['qba']['aviso_permisos'] = $accion === 'desactivar'
                        ? 'La cuenta fue desactivada. Se cerrará su acceso en la siguiente petición.'
                        : 'La cuenta fue reactivada y puede volver a ingresar.';
                } else {
                    throw new DomainException('Seleccioná una acción válida.');
                }
                Http::redirigir('equipo-permisos', $filtrosPersonal);
            }
        } catch (DomainException $excepcion) {
            http_response_code(422);
            $error = $excepcion->getMessage();
        } catch (Throwable $excepcion) {
            error_log('HU21/HU22: falló la gestión de personal y permisos (' . $excepcion::class . ').');
            http_response_code(503);
            $error = 'No pudimos actualizar los permisos en este momento. Intentá nuevamente.';
        }

        try {
            $usuarios = PermisosEquipo::gestion()->listar($busqueda, $rol, true);
        } catch (DomainException $excepcion) {
            http_response_code(422);
            $error = $error !== '' ? $error : $excepcion->getMessage();
            $usuarios = [];
        } catch (Throwable $excepcion) {
            error_log('HU21/HU22: falló la consulta de cuentas (' . $excepcion::class . ').');
            http_response_code(503);
            $error = 'No pudimos cargar las cuentas en este momento. Intentá nuevamente.';
            $usuarios = [];
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $aviso = (string) ($_SESSION['qba']['aviso_permisos'] ?? '');
            unset($_SESSION['qba']['aviso_permisos']);
        }

        Vista::mostrar('equipo-personal', [
            'tituloPagina' => 'Administrar personal | Panadería Rustic',
            'seccionActiva' => 'permisos',
            'busqueda' => $busqueda,
            'rol' => $rol,
            'usuarios' => $usuarios,
            'puedeEditarRoles' => true,
            'rutaPersonal' => 'equipo-permisos',
            'aviso' => $aviso,
            'error' => $error,
            'nuevoNombre' => $nuevoNombre,
            'nuevaCedula' => $nuevaCedula,
            'nuevoEmail' => $nuevoEmail,
            'mostrarAltaEmpleado' => $mostrarAltaEmpleado,
            'filtrosPersonal' => $filtrosPersonal,
        ]);
    }
}
