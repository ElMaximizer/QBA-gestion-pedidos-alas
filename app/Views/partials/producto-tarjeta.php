<article class="producto">
    <a class="producto__imagen-enlace" href="<?= $escapar($url('producto', ['id' => $producto['id_producto']])) ?>">
        <img class="producto__imagen" src="<?= $escapar($recurso('images/' . $producto['imagen'])) ?>" width="400" height="245" alt="<?= $escapar($producto['nombre']) ?>" loading="lazy">
    </a>
    <span class="etiqueta producto__categoria"><?= $escapar($producto['categoria']) ?></span>
    <h3 class="producto__nombre"><a href="<?= $escapar($url('producto', ['id' => $producto['id_producto']])) ?>"><?= $escapar($producto['nombre']) ?></a></h3>
    <p class="producto__descripcion"><?= $escapar($producto['descripcion']) ?></p>
    <div class="producto__precio">
        <?= $dinero($producto['precio']) ?> <small class="producto__presentacion">/ <?= $escapar($producto['presentacion']) ?></small>
    </div>
    <a class="boton boton--contorno producto__boton" href="<?= $escapar($url('producto', ['id' => $producto['id_producto']])) ?>">Ver producto →</a>
</article>
