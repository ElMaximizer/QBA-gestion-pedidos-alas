<main id="contenido" class="contenedor panel contenido-inicio">
    <div class="titulo-pagina"><span class="etiqueta">SITIO PÚBLICO</span><h1 class="titulo-pagina__titulo">Elegir qué se muestra en el inicio</h1><p class="titulo-pagina__descripcion">Seleccioná los favoritos de la casa y las imágenes que van pasando en la portada.</p></div>
    <?php if ($aviso !== ''): ?><div class="aviso aviso--exito" role="status"><?= $escapar($aviso) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="aviso aviso--error" role="alert"><?= $escapar($error) ?></div><?php endif; ?>
    <?php if ($productos === []): ?>
        <section class="tarjeta vacio"><h2 class="vacio__titulo">Todavía no hay productos activos</h2><p class="vacio__descripcion">Agregá productos con imágenes desde Productos y después volvé para elegir el contenido del inicio.</p><a class="boton boton--dorado" href="<?= $url('equipo-productos') ?>">Ir a productos</a></section>
    <?php else: ?>
        <form method="post" action="<?= $url('equipo-contenido-inicio') ?>" class="contenido-inicio__formulario">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <div class="contenido-inicio__ayuda"><p><strong>Favoritos de la casa:</strong> elegí hasta 3 productos. Se muestran en esa sección.</p><p><strong>Carrusel de portada:</strong> elegí hasta 5. Las imágenes cambian cada 4 segundos.</p></div>
            <div class="contenido-inicio__lista">
                <?php foreach ($productos as $producto): ?>
                    <?php $idProducto = (int) $producto['id_producto']; ?>
                    <article class="tarjeta contenido-inicio__producto">
                        <img src="<?= $escapar($recurso('images/' . $producto['imagen'])) ?>" alt="" width="120" height="86" loading="lazy">
                        <div class="contenido-inicio__identidad"><h2><?= $escapar($producto['nombre']) ?></h2><p><?= $escapar($producto['categoria']) ?> · <?= $dinero($producto['precio']) ?></p></div>
                        <label class="contenido-inicio__opcion"><input type="checkbox" name="favoritos[]" value="<?= $idProducto ?>" <?= in_array($idProducto, $seleccionFavoritos, true) ? 'checked' : '' ?>><span>Favorito de la casa</span></label>
                        <label class="contenido-inicio__opcion"><input type="checkbox" name="portada[]" value="<?= $idProducto ?>" <?= in_array($idProducto, $seleccionPortada, true) ? 'checked' : '' ?>><span>Imagen del carrusel</span></label>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="acciones contenido-inicio__acciones"><button class="boton boton--dorado" type="submit">Guardar selección</button><a class="boton boton--contorno" href="<?= $url('equipo-panel') ?>">Volver al panel</a><a class="boton boton--contorno" href="<?= $url('inicio') ?>" target="_blank" rel="noopener">Ver sitio</a></div>
        </form>
    <?php endif; ?>
</main>