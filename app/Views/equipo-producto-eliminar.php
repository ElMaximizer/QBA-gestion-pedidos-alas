<main id="contenido" class="contenedor panel gestion-productos">
    <div class="titulo-pagina">
        <span class="etiqueta">ADMINISTRACIÓN</span>
        <h1 class="titulo-pagina__titulo">Eliminar producto</h1>
        <p class="titulo-pagina__descripcion">Revisá el producto y sus consecuencias antes de confirmar la eliminación.</p>
    </div>
    <?php if ($error): ?>
        <div class="aviso aviso--error" role="alert" tabindex="-1" data-aviso-productos><?= $escapar($error) ?> </div>
    <?php endif; ?>
    <form class="tarjeta producto-eliminar" method="post" data-gestion-guardar="Eliminando producto…"
        action="<?= $escapar($url('producto-eliminar', ['id' => $producto['id_producto']] + $filtrosListado)) ?>">
        <input type="hidden" name="csrf" value="<?= $escapar($csrf) ?>">
        <input type="hidden" name="version" value="<?= $escapar($version) ?>">
        <span class="insignia insignia--error">Eliminar producto</span>
        <p>PRD-<?= str_pad((string) $producto['id_producto'], 3, '0', STR_PAD_LEFT) ?> · <?= $escapar($producto['categoria']) ?></p>
        <h2 class="producto-eliminar__titulo">¿Eliminar <?= $escapar($producto['nombre']) ?>?</h2>
        <p class="producto-eliminar__descripcion">Dejará de aparecer en el catálogo. Los pedidos anteriores conservarán su información.</p>
        <aside class="recuadro">
            <h3 class="recuadro__titulo">Podés desactivarlo en su lugar</h3>
            <p class="recuadro__descripcion">Si solo necesitás ocultarlo temporalmente, cambiá su estado a Inactivo.</p>
            <a class="enlace-texto" href="<?= $escapar($url('producto-editar', ['id' => $producto['id_producto']] + $filtrosListado)) ?>#producto-activo">Ir a editar el estado</a>
        </aside>
        <div class="acciones producto-eliminar__acciones">
            <a class="boton boton--contorno" href="<?= $escapar($url('equipo-productos', $filtrosListado)) ?>">Volver a productos</a>
            <button class="boton boton--rojo">Eliminar producto</button>
        </div>
    </form>
</main>
