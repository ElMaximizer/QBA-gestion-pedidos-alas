<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Conexion;
use App\Core\Http;
use App\Core\Sesion;
use App\Core\Vista;
use App\Models\AccesoCliente;
use DomainException;
use Throwable;

final class AccesoController
{
    public function index(): void
    {
        header('Cache-Control: no-store');
        if (Sesion::cliente()) {
            Http::redirigir('inicio');
        }
        $email = '';
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Sesion::validarCsrf($_POST['csrf'] ?? null);
                $email = Http::texto($_POST, 'email');
                $cliente = (new AccesoCliente(Conexion::obtener()))->autenticar($email, Http::cadena($_POST, 'password'));
                if (!(int) $cliente['verificado']) {
                    $_SESSION['qba']['registro_pendiente_email'] = $cliente['email'];
                    throw new DomainException('Tu correo todavía no está verificado. Ingresá el código para activar la cuenta.');
                }
                Sesion::iniciarCliente((int) $cliente['id_cliente']);
                Http::redirigir('inicio');
            } catch (DomainException $excepcion) {
                http_response_code(422);
                $error = $excepcion->getMessage();
            } catch (Throwable $excepcion) {
                error_log('HU17: falló el inicio de sesión (' . $excepcion::class . ').');
                http_response_code(503);
                $error = 'No pudimos iniciar sesión en este momento. Intentá nuevamente.';
            }
        }
        Vista::mostrar('cliente-login', ['tituloPagina' => 'Acceso para clientes | Panadería Alas',
            'email' => $email, 'error' => $error]);
    }

    public function salir(): void
    {
        Sesion::validarCsrf($_POST['csrf'] ?? null);
        Sesion::cerrarCliente();
        Http::redirigir('inicio');
    }
}
