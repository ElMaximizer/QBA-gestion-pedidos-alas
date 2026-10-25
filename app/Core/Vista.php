<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\Carrito;

final class Vista
{
    public static function mostrar(string $vista, array $variables = []): void
    {
        $escapar = static fn (mixed $texto): string => htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $dinero = static fn (int $centavos): string => '$ ' . number_format($centavos / 100, $centavos % 100 === 0 ? 0 : 2, ',', '.');
        $url = static fn (string $ruta, array $parametros = []): string => Rutas::url($ruta, $parametros);
        $recurso = static fn (string $archivo): string => Rutas::recurso($archivo);
        $clienteActual = Sesion::cliente();
        $equipoActual = Sesion::equipo();
        $administraProductos = Sesion::administraProductos();
        $csrf = Sesion::csrf();
        $cantidadCarrito = Carrito::unidades();
        $seccionActiva = $variables['seccionActiva'] ?? '';
        $esGestionProducto = in_array($vista, ['equipo-productos', 'equipo-producto-form', 'equipo-producto-eliminar'], true);
        $esGestionStock = $vista === 'equipo-stock';
        $esPermisosEquipo = $vista === 'equipo-permisos';
        $esPersonalEquipo = $vista === 'equipo-personal';
        $esPanel = ($variables['layoutEquipo'] ?? false) || $vista === 'equipo-panel'
            || $esGestionProducto || $esGestionStock || $esPermisosEquipo || $esPersonalEquipo;
        $esRegistro = in_array($vista, ['cliente-login', 'cliente-registro', 'cliente-verificar', 'registro-completado',
            'equipo-registro', 'equipo-solicitud-enviada', 'equipo-login'], true);
        $estilos = $vista === 'inicio' ? [] : ['css/componentes.css',
            $esPanel ? 'css/panel.css' : ($esRegistro ? 'css/registro.css' : 'css/pedidos.css')];
        $scripts = in_array($vista, ['cliente-login', 'cliente-registro', 'equipo-registro', 'equipo-login'], true) ? ['js/registro.js'] : [];

        if ($esGestionProducto) {
            $estilos[] = 'css/gestion-productos.css';
        }

        if ($esGestionStock) {
            $estilos[] = 'css/equipo-stock.css';
        }

        if ($vista === 'equipo-pedidos') {
            $estilos[] = 'css/equipo-pedidos.css';
        }

        if ($esPermisosEquipo) {
            $estilos[] = 'css/equipo-permisos.css';
        }

        if ($esPersonalEquipo) {
            $estilos[] = 'css/equipo-personal.css';
        }

        if ($vista === 'equipo-producto-form') {
            $scripts[] = 'js/productos.js';
        }

        extract($variables, EXTR_SKIP);

        require dirname(__DIR__) . '/Views/partials/encabezado.php';
        require dirname(__DIR__) . '/Views/' . $vista . '.php';
        require dirname(__DIR__) . '/Views/partials/pie.php';
    }
}
