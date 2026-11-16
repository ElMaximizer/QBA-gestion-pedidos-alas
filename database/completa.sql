-- QBA / Panadería Rustic: instalación y actualización completa.
-- Esquema de desarrollo revisado el 2026-11-15.
--
-- Importar ESTE archivo en la base elegida en phpMyAdmin o con mysql BASE < completa.sql.
-- Crear/seleccionar la base antes de importar y usar ese nombre en config/database.php.
-- No fija un nombre de base ni necesita importar otros SQL.
-- Crear un respaldo antes de actualizar una instalación existente: el DDL hace COMMIT.
-- Se puede repetir. No borra tablas, cuentas, productos, pedidos ni sus historiales.
-- No agrega productos de muestra, usuarios ni contraseñas.
-- Conserva las categorías elegidas; no aplica reclasificaciones por nombre.
-- Los ALTER condicionales usan information_schema para admitir MySQL y MariaDB.
-- No requiere procedimientos almacenados ni desactivar las claves foráneas.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Catálogo y materias primas.

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

-- Cuentas y acceso de clientes y equipo.

CREATE TABLE IF NOT EXISTS clientes (
    id_cliente INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    version_sesion CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '',
    telefono VARCHAR(30) NOT NULL DEFAULT '',
    verificado TINYINT(1) NOT NULL DEFAULT 0,
    codigo_verificacion_hash VARCHAR(255) NULL,
    codigo_verificacion_vence DATETIME NULL,
    intentos_verificacion TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ultimo_codigo_en DATETIME NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_clientes_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usuarios_equipo (
    id_usuario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    cedula INT UNSIGNED NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    estado ENUM('pendiente', 'aprobado', 'rechazado', 'desactivado') NOT NULL DEFAULT 'pendiente',
    rol ENUM('empleado', 'administrador', 'dueno') NOT NULL DEFAULT 'empleado',
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_equipo_cedula (cedula),
    UNIQUE KEY uq_equipo_email (email),
    KEY idx_equipo_estado (estado, creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS correos_locales (
    id_correo INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    destinatario VARCHAR(190) NOT NULL,
    asunto VARCHAR(160) NOT NULL,
    cuerpo TEXT NOT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_correos_destinatario (destinatario, id_correo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS direcciones_cliente (
    id_direccion INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT UNSIGNED NOT NULL,
    alias VARCHAR(50) NOT NULL,
    direccion VARCHAR(250) NOT NULL,
    referencias VARCHAR(120) NOT NULL DEFAULT '',
    principal TINYINT(1) NOT NULL DEFAULT 0,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_direcciones_cliente (id_cliente, principal, id_direccion),
    CONSTRAINT fk_direccion_cliente
        FOREIGN KEY (id_cliente) REFERENCES clientes (id_cliente) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS codigos_password_cliente (
    id_cliente INT UNSIGNED NOT NULL PRIMARY KEY,
    version_acceso CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    password_pendiente_hash VARCHAR(255) NULL,
    codigo_hash VARCHAR(255) NULL,
    vence_en DATETIME NULL,
    intentos TINYINT UNSIGNED NOT NULL DEFAULT 0,
    enviado_en DATETIME NOT NULL,
    CONSTRAINT fk_codigo_password_cliente
        FOREIGN KEY (id_cliente) REFERENCES clientes (id_cliente) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS intentos_acceso_cliente (
    clave_email CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    intentos TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bloqueado_hasta DATETIME NULL,
    actualizado_en DATETIME NOT NULL,
    KEY idx_acceso_actualizado (actualizado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS intentos_acceso_equipo (
    clave_cedula CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    intentos TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bloqueado_hasta DATETIME NULL,
    actualizado_en DATETIME NOT NULL,
    KEY idx_acceso_equipo_actualizado (actualizado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pedidos, historial y comprobantes.

CREATE TABLE IF NOT EXISTS pedidos (
    id_pedido INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    -- Los pedidos tomados por el equipo conservan NULL; la web asocia la cuenta del cliente.
    id_cliente INT UNSIGNED NULL,
    id_usuario_registro INT UNSIGNED NULL,
    cliente_nombre VARCHAR(120) NOT NULL,
    telefono VARCHAR(30) NOT NULL,
    fecha_retiro DATE NOT NULL,
    hora_retiro TIME NOT NULL,
    modalidad_entrega VARCHAR(20) NOT NULL DEFAULT 'retiro',
    direccion_entrega VARCHAR(250) NOT NULL DEFAULT '',
    metodo_pago VARCHAR(20) NOT NULL DEFAULT 'local',
    estado_pago VARCHAR(20) NOT NULL DEFAULT 'pendiente',
    pago_confirmado_en DATETIME NULL,
    id_usuario_pago INT UNSIGNED NULL,
    transferencia_enviada_en DATETIME NULL,
    entregado_en DATETIME NULL,
    id_usuario_entrega INT UNSIGNED NULL,
    notas TEXT NOT NULL,
    estado VARCHAR(30) NOT NULL DEFAULT 'Pendiente',
    sena_recibida TINYINT(1) NOT NULL DEFAULT 0,
    total DECIMAL(10,2) UNSIGNED NOT NULL,
    token_confirmacion CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pedidos_confirmacion (token_confirmacion),
    KEY idx_pedidos_retiro (fecha_retiro, estado),
    KEY idx_pedidos_cliente (id_cliente, creado_en),
    CONSTRAINT fk_pedidos_usuario_registro
        FOREIGN KEY (id_usuario_registro) REFERENCES usuarios_equipo (id_usuario),
    CONSTRAINT fk_pedidos_usuario_pago
        FOREIGN KEY (id_usuario_pago) REFERENCES usuarios_equipo (id_usuario) ON DELETE SET NULL,
    CONSTRAINT fk_pedidos_usuario_entrega
        FOREIGN KEY (id_usuario_entrega) REFERENCES usuarios_equipo (id_usuario) ON DELETE SET NULL
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

CREATE TABLE IF NOT EXISTS comprobantes_transferencia (
    id_pedido INT UNSIGNED NOT NULL PRIMARY KEY,
    id_cliente INT UNSIGNED NULL,
    id_usuario_equipo INT UNSIGNED NULL,
    tipo_mime VARCHAR(30) NOT NULL,
    contenido MEDIUMBLOB NOT NULL,
    enviado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado_revision ENUM('pendiente', 'aprobada', 'rechazada') NOT NULL DEFAULT 'pendiente',
    CONSTRAINT fk_comprobante_transferencia_pedido
        FOREIGN KEY (id_pedido) REFERENCES pedidos (id_pedido) ON DELETE CASCADE,
    CONSTRAINT fk_comprobante_transferencia_cliente
        FOREIGN KEY (id_cliente) REFERENCES clientes (id_cliente) ON DELETE CASCADE,
    CONSTRAINT fk_comprobante_transferencia_usuario_equipo
        FOREIGN KEY (id_usuario_equipo) REFERENCES usuarios_equipo (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Recepciones de mercadería y consultas.

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

CREATE TABLE IF NOT EXISTS mensajes_contacto (
    id_mensaje INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    motivo VARCHAR(80) NOT NULL,
    mensaje TEXT NOT NULL,
    leido TINYINT(1) NOT NULL DEFAULT 0,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_mensajes_contacto_creado (creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Recuperación de contraseña (HU17: clientes y equipo).

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

-- Actualización de instalaciones anteriores del mismo proyecto.
-- CREATE TABLE IF NOT EXISTS no agrega columnas a una tabla que ya existe.

-- productos.portada_orden
SET @qba_sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE productos ADD COLUMN portada_orden TINYINT UNSIGNED NULL AFTER destacado',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'productos' AND COLUMN_NAME = 'portada_orden');
PREPARE qba_migracion FROM @qba_sql;
EXECUTE qba_migracion;
DEALLOCATE PREPARE qba_migracion;

-- clientes.version_sesion
SET @qba_sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE clientes ADD COLUMN version_sesion CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '''' AFTER password_hash',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clientes' AND COLUMN_NAME = 'version_sesion');
PREPARE qba_migracion FROM @qba_sql;
EXECUTE qba_migracion;
DEALLOCATE PREPARE qba_migracion;

-- clientes.telefono
SET @qba_sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE clientes ADD COLUMN telefono VARCHAR(30) NOT NULL DEFAULT ''''',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clientes' AND COLUMN_NAME = 'telefono');
PREPARE qba_migracion FROM @qba_sql;
EXECUTE qba_migracion;
DEALLOCATE PREPARE qba_migracion;

-- direcciones_cliente.referencias
SET @qba_sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE direcciones_cliente ADD COLUMN referencias VARCHAR(120) NOT NULL DEFAULT ''''',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'direcciones_cliente' AND COLUMN_NAME = 'referencias');
PREPARE qba_migracion FROM @qba_sql;
EXECUTE qba_migracion;
DEALLOCATE PREPARE qba_migracion;

-- codigos_password_cliente.password_pendiente_hash
SET @qba_sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE codigos_password_cliente ADD COLUMN password_pendiente_hash VARCHAR(255) NULL',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'codigos_password_cliente' AND COLUMN_NAME = 'password_pendiente_hash');
PREPARE qba_migracion FROM @qba_sql;
EXECUTE qba_migracion;
DEALLOCATE PREPARE qba_migracion;

-- pedidos.id_usuario_registro
SET @qba_sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE pedidos ADD COLUMN id_usuario_registro INT UNSIGNED NULL AFTER id_cliente',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos' AND COLUMN_NAME = 'id_usuario_registro');
PREPARE qba_migracion FROM @qba_sql;
EXECUTE qba_migracion;
DEALLOCATE PREPARE qba_migracion;

-- pedidos.modalidad_entrega
SET @qba_sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE pedidos ADD COLUMN modalidad_entrega VARCHAR(20) NOT NULL DEFAULT ''retiro'' AFTER hora_retiro',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos' AND COLUMN_NAME = 'modalidad_entrega');
PREPARE qba_migracion FROM @qba_sql;
EXECUTE qba_migracion;
DEALLOCATE PREPARE qba_migracion;

-- pedidos.direccion_entrega
SET @qba_sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE pedidos ADD COLUMN direccion_entrega VARCHAR(250) NOT NULL DEFAULT '''' AFTER modalidad_entrega',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos' AND COLUMN_NAME = 'direccion_entrega');
PREPARE qba_migracion FROM @qba_sql;
EXECUTE qba_migracion;
DEALLOCATE PREPARE qba_migracion;

-- pedidos.metodo_pago
SET @qba_sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE pedidos ADD COLUMN metodo_pago VARCHAR(20) NOT NULL DEFAULT ''local'' AFTER direccion_entrega',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos' AND COLUMN_NAME = 'metodo_pago');
PREPARE qba_migracion FROM @qba_sql;
EXECUTE qba_migracion;
DEALLOCATE PREPARE qba_migracion;

-- pedidos.estado_pago
SET @qba_sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE pedidos ADD COLUMN estado_pago VARCHAR(20) NOT NULL DEFAULT ''pendiente'' AFTER metodo_pago',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos' AND COLUMN_NAME = 'estado_pago');
PREPARE qba_migracion FROM @qba_sql;
EXECUTE qba_migracion;
DEALLOCATE PREPARE qba_migracion;

-- pedidos.pago_confirmado_en
SET @qba_sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE pedidos ADD COLUMN pago_confirmado_en DATETIME NULL AFTER estado_pago',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos' AND COLUMN_NAME = 'pago_confirmado_en');
PREPARE qba_migracion FROM @qba_sql;
EXECUTE qba_migracion;
DEALLOCATE PREPARE qba_migracion;

-- pedidos.id_usuario_pago
SET @qba_sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE pedidos ADD COLUMN id_usuario_pago INT UNSIGNED NULL AFTER pago_confirmado_en',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos' AND COLUMN_NAME = 'id_usuario_pago');
PREPARE qba_migracion FROM @qba_sql;
EXECUTE qba_migracion;
DEALLOCATE PREPARE qba_migracion;

-- pedidos.transferencia_enviada_en
SET @qba_sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE pedidos ADD COLUMN transferencia_enviada_en DATETIME NULL AFTER id_usuario_pago',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos' AND COLUMN_NAME = 'transferencia_enviada_en');
PREPARE qba_migracion FROM @qba_sql;
EXECUTE qba_migracion;
DEALLOCATE PREPARE qba_migracion;

-- pedidos.entregado_en
SET @qba_sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE pedidos ADD COLUMN entregado_en DATETIME NULL AFTER transferencia_enviada_en',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos' AND COLUMN_NAME = 'entregado_en');
PREPARE qba_migracion FROM @qba_sql;
EXECUTE qba_migracion;
DEALLOCATE PREPARE qba_migracion;

-- pedidos.id_usuario_entrega
SET @qba_sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE pedidos ADD COLUMN id_usuario_entrega INT UNSIGNED NULL AFTER entregado_en',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos' AND COLUMN_NAME = 'id_usuario_entrega');
PREPARE qba_migracion FROM @qba_sql;
EXECUTE qba_migracion;
DEALLOCATE PREPARE qba_migracion;

-- pedidos.sena_recibida
SET @qba_sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE pedidos ADD COLUMN sena_recibida TINYINT(1) NOT NULL DEFAULT 0 AFTER estado',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos' AND COLUMN_NAME = 'sena_recibida');
PREPARE qba_migracion FROM @qba_sql;
EXECUTE qba_migracion;
DEALLOCATE PREPARE qba_migracion;

-- comprobantes_transferencia.id_usuario_equipo
SET @qba_sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE comprobantes_transferencia ADD COLUMN id_usuario_equipo INT UNSIGNED NULL AFTER id_cliente',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'comprobantes_transferencia' AND COLUMN_NAME = 'id_usuario_equipo');
PREPARE qba_migracion FROM @qba_sql;
EXECUTE qba_migracion;
DEALLOCATE PREPARE qba_migracion;

-- Las cargas del equipo admiten comprobantes sin una cuenta de cliente.
SET @qba_sql = (SELECT IF(COUNT(*) > 0,
    'ALTER TABLE comprobantes_transferencia MODIFY id_cliente INT UNSIGNED NULL',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'comprobantes_transferencia'
        AND COLUMN_NAME = 'id_cliente' AND IS_NULLABLE = 'NO');
PREPARE qba_migracion FROM @qba_sql;
EXECUTE qba_migracion;
DEALLOCATE PREPARE qba_migracion;

-- Incorporar el estado de revisión que antes requería ejecutar otra migración PHP.
SET @qba_migrar_revision = (SELECT COUNT(*) = 0 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'comprobantes_transferencia'
        AND COLUMN_NAME = 'estado_revision');
SET @qba_sql = IF(@qba_migrar_revision,
    'ALTER TABLE comprobantes_transferencia ADD COLUMN estado_revision ENUM(''pendiente'', ''aprobada'', ''rechazada'') NOT NULL DEFAULT ''pendiente'' AFTER enviado_en',
    'DO 0');
PREPARE qba_migracion FROM @qba_sql;
EXECUTE qba_migracion;
DEALLOCATE PREPARE qba_migracion;

-- Solo convertir los valores de pago heredados que la aplicación ya no utiliza.
SET @qba_sql = (SELECT IF(COUNT(*) > 0,
    'ALTER TABLE pedidos MODIFY estado_pago VARCHAR(20) NOT NULL DEFAULT ''pendiente''',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos'
        AND COLUMN_NAME = 'estado_pago' AND DATA_TYPE = 'enum');
PREPARE qba_migracion FROM @qba_sql;
EXECUTE qba_migracion;
DEALLOCATE PREPARE qba_migracion;

UPDATE pedidos SET estado_pago = 'pagado' WHERE estado_pago = 'confirmado';
UPDATE pedidos SET estado_pago = CASE
    WHEN pago_confirmado_en IS NULL THEN 'pendiente' ELSE 'pagado' END
    WHERE estado_pago = '';

-- Inferir la revisión histórica únicamente cuando se acaba de agregar la columna.
-- Al repetir la importación se conservan las decisiones de revisión ya registradas.
UPDATE comprobantes_transferencia c
    INNER JOIN pedidos p ON p.id_pedido = c.id_pedido
    SET c.estado_revision = 'rechazada'
    WHERE @qba_migrar_revision = 1 AND p.metodo_pago = 'transferencia'
        AND p.estado_pago = 'pendiente' AND p.transferencia_enviada_en IS NULL
        AND c.estado_revision = 'pendiente';
UPDATE comprobantes_transferencia c
    INNER JOIN pedidos p ON p.id_pedido = c.id_pedido
    SET c.estado_revision = 'aprobada'
    WHERE @qba_migrar_revision = 1 AND p.metodo_pago = 'transferencia'
        AND p.estado_pago = 'pagado' AND c.estado_revision = 'pendiente';

-- Admitir la desactivación de cuentas del equipo en instalaciones antiguas.
SET @qba_sql = (SELECT IF(COUNT(*) > 0,
    'ALTER TABLE usuarios_equipo MODIFY estado ENUM(''pendiente'', ''aprobado'', ''rechazado'', ''desactivado'') NOT NULL DEFAULT ''pendiente''',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios_equipo'
        AND COLUMN_NAME = 'estado' AND COLUMN_TYPE NOT LIKE '%desactivado%');
PREPARE qba_migracion FROM @qba_sql;
EXECUTE qba_migracion;
DEALLOCATE PREPARE qba_migracion;

-- Completar las relaciones agregadas por las migraciones de pedidos y comprobantes.

SET @qba_sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE pedidos ADD CONSTRAINT fk_pedidos_usuario_registro FOREIGN KEY (id_usuario_registro) REFERENCES usuarios_equipo (id_usuario)',
    'DO 0') FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos'
        AND CONSTRAINT_NAME = 'fk_pedidos_usuario_registro' AND CONSTRAINT_TYPE = 'FOREIGN KEY');
PREPARE qba_migracion FROM @qba_sql;
EXECUTE qba_migracion;
DEALLOCATE PREPARE qba_migracion;

SET @qba_sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE pedidos ADD CONSTRAINT fk_pedidos_usuario_pago FOREIGN KEY (id_usuario_pago) REFERENCES usuarios_equipo (id_usuario) ON DELETE SET NULL',
    'DO 0') FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos'
        AND CONSTRAINT_NAME = 'fk_pedidos_usuario_pago' AND CONSTRAINT_TYPE = 'FOREIGN KEY');
PREPARE qba_migracion FROM @qba_sql;
EXECUTE qba_migracion;
DEALLOCATE PREPARE qba_migracion;

SET @qba_sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE pedidos ADD CONSTRAINT fk_pedidos_usuario_entrega FOREIGN KEY (id_usuario_entrega) REFERENCES usuarios_equipo (id_usuario) ON DELETE SET NULL',
    'DO 0') FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos'
        AND CONSTRAINT_NAME = 'fk_pedidos_usuario_entrega' AND CONSTRAINT_TYPE = 'FOREIGN KEY');
PREPARE qba_migracion FROM @qba_sql;
EXECUTE qba_migracion;
DEALLOCATE PREPARE qba_migracion;

SET @qba_sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE comprobantes_transferencia ADD CONSTRAINT fk_comprobante_transferencia_usuario_equipo FOREIGN KEY (id_usuario_equipo) REFERENCES usuarios_equipo (id_usuario) ON DELETE SET NULL',
    'DO 0') FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'comprobantes_transferencia'
        AND CONSTRAINT_NAME = 'fk_comprobante_transferencia_usuario_equipo' AND CONSTRAINT_TYPE = 'FOREIGN KEY');
PREPARE qba_migracion FROM @qba_sql;
EXECUTE qba_migracion;
DEALLOCATE PREPARE qba_migracion;

SET @qba_sql = NULL, @qba_migrar_revision = NULL;

