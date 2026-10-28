-- Esto lo creo gpt, puede llegar a no ser exacto a como lo teniamos en el otro repo

CREATE TABLE IF NOT EXISTS productos (
    id_producto INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    descripcion VARCHAR(280) NOT NULL,
    precio DECIMAL(10,2) UNSIGNED NOT NULL,
    categoria VARCHAR(30) NOT NULL,
    presentacion VARCHAR(80) NOT NULL DEFAULT 'Unidad',
    ingredientes TEXT NOT NULL,
    alergenos VARCHAR(500) NOT NULL DEFAULT '',
    imagen VARCHAR(255) NOT NULL DEFAULT '',
    destacado TINYINT(1) NOT NULL DEFAULT 0,
    portada_orden TINYINT UNSIGNED NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    eliminado_en DATETIME NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_productos_catalogo (activo, eliminado_en, categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS materias_primas (
    id_materia INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    unidad VARCHAR(20) NOT NULL,
    stock_actual DECIMAL(12,3) UNSIGNED NOT NULL DEFAULT 0,
    stock_minimo DECIMAL(12,3) UNSIGNED NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_materias_primas_nombre (nombre),
    KEY idx_materias_primas_stock (activo, stock_actual, stock_minimo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pedidos (
    id_pedido INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    -- Los pedidos tomados por el equipo conservan NULL; la web asocia la cuenta del cliente.
    id_cliente INT UNSIGNED NULL,
    cliente_nombre VARCHAR(120) NOT NULL,
    telefono VARCHAR(30) NOT NULL,
    fecha_retiro DATE NOT NULL,
    hora_retiro TIME NOT NULL,
    notas TEXT NOT NULL,
    estado VARCHAR(30) NOT NULL DEFAULT 'Pendiente',
    sena_recibida TINYINT(1) NOT NULL DEFAULT 0,
    total DECIMAL(10,2) UNSIGNED NOT NULL,
    token_confirmacion CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pedidos_confirmacion (token_confirmacion),
    KEY idx_pedidos_retiro (fecha_retiro, estado),
    KEY idx_pedidos_cliente (id_cliente, creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pedido_detalles (
    id_detalle INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT UNSIGNED NOT NULL,
    id_producto INT UNSIGNED NULL,
    nombre_producto VARCHAR(120) NOT NULL,
    cantidad INT UNSIGNED NOT NULL,
    precio_unitario DECIMAL(10,2) UNSIGNED NOT NULL,
    CONSTRAINT fk_detalle_pedido FOREIGN KEY (id_pedido) REFERENCES pedidos (id_pedido),
    CONSTRAINT fk_detalle_producto FOREIGN KEY (id_producto) REFERENCES productos (id_producto) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
