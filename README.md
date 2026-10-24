# QBA — Gestión de pedidos de Panadería Alas

Proyecto PHP con MVC y CSS según BEM. Conserva el diseño público del
[repositorio anterior](https://github.com/ElMaximizer/software-pedidos-alas/tree/6e4a0b26bc2ba412db66dd1541acb209d3e4f6ae).

## HU18 — Realizar un pedido

El cliente puede explorar y filtrar productos, ver su detalle, agregar cantidades
al carrito, actualizarlo y completar sus datos para coordinar el retiro. Al
confirmar, el pedido y sus líneas se guardan en MySQL como **Pendiente**, se vacía
el carrito y se muestra el número del pedido.

Se conserva el comportamiento anterior de admitir pedidos sin iniciar sesión.
HU16/HU17 incorporarán las cuentas y su asociación con los pedidos; por ahora,
id_cliente queda en NULL. No hay cobros en línea, registro de señas, envío de
correos, panel administrativo ni historial de pedidos en esta historia.
Los enlaces de acceso, nosotros y contacto siguen inactivos.

## Requisitos

- PHP 8.2 o superior con PDO MySQL, mbstring y sesiones habilitados.
- MySQL o MariaDB con tablas InnoDB; probado con MariaDB 10.4 de XAMPP.
- MySQL iniciado desde el panel de XAMPP.
- Para las pruebas: extensiones cURL y DOM, y proc_open habilitado.

No se necesitan Composer, Node ni JavaScript para este flujo.

## Preparar la base local

La configuración está en config/database.php. Por defecto utiliza el MySQL
local de XAMPP, usuario root, contraseña vacía y la base **qba_pedidos_alas**.
Se puede cambiar mediante QBA_DB_HOST, QBA_DB_PORT, QBA_DB_NAME, QBA_DB_USER y
QBA_DB_PASSWORD. Las credenciales de otros entornos se pasan como variables.

Desde la raíz del repositorio, en PowerShell:

    C:\xampp\php\php.exe database\instalar.php --demo

El instalador crea la base y las tablas productos, pedidos y pedido_detalles.
La opción --demo agrega tres productos de muestra, con sus textos e ingredientes
de demostración. Se deben revisar esos datos antes de usar un catálogo real.
Sin esa opción se crea solamente la estructura. La instalación se puede repetir
y no importa datos del repositorio anterior.

## Ejecutar en desarrollo

    C:\xampp\php\php.exe -S 127.0.0.1:8000 -t public

Abrir <http://127.0.0.1:8000/>. Para detener el servidor, pulsar Ctrl+C en esa
terminal. El servidor integrado es para desarrollo local. La carpeta de sesiones
configurada por PHP debe tener permisos de escritura.

Con Apache de XAMPP también se puede abrir
<http://localhost/QBA-gestion-pedidos-alas/public/index.php>.
La raíz pública de un virtual host debe apuntar a public/.

## Recorrido MVC

- public/index.php: rutas permitidas, métodos HTTP y manejo de errores.
- app/bootstrap.php: autocarga, zona horaria y sesión.
- app/Controllers/: InicioController, ProductoController, CarritoController y
  PedidoController coordinan las peticiones.
- app/Models/Producto.php: consultas del catálogo.
- app/Models/Carrito.php: cantidades y resumen del carrito.
- app/Models/Pedido.php: validaciones y guardado transaccional.
- app/Core/: conexión PDO, lectura de campos HTTP, CSRF y renderizado.
- app/Views/: presentación de inicio, catálogo, producto, carrito, formulario y
  confirmación. Los parciales reutilizan cabecera, pie y tarjeta de producto.
- config/database.php: conexión configurable mediante variables de entorno.
- database/: estructura, productos de muestra e instalador exclusivo de CLI.
- public/css/inicio.css: estilos compartidos y portada.
- public/css/pedidos.css: componentes de HU18.
- public/images/: cinco imágenes originales.
- tests/pedidos.php: pruebas del dominio y del flujo HTTP.

El punto de entrada selecciona un controlador. El controlador coordina la
petición, los modelos aplican reglas y consultan o guardan datos, y las vistas
presentan el resultado. Inicio y catálogo reutilizan la misma tarjeta de
producto y la misma fuente de datos.

## Rutas

Todas se acceden a través de public/index.php:

| Parámetro ruta | Métodos | Pantalla u operación |
| --- | --- | --- |
| inicio o sin parámetro | GET, HEAD | Inicio |
| productos | GET, HEAD | Catálogo, búsqueda q y filtro categoria |
| producto | GET, HEAD | Detalle por id |
| carrito | GET, HEAD, POST | Ver, agregar, actualizar, quitar o vaciar |
| realizar-pedido | GET, HEAD, POST | Revisar datos y confirmar |
| pedido-confirmado | GET, HEAD | Número guardado en la sesión |

Las rutas desconocidas responden 404 y los métodos no permitidos, 405.
Las modificaciones requieren POST y CSRF. Después de una operación correcta se
redirige con 303 para evitar repetirla al recargar.

## Reglas del pedido

- Entre 1 y 99 unidades por producto y hasta 50 productos distintos.
- Nombre, teléfono y fecha/hora futura de retiro obligatorios, según la zona
  America/Montevideo; notas opcionales de hasta 2000 caracteres.
- Los precios, el total y el estado los determina el servidor. Los importes se
  calculan en centavos enteros y se guardan como DECIMAL.
- Los productos del carrito se consultan juntos. Antes de guardar se bloquean
  dentro de la transacción y se comprueban su disponibilidad y precio.
- El formulario conserva una firma del carrito durante 30 minutos. Si cambia
  el carrito o un precio, se solicita revisarlo antes de confirmar.
- La cabecera y los detalles se guardan juntos; un error revierte la operación.
- Cada confirmación tiene un token de sesión y una restricción única en MySQL.
  Reenviar el mismo formulario devuelve el mismo pedido.

## BEM y diseño

Se conservan las imágenes, colores, tipografía, medidas y puntos de adaptación
del diseño público anterior. Los bloques nuevos incluyen detalle, campo,
carrito-fila, cantidad y resumen. Los elementos usan bloque__elemento y los
modificadores, bloque--modificador; por ejemplo, campo__entrada y boton--dorado.
inicio.css contiene los estilos comunes y pedidos.css se carga en las pantallas
del flujo.

## Verificación

    C:\xampp\php\php.exe tests\pedidos.php

Las pruebas crean una base temporal qba_hu18_test_*, un servidor local y sus
sesiones, y los eliminan al terminar. Requieren permisos locales para crear y
eliminar esa base y crear un trigger de prueba. Cubren validaciones, precios
manipulados o modificados, productos retirados, CSRF, formularios malformados,
rollback de cabecera y detalles, doble envío y el recorrido HTTP completo.

Para comprobar toda la sintaxis PHP en PowerShell:

    Get-ChildItem app,public,config,database,tests -Recurse -Filter *.php |
        ForEach-Object { C:\xampp\php\php.exe -l $_.FullName }
