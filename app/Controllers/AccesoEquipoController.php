<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Conexion;
use App\Core\Http;
use App\Core\Sesion;
use App\Core\Vista;
use App\Models\AccesoEquipo;
use DomainException;
use Throwable;

final class AccesoEquipoController
{
    public function index(): void
    {
        header('Cache-Control: no-store');
        if (Sesion::equipo()) {
            Http::redirigir('equipo-panel');
        }
        $cedula = '';
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Sesion::validarCsrf($_POST['csrf'] ?? null);
                $cedula = Http::texto($_POST, 'cedula');
                $usuario = (new AccesoEquipo(Conexion::obtener()))->autenticar($cedula, Http::cadena($_POST, 'password'));
                if ($usuario['estado'] !== 'aprobado') {
                    throw new DomainException('Tu solicitud no está aprobada. Consultá con el dueño de la panadería.');
                }
                Sesion::iniciarEquipo((int) $usuario['id_usuario']);
                Http::redirigir('equipo-panel');
            } catch (DomainException $excepcion) {
                http_response_code(422);
                $error = $excepcion->getMessage();
            } catch (Throwable $excepcion) {
                error_log('Acceso del equipo: falló el inicio de sesión (' . $excepcion::class . ').');
                http_response_code(503);
                $error = 'No pudimos iniciar sesión en este momento. Intentá nuevamente.';
            }
        }
        Vista::mostrar('equipo-login', ['tituloPagina' => 'Acceso del equipo | Panadería Rustic',
            'cedula' => $cedula, 'error' => $error]);
    }

    public function salir(): void
    {
        Sesion::validarCsrf($_POST['csrf'] ?? null);
        Sesion::cerrarEquipo();
        Http::redirigir('equipo-login');
    }
}
