<?php
declare(strict_types=1);

// Receptor SMTP exclusivo de pruebas: solo escucha en la interfaz local.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$directorio = $argv[1] ?? '';
if (!is_dir($directorio)) {
    exit(1);
}
$servidor = stream_socket_server('tcp://127.0.0.1:0', $numero, $error);
if (!$servidor) {
    throw new RuntimeException($error);
}
file_put_contents($directorio . '/direccion', stream_socket_get_name($servidor, false));
$mensajes = 0;
while ($conexion = @stream_socket_accept($servidor, 30)) {
    stream_set_timeout($conexion, 5);
    fwrite($conexion, "220 localhost pruebas SMTP\r\n");
    while (($linea = fgets($conexion)) !== false) {
        $comando = strtoupper(strtok(trim($linea), ' '));
        if ($comando === 'EHLO' || $comando === 'HELO') {
            fwrite($conexion, "250-localhost\r\n250 8BITMIME\r\n");
        } elseif ($comando === 'RCPT' && str_contains($linea, 'fallo@example.test') && !is_file($directorio . '/recuperado')) {
            fwrite($conexion, "550 rechazo de prueba sin-datos-privados\r\n");
        } elseif ($comando === 'DATA') {
            fwrite($conexion, "354 terminar con punto\r\n");
            $mensaje = '';
            while (($dato = fgets($conexion)) !== false && $dato !== ".\r\n") {
                $mensaje .= str_starts_with($dato, '..') ? substr($dato, 1) : $dato;
            }
            file_put_contents($directorio . '/mensaje-' . ++$mensajes . '.eml', $mensaje);
            fwrite($conexion, "250 aceptado\r\n");
        } elseif ($comando === 'QUIT') {
            fwrite($conexion, "221 adios\r\n");
            break;
        } else {
            fwrite($conexion, "250 OK\r\n");
        }
    }
    fclose($conexion);
}
fclose($servidor);
