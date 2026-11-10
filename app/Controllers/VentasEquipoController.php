<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Conexion;
use App\Core\Http;
use App\Core\Sesion;
use App\Core\Vista;
use App\Models\ConsultaVentas;
use DomainException;

final class VentasEquipoController
{
    public function index(): void
    {
        header('Cache-Control: no-store');
        $usuario = Sesion::requerirEquipo();
        if (!in_array($usuario['rol'] ?? '', ['administrador', 'dueno'], true)) {
            http_response_code(403);
            Vista::mostrar('error', [
                'tituloPagina' => 'Acceso restringido | Panadería Rustic',
                'mensaje' => 'Solo administrador y dueño pueden consultar las ventas.',
                'layoutEquipo' => true,
                'rutaVolver' => 'equipo-panel',
                'textoVolver' => 'Volver al panel',
            ]);
            return;
        }

        $hoy = date('Y-m-d');
        $desde = Http::texto($_GET, 'desde', date('Y-m-01'));
        $hasta = Http::texto($_GET, 'hasta', $hoy);
        $error = '';
        $resultado = ['resumen' => [], 'pedidos' => []];
        try {
            $resultado = (new ConsultaVentas(Conexion::obtener()))->consultar($desde, $hasta);
        } catch (DomainException $excepcion) {
            http_response_code(422);
            $error = $excepcion->getMessage();
        }

        Vista::mostrar('equipo-ventas', $resultado + [
            'tituloPagina' => 'Consultar ventas | Panadería Rustic',
            'seccionActiva' => 'ventas',
            'layoutEquipo' => true,
            'desde' => $desde,
            'hasta' => $hasta,
            'error' => $error,
        ]);
    }
}
