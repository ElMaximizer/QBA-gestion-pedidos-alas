<main id="contenido">
    <section class="portada" aria-labelledby="titulo-inicio">
        <div class="contenedor portada__contenido">
            <div>
                <span class="etiqueta">HORNEADO FRESCO CADA DÍA</span>
                <h1 class="portada__titulo" id="titulo-inicio">El sabor de siempre,<br> hecho para compartir.</h1>
                <p class="portada__descripcion">Panes, bizcochos y tortas elaborados con dedicación. Encuentra tu próximo favorito en Panadería Alas.</p>
                <div class="portada__acciones">
                    <a class="boton boton--dorado" href="<?= $url('productos') ?>">Ver productos</a>
                    <a class="boton boton--contorno" role="link" aria-disabled="true">Conocer la panadería</a>
                </div>
                <p class="etiqueta portada__sello">PANES · BIZCOCHOS · TORTAS</p>
            </div>
            <img class="portada__imagen" src="<?= $escapar($recurso('images/panes.png')) ?>" alt="Panes, bizcochos y tortas artesanales" width="664" height="398" fetchpriority="high">
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
                <a class="categoria" href="<?= $escapar($url('productos', ['categoria' => $categoria['nombre']])) ?>">
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
            <?php foreach ($productosDestacados as $producto) { require __DIR__ . '/partials/producto-tarjeta.php'; } ?>
        </div>
    </section>
</main>
