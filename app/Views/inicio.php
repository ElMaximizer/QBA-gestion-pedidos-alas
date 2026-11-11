<?php $imagenDestacada = $productosCarrusel[0] ?? ($productosDestacados[0] ?? null); ?>
<main id="contenido" class="inicio-rustic">
    <section class="inicio-portada" aria-labelledby="inicio-portada-titulo">
        <?php if ($imagenDestacada !== null && !empty($imagenDestacada['imagen'])): ?>
            <img class="inicio-portada__foto" src="<?= $escapar($recurso('images/' . $imagenDestacada['imagen'])) ?>"
                alt="<?= $escapar($imagenDestacada['nombre']) ?>" width="1080" height="724" fetchpriority="high">
        <?php else: ?>
            <div class="inicio-portada__foto inicio-portada__foto--sin-productos" aria-hidden="true"></div>
        <?php endif; ?>
        <div class="inicio-portada__velo">
        </div>
        <div class="inicio-portada__sello" aria-hidden="true">
            <span>HECHO EN</span><strong>Salto</strong><span>PARA COMPARTIR</span>
        </div>
        <div class="inicio-portada__texto">
            <span class="etiqueta-rustic">PANADERÍA ARTESANAL · SALTO, URUGUAY</span>
            <h1 id="inicio-portada-titulo">Las cosas buenas<br>se disfrutan <em>despacio.</em></h1>

            <a class="boton-rustic boton-rustic--claro" href="<?= $url('productos') ?>">Descubrí nuestros productos <span aria-hidden="true"></span></a>
        </div>
        <a class="inicio-portada__indicacion" href="#inicio-presentacion">UN POCO MÁS DE RUSTIC <span
                aria-hidden="true"></span></a>
    </section>

    <section id="inicio-presentacion" class="inicio-presentacion contenedor-rustic"
        aria-labelledby="inicio-presentacion-titulo">
        <div>
            <span class="etiqueta-rustic">EL UNIVERSO RUSTIC</span>
            <h2 id="inicio-presentacion-titulo">Lo simple.<br>Lo de siempre.<br><em>Lo que nos reúne.</em></h2>
        </div>
        <div class="inicio-presentacion__detalle">
            <p>Creemos en esos pequeños rituales que hacen más lindo el día: un roll para la merienda, un cup para darte un gusto o algo salado para compartir.</p>
            <p>Por eso hacemos lo que hacemos: para acompañar tus momentos con algo rico, hecho con dedicación.</p>
            <a class="enlace-rustic" href="<?= $url('nosotros') ?>">Conocé nuestra panadería <span
                    aria-hidden="true"></span></a>
        </div>
    </section>

    <section class="inicio-seleccion contenedor-rustic" aria-labelledby="inicio-seleccion-titulo">
        <div class="inicio-seccion__encabezado">
            <div>
                <span class="etiqueta-rustic">PARA CADA MOMENTO DEL DÍA</span>
                <h2 id="inicio-seleccion-titulo">Encontrá tu favorito.</h2>
            </div>
            <a class="enlace-rustic" href="<?= $url('productos') ?>">Ver todo el catálogo </a>
        </div>
        <div class="inicio-seleccion__columnas">
            <a class="inicio-seleccion__imagen"
                href="<?= $imagenDestacada !== null ? $escapar($url('producto', ['id' => $imagenDestacada['id_producto']])) : $escapar($url('productos')) ?>">
                <?php if ($imagenDestacada !== null && !empty($imagenDestacada['imagen'])): ?>
                    <img src="<?= $escapar($recurso('images/' . $imagenDestacada['imagen'])) ?>"
                        alt="<?= $escapar($imagenDestacada['nombre']) ?>" width="1080" height="724" loading="lazy">
                <?php endif; ?>
                <span>Algo rico para cada momento. <span aria-hidden="true"></span></span>
            </a>
            <div class="inicio-categorias">
                <?php foreach ($categorias as $indice => $categoria): ?>
                    <a class="inicio-categoria"
                        href="<?= $escapar($url('productos', ['categoria' => $categoria['nombre']])) ?>"><span
                            class="inicio-categoria__numero"><?= sprintf('%02d', $indice + 1) ?></span>
                        <div>
                            <h3><?= $escapar($categoria['nombre']) ?></h3>
                            <p><?= $escapar($categoria['descripcion']) ?></p>
                        </div><span class="inicio-categoria__flecha" aria-hidden="true"></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php if ($productosDestacados): ?>
        <section class="inicio-favoritos contenedor-rustic" aria-labelledby="inicio-favoritos-titulo">
            <div class="inicio-seccion__encabezado">
                <div>
                    <span class="etiqueta-rustic">LA SELECCIÓN DE LA CASA</span>
                    <h2 id="inicio-favoritos-titulo">Para tentarse un poquito.</h2>
                </div>
                <a class="enlace-rustic" href="<?= $url('productos') ?>">Explorar productos </a>
            </div>
            <div class="inicio-productos">
                <?php foreach ($productosDestacados as $producto): ?>
                    <article class="inicio-producto">
                        <a class="inicio-producto__foto"
                            href="<?= $escapar($url('producto', ['id' => $producto['id_producto']])) ?>"><img
                                src="<?= $escapar($recurso('images/' . $producto['imagen'])) ?>"
                                alt="<?= $escapar($producto['nombre']) ?>" width="400" height="300" loading="lazy"><span
                                aria-hidden="true"></span></a>
                        <div class="inicio-producto__informacion">
                            <span class="etiqueta-rustic"><?= $escapar($producto['categoria']) ?></span>
                            <h3>
                                <a
                                    href="<?= $escapar($url('producto', ['id' => $producto['id_producto']])) ?>"><?= $escapar($producto['nombre']) ?></a>
                            </h3>
                            <p><?= $escapar($producto['descripcion']) ?></p>
                            <div class="inicio-producto__base">
                                <span><?= $dinero($producto['precio']) ?> <small>/ <?= $escapar($producto['presentacion']) ?></small></span>
                                <a href="<?= $escapar($url('producto', ['id' => $producto['id_producto']])) ?>">Ver producto </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="inicio-historia" aria-labelledby="inicio-historia-titulo">
        <div class="inicio-historia__imagen">
            <?php if ($imagenDestacada !== null && !empty($imagenDestacada['imagen'])): ?>
                <img src="<?= $escapar($recurso('images/' . $imagenDestacada['imagen'])) ?>"
                    alt="<?= $escapar($imagenDestacada['nombre']) ?>" width="1080" height="724" loading="lazy">
            <?php else: ?>
                <div class="inicio-historia__imagen-vacia" aria-hidden="true"></div>
            <?php endif; ?>
        </div>
        <div class="inicio-historia__texto">
            <span class="etiqueta-rustic">EL OFICIO. EL CUIDADO. EL SABOR.</span>
            <h2 id="inicio-historia-titulo">Hay cosas que<br>merecen <em>su tiempo.</em></h2>
            <p>La dedicación se nota en los detalles: en cada masa, en los rellenos y en las combinaciones que dan ganas de volver a compartir.</p>
            <a class="boton-rustic boton-rustic--oscuro" href="<?= $url('nosotros') ?>">Nuestra historia <span
                    aria-hidden="true"></span></a>
        </div>
    </section>

    <section class="inicio-visita contenedor-rustic" aria-labelledby="inicio-visita-titulo">
        <span class="etiqueta-rustic">TU PRÓXIMO MOMENTO RUSTIC</span>
        <div class="inicio-visita__columnas">
            <h2 id="inicio-visita-titulo">La mesa está lista.<br><em>Solo faltás vos.</em></h2>
            <div>
                <p>Uruguay 1486, Salto, Uruguay<br>Martes a domingo · 15:30 a 19:30</p>
                <a class="boton-rustic boton-rustic--oscuro" href="<?= $url('productos') ?>">Elegí algo rico <span
                        aria-hidden="true"></span></a>
                <a class="enlace-rustic inicio-visita__contacto" href="<?= $url('contacto') ?>">Hablemos </a>
            </div>
        </div>
    </section>
</main>
