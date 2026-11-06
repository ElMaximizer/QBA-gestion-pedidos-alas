<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Conexion;
use DomainException;
use PDO;
use Throwable;

final class PermisosEquipo
{
    public const ROLES = PersonalEquipo::ROLES;
    public const FUNCIONES = PersonalEquipo::FUNCIONES;

    private readonly PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public static function gestion(): self
    {
        return new self(Conexion::obtener());
    }

    public function listar(string $busqueda = '', string $rol = ''): array
    {
        return (new PersonalEquipo($this->conexion))->listar($busqueda, $rol);
    }

    public function cambiarRol(int $idUsuario, int $idDueno, string $rolNuevo): void
    {
        if ($idUsuario < 1 || $idDueno < 1 || !isset(self::ROLES[$rolNuevo])) {
            throw new DomainException('Seleccioná una cuenta y un rol válidos.');
        }
        if ($idUsuario === $idDueno) {
            throw new DomainException(
                'No podés cambiar los permisos de tu propia cuenta de dueño.',
            );
        }

        $this->conexion->beginTransaction();
        try {
            $consulta = $this->conexion->prepare(
                'SELECT id_usuario, estado, rol FROM usuarios_equipo WHERE id_usuario = ? FOR UPDATE',
            );
            $consulta->execute([$idUsuario]);
            $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

            if (
                !$usuario ||
                $usuario['estado'] !== 'aprobado' ||
                !in_array($usuario['rol'], array_keys(self::ROLES), true)
            ) {
                throw new DomainException('La cuenta no está disponible para cambiar de rol.');
            }
            if ($usuario['rol'] === $rolNuevo) {
                throw new DomainException('La cuenta ya tiene ese rol.');
            }

            $actualizar = $this->conexion->prepare("UPDATE usuarios_equipo SET rol = ?
                WHERE id_usuario = ? AND estado = 'aprobado' AND rol IN ('empleado', 'administrador')");
            $actualizar->execute([$rolNuevo, $idUsuario]);
            if ($actualizar->rowCount() !== 1) {
                throw new DomainException(
                    'No se pudo actualizar el rol. Recargá la página e intentá nuevamente.',
                );
            }

            $this->conexion->commit();
        } catch (Throwable $error) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $error;
        }
    }
}
