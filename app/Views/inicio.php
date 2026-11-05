<main id="contenido" class="r-home">
    <section class="r-hero" aria-labelledby="r-titulo">
        <img class="r-hero__foto" src="<?= $escapar($recurso('images/rustic/pan-trenzado.webp')) ?>" alt="Pan trenzado artesanal de Rustic con semillas, sobre una mesa de madera" width="1080" height="724" fetchpriority="high">
        <div class="r-hero__velo"></div>
        <div class="r-hero__sello" aria-hidden="true"><span>HECHO EN</span><strong>Salto</strong><span>PARA COMPARTIR</span></div>
        <div class="r-hero__texto">
            <span class="r-eyebrow">PANADERÍA ARTESANAL · SALTO, URUGUAY</span>
            <h1 id="r-titulo">Las cosas buenas<br>se disfrutan <em>despacio.</em></h1>
            <p>El aroma del pan. Una mesa compartida.<br>Un pequeño momento que lo cambia todo.</p>
            <a class="r-button r-button--cream" href="<?= $url('productos') ?>">Descubrí nuestros productos <span aria-hidden="true">↗</span></a>
        </div>
        <a class="r-hero__scroll" href="#r-universo">UN POCO MÁS DE RUSTIC <span aria-hidden="true">↓</span></a>
    </section>

    <section id="r-universo" class="r-intro r-wrap" aria-labelledby="r-intro-titulo">
        <div><span class="r-eyebrow">EL UNIVERSO RUSTIC</span><h2 id="r-intro-titulo">Lo simple.<br>Lo de siempre.<br><em>Lo que nos reúne.</em></h2></div>
        <div class="r-intro__detalle"><p>Creemos en esos pequeños rituales que hacen más lindo el día. En el pan para el desayuno, los bizcochos para una charla y una torta para celebrar.</p><p>Por eso hacemos lo que hacemos: para acompañar tus momentos con algo rico, hecho con dedicación.</p><a class="r-link" href="<?= $url('nosotros') ?>">Conocé nuestra panadería <span aria-hidden="true">↗</span></a></div>
    </section>

    <section class="r-selection r-wrap" aria-labelledby="r-selection-titulo">
        <div class="r-section-head"><div><span class="r-eyebrow">PARA CADA MOMENTO DEL DÍA</span><h2 id="r-selection-titulo">Encontrá tu favorito.</h2></div><a class="r-link" href="<?= $url('productos') ?>">Ver todo el catálogo ↗</a></div>
        <div class="r-selection__grid">
            <a class="r-selection__image" href="<?= $escapar($url('productos', ['categoria' => 'Panes'])) ?>"><img src="<?= $escapar($recurso('images/rustic/pan-artesanal.webp')) ?>" alt="Pan artesanal de corteza dorada junto a su cesto de fermentación" width="4928" height="3264" loading="lazy"><span>El placer de lo recién hecho. <span aria-hidden="true">↗</span></span></a>
            <div class="r-category-list">
                <?php foreach ($categorias as $indice => $categoria): ?>
                <a class="r-category" href="<?= $escapar($url('productos', ['categoria' => $categoria['nombre']])) ?>"><span class="r-category__numero"><?= sprintf('%02d', $indice + 1) ?></span><div><h3><?= $escapar($categoria['nombre']) ?></h3><p><?= $escapar($categoria['descripcion']) ?></p></div><span class="r-category__arrow" aria-hidden="true">↗</span></a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php if ($productosDestacados): ?>
    <section class="r-favorites r-wrap" aria-labelledby="r-favorites-titulo">
        <div class="r-section-head"><div><span class="r-eyebrow">LA SELECCIÓN DE LA CASA</span><h2 id="r-favorites-titulo">Para tentarse un poquito.</h2></div><a class="r-link" href="<?= $url('productos') ?>">Explorar productos ↗</a></div>
        <div class="r-products">
            <?php foreach ($productosDestacados as $producto): ?>
            <article class="r-product"><a class="r-product__foto" href="<?= $escapar($url('producto', ['id' => $producto['id_producto']])) ?>"><img src="<?= $escapar($recurso('images/' . $producto['imagen'])) ?>" alt="<?= $escapar($producto['nombre']) ?>" width="400" height="300" loading="lazy"><span aria-hidden="true">↗</span></a><div class="r-product__info"><span class="r-eyebrow"><?= $escapar($producto['categoria']) ?></span><h3><a href="<?= $escapar($url('producto', ['id' => $producto['id_producto']])) ?>"><?= $escapar($producto['nombre']) ?></a></h3><p><?= $escapar($producto['descripcion']) ?></p><div class="r-product__base"><span><?= $dinero($producto['precio']) ?> <small>/ <?= $escapar($producto['presentacion']) ?></small></span><a href="<?= $escapar($url('producto', ['id' => $producto['id_producto']])) ?>">Ver producto →</a></div></div></article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="r-story" aria-labelledby="r-story-titulo"><div class="r-story__image"><img src="<?= $escapar($recurso('images/rustic/pan-horneado.webp')) ?>" alt="Detalle de la corteza de un pan de Rustic sobre una rejilla" width="4928" height="3264" loading="lazy"></div><div class="r-story__text"><span class="r-eyebrow">EL OFICIO. EL CUIDADO. EL SABOR.</span><h2 id="r-story-titulo">Hay cosas que<br>merecen <em>su tiempo.</em></h2><p>La dedicación se nota en los detalles. En la textura de la corteza, en el aroma de cada preparación y en las ganas de volver a compartir.</p><a class="r-button r-button--dark" href="<?= $url('nosotros') ?>">Nuestra historia <span aria-hidden="true">↗</span></a></div></section>

    <section class="r-visit r-wrap" aria-labelledby="r-visit-titulo"><span class="r-eyebrow">TU PRÓXIMO MOMENTO RUSTIC</span><div class="r-visit__grid"><h2 id="r-visit-titulo">La mesa está lista.<br><em>Solo faltás vos.</em></h2><div><p>Uruguay 1486, Salto, Uruguay<br>Martes a domingo · 15:30 a 19:30</p><a class="r-button r-button--dark" href="<?= $url('productos') ?>">Elegí algo rico <span aria-hidden="true">↗</span></a><a class="r-link r-visit__contact" href="<?= $url('contacto') ?>">Hablemos ↗</a></div></div></section>
</main>
