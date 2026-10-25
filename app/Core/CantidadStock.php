<?php
declare(strict_types=1);

namespace App\Core;

use DomainException;

/** Cantidades DECIMAL(12,3), calculadas como milésimas enteras. */
final class CantidadStock
{
    public static function validar(mixed $valor, string $campo, bool $positivo = false): string
    {
        $valor = is_string($valor) ? str_replace(',', '.', trim($valor)) : '';
        if (!preg_match('/^[0-9]{1,9}(?:\.[0-9]{1,3})?$/D', $valor)) {
            throw new DomainException(ucfirst($campo) . ': ingresá un número ' . ($positivo ? 'mayor que cero' : 'no negativo') . ' con hasta tres decimales.');
        }
        [$entero, $decimal] = array_pad(explode('.', $valor, 2), 2, '');
        $normalizado = (int) $entero . '.' . str_pad($decimal, 3, '0');
        if ($positivo && $normalizado === '0.000') {
            throw new DomainException('La cantidad recibida debe ser mayor que cero.');
        }
        return $normalizado;
    }

    public static function sumar(string $actual, string $recibido): string
    {
        $total = (int) str_replace('.', '', self::validar($actual, 'stock actual'))
            + (int) str_replace('.', '', self::validar($recibido, 'cantidad recibida', true));
        if ($total > 999999999999) {
            throw new DomainException('La entrega supera el stock máximo permitido de una materia prima.');
        }
        return intdiv($total, 1000) . '.' . str_pad((string) ($total % 1000), 3, '0', STR_PAD_LEFT);
    }
}
