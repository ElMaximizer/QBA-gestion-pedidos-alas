# QBA — Gestión de pedidos de Panadería Alas

Proyecto PHP organizado con MVC y clases CSS según la metodología BEM.
La pantalla de inicio conserva el diseño del repositorio original: cabecera,
portada, categorías, destacados y pie de página. Las funcionalidades se
incorporarán de forma incremental según las historias de usuario del equipo.

## Requisitos

- PHP 8.2 o superior, disponible en la instalación local de XAMPP.
- Un navegador actualizado.

La pantalla actual funciona sin base de datos ni dependencias externas. Las
categorías y los productos son muestras estáticas del diseño; los enlaces a
secciones todavía no implementadas se muestran inactivos, con `aria-disabled`.

## Ejecutar en desarrollo

Desde la raíz del repositorio, en PowerShell:

```powershell
C:\xampp\php\php.exe -S 127.0.0.1:8000 -t public
```

Abrir <http://127.0.0.1:8000/>. Para detener el servidor, pulsar `Ctrl+C`
en esa terminal. El servidor integrado de PHP se utiliza para desarrollo local.

Si Apache de XAMPP está iniciado y el proyecto está en `htdocs`, también se
puede abrir <http://localhost/QBA-gestion-pedidos-alas/public/index.php>.
Al configurar un virtual host, su raíz pública debe apuntar a `public/`.

## Estructura y recorrido de una petición

```text
app/
├── Controllers/InicioController.php
├── Models/
└── Views/
    ├── inicio.php
    └── partials/
        ├── encabezado.php
        └── pie.php
config/
database/
public/
├── index.php
├── css/inicio.css
├── js/
└── images/
    ├── panes.png
    ├── carrito.svg
    ├── pan.svg
    ├── croissant.svg
    └── torta.svg
```

1. `public/index.php` recibe la petición y valida la ruta.
2. `InicioController` prepara los datos y selecciona la vista.
3. `app/Views/inicio.php` genera el HTML, escapa los valores que recibe e incluye
   los parciales de cabecera y pie para poder reutilizarlos en otras pantallas.

Los modelos se agregarán al incorporar lógica de dominio y persistencia.
Las vistas contienen la presentación; las consultas y operaciones de datos
corresponderán a la capa de modelos.

## Rutas actuales

| Petición | Resultado |
| --- | --- |
| `GET /` o `GET /index.php` | Pantalla de inicio |
| `GET /?ruta=inicio` | La misma pantalla de inicio |
| Otra ruta en el parámetro `ruta`, incluso un valor que sea un array | HTTP 404 |
| Una ruta adicional como `/no-existe` | HTTP 404 |
| Un método distinto de GET o HEAD sobre el index | HTTP 405 |

El parámetro `ruta` selecciona únicamente rutas permitidas. No se utiliza para
construir nombres de archivos. Las rutas de las próximas funcionalidades se
agregarán conforme se implementen sus controladores.

## Convención BEM

Los bloques principales son `encabezado`, `navegacion`, `portada`, `categoria`,
`producto` y `pie-pagina`. Sus elementos usan el formato `bloque__elemento`, como
`portada__titulo` y `producto__precio`. Los botones comparten el bloque `boton`
y los modificadores `boton--dorado` y `boton--contorno`.

Los colores, tamaños, espaciados y puntos de adaptación se tomaron de
`public/css/alas.css` del [repositorio original](https://github.com/ElMaximizer/software-pedidos-alas/tree/6e4a0b26bc2ba412db66dd1541acb209d3e4f6ae).
Se incorporaron únicamente los estilos del inicio y sus cinco imágenes, con
nombres BEM para los componentes. Las imágenes conservan sus archivos originales.

## Comprobar la sintaxis PHP

```powershell
C:\xampp\php\php.exe -l public\index.php
C:\xampp\php\php.exe -l app\Controllers\InicioController.php
C:\xampp\php\php.exe -l app\Views\inicio.php
C:\xampp\php\php.exe -l app\Views\partials\encabezado.php
C:\xampp\php\php.exe -l app\Views\partials\pie.php
```
