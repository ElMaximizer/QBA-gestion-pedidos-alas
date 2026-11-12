<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DatosCuenta;
use DomainException;
use PDO;
use PDOException;
use Throwable;

final class CuentaCliente
{
    private readonly PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function datos(int $id): array
    {
        $consulta = $this->conexion->prepare('SELECT c.nombre, c.email, c.telefono, c.creado_en,
            p.email AS email_pendiente FROM clientes c
            LEFT JOIN cambios_correo_cliente p ON p.id_cliente = c.id_cliente
            WHERE c.id_cliente = ? AND c.verificado = 1');
        $consulta->execute([$id]);
        $cuenta = $consulta->fetch();
        if (!$cuenta) {
            throw new DomainException('Tu cuenta ya no está disponible. Volvé a iniciar sesión.');
        }
        return $cuenta;
    }

    public function actualizarNombre(int $id, string $version, string $nombre): void
    {
        $nombre = DatosCuenta::nombre($nombre);
        $this->modificar($id, $version, function () use ($id, $nombre): void {
            $consulta = $this->conexion->prepare('UPDATE clientes SET nombre = ? WHERE id_cliente = ?');
            $consulta->execute([$nombre, $id]);
        });
    }

    public function actualizarPerfil(int $id, string $version, string $nombre, string $telefono): void
    {
        $nombre = DatosCuenta::nombre($nombre);
        $telefono = trim($telefono);
        $digitos = preg_replace('/\D/', '', $telefono);
        if ($telefono !== '' && (
            mb_strlen($telefono) > 30 || !preg_match('/^\+?[0-9 ()\-]+$/D', $telefono) ||
            strlen($digitos) < 6 || strlen($digitos) > 15
        )) {
            throw new DomainException('Ingresá un teléfono válido, con entre 6 y 15 dígitos, o dejalo vacío.');
        }
        $this->modificar($id, $version, function () use ($id, $nombre, $telefono): void {
            $consulta = $this->conexion->prepare('UPDATE clientes SET nombre = ?, telefono = ? WHERE id_cliente = ?');
            $consulta->execute([$nombre, $telefono, $id]);
        });
    }

    public static function direccionEntrega(array $direccion): string
    {
        $referencias = $direccion['referencias'] ?? '';
        return $direccion['direccion'] . ($referencias !== '' ? ' — Referencias: ' . $referencias : '');
    }

    public function direcciones(int $id): array
    {
        $consulta = $this->conexion->prepare('SELECT id_direccion, alias, direccion, referencias, principal
            FROM direcciones_cliente WHERE id_cliente = ? ORDER BY principal DESC, id_direccion');
        $consulta->execute([$id]);
        return $consulta->fetchAll();
    }

    public function buscarDireccion(int $id, int $idDireccion): ?array
    {
        $consulta = $this->conexion->prepare('SELECT id_direccion, alias, direccion, referencias, principal
            FROM direcciones_cliente WHERE id_direccion = ? AND id_cliente = ?');
        $consulta->execute([$idDireccion, $id]);
        return $consulta->fetch() ?: null;
    }

    public function guardarDireccion(
        int $id,
        string $version,
        string $alias,
        string $direccion,
        ?int $idDireccion = null,
        string $referencias = '',
    ): int {
        $alias = trim($alias);
        $direccion = trim($direccion);
        $referencias = trim($referencias);
        if (
            $alias === '' || !mb_check_encoding($alias, 'UTF-8') || mb_strlen($alias) > 50 ||
            $direccion === '' || !mb_check_encoding($direccion, 'UTF-8') || mb_strlen($direccion) > 250
        ) {
            throw new DomainException('Completá el nombre de la dirección (hasta 50 caracteres) y la dirección completa (hasta 250).');
        }
        if (!mb_check_encoding($referencias, 'UTF-8') || mb_strlen($referencias) > 120 ||
            mb_strlen(self::direccionEntrega(['direccion' => $direccion, 'referencias' => $referencias])) > 250
        ) {
            throw new DomainException('Las referencias admiten hasta 120 caracteres; la dirección completa con sus referencias debe caber en 250.');
        }
        return $this->modificar($id, $version, function () use ($id, $alias, $direccion, $referencias, $idDireccion): int {
            if ($idDireccion !== null) {
                $this->exigirDireccion($id, $idDireccion);
                $consulta = $this->conexion->prepare('UPDATE direcciones_cliente SET alias = ?,
                    direccion = ?, referencias = ? WHERE id_direccion = ? AND id_cliente = ?');
                $consulta->execute([$alias, $direccion, $referencias, $idDireccion, $id]);
                return $idDireccion;
            }
            $consulta = $this->conexion->prepare('SELECT COUNT(*) FROM direcciones_cliente WHERE id_cliente = ?');
            $consulta->execute([$id]);
            $principal = (int) $consulta->fetchColumn() === 0 ? 1 : 0;
            $consulta = $this->conexion->prepare('INSERT INTO direcciones_cliente
                (id_cliente, alias, direccion, referencias, principal) VALUES (?, ?, ?, ?, ?)');
            $consulta->execute([$id, $alias, $direccion, $referencias, $principal]);
            return (int) $this->conexion->lastInsertId();
        });
    }

    public function direccionPrincipal(int $id, string $version, int $idDireccion): void
    {
        $this->modificar($id, $version, function () use ($id, $idDireccion): void {
            $this->exigirDireccion($id, $idDireccion);
            $consulta = $this->conexion->prepare('UPDATE direcciones_cliente SET principal =
                (id_direccion = ?) WHERE id_cliente = ?');
            $consulta->execute([$idDireccion, $id]);
        });
    }

    public function eliminarDireccion(int $id, string $version, int $idDireccion): void
    {
        $this->modificar($id, $version, function () use ($id, $idDireccion): void {
            $direccion = $this->exigirDireccion($id, $idDireccion);
            $consulta = $this->conexion->prepare('DELETE FROM direcciones_cliente
                WHERE id_direccion = ? AND id_cliente = ?');
            $consulta->execute([$idDireccion, $id]);
            if ((int) $direccion['principal'] === 1) {
                $consulta = $this->conexion->prepare('UPDATE direcciones_cliente SET principal = 1
                    WHERE id_cliente = ? ORDER BY id_direccion LIMIT 1');
                $consulta->execute([$id]);
            }
        });
    }

    private function exigirDireccion(int $id, int $idDireccion): array
    {
        $direccion = $this->buscarDireccion($id, $idDireccion);
        if ($direccion === null) {
            throw new DomainException('La dirección ya no está disponible. Volvé a revisar tus direcciones.');
        }
        return $direccion;
    }

    public function solicitarCodigoPassword(int $id, string $version, string $actual, string $nueva, string $confirmacion): array
    {
        $passwordHash = DatosCuenta::hashPassword($nueva, $confirmacion);
        $codigo = (string) random_int(100000, 999999);
        $hash = password_hash($codigo, PASSWORD_DEFAULT);
        return $this->conPassword($id, $version, $actual, function (array $cuenta) use ($id, $version, $codigo, $hash, $passwordHash): array {
            $consulta = $this->conexion->prepare('SELECT enviado_en > DATE_SUB(UTC_TIMESTAMP(),
                INTERVAL 1 MINUTE) FROM codigos_password_cliente WHERE id_cliente = ? FOR UPDATE');
            $consulta->execute([$id]);
            if ($consulta->fetchColumn()) {
                throw new DomainException('Esperá un minuto antes de pedir otro código.');
            }
            $consulta = $this->conexion->prepare('INSERT INTO codigos_password_cliente
                (id_cliente, version_acceso, codigo_hash, password_pendiente_hash, vence_en, enviado_en)
                VALUES (?, ?, ?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 10 MINUTE), UTC_TIMESTAMP())
                ON DUPLICATE KEY UPDATE version_acceso = VALUES(version_acceso), codigo_hash = VALUES(codigo_hash),
                    password_pendiente_hash = VALUES(password_pendiente_hash),
                    vence_en = VALUES(vence_en), enviado_en = VALUES(enviado_en), intentos = 0');
            $consulta->execute([$id, $version, $hash, $passwordHash]);
            return ['email' => $cuenta['email'], 'nombre' => $cuenta['nombre'], 'codigo' => $codigo];
        });
    }

    private function quitarCodigoPassword(int $id): void
    {
        // Se conserva enviado_en para que cancelar o completar no eluda el límite de envíos.
        $consulta = $this->conexion->prepare('UPDATE codigos_password_cliente SET codigo_hash = NULL,
            password_pendiente_hash = NULL, vence_en = NULL WHERE id_cliente = ?');
        $consulta->execute([$id]);
    }

    public function passwordPendiente(int $id, string $version): bool
    {
        $consulta = $this->conexion->prepare('SELECT version_acceso FROM codigos_password_cliente
            WHERE id_cliente = ? AND password_pendiente_hash IS NOT NULL AND codigo_hash IS NOT NULL');
        $consulta->execute([$id]);
        $guardada = $consulta->fetchColumn();
        return is_string($guardada) && hash_equals($guardada, $version);
    }

    public function cancelarPassword(int $id, string $version): void
    {
        $this->modificar($id, $version, function () use ($id): void {
            $this->quitarCodigoPassword($id);
        });
    }

    public function verificarPassword(int $id, string $version, string $codigo): string
    {
        $resultado = $this->modificar($id, $version, function (array $cuenta) use ($id, $version, $codigo): array {
            $consulta = $this->conexion->prepare('SELECT codigo_hash, password_pendiente_hash, intentos, version_acceso,
                vence_en > UTC_TIMESTAMP() AS vigente FROM codigos_password_cliente WHERE id_cliente = ? FOR UPDATE');
            $consulta->execute([$id]);
            $pendiente = $consulta->fetch();
            if (!$pendiente || !$pendiente['codigo_hash'] || !$pendiente['password_pendiente_hash'] || !$pendiente['vigente'] ||
                (int) $pendiente['intentos'] >= 5 || !hash_equals($pendiente['version_acceso'], $version)
            ) {
                return ['error' => 'Pedí un código vigente al correo de tu cuenta antes de cambiar la contraseña.'];
            }
            if (!preg_match('/^[0-9]{6}$/D', $codigo) || !password_verify($codigo, $pendiente['codigo_hash'])) {
                $consulta = $this->conexion->prepare('UPDATE codigos_password_cliente SET intentos = intentos + 1
                    WHERE id_cliente = ?');
                $consulta->execute([$id]);
                return ['error' => 'El código no coincide. Revisalo; admite hasta cinco intentos.'];
            }
            $hash = $pendiente['password_pendiente_hash'];
            $nonce = bin2hex(random_bytes(32));
            $consulta = $this->conexion->prepare('UPDATE clientes SET password_hash = ?,
                version_sesion = ? WHERE id_cliente = ?');
            $consulta->execute([$hash, $nonce, $id]);
            $this->quitarCorreoPendiente($id);
            $this->quitarCodigoPassword($id);
            $cuenta['password_hash'] = $hash;
            $cuenta['version_sesion'] = $nonce;
            return ['version' => AccesoCliente::version($cuenta)];
        });
        if (isset($resultado['error'])) {
            throw new DomainException($resultado['error']);
        }
        return $resultado['version'];
    }

    public function solicitarCorreo(int $id, string $version, string $password, string $email): array
    {
        $email = DatosCuenta::email($email);
        $codigo = (string) random_int(100000, 999999);
        $hash = password_hash($codigo, PASSWORD_DEFAULT);
        return $this->conPassword($id, $version, $password, function (array $cuenta) use (
            $id, $email, $codigo, $hash,
        ): array {
            if ($email === $cuenta['email']) {
                throw new DomainException('Ingresá un correo diferente del actual.');
            }
            $consulta = $this->conexion->prepare('SELECT id_cliente FROM clientes WHERE email = ?');
            $consulta->execute([$email]);
            if ($consulta->fetchColumn()) {
                throw new DomainException('Ese correo ya está asociado a otra cuenta.');
            }
            $consulta = $this->conexion->prepare('SELECT enviado_en > DATE_SUB(UTC_TIMESTAMP(),
                INTERVAL 1 MINUTE) FROM cambios_correo_cliente WHERE id_cliente = ? FOR UPDATE');
            $consulta->execute([$id]);
            if ($consulta->fetchColumn()) {
                throw new DomainException('Esperá un minuto antes de pedir otro código.');
            }
            $consulta = $this->conexion->prepare('INSERT INTO cambios_correo_cliente
                (id_cliente, email, codigo_hash, vence_en, enviado_en)
                VALUES (?, ?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 10 MINUTE), UTC_TIMESTAMP())
                ON DUPLICATE KEY UPDATE email = VALUES(email), codigo_hash = VALUES(codigo_hash),
                    vence_en = VALUES(vence_en), enviado_en = VALUES(enviado_en), intentos = 0');
            $consulta->execute([$id, $email, $hash]);
            return ['email' => $email, 'nombre' => $cuenta['nombre'], 'codigo' => $codigo];
        });
    }

    public function verificarCorreo(int $id, string $version, string $codigo): string
    {
        if (!preg_match('/^[0-9]{6}$/D', $codigo)) {
            throw new DomainException('Ingresá el código de seis dígitos que enviamos al nuevo correo.');
        }
        $resultado = $this->modificar($id, $version, function (array $cuenta) use ($id, $codigo): array {
            $consulta = $this->conexion->prepare('SELECT email, codigo_hash, intentos,
                vence_en > UTC_TIMESTAMP() AS vigente FROM cambios_correo_cliente
                WHERE id_cliente = ? FOR UPDATE');
            $consulta->execute([$id]);
            $pendiente = $consulta->fetch();
            if (!$pendiente || !$pendiente['email']) {
                return ['error' => 'No tenés un cambio de correo pendiente.'];
            }
            if (!$pendiente['codigo_hash'] || !$pendiente['vigente'] || (int) $pendiente['intentos'] >= 5) {
                return ['error' => 'El código venció o alcanzaste el límite de intentos. Pedí uno nuevo.'];
            }
            if (!password_verify($codigo, $pendiente['codigo_hash'])) {
                $intentos = (int) $pendiente['intentos'] + 1;
                $consulta = $this->conexion->prepare('UPDATE cambios_correo_cliente SET intentos = ?,
                    codigo_hash = IF(? >= 5, NULL, codigo_hash) WHERE id_cliente = ?');
                $consulta->execute([$intentos, $intentos, $id]);
                return ['error' => 'El código no coincide. Revisalo e intentá nuevamente.'];
            }
            $nonce = bin2hex(random_bytes(32));
            $consulta = $this->conexion->prepare('UPDATE clientes SET email = ?,
                version_sesion = ? WHERE id_cliente = ?');
            $consulta->execute([$pendiente['email'], $nonce, $id]);
            $this->quitarCorreoPendiente($id);
            $cuenta['email'] = $pendiente['email'];
            $cuenta['version_sesion'] = $nonce;
            return ['version' => AccesoCliente::version($cuenta)];
        });
        // Conservar el intento incorrecto antes de informar el error al formulario.
        if (isset($resultado['error'])) {
            throw new DomainException($resultado['error']);
        }
        return $resultado['version'];
    }

    public function cancelarCorreo(int $id, string $version): void
    {
        $this->modificar($id, $version, function () use ($id): void {
            $this->quitarCorreoPendiente($id);
        });
    }

    public function eliminar(int $id, string $version, string $password): void
    {
        $hash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
        $email = 'eliminada-' . $id . '-' . bin2hex(random_bytes(12)) . '@cuentas.invalid';
        $this->conPassword($id, $version, $password, function () use ($id, $hash, $email): array {
            // Conservar la fila evita perder los pedidos y comprobantes asociados por claves foráneas.
            $consulta = $this->conexion->prepare("UPDATE clientes SET nombre = 'Cuenta eliminada',
                email = ?, password_hash = ?, telefono = '', verificado = 0, codigo_verificacion_hash = NULL,
                codigo_verificacion_vence = NULL, intentos_verificacion = 0, ultimo_codigo_en = NULL
                WHERE id_cliente = ?");
            $consulta->execute([$email, $hash, $id]);
            $this->quitarCorreoPendiente($id);
            $this->quitarCodigoPassword($id);
            $consulta = $this->conexion->prepare('DELETE FROM direcciones_cliente WHERE id_cliente = ?');
            $consulta->execute([$id]);
            return [];
        });
    }

    private function conPassword(int $id, string $version, string $password, callable $operacion): array
    {
        return (new IntentosAcceso($this->conexion, 'cliente'))->autenticar(
            'cuenta:' . $id,
            'La contraseña actual no es correcta.',
            function () use ($id, $version, $password, $operacion): ?array {
                $cuenta = $this->cuentaBloqueada($id, $version);
                if (!DatosCuenta::passwordCorrecto($password, $cuenta['password_hash'])) {
                    return null;
                }
                return $operacion($cuenta);
            },
        );
    }

    private function modificar(int $id, string $version, callable $operacion): mixed
    {
        $this->conexion->beginTransaction();
        try {
            $cuenta = $this->cuentaBloqueada($id, $version);
            $resultado = $operacion($cuenta);
            $this->conexion->commit();
            return $resultado;
        } catch (Throwable $error) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            if ($error instanceof PDOException && (int) ($error->errorInfo[1] ?? 0) === 1062) {
                throw new DomainException('Ese correo ya está asociado a otra cuenta.');
            }
            throw $error;
        }
    }

    private function cuentaBloqueada(int $id, string $version): array
    {
        $consulta = $this->conexion->prepare('SELECT id_cliente, nombre, email, password_hash, version_sesion
            FROM clientes WHERE id_cliente = ? AND verificado = 1 FOR UPDATE');
        $consulta->execute([$id]);
        $cuenta = $consulta->fetch();
        if (!$cuenta || !hash_equals(AccesoCliente::version($cuenta), $version)) {
            throw new DomainException('Tu cuenta cambió. Volvé a iniciar sesión para continuar.');
        }
        return $cuenta;
    }

    private function quitarCorreoPendiente(int $id): void
    {
        // Mantener el último envío evita eludir el minuto de espera cancelando la solicitud.
        $consulta = $this->conexion->prepare('UPDATE cambios_correo_cliente SET email = NULL,
            codigo_hash = NULL, vence_en = NULL, intentos = 0 WHERE id_cliente = ?');
        $consulta->execute([$id]);
    }
}
