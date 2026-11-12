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
            ? "u.estado IN ('aprobado', 'desactivado') AND u.rol IN ('empleado', 'administrador')"
            : "u.estado = 'aprobado' AND u.rol IN ('empleado', 'administrador')";
        $parametros = [];

        if ($busqueda !== '') {
            $condiciones .= ' AND (u.nombre LIKE ? OR u.email LIKE ?)';
            $parametros[] = '%' . $busqueda . '%';
            $parametros[] = '%' . $busqueda . '%';
        }
        if ($rol !== '') {
            $condiciones .= ' AND u.rol = ?';
            $parametros[] = $rol;
        }

        $sql = 'SELECT u.id_usuario, u.nombre, u.email, u.rol, u.estado,
                COALESCE(registros.total, 0) AS pedidos_registrados,
                COALESCE(cambios.total, 0) AS pedidos_actualizados,
                COALESCE(entregas.total, 0) AS pedidos_entregados,
                COALESCE(mercaderia.total, 0) AS mercaderia_registrada,
                COALESCE(pagos.total, 0) AS pagos_confirmados
            FROM usuarios_equipo u
            LEFT JOIN (
                SELECT id_usuario_registro AS id_usuario, COUNT(*) AS total
                FROM pedidos
                WHERE id_usuario_registro IS NOT NULL
                GROUP BY id_usuario_registro
            ) registros ON registros.id_usuario = u.id_usuario
            LEFT JOIN (
                SELECT id_usuario, COUNT(*) AS total
                FROM pedido_cambios
                GROUP BY id_usuario
            ) cambios ON cambios.id_usuario = u.id_usuario
            LEFT JOIN (
                SELECT id_usuario_entrega AS id_usuario, COUNT(*) AS total
                FROM pedidos
                WHERE id_usuario_entrega IS NOT NULL
                GROUP BY id_usuario_entrega
            ) entregas ON entregas.id_usuario = u.id_usuario
            LEFT JOIN (
                SELECT id_usuario, COUNT(*) AS total
                FROM entregas_mercaderia
                WHERE id_usuario IS NOT NULL
                GROUP BY id_usuario
            ) mercaderia ON mercaderia.id_usuario = u.id_usuario
            LEFT JOIN (
                SELECT id_usuario_pago AS id_usuario, COUNT(*) AS total
                FROM pedidos
                WHERE id_usuario_pago IS NOT NULL
                GROUP BY id_usuario_pago
            ) pagos ON pagos.id_usuario = u.id_usuario
            WHERE ' . $condiciones . '
            ORDER BY CASE u.rol WHEN \'administrador\' THEN 0 ELSE 1 END, u.nombre, u.id_usuario';

        $consulta = $this->conexion->prepare($sql);
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
