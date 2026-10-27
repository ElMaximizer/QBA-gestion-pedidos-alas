<main id="contenido" class="contenedor pagina-pedidos">
    <a class="enlace-volver" href="<?= $url('productos') ?>">← Seguir comprando</a>
    <div class="titulo-pagina">
        <h1 class="titulo-pagina__titulo">Tu carrito</h1>
        <p class="titulo-pagina__descripcion">Revisá tus productos antes de realizar el pedido.</p>
    </div>
    <div data-carrito-pagina>
        <?php require __DIR__ . '/partials/carrito-contenido.php'; ?>
    </div>
</main>
