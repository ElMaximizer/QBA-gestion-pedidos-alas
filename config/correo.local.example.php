<?php
declare(strict_types=1);

// Copiar como correo.local.php. Ese archivo queda excluido de Git.
return [
    'transport' => 'smtp', // Cambiar a 'local' para usar la bandeja de desarrollo.
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => '', // Dirección completa de la cuenta de Gmail que envía.
    'password' => '', // Contraseña de aplicación de Google, sin espacios.
    'from_email' => '', // Vacío: utiliza la misma dirección que username.
    'from_name' => 'Panadería Alas',
];
