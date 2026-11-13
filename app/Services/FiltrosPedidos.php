<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Http;
use App\Models\Pedido;
use DomainException;

/** Contexto permitido del listado, compartido por consulta, detalle y edición. */
final class FiltrosPedidos
{
    public static function leer(array $entrada): array
    {
        $busqueda = Http::texto($entrada, 'q');
        $estado = Http::texto($entrada, 'estado');
        $fecha = Http::texto($entrada, 'fecha');
        $vista = Http::texto($entrada, 'vista', 'cuadricula');
        $pagina = filter_var(Http::texto($entrada, 'pagina', '1'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if (
            !$pagina ||
            !mb_check_encoding($busqueda, 'UTF-8') ||
            mb_strlen($busqueda) > 100 ||
            !in_array($estado, ['', ...Pedido::ESTADOS], true) ||
            !in_array($fecha, ['', 'hoy', 'proximos', 'anteriores'], true)
        ) {
            throw new DomainException('Revisá la búsqueda y los filtros seleccionados.');
        }
        $filtros = array_filter(
            ['q' => $busqueda, 'estado' => $estado, 'fecha' => $fecha],
            static fn(string $valor): bool => $valor !== '',
        );
        if ($vista === 'lista') {
            $filtros['vista'] = 'lista';
        }
        if ($pagina > 1) {
            $filtros['pagina'] = $pagina;
        }
        return $filtros;
    }
}
