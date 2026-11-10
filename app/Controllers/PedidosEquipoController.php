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
        $puedeRegistrarPago = in_array($usuario['rol'] ?? null, ['administrador', 'dueno'], true);
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

                if ($accion === 'registrar_pago') {
                    if (!$puedeRegistrarPago) {
                        http_response_code(403);
                        throw new DomainException('Solo administrador y dueño pueden registrar pagos.');
                    }
                    $valorPago = $_POST['pago_recibido'] ?? null;
                    if (!is_string($valorPago) || !in_array($valorPago, ['0', '1'], true)) {
                        throw new DomainException('Revisá el estado del pago seleccionado.');
                    }
                    (new ConsultaPedidos(Conexion::obtener()))->registrarPago(
                        $idPedido,
                        $valorPago === '1',
                        (int) $usuario['id_usuario'],
                    );
                    $_SESSION['qba']['aviso_pedidos'] = $valorPago === '1'
                        ? 'Se registró el pago total del pedido.'
                        : 'El pago quedó pendiente nuevamente.';
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
                    $_SESSION['qba']['aviso_pedidos'] = $estadoNuevo === 'Entregado'
                        ? 'Se registró la entrega del pedido.'
                        : 'El estado del pedido fue actualizado.';
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
                'estados_siguientes' => Pedido::siguientesEstados($pedido['estado'], $pedido['modalidad_entrega']),
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
                'puedeRegistrarPago' => $puedeRegistrarPago,
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
