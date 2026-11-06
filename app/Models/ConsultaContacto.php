<?php
declare(strict_types=1);

namespace App\Models;

use DomainException;
use PDO;

final class ConsultaContacto
{
    public const MOTIVOS = [
        'Consulta sobre productos',
        'Disponibilidad',
        'Pedidos para compartir',
        'Tortas y celebraciones',
        'Otra consulta',
    ];

    private readonly PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function nuevas(): int
    {
        return (int) $this->conexion
            ->query('SELECT COUNT(*) FROM mensajes_contacto WHERE leido = 0')
            ->fetchColumn();
    }

    public function listar(string $estado = '', int $pagina = 1): array
    {
        if (!in_array($estado, ['', 'nuevas', 'leidas'], true) || $pagina < 1) {
            throw new DomainException('Revisá el filtro y la página de consultas.');
        }
        $resumen = array_map(
            'intval',
            $this->conexion
                ->query(
                    'SELECT COUNT(*) AS total, COALESCE(SUM(leido = 0),0) AS nuevas FROM mensajes_contacto',
                )
                ->fetch(),
        );
        $resumen['leidas'] = $resumen['total'] - $resumen['nuevas'];
        $total = $resumen[$estado === '' ? 'total' : $estado];
        $paginas = max(1, (int) ceil($total / 10));
        $pagina = min($pagina, $paginas);
        $condicion = '';
        if ($estado === 'nuevas') {
            $condicion = ' WHERE leido = 0';
        } elseif ($estado === 'leidas') {
            $condicion = ' WHERE leido = 1';
        }
        $consulta = $this->conexion->prepare(
            'SELECT id_mensaje,nombre,email,motivo,mensaje,leido,creado_en FROM mensajes_contacto' .
                $condicion .
                ' ORDER BY creado_en DESC,id_mensaje DESC LIMIT 10 OFFSET ?',
        );
        $consulta->bindValue(1, ($pagina - 1) * 10, PDO::PARAM_INT);
        $consulta->execute();
        return [
            'consultas' => $consulta->fetchAll(),
            'resumen' => $resumen,
            'total' => $total,
            'pagina' => $pagina,
            'paginas' => $paginas,
        ];
    }

    public function marcarLeida(int $id): void
    {
        if ($id < 1) {
            throw new DomainException('Elegí una consulta válida.');
        }
        $consulta = $this->conexion->prepare(
            'UPDATE mensajes_contacto SET leido = 1 WHERE id_mensaje = ?',
        );
        $consulta->execute([$id]);
        if ($consulta->rowCount() === 0) {
            $existe = $this->conexion->prepare(
                'SELECT 1 FROM mensajes_contacto WHERE id_mensaje = ?',
            );
            $existe->execute([$id]);
            if (!$existe->fetchColumn()) {
                throw new DomainException('La consulta ya no está disponible.');
            }
        }
    }

    public function guardar(array $datos): void
    {
        $nombre = $datos['nombre'] ?? null;
        $email = $datos['email'] ?? null;
        $motivo = $datos['motivo'] ?? null;
        $mensaje = $datos['mensaje'] ?? null;
        if (
            !is_string($nombre) ||
            !mb_check_encoding($nombre, 'UTF-8') ||
            mb_strlen($nombre) < 2 ||
            mb_strlen($nombre) > 120 ||
            !is_string($email) ||
            strlen($email) > 190 ||
            filter_var($email, FILTER_VALIDATE_EMAIL) === false ||
            !is_string($motivo) ||
            !in_array($motivo, self::MOTIVOS, true) ||
            !is_string($mensaje) ||
            !mb_check_encoding($mensaje, 'UTF-8') ||
            mb_strlen($mensaje) < 5 ||
            mb_strlen($mensaje) > 5000
        ) {
            throw new DomainException('Revisá tu nombre, correo, motivo y mensaje.');
        }
        if (time() - (int) ($_SESSION['qba']['contacto_ultimo_envio'] ?? 0) < 30) {
            throw new DomainException('Esperá unos segundos antes de enviar otra consulta.');
        }
        $consulta = $this->conexion->prepare(
            'INSERT INTO mensajes_contacto (nombre,email,motivo,mensaje) VALUES (?,?,?,?)',
        );
        $consulta->execute([trim($nombre), strtolower(trim($email)), $motivo, trim($mensaje)]);
        $_SESSION['qba']['contacto_ultimo_envio'] = time();
    }
}
