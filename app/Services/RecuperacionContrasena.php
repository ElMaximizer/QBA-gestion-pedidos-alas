<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\RecuperacionContrasena as RecuperacionContrasenaModel;
use PDO;
use Throwable;

final class RecuperacionContrasena
{
    private readonly RecuperacionContrasenaModel $modelo;
    private readonly Correo $correo;

    public function __construct(PDO $conexion)
    {
        $this->modelo = new RecuperacionContrasenaModel($conexion);
        $this->correo = new Correo($conexion);
    }

    public function solicitar(string $tipo, string $identidad, string $ip): void
    {
        $datos = $this->modelo->solicitar($tipo, $identidad, $ip);
        if ($datos === null) {
            return;
        }
        $cuerpo = "Hola {$datos['nombre']},\n\n" .
            "Tu código para cambiar la contraseña de Panadería Rustic es: {$datos['codigo']}\n\n" .
            'El código vence en 10 minutos y solo puede utilizarse una vez. Si no solicitaste el cambio, ignorá este mensaje.';
        try {
            $this->correo->enviar(
                $datos['email'],
                $datos['nombre'],
                'Código para cambiar tu contraseña · Rustic',
                $cuerpo,
            );
        } catch (Throwable $error) {
            error_log('Recuperación de contraseña: falló el envío del código (' . $error::class . ').');
        }
    }

    public function restablecer(string $tipo, string $identidad, string $codigo, string $password, string $confirmacion): void
    {
        $this->modelo->restablecer($tipo, $identidad, $codigo, $password, $confirmacion);
    }

    public function verificarCodigo(string $tipo, string $identidad, string $codigo): void
    {
        $this->modelo->verificarCodigo($tipo, $identidad, $codigo);
    }
}
