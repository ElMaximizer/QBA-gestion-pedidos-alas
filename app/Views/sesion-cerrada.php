<main id="contenido" class="contenedor registro-pagina">
    <section class="tarjeta vacio registro__completado" aria-labelledby="titulo-sesion-cerrada">
        <span class="insignia insignia--exito">Sesión finalizada</span>
        <h1 class="vacio__titulo" id="titulo-sesion-cerrada">¡Hasta pronto!</h1>
        <p class="vacio__descripcion"><?= $escapar($mensaje) ?></p>
        <div class="acciones vacio__acciones">
            <a class="boton boton--dorado" href="<?= $url('inicio') ?>">Volver al inicio</a>
        </div>
    </section>
</main>