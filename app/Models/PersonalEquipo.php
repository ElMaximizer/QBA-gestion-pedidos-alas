<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Conexion;
use DomainException;
use PDO;

final class PersonalEquipo
{
    public const FUNCIONES = [
        'empleado' =>
            'Registrar y consultar pedidos, consultar productos, actualizar estados y registrar entregas.',
        'administrador' =>
            'Administrar productos, controlar el stock, registrar entregas, consultar ventas y gestionar pedidos.',
    ];

    public const ROLES = [
        'empleado' => 'Empleado',
        'administrador' => 'Administrador',
    ];

    private readonly PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public static function directorio(): self
    {
        return new self(Conexion::obtener());
    }

    public function listar(string $busqueda = '', string $rol = '', bool $incluirDesactivados = false): array
    {
        if (
            !mb_check_encoding($busqueda, 'UTF-8') ||
            mb_strlen($busqueda) > 120 ||
            !in_array($rol, ['', ...array_keys(self::ROLES)], true)
        ) {
            throw new DomainException('Revisá la búsqueda y el rol seleccionados.');
        }

        $condiciones = $incluirDesactivados
            ? "estado IN ('aprobado', 'desactivado') AND rol IN ('empleado', 'administrador')"
            : "estado = 'aprobado' AND rol IN ('empleado', 'administrador')";
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

        $consulta = $this->conexion->prepare(
            'SELECT id_usuario, nombre, email, rol, estado FROM usuarios_equipo WHERE ' .
                $condiciones .
                " ORDER BY CASE rol WHEN 'administrador' THEN 0 ELSE 1 END, nombre, id_usuario",
        );
        $consulta->execute($parametros);

        $personas = $consulta->fetchAll(PDO::FETCH_ASSOC);
        foreach ($personas as $indice => $persona) {
            $personas[$indice]['nombre_rol'] = self::ROLES[$persona['rol']];
            $personas[$indice]['funciones'] = self::FUNCIONES[$persona['rol']];
            $personas[$indice]['nombre_estado'] = $persona['estado'] === 'aprobado' ? 'Activa' : 'Desactivada';
        }

        return $personas;
    }
}
