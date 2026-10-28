<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Conexion;
use App\Core\Http;
use App\Core\Sesion;
use App\Core\Vista;
use App\Models\Cliente;
use App\Services\RegistroCliente;
use DomainException;
use Throwable;

final class RegistroController
{
    public function index(): void
    {
        $nombre = '';
        $email = '';
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Sesion::validarCsrf($_POST['csrf'] ?? null);
                $nombre = Http::texto($_POST, 'nombre');
                $email = Http::texto($_POST, 'email');
                $cliente = (new RegistroCliente(Conexion::obtener()))->registrar($nombre, $email,
                    Http::cadena($_POST, 'password'), Http::cadena($_POST, 'confirm_password'));
                $_SESSION['qba']['registro_pendiente_email'] = $cliente['email'];
                if (!$cliente['correo_enviado']) {
                    $_SESSION['qba']['registro_error_correo'] = 'Creamos tu cuenta, pero no pudimos enviar el código. Esperá un minuto y usá «Reenviar código».';
                }
                Http::redirigir('verificar-cuenta');
            } catch (DomainException $excepcion) {
                http_response_code(422);
                $error = $excepcion->getMessage();
            } catch (Throwable $excepcion) {
                error_log((string) $excepcion);
                http_response_code(503);
                $error = 'No pudimos crear la cuenta en este momento. Intentá nuevamente.';
            }
        }
        Vista::mostrar('cliente-registro', ['tituloPagina' => 'Crear cuenta de cliente | Panadería Rustic',
            'nombre' => $nombre, 'email' => $email, 'error' => $error]);
    }

    public function verificar(): void
    {
        $email = $_SESSION['qba']['registro_pendiente_email'] ?? '';
        $error = $_SESSION['qba']['registro_error_correo'] ?? '';
        $mensaje = $_SESSION['qba']['registro_mensaje'] ?? '';
        unset($_SESSION['qba']['registro_mensaje']);
        unset($_SESSION['qba']['registro_error_correo']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Sesion::validarCsrf($_POST['csrf'] ?? null);
                $email = Http::texto($_POST, 'email');
                $accion = Http::texto($_POST, 'accion');
                $clientes = new Cliente(Conexion::obtener());
                if ($accion === 'reenviar') {
                    $enviado = (new RegistroCliente(Conexion::obtener()))->reenviar($email);
                    $_SESSION['qba']['registro_pendiente_email'] = strtolower($email);
                    if ($enviado) {
                        $_SESSION['qba']['registro_mensaje'] = 'Si la cuenta está pendiente, enviamos un nuevo código. Revisá también la carpeta de spam.';
                    } else {
                        $_SESSION['qba']['registro_error_correo'] = 'No pudimos enviar el código. Tu cuenta sigue pendiente; esperá un minuto y volvé a pedirlo.';
                    }
                    Http::redirigir('verificar-cuenta');
                }
                if ($accion !== 'verificar') {
                    throw new DomainException('Seleccioná una acción válida.');
                }
                $clientes->verificar($email, Http::texto($_POST, 'codigo'));
                unset($_SESSION['qba']['registro_pendiente_email']);
                $_SESSION['qba']['registro_completado_email'] = strtolower($email);
                Http::redirigir('registro-completado');
            } catch (DomainException $excepcion) {
                http_response_code(422);
                $error = $excepcion->getMessage();
            } catch (Throwable $excepcion) {
                error_log((string) $excepcion);
                http_response_code(503);
                $error = 'No pudimos procesar la verificación. Intentá nuevamente.';
            }
        }
        Vista::mostrar('cliente-verificar', ['tituloPagina' => 'Verificar cuenta de cliente | Panadería Rustic',
            'email' => $email, 'error' => $error, 'mensaje' => $mensaje]);
    }

    public function completado(): void
    {
        $email = $_SESSION['qba']['registro_completado_email'] ?? null;
        if (!$email) {
            Http::redirigir('registro');
        }
        Vista::mostrar('registro-completado', ['tituloPagina' => 'Cuenta creada | Panadería Rustic', 'email' => $email]);
    }
}
