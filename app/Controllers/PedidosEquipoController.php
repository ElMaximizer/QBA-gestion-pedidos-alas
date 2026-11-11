<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Http;
use App\Core\Conexion;
use App\Core\Sesion;
use App\Core\Vista;
use App\Models\ConsultaPedidos;
use App\Models\Pedido;
use DomainException;
use Throwable;

final class PedidosEquipoController
{
    public function index(): void
    {
        header('Cache-Control: no-store');
        $usuario = Sesion::requerirEquipo();
        $puedeVerTransferencias = in_array($usuario['rol'] ?? null, ['empleado', 'administrador', 'dueno'], true);
        $puedeRegistrarPagoLocal = in_array($usuario['rol'] ?? null, ['empleado', 'administrador', 'dueno'], true);
        $puedeDecidirTransferencias = in_array($usuario['rol'] ?? null, ['administrador', 'dueno'], true);
        $puedeCancelar = in_array($usuario['rol'] ?? null, ['administrador', 'dueno'], true);
        $puedeActualizarEstado = in_array($usuario['rol'] ?? null, ['empleado', 'administrador', 'dueno'], true);
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Sesion::validarCsrf($_POST['csrf'] ?? null);
                $accion = Http::texto($_POST, 'accion');
                $idPedido = filter_var(Http::texto($_POST, 'id_pedido'), FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 1],
                ]);
                if (!$idPedido) {
                    throw new DomainException('Seleccioná un pedido válido.');
                }

                if ($accion === 'registrar_pago_local') {
                    if (!$puedeRegistrarPagoLocal) {
                        http_response_code(403);
                        throw new DomainException('Tu cuenta no puede registrar pagos recibidos en el local.');
                    }
                    (new ConsultaPedidos(Conexion::obtener()))->registrarPagoLocal($idPedido, (int) $usuario['id_usuario']);
                    $_SESSION['qba']['aviso_pedidos'] = 'Se registró el pago en efectivo recibido en el local.';
                } elseif ($accion === 'revisar_transferencia') {
                    if (!$puedeDecidirTransferencias) {
                        http_response_code(403);
                        throw new DomainException('Solo administrador y dueño pueden revisar transferencias.');
                    }
                    $decision = Http::texto($_POST, 'decision');
                    if (!in_array($decision, ['aprobar', 'rechazar'], true)) {
                        throw new DomainException('Seleccioná una decisión válida para el comprobante.');
                    }
                    (new ConsultaPedidos(Conexion::obtener()))->revisarTransferencia(
                        $idPedido,
                        $decision === 'aprobar',
                        (int) $usuario['id_usuario'],
                    );
                    $_SESSION['qba']['aviso_pedidos'] = $decision === 'aprobar'
                        ? 'La transferencia fue aprobada y el pago quedó confirmado.'
                        : 'El comprobante fue rechazado. El cliente puede volver a enviarlo.';
                } elseif ($accion === 'actualizar_estado') {
                    if (!$puedeActualizarEstado) {
                        http_response_code(403);
                        throw new DomainException('Tu cuenta no puede actualizar estados de pedidos.');
                    }
                    $estadoNuevo = Http::texto($_POST, 'estado_nuevo');
                    (new Pedido(Conexion::obtener()))->actualizarEstadoEquipo(
                        $idPedido,
                        $estadoNuevo,
                        (int) $usuario['id_usuario'],
                    );
                    $_SESSION['qba']['aviso_pedidos'] = 'Se guardó el estado del pedido.';
                } elseif ($accion === 'cancelar_pedido') {
                    if (!$puedeCancelar) {
                        http_response_code(403);
                        throw new DomainException('Solo administrador y dueño pueden cancelar pedidos.');
                    }
                    (new Pedido(Conexion::obtener()))->cancelarDesdeEquipo(
                        $idPedido,
                        (int) $usuario['id_usuario'],
                    );
                    $_SESSION['qba']['aviso_pedidos'] = 'El pedido fue cancelado y quedó registrado en el historial.';
                } else {
                    throw new DomainException('Seleccioná una acción válida.');
                }
                Http::redirigir('equipo-pedidos');
            } catch (DomainException $excepcion) {
                if (http_response_code() < 400) {
                    http_response_code(422);
                }
                $error = $excepcion->getMessage();
            } catch (Throwable $excepcion) {
                error_log('Gestión de pedidos: falló una actualización (' . $excepcion::class . ').');
                http_response_code(503);
                $error = 'No pudimos guardar el cambio. Intentá nuevamente.';
            }
        }

        $aviso = $_SESSION['qba']['aviso_pedidos'] ?? '';
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            unset($_SESSION['qba']['aviso_pedidos']);
        }

        $busqueda = Http::texto($_GET, 'q');
        $estado = Http::texto($_GET, 'estado');
        $fecha = Http::texto($_GET, 'fecha');
        $pagina = filter_var(Http::texto($_GET, 'pagina', '1'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if (!$pagina) {
            throw new \DomainException('Seleccioná una página válida.');
        }

        $resultado = (new ConsultaPedidos(Conexion::obtener()))->listar(
            $busqueda,
            $estado,
            $fecha,
            $pagina,
        );
        foreach ($resultado['pedidos'] as $indice => $pedido) {
            $resultado['pedidos'][$indice] = $pedido + [
                'puede_modificar' => Pedido::puedeModificar($usuario['rol'], $pedido['estado']),
                'puede_cancelar' => $puedeCancelar && in_array($pedido['estado'], ['Pendiente', 'En preparación', 'Listo para retirar'], true),
                'estados_actualizables' => Pedido::estadosActualizables(
                    $usuario['rol'],
                    $pedido['estado'],
                    $pedido['modalidad_entrega'],
                ),
            ];
        }
        Vista::mostrar(
            'equipo-pedidos',
            $resultado + [
                'tituloPagina' => 'Consultar pedidos | Panadería Rustic',
                'seccionActiva' => 'pedidos',
                'layoutEquipo' => true,
                'busqueda' => $busqueda,
                'estado' => $estado,
                'fecha' => $fecha,
                'puedeVerTransferencias' => $puedeVerTransferencias,
                'puedeRegistrarPagoLocal' => $puedeRegistrarPagoLocal,
                'puedeDecidirTransferencias' => $puedeDecidirTransferencias,
                'puedeActualizarEstado' => $puedeActualizarEstado,
                'puedeRegistrarPedido' => in_array(
                    $usuario['rol'],
                    ['empleado', 'administrador', 'dueno'],
                    true,
                ),
                'estados' => Pedido::ESTADOS,
                'aviso' => $aviso,
                'error' => $error,
            ],
        );
    }

    public function detalle(): void
    {
        header('Cache-Control: no-store');
        Sesion::requerirEquipo();
        $idPedido = filter_var(Http::texto($_GET, 'id'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if (!$idPedido) {
            http_response_code(404);
            Vista::mostrar('error', [
                'tituloPagina' => 'Pedido no encontrado | Panadería Rustic',
                'mensaje' => 'Seleccioná un pedido válido.',
                'layoutEquipo' => true,
                'rutaVolver' => 'equipo-pedidos',
                'textoVolver' => 'Volver a pedidos',
            ]);
            return;
        }

        $pedido = (new Pedido(Conexion::obtener()))->buscarGestion($idPedido);
        if ($pedido === null) {
            http_response_code(404);
            Vista::mostrar('error', [
                'tituloPagina' => 'Pedido no encontrado | Panadería Rustic',
                'mensaje' => 'No encontramos ese pedido.',
                'layoutEquipo' => true,
                'rutaVolver' => 'equipo-pedidos',
                'textoVolver' => 'Volver a pedidos',
            ]);
            return;
        }

        Vista::mostrar('equipo-pedido-detalle', [
            'tituloPagina' => 'Detalle del pedido #' . $idPedido . ' | Panadería Rustic',
            'seccionActiva' => 'pedidos',
            'layoutEquipo' => true,
            'pedido' => $pedido,
        ]);
    }
}
