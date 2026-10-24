-- HU16. Se puede aplicar sobre la base existente sin modificar productos ni pedidos.
CREATE TABLE IF NOT EXISTS clientes (
    id_cliente INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    verificado TINYINT(1) NOT NULL DEFAULT 0,
    codigo_verificacion_hash VARCHAR(255) NULL,
    codigo_verificacion_vence DATETIME NULL,
    intentos_verificacion TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ultimo_codigo_en DATETIME NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_clientes_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bandeja de desarrollo: estos mensajes se consultan únicamente mediante CLI.
-- Solo se utiliza con QBA_MAIL_TRANSPORT=local; SMTP no guarda aquí los códigos.
CREATE TABLE IF NOT EXISTS correos_locales (
    id_correo INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    destinatario VARCHAR(190) NOT NULL,
    asunto VARCHAR(160) NOT NULL,
    cuerpo TEXT NOT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_correos_destinatario (destinatario, id_correo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
