-- Aplicar después de clientes.sql. No modifica cuentas ni pedidos existentes.
CREATE TABLE IF NOT EXISTS codigos_password_cliente (
    id_cliente INT UNSIGNED NOT NULL PRIMARY KEY,
    version_acceso CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    codigo_hash VARCHAR(255) NULL,
    vence_en DATETIME NULL,
    intentos TINYINT UNSIGNED NOT NULL DEFAULT 0,
    enviado_en DATETIME NOT NULL,
    CONSTRAINT fk_codigo_password_cliente
        FOREIGN KEY (id_cliente) REFERENCES clientes (id_cliente) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Compatible con la versión anterior: sus códigos sin contraseña pendiente no confirman cambios.
SET @qba_password_pendiente = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE codigos_password_cliente ADD COLUMN password_pendiente_hash VARCHAR(255) NULL',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'codigos_password_cliente'
        AND COLUMN_NAME = 'password_pendiente_hash');
PREPARE qba_password FROM @qba_password_pendiente;
EXECUTE qba_password;
DEALLOCATE PREPARE qba_password;
