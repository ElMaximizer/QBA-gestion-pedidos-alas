# QBA — Gestión de pedidos de Panadería Alas

Proyecto PHP con MVC y CSS según BEM. Conserva el diseño público del
[repositorio anterior](https://github.com/ElMaximizer/software-pedidos-alas/tree/6e4a0b26bc2ba412db66dd1541acb209d3e4f6ae).

## Registro del equipo

Desde **Acceso al equipo → Solicitar usuario**, en el pie del sitio, se puede solicitar un usuario
con nombre, cédula, correo y contraseña. Se conserva el formulario del proyecto
anterior y el flujo de solicitud pendiente de aprobación del dueño.

La solicitud se guarda en **usuarios_equipo**, con rol empleado y estado
pendiente fijados por el servidor. El formulario no permite elegir privilegios.
La cédula acepta de 6 a 8 dígitos sin puntos ni guiones y se guarda numéricamente,
como en el sistema anterior: los ceros iniciales no permiten duplicarla.
Cédula y correo tienen restricciones únicas en MySQL. El correo se normaliza y
la contraseña se guarda mediante password_hash, conservando sus espacios.

Después del guardado se envía un aviso de recepción mediante PHPMailer y la
misma configuración de correo de HU16. Ese aviso no incluye cédula ni contraseña.
Si falla, la solicitud permanece pendiente y la confirmación informa el problema.
No se envía un código de verificación para este registro: la habilitación depende
de la revisión posterior del dueño, igual que en el flujo activo anterior.

Solicitar acceso no inicia sesión ni concede permisos. Las cuentas del equipo
y de clientes permanecen independientes y pueden compartir correo. Se conserva
el carrito y, si existe, la sesión de cliente. Las contraseñas no se recuperan
en el formulario ni se guardan en la sesión.

Esta etapa incorpora el registro y la confirmación de la solicitud. La aprobación,
el rechazo y la asignación de otros roles se implementarán en sus pasos
correspondientes. Todavía no hay un panel de revisión
ni una cuenta de dueño creada automáticamente.

Para incorporar la tabla sobre una instalación existente:

    C:\xampp\php\php.exe database\instalar.php

El instalador conserva los clientes, productos y pedidos existentes. El origen
visual y funcional es el [registro del equipo anterior](https://github.com/ElMaximizer/software-pedidos-alas/blob/produccion/app/Views/auth/pagina.php).

## Inicio de sesión del equipo

**Acceso al equipo**, en el pie del sitio, abre el formulario de cédula y
contraseña del diseño anterior. Solo ingresan cuentas de usuarios_equipo con
estado aprobado y rol empleado, administrador o dueño. Una contraseña incorrecta
y una cédula inexistente reciben el mismo error. Una solicitud pendiente o
rechazada no obtiene acceso; el aviso de aprobación se muestra únicamente
después de comprobar la contraseña.

Al ingresar aparece el panel del equipo con el nombre, el rol y el cierre
de sesión. Es el punto de entrada para las funciones que se implementen después;
su estructura se describe en la sección siguiente.
El menú **Hola, nombre → Panel del equipo** permite regresar desde el sitio.
La recuperación de contraseña y el panel de aprobación siguen pendientes.
No se crean dueños ni se aprueban solicitudes automáticamente.

La sesión guarda el identificador del usuario y consulta su aprobación y rol
en la base una vez por petición. Un cambio de rol se refleja en la siguiente
petición; revocar la aprobación cierra el acceso. Vence después de treinta
minutos sin actividad. Al ingresar o salir se renuevan la cookie y CSRF, se
conserva el carrito y se invalidan los formularios de la identidad anterior.
Iniciar sesión como cliente sustituye la sesión del equipo y viceversa.
El cierre requiere POST y CSRF; la pantalla de acceso y el panel no se guardan
en caché.

Ambos accesos comparten la comprobación de contraseñas y el límite persistente
de intentos, con contadores independientes. Seis contraseñas incorrectas para
la misma cédula bloquean el acceso durante un minuto, incluso desde otra sesión.
Los ceros iniciales no permiten eludirlo. Los intentos aislados vencen a los diez
minutos; un acceso con contraseña correcta los elimina. La tabla
intentos_acceso_equipo conserva una clave SHA-256 de la cédula, contadores y
fechas UTC, sin contraseñas. Se limpian hasta cien registros de más de un día
por intento. El acceso no envía correos.

En una instalación existente, ejecutá el instalador sin --demo para agregar
la tabla de intentos conservando los datos y las aprobaciones actuales:

    C:\xampp\php\php.exe database\instalar.php

## Panel del equipo — estructura visual

La rama técnica admin/panel incorpora solamente la presentación del
[panel anterior](https://github.com/ElMaximizer/software-pedidos-alas/blob/produccion/app/Views/admin/panel.php):
cabecera interna con Salir, navegación, tarjetas de resumen, Próximos retiros,
Atención del equipo y pie de gestión interna. Reutiliza los colores, tamaños y
componentes existentes con clases BEM y adaptación a pantallas pequeñas.

Empleado, administrador y dueño comparten la misma pantalla. Las opciones de
Atención del equipo conservan la visibilidad del proyecto anterior: consultas
para todos; alta de personal y bandeja local para administrador; gestión de
personal, solicitudes, actividad y bandeja local para dueño. Esa visibilidad
es únicamente presentación; los permisos de cada futura operación deberán
validarse en su implementación correspondiente. HU06 ya incorpora esa validación
para la gestión de productos.

HU06 muestra en **Productos activos** la cantidad real de productos activos y
de sus categorías, excluyendo los eliminados. Se actualiza al volver al panel
después de agregar, editar, activar, desactivar o eliminar productos. Los otros
contadores muestran — y el área de retiros muestra Próximamente. Los accesos
a módulos futuros están deshabilitados y no tienen URL ni
formularios de operación. HU06 habilita **Productos** y **+ Agregar producto**
para administrador y dueño. También funcionan el regreso al sitio, el regreso
al panel y el cierre de sesión.

PanelEquipoController obtiene la identidad aprobada de la sesión y prepara la
vista equipo-panel. Los parciales equipo-encabezado y equipo-pie comparten la
estructura HTML general del sitio; panel.css agrega los estilos internos sin
cargar los estilos de registro o pedidos. La autenticación continúa a cargo
de AccesoEquipoController y Sesion.

## HU06 — Administrar productos

Desde **Panel → Productos**, administrador y dueño pueden agregar, editar,
desactivar y eliminar productos. El acceso se comprueba en el servidor en cada
petición; un empleado recibe 403 aunque escriba la URL directamente o modifique
el rol enviado por un formulario. Un visitante o cliente debe iniciar sesión
como integrante del equipo. No se crea ni se aprueba ninguna cuenta al instalar
esta historia.

Se conservan el [listado](https://github.com/ElMaximizer/software-pedidos-alas/blob/produccion/app/Views/admin/productos.php)
y el [formulario anterior](https://github.com/ElMaximizer/software-pedidos-alas/blob/produccion/app/Views/admin/producto-form.php),
con su cabecera, colores, medidas, confirmación y adaptación a celular. Se
reutilizan las tarjetas, campos y avisos, con clases BEM. Alta y edición comparten
el mismo formulario. JavaScript solo agrega la vista previa de imagen; todo el
guardado funciona sin JavaScript.

El listado muestra inicialmente los productos activos. Busca por nombre o código
PRD-001 y permite seleccionar inactivos o todos los estados. Muestra 25 productos
por página y mantiene los filtros al cambiar de página. El
formulario incluye nombre, categoría, descripción, precio, presentación,
ingredientes, alérgenos, estado e imagen opcional. Las categorías del proyecto
anterior son Panes, Bizcochos, Tortas y Especiales.

El servidor valida longitudes, categoría y estado. El precio admite coma o punto,
debe estar entre 0,01 y 99.999.999,99 y tener como máximo dos decimales. Se procesa
como centavos enteros y se guarda en DECIMAL, sin cálculos con flotantes. Los
campos no editables, como id, destacado, imagen y eliminado_en enviados como
texto, no cambian los valores que decide el servidor. El id a editar o eliminar
sale de la URL; la imagen se obtiene exclusivamente de la subida del archivo.

**Inactivo** oculta temporalmente el producto y permite editarlo o reactivarlo.
**Eliminar** primero muestra una confirmación y después requiere POST y CSRF.
Hace una baja lógica: marca activo=0 y eliminado_en en UTC, conservando la fila
y la referencia de los pedidos. Los detalles del pedido mantienen sus nombres y
precios originales. El catálogo, el carrito y el cierre del pedido respetan la
disponibilidad y los precios actualizados.

Editar y eliminar comprueban dentro de una transacción la versión leída por el
formulario. Si otra pestaña cambió el producto, se solicita volver al listado y
abrirlo nuevamente. Los cambios se confirman con una redirección 303 y un aviso;
recargar el listado no repite el guardado. Los errores de MySQL se informan con
un mensaje general, sin mostrar consultas ni detalles internos.

Las fotografías admiten JPG o PNG de hasta 4 MB y 8000 píxeles por lado. Se
comprueban el archivo recibido por PHP, su tamaño real, tipo y dimensiones; el
nombre original no se utiliza. Se guardan con un nombre aleatorio bajo
public/images/productos/ y se muestran en el catálogo, detalle y carrito. Sin
fotografía se usa la ilustración de la categoría. Si falla el guardado, se retira
la imagen recién subida y se conserva la anterior. Al reemplazar o eliminar, se
retira la fotografía que deja de utilizarse después de confirmar la base.
Referencia: [subidas de archivos en PHP](https://www.php.net/manual/en/features.file-upload.php).

La carpeta de imágenes necesita permisos de escritura para PHP. Se versiona su
.htaccess de protección; las fotos son datos de la aplicación y están excluidas
de Git. Para trasladar o respaldar un catálogo real, se conserva esa carpeta
junto con la base de datos.

HU06 utiliza la tabla productos que ya existe en qba_pedidos_alas. No requiere
migraciones, nuevas tablas ni volver a cargar los productos de demostración.
GestionProductoController coordina los formularios; Producto valida y consulta
los datos; GestionProducto coordina el guardado con ImagenProducto. Las vistas
reutilizan la estructura interna del panel y gestion-productos.css contiene sus
estilos específicos.

## HU09 — Registrar entregas de mercadería

Desde **Panel → Registrar mercadería** o **Stock → Registrar mercadería**, una
cuenta aprobada de administrador o dueño registra la recepción de un proveedor.
Se indica proveedor, fecha de recepción (hasta hoy), comprobante y observaciones
opcionales, y entre 1 y 20 materias primas activas con sus cantidades recibidas.
Las unidades se toman del inventario de HU07; no se convierten unidades ni se
modifican el stock mínimo o los productos del catálogo. Una materia prima se
incluye una sola vez por entrega. El proveedor se registra por nombre, sin
incorporar un módulo de administración de proveedores.

Las cantidades admiten coma o punto y hasta tres decimales. El cálculo usa
milésimas enteras, compartido con HU07 mediante CantidadStock. Solo se aceptan
cantidades mayores que cero y el stock resultante no puede superar
999.999.999,999. Los insumos se bloquean en orden dentro de una transacción:
el historial, sus detalles y todo el stock se confirman juntos o se revierten.
Esto evita perder sumas de otras recepciones concurrentes. Un formulario de
ajuste manual de HU07 anterior a una recepción debe recargarse antes de guardar.

CSRF y un formulario de sesión válido durante 30 minutos protegen el registro.
Una clave única en la base evita duplicar una recepción por doble clic o reenvío
del mismo formulario, también si dos solicitudes llegan al mismo tiempo. Después
del guardado se redirige con 303 al historial. La identidad del responsable, las
unidades y los saldos los determina el servidor; no se aceptan del navegador.
El permiso se vuelve a comprobar en cada petición, incluido un formulario abierto.

**Stock → Ver entregas de proveedores** muestra 25 recepciones por página y
permite buscar por proveedor, comprobante o materia prima. Cada recepción
conserva el responsable, fecha, observaciones y el nombre, unidad, cantidad y
saldo anterior/resultante de cada insumo. Los nombres históricos se mantienen
aunque luego cambie el inventario. Esta historia registra recepciones; no incluye
edición, eliminación, devoluciones ni facturación de proveedores.

El formulario y el historial reutilizan el diseño interno y los componentes de
stock, con estilos BEM en mercaderia.css. JavaScript agrega y quita filas sin
recargar; las mismas acciones funcionan mediante POST sin JavaScript y no
modifican el stock hasta confirmar la recepción.

Para actualizar una instalación existente, ejecutar desde la raíz del proyecto:

    C:\xampp\php\php.exe database\instalar.php

El instalador aplica **database/entregas-mercaderia.sql** después del inventario y
las cuentas del equipo. Crea entregas_mercaderia y entrega_mercaderia_detalles
sin borrar ni recargar datos. No usar --demo para actualizar una instalación real.
Cambiar de rama no actualiza la base automáticamente.

Rutas: **/equipo/stock/entregas** (GET/HEAD) y
**/equipo/stock/entregas/registrar** (GET/HEAD/POST).
EntregasMercaderiaController coordina el formulario y la sesión;
EntregaMercaderia valida, registra la transacción y consulta el historial;
StockMateriaPrima proporciona los insumos activos.

Para probar esta historia en una base temporal aislada, con limpieza al terminar:

    C:\xampp\php\php.exe tests\mercaderia.php

Las pruebas cubren precisión decimal, validaciones, permisos, CSRF, reenvíos,
recepciones simultáneas con conexiones independientes, rollback completo,
integración con HU07, conservación del historial y la
actualización de la base sin pérdida de recepciones.

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

Al ingresar se vuelve al inicio, o al formulario de pedido si el acceso se pidió
desde el carrito. Aparece el menú **Hola, nombre** con **Cerrar sesión**. El
destino se fija en la sesión del servidor; no se aceptan URLs de retorno enviadas
por el navegador. El registro y el acceso reutilizan el campo de contraseña, sus estilos
BEM y el control para mostrarla. Los formularios funcionan sin JavaScript.
La recuperación de contraseña y el historial de **HU20**
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

## HU01 — Registrar un pedido desde el equipo

Administrador y dueño acceden desde **Nuevo pedido**, en el panel o en el
listado de pedidos. La dirección es `/equipo/pedidos/registrar`. Se conserva
la distribución del formulario anterior: cliente y retiro, líneas de productos
y un resumen lateral con el total.

El cliente atendido en el mostrador o por teléfono no necesita una cuenta web.
Se registran nombre, teléfono, fecha y hora de retiro, productos, cantidades y
notas opcionales. Solo se ofrecen productos activos; se permiten hasta 50
productos distintos y entre 1 y 99 unidades por producto. El resumen se actualiza
con JavaScript y también al agregar o quitar líneas mediante el servidor.

Se reutiliza `Pedido` para validar, calcular en centavos y guardar cabecera y
detalles en una transacción. El pedido comienza en **Pendiente** y aparece en
el listado de HU04. Los precios se comprueban nuevamente al guardar: si cambiaron,
se muestran los nuevos importes para revisarlos antes de volver a confirmar.
El token del formulario evita duplicados por doble envío. Un error conserva
los datos ingresados. El carrito público no se utiliza ni se vacía.

`id_usuario_registro` identifica a la cuenta del equipo que tomó el pedido;
`id_cliente` conserva NULL para estos pedidos. Ambos identificadores, el total
y el estado se deciden en el servidor. Los pedidos web conservan su asociación
al cliente. La creación para empleados queda pendiente de HU11.

Para actualizar una instalación existente, ejecutar el instalador habitual:

    C:\xampp\php\php.exe database\instalar.php

También se puede ejecutar `database/pedidos-equipo.sql` desde MySQL sobre la base
del proyecto, después de `estructura.sql` y `equipo.sql`. Agrega la columna del
responsable y su clave foránea sin modificar los pedidos existentes y admite
ejecuciones repetidas. No se necesita cargar nuevamente los datos de demostración.

## HU18 — Realizar un pedido

El cliente puede explorar y filtrar productos, ver su detalle, agregar cantidades
al carrito y actualizarlo sin iniciar sesión. Para completar sus datos y confirmar
debe ingresar con una cuenta de cliente verificada. Al
confirmar, el pedido y sus líneas se guardan en MySQL como **Pendiente**, se vacía
el carrito y se muestra el número del pedido.

El servidor protege tanto la apertura del formulario como su envío por POST.
Sin sesión de cliente, o si esta venció, se solicita iniciar sesión antes de
continuar, conservando el carrito. Al ingresar se renuevan la cookie, CSRF y los
formularios, y se vuelve al pedido sin enviarlo automáticamente. Registrarse y
verificar el correo antes de ingresar también conserva esa continuación.
La sesión del equipo no reemplaza la cuenta de cliente para realizar un pedido
desde la web. Los pedidos nuevos se asocian al id_cliente de la sesión, ignorando
los identificadores enviados por el navegador. Los pedidos históricos con
id_cliente NULL se conservan. No hay cambios en la estructura de la base.
No hay cobros en línea, registro de señas, envío de
correos, panel administrativo ni historial de pedidos en esta historia.
Los enlaces de nosotros y contacto siguen inactivos.

## Requisitos

- PHP 8.2 o superior con PDO MySQL, mbstring, OpenSSL, fileinfo y sesiones habilitados.
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
clientes, correos_locales, intentos_acceso_cliente, usuarios_equipo e
intentos_acceso_equipo. database/clientes.sql incorpora las tablas de HU16
sobre la base existente, conservando los productos y pedidos registrados.
La opción --demo agrega tres productos de muestra, con sus textos e ingredientes
de demostración. Se deben revisar esos datos antes de usar un catálogo real.
Sin esa opción se crea solamente la estructura. La instalación se puede repetir
y no importa datos del repositorio anterior.

Si ya tenías HU16 instalado, ejecutá el instalador sin --demo para incorporar
la tabla de HU17 conservando los datos existentes:

    C:\xampp\php\php.exe database\instalar.php

## Ejecutar en desarrollo

    C:\xampp\php\php.exe -S 127.0.0.1:8000 -t public public/router.php

Abrir <http://127.0.0.1:8000/>. Para detener el servidor, pulsar Ctrl+C en esa
terminal. El servidor integrado es para desarrollo local. La carpeta de sesiones
configurada por PHP debe tener permisos de escritura.

Con Apache de XAMPP también se puede abrir
<http://localhost/QBA-gestion-pedidos-alas/public/>.
La raíz pública de un virtual host debe apuntar a public/.
Apache usa public/.htaccess para dirigir las páginas al punto de entrada MVC.
Necesita mod_rewrite y AllowOverride habilitados; ya están activos en el XAMPP
local utilizado para probar el proyecto. No hace falta modificar su configuración.
El servidor integrado de PHP usa public/router.php para resolver las mismas
direcciones y servir los recursos estáticos; ese archivo solo funciona en ese
servidor de desarrollo. Referencias: [Apache](https://httpd.apache.org/docs/2.4/rewrite/flags.html)
y [servidor de PHP](https://www.php.net/manual/en/features.commandline.webserver.php).

## Recorrido MVC

- public/index.php: rutas permitidas, métodos HTTP y manejo de errores.
- config/rutas.php: nombres internos, direcciones públicas, controladores y métodos en una lista única.
- app/Core/Rutas.php: resolución de direcciones y generación de enlaces y recursos.
- public/.htaccess y public/router.php: entrada MVC desde Apache y el servidor de desarrollo.
- app/bootstrap.php: autocarga, zona horaria y sesión.
- app/Controllers/: InicioController, ProductoController, CarritoController y
  PedidoController coordinan las peticiones.
- app/Models/Producto.php: catálogo, validación y gestión de productos, versiones y baja lógica.
- app/Models/Carrito.php: cantidades y resumen del carrito.
- app/Models/Pedido.php: validaciones y guardado transaccional.
- app/Models/Cliente.php: registro, códigos y activación de cuentas.
- app/Models/AccesoCliente.php: credenciales, límite de intentos e identidad verificada.
- app/Models/AccesoEquipo.php: credenciales del equipo e identidad aprobada.
- app/Models/IntentosAcceso.php: límite persistente compartido, con contadores independientes.
- app/Models/UsuarioEquipo.php: validación de cédula y guardado de solicitudes pendientes.
- app/Core/DatosCuenta.php: reglas compartidas de nombre, correo, cédula y contraseñas.
- app/Controllers/AccesoController.php: inicio y cierre de sesión de HU17.
- app/Controllers/AccesoEquipoController.php: inicio y cierre de sesión del equipo.
- app/Controllers/PanelEquipoController.php: presentación del panel para la identidad aprobada.
- app/Controllers/GestionProductoController.php: listado, alta, edición y eliminación de HU06.
- app/Services/GestionProducto.php e ImagenProducto.php: guardado e imágenes del catálogo.
- app/Controllers/RegistroController.php: formularios y resultados de HU16.
- app/Controllers/RegistroEquipoController.php: registro y confirmación de solicitudes del equipo.
- app/Services/RegistroCliente.php: coordina registro y entrega del código.
- app/Services/Correo.php: transporte compartido de correo local o SMTP con PHPMailer.
- app/Services/CorreoVerificacion.php: contenido del código de verificación de clientes.
- app/Services/RegistroEquipo.php: guardado de la solicitud y aviso de recepción.
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
- public/css/panel.css: estructura visual del panel y navegación interna del equipo.
- public/css/gestion-productos.css y public/js/productos.js: presentación de HU06 y vista previa de imágenes.
- public/css/registro.css y public/js/registro.js: presentación y control de contraseña de HU16/HU17.
- public/images/: cinco imágenes originales.
- tests/pedidos.php: pruebas del dominio y del flujo HTTP.
- tests/productos.php: validaciones, permisos, imágenes, concurrencia y conservación de pedidos de HU06.
- tests/registro.php: pruebas del registro y de la activación.
- tests/acceso.php: pruebas de autenticación, sesiones y asociación de pedidos.
- tests/equipo.php: validaciones, duplicados, solicitud pendiente y aislamiento de identidades.
- tests/acceso-equipo.php: aprobación, intentos, sesiones del equipo y cambio de identidad.
- tests/soporte.php: base, servidor y sesiones temporales compartidos por las pruebas.

El punto de entrada selecciona un controlador. El controlador coordina la
petición, los modelos aplican reglas y consultan o guardan datos, y las vistas
presentan el resultado. Inicio y catálogo reutilizan la misma tarjeta de
producto y la misma fuente de datos.

## Rutas

Todas las páginas siguen pasando internamente por public/index.php. La dirección
visible usa rutas amigables; config/rutas.php es la lista única para resolverlas
y generar enlaces, formularios y redirecciones.

| Dirección desde public/ | Métodos | Pantalla u operación |
| --- | --- | --- |
| / | GET, HEAD | Inicio |
| /productos | GET, HEAD | Catálogo, búsqueda q y filtro categoria |
| /producto?id=1 | GET, HEAD | Detalle por id |
| /carrito | GET, HEAD, POST | Ver, agregar, actualizar, quitar o vaciar |
| /pedidos/realizar | GET, HEAD, POST | Revisar datos y confirmar |
| /pedidos/confirmado | GET, HEAD | Número guardado en la sesión |
| /clientes/registro | GET, HEAD, POST | Crear una cuenta |
| /clientes/verificar | GET, HEAD, POST | Activar una cuenta o reenviar el código |
| /clientes/registro-completado | GET, HEAD | Confirmación del registro verificado |
| /clientes/login | GET, HEAD, POST | Iniciar sesión con una cuenta verificada |
| /clientes/salir | POST | Cerrar la sesión de cliente conservando el carrito |
| /equipo/registro | GET, HEAD, POST | Solicitar un usuario del equipo |
| /equipo/solicitud-enviada | GET, HEAD | Confirmación de la solicitud de esta sesión |
| /equipo/login | GET, HEAD, POST | Iniciar sesión con cédula y contraseña |
| /equipo/panel | GET, HEAD | Panel visual de la cuenta aprobada del equipo |
| /equipo/pedidos | GET, HEAD, POST | Consultar pedidos del equipo y registrar la seña según permisos |
| /equipo/pedidos/registrar | GET, HEAD, POST | Registrar un pedido como administrador o dueño |
| /equipo/salir | POST | Cerrar la sesión del equipo conservando el carrito |
| /equipo/productos | GET, HEAD | Listado y filtros de productos para administrador y dueño |
| /equipo/productos/agregar | GET, HEAD, POST | Agregar un producto |
| /equipo/productos/editar?id=1 | GET, HEAD, POST | Editar un producto existente |
| /equipo/productos/eliminar?id=1 | GET, HEAD, POST | Revisar y confirmar la baja lógica |

En XAMPP, por ejemplo, el panel se abre en
<http://localhost/QBA-gestion-pedidos-alas/public/equipo/panel>.
Al configurar public/ como raíz de un sitio, la misma ruta queda en /equipo/panel.
La carpeta de instalación se calcula desde el script ejecutado por el servidor;
no se fija el nombre del repositorio ni se utiliza el encabezado Host para generar
direcciones. Los recursos usan esa misma base y siguen cargando desde páginas
anidadas como /equipo/registro. No se usa una etiqueta base HTML.

Los filtros y los identificadores continúan como parámetros de consulta; por
ejemplo, /productos?categoria=Panes o /producto?id=1. El selector ruta ya no se
genera en las vistas. Los enlaces antiguos index.php?ruta=... siguen disponibles:
GET y HEAD redirigen con 302 a la dirección nueva, conservando los demás parámetros.
Los formularios POST antiguos se procesan una sola vez, mantienen CSRF y terminan
con la redirección habitual a la dirección nueva. Un parámetro ruta sobre una
dirección amigable se rechaza con 404 y no cambia su controlador.

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
El registro del equipo reutiliza registro, campo y contrasena; campo__ayuda
presenta las indicaciones de cédula, correo y contraseña. La ilustración lateral
se comparte entre los formularios, la confirmación y la pantalla protegida.

## Verificación

    C:\xampp\php\php.exe tests\pedidos-equipo.php
    C:\xampp\php\php.exe tests\pedidos.php
    C:\xampp\php\php.exe tests\productos.php
    C:\xampp\php\php.exe tests\registro.php
    C:\xampp\php\php.exe tests\acceso.php
    C:\xampp\php\php.exe tests\equipo.php
    C:\xampp\php\php.exe tests\acceso-equipo.php
    C:\xampp\php\php.exe tests\correo.php
    C:\xampp\php\php.exe tests\rutas.php

Las pruebas crean una base temporal qba_hu01_test_*, qba_hu06_test_*, qba_hu16_test_*, qba_hu17_test_*, qba_hu18_test_* o qba_equipo_test_*, un servidor local y sus
sesiones, y los eliminan al terminar. Requieren permisos locales para crear y
eliminar esa base y crear un trigger de prueba. Cubren validaciones, precios
manipulados o modificados, productos retirados, CSRF, formularios malformados,
rollback de cabecera y detalles, doble envío y el recorrido HTTP completo.
HU01 comprueba los permisos del equipo, el responsable del registro, la separación
del carrito público, líneas sin JavaScript, cambios del catálogo, recuperación de
errores y actualización de una base existente sin perder pedidos.
El registro también comprueba contraseñas protegidas, correos duplicados,
códigos vencidos o bloqueados, reenvíos y conservación del carrito.
Las pruebas fuerzan la bandeja local o un receptor SMTP temporal en 127.0.0.1;
no usan las credenciales de Gmail ni envían correos fuera de esta máquina.
tests/correo.php comprueba la entrega real de PHPMailer a ese receptor, UTF-8,
reenvío, activación y recuperación después de un rechazo SMTP.
HU17 comprueba cuentas sin verificar, contraseñas inválidas, bloqueos persistentes,
renovación de cookies y CSRF, vencimiento de la sesión, conservación del carrito,
aislamiento entre identidades y asociación del pedido desde el servidor.
El registro del equipo también comprueba cédula y correo únicos, roles y estados
manipulados, guardado y entrega fallidos, ausencia de contraseñas en la sesión y
conservación de la identidad de cliente. La prueba SMTP incluye el aviso de
recepción del equipo tanto aceptado como rechazado por el receptor temporal.
El acceso del equipo comprueba los tres roles, solicitudes no aprobadas,
bloqueo persistente, cambio y revocación de permisos, vencimiento, renovación
de cookies y CSRF, protección del panel y sustitución de la identidad de cliente.
Las cuentas aprobadas de prueba existen únicamente en la base temporal.
HU06 comprueba precios y campos inválidos, permisos por rol, CSRF, búsqueda y
paginación, imágenes falsas o excesivas, reemplazo y limpieza tras un fallo de
MySQL, versiones de formularios y conservación de pedidos históricos. El servidor
temporal utiliza su propia carpeta de subidas temporales y retira las fotos de
prueba al terminar; no utiliza los productos ni las cuentas de la base real.
Las pruebas de rutas comprueban la instalación en una carpeta o en la raíz,
recursos desde páginas anidadas, filtros, enlaces antiguos, métodos HTTP,
direcciones desconocidas y protección de los archivos internos. Los recorridos
existentes de registro, acceso y pedidos también usan las direcciones nuevas.

Para comprobar toda la sintaxis PHP en PowerShell:

    Get-ChildItem app,public,config,database,tests -Recurse -Filter *.php |
        ForEach-Object { C:\xampp\php\php.exe -l $_.FullName }
