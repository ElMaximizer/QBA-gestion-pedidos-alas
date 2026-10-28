<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

final class CorreoVerificacion
{
    private readonly Correo $correo;

    public function __construct(PDO $db, ?array $config = null)
    {
        $this->correo = new Correo($db, $config);
    }

    public function enviar(string $email, string $nombre, string $codigo): void
    {
        $cuerpo = "Hola {$nombre},\n\nTu código para verificar la cuenta de Panadería R U S T I C es: {$codigo}\n\n"
            . 'El código vence en 10 minutos. Si no solicitaste esta cuenta, podés ignorar este mensaje.';
        $this->correo->enviar($email, $nombre, 'Código para verificar tu cuenta · Panadería Rustic', $cuerpo);
    }
}
