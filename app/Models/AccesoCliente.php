<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DatosCuenta;
use PDO;

final class AccesoCliente
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function autenticar(string $email, string $password): array
    {
        $email = DatosCuenta::email($email);
        return (new IntentosAcceso($this->db, 'cliente'))->autenticar($email,
            'Revisá tu correo y contraseña e intentá nuevamente.', function () use ($email, $password): ?array {
                $consulta = $this->db->prepare('SELECT id_cliente, nombre, email, password_hash, verificado
                    FROM clientes WHERE email = ? FOR UPDATE');
                $consulta->execute([$email]);
                $cliente = $consulta->fetch();
                $coincide = DatosCuenta::passwordCorrecto($password, $cliente['password_hash'] ?? null);
                if (!$coincide || !$cliente) {
                    return null;
                }
                if ((int) $cliente['verificado'] === 1 && password_needs_rehash($cliente['password_hash'], PASSWORD_DEFAULT)) {
                    $consulta = $this->db->prepare('UPDATE clientes SET password_hash = ? WHERE id_cliente = ?');
                    $consulta->execute([password_hash($password, PASSWORD_DEFAULT), $cliente['id_cliente']]);
                }
                unset($cliente['password_hash']);
                return $cliente;
            });
    }

    public function buscarVerificado(int $idCliente): ?array
    {
        $consulta = $this->db->prepare('SELECT id_cliente, nombre, email FROM clientes
            WHERE id_cliente = ? AND verificado = 1');
        $consulta->execute([$idCliente]);
        return $consulta->fetch() ?: null;
    }
}
