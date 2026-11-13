<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Conexion;

$email = strtolower(trim($argv[1] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
    fwrite(STDERR, "Uso: php database/ver-correo.php correo@ejemplo.com\n");
    exit(1);
}

try {
    $consulta = Conexion::obtener()->prepare('SELECT asunto, cuerpo, creado_en FROM correos_locales
        WHERE destinatario = ? ORDER BY id_correo DESC LIMIT 1');
    $consulta->execute([$email]);
    $correo = $consulta->fetch();
    if (!$correo) {
        fwrite(STDERR, "No hay mensajes locales para ese correo.\n");
        exit(1);
    }
    echo "Bandeja local de desarrollo — {$correo['creado_en']}\n{$correo['asunto']}\n\n{$correo['cuerpo']}\n";
} catch (Throwable $error) {
    fwrite(STDERR, "No se pudo consultar la bandeja local. Revisá la conexión y la instalación de la base.\n");
    exit(1);
}
