<main id="contenido" class="nosotros-pagina">
    <section class="nosotros" aria-labelledby="titulo-nosotros">
        <div class="contenedor nosotros__contenido">
            <div class="nosotros__introduccion">
                <span class="etiqueta">NUESTRA PANADERÍA</span>
                <h1 class="nosotros__titulo" id="titulo-nosotros">El sabor que nos reúne.</h1>
                <p class="nosotros__bajada">En Rustic creemos que los pequeños momentos merecen algo hecho con dedicación.</p>
            </div>
            <div class="nosotros__detalle">
                <img class="nosotros__imagen" src="<?= $escapar($recurso('images/panes.png')) ?>" alt="Panes y productos artesanales de Panadería Rustic" width="664" height="398">
                <div class="nosotros__texto">
                    <h2 class="nosotros__subtitulo">De nuestras manos<br>a tu mesa.</h2>
                    <p>Un pan para el desayuno, bizcochos para una charla o una torta para celebrar. Queremos acompañarte con sabores que inviten a compartir.</p>
                    <p class="nosotros__nota">Nuestra propuesta combina recetas familiares, ingredientes seleccionados y cuidado en cada preparación.</p>
                    <a class="boton boton--dorado" href="<?= $url('productos') ?>">Explorar productos</a>
                </div>
            </div>
            <a class="enlace-texto nosotros__volver" href="<?= $url('inicio') ?>">← Volver al inicio</a>
        </div>
    </section>
</main>