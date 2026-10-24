# QBA — Gestión de pedidos de Panadería Alas

Proyecto PHP con MVC y CSS según BEM. Conserva el diseño público del
[repositorio anterior](https://github.com/ElMaximizer/software-pedidos-alas/tree/6e4a0b26bc2ba412db66dd1541acb209d3e4f6ae).

## HU16 — Registrarse

Desde **Acceso clientes** se puede crear una cuenta con nombre, correo y
contraseña, y activarla con un código de seis dígitos. El correo es único y las
contraseñas se guardan mediante password_hash, sin conservarlas en texto plano.

La verificación vence en 10 minutos, admite hasta cinco intentos fallidos y
permite reenviar un código después de un minuto. Los límites se guardan en la
base de datos y también se respetan desde otra sesión. Las fechas de vigencia
se calculan en UTC. El código se guarda protegido antes de entregar el correo;
el envío SMTP se realiza sin mantener bloqueos en la base de datos.

Por defecto los mensajes se guardan en una **bandeja local de desarrollo**.
Después de registrarte, consultá el último mensaje con el mismo correo que
usaste en el formulario:

    C:\xampp\php\php.exe database\ver-correo.php tu-correo@ejemplo.com

Ingresá ese código en la pantalla de verificación para completar el registro.
La bandeja no tiene una ruta web pública y el comando solo funciona por CLI.
El envío real usa PHPMailer por SMTP y se activa con la configuración siguiente.

### Enviar los códigos con Gmail

1. Copiar config/correo.local.example.php como config/correo.local.php.
   Si el archivo privado ya existe, editarlo directamente.
2. En la cuenta que enviará los mensajes, activar la verificación en dos pasos
   y generar una [contraseña de aplicación de Google](https://support.google.com/mail/answer/185833?hl=es).
   Algunas cuentas administradas o con Protección Avanzada no ofrecen esa opción.
3. En el archivo privado, completar username con la dirección completa de Gmail,
   password con la contraseña de aplicación sin los espacios de presentación y
   transport con smtp. El remitente usa esa misma dirección si from_email está vacío.
4. Registrar una cuenta y revisar el correo recibido, incluida la carpeta de spam.

Se utiliza smtp.gmail.com, puerto 587 y STARTTLS, según la
[configuración oficial de Gmail](https://support.google.com/mail/answer/7104828?hl=en).
La verificación de certificados TLS se conserva habilitada. La contraseña
habitual de la cuenta no se usa para este acceso.

config/correo.local.php está excluido de Git. Se comparte la plantilla sin
credenciales y la librería PHPMailer en lib/PHPMailer/. Las credenciales se
completan únicamente en config/correo.local.php; config/correo.php conserva sus
valores por defecto vacíos. Para enviar correos reales, el archivo privado debe
tener transport en smtp: con local los mensajes solo llegan a la bandeja de desarrollo.

Como alternativa para despliegues se puede definir QBA_MAIL_TRANSPORT=smtp y
QBA_SMTP_USERNAME, QBA_SMTP_PASSWORD, QBA_SMTP_HOST, QBA_SMTP_PORT,
QBA_SMTP_ENCRYPTION, QBA_MAIL_FROM, QBA_MAIL_FROM_NAME y QBA_SMTP_TIMEOUT.
Al definir QBA_MAIL_TRANSPORT en el entorno se omite el archivo privado y se
utilizan las variables más los valores por defecto de config/correo.php.
Con Apache las variables deben estar disponibles para su proceso.

El registro y el reenvío usan el mismo servicio de correo. En modo smtp no se
guardan copias de los códigos en correos_locales. Si SMTP falla, se informa que
la cuenta sigue pendiente y se permite pedir otro código después de un minuto;
no se cambia automáticamente a la bandeja local. No hay reintentos automáticos:
el botón de reenvío genera otro código y reemplaza el anterior. Que SMTP acepte
el mensaje no garantiza su llegada a la bandeja de entrada.

HU16 no inicia una sesión de cliente automáticamente. Al completar la
verificación se ofrece continuar al acceso de **HU17**.

## HU17 — Iniciar sesión

**Acceso clientes** abre el formulario de correo y contraseña con el mismo diseño
del proyecto anterior. El cliente debe haber verificado el correo mediante HU16.
Si la contraseña es correcta pero falta la verificación, se ofrece completar ese
paso sin otorgar una sesión autenticada ni enviar otro código automáticamente.

Al ingresar se vuelve al inicio y aparece el menú **Hola, nombre** con **Cerrar
sesión**. El registro y el acceso reutilizan el campo de contraseña, sus estilos
BEM y el control para mostrarla. Los formularios funcionan sin JavaScript.
La recuperación de contraseña, el acceso del equipo y el historial de **HU20**
no forman parte de esta implementación.

La identidad se guarda únicamente en el servidor y se revalida contra la base
una vez por petición. Al ingresar o salir se renuevan la cookie y CSRF, se conserva
el carrito y se invalidan los formularios y confirmaciones de la identidad
anterior. La sesión de cliente vence después de 30 minutos sin actividad.
Cerrar sesión requiere POST y CSRF; una visita a un enlace no cierra la cuenta.

Seis contraseñas incorrectas para un mismo correo bloquean el acceso durante un
minuto, incluso desde otra sesión. Los intentos aislados vencen a los diez minutos
y un acceso correcto los reinicia. La tabla intentos_acceso_cliente conserva una
clave SHA-256 del correo, contadores y fechas UTC; no guarda contraseñas. Se limpian
hasta cien registros de más de un día al intentar un acceso. Un correo inexistente
y una contraseña incorrecta reciben el mismo mensaje.

Los pedidos nuevos realizados con sesión quedan asociados al id_cliente de esa
cuenta. El nombre se precarga y puede ajustarse para el retiro; modificarlo no
cambia la identidad asociada. Un id_cliente enviado desde el navegador se ignora.
Los pedidos anteriores conservan su asociación original, incluidos los de
invitados con NULL. La consulta del historial se implementará en HU20.

## HU18 — Realizar un pedido

El cliente puede explorar y filtrar productos, ver su detalle, agregar cantidades
al carrito, actualizarlo y completar sus datos para coordinar el retiro. Al
confirmar, el pedido y sus líneas se guardan en MySQL como **Pendiente**, se vacía
el carrito y se muestra el número del pedido.

Se conserva el comportamiento anterior de admitir pedidos sin iniciar sesión:
en esos casos id_cliente queda en NULL. Con HU17 se toma la identidad de la sesión.
No hay cobros en línea, registro de señas, envío de
correos, panel administrativo ni historial de pedidos en esta historia.
Los enlaces de nosotros y contacto siguen inactivos.

## Requisitos

- PHP 8.2 o superior con PDO MySQL, mbstring, OpenSSL y sesiones habilitados.
- MySQL o MariaDB con tablas InnoDB; probado con MariaDB 10.4 de XAMPP.
- MySQL iniciado desde el panel de XAMPP.
- Para las pruebas: extensiones cURL y DOM, y proc_open habilitado.

No se necesitan Composer ni Node. Los formularios funcionan sin JavaScript;
el registro y el acceso lo usan únicamente para mostrar y ocultar la contraseña.

PHPMailer 7.1.1 está incluido en lib/PHPMailer/, copiado del ZIP de su
[publicación oficial](https://github.com/PHPMailer/PHPMailer/releases/tag/v7.1.1).
app/bootstrap.php carga sus clases directamente desde esa carpeta. Al descargar
el repositorio, la librería ya está disponible. Su origen, licencia e instrucciones
para actualizarla están documentados en lib/PHPMailer/ORIGEN.md.

## Preparar la base local

La configuración está en config/database.php. Por defecto utiliza el MySQL
local de XAMPP, usuario root, contraseña vacía y la base **qba_pedidos_alas**.
Se puede cambiar mediante QBA_DB_HOST, QBA_DB_PORT, QBA_DB_NAME, QBA_DB_USER y
QBA_DB_PASSWORD. Las credenciales de otros entornos se pasan como variables.

Desde la raíz del repositorio, en PowerShell:

    C:\xampp\php\php.exe database\instalar.php --demo

El instalador crea la base y las tablas productos, pedidos, pedido_detalles,
clientes, correos_locales e intentos_acceso_cliente. database/clientes.sql incorpora las tablas de HU16
sobre la base existente, conservando los productos y pedidos registrados.
La opción --demo agrega tres productos de muestra, con sus textos e ingredientes
de demostración. Se deben revisar esos datos antes de usar un catálogo real.
Sin esa opción se crea solamente la estructura. La instalación se puede repetir
y no importa datos del repositorio anterior.

Si ya tenías HU16 instalado, ejecutá el instalador sin --demo para incorporar
la tabla de HU17 conservando los datos existentes:

    C:\xampp\php\php.exe database\instalar.php

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
- app/Models/Cliente.php: registro, códigos y activación de cuentas.
- app/Models/AccesoCliente.php: credenciales, límite de intentos e identidad verificada.
- app/Controllers/AccesoController.php: inicio y cierre de sesión de HU17.
- app/Controllers/RegistroController.php: formularios y resultados de HU16.
- app/Services/RegistroCliente.php: coordina registro y entrega del código.
- app/Services/CorreoVerificacion.php: bandeja local o envío SMTP con PHPMailer.
- app/Core/: conexión PDO, lectura de campos HTTP, CSRF y renderizado.
- app/Views/: presentación de inicio, catálogo, producto, carrito, formulario y
  confirmación. Los parciales reutilizan cabecera, pie y tarjeta de producto.
- config/database.php: conexión configurable mediante variables de entorno.
- config/correo.php: valores de correo y carga de la configuración privada o del entorno.
- lib/PHPMailer/: archivos oficiales de la librería de correo, licencia y versión.
- database/: estructura, productos de muestra e instalador exclusivo de CLI.
- public/css/inicio.css: estilos compartidos y portada.
- public/css/componentes.css: formularios, tarjetas, avisos y botones compartidos.
- public/css/pedidos.css: componentes de HU18.
- public/css/registro.css y public/js/registro.js: presentación y control de contraseña de HU16/HU17.
- public/images/: cinco imágenes originales.
- tests/pedidos.php: pruebas del dominio y del flujo HTTP.
- tests/registro.php: pruebas del registro y de la activación.
- tests/acceso.php: pruebas de autenticación, sesiones y asociación de pedidos.
- tests/soporte.php: base, servidor y sesiones temporales compartidos por las pruebas.

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
| registro | GET, HEAD, POST | Crear una cuenta |
| verificar-cuenta | GET, HEAD, POST | Activar una cuenta o reenviar el código |
| registro-completado | GET, HEAD | Confirmación del registro verificado |
| cliente-login | GET, HEAD, POST | Iniciar sesión con una cuenta verificada |
| cerrar-sesion | POST | Cerrar la sesión de cliente conservando el carrito |

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
inicio.css conserva la portada y la cabecera/pie compartidos; componentes.css
reúne los formularios y tarjetas reutilizados. pedidos.css y registro.css
contienen los estilos de cada flujo. El registro usa los bloques registro y
contrasena, con elementos como registro__tarjeta y contrasena__boton.
El acceso reutiliza esos mismos componentes. El menú autenticado usa el bloque
cuenta y elementos como cuenta__activador y cuenta__salir.

## Verificación

    C:\xampp\php\php.exe tests\pedidos.php
    C:\xampp\php\php.exe tests\registro.php
    C:\xampp\php\php.exe tests\acceso.php
    C:\xampp\php\php.exe tests\correo.php

Las pruebas crean una base temporal qba_hu16_test_*, qba_hu17_test_* o qba_hu18_test_*, un servidor local y sus
sesiones, y los eliminan al terminar. Requieren permisos locales para crear y
eliminar esa base y crear un trigger de prueba. Cubren validaciones, precios
manipulados o modificados, productos retirados, CSRF, formularios malformados,
rollback de cabecera y detalles, doble envío y el recorrido HTTP completo.
El registro también comprueba contraseñas protegidas, correos duplicados,
códigos vencidos o bloqueados, reenvíos y conservación del carrito.
Las pruebas fuerzan la bandeja local o un receptor SMTP temporal en 127.0.0.1;
no usan las credenciales de Gmail ni envían correos fuera de esta máquina.
tests/correo.php comprueba la entrega real de PHPMailer a ese receptor, UTF-8,
reenvío, activación y recuperación después de un rechazo SMTP.
HU17 comprueba cuentas sin verificar, contraseñas inválidas, bloqueos persistentes,
renovación de cookies y CSRF, vencimiento de la sesión, conservación del carrito,
aislamiento entre identidades y asociación del pedido desde el servidor.

Para comprobar toda la sintaxis PHP en PowerShell:

    Get-ChildItem app,public,config,database,tests -Recurse -Filter *.php |
        ForEach-Object { C:\xampp\php\php.exe -l $_.FullName }
