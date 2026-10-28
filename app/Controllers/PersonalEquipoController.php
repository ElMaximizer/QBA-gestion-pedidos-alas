<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Http;
use App\Core\Sesion;
use App\Core\Vista;
use App\Models\PersonalEquipo;
use DomainException;

final class PersonalEquipoController
{
    public function index(): void
    {
        header('Cache-Control: no-store');
        $usuario = Sesion::requerirEquipo();
        if (!in_array($usuario['rol'], ['administrador', 'dueno'], true)) {
            http_response_code(403);
            Vista::mostrar('error', [
                'tituloPagina' => 'Acceso no permitido',
                'mensaje' => 'La consulta del personal está disponible para administrador y dueño.',
                'layoutEquipo' => true,
                'rutaVolver' => 'equipo-panel',
                'textoVolver' => 'Volver al panel',
            ]);
            return;
        }

        try {
            $busqueda = Http::texto($_GET, 'q');
            $rol = Http::texto($_GET, 'rol');
            $personas = PersonalEquipo::directorio()->listar($busqueda, $rol);
        } catch (DomainException $excepcion) {
            http_response_code(422);
            Vista::mostrar('error', [
                'tituloPagina' => 'Revisá los filtros',
                'mensaje' => $excepcion->getMessage(),
                'layoutEquipo' => true,
                'rutaVolver' => 'equipo-personal',
                'textoVolver' => 'Volver al personal',
            ]);
            return;
        }

        Vista::mostrar('equipo-personal', [
            'tituloPagina' => 'Personal del equipo | Panadería R U S T I C',
            'seccionActiva' => 'personal',
            'busqueda' => $busqueda,
            'rol' => $rol,
            'personas' => $personas,
        ]);
    }
}
