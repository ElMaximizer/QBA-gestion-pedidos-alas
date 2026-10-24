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
        $url = static fn (string $ruta, array $parametros = []): string => 'index.php?' . http_build_query(['ruta' => $ruta] + $parametros);
        $csrf = Sesion::csrf();
        $cantidadCarrito = Carrito::unidades();
        $seccionActiva = $variables['seccionActiva'] ?? '';
        $estilos = $vista === 'inicio' ? [] : ['css/pedidos.css'];
        extract($variables, EXTR_SKIP);
        require dirname(__DIR__) . '/Views/partials/encabezado.php';
        require dirname(__DIR__) . '/Views/' . $vista . '.php';
        require dirname(__DIR__) . '/Views/partials/pie.php';
    }
}
