<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DatosCuenta;
use DomainException;
use PDO;
use PDOException;

final class UsuarioEquipo
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function solicitar(string $nombre, string $cedula, string $email, string $password, string $confirmacion): array
    {
        $nombre = DatosCuenta::nombre($nombre);
        $cedula = trim($cedula);
        if (!preg_match('/^[0-9]{6,8}$/D', $cedula)) {
            throw new DomainException('Ingresá una cédula de 6 a 8 dígitos, sin puntos ni guiones.');
        }
        $email = DatosCuenta::email($email);
        $passwordHash = DatosCuenta::hashPassword($password, $confirmacion);
        try {
            // El servidor fija rol y estado; el formulario no puede conceder permisos.
            $consulta = $this->db->prepare("INSERT INTO usuarios_equipo
                (nombre, cedula, email, password_hash, estado, rol) VALUES (?, ?, ?, ?, 'pendiente', 'empleado')");
            $consulta->execute([$nombre, (int) $cedula, $email, $passwordHash]);
            return ['id_usuario' => (int) $this->db->lastInsertId(), 'nombre' => $nombre, 'email' => $email];
        } catch (PDOException $error) {
            if ((int) ($error->errorInfo[1] ?? 0) === 1062) {
                throw new DomainException('Ya existe una cuenta o solicitud con esa cédula o correo.');
            }
            throw $error;
        }
    }
}
