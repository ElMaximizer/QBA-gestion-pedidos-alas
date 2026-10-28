<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Vista;

final class NosotrosController
{
    public function index(): void
    {
        Vista::mostrar('nosotros', [
            'tituloPagina' => 'Nosotros | Panadería Alas',
            'seccionActiva' => 'nosotros',
        ]);
    }
}