<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Cliente;
use PDO;
use Throwable;

/** Coordina la persistencia y la entrega sin mantener bloqueos durante el envío SMTP. */
final class RegistroCliente
{
    private readonly Cliente $clientes;
    private readonly CorreoVerificacion $correo;

    public function __construct(PDO $db, ?CorreoVerificacion $correo = null)
    {
        $this->clientes = new Cliente($db);
        $this->correo = $correo ?? new CorreoVerificacion($db);
    }

    public function registrar(string $nombre, string $email, string $password, string $confirmacion): array
    {
        return $this->entregar($this->clientes->registrar($nombre, $email, $password, $confirmacion));
    }

    public function reenviar(string $email): bool
    {
        $cliente = $this->clientes->reenviar($email);
        // Conserva una respuesta genérica para cuentas inexistentes o ya activadas.
        return $cliente === null || $this->entregar($cliente)['correo_enviado'];
    }

    private function entregar(array $cliente): array
    {
        try {
            $this->correo->enviar($cliente['email'], $cliente['nombre'], $cliente['codigo']);
            $cliente['correo_enviado'] = true;
        } catch (Throwable $error) {
            // No registrar credenciales, contenido del correo ni trazas con argumentos.
            error_log('HU16: falló la entrega del código (' . $error::class . '). Revisá la configuración de correo.');
            $cliente['correo_enviado'] = false;
        }
        unset($cliente['codigo']);
        return $cliente;
    }
}
