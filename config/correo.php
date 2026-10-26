<?php
declare(strict_types=1);

$archivoLocal = __DIR__ . '/correo.local.php';
$config = array_replace([
    'transport' => 'local',
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => '',
    'password' => '',
    'from_email' => '',
    'from_name' => 'Panadería Alas',
    'timeout' => 10,
], getenv('QBA_MAIL_TRANSPORT') === false && is_file($archivoLocal) ? require $archivoLocal : []);

// Un transporte definido en el entorno usa solo variables y valores por defecto.
// Así las pruebas y los despliegues no heredan credenciales de esta máquina.
foreach (['transport' => 'QBA_MAIL_TRANSPORT', 'host' => 'QBA_SMTP_HOST',
    'port' => 'QBA_SMTP_PORT', 'encryption' => 'QBA_SMTP_ENCRYPTION',
    'username' => 'QBA_SMTP_USERNAME', 'password' => 'QBA_SMTP_PASSWORD',
    'from_email' => 'QBA_MAIL_FROM', 'from_name' => 'QBA_MAIL_FROM_NAME',
    'timeout' => 'QBA_SMTP_TIMEOUT'] as $clave => $variable) {
    $valor = getenv($variable);
    if ($valor !== false) {
        $config[$clave] = $valor;
    }
}

return $config;
