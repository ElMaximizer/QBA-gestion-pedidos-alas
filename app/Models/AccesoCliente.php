<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DatosCuenta;
use PDO;

final class AccesoCliente
{
    private readonly PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function autenticar(string $email, string $password): array
    {
        $email = DatosCuenta::email($email);
        return (new IntentosAcceso($this->conexion, 'cliente'))->autenticar(
            $email,
            'Revisá tu correo y contraseña e intentá nuevamente.',
            function () use ($email, $password): ?array {
                $consulta = $this->conexion
                    ->prepare('SELECT id_cliente, nombre, email, password_hash, version_sesion, verificado
                    FROM clientes WHERE email = ? FOR UPDATE');
                $consulta->execute([$email]);
                $cliente = $consulta->fetch();
                $coincide = DatosCuenta::passwordCorrecto(
                    $password,
                    $cliente['password_hash'] ?? null,
                );
                if (!$coincide || !$cliente) {
                    return null;
                }
                if (
                    (int) $cliente['verificado'] === 1 &&
                    password_needs_rehash($cliente['password_hash'], PASSWORD_DEFAULT)
                ) {
                    $consulta = $this->conexion->prepare(
                        'UPDATE clientes SET password_hash = ? WHERE id_cliente = ?',
                    );
                    $cliente['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
                    $consulta->execute([$cliente['password_hash'], $cliente['id_cliente']]);
                }
                $cliente['version_acceso'] = self::version($cliente);
                unset($cliente['password_hash']);
                unset($cliente['version_sesion']);
                return $cliente;
            },
        );
    }

    public function buscarVerificado(int $idCliente): ?array
    {
        $consulta = $this->conexion->prepare('SELECT id_cliente, nombre, email, password_hash, version_sesion FROM clientes
            WHERE id_cliente = ? AND verificado = 1');
        $consulta->execute([$idCliente]);
        $cliente = $consulta->fetch();
        if (!$cliente) {
            return null;
        }
        $cliente['version_acceso'] = self::version($cliente);
        unset($cliente['password_hash']);
        unset($cliente['version_sesion']);
        return $cliente;
    }

    /** Invalida sesiones anteriores al cambiar la contraseña o el correo de acceso. */
    public static function version(array $cliente): string
    {
        return hash(
            'sha256',
            $cliente['password_hash'] . "\0" . $cliente['email'] . "\0" . $cliente['version_sesion'],
        );
    }
}
