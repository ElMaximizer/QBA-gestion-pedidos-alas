<main id="contenido" class="contenedor pagina-pedidos">
    <a class="enlace-volver" href="<?= $url('productos') ?>">← Volver a productos</a>
    <?php if (!$producto): ?>
    <section class="tarjeta vacio">
        <h1 class="vacio__titulo">Producto no disponible</h1>
        <p class="vacio__descripcion">Este producto ya no está en el catálogo. Explorá las opciones disponibles.</p>
        <a class="boton boton--dorado" href="<?= $url('productos') ?>">Ver productos</a>
    </section>
    <?php else: ?>
    <div class="detalle">
        <img class="detalle__imagen" src="images/<?= $escapar($producto['imagen']) ?>" width="400" height="245" alt="<?= $escapar($producto['nombre']) ?>">
        <section>
            <span class="insignia insignia--neutra"><?= $escapar($producto['categoria']) ?></span>
            <h1 class="detalle__titulo"><?= $escapar($producto['nombre']) ?></h1>
            <p class="detalle__descripcion"><?= $escapar($producto['descripcion']) ?></p>
            <p>Elaborado con dedicación para acompañar tus momentos.</p>
            <h2><?= $dinero($producto['precio']) ?> / <?= $escapar($producto['presentacion']) ?></h2>
            <hr class="separador">
            <h4 class="detalle__subtitulo">Ingredientes</h4>
            <p><?= $escapar($producto['ingredientes']) ?></p>
            <aside class="recuadro">
                <h4 class="recuadro__titulo">Información de alérgenos</h4>
                <p class="recuadro__descripcion"><?= $escapar($producto['alergenos'] ?: 'Consultá por alérgenos antes de realizar tu pedido.') ?></p>
            </aside>
            <form method="post" action="<?= $url('carrito') ?>" class="agregar-carrito">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                <input type="hidden" name="accion" value="agregar">
                <input type="hidden" name="id_producto" value="<?= $producto['id_producto'] ?>">
                <label class="campo">Cantidad
                    <input class="campo__entrada" type="number" name="cantidad" min="1" max="99" value="1" required>
                </label>
                <button class="boton boton--dorado">Agregar al carrito</button>
            </form>
            <a class="enlace-texto" role="link" aria-disabled="true">Consultar disponibilidad →</a>
            <p class="texto-pequeno">Te confirmaremos disponibilidad y forma de retiro.</p>
        </section>
    </div>
    <?php endif; ?>
</main>
