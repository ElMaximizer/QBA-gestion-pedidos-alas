<main id="contenido" class="contenedor registro-pagina">
    <a class="enlace-volver" href="<?= $url('inicio') ?>">← Volver al sitio</a>
    <div class="registro">
        <?php require __DIR__ . '/partials/equipo-historia.php'; ?>
        <section class="tarjeta registro__tarjeta">
            <span class="etiqueta registro__etiqueta">ACCESO DEL EQUIPO</span>
            <h1 class="registro__titulo">Acceso del equipo</h1>
            <p>Ingresa con tu cédula de empleado, administrador o dueño.</p>
            <?php if ($error): ?>
            <div class="aviso aviso--error" role="alert">
                <strong class="aviso__titulo">No pudimos iniciar sesión</strong>
                <p class="aviso__descripcion"><?= $escapar($error) ?></p>
            </div>
            <?php endif; ?>
            <form method="post" action="<?= $url('equipo-login') ?>" class="pila">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                <label class="campo">Cédula
                    <input class="campo__entrada" type="text" name="cedula" value="<?= $escapar($cedula) ?>" placeholder="Ingresa tu número de cédula" inputmode="numeric" pattern="[0-9]{6,8}" maxlength="8" autocomplete="username" aria-describedby="ayuda-cedula" required>
                    <span class="campo__ayuda" id="ayuda-cedula">Sin puntos ni guiones.</span>
                </label>
                <?php $autocompletePassword = 'current-password'; $placeholderPassword = '••••••••'; require __DIR__ . '/partials/campo-contrasena.php'; ?>
                <button class="boton boton--dorado boton--completo">Iniciar sesión</button>
                <hr class="registro__separador">
                <p class="texto-pequeno">¿Todavía no tienes acceso?</p>
                <a class="boton boton--contorno boton--completo" href="<?= $url('equipo-registro') ?>">Solicitar usuario</a>
            </form>
        </section>
    </div>
</main>
