<main id="contenido" class="contacto-pagina">
    <section class="contacto" aria-labelledby="contacto-titulo">
        <div class="contenedor contacto__contenido">
            <div class="contacto__introduccion">
                <span class="etiqueta">ESTAMOS PARA AYUDARTE</span>
                <h1 class="contacto__titulo" id="contacto-titulo">Hablemos de algo rico.</h1>
                <p class="contacto__bajada">Consultanos por productos, disponibilidad o pedidos especiales.</p>
                <div class="contacto__canales" role="group" aria-label="Contacto directo">
                    <a href="https://wa.me/59893555459" target="_blank" rel="noopener noreferrer"
                        aria-label="WhatsApp: 093 555 459 (abre en otra pestaña)">WhatsApp <span>093 555 459</span></a>
                    <a href="tel:+59898734738">Llamanos <span>098 734 738</span></a>
                </div>
            </div>
            <div class="contacto__columnas">
                <form class="tarjeta contacto__formulario" method="post" action="<?= $url('contacto') ?>">
                    <input type="hidden" name="csrf" value="<?= $escapar($csrf) ?>">
                    <?php if ($error !== ''): ?>
                        <p class="contacto__error" role="alert"><?= $escapar($error) ?></p>
                    <?php endif; ?>
                    <label class="contacto__campo">
                        <span>Tu nombre <span class="campo-obligatorio" aria-hidden="true"
                                title="Campo obligatorio">*</span></span>
                        <input name="nombre" type="text" minlength="2" maxlength="120" autocomplete="name" required
                            value="<?= $escapar($datos['nombre']) ?>">
                    </label>
                    <label class="contacto__campo">
                        <span>Correo electrónico <span class="campo-obligatorio" aria-hidden="true"
                                title="Campo obligatorio">*</span></span>
                        <input name="email" type="email" maxlength="190" autocomplete="email" required
                            value="<?= $escapar($datos['email']) ?>">
                    </label>
                    <label class="contacto__campo">
                        <span>¿Sobre qué querés consultar? <span class="campo-obligatorio" aria-hidden="true"
                                title="Campo obligatorio">*</span></span>
                        <select name="motivo" required>
                            <option value="">Elegí un motivo</option>
                            <?php foreach ($motivos as $motivo): ?>
                                <option value="<?= $escapar($motivo) ?>" <?= $datos['motivo'] === $motivo ? 'selected' : '' ?>><?= $escapar($motivo) ?> </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="contacto__campo">
                        <span>Tu mensaje <span class="campo-obligatorio" aria-hidden="true"
                                title="Campo obligatorio">*</span></span>
                        <textarea name="mensaje" rows="6" minlength="5" maxlength="5000"
                            required><?= $escapar($datos['mensaje']) ?></textarea>
                    </label>
                    <p class="contacto__ayuda">Dejanos tu consulta y el equipo podrá responderte al correo que indiques. </p>
                    <button class="boton boton--dorado" type="submit">Enviar consulta</button>
                </form>
                <aside class="contacto__panel">
                    <span class="etiqueta">PANADERÍA RUSTIC</span>
                    <h2>Visitanos en Salto.</h2>
                    <dl class="contacto__datos">
                        <div><dt>Dirección</dt><dd>Uruguay 1486, Salto, Uruguay</dd></div>
                        <div><dt>Horario</dt><dd>Martes a domingo<br>15:30 a 19:30</dd></div>
                    </dl>
                    <a class="enlace-texto" href="https://www.google.com/maps/search/?api=1&amp;query=Uruguay+1486%2C+Salto%2C+Uruguay"
                        target="_blank" rel="noopener noreferrer">Cómo llegar <span class="contacto__enlace-ayuda">(abre en otra pestaña)</span></a>
                    <a class="enlace-texto" href="<?= $url('productos') ?>">Explorar productos </a>
                </aside>
            </div>
            <a class="enlace-texto contacto__volver" href="<?= $url('inicio') ?>"> Volver al inicio</a>
        </div>
    </section>
</main>
