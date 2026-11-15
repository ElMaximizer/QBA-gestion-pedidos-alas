<?php
declare(strict_types=1);

namespace App\Services;

use PDO;
use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;

final class Correo
{
    private readonly array $configuracion;

    private readonly PDO $conexion;

    public function __construct(PDO $conexion, ?array $configuracion = null)
    {
        $this->conexion = $conexion;

        $this->configuracion =
            $configuracion ?? (require dirname(__DIR__, 2) . '/config/correo.php');
    }

    public function enviar(string $email, string $nombre, string $asunto, string $cuerpo): void
    {
        if ($this->configuracion['transport'] === 'local') {
            $consulta = $this->conexion->prepare(
                'INSERT INTO correos_locales (destinatario, asunto, cuerpo) VALUES (?, ?, ?)',
            );
            $consulta->execute([$email, $asunto, $cuerpo]);
            return;
        }
        if ($this->configuracion['transport'] !== 'smtp') {
            throw new RuntimeException('El transporte de correo debe ser local o smtp.');
        }
        $host = (string) $this->configuracion['host'];
        $puerto = filter_var($this->configuracion['port'], FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 65535],
        ]);
        $cifrado = (string) $this->configuracion['encryption'];
        $cifrado = $cifrado === 'none' ? '' : $cifrado;
        $usuario = (string) $this->configuracion['username'];
        $password = (string) $this->configuracion['password'];
        $remitente = (string) ($this->configuracion['from_email'] ?: $usuario);
        $timeout = filter_var($this->configuracion['timeout'], FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 30],
        ]);
        $esPruebaLocal =
            in_array($host, ['127.0.0.1', 'localhost', '::1'], true) &&
            $cifrado === '' &&
            $usuario === '' &&
            $password === '';
        if (
            !preg_match('/^[a-zA-Z0-9.:-]+$/D', $host) ||
            !$puerto ||
            !$timeout ||
            !filter_var($remitente, FILTER_VALIDATE_EMAIL) ||
            (!$esPruebaLocal &&
                (!in_array($cifrado, ['tls', 'ssl'], true) || $usuario === '' || $password === ''))
        ) {
            throw new RuntimeException('La configuración SMTP está incompleta o no es válida.');
        }
        if (!class_exists(PHPMailer::class)) {
            throw new RuntimeException('Faltan los archivos de PHPMailer en lib/PHPMailer/src.');
        }
        $correo = new PHPMailer(true);
        $correo->isSMTP();
        $correo->Host = $host;
        $correo->Port = $puerto;
        $correo->SMTPSecure = $cifrado;
        $correo->SMTPAuth = !$esPruebaLocal;
        $correo->Username = $usuario;
        $correo->Password = $password;
        $correo->SMTPAutoTLS = !$esPruebaLocal;
        $correo->Timeout = $timeout;
        $correo->getSMTPInstance()->Timelimit = $timeout;
        $correo->SMTPDebug = 0;
        $correo->CharSet = PHPMailer::CHARSET_UTF8;
        $correo->Encoding = PHPMailer::ENCODING_BASE64;
        $correo->setFrom($remitente, (string) $this->configuracion['from_name']);
        $correo->addAddress($email, $nombre);
        $correo->Subject = $asunto;
        $correo->Body = $cuerpo;
        $correo->send();
    }
}
