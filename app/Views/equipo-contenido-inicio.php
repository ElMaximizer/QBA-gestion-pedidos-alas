<main id="contenido" class="contenedor panel contenido-inicio">
    <div class="titulo-pagina">
        <span class="etiqueta">SITIO PÚBLICO</span>
        <h1 class="titulo-pagina__titulo">Contenido del inicio</h1>
        <p class="titulo-pagina__descripcion">Elegí los favoritos de la casa y el orden de los productos de la portada.</p>
    </div>
    <?php if ($aviso !== ''): ?>
    <p class="aviso aviso--exito" role="status"><?= $escapar($aviso) ?></p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
    <p class="aviso aviso--error" role="alert"><?= $escapar($error) ?></p>
    <?php endif; ?>
    <?php if (!$productos): ?>
    <section class="tarjeta vacio">
        <h2 class="vacio__titulo">Todavía no hay productos activos</h2>
        <p>Agregá o activá productos del catálogo para mostrarlos en el inicio.</p>
        <a class="boton boton--dorado" href="<?= $url('equipo-productos') ?>">Ir a productos</a>
    </section>
    <?php else: ?>
    <form class="contenido-inicio__formulario" method="post" action="<?= $url('equipo-contenido-inicio') ?>">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="version" value="<?= $escapar($seleccion['version']) ?>">
        <fieldset class="tarjeta contenido-inicio__seccion">
            <legend class="contenido-inicio__titulo">Favoritos de la casa</legend>
            <p>Elegí hasta 3 productos activos para destacar debajo de las categorías.</p>
            <div class="contenido-inicio__lista">
                <?php foreach ($productos as $producto): ?>
                <label class="contenido-inicio__producto">
                    <input class="contenido-inicio__casilla" type="checkbox" name="favoritos[]" value="<?= $producto['id_producto'] ?>" <?= in_array($producto['id_producto'], $seleccion['favoritos'], true) ? 'checked' : '' ?>>
                    <img class="contenido-inicio__imagen" src="<?= $escapar($recurso('images/' . $producto['imagen'])) ?>" alt="" width="72" height="64" loading="lazy">
                    <span class="contenido-inicio__identidad"><strong><?= $escapar($producto['nombre']) ?></strong><span><?= $escapar($producto['categoria']) ?> · <?= $dinero($producto['precio']) ?></span></span>
                </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <fieldset class="tarjeta contenido-inicio__seccion">
            <legend class="contenido-inicio__titulo">Carrusel de portada</legend>
            <p>Elegí hasta 5 productos distintos en el orden en que querés mostrarlos. Si dejás todos los espacios vacíos, se muestran los favoritos.</p>
            <div class="contenido-inicio__orden">
                <?php for ($posicion = 0; $posicion < 5; $posicion++): ?>
                <label class="campo">Posición <?= $posicion + 1 ?>
                    <select class="campo__entrada" name="portada[]">
                        <option value="">Sin producto</option>
                        <?php foreach ($productos as $producto): ?>
                        <option value="<?= $producto['id_producto'] ?>" <?= ($seleccion['portada'][$posicion] ?? null) === $producto['id_producto'] ? 'selected' : '' ?>><?= $escapar($producto['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <?php endfor; ?>
            </div>
        </fieldset>
        <div class="acciones contenido-inicio__acciones">
            <button class="boton boton--dorado">Guardar selección</button>
            <a class="boton boton--contorno" href="<?= $url('equipo-contenido-inicio') ?>">Recargar selección</a>
            <a class="boton boton--contorno" href="<?= $url('inicio') ?>" target="_blank" rel="noopener noreferrer">Ver sitio ↗</a>
        </div>
    </form>
    <?php endif; ?>
    <a class="enlace-volver contenido-inicio__volver" href="<?= $url('equipo-panel') ?>">← Volver al panel</a>
</main>
