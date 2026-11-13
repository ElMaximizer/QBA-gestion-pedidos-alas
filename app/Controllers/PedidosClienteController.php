<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Conexion;
use App\Core\Http;
use App\Core\Sesion;
use App\Core\Transferencia;
use App\Core\Vista;
use App\Models\ComprobanteTransferencia;
use App\Models\PedidosCliente;
use App\Services\ImagenComprobante;
use DomainException;
use Throwable;

final class PedidosClienteController
{
    public function index(): void
    {
        header('Cache-Control: no-store');
        $cliente = Sesion::cliente();
        if ($cliente === null) {
            Http::redirigir('cliente-login');
        }

        try {
            $pedidos = (new PedidosCliente(Conexion::obtener()))->listar((int) $cliente['id_cliente']);
        } catch (Throwable $error) {
            error_log('HU20: falló la consulta del historial (' . $error::class . ').');
            http_response_code(503);
            Vista::mostrar('error', [
                'tituloPagina' => 'Mis pedidos | Panadería Rustic',
                'mensaje' => 'No pudimos cargar tus pedidos en este momento. Intentá nuevamente.',
                'rutaVolver' => 'inicio',
                'textoVolver' => 'Volver al inicio',
            ]);
            return;
        }

        $aviso = (string) ($_SESSION['qba']['aviso_transferencia_cliente'] ?? '');
        $errorPago = (string) ($_SESSION['qba']['error_transferencia_cliente'] ?? '');
        unset($_SESSION['qba']['aviso_transferencia_cliente'], $_SESSION['qba']['error_transferencia_cliente']);

        Vista::mostrar('cliente-pedidos', [
            'tituloPagina' => 'Mis pedidos | Panadería Rustic',
            'seccionActiva' => 'pedidos',
            'pedidos' => $pedidos,
            'transferencia' => Transferencia::configuracion(),
            'avisoTransferencia' => $aviso,
            'errorTransferencia' => $errorPago,
        ]);
    }

    public function transferencia(): void
    {
        header('Cache-Control: no-store');
        $cliente = Sesion::cliente();
        if ($cliente === null) {
            Http::redirigir('cliente-login');
        }
        $id = filter_var(Http::texto($_GET, 'id'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        try {
            $pedidos = (new PedidosCliente(Conexion::obtener()))->listar((int) $cliente['id_cliente']);
        } catch (Throwable $error) {
            error_log('HU18: falló la consulta de la transferencia (' . $error::class . ').');
            http_response_code(503);
            Vista::mostrar('error', [
                'tituloPagina' => 'Transferencia | Panadería Rustic',
                'mensaje' => 'No pudimos cargar el pedido en este momento. Intentá nuevamente.',
                'rutaVolver' => 'cliente-pedidos',
                'textoVolver' => 'Volver a mis pedidos',
            ]);
            return;
        }
        $pedido = null;
        foreach ($pedidos as $candidato) {
            if ($candidato['id_pedido'] === $id) {
                $pedido = $candidato;
                break;
            }
        }
        if ($pedido === null || !PedidosCliente::puedeInformarTransferencia($pedido)) {
            http_response_code(404);
            Vista::mostrar('error', [
                'tituloPagina' => 'Transferencia no disponible | Panadería Rustic',
                'mensaje' => 'Seleccioná un pedido propio con una transferencia pendiente de informar.',
                'rutaVolver' => 'cliente-pedidos',
                'textoVolver' => 'Volver a mis pedidos',
            ]);
            return;
        }
        Vista::mostrar('cliente-transferencia', [
            'tituloPagina' => 'Transferencia del pedido #' . $id . ' | Panadería Rustic',
            'transferencia' => Transferencia::configuracion(),
            'idPedidoDialogo' => $id,
            'totalPedidoDialogo' => $pedido['total_centavos'],
            'nombrePedidoDialogo' => $pedido['cliente_nombre'],
        ]);
    }

    public function informarTransferencia(): void
    {
        header('Cache-Control: no-store');
        $cliente = Sesion::cliente();
        if ($cliente === null) {
            Http::redirigir('cliente-login');
        }

        try {
            Sesion::validarCsrf($_POST['csrf'] ?? null);
            $idPedido = filter_var($_POST['id_pedido'] ?? null, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]);
            if ($idPedido === false) {
                throw new DomainException('Seleccioná un pedido válido.');
            }

            $comprobante = ImagenComprobante::leer($_FILES['comprobante'] ?? null);
            (new ComprobanteTransferencia(Conexion::obtener()))->guardar(
                (int) $idPedido,
                (int) $cliente['id_cliente'],
                $comprobante['mime'],
                $comprobante['contenido'],
            );
            $_SESSION['qba']['aviso_transferencia_cliente'] =
                'Avisamos al equipo. El pago queda pendiente de aprobación hasta que confirme la recepción.';
        } catch (DomainException $excepcion) {
            $_SESSION['qba']['error_transferencia_cliente'] = $excepcion->getMessage();
        } catch (Throwable $excepcion) {
            error_log('Transferencia del cliente: falló el aviso (' . $excepcion::class . ').');
            $_SESSION['qba']['error_transferencia_cliente'] =
                'No pudimos avisar al equipo. Intentá nuevamente desde Mis pedidos.';
        }

        Http::redirigir('cliente-pedidos');
    }
}
