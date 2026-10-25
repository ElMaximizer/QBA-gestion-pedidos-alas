<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Sesion;
use App\Core\Vista;
use App\Models\UsuarioEquipo;

final class PanelEquipoController
{
    public function index(): void
    {
        header('Cache-Control: no-store');
        $usuario = Sesion::requerirEquipo();
        // Solo se prepara la presentación: los módulos se incorporarán en sus historias.
        Vista::mostrar('equipo-panel', ['tituloPagina' => 'Panel de administración | Panadería Alas',
            'usuario' => $usuario, 'nombreRol' => UsuarioEquipo::ROLES[$usuario['rol']]]);
    }
}
