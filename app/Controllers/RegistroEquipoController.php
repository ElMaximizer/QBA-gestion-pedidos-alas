<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Producto;
use App\Core\Conexion;
use App\Core\Http;
use App\Core\Sesion;
use App\Core\Vista;
use App\Services\RegistroEquipo;
use DomainException;
use Throwable;

final class RegistroEquipoController
{
    public function index(): void
    {
        header('Cache-Control: no-store');
        $datos = ['nombre' => '', 'cedula' => '', 'email' => ''];
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Sesion::validarCsrf($_POST['csrf'] ?? null);
                unset($_SESSION['qba']['solicitud_equipo']);
                foreach (array_keys($datos) as $campo) {
                    $datos[$campo] = Http::texto($_POST, $campo);
                }
                $usuario = (new RegistroEquipo(Conexion::obtener()))->solicitar($datos['nombre'], $datos['cedula'], $datos['email'],
                    Http::cadena($_POST, 'password'), Http::cadena($_POST, 'confirm_password'));
                $_SESSION['qba']['solicitud_equipo'] = ['creado' => time(), 'correo_enviado' => $usuario['correo_enviado']];
                Http::redirigir('equipo-solicitud-enviada');
            } catch (DomainException $excepcion) {
                http_response_code(422);
                $error = $excepcion->getMessage();
            } catch (Throwable $excepcion) {
                error_log('Registro del equipo: falló el guardado (' . $excepcion::class . ').');
                http_response_code(503);
                $error = 'No pudimos guardar tu solicitud en este momento. Intentá nuevamente.';
            }
        }
        Vista::mostrar('equipo-registro', ['tituloPagina' => 'Solicitar usuario | Panadería Alas',
            'datos' => $datos, 'error' => $error]);
    }

    public function enviado(): void
    {
        header('Cache-Control: no-store');
        $solicitud = $_SESSION['qba']['solicitud_equipo'] ?? null;
        if (!is_array($solicitud) || ($solicitud['creado'] ?? 0) < time() - 1800) {
            unset($_SESSION['qba']['solicitud_equipo']);
            Http::redirigir('equipo-registro');
        }
        Vista::mostrar('equipo-solicitud-enviada', ['tituloPagina' => 'Solicitud recibida | Panadería Alas',
            'correoEnviado' => $solicitud['correo_enviado']]);
    }
    
    public function registrar(): void
{
    Sesion::requerirEquipo();

    $datos = [
        'cliente_nombre' => '',
        'telefono' => '',
        'fecha_retiro' => date('Y-m-d', strtotime('+1 day')),
        'hora_retiro' => '10:00',
        'notas' => '',
    ];

    $productos = Producto::catalogo()->listar();

    Vista::mostrar('admin/pedido-form', [
        'tituloPagina' => 'Agregar pedido | Panadería Alas',
        'layoutEquipo' => true,
        'seccionActiva' => 'pedidos',
        'datos' => $datos,
        'productos' => $productos,
        'error' => '',
    ]);
}
}

