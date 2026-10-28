<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Conexion;
use App\Core\Http;
use App\Core\Sesion;
use App\Core\Vista;
use App\Models\Carrito;
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
        $datos = ['cliente_nombre' => $cliente['nombre'], 'telefono' => '', 'fecha_retiro' => date('Y-m-d', strtotime('+1 day')),
            'hora_retiro' => '10:00', 'notas' => ''];
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
                    throw new DomainException('El carrito o sus precios cambiaron. Volvé al carrito para revisarlos antes de confirmar.');
                }
                $puedeConfirmar = true;
                foreach (array_keys($datos) as $campo) {
                    $datos[$campo] = Http::texto($_POST, $campo);
                }
                $id = (new Pedido(Conexion::obtener()))->crear($datos, Carrito::cantidades(), $formulario['firma'], $token,
                    (int) $cliente['id_cliente']);
                Sesion::confirmar($token, $id);
                Carrito::vaciar();
                Http::redirigir('pedido-confirmado');
            } catch (DomainException $excepcion) {
                http_response_code(422);
                $error = $excepcion->getMessage();
            } catch (Throwable $excepcion) {
                error_log((string) $excepcion);
                http_response_code(503);
                $error = 'No pudimos guardar el pedido. Tus productos siguen en el carrito; intentá nuevamente.';
            }
        }
        // Conservamos el carrito para que un producto retirado se pueda quitar desde su pantalla.
        $articulos = $carrito->articulos(false);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $puedeConfirmar = !in_array(false, array_column($articulos, 'disponible'), true);
            $token = Sesion::nuevoFormulario(Carrito::firma($articulos));
            if (!$puedeConfirmar) {
                $error = 'Hay productos que ya no están disponibles. Revisá el carrito antes de continuar.';
            }
        }
        Vista::mostrar('realizar-pedido', ['tituloPagina' => 'Realizar pedido | Panadería Rustic', 'datos' => $datos,
            'articulos' => $articulos, 'total' => array_sum(array_column($articulos, 'subtotal')),
            'error' => $error, 'token' => $token, 'puedeConfirmar' => $puedeConfirmar]);
    }

    public function confirmado(): void
    {
        header('Cache-Control: no-store');
        $idPedido = $_SESSION['qba']['pedido_confirmado'] ?? null;
        if (!$idPedido) {
            Http::redirigir('productos');
        }
        Vista::mostrar('pedido-confirmado', ['tituloPagina' => 'Pedido registrado | Panadería Rustic', 'idPedido' => $idPedido]);
    }
}
