<?php
declare(strict_types=1);

date_default_timezone_set('America/Montevideo');

spl_autoload_register(static function (string $clase): void {
    foreach (['App\\' => __DIR__,
        'PHPMailer\\PHPMailer\\' => dirname(__DIR__) . '/lib/PHPMailer/src'] as $prefijo => $directorio) {
        if (str_starts_with($clase, $prefijo)) {
            $archivo = $directorio . '/' . str_replace('\\', '/', substr($clase, strlen($prefijo))) . '.php';
            if (is_file($archivo)) {
                require_once $archivo;
            }
            return;
        }
    }
});

if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_name('QBA_SESSION');
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
}
