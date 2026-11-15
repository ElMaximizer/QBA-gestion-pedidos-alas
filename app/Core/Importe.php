<?php
declare(strict_types=1);

namespace App\Core;

/** Convierte importes decimales no negativos, con hasta dos decimales, sin usar float. */
final class Importe
{
    public static function aCentavos(string $importe): int
    {
        [$entero, $decimal] = array_pad(explode('.', $importe, 2), 2, '0');
        return (int) $entero * 100 + (int) str_pad(substr($decimal, 0, 2), 2, '0');
    }

    public static function aDecimal(int $centavos): string
    {
        return sprintf('%d.%02d', intdiv($centavos, 100), $centavos % 100);
    }
}
