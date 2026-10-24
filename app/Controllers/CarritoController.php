<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Http;
use App\Core\Sesion;
use App\Core\Vista;
use App\Models\Carrito;
use App\Models\Producto;
use DomainException;

final class CarritoController
{
    public function index(): void
    {
        $carrito = new Carrito(Producto::catalogo());
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Sesion::validarCsrf($_POST['csrf'] ?? null);
                $carrito->actualizar(Http::texto($_POST, 'accion'), Http::texto($_POST, 'id_producto'), Http::texto($_POST, 'cantidad'));
                Http::redirigir('carrito');
            } catch (DomainException $excepcion) {
                http_response_code(422);
                $error = $excepcion->getMessage();
            }
        }
        $articulos = $carrito->articulos(false);
        $puedeContinuar = !in_array(false, array_column($articulos, 'disponible'), true);
        if (!$puedeContinuar && $error === '') {
            $error = 'Hay productos que ya no están disponibles. Quitalos del carrito para continuar.';
        }
        Vista::mostrar('carrito', ['tituloPagina' => 'Tu carrito | Panadería Alas', 'error' => $error,
            'articulos' => $articulos, 'total' => array_sum(array_column($articulos, 'subtotal')), 'puedeContinuar' => $puedeContinuar]);
    }
}
