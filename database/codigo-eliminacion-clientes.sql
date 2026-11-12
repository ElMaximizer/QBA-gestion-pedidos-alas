-- Aplicar después de clientes.sql. Conserva las cuentas y los pedidos existentes.
CREATE TABLE IF NOT EXISTS codigos_eliminacion_cliente (
    id_cliente INT UNSIGNED NOT NULL PRIMARY KEY,
    version_acceso CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    codigo_hash VARCHAR(255) NULL,
    vence_en DATETIME NULL,
    intentos TINYINT UNSIGNED NOT NULL DEFAULT 0,
    enviado_en DATETIME NOT NULL,
    CONSTRAINT fk_codigo_eliminacion_cliente
        FOREIGN KEY (id_cliente) REFERENCES clientes (id_cliente) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
