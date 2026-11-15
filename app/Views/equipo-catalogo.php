<main id="contenido" class="contenedor panel catalogo-equipo">
    <?php $hayFiltros = $busqueda !== '' || $categoria !== ''; ?>
    <div class="titulo-pagina">
        <span class="etiqueta">ATENCIÓN DEL EQUIPO</span>
        <h1 class="titulo-pagina__titulo">Consultar productos</h1>
        <p class="titulo-pagina__descripcion">Consultá los productos activos, sus precios y presentaciones para informar a los clientes.</p>
    </div>

    <?php if ($error !== ''): ?>
        <p class="aviso aviso--error" role="alert"><?= $escapar($error) ?></p>
    <?php endif; ?>

    <form class="tarjeta catalogo-equipo__filtros" method="get" action="<?= $url('equipo-catalogo') ?>">
        <label class="campo">Buscar producto
            <input class="campo__entrada" type="search" name="q" maxlength="120" value="<?= $escapar($busqueda) ?>" placeholder="Nombre del producto">
        </label>
        <label class="campo">Categoría
            <select class="campo__entrada" name="categoria">
                <option value="">Todas las categorías</option>
                <?php foreach ($categorias as $opcion): ?>
                    <option value="<?= $escapar($opcion) ?>" <?= $categoria === $opcion ? 'selected' : '' ?>><?= $escapar($opcion) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="boton boton--dorado" type="submit">Aplicar filtros</button>
        <?php if ($hayFiltros): ?>
            <a class="boton boton--contorno" href="<?= $url('equipo-catalogo') ?>">Restablecer filtros</a>
        <?php endif; ?>
    </form>

    <?php if ($hayFiltros): ?>
        <p class="catalogo-equipo__criterio">Filtros aplicados: <?= $categoria === '' ? 'Todas las categorías' : $escapar($categoria) ?><?php if ($busqueda !== ''): ?> · Búsqueda: «<?= $escapar($busqueda) ?>»<?php endif; ?>.</p>
    <?php endif; ?>

    <?php if ($error === ''): ?>
        <p class="catalogo-equipo__cantidad"><?= count($productos) ?> <?= count($productos) === 1 ? 'producto activo encontrado' : 'productos activos encontrados' ?></p>
        <?php if ($productos === []): ?>
            <section class="tarjeta vacio catalogo-equipo__vacio">
                <h2 class="vacio__titulo"><?= $hayFiltros ? 'No encontramos productos con estos filtros' : 'Todavía no hay productos activos' ?></h2>
                <p class="vacio__descripcion"><?= $hayFiltros ? 'Probá con otro nombre o restablecé los filtros.' : 'Consultá al administrador o al dueño para actualizar el catálogo.' ?></p>
            </section>
        <?php else: ?>
            <div class="catalogo-equipo__lista">
                <?php foreach ($productos as $producto): ?>
                    <article class="tarjeta catalogo-equipo__producto" aria-labelledby="catalogo-producto-<?= (int) $producto['id_producto'] ?>">
                        <img class="catalogo-equipo__imagen" src="<?= $escapar($recurso('images/' . $producto['imagen'])) ?>" alt="<?= $escapar($producto['nombre']) ?>" width="400" height="245" loading="lazy">
                        <div class="catalogo-equipo__contenido">
                            <p class="catalogo-equipo__codigo">PRD-<?= str_pad((string) $producto['id_producto'], 3, '0', STR_PAD_LEFT) ?> · <?= $escapar($producto['categoria']) ?></p>
                            <h2 id="catalogo-producto-<?= (int) $producto['id_producto'] ?>"><?= $escapar($producto['nombre']) ?></h2>
                            <?php if ($producto['descripcion'] !== ''): ?>
                                <p class="catalogo-equipo__descripcion"><?= $escapar($producto['descripcion']) ?></p>
                            <?php endif; ?>
                            <p class="catalogo-equipo__precio"><strong><?= $dinero($producto['precio'], true) ?></strong><span>por <?= $escapar($producto['presentacion']) ?></span></p>
                            <details class="catalogo-equipo__informacion">
                                <summary>Ingredientes y alérgenos <span class="solo-lectores">de <?= $escapar($producto['nombre']) ?></span></summary>
                                <dl>
                                    <div><dt>Ingredientes</dt><dd><?= $escapar($producto['ingredientes'] !== '' ? $producto['ingredientes'] : 'Sin ingredientes registrados.') ?></dd></div>
                                    <div><dt>Alérgenos</dt><dd><?= $escapar($producto['alergenos'] !== '' ? $producto['alergenos'] : 'Sin información de alérgenos registrada.') ?></dd></div>
                                </dl>
                            </details>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <a class="enlace-texto" href="<?= $url('equipo-panel') ?>">Volver al panel</a>
</main>
