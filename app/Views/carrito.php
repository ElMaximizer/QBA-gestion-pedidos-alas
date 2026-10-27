<main id="contenido" class="contenedor pagina-pedidos">
    <a class="enlace-volver" href="<?= $url('productos') ?>">← Seguir comprando</a>
    <div class="titulo-pagina">
        <h1 class="titulo-pagina__titulo">Tu carrito</h1>
        <p class="titulo-pagina__descripcion">Revisa tus productos antes de realizar el pedido.</p>
    </div>
    <?php if ($error): ?>
    <div class="aviso aviso--error" role="alert"><?= $escapar($error) ?></div>
    <?php endif; ?>
    <?php if (!$articulos): ?>
    <section class="tarjeta vacio">
        <h2 class="vacio__titulo">Tu carrito está vacío</h2>
        <p class="vacio__descripcion">Explora el catálogo y encuentra algo rico para compartir.</p>
        <a class="boton boton--dorado" href="<?= $url('productos') ?>">Ver productos</a>
    </section>
    <?php else: ?>
    <p><?= count($articulos) ?> productos · <?= $cantidadCarrito ?> unidades</p>
    <div class="pedido-distribucion">
        <div class="pila">
            <?php foreach ($articulos as $articulo): ?>
            <article class="tarjeta carrito-fila">
                <img class="carrito-fila__imagen" src="<?= $escapar($recurso('images/' . $articulo['imagen'])) ?>" width="100" height="85" alt="<?= $escapar($articulo['nombre']) ?>">
                <div>
                    <span class="etiqueta carrito-fila__categoria"><?= $escapar($articulo['categoria']) ?></span>
                    <h3 class="carrito-fila__nombre"><?= $escapar($articulo['nombre']) ?></h3>
                    <p class="texto-pequeno carrito-fila__precio"><?= $articulo['disponible'] ? $dinero($articulo['precio']) . ' / ' . $escapar($articulo['presentacion']) : 'Quitá este producto para continuar.' ?></p>
                    <form method="post" action="<?= $url('carrito') ?>">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                        <input type="hidden" name="accion" value="quitar">
                        <input type="hidden" name="id_producto" value="<?= $articulo['id_producto'] ?>">
                        <button class="boton-texto boton-texto--rojo">Quitar</button>
                    </form>
                </div>
                <?php if ($articulo['disponible']): ?>
                <form class="cantidad" method="post" action="<?= $url('carrito') ?>">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="accion" value="actualizar">
                    <input type="hidden" name="id_producto" value="<?= $articulo['id_producto'] ?>">
                    <label class="solo-lectores" for="cantidad-<?= $articulo['id_producto'] ?>">Cantidad de <?= $escapar($articulo['nombre']) ?></label>
                    <input class="campo__entrada cantidad__entrada" id="cantidad-<?= $articulo['id_producto'] ?>" type="number" name="cantidad" min="1" max="99" value="<?= $articulo['cantidad'] ?>" required>
                    <button class="boton-texto cantidad__boton">Actualizar</button>
                </form>
                <strong class="carrito-fila__subtotal"><?= $dinero($articulo['subtotal']) ?></strong>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
            <form method="post" action="<?= $url('carrito') ?>">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                <input type="hidden" name="accion" value="vaciar">
                <button class="boton-texto boton-texto--rojo">Vaciar carrito</button>
            </form>
        </div>
        <aside class="tarjeta resumen">
            <h2 class="resumen__titulo">Resumen del pedido</h2>
            <div class="resumen__linea"><span>Subtotal (<?= $cantidadCarrito ?> unidades)</span><strong><?= $dinero($total) ?></strong></div>
            <div class="resumen__linea"><span>Retiro en el local</span><strong>Sin costo</strong></div>
            <hr class="separador">
            <div class="resumen__linea resumen__linea--total"><strong>Total estimado</strong><strong><?= $dinero($total) ?></strong></div>
            <?php if ($puedeContinuar): ?>
            <a class="boton boton--dorado boton--completo" href="<?= $url('realizar-pedido') ?>">Realizar pedido →</a>
            <?php else: ?>
            <button class="boton boton--dorado boton--completo" disabled>Realizar pedido →</button>
            <?php endif; ?>
            <p class="texto-pequeno resumen__texto"><?= $clienteActual ? 'Completa tus datos y coordinaremos disponibilidad y retiro.' : 'Para confirmar el pedido, iniciá sesión o creá una cuenta de cliente.' ?></p>
        </aside>
    </div>
    <?php endif; ?>
</main>
