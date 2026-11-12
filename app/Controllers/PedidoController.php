<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Conexion;
use App\Core\Http;
use App\Core\Sesion;
use App\Core\Transferencia;
use App\Core\Vista;
use App\Models\Carrito;
use App\Models\CuentaCliente;
use App\Models\Pedido;
use App\Models\Producto;
use DomainException;
use Throwable;

final class PedidoController
{
    public function crear(): void
    {
        header('Cache-Control: no-store');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !Carrito::cantidades()) {
            Http::redirigir('carrito');
        }
        $cliente = Sesion::cliente();
        if ($cliente === null) {
            $_SESSION['qba']['continuar_pedido'] = true;
            Http::redirigir('cliente-login');
        }
        $datos = [
            'cliente_nombre' => $cliente['nombre'],
            'telefono' => '',
            'fecha_retiro' => date('Y-m-d', strtotime('+1 day')),
            'hora_retiro' => '10:00',
            'modalidad_entrega' => 'retiro',
            'direccion_entrega' => '',
            'metodo_pago' => 'local',
            'notas' => '',
        ];
        $cuentas = new CuentaCliente(Conexion::obtener());
        $datos['telefono'] = $cuentas->datos((int) $cliente['id_cliente'])['telefono'];
        $direcciones = $cuentas->direcciones((int) $cliente['id_cliente']);
        $direccionSeleccionada = '';
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $direcciones) {
            $direccionSeleccionada = (string) $direcciones[0]['id_direccion'];
            $datos['direccion_entrega'] = CuentaCliente::direccionEntrega($direcciones[0]);
        }
        $error = '';
        $token = '';
        $puedeConfirmar = false;
        $carrito = new Carrito(Producto::catalogo());
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Sesion::validarCsrf($_POST['csrf'] ?? null);
                $token = Http::texto($_POST, 'pedido_token');
                $formulario = Sesion::formulario($token);
                if ($formulario['pedido'] !== null) {
                    Sesion::confirmar($token, $formulario['pedido']);
                    Http::redirigir('pedido-confirmado');
                }
                $articulos = $carrito->articulos();
                if (!hash_equals($formulario['firma'], Carrito::firma($articulos))) {
                    throw new DomainException(
                        'El carrito o sus precios cambiaron. Volvé al carrito para revisarlos antes de confirmar.',
                    );
                }
                $puedeConfirmar = true;
                foreach (array_keys($datos) as $campo) {
                    $datos[$campo] = Http::texto($_POST, $campo);
                }
                $direccionSeleccionada = Http::texto($_POST, 'id_direccion');
                if ($datos['modalidad_entrega'] === 'domicilio' && $direccionSeleccionada !== '') {
                    $idDireccion = filter_var($direccionSeleccionada, FILTER_VALIDATE_INT, [
                        'options' => ['min_range' => 1],
                    ]);
                    $guardada = $idDireccion === false
                        ? null
                        : $cuentas->buscarDireccion((int) $cliente['id_cliente'], $idDireccion);
                    if ($guardada === null) {
                        throw new DomainException('La dirección seleccionada no está disponible. Elegí otra dirección o escribila nuevamente.');
                    }
                    $datos['direccion_entrega'] = CuentaCliente::direccionEntrega($guardada);
                }
                $id = (new Pedido(Conexion::obtener()))->crear(
                    $datos,
                    Carrito::cantidades(),
                    $formulario['firma'],
                    $token,
                    (int) $cliente['id_cliente'],
                );
                $_SESSION['qba']['pedido_confirmado_metodo_pago'] = $datos['metodo_pago'];
                $_SESSION['qba']['pedido_confirmado_total'] = array_sum(array_column($articulos, 'subtotal'));
                $_SESSION['qba']['pedido_confirmado_nombre'] = $datos['cliente_nombre'];
                Sesion::confirmar($token, $id);
                Carrito::vaciar();
                Http::redirigir('pedido-confirmado');
            } catch (DomainException $excepcion) {
                http_response_code(422);
                $error = $excepcion->getMessage();
            } catch (Throwable $excepcion) {
                error_log((string) $excepcion);
                http_response_code(503);
                $error =
                    'No pudimos guardar el pedido. Tus productos siguen en el carrito; intentá nuevamente.';
            }
        }
        // Conservamos el carrito para que un producto retirado se pueda quitar desde su pantalla.
        $articulos = $carrito->articulos(false);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $puedeConfirmar = !in_array(false, array_column($articulos, 'disponible'), true);
            $token = Sesion::nuevoFormulario(Carrito::firma($articulos));
            if (!$puedeConfirmar) {
                $error =
                    'Hay productos que ya no están disponibles. Revisá el carrito antes de continuar.';
            }
        }
        Vista::mostrar('realizar-pedido', [
            'tituloPagina' => 'Realizar pedido | Panadería Rustic',
            'datos' => $datos,
            'articulos' => $articulos,
            'total' => array_sum(array_column($articulos, 'subtotal')),
            'error' => $error,
            'token' => $token,
            'puedeConfirmar' => $puedeConfirmar,
            'direccionesGuardadas' => $direcciones,
            'direccionSeleccionada' => $direccionSeleccionada,
        ]);
    }

    public function confirmado(): void
    {
        header('Cache-Control: no-store');
        $idPedido = $_SESSION['qba']['pedido_confirmado'] ?? null;
        if (!$idPedido) {
            Http::redirigir('productos');
        }
        Vista::mostrar('pedido-confirmado', [
            'tituloPagina' => 'Pedido registrado | Panadería Rustic',
            'idPedido' => $idPedido,
            'metodoPago' => $_SESSION['qba']['pedido_confirmado_metodo_pago'] ?? 'local',
            'transferencia' => Transferencia::configuracion(),
            'totalPedidoCentavos' => (int) ($_SESSION['qba']['pedido_confirmado_total'] ?? 0),
            'nombrePedidoDialogo' => (string) ($_SESSION['qba']['pedido_confirmado_nombre'] ?? ''),
        ]);
    }
}
