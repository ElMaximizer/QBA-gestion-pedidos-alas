-- HU17. Límite de intentos compartido entre sesiones, sin guardar contraseñas.
CREATE TABLE IF NOT EXISTS intentos_acceso_cliente (
    clave_email CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    intentos TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bloqueado_hasta DATETIME NULL,
    actualizado_en DATETIME NOT NULL,
    KEY idx_acceso_actualizado (actualizado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
