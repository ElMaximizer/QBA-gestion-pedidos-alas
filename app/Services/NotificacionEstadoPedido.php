<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

final class NotificacionEstadoPedido
{
    public const ENVIADO = 'enviado';
    public const LOCAL = 'local';
    public const SIN_CORREO = 'sin_correo';

    private readonly Correo $correo;

    private readonly array $configuracion;

    private readonly PDO $conexion;

    public function __construct(PDO $conexion, ?array $configuracion = null)
    {
        $this->conexion = $conexion;
        $this->configuracion = $configuracion ?? require dirname(__DIR__, 2) . '/config/correo.php';
        $this->correo = new Correo($conexion, $this->configuracion);
    }

    public function notificar(int $idPedido, string $estadoNuevo): string
    {
        $consulta = $this->conexion->prepare(
            'SELECT c.email, c.nombre
             FROM pedidos p
             INNER JOIN clientes c ON c.id_cliente = p.id_cliente
             WHERE p.id_pedido = ? AND c.verificado = 1
             LIMIT 1',
        );
        $consulta->execute([$idPedido]);
        $cliente = $consulta->fetch(PDO::FETCH_ASSOC);
        if (!is_array($cliente) || !filter_var($cliente['email'] ?? null, FILTER_VALIDATE_EMAIL)) {
            return self::SIN_CORREO;
        }

        $nombre = (string) $cliente['nombre'];
        $asunto = 'Actualización de tu pedido #' . $idPedido . ' · Rustic';
        $cuerpo = "Hola {$nombre},\n\nEl estado de tu pedido #{$idPedido} se actualizó a: {$estadoNuevo}.\n\n" .
            'Podés consultar el seguimiento desde tu cuenta, en Mis pedidos.
                Gracias por elegir Rustic.';
        $this->correo->enviar((string) $cliente['email'], $nombre, $asunto, $cuerpo);

        return ($this->configuracion['transport'] ?? 'local') === 'smtp'
            ? self::ENVIADO
            : self::LOCAL;
    }
}
