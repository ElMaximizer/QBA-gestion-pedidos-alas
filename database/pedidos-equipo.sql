-- HU01. Ejecutar después de estructura.sql y equipo.sql. Conserva todos los pedidos.
-- Compatible con MySQL y MariaDB; se puede ejecutar más de una vez.
SET @qba_hu01_sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE pedidos ADD COLUMN id_usuario_registro INT UNSIGNED NULL AFTER id_cliente',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos' AND COLUMN_NAME = 'id_usuario_registro');
PREPARE qba_hu01 FROM @qba_hu01_sql;
EXECUTE qba_hu01;
DEALLOCATE PREPARE qba_hu01;

SET @qba_hu01_sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE pedidos ADD CONSTRAINT fk_pedidos_usuario_registro FOREIGN KEY (id_usuario_registro) REFERENCES usuarios_equipo (id_usuario)',
    'DO 0') FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos' AND CONSTRAINT_NAME = 'fk_pedidos_usuario_registro');
PREPARE qba_hu01 FROM @qba_hu01_sql;
EXECUTE qba_hu01;
DEALLOCATE PREPARE qba_hu01;
