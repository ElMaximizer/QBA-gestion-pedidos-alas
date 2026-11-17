<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Conexion;
use App\Core\DatosCuenta;
use App\Core\Http;
use App\Core\Rutas;
use App\Core\Sesion;
use App\Core\Vista;
use App\Services\RecuperacionContrasena;
use DomainException;
use Throwable;

final class RecuperacionContrasenaController
{
    public function cliente(): void
    {
        $this->mostrar('cliente');
    }

    public function equipo(): void
    {
        $this->mostrar('equipo');
    }

    private function mostrar(string $tipo): void
    {
        header('Cache-Control: no-store');
        if (Sesion::cliente()) {
            Http::redirigir($tipo === 'cliente' ? 'cliente-cuenta' : 'inicio');
        }
        if (Sesion::equipo()) {
            Http::redirigir('equipo-panel');
        }

        $claveEstado = 'recuperacion_password';
        $claveResultado = 'recuperacion_resultado_' . $tipo;
        $estado = $_SESSION['qba'][$claveEstado] ?? null;
        if (!is_array($estado) || ($estado['tipo'] ?? '') !== $tipo) {
            unset($_SESSION['qba'][$claveEstado]);
            $estado = null;
        }
        if (($_GET['nuevo'] ?? '') === '1') {
            unset($_SESSION['qba'][$claveEstado]);
            $estado = null;
        }
        if (
            is_array($estado) &&
            isset($estado['codigo_validado_hasta']) &&
            (int) $estado['codigo_validado_hasta'] < time()
        ) {
            unset($estado['codigo_validado'], $estado['codigo_validado_hasta']);
            $_SESSION['qba'][$claveEstado] = $estado;
        }

        $mensaje = (string) ($_SESSION['qba']['recuperacion_mensaje_' . $tipo] ?? '');
        unset($_SESSION['qba']['recuperacion_mensaje_' . $tipo]);
        $resultado = (string) ($_SESSION['qba'][$claveResultado] ?? '');
        unset($_SESSION['qba'][$claveResultado]);
        $error = '';
        $identidad = is_array($estado) ? (string) ($estado['identidad'] ?? '') : '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Sesion::validarCsrf($_POST['csrf'] ?? null);
                $accion = Http::texto($_POST, 'accion');
                if ($accion === 'solicitar') {
                    $campo = $tipo === 'cliente' ? 'email' : 'cedula';
                    $identidad = Http::texto($_POST, $campo);
                    $identidad = $tipo === 'cliente'
                        ? DatosCuenta::email($identidad)
                        : (string) DatosCuenta::cedula($identidad);
                    (new RecuperacionContrasena(Conexion::obtener()))->solicitar(
                        $tipo,
                        $identidad,
                        (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
                    );
                    $_SESSION['qba'][$claveEstado] = [
                        'tipo' => $tipo,
                        'identidad' => $identidad,
                        'codigo_validado' => null,
                        'codigo_validado_hasta' => null,
                    ];
                    $_SESSION['qba']['recuperacion_mensaje_' . $tipo] =
                        'Si los datos corresponden a una cuenta activa, enviaremos un código al correo asociado. Revisá también la carpeta de spam.';
                    Http::redirigir($tipo === 'cliente' ? 'cliente-recuperar-password' : 'equipo-recuperar-password');
                }
                if ($accion === 'reenviar' && is_array($estado) && $identidad !== '') {
                    (new RecuperacionContrasena(Conexion::obtener()))->solicitar(
                        $tipo,
                        $identidad,
                        (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
                    );
                    $_SESSION['qba']['recuperacion_mensaje_' . $tipo] =
                        'Si la cuenta está activa, enviaremos un nuevo código. Revisá también la carpeta de spam; si lo pediste hace poco, esperá un minuto antes de volver a intentarlo.';
                    Http::redirigir($tipo === 'cliente' ? 'cliente-recuperar-password' : 'equipo-recuperar-password');
                }
                if ($accion === 'verificar_codigo' && is_array($estado) && $identidad !== '') {
                    $codigo = Http::texto($_POST, 'codigo');
                    (new RecuperacionContrasena(Conexion::obtener()))->verificarCodigo(
                        $tipo,
                        $identidad,
                        $codigo,
                    );
                    $estado['codigo_validado'] = $codigo;
                    $estado['codigo_validado_hasta'] = time() + 300;
                    $_SESSION['qba'][$claveEstado] = $estado;
                    Http::redirigir($tipo === 'cliente' ? 'cliente-recuperar-password' : 'equipo-recuperar-password');
                }
                if (
                    $accion !== 'restablecer' ||
                    !is_array($estado) ||
                    $identidad === '' ||
                    !is_string($estado['codigo_validado'] ?? null) ||
                    (int) ($estado['codigo_validado_hasta'] ?? 0) < time()
                ) {
                    throw new DomainException('Volvé a solicitar un código para continuar.');
                }
                (new RecuperacionContrasena(Conexion::obtener()))->restablecer(
                    $tipo,
                    $identidad,
                    $estado['codigo_validado'],
                    Http::cadena($_POST, 'password'),
                    Http::cadena($_POST, 'confirm_password'),
                );
                Sesion::cerrarCliente();
                $_SESSION['qba'][$claveResultado] = 'La contraseña se cambió correctamente. Ya podés iniciar sesión con tu nueva contraseña.';
                Http::redirigir($tipo === 'cliente' ? 'cliente-recuperar-password' : 'equipo-recuperar-password');
            } catch (DomainException $excepcion) {
                http_response_code(422);
                $error = $excepcion->getMessage();
            } catch (Throwable $excepcion) {
                error_log(
                    'Recuperación de contraseña: operación fallida (' . $excepcion::class . ') en ' .
                        basename($excepcion->getFile()) . ':' . $excepcion->getLine() . ' — ' .
                        $excepcion->getMessage(),
                );
                http_response_code(503);
                $error = 'No pudimos procesar la solicitud. Intentá nuevamente en unos minutos.';
            }
        }

        Vista::mostrar('recuperacion-contrasena', [
            'tituloPagina' => 'Recuperar contraseña | Panadería Rustic',
            'tipoRecuperacion' => $tipo,
            'identidad' => $identidad,
            'mostrarVerificacion' => is_array($estado) && !is_string($estado['codigo_validado'] ?? null),
            'mostrarCambio' => is_array($estado) && is_string($estado['codigo_validado'] ?? null)
                && (int) ($estado['codigo_validado_hasta'] ?? 0) >= time(),
            'mensaje' => $mensaje,
            'resultado' => $resultado,
            'error' => $error,
        ]);
    }
}
