<?php
$escapar = static fn (string $texto): string => htmlspecialchars($texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
require __DIR__ . '/partials/encabezado.php';
?>
<main id="contenido">
    <section class="portada" aria-labelledby="titulo-inicio">
        <div class="contenedor portada__contenido">
            <div>
                <span class="etiqueta">HORNEADO FRESCO CADA DÍA</span>
                <h1 class="portada__titulo" id="titulo-inicio">El sabor de siempre,<br> hecho para compartir.</h1>
                <p class="portada__descripcion">Panes, bizcochos y tortas elaborados con dedicación. Encuentra tu próximo favorito en Panadería Alas.</p>
                <div class="portada__acciones">
                    <a class="boton boton--dorado" role="link" aria-disabled="true">Ver productos</a>
                    <a class="boton boton--contorno" role="link" aria-disabled="true">Conocer la panadería</a>
                </div>
                <p class="etiqueta portada__sello">PANES · BIZCOCHOS · TORTAS</p>
            </div>
            <img class="portada__imagen" src="images/panes.png" alt="Panes, bizcochos y tortas artesanales" width="664" height="398" fetchpriority="high">
        </div>
    </section>

    <section class="categorias" aria-labelledby="titulo-categorias">
        <div class="contenedor">
            <div class="encabezado-seccion">
                <span class="etiqueta">ELIGE TU MOMENTO</span>
                <h2 class="encabezado-seccion__titulo" id="titulo-categorias">Algo rico para cada día.</h2>
                <p class="encabezado-seccion__descripcion">Explora nuestra selección artesanal.</p>
            </div>
            <div class="categorias__lista">
                <?php foreach ($categorias as $categoria): ?>
                <a class="categoria" role="link" aria-disabled="true">
                    <span class="etiqueta categoria__etiqueta"><?= $escapar($categoria['etiqueta']) ?></span>
                    <h3 class="categoria__titulo"><?= $escapar($categoria['nombre']) ?></h3>
                    <p class="categoria__descripcion"><?= $escapar($categoria['descripcion']) ?></p>
                    <strong class="categoria__accion">Explorar →</strong>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="contenedor destacados" aria-labelledby="titulo-destacados">
        <div class="encabezado-seccion">
            <span class="etiqueta">HECHOS PARA DISFRUTAR</span>
            <h2 class="encabezado-seccion__titulo" id="titulo-destacados">Los favoritos de la casa</h2>
            <p class="encabezado-seccion__descripcion">Una selección para empezar.</p>
        </div>
        <div class="destacados__lista">
            <?php foreach ($productosDestacados as $producto): ?>
            <article class="producto">
                <a class="producto__imagen-enlace" role="link" aria-disabled="true" aria-label="<?= $escapar($producto['nombre']) ?>">
                    <img class="producto__imagen" src="images/<?= $escapar($producto['imagen']) ?>" width="400" height="245" alt="<?= $escapar($producto['nombre']) ?>" loading="lazy">
                </a>
                <span class="etiqueta producto__categoria"><?= $escapar($producto['categoria']) ?></span>
                <h3 class="producto__nombre"><a role="link" aria-disabled="true"><?= $escapar($producto['nombre']) ?></a></h3>
                <p class="producto__descripcion"><?= $escapar($producto['descripcion']) ?></p>
                <div class="producto__precio">
                    <?= $escapar($producto['precio']) ?> <small class="producto__presentacion">/ <?= $escapar($producto['presentacion']) ?></small>
                </div>
                <a class="boton boton--contorno producto__boton" role="link" aria-disabled="true">Ver producto →</a>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>
<?php require __DIR__ . '/partials/pie.php'; ?>
