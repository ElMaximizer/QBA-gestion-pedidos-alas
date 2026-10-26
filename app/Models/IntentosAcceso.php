<?php
declare(strict_types=1);

namespace App\Models;

use DomainException;
use InvalidArgumentException;
use PDO;
use Throwable;

/** Límite persistente compartido por ambos accesos, con contadores independientes. */
final class IntentosAcceso
{
    private readonly string $tabla;
    private readonly string $columna;

    public function __construct(private readonly PDO $db, string $ambito)
    {
        // Los identificadores SQL salen de esta lista cerrada, nunca de la solicitud HTTP.
        [$this->tabla, $this->columna] = match ($ambito) {
            'cliente' => ['intentos_acceso_cliente', 'clave_email'],
            'equipo' => ['intentos_acceso_equipo', 'clave_cedula'],
            default => throw new InvalidArgumentException('Ámbito de acceso inválido.'),
        };
    }

    /** $comprobar devuelve la cuenta sin contraseña o null cuando las credenciales no coinciden. */
    public function autenticar(string $identidad, string $mensajeError, callable $comprobar): array
    {
        $clave = hash('sha256', $identidad);
        $tabla = $this->tabla;
        $columna = $this->columna;
        $this->db->exec("DELETE FROM $tabla WHERE actualizado_en < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY) LIMIT 100");
        $errorAcceso = '';
        $usuario = null;
        $this->db->beginTransaction();
        try {
            $consulta = $this->db->prepare("INSERT INTO $tabla ($columna, actualizado_en)
                VALUES (?, UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE $columna = VALUES($columna)");
            $consulta->execute([$clave]);
            $consulta = $this->db->prepare("SELECT intentos, bloqueado_hasta,
                bloqueado_hasta > UTC_TIMESTAMP() AS bloqueado,
                actualizado_en < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 10 MINUTE) AS vencido
                FROM $tabla WHERE $columna = ? FOR UPDATE");
            $consulta->execute([$clave]);
            $limite = $consulta->fetch();
            if ($limite['bloqueado']) {
                $errorAcceso = 'Esperá un minuto antes de volver a intentarlo.';
            } else {
                $usuario = $comprobar();
                if ($usuario === null) {
                    $intentos = $limite['bloqueado_hasta'] !== null || $limite['vencido'] ? 1 : (int) $limite['intentos'] + 1;
                    $consulta = $this->db->prepare("UPDATE $tabla SET intentos = ?,
                        bloqueado_hasta = IF(? >= 6, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 1 MINUTE), NULL),
                        actualizado_en = UTC_TIMESTAMP() WHERE $columna = ?");
                    $consulta->execute([$intentos, $intentos, $clave]);
                    $errorAcceso = $mensajeError;
                } else {
                    $consulta = $this->db->prepare("DELETE FROM $tabla WHERE $columna = ?");
                    $consulta->execute([$clave]);
                }
            }
            // Se conservan los fallos y el bloqueo antes de informar el error.
            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
        if ($errorAcceso !== '') {
            throw new DomainException($errorAcceso);
        }
        return $usuario;
    }
}
