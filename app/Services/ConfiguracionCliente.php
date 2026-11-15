<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\CuentaCliente;
use PDO;
use Throwable;

final class ConfiguracionCliente
{
    private readonly PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function solicitarCodigoPassword(int $id, string $version, string $password, string $nueva, string $confirmacion): bool
    {
        $cambio = (new CuentaCliente($this->conexion))->solicitarCodigoPassword($id, $version, $password, $nueva, $confirmacion);
        try {
            (new Correo($this->conexion))->enviar(
                $cambio['email'],
                $cambio['nombre'],
                'Confirmá el cambio de contraseña · Panadería Rustic',
                "Hola {$cambio['nombre']},\n\nTu código para cambiar la contraseña es: {$cambio['codigo']}\n\n" .
                    'Vence en 10 minutos. Si no solicitaste este cambio, no compartas el código. Tu contraseña sigue vigente.',
            );
            return true;
        } catch (Throwable $error) {
            error_log('Cuenta cliente: falló el envío del código de contraseña (' . $error::class . ').');
            return false;
        }
    }

    public function solicitarCodigoEliminacion(int $id, string $version, string $password): bool
    {
        $cambio = (new CuentaCliente($this->conexion))->solicitarCodigoEliminacion($id, $version, $password);
        try {
            (new Correo($this->conexion))->enviar(
                $cambio['email'],
                $cambio['nombre'],
                'Confirmá la eliminación de tu cuenta · Panadería Rustic',
                "Hola {$cambio['nombre']},\n\nTu código para eliminar tu cuenta es: {$cambio['codigo']}\n\n" .
                    'Vence en 10 minutos. Tu cuenta seguirá activa hasta que confirmes. Si no solicitaste este cambio, no compartas el código.',
            );
            return true;
        } catch (Throwable $error) {
            error_log('Cuenta cliente: falló el envío del código de eliminación (' . $error::class . ').');
            return false;
        }
    }

    public function solicitarCorreo(int $id, string $version, string $password, string $email): bool
    {
        $cambio = (new CuentaCliente($this->conexion))->solicitarCorreo($id, $version, $password, $email);
        try {
            // El envío se hace después del commit; no mantiene bloqueos durante SMTP.
            (new Correo($this->conexion))->enviar(
                $cambio['email'],
                $cambio['nombre'],
                'Confirmá tu nuevo correo · Panadería Rustic',
                "Hola {$cambio['nombre']},\n\nTu código para cambiar el correo de tu cuenta es: {$cambio['codigo']}\n\n" .
                    'Vence en 10 minutos. Tu correo actual sigue vigente hasta que confirmes el cambio.',
            );
            return true;
        } catch (Throwable $error) {
            error_log('Cuenta cliente: falló el envío del código (' . $error::class . ').');
            return false;
        }
    }
}
