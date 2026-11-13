<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Http;
use App\Core\Sesion;
use App\Core\Vista;
use App\Models\Producto;
use DomainException;

final class CatalogoEquipoController
{
    public function index(): void
    {
        header('Cache-Control: no-store');
        $usuario = Sesion::requerirEquipo();
        if (!in_array($usuario['rol'], ['empleado', 'administrador', 'dueno'], true)) {
            http_response_code(403);
            Vista::mostrar('error', [
                'tituloPagina' => 'Acceso no permitido',
                'mensaje' => 'La consulta de productos está disponible para el equipo aprobado.',
                'layoutEquipo' => true,
                'rutaVolver' => 'equipo-panel',
                'textoVolver' => 'Volver al panel',
            ]);
            return;
        }

        $busqueda = '';
        $categoria = '';
        $productos = [];
        $error = '';
        try {
            $busqueda = Http::texto($_GET, 'q');
            $categoria = Http::texto($_GET, 'categoria');
            if (
                !mb_check_encoding($busqueda, 'UTF-8')
                || mb_strlen($busqueda) > 120
                || ($categoria !== '' && !in_array($categoria, Producto::CATEGORIAS, true))
            ) {
                throw new DomainException('Revisá la búsqueda y la categoría seleccionada.');
            }
            $productos = Producto::catalogo()->listar($busqueda, $categoria);
        } catch (DomainException $excepcion) {
            http_response_code(422);
            $error = $excepcion->getMessage();
        }

        Vista::mostrar('equipo-catalogo', [
            'tituloPagina' => 'Consultar productos | Panadería Rustic',
            'layoutEquipo' => true,
            'seccionActiva' => 'productos',
            'busqueda' => $busqueda,
            'categoria' => $categoria,
            'categorias' => Producto::CATEGORIAS,
            'productos' => $productos,
            'error' => $error,
        ]);
    }
}
