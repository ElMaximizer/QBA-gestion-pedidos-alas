<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Conexion;
use DomainException;
use PDO;
use Throwable;

final class PermisosEquipo
{
    public const ROLES = [
        'empleado' => 'Empleado',
        'administrador' => 'Administrador',
    ];

    public const FUNCIONES = [
        'empleado' => 'Registrar y consultar pedidos, consultar productos, actualizar estados y registrar entregas.',
        'administrador' => 'Administrar productos, controlar el stock, gestionar pedidos, registrar entregas y consultar ventas.',
    ];

    public function __construct(private readonly PDO $db)
    {
    }

    public static function gestion(): self
    {
        return new self(Conexion::obtener());
    }

    public function listar(string $busqueda = '', string $rol = ''): array
    {
        if (!mb_check_encoding($busqueda, 'UTF-8') || mb_strlen($busqueda) > 120
            || !in_array($rol, ['', ...array_keys(self::ROLES)], true)) {
            throw new DomainException('Revisá la búsqueda y el rol seleccionados.');
        }

        $condiciones = "estado = 'aprobado' AND rol IN ('empleado', 'administrador')";
        $parametros = [];
        if ($busqueda !== '') {
            $condiciones .= ' AND (nombre LIKE ? OR email LIKE ?)';
            $parametros[] = '%' . $busqueda . '%';
            $parametros[] = '%' . $busqueda . '%';
        }
        if ($rol !== '') {
            $condiciones .= ' AND rol = ?';
            $parametros[] = $rol;
        }

        $consulta = $this->db->prepare('SELECT id_usuario, nombre, email, rol FROM usuarios_equipo WHERE '
            . $condiciones . " ORDER BY CASE rol WHEN 'administrador' THEN 0 ELSE 1 END, nombre, id_usuario");
        $consulta->execute($parametros);
        $usuarios = $consulta->fetchAll(PDO::FETCH_ASSOC);

        foreach ($usuarios as &$usuario) {
            $usuario['nombre_rol'] = self::ROLES[$usuario['rol']];
            $usuario['funciones'] = self::FUNCIONES[$usuario['rol']];
        }
        unset($usuario);

        return $usuarios;
    }

    public function cambiarRol(int $idUsuario, int $idDueno, string $rolNuevo): void
    {
        if ($idUsuario < 1 || $idDueno < 1 || !isset(self::ROLES[$rolNuevo])) {
            throw new DomainException('Seleccioná una cuenta y un rol válidos.');
        }
        if ($idUsuario === $idDueno) {
            throw new DomainException('No podés cambiar los permisos de tu propia cuenta de dueño.');
        }

        $this->db->beginTransaction();
        try {
            $consulta = $this->db->prepare('SELECT id_usuario, estado, rol FROM usuarios_equipo WHERE id_usuario = ? FOR UPDATE');
            $consulta->execute([$idUsuario]);
            $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

            if (!$usuario || $usuario['estado'] !== 'aprobado'
                || !in_array($usuario['rol'], array_keys(self::ROLES), true)) {
                throw new DomainException('La cuenta no está disponible para cambiar de rol.');
            }
            if ($usuario['rol'] === $rolNuevo) {
                throw new DomainException('La cuenta ya tiene ese rol.');
            }

            $actualizar = $this->db->prepare("UPDATE usuarios_equipo SET rol = ?
                WHERE id_usuario = ? AND estado = 'aprobado' AND rol IN ('empleado', 'administrador')");
            $actualizar->execute([$rolNuevo, $idUsuario]);
            if ($actualizar->rowCount() !== 1) {
                throw new DomainException('No se pudo actualizar el rol. Recargá la página e intentá nuevamente.');
            }

            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }
}
