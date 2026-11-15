-- HU23. Aplicar después de estructura.sql y equipo.sql. No altera pedidos existentes.
CREATE TABLE IF NOT EXISTS pedido_cambios (
    id_cambio BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT UNSIGNED NOT NULL,
    id_usuario INT UNSIGNED NOT NULL,
    nombre_responsable VARCHAR(120) NOT NULL,
    motivo VARCHAR(500) NOT NULL DEFAULT '',
    antes JSON NOT NULL,
    despues JSON NOT NULL,
    token_formulario CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pedido_cambios_token (token_formulario),
    KEY idx_pedido_cambios_pedido (id_pedido, id_cambio),
    CONSTRAINT fk_pedido_cambios_pedido FOREIGN KEY (id_pedido) REFERENCES pedidos (id_pedido),
    CONSTRAINT fk_pedido_cambios_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios_equipo (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
