<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Http;
use App\Core\Sesion;
use App\Core\Vista;
use App\Models\ConsultaPedidos;
use App\Core\Conexion;

final class PedidosEquipoController
{
    public function index(): void
    {
        header('Cache-Control: no-store');
        Sesion::requerirEquipo();

        $busqueda = Http::texto($_GET, 'q');
        $estado = Http::texto($_GET, 'estado');
        $fecha = Http::texto($_GET, 'fecha');
        $pagina = filter_var(Http::texto($_GET, 'pagina', '1'), FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]);
        if (!$pagina) {
            throw new \DomainException('Seleccioná una página válida.');
        }

        $resultado = (new ConsultaPedidos(Conexion::obtener()))->listar($busqueda, $estado, $fecha, $pagina);
        Vista::mostrar('equipo-pedidos', $resultado + [
            'tituloPagina' => 'Consultar pedidos | Panadería Alas',
            'seccionActiva' => 'pedidos',
            'layoutEquipo' => true,
            'busqueda' => $busqueda,
            'estado' => $estado,
            'fecha' => $fecha,
        ]);
    }
}
