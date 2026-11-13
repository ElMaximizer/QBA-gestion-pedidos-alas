<main id="contenido" class="contenedor <?= $esPanel ? 'panel' : 'pagina-pedidos' ?>">
    <section class="tarjeta vacio<?= $esPanel ? ' panel-error' : '' ?>" role="alert">
        <h1 class="vacio__titulo"><?= $escapar($tituloPagina) ?></h1>
        <p class="vacio__descripcion"><?= $escapar($mensaje) ?></p>
        <a class="boton boton--dorado"
            href="<?= $escapar($url($rutaVolver ?? 'inicio', $parametrosVolver ?? [])) ?>"><?= $escapar($textoVolver ?? 'Volver al inicio') ?></a>
    </section>
</main>
