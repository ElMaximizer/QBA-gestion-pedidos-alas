<?php
declare(strict_types=1);

date_default_timezone_set('America/Montevideo');

spl_autoload_register(static function (string $clase): void {
    if (str_starts_with($clase, 'App\\')) {
        $archivo = __DIR__ . '/' . str_replace('\\', '/', substr($clase, 4)) . '.php';
        if (is_file($archivo)) {
            require $archivo;
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
