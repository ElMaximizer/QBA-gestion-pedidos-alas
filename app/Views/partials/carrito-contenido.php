<div class="carrito-contenido">
    <?php if ($error): ?>
    <p class="carrito-contenido__aviso" role="alert"><?= $escapar($error) ?></p>
    <?php endif; ?>
    <?php if (!$articulos): ?>
    <section class="carrito-contenido__vacio">
        <img src="<?= $escapar($recurso('images/carrito.svg')) ?>" width="48" height="48" alt="">
        <h3 class="carrito-contenido__titulo-vacio">Tu carrito está vacío</h3>
        <p>Explorá el catálogo y encontrá algo rico para compartir.</p>
        <a class="boton boton--dorado" href="<?= $url('productos') ?>">Ver productos</a>
    </section>
    <?php else: ?>
    <div class="carrito-contenido__productos">
        <p class="carrito-contenido__cantidad"><?= count($articulos) ?> <?= count($articulos) === 1 ? 'producto' : 'productos' ?> · <?= $cantidadCarrito ?> <?= $cantidadCarrito === 1 ? 'unidad' : 'unidades' ?></p>
        <ul class="carrito-contenido__lista">
            <?php foreach ($articulos as $articulo): ?>
            <li class="carrito-contenido__articulo">
                <img class="carrito-contenido__imagen" src="<?= $escapar($recurso('images/' . $articulo['imagen'])) ?>" width="80" height="80" alt="">
                <div class="carrito-contenido__detalle">
                    <span class="carrito-contenido__categoria"><?= $escapar($articulo['categoria']) ?></span>
                    <h3 class="carrito-contenido__nombre"><?= $escapar($articulo['nombre']) ?></h3>
                    <p class="carrito-contenido__precio"><?= $articulo['disponible'] ? $dinero($articulo['precio']) . ' / ' . $escapar($articulo['presentacion']) : 'Quitá este producto para continuar.' ?></p>
                    <form method="post" action="<?= $url('carrito') ?>" data-carrito-form>
                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                        <input type="hidden" name="accion" value="quitar">
                        <input type="hidden" name="id_producto" value="<?= $articulo['id_producto'] ?>">
                        <button class="carrito-contenido__quitar" aria-label="Quitar <?= $escapar($articulo['nombre']) ?>">Quitar</button>
                    </form>
                </div>
                <?php if ($articulo['disponible']): ?>
                <div class="carrito-contenido__controles">
                    <form class="carrito-contenido__formulario" method="post" action="<?= $url('carrito') ?>" data-carrito-form>
                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                        <input type="hidden" name="accion" value="actualizar">
                        <input type="hidden" name="id_producto" value="<?= $articulo['id_producto'] ?>">
                        <label class="carrito-contenido__campo">Cantidad
                            <input class="carrito-contenido__entrada" type="number" name="cantidad" min="1" max="99" value="<?= $articulo['cantidad'] ?>" aria-label="Cantidad de <?= $escapar($articulo['nombre']) ?>" required>
                        </label>
                        <button class="carrito-contenido__actualizar">Actualizar</button>
                    </form>
                    <strong class="carrito-contenido__subtotal"><?= $dinero($articulo['subtotal']) ?></strong>
                </div>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <form class="carrito-contenido__vaciar" method="post" action="<?= $url('carrito') ?>" data-carrito-form>
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <input type="hidden" name="accion" value="vaciar">
            <button class="carrito-contenido__quitar">Vaciar carrito</button>
        </form>
    </div>
    <section class="carrito-contenido__resumen" aria-label="Resumen del pedido">
        <div class="carrito-contenido__linea"><span>Retiro en el local</span><span>Sin costo</span></div>
        <div class="carrito-contenido__linea carrito-contenido__linea--total"><span>Total estimado</span><strong><?= $dinero($total) ?></strong></div>
        <?php if ($puedeContinuar): ?>
        <a class="boton boton--dorado carrito-contenido__continuar" href="<?= $url('realizar-pedido') ?>">Realizar pedido →</a>
        <?php else: ?>
        <button class="boton boton--dorado carrito-contenido__continuar" disabled>Realizar pedido →</button>
        <?php endif; ?>
        <p class="carrito-contenido__ayuda"><?= $clienteActual ? 'Completá tus datos para coordinar el retiro. No se realiza ningún cobro en línea.' : 'Para confirmar el pedido, iniciá sesión o creá una cuenta de cliente.' ?></p>
    </section>
    <?php endif; ?>
</div>
