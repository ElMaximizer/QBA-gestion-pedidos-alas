<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DatosCuenta;
use DomainException;
use PDO;
use PDOException;
use Throwable;

final class Cliente
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function registrar(string $nombre, string $email, string $password, string $confirmacion): array
    {
        $nombre = DatosCuenta::nombre($nombre);
        $email = DatosCuenta::email($email);
        $passwordHash = DatosCuenta::hashPassword($password, $confirmacion);
        [$codigo, $codigoHash] = self::nuevoCodigo();
        $this->db->beginTransaction();
        try {
            $consulta = $this->db->prepare('INSERT INTO clientes
                (nombre, email, password_hash, codigo_verificacion_hash, codigo_verificacion_vence, ultimo_codigo_en)
                VALUES (?, ?, ?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 10 MINUTE), UTC_TIMESTAMP())');
            $consulta->execute([$nombre, $email, $passwordHash, $codigoHash]);
            $id = (int) $this->db->lastInsertId();
            $this->db->commit();
            return ['id_cliente' => $id, 'nombre' => $nombre, 'email' => $email, 'codigo' => $codigo];
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            if ($error instanceof PDOException && (int) ($error->errorInfo[1] ?? 0) === 1062) {
                throw new DomainException('Ya existe una cuenta con ese correo. Si está pendiente, verificá tu cuenta.');
            }
            throw $error;
        }
    }

    public function verificar(string $email, string $codigo): int
    {
        $email = DatosCuenta::email($email);
        if (!preg_match('/^[0-9]{6}$/D', $codigo)) {
            throw new DomainException('Ingresá el código de seis dígitos del mensaje de verificación.');
        }
        $this->db->beginTransaction();
        $errorVerificacion = '';
        $idCliente = 0;
        try {
            $consulta = $this->db->prepare('SELECT id_cliente, verificado, codigo_verificacion_hash,
                codigo_verificacion_vence > UTC_TIMESTAMP() AS vigente, intentos_verificacion
                FROM clientes WHERE email = ? FOR UPDATE');
            $consulta->execute([$email]);
            $cliente = $consulta->fetch();
            if (!$cliente || (int) $cliente['verificado'] === 1) {
                $errorVerificacion = 'No encontramos una cuenta pendiente de verificación para ese correo.';
            } elseif (!$cliente['codigo_verificacion_hash'] || !$cliente['vigente'] || (int) $cliente['intentos_verificacion'] >= 5) {
                $errorVerificacion = 'El código venció o alcanzaste el límite de intentos. Pedí uno nuevo.';
            } elseif (!password_verify($codigo, $cliente['codigo_verificacion_hash'])) {
                $intentos = (int) $cliente['intentos_verificacion'] + 1;
                $actualizar = $this->db->prepare('UPDATE clientes SET intentos_verificacion = ?,
                    codigo_verificacion_hash = IF(? >= 5, NULL, codigo_verificacion_hash),
                    codigo_verificacion_vence = IF(? >= 5, NULL, codigo_verificacion_vence) WHERE id_cliente = ?');
                $actualizar->execute([$intentos, $intentos, $intentos, $cliente['id_cliente']]);
                $errorVerificacion = $intentos >= 5
                    ? 'Alcanzaste el límite de intentos. Pedí un nuevo código.'
                    : 'El código no coincide. Revisalo e intentá nuevamente.';
            } else {
                $idCliente = (int) $cliente['id_cliente'];
                $actualizar = $this->db->prepare('UPDATE clientes SET verificado = 1,
                    codigo_verificacion_hash = NULL, codigo_verificacion_vence = NULL,
                    intentos_verificacion = 0 WHERE id_cliente = ?');
                $actualizar->execute([$cliente['id_cliente']]);
            }
            // Los intentos fallidos se conservan antes de informar el error al controlador.
            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
        if ($errorVerificacion !== '') {
            throw new DomainException($errorVerificacion);
        }
        return $idCliente;
    }

    public function reenviar(string $email): ?array
    {
        $email = DatosCuenta::email($email);
        [$codigo, $codigoHash] = self::nuevoCodigo();
        $this->db->beginTransaction();
        try {
            $consulta = $this->db->prepare('SELECT id_cliente, nombre, verificado,
                ultimo_codigo_en > DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 MINUTE) AS enfriamiento
                FROM clientes WHERE email = ? FOR UPDATE');
            $consulta->execute([$email]);
            $cliente = $consulta->fetch();
            if ($cliente && !(int) $cliente['verificado']) {
                if ($cliente['enfriamiento']) {
                    throw new DomainException('Esperá un minuto antes de pedir otro código.');
                }
                $actualizar = $this->db->prepare('UPDATE clientes SET codigo_verificacion_hash = ?,
                    codigo_verificacion_vence = DATE_ADD(UTC_TIMESTAMP(), INTERVAL 10 MINUTE),
                    ultimo_codigo_en = UTC_TIMESTAMP(), intentos_verificacion = 0 WHERE id_cliente = ?');
                $actualizar->execute([$codigoHash, $cliente['id_cliente']]);
            }
            $this->db->commit();
            return $cliente && !(int) $cliente['verificado']
                ? ['email' => $email, 'nombre' => $cliente['nombre'], 'codigo' => $codigo]
                : null;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }

    private static function nuevoCodigo(): array
    {
        $codigo = (string) random_int(100000, 999999);
        return [$codigo, password_hash($codigo, PASSWORD_DEFAULT)];
    }

}
