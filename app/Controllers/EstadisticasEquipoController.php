<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Conexion;
use App\Core\Sesion;
use App\Core\Vista;
use App\Models\ConsultaPedidos;

final class EstadisticasEquipoController
{
    public function index(): void
    {
        $usuario = Sesion::requerirEquipo();
        if (!in_array($usuario['rol'] ?? '', ['administrador', 'dueno'], true)) {
            http_response_code(403);
            Vista::mostrar('error', [
                'tituloPagina' => 'Acceso restringido | Panadería Rustic',
                'mensaje' => 'Solo administración y dueño pueden consultar las estadísticas.',
                'layoutEquipo' => true,
                'rutaVolver' => 'equipo-panel',
                'textoVolver' => 'Volver al panel',
            ]);
            return;
        }

        $productos = (new ConsultaPedidos(Conexion::obtener()))->productosMasSolicitados();
        Vista::mostrar('equipo-estadisticas', [
            'tituloPagina' => 'Estadísticas | Panadería Rustic',
            'seccionActiva' => 'estadisticas',
            'layoutEquipo' => true,
            'productosMasSolicitados' => $productos,
        ]);
    }
}
