<?php
declare(strict_types=1);

namespace App\Services;

use DomainException;
use finfo;
use RuntimeException;

final class ImagenProducto
{
    private const DIRECTORIO = __DIR__ . '/../../public/images/';
    public const MAXIMO_BYTES = 4 * 1024 * 1024;

    public function guardar(?array $archivo): ?string
    {
        if ($archivo === null || ($archivo['error'] ?? null) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($archivo['error'] ?? null) !== UPLOAD_ERR_OK || !is_string($archivo['tmp_name'] ?? null)
            || !is_uploaded_file($archivo['tmp_name'])) {
            throw new DomainException('Elegí una imagen JPG o PNG de hasta 4 MB.');
        }
        $temporal = $archivo['tmp_name'];
        $tamano = filesize($temporal);
        if (!$tamano || $tamano > self::MAXIMO_BYTES) {
            throw new DomainException('Elegí una imagen JPG o PNG de hasta 4 MB.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporal);
        $dimensiones = @getimagesize($temporal);
        if (!in_array($mime, ['image/jpeg', 'image/png'], true) || !$dimensiones
            || $dimensiones[0] > 8000 || $dimensiones[1] > 8000 || ($dimensiones['mime'] ?? '') !== $mime) {
            throw new DomainException('El archivo debe ser una imagen JPG o PNG válida de hasta 8000 píxeles por lado.');
        }
        // Se descartan el nombre y la extensión enviados por el navegador.
        $ruta = 'productos/' . bin2hex(random_bytes(16)) . ($mime === 'image/png' ? '.png' : '.jpg');
        if (!@move_uploaded_file($temporal, self::DIRECTORIO . $ruta)) {
            throw new RuntimeException('No se pudo guardar la imagen del producto.');
        }
        return $ruta;
    }

    public function retirar(string $ruta): void
    {
        // Solo retira imágenes administradas por este servicio, nunca las ilustraciones del sitio.
        if (preg_match('~^productos/[a-f0-9]{32}\.(?:jpg|png)$~D', $ruta)) {
            $archivo = self::DIRECTORIO . $ruta;
            clearstatcache(true, $archivo);
            if (is_file($archivo) && !@unlink($archivo)) {
                error_log('HU06: no se pudo retirar una imagen sin uso.');
            }
        }
    }
}
