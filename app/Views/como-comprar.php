<main id="contenido" class="contenedor informacion">
    <header class="informacion__encabezado">
        <span class="etiqueta">TUS PEDIDOS EN RUSTIC</span>
        <h1 class="informacion__titulo">Comprar en Rustic</h1>
        <p>Elegí tus productos y enviá tu pedido desde la web.</p>
    </header>
    <section class="informacion__tarjeta informacion__guia" id="como-comprar" aria-labelledby="titulo-como-comprar">
        <h2 class="informacion__subtitulo" id="titulo-como-comprar">¿Cómo comprar?</h2>
        <ol class="informacion__pasos">
            <li><strong>Elegí tus productos.</strong> Recorré el <a class="informacion__enlace" href="<?= $url('productos') ?>">catálogo</a>, seleccioná las cantidades y agregalas al carrito.</li>
            <li><strong>Ingresá a tu cuenta.</strong> Para confirmar el pedido necesitás iniciar sesión. Si todavía no tenés cuenta, <a class="informacion__enlace" href="<?= $url('registro') ?>">registrate</a> y verificá tu correo.</li>
            <li><strong>Completá los datos de retiro.</strong> Revisá el carrito e indicá el nombre, teléfono, fecha y hora de retiro. Podés agregar notas para el equipo.</li>
            <li><strong>Enviá el pedido.</strong> Al confirmar, vas a recibir un número de pedido. Queda pendiente de confirmación por el equipo.</li>
        </ol>
        <p class="informacion__nota">No se realiza ningún cobro en línea al enviar el pedido.</p>
        <a class="boton boton--dorado" href="<?= $url('productos') ?>">Ver productos</a>
    </section>
    <div class="informacion__secciones">
        <section class="informacion__tarjeta" id="metodos-de-pago" aria-labelledby="titulo-metodos-de-pago">
            <h2 class="informacion__subtitulo" id="titulo-metodos-de-pago">Métodos de pago</h2>
            <p class="informacion__estado">Pendiente de publicación</p>
            <p>Los medios de pago aceptados se publicarán en este espacio. Por ahora, consultalos directamente con la panadería.</p>
            <a class="informacion__enlace" href="<?= $url('contacto') ?>">Consultar medios de pago →</a>
        </section>
        <section class="informacion__tarjeta" id="envios" aria-labelledby="titulo-envios">
            <h2 class="informacion__subtitulo" id="titulo-envios">Envíos</h2>
            <p class="informacion__estado">Pendiente de publicación</p>
            <p>La información sobre envíos todavía no fue publicada. Los pedidos de esta web solicitan fecha y hora de retiro; para consultar otras opciones, comunicate con la panadería.</p>
            <a class="informacion__enlace" href="<?= $url('contacto') ?>">Consultar opciones de entrega →</a>
        </section>
        <section class="informacion__tarjeta" id="devoluciones" aria-labelledby="titulo-devoluciones">
            <h2 class="informacion__subtitulo" id="titulo-devoluciones">Devoluciones</h2>
            <p class="informacion__estado">Pendiente de publicación</p>
            <p>La política de devoluciones todavía no fue publicada. Si necesitás informar un problema con tu pedido, contactá a la panadería e indicá el número de pedido.</p>
            <a class="informacion__enlace" href="<?= $url('contacto') ?>">Consultar sobre un pedido →</a>
        </section>
    </div>
    <a class="informacion__volver informacion__enlace" href="<?= $url('inicio') ?>">← Volver al inicio</a>
</main>
