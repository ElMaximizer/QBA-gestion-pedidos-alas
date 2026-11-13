# Cómo leer y mantener el código

El código de la aplicación usa nombres en español, funciones con una tarea clara
y bloques separados. Se prioriza poder seguir los pasos en orden.

## Dónde buscar

| Carpeta o archivo | Para qué sirve |
| --- | --- |
| `public/index.php` | Recibe las solicitudes y ejecuta la acción correspondiente. |
| `config/rutas.php` | Relaciona cada dirección del sitio con su controlador. |
| `app/Controllers/` | Lee los formularios, coordina las operaciones y elige la pantalla. |
| `app/Models/` | Valida los datos y consulta o modifica la base. |
| `app/Services/` | Coordina tareas como enviar correos o guardar imágenes. |
| `app/Views/` | Contiene el HTML y los datos que muestra cada pantalla. |
| `app/Views/partials/` | Contiene partes compartidas, como el encabezado y el pie. |
| `app/Core/` | Reúne herramientas comunes: sesión, conexión, direcciones y vistas. |
| `public/css/` | Define la presentación de las pantallas. |
| `public/js/` | Agrega las interacciones del navegador. |
| `tests/` | Comprueba los flujos usando bases y servidores temporales. |

Para seguir una pantalla, empezá por su dirección en `config/rutas.php`.
Después leé el controlador, los modelos que llama y la vista que muestra.

## PHP

- Usar cuatro espacios por nivel y dejar una línea en blanco entre tareas distintas.
- Escribir siempre las llaves de `if`, `foreach` y los demás bloques.
- Usar nombres como `$conexion`, `$configuracion` y `$productosPorId`.
- Separar la declaración de una propiedad y su asignación en el constructor.
- Preferir un `foreach` sencillo a varias funciones de transformación encadenadas.
- Usar `if` y `elseif` cuando hay varias decisiones. Evitar condicionales abreviados anidados.
- Separar los cálculos de la presentación. Por ejemplo, preparar el resumen de un
  pedido en un método propio y pasar sus resultados a la vista.
- Comentar el motivo de una decisión que no resulte evidente por sus nombres.

Las palabras que aparecen después de los parámetros o de `:` indican los tipos:
`string` es texto, `int` es un entero, `bool` es verdadero o falso y `array` es un
conjunto de datos. `?` permite también un valor vacío (`null`). `void` indica que
un método no devuelve un resultado y `never` que termina o redirige la solicitud.
`PDO` representa la conexión con la base. Una propiedad `readonly` se asigna una
sola vez, normalmente al crear el objeto.

En las vistas, `<?= $variable ?>` muestra un dato. `$escapar(...)` permite mostrar
texto recibido de una persona sin interpretarlo como HTML. `$url(...)` prepara
un enlace interno, `$recurso(...)` localiza una imagen o archivo y `$dinero(...)`
muestra un importe guardado en centavos.

`App\Core\Importe` comparte las conversiones entre los importes decimales de la
base y los centavos usados en los cálculos. `App\Services\ImagenComprobante`
comparte la lectura y validación de los comprobantes enviados por clientes y equipo.

Las vistas usan `if (...):` con `endif;` y `foreach (...):` con `endforeach;`.
Cada apertura, contenido y cierre debe verse en su nivel de sangría. Los campos
`textarea` conservan el contenido pegado a sus etiquetas para no agregar espacios
al valor del formulario.

## CSS

- Escribir cada propiedad en su propia línea y separar las reglas con una línea vacía.
- Agrupar las reglas por parte de la pantalla y explicar los grupos grandes con un comentario.
- Mantener juntas las adaptaciones a tamaños de pantalla cuando ya están agrupadas.
- Usar nombres que indiquen qué elemento se está dibujando.

Por ejemplo, `inicio-portada` es la portada, `inicio-portada__foto` es su fotografía
y `boton-rustic--claro` es una variante clara del botón. Los dos guiones bajos
separan un componente de una de sus partes; los dos guiones indican una variante.

`rustic-nuevo.css` contiene la presentación general de la tienda y las secciones
del inicio. `rustic-identidad.css` agrega ajustes compartidos de identidad visual.
Las demás hojas corresponden a pantallas, componentes o temas existentes.
El orden en que se cargan y el orden de sus reglas forman parte del diseño.

## JavaScript

Cada archivo empieza con una función de nombre descriptivo y termina llamándola.
Por ejemplo, `iniciarPanelCarrito()` prepara el carrito y `iniciarNavegacion()`
prepara el menú. Si sus controles no están en la página, la función termina.

Se usan funciones convencionales y recorridos `for` o `for...of`. Las acciones
largas se dividen en pasos con nombre, como abrir el panel, cargar el carrito,
actualizar su contenido y recuperar el foco del teclado.

`addEventListener` indica qué hacer ante una acción. `async` y `await` permiten
esperar una respuesta del servidor antes de continuar. Los atributos `data-*`
conectan los controles del HTML con sus scripts; los atributos `aria-*` describen
su estado para las herramientas de accesibilidad.

`consulta-filtros.js` comparte el estado de consulta y la recuperación del botón
al volver a la página en los filtros de personal y ventas.

## Aplicar el formato

Solo para trabajar en el código, instalar las herramientas una vez con:

```text
npm ci
```

Después se puede aplicar o comprobar el formato con:

```text
npm run format
npm run format:check
```

El formateador cubre el PHP de lógica, las pruebas, el CSS y el JavaScript.
Las vistas mixtas de HTML y PHP se revisan manualmente para conservar sus textos
y atributos. `.editorconfig` mantiene sus reglas básicas de sangría y guardado.
Se excluyen PHPMailer, las imágenes y la configuración privada del correo.
Node.js y estas herramientas no son necesarios para ejecutar la aplicación PHP.

## Verificación del refactor del 5 de noviembre de 2026

Se comprobó la sintaxis de 120 archivos PHP y de los siete scripts JavaScript.
Se compararon las reglas de las 25 hojas CSS y la estructura HTML, los atributos
y los textos normalizados de las 43 vistas. Los siete scripts conservaron sus
resultados en 44 estados comparados con la versión anterior, incluyendo errores,
foco del teclado, validaciones y límites de filas.

Las 15 pruebas generales se ejecutaron antes y después: nueve pasaron y seis
fallaron en las mismas comprobaciones que ya fallaban al iniciar el trabajo:

| Prueba | Comprobación que falla antes y después |
| --- | --- |
| `tests/acceso-equipo.php` | Acciones disponibles en el panel del administrador. |
| `tests/acceso.php` | Identidad obtenida al reutilizar la cookie anterior. |
| `tests/contenido-inicio.php` | Integración de marca, carrusel y carrito en el inicio. |
| `tests/mercaderia.php` | Integración de la recepción con stock y panel. |
| `tests/pedidos-equipo.php` | Enlaces al registro interno desde el panel y el listado. |
| `tests/productos.php` | Accesos de productos habilitados en el panel. |

Esos fallos previos quedan pendientes. El refactor conserva las funcionalidades,
las reglas de permisos y los datos; no modifica el esquema de la base.
