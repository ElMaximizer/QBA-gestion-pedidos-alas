<div class="producto-compra">
    <dl class="producto-compra__datos">
        <div>
            <dt>Ingredientes</dt>
            <dd><?= $escapar($producto['ingredientes']) ?></dd>
        </div>
        <div class="producto-compra__alergenos">
            <dt>Alérgenos</dt>
            <dd><?= $escapar(trim((string) $producto['alergenos']) !== '' ? $producto['alergenos'] : 'Consultá por alérgenos antes de realizar tu pedido.') ?></dd>
        </div>
    </dl>
    <div class="producto-compra__acciones">
        <p class="producto-compra__precio"><?= $dinero($producto['precio'], true) ?> <small>/ <?= $escapar($producto['presentacion']) ?></small></p>
        <form class="producto-compra__formulario" method="post" action="<?= $url('carrito') ?>" data-carrito-form
            aria-label="<?= $escapar('Agregar ' . $producto['nombre'] . ' al carrito') ?>">
            <input type="hidden" name="csrf" value="<?= $escapar($csrf) ?>">
            <input type="hidden" name="accion" value="agregar">
            <input type="hidden" name="id_producto" value="<?= (int) $producto['id_producto'] ?>">
            <label class="producto-compra__cantidad">Cantidad
                <input type="number" name="cantidad" min="1" max="99" step="1" value="1" required inputmode="numeric"
                    aria-label="<?= $escapar('Cantidad de ' . $producto['nombre']) ?>">
            </label>
            <button class="boton boton--dorado producto-compra__agregar" type="submit"
                aria-label="<?= $escapar('Agregar ' . $producto['nombre'] . ' al carrito') ?>">Agregar al carrito</button>
        </form>
        <a class="producto-compra__consulta" href="<?= $url('contacto') ?>">Consultar disponibilidad</a>
        <p class="producto-compra__ayuda">Te confirmaremos disponibilidad y forma de entrega o retiro.</p>
    </div>
</div>
