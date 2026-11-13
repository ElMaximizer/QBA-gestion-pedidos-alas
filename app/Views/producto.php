<main id="contenido" class="contenedor pagina-pedidos">
    <a class="enlace-volver" href="<?= $url('productos') ?>"> Volver a productos</a>
    <?php if (!$producto): ?>
        <section class="tarjeta vacio">
            <h1 class="vacio__titulo">Producto no disponible</h1>
            <p class="vacio__descripcion">Este producto ya no está en el catálogo. Explorá las opciones disponibles.</p>
            <a class="boton boton--dorado" href="<?= $url('productos') ?>">Ver productos</a>
        </section>
    <?php else: ?>
        <div class="detalle">
            <img class="detalle__imagen" src="<?= $escapar($recurso('images/' . $producto['imagen'])) ?>" width="400"
                height="245" alt="<?= $escapar($producto['nombre']) ?>">
            <section>
                <span class="insignia insignia--neutra"><?= $escapar($producto['categoria']) ?></span>
                <h1 class="detalle__titulo"><?= $escapar($producto['nombre']) ?></h1>
                <p class="detalle__descripcion"><?= $escapar($producto['descripcion']) ?></p>
                <p class="detalle__precio"><?= $dinero($producto['precio']) ?> / <?= $escapar($producto['presentacion']) ?></p>
                <a class="enlace-texto detalle__ingredientes-enlace" href="#producto-ingredientes">Ver ingredientes y alérgenos</a>
                <form method="post" action="<?= $url('carrito') ?>" class="agregar-carrito" data-carrito-form>
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="accion" value="agregar">
                    <input type="hidden" name="id_producto" value="<?= $producto['id_producto'] ?>">
                    <label class="campo">
                        <span class="campo-obligatorio__etiqueta">Cantidad <span class="campo-obligatorio"
                                aria-hidden="true" title="Campo obligatorio">*</span></span>
                        <input class="campo__entrada" type="number" name="cantidad" min="1" max="99" value="1" required>
                    </label>
                    <button class="boton boton--dorado">Agregar al carrito</button>
                </form>
                <a class="enlace-texto" href="<?= $url('contacto') ?>">Consultar disponibilidad</a>
                <p class="texto-pequeno">Te confirmaremos disponibilidad y forma de entrega o retiro.</p>
                <hr class="separador">
                <h2 class="detalle__subtitulo" id="producto-ingredientes">Ingredientes</h2>
                <p><?= $escapar($producto['ingredientes']) ?></p>
                <aside class="recuadro" aria-labelledby="producto-alergenos">
                    <h2 class="recuadro__titulo" id="producto-alergenos">Información de alérgenos</h2>
                    <p class="recuadro__descripcion"><?= $escapar($producto['alergenos'] ?: 'Consultá por alérgenos antes de realizar tu pedido.') ?></p>
                </aside>
            </section>
        </div>
    <?php endif; ?>
</main>
