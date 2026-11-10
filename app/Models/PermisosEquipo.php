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

    public function listar(string $busqueda = '', string $rol = '', bool $incluirDesactivados = false): array
    {
        return (new PersonalEquipo($this->conexion))->listar($busqueda, $rol, $incluirDesactivados);
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

    public function cambiarEstado(int $idUsuario, int $idDueno, string $accion): void
    {
        if ($idUsuario < 1 || $idDueno < 1 || !in_array($accion, ['desactivar', 'reactivar'], true)) {
            throw new DomainException('Seleccioná una cuenta y una acción válidas.');
        }
        if ($idUsuario === $idDueno) {
            throw new DomainException('No podés modificar el estado de tu propia cuenta de dueño.');
        }

        $this->conexion->beginTransaction();
        try {
            $consulta = $this->conexion->prepare(
                'SELECT id_usuario, estado, rol FROM usuarios_equipo WHERE id_usuario = ? FOR UPDATE',
            );
            $consulta->execute([$idUsuario]);
            $usuario = $consulta->fetch(PDO::FETCH_ASSOC);
            if (!$usuario || !in_array($usuario['rol'], ['empleado', 'administrador'], true)) {
                throw new DomainException('La cuenta no está disponible para esta acción.');
            }
            $estadoEsperado = $accion === 'desactivar' ? 'aprobado' : 'desactivado';
            $estadoNuevo = $accion === 'desactivar' ? 'desactivado' : 'aprobado';
            if ($usuario['estado'] !== $estadoEsperado) {
                throw new DomainException('El estado de la cuenta cambió. Actualizá la lista.');
            }
            $actualizar = $this->conexion->prepare(
                'UPDATE usuarios_equipo SET estado = ? WHERE id_usuario = ? AND estado = ?',
            );
            $actualizar->execute([$estadoNuevo, $idUsuario, $estadoEsperado]);
            if ($actualizar->rowCount() !== 1) {
                throw new DomainException('No se pudo actualizar la cuenta. Recargá e intentá nuevamente.');
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
