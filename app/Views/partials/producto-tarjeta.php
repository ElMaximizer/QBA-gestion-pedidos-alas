<article class="producto">
    <div class="producto__imagen-enlace">
        <img class="producto__imagen" src="<?= $escapar($recurso('images/' . $producto['imagen'])) ?>" width="400"
            height="245" alt="<?= $escapar($producto['nombre']) ?>" loading="lazy">
    </div>
    <span class="etiqueta producto__categoria"><?= $escapar($producto['categoria']) ?></span>
    <h3 class="producto__nombre">
        <?= $escapar($producto['nombre']) ?>
    </h3>
    <p class="producto__descripcion"><?= $escapar($producto['descripcion']) ?></p>
    <?php require __DIR__ . '/producto-compra.php'; ?>
</article>
