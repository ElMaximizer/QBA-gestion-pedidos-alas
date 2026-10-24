<main id="contenido" class="contenedor registro-pagina">
    <section class="tarjeta vacio registro__completado">
        <span class="insignia insignia--exito">Cuenta verificada</span>
        <h1 class="vacio__titulo">¡Tu cuenta está lista!</h1>
        <p class="vacio__descripcion">Verificaste el correo <strong><?= $escapar($email) ?></strong> y completaste tu registro en Panadería Alas.</p>
        <div class="acciones vacio__acciones">
            <a class="boton boton--dorado" href="<?= $url('productos') ?>">Ver productos</a>
            <a class="boton boton--contorno" href="index.php">Volver al inicio</a>
        </div>
    </section>
</main>
