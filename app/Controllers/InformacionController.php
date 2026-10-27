<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Vista;

final class InformacionController
{
    public function legal(): void
    {
        Vista::mostrar('aviso-legal', ['tituloPagina' => 'Aviso legal | Panadería Alas']);
    }

    public function comprar(): void
    {
        Vista::mostrar('como-comprar', ['tituloPagina' => 'Cómo comprar | Panadería Alas']);
    }
}
