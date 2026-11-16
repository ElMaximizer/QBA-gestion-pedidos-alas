# Base de datos del proyecto

`database/completa.sql` es la fuente de la estructura vigente. Incluye la
instalación y las actualizaciones de las versiones anteriores de este proyecto.
No necesita importar los SQL individuales ni ejecutar una migración adicional
para los estados de revisión de los comprobantes.

## Importar un único SQL

En phpMyAdmin, creá o seleccioná la base de destino y usá **Importar** para cargar
`database/completa.sql`. En XAMPP, el nombre predeterminado es `qba_pedidos_alas`.
El archivo trabaja sobre la base seleccionada: no impone ese nombre a producción
ni cambia de base durante la importación.

Después de importar, `config/database.php` debe apuntar a esa misma base. Admite
las variables `QBA_DB_HOST`, `QBA_DB_PORT`, `QBA_DB_NAME`, `QBA_DB_USER` y
`QBA_DB_PASSWORD`.

La carpeta `database` contiene únicamente `completa.sql`. No hay instalador PHP
ni carga automática de datos de demostración. Los productos se cargan desde el
panel del equipo. No se agregan cuentas, contraseñas ni aprobaciones; la whitelist
y la configuración de correo siguen en sus archivos privados.

## Actualizar una base existente

Respaldá la base antes de importar. Los cambios de estructura de MySQL/MariaDB
hacen confirmaciones implícitas y no se pueden deshacer con un `ROLLBACK` global.
El archivo se puede ejecutar más de una vez: las tablas, columnas y relaciones
que ya existen se conservan. No desactiva las claves foráneas.

Conserva clientes, contraseñas, permisos, productos, categorías elegidas, stock,
pedidos, detalles, historial y comprobantes. No contiene `DROP`, `TRUNCATE` ni
eliminaciones de registros. Al actualizar versiones anteriores:

- Agrega los campos de cuenta, contacto, portada, pagos y entregas que faltan.
- Admite comprobantes cargados por el equipo sin una cuenta de cliente.
- Normaliza el estado de pago antiguo `confirmado` a `pagado`. Si el estado
  estaba vacío, usa la fecha de confirmación para distinguir pendiente y pagado.
- Cuando falta `estado_revision`, lo agrega e infiere las revisiones históricas
  desde el pago del pedido, con las reglas de la migración anterior. Al repetir
  la importación, conserva las revisiones ya registradas.
- Crea las tablas de recuperación de contraseña y de límites de solicitudes.

Para futuras modificaciones de la estructura, actualizar `completa.sql`. Los
archivos individuales y las herramientas anteriores se pueden consultar en el
historial de Git. No se aplican reclasificaciones de categorías por nombre.

## Tablas incluidas

| Área | Tablas |
| --- | --- |
| Catálogo y stock | `productos`, `materias_primas` |
| Clientes | `clientes`, `cambios_correo_cliente`, `direcciones_cliente`, `codigos_password_cliente`, `codigos_eliminacion_cliente` |
| Equipo y acceso | `usuarios_equipo`, `intentos_acceso_cliente`, `intentos_acceso_equipo` |
| Pedidos y pagos | `pedidos`, `pedido_detalles`, `pedido_cambios`, `comprobantes_transferencia` |
| Mercadería | `entregas_mercaderia`, `entrega_mercaderia_detalles` |
| Contacto y correo local | `mensajes_contacto`, `correos_locales` |
| Recuperación de contraseña | `recuperaciones_password`, `limites_recuperacion_password` |

El esquema usa InnoDB y UTF-8 (`utf8mb4_unicode_ci`), conserva los importes como
decimales y los códigos como hashes. Los cambios condicionales consultan
`information_schema`; no requieren procedimientos almacenados ni comandos
`SOURCE` para importar otros archivos.
