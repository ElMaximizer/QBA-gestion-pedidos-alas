-- Configuración de clientes: el correo vigente se conserva hasta verificar el nuevo.
-- Aplicar después de clientes.sql. Conserva los datos de cuentas y pedidos existentes.
SET @qba_version_sesion = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE clientes ADD COLUMN version_sesion CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '''' AFTER password_hash',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clientes' AND COLUMN_NAME = 'version_sesion');
PREPARE qba_cuenta FROM @qba_version_sesion;
EXECUTE qba_cuenta;
DEALLOCATE PREPARE qba_cuenta;

CREATE TABLE IF NOT EXISTS cambios_correo_cliente (
    id_cliente INT UNSIGNED NOT NULL PRIMARY KEY,
    email VARCHAR(190) NULL,
    codigo_hash VARCHAR(255) NULL,
    vence_en DATETIME NULL,
    intentos TINYINT UNSIGNED NOT NULL DEFAULT 0,
    enviado_en DATETIME NOT NULL,
    CONSTRAINT fk_cambio_correo_cliente
        FOREIGN KEY (id_cliente) REFERENCES clientes (id_cliente) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
