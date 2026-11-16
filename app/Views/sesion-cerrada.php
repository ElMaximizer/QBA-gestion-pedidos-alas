<main id="contenido" class="contenedor sesion-cerrada">
    <section class="tarjeta sesion-cerrada__tarjeta" aria-labelledby="sesion-cerrada-titulo">
        <span class="etiqueta sesion-cerrada__etiqueta">RUSTIC · PANADERÍA ARTESANAL</span>
        <span class="sesion-cerrada__marca" aria-hidden="true">✓</span>
        <h1 id="sesion-cerrada-titulo">Gracias por visitarnos.</h1>
        <p class="sesion-cerrada__mensaje">
            Cerraste tu sesión de forma segura. Cuando quieras volver, te esperamos.
        </p>
        <p class="sesion-cerrada__detalle">
            <?= $tipoCuenta === 'equipo' ? 'Tu acceso al panel del equipo quedó cerrado.' : 'Tu cuenta de cliente quedó cerrada.' ?>
        </p>
        <div class="sesion-cerrada__acciones">
            <a class="boton boton--dorado" href="<?= $url('inicio') ?>">Volver al inicio</a>
            <a class="boton boton--contorno" href="<?= $url($tipoCuenta === 'equipo' ? 'equipo-login' : 'cliente-login') ?>">
                Ingresar nuevamente
            </a>
        </div>
    </section>
</main>
