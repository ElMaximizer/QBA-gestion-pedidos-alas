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
            [
                'nombre' => 'Rolls y cups',
                'etiqueta' => '01 / DULCES',
                'descripcion' => 'Rolls de canela, dulce de leche y chocolate; cups para cada antojo.',
            ],
            [
                'nombre' => 'Hojaldres y medialunas',
                'etiqueta' => '02 / SALADOS',
                'descripcion' => 'Opciones rellenas y recién horneadas para compartir.',
            ],
            [
                'nombre' => 'Budines',
                'etiqueta' => '03 / MERIENDAS',
                'descripcion' => 'Frutos secos, limón y naranja para acompañar el día.',
            ],
        ];

        $catalogo = Producto::catalogo();
        Vista::mostrar('inicio', [
            'tituloPagina' => 'Inicio | Panadería Rustic',
            'seccionActiva' => 'inicio',
            'categorias' => $categorias,
            'productosDestacados' => $catalogo->listar(destacados: true),
            'productosCarrusel' => $catalogo->listarPortada(),
        ]);
    }
}
