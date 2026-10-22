<?php
declare(strict_types=1);

namespace App\Controllers;

final class InicioController
{
    public function index(): void
    {
        $tituloPagina = 'Inicio | Panadería Alas';
        $categorias = [
            ['nombre' => 'Panes', 'etiqueta' => '01 / PANADERÍA', 'descripcion' => 'Integral, centeno y los clásicos de cada mesa.'],
            ['nombre' => 'Bizcochos', 'etiqueta' => '02 / DULCERÍA', 'descripcion' => 'Para el desayuno, la merienda y el antojo.'],
            ['nombre' => 'Tortas', 'etiqueta' => '03 / CELEBRACIONES', 'descripcion' => 'El detalle especial para compartir.'],
        ];

        // Muestras del catálogo original para presentar el diseño sin consultar una base.
        $productosDestacados = [
            ['nombre' => 'Pan integral', 'categoria' => 'Panes', 'descripcion' => 'Pan artesanal de harina integral, por unidad.', 'precio' => '$ 95', 'presentacion' => 'Unidad', 'imagen' => 'pan.svg'],
            ['nombre' => 'Bizcocho de chocolate', 'categoria' => 'Bizcochos', 'descripcion' => 'Un toque dulce para la hora de la merienda.', 'precio' => '$ 65', 'presentacion' => 'Unidad', 'imagen' => 'croissant.svg'],
            ['nombre' => 'Torta de cumpleaños', 'categoria' => 'Tortas', 'descripcion' => 'Una celebración hecha para compartir.', 'precio' => '$ 1.200', 'presentacion' => '8 porciones', 'imagen' => 'torta.svg'],
        ];

        require dirname(__DIR__) . '/Views/inicio.php';
    }
}
