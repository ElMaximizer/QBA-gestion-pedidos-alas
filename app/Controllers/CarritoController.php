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
        header('Cache-Control: no-store');
        header('Vary: Accept');
        $esPeticionJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
        $carrito = new Carrito(Producto::catalogo());
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Sesion::validarCsrf($_POST['csrf'] ?? null);
                $carrito->actualizar(Http::texto($_POST, 'accion'), Http::texto($_POST, 'id_producto'), Http::texto($_POST, 'cantidad'));
                if (!$esPeticionJson) {
                    Http::redirigir('carrito');
                }
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
        $datos = ['tituloPagina' => 'Tu carrito | Panadería Alas', 'error' => $error,
            'articulos' => $articulos, 'total' => array_sum(array_column($articulos, 'subtotal')), 'puedeContinuar' => $puedeContinuar];
        if ($esPeticionJson) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['html' => Vista::fragmento('partials/carrito-contenido', $datos),
                'unidades' => Carrito::unidades(), 'csrf' => Sesion::csrf(), 'error' => $error],
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            return;
        }
        Vista::mostrar('carrito', $datos);
    }
}
