<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DatosCuenta;
use DomainException;
use RuntimeException;

final class WhitelistEquipo
{
    private readonly string $archivo;

    public function __construct(?string $archivo = null)
    {
        $this->archivo = $archivo ?? (getenv('QBA_EQUIPO_WHITELIST') ?: dirname(__DIR__, 2) . '/storage/equipo/whitelist.local.json');
    }

    public function listar(): array
    {
        return $this->bloquear(LOCK_SH, fn(): array => $this->leer());
    }

    public function conCedulaHabilitada(string $cedula, callable $solicitar): array
    {
        $numero = self::normalizar($cedula);
        return $this->bloquear(LOCK_SH, function () use ($numero, $solicitar): array {
            if (!in_array($numero, $this->leer(), true)) {
                throw new DomainException('Tu cédula no está habilitada para solicitar acceso. Contactá a la administración.');
            }
            // Mantener la lectura bloqueada hasta crear la solicitud evita competir con una baja.
            return $solicitar();
        });
    }

    public function modificar(string $cedula, bool $agregar): void
    {
        $numero = self::normalizar($cedula);
        $this->bloquear(LOCK_EX, function () use ($numero, $agregar): void {
            $cedulas = $this->leer();
            $posicion = array_search($numero, $cedulas, true);
            if ($agregar) {
                if ($posicion !== false) {
                    throw new DomainException('Esa cédula ya está habilitada.');
                }
                $cedulas[] = $numero;
                if (count($cedulas) > 10000) {
                    throw new DomainException('La whitelist alcanzó el máximo de 10000 cédulas.');
                }
            } else {
                if ($posicion === false) {
                    throw new DomainException('Esa cédula ya no está en la whitelist.');
                }
                unset($cedulas[$posicion]);
                $cedulas = array_values($cedulas);
            }
            sort($cedulas, SORT_STRING);
            $contenido = json_encode(['cedulas' => $cedulas], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
            $temporal = tempnam(dirname($this->archivo), '.whitelist-');
            if ($temporal === false) {
                throw new RuntimeException('No se pudo preparar la whitelist.');
            }
            try {
                chmod($temporal, 0600);
                if (file_put_contents($temporal, $contenido) !== strlen($contenido) || !rename($temporal, $this->archivo)) {
                    throw new RuntimeException('No se pudo guardar la whitelist.');
                }
            } finally {
                if (is_file($temporal)) {
                    unlink($temporal);
                }
            }
        });
    }

    private static function normalizar(string $cedula): string
    {
        // Coincide con la identidad numérica del equipo; los ceros iniciales no crean otra cédula.
        return str_pad((string) DatosCuenta::cedula($cedula), 6, '0', STR_PAD_LEFT);
    }

    private function leer(): array
    {
        if (!file_exists($this->archivo)) {
            return [];
        }
        $contenido = file_get_contents($this->archivo);
        if ($contenido === false || strlen($contenido) > 1024 * 1024) {
            throw new RuntimeException('No se pudo leer la whitelist.');
        }
        $datos = json_decode($contenido, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($datos) || array_keys($datos) !== ['cedulas'] || !is_array($datos['cedulas']) ||
            !array_is_list($datos['cedulas']) || count($datos['cedulas']) > 10000
        ) {
            throw new RuntimeException('Formato de whitelist inválido.');
        }
        $cedulas = [];
        $vistas = [];
        foreach ($datos['cedulas'] as $cedula) {
            if (!is_string($cedula) || self::normalizar($cedula) !== $cedula || isset($vistas[$cedula])) {
                throw new RuntimeException('Cédula de whitelist inválida.');
            }
            $cedulas[] = $cedula;
            $vistas[$cedula] = true;
        }
        return $cedulas;
    }

    private function bloquear(int $modo, callable $operacion): mixed
    {
        $directorio = dirname($this->archivo);
        if (!is_dir($directorio) && !mkdir($directorio, 0700, true) && !is_dir($directorio)) {
            throw new RuntimeException('No se pudo preparar el directorio privado de whitelist.');
        }
        $bloqueo = fopen($this->archivo . '.lock', 'c+b');
        if ($bloqueo === false) {
            throw new RuntimeException('No se pudo abrir el bloqueo de whitelist.');
        }
        try {
            if (!flock($bloqueo, $modo)) {
                throw new RuntimeException('No se pudo bloquear la whitelist.');
            }
            return $operacion();
        } finally {
            flock($bloqueo, LOCK_UN);
            fclose($bloqueo);
        }
    }
}
