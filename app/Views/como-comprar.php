<main id="contenido" class="contenedor informacion">
    <header class="informacion__encabezado">
        <span class="etiqueta">TUS PEDIDOS EN RUSTIC</span>
        <h1 class="informacion__titulo">Comprar en Rustic</h1>
        <p>Elegí tus productos y enviá tu pedido desde la web.</p>
    </header>
    <section class="informacion__tarjeta informacion__guia" id="como-comprar" aria-labelledby="titulo-como-comprar">
        <h2 class="informacion__subtitulo" id="titulo-como-comprar">¿Cómo comprar?</h2>
        <ol class="informacion__pasos">
            <li>
                <strong>Elegí tus productos.</strong> Recorré el <a class="informacion__enlace" href="<?= $url('productos') ?>">catálogo</a>, seleccioná las cantidades y agregalas al carrito. </li>
            <li>
                <strong>Ingresá a tu cuenta.</strong> Para confirmar el pedido necesitás iniciar sesión. Si todavía no tenés cuenta, <a class="informacion__enlace" href="<?= $url('registro') ?>">registrate</a> y verificá tu correo. </li>
            <li>
                <strong>Elegí cómo recibirlo.</strong> Seleccioná retiro en el local o envío a domicilio sin costo adicional. Indicá el nombre, la fecha y la hora; si elegís delivery, completá la dirección. El teléfono y las notas son opcionales. </li>
            <li>
                <strong>Elegí cómo pagar.</strong> Podés pagar en el local o transferir el total. Si elegís transferencia, después de confirmar vas a ver las instrucciones para realizarla y adjuntar el comprobante. </li>
            <li>
                <strong>Enviá el pedido.</strong> Al confirmar, vas a recibir un número de pedido. Queda pendiente de confirmación por el equipo. </li>
        </ol>
        <p class="informacion__nota">Confirmar el pedido no realiza un cobro automático. Si pagás por transferencia, el pago queda pendiente hasta que el equipo revise el comprobante.</p>
        <a class="boton boton--dorado" href="<?= $url('productos') ?>">Ver productos</a>
    </section>
    <div class="informacion__secciones">
        <section class="informacion__tarjeta" id="metodos-de-pago" aria-labelledby="titulo-metodos-de-pago">
            <h2 class="informacion__subtitulo" id="titulo-metodos-de-pago">Métodos de pago</h2>
            <p>Al realizar tu pedido podés elegir pagar en el local o transferir el total.</p>
            <p>Para transferir, seguí las instrucciones que aparecen al confirmar. También podés acceder desde Mis pedidos para adjuntar el comprobante y consultar su revisión.</p>
            <a class="informacion__enlace" href="<?= $url('contacto') ?>">Consultar medios de pago </a>
        </section>
        <section class="informacion__tarjeta" id="envios" aria-labelledby="titulo-envios">
            <h2 class="informacion__subtitulo" id="titulo-envios">Retiro y delivery</h2>
            <p>Podés retirar en el local de Uruguay 1486, Salto, o elegir envío a domicilio sin costo adicional al realizar el pedido.</p>
            <p>En ambos casos indicá una fecha y hora. El equipo se comunicará para coordinar la preparación y la entrega. Si tenés dudas sobre la zona de reparto, consultanos antes de confirmar.</p>
            <a class="informacion__enlace" href="<?= $url('contacto') ?>">Consultar opciones de entrega </a>
        </section>
        <section class="informacion__tarjeta" id="devoluciones" aria-labelledby="titulo-devoluciones">
            <h2 class="informacion__subtitulo" id="titulo-devoluciones">Devoluciones</h2>
            <p class="informacion__estado">Pendiente de publicación</p>
            <p>La política de devoluciones todavía no fue publicada. Si necesitás informar un problema con tu pedido, contactá a la panadería e indicá el número de pedido.</p>
            <a class="informacion__enlace" href="<?= $url('contacto') ?>">Consultar sobre un pedido </a>
        </section>
    </div>
    <a class="informacion__volver informacion__enlace" href="<?= $url('inicio') ?>"> Volver al inicio</a>
</main>
