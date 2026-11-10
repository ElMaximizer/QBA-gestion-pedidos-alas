CREATE TABLE IF NOT EXISTS comprobantes_transferencia (
    id_pedido INT UNSIGNED NOT NULL PRIMARY KEY,
    id_cliente INT UNSIGNED NOT NULL,
    tipo_mime VARCHAR(30) NOT NULL,
    contenido MEDIUMBLOB NOT NULL,
    enviado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado_revision ENUM('pendiente', 'aprobada', 'rechazada') NOT NULL DEFAULT 'pendiente',
    CONSTRAINT fk_comprobante_transferencia_pedido
        FOREIGN KEY (id_pedido) REFERENCES pedidos (id_pedido) ON DELETE CASCADE,
    CONSTRAINT fk_comprobante_transferencia_cliente
        FOREIGN KEY (id_cliente) REFERENCES clientes (id_cliente) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
