-- Registro del equipo. Las solicitudes requieren la aprobación posterior del dueño.
CREATE TABLE IF NOT EXISTS usuarios_equipo (
    id_usuario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    cedula INT UNSIGNED NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    estado ENUM('pendiente', 'aprobado', 'rechazado') NOT NULL DEFAULT 'pendiente',
    rol ENUM('empleado', 'administrador', 'dueno') NOT NULL DEFAULT 'empleado',
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_equipo_cedula (cedula),
    UNIQUE KEY uq_equipo_email (email),
    KEY idx_equipo_estado (estado, creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
