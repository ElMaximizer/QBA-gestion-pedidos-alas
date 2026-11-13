-- Libreta personal de direcciones. Los pedidos conservan su dirección histórica.
-- Aplicar después de clientes.sql. Se puede repetir sin reemplazar datos.
CREATE TABLE IF NOT EXISTS direcciones_cliente (
    id_direccion INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT UNSIGNED NOT NULL,
    alias VARCHAR(50) NOT NULL,
    direccion VARCHAR(250) NOT NULL,
    principal TINYINT(1) NOT NULL DEFAULT 0,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_direcciones_cliente (id_cliente, principal, id_direccion),
    CONSTRAINT fk_direccion_cliente
        FOREIGN KEY (id_cliente) REFERENCES clientes (id_cliente) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
