<?php
declare(strict_types=1);

namespace App\Models;

use DomainException;
use PDO;
use Throwable;

final class SolicitudesEquipo
{
    public function __construct(private readonly PDO $db) {}

    public function pendientes(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM usuarios_equipo WHERE estado = 'pendiente' AND rol = 'empleado'")->fetchColumn();
    }

    public function listar(int $pagina = 1): array
    {
        if ($pagina < 1) {
            throw new DomainException('Elegí una página válida.');
        }
        $total = $this->pendientes();
        $paginas = max(1, (int) ceil($total / 10));
        $pagina = min($pagina, $paginas);
        $consulta = $this->db->prepare("SELECT id_usuario,nombre,cedula,email,creado_en FROM usuarios_equipo
            WHERE estado = 'pendiente' AND rol = 'empleado' ORDER BY creado_en DESC,id_usuario DESC LIMIT 10 OFFSET ?");
        $consulta->bindValue(1, ($pagina - 1) * 10, PDO::PARAM_INT);
        $consulta->execute();
        return ['solicitudes' => $consulta->fetchAll(), 'total' => $total, 'pagina' => $pagina, 'paginas' => $paginas];
    }

    public function resolver(int $idUsuario, int $idDueno, string $accion): void
    {
        if ($idUsuario < 1 || $idDueno < 1 || $idUsuario === $idDueno || !in_array($accion, ['aprobar', 'rechazar'], true)) {
            throw new DomainException('Elegí una solicitud y una acción válidas.');
        }
        $this->db->beginTransaction();
        try {
            // La autorización se vuelve a comprobar al guardar, con la cuenta bloqueada hasta terminar.
            $consulta = $this->db->prepare("SELECT id_usuario FROM usuarios_equipo
                WHERE id_usuario = ? AND estado = 'aprobado' AND rol = 'dueno' FOR UPDATE");
            $consulta->execute([$idDueno]);
            if (!$consulta->fetchColumn()) {
                throw new DomainException('Solo un dueño aprobado puede resolver las solicitudes.');
            }
            $consulta = $this->db->prepare("UPDATE usuarios_equipo SET estado = ?
                WHERE id_usuario = ? AND estado = 'pendiente' AND rol = 'empleado'");
            $consulta->execute([$accion === 'aprobar' ? 'aprobado' : 'rechazado', $idUsuario]);
            if ($consulta->rowCount() !== 1) {
                throw new DomainException('La solicitud ya fue resuelta o no está disponible. Recargá la página.');
            }
            $this->db->commit();
        } catch (Throwable $excepcion) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $excepcion;
        }
    }
}
