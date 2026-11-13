<?php
declare(strict_types=1);

namespace App\Core;

final class Transferencia
{
    public static function configuracion(): array
    {
        $archivo = dirname(__DIR__, 2) . '/config/transferencia.local.php';
        if (!is_file($archivo)) {
            return [];
        }

        $datos = require $archivo;
        if (!is_array($datos)) {
            return [];
        }

        return [
            'banco' => trim((string) ($datos['banco'] ?? '')),
            'titular' => trim((string) ($datos['titular'] ?? '')),
            'tipo_cuenta' => trim((string) ($datos['tipo_cuenta'] ?? '')),
            'numero_cuenta' => trim((string) ($datos['numero_cuenta'] ?? '')),
            'alias' => trim((string) ($datos['alias'] ?? '')),
            'moneda' => trim((string) ($datos['moneda'] ?? 'UYU')),
        ];
    }

    public static function configurada(array $datos): bool
    {
        foreach (['banco', 'titular', 'tipo_cuenta', 'numero_cuenta'] as $campo) {
            if (trim((string) ($datos[$campo] ?? '')) === '') {
                return false;
            }
        }

        return true;
    }
}
