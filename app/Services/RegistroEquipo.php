<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\UsuarioEquipo;
use PDO;
use Throwable;

final class RegistroEquipo
{
    private readonly UsuarioEquipo $usuarios;
    private readonly Correo $correo;

    public function __construct(PDO $db, ?Correo $correo = null)
    {
        $this->usuarios = new UsuarioEquipo($db);
        $this->correo = $correo ?? new Correo($db);
    }

    public function solicitar(string $nombre, string $cedula, string $email, string $password, string $confirmacion): array
    {
        $usuario = $this->usuarios->solicitar($nombre, $cedula, $email, $password, $confirmacion);
        try {
            $this->correo->enviar($usuario['email'], $usuario['nombre'], 'Solicitud de acceso recibida · Panadería Alas',
                "Hola {$usuario['nombre']},\n\nRecibimos tu solicitud de acceso al equipo. "
                . 'El dueño de Panadería Alas debe aprobarla antes de que puedas iniciar sesión.');
            $usuario['correo_enviado'] = true;
        } catch (Throwable $error) {
            // La solicitud persiste aunque falle el aviso; no se registran datos personales ni credenciales.
            error_log('Registro del equipo: falló el correo de confirmación (' . $error::class . ').');
            $usuario['correo_enviado'] = false;
        }
        return $usuario;
    }
}
