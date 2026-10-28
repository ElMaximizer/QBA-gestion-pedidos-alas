<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Http;
use App\Core\Rutas;
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

        if ($usuario['rol'] === 'dueno') {
            $parametros = array_intersect_key($_GET, ['q' => true, 'rol' => true]);
            header('Location: ' . Rutas::url('equipo-permisos', $parametros), true, 303);
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
            'tituloPagina' => 'Personal del equipo | Panadería Rustic',
            'seccionActiva' => 'personal',
            'busqueda' => $busqueda,
            'rol' => $rol,
            'usuarios' => $personas,
            'puedeEditarRoles' => false,
            'rutaPersonal' => 'equipo-personal',
            'aviso' => '',
            'error' => '',
        ]);
    }
}
