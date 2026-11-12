<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Conexion;
use App\Core\Http;
use App\Core\Sesion;
use App\Core\Vista;
use App\Models\CuentaCliente;
use App\Services\ConfiguracionCliente;
use DomainException;
use Throwable;

final class CuentaClienteController
{
    private const SECCIONES = [
        'perfil' => 'Datos personales',
        'direcciones' => 'Mis direcciones',
        'seguridad' => 'Contraseña',
        'correo' => 'Correo electrónico',
        'privacidad' => 'Eliminar cuenta',
    ];

    private const ACCIONES = [
        'nombre' => 'perfil',
        'password' => 'seguridad',
        'cancelar-password' => 'seguridad',
        'codigo-password' => 'seguridad',
        'correo' => 'correo',
        'verificar-correo' => 'correo',
        'cancelar-correo' => 'correo',
        'guardar-direccion' => 'direcciones',
        'eliminar-direccion' => 'direcciones',
        'direccion-principal' => 'direcciones',
    ];

    public function index(): void
    {
        $cliente = $this->requerirCliente();
        $id = (int) $cliente['id_cliente'];
        $cuentas = new CuentaCliente(Conexion::obtener());
        $seccion = Http::texto($_GET, 'seccion', 'perfil');
        if (!isset(self::SECCIONES[$seccion])) {
            throw new DomainException('Seleccioná una sección válida de tu cuenta.');
        }
        $error = '';
        $valores = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Sesion::validarCsrf($_POST['csrf'] ?? null);
                $version = $_SESSION['qba']['cliente_version'];
                $accion = Http::texto($_POST, 'accion');
                if (!isset(self::ACCIONES[$accion])) {
                    throw new DomainException('Seleccioná una acción válida para tu cuenta.');
                }
                $seccion = self::ACCIONES[$accion];
                if ($accion === 'nombre') {
                    $valores['nombre'] = Http::texto($_POST, 'nombre');
                    $valores['telefono'] = Http::texto($_POST, 'telefono');
                    $cuentas->actualizarPerfil($id, $version, $valores['nombre'], $valores['telefono']);
                    $aviso = 'Tus datos personales se actualizaron correctamente.';
                } elseif ($accion === 'codigo-password') {
                    $enviado = (new ConfiguracionCliente(Conexion::obtener()))->solicitarCodigoPassword(
                        $id, $version, Http::cadena($_POST, 'password'),
                        Http::cadena($_POST, 'new_password'), Http::cadena($_POST, 'confirm_password'),
                    );
                    if (!$enviado) {
                        throw new DomainException('No pudimos enviar el código. Tu contraseña sigue vigente; esperá un minuto y volvé a solicitarlo.');
                    }
                    $aviso = 'Enviamos un código a tu correo actual. Vence en 10 minutos y admite cinco intentos.';
                } elseif ($accion === 'cancelar-password') {
                    $cuentas->cancelarPassword($id, $version);
                    $aviso = 'Cancelamos el cambio. Tu contraseña actual sigue vigente.';
                } elseif ($accion === 'password') {
                    $nuevaVersion = $cuentas->verificarPassword(
                        $id,
                        $version,
                        Http::texto($_POST, 'codigo'),
                    );
                    Sesion::iniciarCliente($id, $nuevaVersion);
                    $aviso = 'Contraseña actualizada. Las otras sesiones quedaron cerradas y se canceló cualquier cambio de correo pendiente.';
                } elseif ($accion === 'correo') {
                    $valores['nuevo_email'] = Http::texto($_POST, 'email');
                    $enviado = (new ConfiguracionCliente(Conexion::obtener()))->solicitarCorreo(
                        $id,
                        $version,
                        Http::cadena($_POST, 'password'),
                        $valores['nuevo_email'],
                    );
                    if (!$enviado) {
                        throw new DomainException('El cambio quedó pendiente, pero no pudimos enviar el código. Tu correo actual sigue vigente; esperá un minuto y volvé a solicitarlo.');
                    }
                    $aviso = 'Enviamos un código al nuevo correo. Confirmalo para completar el cambio.';
                } elseif ($accion === 'verificar-correo') {
                    $nuevaVersion = $cuentas->verificarCorreo($id, $version, Http::texto($_POST, 'codigo'));
                    Sesion::iniciarCliente($id, $nuevaVersion);
                    $aviso = 'Tu correo se actualizó correctamente. Usalo en el próximo inicio de sesión; las otras sesiones quedaron cerradas.';
                } elseif ($accion === 'cancelar-correo') {
                    $cuentas->cancelarCorreo($id, $version);
                    $aviso = 'Cancelamos el cambio de correo. Tu correo actual sigue vigente.';
                } elseif ($accion === 'guardar-direccion') {
                    $valores['alias'] = Http::texto($_POST, 'alias');
                    $valores['direccion'] = Http::texto($_POST, 'direccion');
                    $valores['referencias'] = Http::texto($_POST, 'referencias');
                    $valores['id_direccion'] = Http::texto($_POST, 'id_direccion');
                    $idDireccion = $valores['id_direccion'] === ''
                        ? null
                        : $this->idDireccion($valores['id_direccion']);
                    $cuentas->guardarDireccion($id, $version, $valores['alias'], $valores['direccion'], $idDireccion, $valores['referencias']);
                    $aviso = $idDireccion === null ? 'Dirección agregada correctamente.' : 'Dirección actualizada correctamente.';
                } elseif ($accion === 'direccion-principal') {
                    $cuentas->direccionPrincipal($id, $version, $this->idDireccion(Http::texto($_POST, 'id_direccion')));
                    $aviso = 'Actualizamos tu dirección principal.';
                } elseif ($accion === 'eliminar-direccion') {
                    $cuentas->eliminarDireccion($id, $version, $this->idDireccion(Http::texto($_POST, 'id_direccion')));
                    $aviso = 'Dirección eliminada. Los pedidos anteriores conservan su dirección.';
                }
                $_SESSION['qba']['aviso_cuenta'] = $aviso;
                Http::redirigir('cliente-cuenta', ['seccion' => $seccion]);
            } catch (DomainException $excepcion) {
                http_response_code(422);
                $error = $excepcion->getMessage();
            } catch (Throwable $excepcion) {
                error_log('Cuenta cliente: falló la configuración (' . $excepcion::class . ').');
                http_response_code(503);
                $error = 'No pudimos guardar los cambios en este momento. Intentá nuevamente.';
            }
        }
        $aviso = $_SESSION['qba']['aviso_cuenta'] ?? '';
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            unset($_SESSION['qba']['aviso_cuenta']);
        }
        $direccionEdicion = null;
        $editar = Http::texto($_GET, 'editar');
        if ($seccion === 'direcciones' && $editar !== '' && !$valores) {
            $direccionEdicion = $cuentas->buscarDireccion($id, $this->idDireccion($editar));
            if ($direccionEdicion === null) {
                http_response_code(404);
                Vista::mostrar('error', [
                    'tituloPagina' => 'Dirección no encontrada',
                    'mensaje' => 'Seleccioná una dirección de tu cuenta.',
                    'rutaVolver' => 'cliente-cuenta',
                    'textoVolver' => 'Volver a mi cuenta',
                ]);
                return;
            }
        }
        Vista::mostrar('cliente-cuenta', [
            'tituloPagina' => 'Configurar mi cuenta | Panadería Rustic',
            'datos' => $cuentas->datos($id),
            'passwordPendiente' => $seccion === 'seguridad' && $cuentas->passwordPendiente($id, $_SESSION['qba']['cliente_version']),
            'valores' => $valores,
            'aviso' => $aviso,
            'error' => $error,
            'seccionCuenta' => $seccion,
            'seccionesCuenta' => self::SECCIONES,
            'direcciones' => $seccion === 'direcciones' ? $cuentas->direcciones($id) : [],
            'direccionFormulario' => $valores + ($direccionEdicion ?? ['id_direccion' => '', 'alias' => '', 'direccion' => '', 'referencias' => '']),
        ]);
    }

    public function eliminar(): void
    {
        $cliente = $this->requerirCliente();
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Sesion::validarCsrf($_POST['csrf'] ?? null);
                if (Http::texto($_POST, 'confirmar') !== '1') {
                    throw new DomainException('Confirmá que querés eliminar tu cuenta.');
                }
                (new CuentaCliente(Conexion::obtener()))->eliminar(
                    (int) $cliente['id_cliente'],
                    $_SESSION['qba']['cliente_version'],
                    Http::cadena($_POST, 'password'),
                );
                Sesion::cerrarCliente();
                $_SESSION['qba']['aviso_cuenta'] = 'Tu cuenta fue eliminada y se cerraron sus sesiones.';
                Http::redirigir('cliente-login');
            } catch (DomainException $excepcion) {
                http_response_code(422);
                $error = $excepcion->getMessage();
            } catch (Throwable $excepcion) {
                error_log('Cuenta cliente: falló la eliminación (' . $excepcion::class . ').');
                http_response_code(503);
                $error = 'No pudimos eliminar tu cuenta en este momento. Intentá nuevamente.';
            }
        }
        Vista::mostrar('cliente-cuenta-eliminar', [
            'tituloPagina' => 'Eliminar mi cuenta | Panadería Rustic',
            'error' => $error,
        ]);
    }

    private function requerirCliente(): array
    {
        header('Cache-Control: no-store');
        $cliente = Sesion::cliente();
        if ($cliente === null) {
            Http::redirigir('cliente-login');
        }
        return $cliente;
    }

    private function idDireccion(string $valor): int
    {
        $id = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            throw new DomainException('Seleccioná una dirección válida.');
        }
        return $id;
    }
}
