<?php
declare(strict_types=1);

namespace App\Models;

use DomainException;
use PDO;
use Throwable;

final class AccesoCliente
{
    // También se verifica un hash cuando el correo no existe, para evitar una salida inmediata.
    private const HASH_INEXISTENTE = '$2y$10$mE29SiS9Om07yVEj3hV8yOq6ANKnjGXXhghK9a9lzzxIKuy4CoAMW';

    public function __construct(private readonly PDO $db)
    {
    }

    public function autenticar(string $email, string $password): array
    {
        $email = strtolower(trim($email));
        if (strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('Ingresá un correo electrónico válido.');
        }
        $clave = hash('sha256', $email);
        // Limpieza acotada de registros antiguos: no modifica cuentas ni bloqueos recientes.
        $this->db->exec('DELETE FROM intentos_acceso_cliente
            WHERE actualizado_en < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY) LIMIT 100');
        $errorAcceso = '';
        $cliente = null;
        $this->db->beginTransaction();
        try {
            $consulta = $this->db->prepare('INSERT INTO intentos_acceso_cliente (clave_email, actualizado_en)
                VALUES (?, UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE clave_email = VALUES(clave_email)');
            $consulta->execute([$clave]);
            $consulta = $this->db->prepare('SELECT intentos, bloqueado_hasta,
                bloqueado_hasta > UTC_TIMESTAMP() AS bloqueado,
                actualizado_en < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 10 MINUTE) AS vencido
                FROM intentos_acceso_cliente WHERE clave_email = ? FOR UPDATE');
            $consulta->execute([$clave]);
            $limite = $consulta->fetch();
            if ($limite['bloqueado']) {
                $errorAcceso = 'Esperá un minuto antes de volver a intentarlo.';
            } else {
                $consulta = $this->db->prepare('SELECT id_cliente, nombre, email, password_hash, verificado
                    FROM clientes WHERE email = ? FOR UPDATE');
                $consulta->execute([$email]);
                $cliente = $consulta->fetch();
                $passwordValido = $password !== '' && strlen($password) <= 72 && !str_contains($password, "\0");
                $coincide = password_verify($passwordValido ? $password : '', $cliente['password_hash'] ?? self::HASH_INEXISTENTE);
                if (!$passwordValido || !$cliente || !$coincide) {
                    $intentos = $limite['bloqueado_hasta'] !== null || $limite['vencido'] ? 1 : (int) $limite['intentos'] + 1;
                    $consulta = $this->db->prepare('UPDATE intentos_acceso_cliente SET intentos = ?,
                        bloqueado_hasta = IF(? >= 6, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 1 MINUTE), NULL),
                        actualizado_en = UTC_TIMESTAMP() WHERE clave_email = ?');
                    $consulta->execute([$intentos, $intentos, $clave]);
                    $errorAcceso = 'Revisá tu correo y contraseña e intentá nuevamente.';
                } else {
                    if ((int) $cliente['verificado'] === 1 && password_needs_rehash($cliente['password_hash'], PASSWORD_DEFAULT)) {
                        $consulta = $this->db->prepare('UPDATE clientes SET password_hash = ? WHERE id_cliente = ?');
                        $consulta->execute([password_hash($password, PASSWORD_DEFAULT), $cliente['id_cliente']]);
                    }
                    $consulta = $this->db->prepare('DELETE FROM intentos_acceso_cliente WHERE clave_email = ?');
                    $consulta->execute([$clave]);
                    unset($cliente['password_hash']);
                }
            }
            // El bloqueo y los intentos fallidos persisten antes de devolver el error.
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
        return $cliente;
    }

    public function buscarVerificado(int $idCliente): ?array
    {
        $consulta = $this->db->prepare('SELECT id_cliente, nombre, email FROM clientes
            WHERE id_cliente = ? AND verificado = 1');
        $consulta->execute([$idCliente]);
        return $consulta->fetch() ?: null;
    }
}
