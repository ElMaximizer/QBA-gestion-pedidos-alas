-- Aplicar después de clientes.sql y direcciones-clientes.sql, con respaldo previo.
-- Campos opcionales: no modifica pedidos ni datos existentes. Se puede repetir.
SET @qba_telefono = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE clientes ADD COLUMN telefono VARCHAR(30) NOT NULL DEFAULT ''''',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clientes' AND COLUMN_NAME = 'telefono');
PREPARE qba_contacto FROM @qba_telefono;
EXECUTE qba_contacto;
DEALLOCATE PREPARE qba_contacto;

SET @qba_referencias = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE direcciones_cliente ADD COLUMN referencias VARCHAR(120) NOT NULL DEFAULT ''''',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'direcciones_cliente' AND COLUMN_NAME = 'referencias');
PREPARE qba_contacto FROM @qba_referencias;
EXECUTE qba_contacto;
DEALLOCATE PREPARE qba_contacto;
