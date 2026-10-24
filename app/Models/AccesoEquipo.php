<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DatosCuenta;
use DomainException;
use PDO;

final class AccesoEquipo
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function autenticar(string $cedula, string $password): array
    {
        $mensaje = 'Revisá tu cédula y contraseña e intentá nuevamente.';
        try {
            $numero = DatosCuenta::cedula($cedula);
        } catch (DomainException) {
            throw new DomainException($mensaje);
        }
        return (new IntentosAcceso($this->db, 'equipo'))->autenticar((string) $numero, $mensaje,
            function () use ($numero, $password): ?array {
                $consulta = $this->db->prepare('SELECT id_usuario, nombre, cedula, email, password_hash, estado, rol
                    FROM usuarios_equipo WHERE cedula = ? FOR UPDATE');
                $consulta->execute([$numero]);
                $usuario = $consulta->fetch();
                $coincide = DatosCuenta::passwordCorrecto($password, $usuario['password_hash'] ?? null);
                if (!$coincide || !$usuario || !isset(UsuarioEquipo::ROLES[$usuario['rol']])) {
                    return null;
                }
                if ($usuario['estado'] === 'aprobado' && password_needs_rehash($usuario['password_hash'], PASSWORD_DEFAULT)) {
                    $consulta = $this->db->prepare('UPDATE usuarios_equipo SET password_hash = ? WHERE id_usuario = ?');
                    $consulta->execute([password_hash($password, PASSWORD_DEFAULT), $usuario['id_usuario']]);
                }
                unset($usuario['password_hash']);
                return $usuario;
            });
    }

    public function buscarAprobado(int $idUsuario): ?array
    {
        $consulta = $this->db->prepare("SELECT id_usuario, nombre, cedula, email, rol FROM usuarios_equipo
            WHERE id_usuario = ? AND estado = 'aprobado'");
        $consulta->execute([$idUsuario]);
        $usuario = $consulta->fetch();
        return $usuario && isset(UsuarioEquipo::ROLES[$usuario['rol']]) ? $usuario : null;
    }
}
