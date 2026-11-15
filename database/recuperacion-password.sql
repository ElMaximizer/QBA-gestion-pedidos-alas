-- Recuperación de contraseñas de clientes y equipo. Solo se guarda el hash del código.
CREATE TABLE IF NOT EXISTS recuperaciones_password (
    tipo ENUM('cliente', 'equipo') NOT NULL,
    clave_identidad CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    id_cuenta INT UNSIGNED NULL,
    codigo_hash VARCHAR(255) NULL,
    solicitado_en DATETIME NULL,
    vence_en DATETIME NULL,
    intentos TINYINT UNSIGNED NOT NULL DEFAULT 0,
    envios TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ventana_envios_en DATETIME NOT NULL,
    PRIMARY KEY (tipo, clave_identidad),
    KEY idx_recuperaciones_vencimiento (vence_en),
    KEY idx_recuperaciones_solicitado (solicitado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS limites_recuperacion_password (
    clave_ip CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
    solicitudes TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ventana_en DATETIME NOT NULL,
    KEY idx_limites_recuperacion_ventana (ventana_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
