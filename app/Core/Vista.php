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
        $esGestionProducto = in_array(
            $vista,
            ['equipo-productos', 'equipo-producto-form', 'equipo-producto-eliminar'],
            true,
        );
        $esGestionStock = $vista === 'equipo-stock';
        $esPersonalEquipo = $vista === 'equipo-personal';
        $esPanel =
            ($variables['layoutEquipo'] ?? false) ||
            in_array($vista, ['equipo-panel', 'equipo-contenido-inicio'], true) ||
            $esGestionProducto ||
            $esGestionStock ||
            $esPersonalEquipo;
        $esRegistro = in_array(
            $vista,
            [
                'cliente-login',
                'cliente-registro',
                'cliente-verificar',
                'registro-completado',
                'equipo-registro',
                'equipo-solicitud-enviada',
                'equipo-login',
            ],
            true,
        );
        $estilos = [];
        if ($vista !== 'inicio') {
            $estilos[] = 'css/componentes.css';

            if ($esPanel) {
                $estilos[] = 'css/panel.css';
            } elseif ($esRegistro) {
                $estilos[] = 'css/registro.css';
            } else {
                $estilos[] = 'css/pedidos.css';
            }
        }

        $scripts = [];
        if (
            in_array(
                $vista,
                ['cliente-login', 'cliente-registro', 'equipo-registro', 'equipo-login', 'cliente-cuenta', 'cliente-cuenta-eliminar'],
                true,
            )
        ) {
            $scripts[] = 'js/registro.js';
        }

        $estilos[] = 'css/navegacion.css';
        if (in_array($vista, ['cliente-cuenta', 'cliente-cuenta-eliminar'], true)) {
            $estilos[] = 'css/cuenta-cliente.css';
        }
        $scripts[] = 'js/navegacion.js';

        if (!$esPanel) {
            $estilos[] = 'css/carrito.css';
            $scripts[] = 'js/carrito.js';
        }

        if ($vista === 'realizar-pedido') {
            $estilos[] = 'css/entrega-pedido.css';
            $scripts[] = 'js/entrega-pedido.js';
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

        if ($vista === 'cliente-pedidos') {
            $estilos[] = 'css/cliente-pedidos.css';
        }

        if (in_array($vista, ['pedido-confirmado', 'cliente-pedidos', 'cliente-transferencia'], true)) {
            $estilos[] = 'css/transferencia-pedido.css';
            $scripts[] = 'js/transferencia-pedido.js';
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
            $scripts[] = 'js/productos.js';
        }

        if ($vista === 'equipo-catalogo') {
            $estilos[] = 'css/equipo-catalogo.css';
        }

        if ($esGestionStock) {
            $estilos[] = 'css/equipo-stock.css';
            $scripts[] = 'js/equipo-stock.js';
        }

        if (in_array($vista, ['equipo-entregas-mercaderia', 'equipo-mercaderia-form'], true)) {
            $estilos[] = 'css/mercaderia.css';
            $scripts[] = 'js/mercaderia.js';
        }

        if (in_array($vista, ['equipo-pedidos', 'equipo-pedido-cancelacion'], true)) {
            $estilos[] = 'css/equipo-pedidos.css';
            $scripts[] = 'js/pedido-equipo.js';
        }

        if ($vista === 'equipo-ventas') {
            $estilos[] = 'css/equipo-ventas.css';
            $scripts[] = 'js/equipo-ventas.js';
        }

        if ($vista === 'equipo-pedido-form') {
            $estilos[] = 'css/equipo-pedido-form.css';
            $scripts[] = 'js/pedido-equipo.js';
        }

        if ($vista === 'equipo-pedido-detalle') {
            $estilos[] = 'css/equipo-pedido-detalle.css';
        }

        if ($esPersonalEquipo) {
            $estilos[] = 'css/equipo-personal.css';
            $scripts[] = 'js/equipo-personal.js';
        }

        $estilos[] = 'css/tema-rustic.css';
        $estilos[] = 'css/tema-rustic-americana.css';

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
            'escapar' => static function (mixed $texto): string {
                return htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            },
            'dinero' => static function (int $centavos, bool $mostrarCentavos = false): string {
                $decimales = $mostrarCentavos && $centavos % 100 !== 0 ? 2 : 0;
                return '$ ' . number_format($centavos / 100, $decimales, ',', '.');
            },
            'url' => static function (string $ruta, array $parametros = []): string {
                return Rutas::url($ruta, $parametros);
            },
            'recurso' => static function (string $archivo): string {
                return Rutas::recurso($archivo);
            },
            'clienteActual' => Sesion::cliente(),
            'equipoActual' => Sesion::equipo(),
            'csrf' => Sesion::csrf(),
            'cantidadCarrito' => Carrito::unidades(),
        ];
    }
}
