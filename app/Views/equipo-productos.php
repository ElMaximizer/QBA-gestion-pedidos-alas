<main id="contenido" class="contenedor panel gestion-productos">
    <div class="titulo-pagina">
        <span class="etiqueta">ADMINISTRACIÓN</span>
        <h1 class="titulo-pagina__titulo">Gestión de productos</h1>
        <p class="titulo-pagina__descripcion">Organiza el catálogo y controla qué productos están disponibles.</p>
    </div>
    <?php if ($aviso): ?>
    <div class="aviso aviso--exito" role="status"><?= $escapar($aviso) ?></div>
    <?php endif; ?>
    <a class="boton boton--dorado" href="<?= $url('producto-agregar') ?>">+ Agregar producto</a>
    <form class="gestion-busqueda" method="get" action="<?= $url('equipo-productos') ?>">
        <label class="campo gestion-busqueda__nombre">Buscar
            <input class="campo__entrada" name="q" value="<?= $escapar($busqueda) ?>" placeholder="Nombre o código de producto" maxlength="120">
        </label>
        <label class="campo gestion-busqueda__estado"><span class="solo-lectores">Estado</span>
            <select class="campo__entrada" name="estado">
                <option value="" <?= $estado === '' ? 'selected' : '' ?>>Todos los estados</option>
                <option value="1" <?= $estado === '1' ? 'selected' : '' ?>>Activos</option>
                <option value="0" <?= $estado === '0' ? 'selected' : '' ?>>Inactivos</option>
            </select>
        </label>
        <button class="boton boton--contorno gestion-busqueda__boton">Buscar</button>
    </form>
    <p class="gestion-productos__cantidad"><?= $total ?> <?= $total === 1 ? 'producto' : 'productos' ?> · Catálogo</p>
    <div class="pila">
        <?php foreach ($productos as $producto): ?>
        <article class="tarjeta gestion-producto">
            <div class="gestion-producto__identidad">
                <h3 class="gestion-producto__nombre"><?= $escapar($producto['nombre']) ?></h3>
                <small class="texto-pequeno">PRD-<?= str_pad((string) $producto['id_producto'], 3, '0', STR_PAD_LEFT) ?> · <?= $escapar($producto['categoria']) ?></small>
            </div>
            <strong class="gestion-producto__precio"><?= $dinero($producto['precio']) ?></strong>
            <span class="gestion-producto__estado"><span class="insignia <?= $producto['activo'] ? 'insignia--exito' : 'insignia--neutra' ?>"><?= $producto['activo'] ? 'Activo' : 'Inactivo' ?></span></span>
            <a class="boton boton--contorno gestion-producto__boton" href="<?= $escapar($url('producto-editar', ['id' => $producto['id_producto']])) ?>">Editar</a>
            <a class="boton boton--rojo gestion-producto__boton" href="<?= $escapar($url('producto-eliminar', ['id' => $producto['id_producto']])) ?>">Eliminar</a>
        </article>
        <?php endforeach; ?>
        <?php if (!$productos): ?>
        <section class="tarjeta vacio gestion-productos__vacio">
            <h2 class="vacio__titulo">No encontramos productos</h2>
            <p class="vacio__descripcion">Probá con otro nombre o cambiá el filtro de estado.</p>
            <a class="boton boton--dorado" href="<?= $escapar($url('equipo-productos', ['estado' => ''])) ?>">Ver todos los productos</a>
        </section>
        <?php endif; ?>
    </div>
    <?php if ($paginas > 1): ?>
    <nav class="paginacion" aria-label="Páginas del catálogo">
        <?php if ($pagina > 1): ?>
        <a class="boton boton--contorno" href="<?= $escapar($url('equipo-productos', ['q' => $busqueda, 'estado' => $estado, 'pagina' => $pagina - 1])) ?>">← Anterior</a>
        <?php endif; ?>
        <span>Página <?= $pagina ?> de <?= $paginas ?></span>
        <?php if ($pagina < $paginas): ?>
        <a class="boton boton--contorno" href="<?= $escapar($url('equipo-productos', ['q' => $busqueda, 'estado' => $estado, 'pagina' => $pagina + 1])) ?>">Siguiente →</a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>
</main>
