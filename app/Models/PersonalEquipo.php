<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Conexion;
use DomainException;
use PDO;

final class PersonalEquipo
{
    public const FUNCIONES = [
        'empleado' => 'Registrar y consultar pedidos, consultar productos, actualizar estados y registrar entregas.',
        'administrador' => 'Administrar productos, controlar el stock, registrar entregas, consultar ventas y gestionar pedidos.',
    ];

    public const ROLES = [
        'empleado' => 'Empleado',
        'administrador' => 'Administrador',
    ];

    public function __construct(private readonly PDO $db)
    {
    }

    public static function directorio(): self
    {
        return new self(Conexion::obtener());
    }

    public function listar(string $busqueda = '', string $rol = ''): array
    {
        if (!mb_check_encoding($busqueda, 'UTF-8') || mb_strlen($busqueda) > 120
            || !in_array($rol, ['', 'empleado', 'administrador'], true)) {
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

        $consulta = $this->db->prepare('SELECT nombre, email, rol FROM usuarios_equipo WHERE '
            . $condiciones . " ORDER BY CASE rol WHEN 'administrador' THEN 0 ELSE 1 END, nombre, id_usuario");
        $consulta->execute($parametros);

        $personas = $consulta->fetchAll();
        foreach ($personas as &$persona) {
            $persona['nombre_rol'] = self::ROLES[$persona['rol']];
            $persona['funciones'] = self::FUNCIONES[$persona['rol']];
        }
        unset($persona);

        return $personas;
    }
}
