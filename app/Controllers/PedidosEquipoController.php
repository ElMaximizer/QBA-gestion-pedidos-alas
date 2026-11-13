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
        $filtrosListado = $this->leerFiltrosListado();
        $puedeVerTransferencias = in_array($usuario['rol'] ?? null, ['empleado', 'administrador', 'dueno'], true);
        $puedeRegistrarPagoLocal = in_array($usuario['rol'] ?? null, ['empleado', 'administrador', 'dueno'], true);
        $puedeDecidirTransferencias = in_array($usuario['rol'] ?? null, ['administrador', 'dueno'], true);
        $puedeCancelar = in_array($usuario['rol'] ?? null, ['administrador', 'dueno'], true);
        $puedeActualizarEstado = in_array($usuario['rol'] ?? null, ['empleado', 'administrador', 'dueno'], true);
        $error = '';
        $idEstadoEnviado = null;
        $estadoEnviado = '';
        $idPagoEnviado = null;

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
                    $idPagoEnviado = $idPedido;
                    if (!$puedeRegistrarPagoLocal) {
                        http_response_code(403);
                        throw new DomainException('Tu cuenta no puede registrar pagos recibidos en el local.');
                    }
                    (new ConsultaPedidos(Conexion::obtener()))->registrarPagoLocal($idPedido, (int) $usuario['id_usuario']);
                    $_SESSION['qba']['aviso_pedidos'] = 'Pedido #' . $idPedido . ': efectivo recibido registrado. El pago figura como Realizado.';
                } elseif ($accion === 'revisar_transferencia') {
                    $idPagoEnviado = $idPedido;
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
                    $_SESSION['qba']['aviso_pedidos'] = 'Pedido #' . $idPedido . ': ' . ($decision === 'aprobar'
                        ? 'transferencia aprobada. El pago quedó confirmado.'
                        : 'comprobante rechazado. El pago sigue pendiente.');
                } elseif ($accion === 'actualizar_estado') {
                    $idEstadoEnviado = $idPedido;
                    if (!$puedeActualizarEstado) {
                        http_response_code(403);
                        throw new DomainException('Tu cuenta no puede actualizar estados de pedidos.');
                    }
                    $estadoNuevo = Http::texto($_POST, 'estado_nuevo');
                    $estadoEnviado = $estadoNuevo;
                    if ($estadoNuevo === 'Cancelado' && $this->pedirConfirmacionCancelacion($idPedido, $accion, $usuario['rol'], $filtrosListado)) {
                        return;
                    }
                    (new Pedido(Conexion::obtener()))->actualizarEstadoEquipo(
                        $idPedido,
                        $estadoNuevo,
                        (int) $usuario['id_usuario'],
                    );
                    $_SESSION['qba']['aviso_pedidos'] =
                        'Pedido #' . $idPedido . ': estado guardado como «' . $estadoNuevo . '».';
                } elseif ($accion === 'cancelar_pedido') {
                    if (!$puedeCancelar) {
                        http_response_code(403);
                        throw new DomainException('Solo administrador y dueño pueden cancelar pedidos.');
                    }
                    if ($this->pedirConfirmacionCancelacion($idPedido, $accion, $usuario['rol'], $filtrosListado)) {
                        return;
                    }
                    (new Pedido(Conexion::obtener()))->cancelarDesdeEquipo(
                        $idPedido,
                        (int) $usuario['id_usuario'],
                    );
                    $_SESSION['qba']['aviso_pedidos'] = 'Pedido #' . $idPedido . ' cancelado. Sus datos y su historial se conservan.';
                } else {
                    throw new DomainException('Seleccioná una acción válida.');
                }
                Http::redirigir('equipo-pedidos', $filtrosListado);
            } catch (DomainException $excepcion) {
                if (http_response_code() < 400) {
                    http_response_code(422);
                }
                $idPedidoError = $idPagoEnviado ?? $idEstadoEnviado;
                $error = ($idPedidoError !== null ? 'Pedido #' . $idPedidoError . ': ' : '') . $excepcion->getMessage();
            } catch (Throwable $excepcion) {
                error_log('Gestión de pedidos: falló una actualización (' . $excepcion::class . ').');
                http_response_code(503);
                $idPedidoError = $idPagoEnviado ?? $idEstadoEnviado;
                $error = ($idPedidoError !== null ? 'Pedido #' . $idPedidoError . ': ' : '') . 'No pudimos guardar el cambio. Intentá nuevamente.';
            }
        }

        $aviso = $_SESSION['qba']['aviso_pedidos'] ?? '';
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            unset($_SESSION['qba']['aviso_pedidos']);
        }

        $busqueda = $filtrosListado['q'] ?? '';
        $estado = $filtrosListado['estado'] ?? '';
        $fecha = $filtrosListado['fecha'] ?? '';
        $modoVista = $filtrosListado['vista'] ?? 'cuadricula';
        $pagina = $filtrosListado['pagina'] ?? 1;

        $resultado = (new ConsultaPedidos(Conexion::obtener()))->listar(
            $busqueda,
            $estado,
            $fecha,
            $pagina,
        );
        if ($resultado['pagina'] > 1) {
            $filtrosListado['pagina'] = $resultado['pagina'];
        } else {
            unset($filtrosListado['pagina']);
        }
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
                'modoVista' => $modoVista,
                'filtrosListado' => $filtrosListado,
                'puedeVerTransferencias' => $puedeVerTransferencias,
                'puedeRegistrarPagoLocal' => $puedeRegistrarPagoLocal,
                'puedeDecidirTransferencias' => $puedeDecidirTransferencias,
                'puedeActualizarEstado' => $puedeActualizarEstado,
                'esEmpleado' => $usuario['rol'] === 'empleado',
                'puedeVerEstadisticas' => in_array(
                    $usuario['rol'],
                    ['administrador', 'dueno'],
                    true,
                ),
                'puedeRegistrarPedido' => in_array(
                    $usuario['rol'],
                    ['empleado', 'administrador', 'dueno'],
                    true,
                ),
                'estados' => Pedido::ESTADOS,
                'aviso' => $aviso,
                'error' => $error,
                'idEstadoEnviado' => $idEstadoEnviado,
                'estadoEnviado' => $estadoEnviado,
            ],
        );
    }

    private function leerFiltrosListado(): array
    {
        $busqueda = Http::texto($_GET, 'q');
        $estado = Http::texto($_GET, 'estado');
        $fecha = Http::texto($_GET, 'fecha');
        $vista = Http::texto($_GET, 'vista', 'cuadricula');
        $pagina = filter_var(Http::texto($_GET, 'pagina', '1'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if (
            !$pagina ||
            !mb_check_encoding($busqueda, 'UTF-8') ||
            mb_strlen($busqueda) > 100 ||
            !in_array($estado, ['', ...Pedido::ESTADOS], true) ||
            !in_array($fecha, ['', 'hoy', 'proximos', 'anteriores'], true)
        ) {
            throw new DomainException('Revisá la búsqueda y los filtros seleccionados.');
        }
        // Solo se conservan parámetros conocidos; nunca una URL de retorno enviada por el navegador.
        $filtros = array_filter(
            ['q' => $busqueda, 'estado' => $estado, 'fecha' => $fecha],
            static fn(string $valor): bool => $valor !== '',
        );
        if ($vista === 'lista') {
            $filtros['vista'] = 'lista';
        }
        if ($pagina > 1) {
            $filtros['pagina'] = $pagina;
        }
        return $filtros;
    }

    private function pedirConfirmacionCancelacion(int $idPedido, string $accion, string $rol, array $filtrosListado): bool
    {
        if (Http::texto($_POST, 'confirmar_cancelacion') === '1') {
            return false;
        }
        $pedido = (new Pedido(Conexion::obtener()))->buscarGestion($idPedido);
        if ($pedido === null) {
            throw new DomainException('El pedido ya no está disponible. Actualizá el listado.');
        }
        $permitida = $accion === 'cancelar_pedido'
            ? in_array($pedido['estado'], ['Pendiente', 'En preparación', 'Listo para retirar'], true)
            : in_array('Cancelado', Pedido::estadosActualizables($rol, $pedido['estado'], $pedido['modalidad_entrega']), true);
        if (!$permitida) {
            throw new DomainException('Este pedido no se puede cancelar con tu cuenta y su estado actual.');
        }
        Vista::mostrar('equipo-pedido-cancelacion', [
            'tituloPagina' => 'Cancelar pedido #' . $idPedido . ' | Panadería Rustic',
            'seccionActiva' => 'pedidos',
            'layoutEquipo' => true,
            'pedido' => $pedido,
            'accion' => $accion,
            'filtrosListado' => $filtrosListado,
        ]);
        return true;
    }

    public function detalle(): void
    {
        header('Cache-Control: no-store');
        Sesion::requerirEquipo();
        $filtrosListado = $this->leerFiltrosListado();
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
                'parametrosVolver' => $filtrosListado,
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
                'parametrosVolver' => $filtrosListado,
            ]);
            return;
        }

        Vista::mostrar('equipo-pedido-detalle', [
            'tituloPagina' => 'Detalle del pedido #' . $idPedido . ' | Panadería Rustic',
            'seccionActiva' => 'pedidos',
            'layoutEquipo' => true,
            'pedido' => $pedido,
            'filtrosListado' => $filtrosListado,
        ]);
    }
}
