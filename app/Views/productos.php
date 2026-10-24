<main id="contenido" class="contenedor pagina-pedidos catalogo">
    <div class="titulo-pagina">
        <span class="etiqueta">NUESTRO CATÁLOGO</span>
        <h1 class="titulo-pagina__titulo">Hecho con dedicación.</h1>
        <p class="titulo-pagina__descripcion">Panes, bizcochos y tortas para acompañar tus momentos.</p>
    </div>
    <form class="busqueda" method="get" action="index.php">
        <label class="campo busqueda__campo">Buscar productos
            <input class="campo__entrada" name="q" value="<?= $escapar($busqueda) ?>" placeholder="Nombre o ingrediente" maxlength="120">
        </label>
        <input type="hidden" name="ruta" value="productos">
        <input type="hidden" name="categoria" value="<?= $escapar($categoria) ?>">
        <button class="boton boton--azul busqueda__boton">Buscar</button>
    </form>
    <nav class="filtros" aria-label="Categorías">
        <?php foreach (['' => 'Todos'] + array_combine($categorias, $categorias) as $valor => $etiqueta): ?>
        <a class="boton filtros__boton <?= $categoria === $valor ? 'boton--azul' : 'boton--contorno' ?>" <?= $categoria === $valor ? 'aria-current="true"' : '' ?> href="<?= $escapar($url('productos', ['q' => $busqueda, 'categoria' => $valor])) ?>"><?= $escapar($etiqueta) ?></a>
        <?php endforeach; ?>
    </nav>
    <?php if (!$productos): ?>
    <section class="tarjeta vacio">
        <h2 class="vacio__titulo">No encontramos ese producto</h2>
        <p class="vacio__descripcion">Prueba con otra palabra o vuelve a ver todas las categorías.</p>
        <a class="boton boton--dorado" href="<?= $url('productos') ?>">Ver todos los productos</a>
    </section>
    <?php else: ?>
    <p class="catalogo__cantidad"><?= count($productos) ?> productos · Ordenar: destacados</p>
    <div class="catalogo__lista">
        <?php foreach ($productos as $producto) { require __DIR__ . '/partials/producto-tarjeta.php'; } ?>
    </div>
    <?php endif; ?>
    <aside class="recuadro">
        <h3 class="recuadro__titulo">¿Buscas algo para una ocasión especial?</h3>
        <p class="recuadro__descripcion">Escríbenos y cuéntanos qué tienes en mente.</p>
    </aside>
    <a class="boton boton--contorno" role="link" aria-disabled="true">Hacer una consulta</a>
</main>
