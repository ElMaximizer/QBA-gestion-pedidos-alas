<?php
declare(strict_types=1);

date_default_timezone_set('America/Montevideo');

spl_autoload_register(static function (string $clase): void {
    // Cada prefijo indica la carpeta donde se buscan sus clases.
    $directorios = [
        'App\\' => __DIR__,
        'PHPMailer\\PHPMailer\\' => dirname(__DIR__) . '/lib/PHPMailer/src',
    ];

    foreach ($directorios as $prefijo => $directorio) {
        if (str_starts_with($clase, $prefijo)) {
            $nombreRelativo = substr($clase, strlen($prefijo));
            $rutaRelativa = str_replace('\\', '/', $nombreRelativo);
            $archivo = $directorio . '/' . $rutaRelativa . '.php';
            if (is_file($archivo)) {
                require_once $archivo;
            }
            return;
        }
    }
});

if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('QBA_SESSION');
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
}
