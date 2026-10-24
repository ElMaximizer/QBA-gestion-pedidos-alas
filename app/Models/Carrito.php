<?php
declare(strict_types=1);

namespace App\Models;

use DomainException;

final class Carrito
{
    public function __construct(private readonly Producto $productos)
    {
    }

    public static function unidades(): int
    {
        return array_sum($_SESSION['qba']['carrito'] ?? []);
    }

    public static function vaciar(): void
    {
        $_SESSION['qba']['carrito'] = [];
    }

    public function actualizar(string $accion, string $id, string $cantidad): void
    {
        if ($accion === 'vaciar') {
            self::vaciar();
            return;
        }
        $idProducto = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$idProducto || !in_array($accion, ['agregar', 'actualizar', 'quitar'], true)) {
            throw new DomainException('Acción o producto no válido.');
        }
        if ($accion === 'quitar') {
            unset($_SESSION['qba']['carrito'][$idProducto]);
            return;
        }
        $unidades = filter_var($cantidad, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 99]]);
        if (!$unidades || !$this->productos->buscarVarios([$idProducto])) {
            throw new DomainException('Revisá el producto y la cantidad (entre 1 y 99).');
        }
        $carrito = $_SESSION['qba']['carrito'] ?? [];
        $final = $accion === 'agregar' ? ($carrito[$idProducto] ?? 0) + $unidades : $unidades;
        if ($final > 99 || (!isset($carrito[$idProducto]) && count($carrito) >= 50)) {
            throw new DomainException('El máximo es 99 unidades por producto y 50 productos distintos.');
        }
        $_SESSION['qba']['carrito'][$idProducto] = $final;
    }

    public function articulos(bool $exigirDisponibilidad = true): array
    {
        $cantidades = $_SESSION['qba']['carrito'] ?? [];
        $productos = $this->productos->buscarVarios(array_keys($cantidades));
        if ($exigirDisponibilidad && count($cantidades) !== count($productos)) {
            throw new DomainException('Uno de los productos ya no está disponible. Quitalo del carrito antes de continuar.');
        }
        $articulos = [];
        foreach ($cantidades as $id => $cantidad) {
            $producto = $productos[$id] ?? ['id_producto' => $id, 'nombre' => 'Producto no disponible',
                'categoria' => 'No disponible', 'precio' => 0, 'presentacion' => '', 'imagen' => 'pan.svg'];
            $producto['disponible'] = isset($productos[$id]);
            $producto['cantidad'] = $cantidad;
            $producto['subtotal'] = $producto['precio'] * $producto['cantidad'];
            $articulos[] = $producto;
        }
        return $articulos;
    }

    public static function firma(array $articulos): string
    {
        $resumen = [];
        foreach ($articulos as $articulo) {
            $resumen[$articulo['id_producto']] = [$articulo['cantidad'], $articulo['precio']];
        }
        ksort($resumen, SORT_NUMERIC);
        return hash('sha256', json_encode($resumen, JSON_THROW_ON_ERROR));
    }

    public static function cantidades(): array
    {
        return $_SESSION['qba']['carrito'] ?? [];
    }
}
