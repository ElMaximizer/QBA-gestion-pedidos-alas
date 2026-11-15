-- Rediseño del inicio. Se puede repetir y conserva los productos existentes.
SET @qba_columna_portada = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'productos' AND COLUMN_NAME = 'portada_orden');
SET @qba_sql_portada = IF(@qba_columna_portada = 0,
    'ALTER TABLE productos ADD COLUMN portada_orden TINYINT UNSIGNED NULL AFTER destacado', 'DO 0');
PREPARE qba_preparar_portada FROM @qba_sql_portada;
EXECUTE qba_preparar_portada;
DEALLOCATE PREPARE qba_preparar_portada;
