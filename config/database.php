<?php
declare(strict_types=1);

// Valores locales de XAMPP; en otros entornos se configuran mediante variables.
return [
    'host' => getenv('QBA_DB_HOST') ?: '127.0.0.1',
    'port' => getenv('QBA_DB_PORT') ?: '3306',
    'database' => getenv('QBA_DB_NAME') ?: 'qba_pedidos_alas',
    'user' => getenv('QBA_DB_USER') !== false ? getenv('QBA_DB_USER') : 'root',
    'password' => getenv('QBA_DB_PASSWORD') !== false ? getenv('QBA_DB_PASSWORD') : '',
];
