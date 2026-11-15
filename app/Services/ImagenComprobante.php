<?php
declare(strict_types=1);

namespace App\Services;

use DomainException;
use finfo;

final class ImagenComprobante
{
    public static function leer(mixed $archivo): array
    {
        if (
            !is_array($archivo) || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK ||
            !is_string($archivo['tmp_name'] ?? null) || !is_uploaded_file($archivo['tmp_name'])
        ) {
            throw new DomainException('Adjuntá la imagen del comprobante de transferencia.');
        }
        $tamano = (int) ($archivo['size'] ?? 0);
        if ($tamano < 1 || $tamano > 2 * 1024 * 1024) {
            throw new DomainException('La imagen del comprobante debe pesar como máximo 2 MB.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
        if (!is_string($mime) || !in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new DomainException('Adjuntá una imagen JPG, PNG o WebP.');
        }
        $imagen = getimagesize($archivo['tmp_name']);
        if (!is_array($imagen) || ($imagen['mime'] ?? '') !== $mime) {
            throw new DomainException('El archivo adjunto no parece ser una imagen válida.');
        }
        $contenido = file_get_contents($archivo['tmp_name']);
        if (!is_string($contenido)) {
            throw new DomainException('No pudimos leer el comprobante. Elegí la imagen nuevamente.');
        }
        return ['mime' => $mime, 'contenido' => $contenido];
    }
}
