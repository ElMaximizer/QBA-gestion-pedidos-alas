<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\Carrito;

final class Vista
{
    public static function mostrar(string $vista, array $variables = []): void
    {
        extract(self::contexto(), EXTR_SKIP);
        $administraProductos = Sesion::administraProductos();
        $seccionActiva = $variables['seccionActiva'] ?? '';
        $esGestionProducto = in_array($vista, ['equipo-productos', 'equipo-producto-form', 'equipo-producto-eliminar'], true);
        $esGestionStock = $vista === 'equipo-stock';
        $esPersonalEquipo = $vista === 'equipo-personal';
        $esPanel = ($variables['layoutEquipo'] ?? false) || in_array($vista, ['equipo-panel', 'equipo-contenido-inicio'], true)
            || $esGestionProducto || $esGestionStock || $esPersonalEquipo;
        $esRegistro = in_array($vista, ['cliente-login', 'cliente-registro', 'cliente-verificar', 'registro-completado',
            'equipo-registro', 'equipo-solicitud-enviada', 'equipo-login'], true);
        $estilos = $vista === 'inicio' ? [] : ['css/componentes.css',
            $esPanel ? 'css/panel.css' : ($esRegistro ? 'css/registro.css' : 'css/pedidos.css')];
        $scripts = in_array($vista, ['cliente-login', 'cliente-registro', 'equipo-registro', 'equipo-login'], true) ? ['js/registro.js'] : [];

        $estilos[] = 'css/navegacion.css';
        $scripts[] = 'js/navegacion.js';
        if (!$esPanel) {
            $estilos[] = 'css/carrito.css';
            $scripts[] = 'js/carrito.js';
        }

        if ($vista === 'inicio') {
            $scripts[] = 'js/carrusel-inicio.js';
        }

        if ($vista === 'equipo-contenido-inicio') {
            $estilos[] = 'css/equipo-contenido-inicio.css';
        }

        if (in_array($vista, ['equipo-consultas', 'equipo-solicitudes'], true)) {
            $estilos[] = 'css/bandeja-equipo.css';
        }

        if ($vista === 'nosotros') {
            $estilos[] = 'css/nosotros.css';
        }

        if (in_array($vista, ['aviso-legal', 'como-comprar'], true)) {
            $estilos[] = 'css/informacion.css';
        }

        if (in_array($vista, ['contacto', 'contacto-enviado'], true)) {
            $estilos[] = 'css/contacto.css';
        }

        if ($esGestionProducto) {
            $estilos[] = 'css/gestion-productos.css';
        }

        if ($esGestionStock) {
            $estilos[] = 'css/equipo-stock.css';
        }

        if (in_array($vista, ['equipo-entregas-mercaderia', 'equipo-mercaderia-form'], true)) {
            $estilos[] = 'css/mercaderia.css';
            if ($vista === 'equipo-mercaderia-form') $scripts[] = 'js/mercaderia.js';
        }

        if ($vista === 'equipo-pedidos') {
            $estilos[] = 'css/equipo-pedidos.css';
        }

        if ($vista === 'equipo-pedido-form') {
            $estilos[] = 'css/equipo-pedido-form.css';
            $scripts[] = 'js/pedido-equipo.js';
        }

        if ($esPersonalEquipo) {
            $estilos[] = 'css/equipo-personal.css';
        }

        if ($vista === 'equipo-producto-form') {
            $scripts[] = 'js/productos.js';
        }

        $estilos[] = 'css/tema-rustic.css';
        extract($variables, EXTR_SKIP);

        require dirname(__DIR__) . '/Views/partials/encabezado.php';
        require dirname(__DIR__) . '/Views/' . $vista . '.php';
        require dirname(__DIR__) . '/Views/partials/pie.php';
    }

    public static function fragmento(string $vista, array $variables = []): string
    {
        extract(self::contexto(), EXTR_SKIP);
        extract($variables, EXTR_SKIP);
        ob_start();
        try {
            require dirname(__DIR__) . '/Views/' . $vista . '.php';
            return (string) ob_get_clean();
        } catch (\Throwable $error) {
            ob_end_clean();
            throw $error;
        }
    }

    private static function contexto(): array
    {
        return [
            'escapar' => static fn (mixed $texto): string => htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'dinero' => static fn (int $centavos): string => '$ ' . number_format($centavos / 100, $centavos % 100 === 0 ? 0 : 2, ',', '.'),
            'url' => static fn (string $ruta, array $parametros = []): string => Rutas::url($ruta, $parametros),
            'recurso' => static fn (string $archivo): string => Rutas::recurso($archivo),
            'clienteActual' => Sesion::cliente(),
            'equipoActual' => Sesion::equipo(),
            'csrf' => Sesion::csrf(),
            'cantidadCarrito' => Carrito::unidades(),
        ];
    }
}
