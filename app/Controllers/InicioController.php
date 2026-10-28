<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Vista;
use App\Models\Producto;

final class InicioController
{
    public function index(): void
    {
        $categorias = [
            ['nombre' => 'Panes', 'etiqueta' => '01 / PANADERÍA', 'descripcion' => 'Integral, centeno y los clásicos de cada mesa.'],
            ['nombre' => 'Bizcochos', 'etiqueta' => '02 / DULCERÍA', 'descripcion' => 'Para el desayuno, la merienda y el antojo.'],
            ['nombre' => 'Tortas', 'etiqueta' => '03 / CELEBRACIONES', 'descripcion' => 'El detalle especial para compartir.'],
        ];

        Vista::mostrar('inicio', ['tituloPagina' => 'Inicio | Panadería Alas', 'seccionActiva' => 'inicio',
            'categorias' => $categorias, 'productosDestacados' => Producto::catalogo()->listar(destacados: true)]);
    }
}
