<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Http;
use App\Core\Vista;
use App\Models\Producto;
use DomainException;

final class ProductoController
{
    public function index(): void
    {
        $busqueda = Http::texto($_GET, 'q');
        $categoria = Http::texto($_GET, 'categoria');
        if (mb_strlen($busqueda) > 120 || ($categoria !== '' && !in_array($categoria, Producto::CATEGORIAS, true))) {
            throw new DomainException('Revisá la búsqueda y la categoría seleccionada.');
        }
        Vista::mostrar('productos', ['tituloPagina' => 'Productos | Panadería Alas', 'seccionActiva' => 'productos',
            'busqueda' => $busqueda, 'categoria' => $categoria, 'categorias' => Producto::CATEGORIAS,
            'productos' => Producto::catalogo()->listar($busqueda, $categoria)]);
    }

    public function ver(): void
    {
        $id = filter_var(Http::texto($_GET, 'id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $producto = $id ? (Producto::catalogo()->buscarVarios([$id])[$id] ?? null) : null;
        if (!$producto) {
            http_response_code(404);
        }
        Vista::mostrar('producto', ['tituloPagina' => ($producto['nombre'] ?? 'Producto no disponible') . ' | Panadería Alas',
            'seccionActiva' => 'productos', 'producto' => $producto]);
    }
}
