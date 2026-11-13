<main id="contenido" class="contenedor registro-pagina">
    <a class="enlace-volver" href="<?= $url('inicio') ?>"> Volver al sitio</a>
    <div class="registro">
        <section class="registro__historia">
            <span class="etiqueta">PANADERÍA RUSTIC</span>
            <h1 class="registro__titulo-historia">Tu panadería,<br>más cerca.</h1>
            <p class="registro__descripcion">Creá tu cuenta y confirmá tu correo con el código de verificación.</p>
            <img class="registro__imagen" src="<?= $escapar($recurso('images/pan-horneado.webp')) ?>" width="648"
                height="350" alt="Elaboraciones artesanales de Panadería Rustic">
        </section>
        <section class="tarjeta registro__tarjeta">
            <span class="etiqueta registro__etiqueta">CUENTA DE CLIENTE</span>
            <h1 class="registro__titulo">Crear cuenta de cliente</h1>
            <?php if ($error): ?>
                <div class="aviso aviso--error" role="alert">
                    <strong class="aviso__titulo">No pudimos procesar tus datos</strong>
                    <p class="aviso__descripcion"><?= $escapar($error) ?></p>
                </div>
            <?php endif; ?>
            <form method="post" action="<?= $url('registro') ?>" class="pila">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                <label class="campo">
                    <span class="campo-obligatorio__etiqueta">Nombre completo <span class="campo-obligatorio"
                            aria-hidden="true" title="Campo obligatorio">*</span></span>
                    <input class="campo__entrada" name="nombre" value="<?= $escapar($nombre) ?>" maxlength="120"
                        autocomplete="name" required>
                </label>
                <label class="campo">
                    <span class="campo-obligatorio__etiqueta">Correo electrónico <span class="campo-obligatorio"
                            aria-hidden="true" title="Campo obligatorio">*</span></span>
                    <input class="campo__entrada" type="email" name="email" value="<?= $escapar($email) ?>"
                        maxlength="190" autocomplete="email" required>
                </label>
                <?php
                $autocompletePassword = 'new-password';
                require __DIR__ . '/partials/campo-contrasena.php';
                ?>
                <label class="campo">
                    <span class="campo-obligatorio__etiqueta">Confirmar contraseña <span class="campo-obligatorio"
                            aria-hidden="true" title="Campo obligatorio">*</span></span>
                    <input class="campo__entrada" type="password" name="confirm_password" minlength="8" maxlength="72"
                        autocomplete="new-password" required>
                </label>
                <div class="registro__aceptaciones">
                    <div class="registro__aceptacion">
                        <input class="registro__casilla" type="checkbox" id="acepta-terminos" name="acepta_terminos"
                            value="1" required <?= $aceptaciones['acepta_terminos'] ? 'checked' : '' ?> aria-describedby="politicas-ayuda">
                        <label class="registro__politicas" for="acepta-terminos">Acepto los
                            <a href="<?= $escapar($url('aviso-legal') . '#terminos') ?>" target="_blank" rel="noopener noreferrer">Términos y condiciones</a>.
                        </label>
                    </div>
                    <div class="registro__aceptacion">
                        <input class="registro__casilla" type="checkbox" id="acepta-privacidad" name="acepta_privacidad"
                            value="1" required <?= $aceptaciones['acepta_privacidad'] ? 'checked' : '' ?> aria-describedby="politicas-ayuda">
                        <label class="registro__politicas" for="acepta-privacidad">Acepto la
                            <a href="<?= $escapar($url('aviso-legal') . '#privacidad') ?>" target="_blank" rel="noopener noreferrer">Política de privacidad</a>.
                        </label>
                    </div>
                    <small class="registro__politicas-ayuda" id="politicas-ayuda">Los enlaces se abren en otra pestaña.</small>
                </div>
                <button class="boton boton--dorado boton--completo">Crear cuenta</button>
                <a class="enlace-texto" href="<?= $url('cliente-login') ?>">Ya tengo cuenta · Iniciar sesión</a>
                <?php if ($error || !empty($_SESSION['qba']['registro_pendiente_email'])): ?>
                    <a class="enlace-texto" href="<?= $url('verificar-cuenta') ?>">Verificar mi cuenta</a>
                <?php endif; ?>
            </form>
        </section>
    </div>
</main>
