-- Login del equipo: intentos compartidos entre sesiones e independientes de los clientes.
CREATE TABLE IF NOT EXISTS intentos_acceso_equipo (
    clave_cedula CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    intentos TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bloqueado_hasta DATETIME NULL,
    actualizado_en DATETIME NOT NULL,
    KEY idx_acceso_equipo_actualizado (actualizado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
