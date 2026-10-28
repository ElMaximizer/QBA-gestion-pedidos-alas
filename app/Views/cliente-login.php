<main id="contenido" class="contenedor registro-pagina">
    <a class="enlace-volver" href="<?= $url('inicio') ?>">← Volver al sitio</a>
    <div class="registro">
        <section class="registro__historia">
            <span class="etiqueta">PANADERÍA ALAS</span>
            <h1 class="registro__titulo-historia">Acceso para<br>clientes.</h1>
            <p class="registro__descripcion">Iniciá sesión para identificarte como cliente y continuar con tus compras.</p>
            <img class="registro__imagen" src="<?= $escapar($recurso('images/panes.png')) ?>" width="648" height="350" alt="Elaboraciones artesanales de Panadería Alas">
        </section>
        <section class="tarjeta registro__tarjeta">
            <span class="etiqueta registro__etiqueta">CUENTA DE CLIENTE</span>
            <h1 class="registro__titulo">Acceso para clientes</h1>
            <?php if ($error): ?>
            <div class="aviso aviso--error" role="alert">
                <strong class="aviso__titulo">No pudimos procesar tus datos</strong>
                <p class="aviso__descripcion"><?= $escapar($error) ?></p>
            </div>
            <?php endif; ?>
            <?php if (!empty($_SESSION['qba']['registro_pendiente_email'])): ?>
            <a class="boton boton--contorno boton--completo" href="<?= $url('verificar-cuenta') ?>">Verificar mi cuenta con un código</a>
            <?php endif; ?>
            <form method="post" action="<?= $url('cliente-login') ?>" class="pila">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                <label class="campo">Correo electrónico
                    <input class="campo__entrada" type="email" name="email" value="<?= $escapar($email) ?>" maxlength="190" autocomplete="email" required>
                </label>
                <?php $autocompletePassword = 'current-password'; require __DIR__ . '/partials/campo-contrasena.php'; ?>
                <button class="boton boton--dorado boton--completo">Ingresar</button>
                <p class="texto-pequeno">¿Todavía no tenés cuenta?</p>
                <a class="boton boton--contorno boton--completo" href="<?= $url('registro') ?>">Crear cuenta de cliente</a>
            </form>
        </section>
    </div>
</main>
