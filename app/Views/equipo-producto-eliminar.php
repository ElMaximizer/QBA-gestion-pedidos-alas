<main id="contenido" class="contenedor panel">
    <div class="titulo-pagina">
        <span class="etiqueta">ADMINISTRACIÓN</span>
        <h1 class="titulo-pagina__titulo">Eliminar producto</h1>
        <p class="titulo-pagina__descripcion">Revisa el producto y sus consecuencias antes de confirmar la eliminación.</p>
    </div>
    <?php if ($error): ?>
    <div class="aviso aviso--error" role="alert"><?= $escapar($error) ?></div>
    <?php endif; ?>
    <form class="tarjeta producto-eliminar" method="post" action="<?= $escapar($url('producto-eliminar', ['id' => $producto['id_producto']])) ?>">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="version" value="<?= $escapar($version) ?>">
        <span class="insignia insignia--error">Eliminar producto</span>
        <h2 class="producto-eliminar__titulo">¿Eliminar <?= $escapar($producto['nombre']) ?>?</h2>
        <p class="producto-eliminar__descripcion">Dejará de aparecer en el catálogo. Los pedidos anteriores conservarán su información.</p>
        <aside class="recuadro">
            <h4 class="recuadro__titulo">Puedes desactivarlo en su lugar</h4>
            <p class="recuadro__descripcion">Si solo necesitas ocultarlo temporalmente, cambia su estado a Inactivo.</p>
        </aside>
        <div class="acciones producto-eliminar__acciones">
            <a class="boton boton--contorno" href="<?= $url('equipo-productos') ?>">Volver a productos</a>
            <button class="boton boton--rojo">Eliminar producto</button>
        </div>
    </form>
</main>
