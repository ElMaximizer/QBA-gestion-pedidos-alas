<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DatosCuenta;
use DomainException;
use PDO;
use Throwable;

final class RecuperacionContrasena
{
    private readonly PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    /** Devuelve destinatario y código solo cuando existe una cuenta activa y se admite el envío. */
    public function solicitar(string $tipo, string $identidad, string $ip): ?array
    {
        [$identidad, $sqlCuenta] = $this->prepararIdentidad($tipo, $identidad);
        $claveIdentidad = hash('sha256', $tipo . "\0" . $identidad);
        $claveIp = hash('sha256', $ip !== '' ? $ip : 'ip-no-disponible');
        $this->conexion->beginTransaction();
        try {
            $this->conexion->exec("DELETE FROM recuperaciones_password
                WHERE solicitado_en < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY) LIMIT 100");
            $this->conexion->exec("DELETE FROM limites_recuperacion_password
                WHERE ventana_en < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY) LIMIT 100");

            $consulta = $this->conexion->prepare('INSERT INTO limites_recuperacion_password
                (clave_ip, solicitudes, ventana_en) VALUES (?, 0, UTC_TIMESTAMP())
                ON DUPLICATE KEY UPDATE clave_ip = VALUES(clave_ip)');
            $consulta->execute([$claveIp]);
            $consulta = $this->conexion->prepare('SELECT solicitudes,
                ventana_en < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 HOUR) AS ventana_vencida
                FROM limites_recuperacion_password WHERE clave_ip = ? FOR UPDATE');
            $consulta->execute([$claveIp]);
            $limiteIp = $consulta->fetch();
            $solicitudesIp = (bool) $limiteIp['ventana_vencida'] ? 0 : (int) $limiteIp['solicitudes'];
            if ($solicitudesIp >= 10) {
                $this->conexion->commit();
                return null;
            }
            $consulta = $this->conexion->prepare('UPDATE limites_recuperacion_password SET
                solicitudes = ?, ventana_en = IF(?, UTC_TIMESTAMP(), ventana_en) WHERE clave_ip = ?');
            $consulta->execute([$solicitudesIp + 1, (int) (bool) $limiteIp['ventana_vencida'], $claveIp]);

            $consulta = $this->conexion->prepare('INSERT INTO recuperaciones_password
                (tipo, clave_identidad, ventana_envios_en) VALUES (?, ?, UTC_TIMESTAMP())
                ON DUPLICATE KEY UPDATE clave_identidad = VALUES(clave_identidad)');
            $consulta->execute([$tipo, $claveIdentidad]);
            $consulta = $this->conexion->prepare('SELECT solicitado_en, envios,
                solicitado_en IS NOT NULL AND solicitado_en > DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 MINUTE) AS minuto_vigente,
                ventana_envios_en < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY) AS ventana_vencida
                FROM recuperaciones_password WHERE tipo = ? AND clave_identidad = ? FOR UPDATE');
            $consulta->execute([$tipo, $claveIdentidad]);
            $limiteCuenta = $consulta->fetch();
            $envios = (bool) $limiteCuenta['ventana_vencida'] ? 0 : (int) $limiteCuenta['envios'];
            if ((bool) $limiteCuenta['minuto_vigente'] || $envios >= 5) {
                $this->conexion->commit();
                return null;
            }

            $consulta = $this->conexion->prepare($sqlCuenta);
            $consulta->execute([$identidad]);
            $cuenta = $consulta->fetch();
            $codigo = null;
            $idCuenta = null;
            $codigoHash = null;
            $venceEn = null;
            if ($cuenta) {
                $codigo = (string) random_int(100000, 999999);
                $idCuenta = (int) $cuenta['id_cuenta'];
                $codigoHash = password_hash($codigo, PASSWORD_DEFAULT);
                $venceEn = 'DATE_ADD(UTC_TIMESTAMP(), INTERVAL 10 MINUTE)';
            }
            $consulta = $this->conexion->prepare('UPDATE recuperaciones_password SET
                id_cuenta = ?, codigo_hash = ?, solicitado_en = UTC_TIMESTAMP(),
                vence_en = ' . ($venceEn ?? 'NULL') . ', intentos = 0, envios = ?,
                ventana_envios_en = IF(?, UTC_TIMESTAMP(), ventana_envios_en)
                WHERE tipo = ? AND clave_identidad = ?');
            $consulta->execute([
                $idCuenta,
                $codigoHash,
                $envios + 1,
                (int) (bool) $limiteCuenta['ventana_vencida'],
                $tipo,
                $claveIdentidad,
            ]);
            $this->conexion->commit();
            return $cuenta && $codigo !== null ? [
                'email' => $cuenta['email'],
                'nombre' => $cuenta['nombre'],
                'codigo' => $codigo,
            ] : null;
        } catch (Throwable $error) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $error;
        }
    }

    public function restablecer(string $tipo, string $identidad, string $codigo, string $password, string $confirmacion): void
    {
        [$identidad] = $this->prepararIdentidad($tipo, $identidad);
        if (!preg_match('/^[0-9]{6}$/D', $codigo)) {
            throw new DomainException('Ingresá el código de seis dígitos que recibiste por correo.');
        }
        $passwordHash = DatosCuenta::hashPassword($password, $confirmacion);
        $claveIdentidad = hash('sha256', $tipo . "\0" . $identidad);
        $errorCodigo = false;
        $this->conexion->beginTransaction();
        try {
            $consulta = $this->conexion->prepare('SELECT id_cuenta, codigo_hash, intentos,
                vence_en > UTC_TIMESTAMP() AS vigente FROM recuperaciones_password
                WHERE tipo = ? AND clave_identidad = ? FOR UPDATE');
            $consulta->execute([$tipo, $claveIdentidad]);
            $recuperacion = $consulta->fetch();
            if (
                !$recuperacion ||
                !$recuperacion['codigo_hash'] ||
                !(bool) $recuperacion['vigente'] ||
                (int) $recuperacion['intentos'] >= 5 ||
                !(int) $recuperacion['id_cuenta']
            ) {
                $errorCodigo = true;
            } elseif (!password_verify($codigo, $recuperacion['codigo_hash'])) {
                $intentos = (int) $recuperacion['intentos'] + 1;
                $consulta = $this->conexion->prepare('UPDATE recuperaciones_password SET
                    intentos = ?, codigo_hash = IF(? >= 5, NULL, codigo_hash),
                    vence_en = IF(? >= 5, NULL, vence_en)
                    WHERE tipo = ? AND clave_identidad = ?');
                $consulta->execute([$intentos, $intentos, $intentos, $tipo, $claveIdentidad]);
                $errorCodigo = true;
            } else {
                $idCuenta = (int) $recuperacion['id_cuenta'];
                if ($tipo === 'cliente') {
                    $consulta = $this->conexion->prepare('UPDATE clientes SET
                        password_hash = ?, version_sesion = ?
                        WHERE id_cliente = ? AND email = ? AND verificado = 1');
                    $consulta->execute([$passwordHash, bin2hex(random_bytes(32)), $idCuenta, $identidad]);
                } else {
                    $consulta = $this->conexion->prepare("UPDATE usuarios_equipo SET password_hash = ?
                        WHERE id_usuario = ? AND cedula = ? AND estado = 'aprobado'");
                    $consulta->execute([$passwordHash, $idCuenta, (int) $identidad]);
                }
                if ($consulta->rowCount() !== 1) {
                    $errorCodigo = true;
                } else {
                    $consulta = $this->conexion->prepare('DELETE FROM recuperaciones_password
                        WHERE tipo = ? AND clave_identidad = ?');
                    $consulta->execute([$tipo, $claveIdentidad]);
                }
            }
            $this->conexion->commit();
        } catch (Throwable $error) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $error;
        }
        if ($errorCodigo) {
            throw new DomainException('El código no es válido o venció. Pedí uno nuevo e intentá nuevamente.');
        }
    }

    public function verificarCodigo(string $tipo, string $identidad, string $codigo): void
    {
        [$identidad] = $this->prepararIdentidad($tipo, $identidad);
        if (!preg_match('/^[0-9]{6}$/D', $codigo)) {
            throw new DomainException('Ingresá el código de seis dígitos que recibiste por correo.');
        }
        $claveIdentidad = hash('sha256', $tipo . "\0" . $identidad);
        $errorCodigo = false;
        $this->conexion->beginTransaction();
        try {
            $consulta = $this->conexion->prepare('SELECT codigo_hash, intentos,
                vence_en > UTC_TIMESTAMP() AS vigente FROM recuperaciones_password
                WHERE tipo = ? AND clave_identidad = ? FOR UPDATE');
            $consulta->execute([$tipo, $claveIdentidad]);
            $recuperacion = $consulta->fetch();
            if (
                !$recuperacion ||
                !$recuperacion['codigo_hash'] ||
                !(bool) $recuperacion['vigente'] ||
                (int) $recuperacion['intentos'] >= 5
            ) {
                $errorCodigo = true;
            } elseif (!password_verify($codigo, $recuperacion['codigo_hash'])) {
                $intentos = (int) $recuperacion['intentos'] + 1;
                $consulta = $this->conexion->prepare('UPDATE recuperaciones_password SET
                    intentos = ?, codigo_hash = IF(? >= 5, NULL, codigo_hash),
                    vence_en = IF(? >= 5, NULL, vence_en)
                    WHERE tipo = ? AND clave_identidad = ?');
                $consulta->execute([$intentos, $intentos, $intentos, $tipo, $claveIdentidad]);
                $errorCodigo = true;
            }
            $this->conexion->commit();
        } catch (Throwable $error) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $error;
        }
        if ($errorCodigo) {
            throw new DomainException('El código no es válido o venció. Pedí uno nuevo e intentá nuevamente.');
        }
    }

    /** @return array{0:string,1:string} Identidad normalizada y consulta segura para el tipo permitido. */
    private function prepararIdentidad(string $tipo, string $identidad): array
    {
        if ($tipo === 'cliente') {
            $email = DatosCuenta::email($identidad);
            return [$email, 'SELECT id_cliente AS id_cuenta, nombre, email FROM clientes
                WHERE email = ? AND verificado = 1 LIMIT 1'];
        }
        if ($tipo === 'equipo') {
            $cedula = (string) DatosCuenta::cedula($identidad);
            return [$cedula, "SELECT id_usuario AS id_cuenta, nombre, email FROM usuarios_equipo
                WHERE cedula = ? AND estado = 'aprobado' LIMIT 1"];
        }
        throw new DomainException('No se pudo identificar el tipo de cuenta.');
    }
}
