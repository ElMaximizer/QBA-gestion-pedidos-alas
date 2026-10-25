-- HU09: historial de recepciones de proveedores y sus movimientos de stock.
-- Se aplica después de estructura.sql y equipo.sql.
CREATE TABLE IF NOT EXISTS entregas_mercaderia (
    id_entrega INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    proveedor VARCHAR(120) NOT NULL,
    fecha_recepcion DATE NOT NULL,
    comprobante VARCHAR(80) NOT NULL DEFAULT '',
    observaciones VARCHAR(1000) NOT NULL DEFAULT '',
    id_usuario INT UNSIGNED NULL,
    responsable VARCHAR(120) NOT NULL,
    token_formulario CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    firma CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    creado_en DATETIME NOT NULL,
    UNIQUE KEY uq_entregas_formulario (token_formulario),
    KEY idx_entregas_fecha (fecha_recepcion, id_entrega),
    CONSTRAINT fk_entregas_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios_equipo (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS entrega_mercaderia_detalles (
    id_detalle INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_entrega INT UNSIGNED NOT NULL,
    id_materia INT UNSIGNED NOT NULL,
    nombre_materia VARCHAR(100) NOT NULL,
    unidad VARCHAR(20) NOT NULL,
    cantidad DECIMAL(12,3) UNSIGNED NOT NULL,
    stock_anterior DECIMAL(12,3) UNSIGNED NOT NULL,
    stock_resultante DECIMAL(12,3) UNSIGNED NOT NULL,
    UNIQUE KEY uq_entrega_materia (id_entrega, id_materia),
    CONSTRAINT fk_entrega_detalle FOREIGN KEY (id_entrega) REFERENCES entregas_mercaderia (id_entrega),
    CONSTRAINT fk_entrega_materia FOREIGN KEY (id_materia) REFERENCES materias_primas (id_materia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
