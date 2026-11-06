<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DatosCuenta;
use DomainException;
use PDO;
use PDOException;

final class UsuarioEquipo
{
    public const ROLES = [
        'empleado' => 'Empleado',
        'administrador' => 'Administrador',
        'dueno' => 'Dueño',
    ];

    private readonly PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function solicitar(
        string $nombre,
        string $cedula,
        string $email,
        string $password,
        string $confirmacion,
    ): array {
        $nombre = DatosCuenta::nombre($nombre);
        $numero = DatosCuenta::cedula($cedula);
        $email = DatosCuenta::email($email);
        $passwordHash = DatosCuenta::hashPassword($password, $confirmacion);
        try {
            // El servidor fija rol y estado; el formulario no puede conceder permisos.
            $consulta = $this->conexion->prepare("INSERT INTO usuarios_equipo
                (nombre, cedula, email, password_hash, estado, rol) VALUES (?, ?, ?, ?, 'pendiente', 'empleado')");
            $consulta->execute([$nombre, $numero, $email, $passwordHash]);
            return [
                'id_usuario' => (int) $this->conexion->lastInsertId(),
                'nombre' => $nombre,
                'email' => $email,
            ];
        } catch (PDOException $error) {
            if ((int) ($error->errorInfo[1] ?? 0) === 1062) {
                throw new DomainException(
                    'Ya existe una cuenta o solicitud con esa cédula o correo.',
                );
            }
            throw $error;
        }
    }
}
