<main id="contenido" class="contenedor registro-pagina">
    <a class="enlace-volver" href="index.php">← Volver al sitio</a>
    <div class="registro">
        <?php require __DIR__ . '/partials/equipo-historia.php'; ?>
        <section class="tarjeta registro__tarjeta">
            <span class="etiqueta registro__etiqueta">ACCESO DEL EQUIPO</span>
            <h1 class="registro__titulo">Solicitar usuario</h1>
            <p>El dueño revisará tu solicitud antes de habilitar tu acceso al equipo.</p>
            <?php if ($error): ?>
            <div class="aviso aviso--error" role="alert">
                <strong class="aviso__titulo">Revisá los datos</strong>
                <p class="aviso__descripcion"><?= $escapar($error) ?></p>
            </div>
            <?php endif; ?>
            <form method="post" action="<?= $url('equipo-registro') ?>" class="pila">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                <label class="campo">Nombre completo
                    <input class="campo__entrada" name="nombre" value="<?= $escapar($datos['nombre']) ?>" placeholder="Tu nombre y apellido" maxlength="120" autocomplete="name" required>
                </label>
                <label class="campo">Cédula
                    <input class="campo__entrada" name="cedula" value="<?= $escapar($datos['cedula']) ?>" inputmode="numeric" pattern="[0-9]{6,8}" minlength="6" maxlength="8" placeholder="Número de cédula" autocomplete="username" aria-describedby="ayuda-cedula" required>
                    <small class="campo__ayuda" id="ayuda-cedula">Sin puntos ni guiones.</small>
                </label>
                <label class="campo">Correo electrónico
                    <input class="campo__entrada" type="email" name="email" value="<?= $escapar($datos['email']) ?>" maxlength="190" placeholder="nombre@ejemplo.com" autocomplete="email" aria-describedby="ayuda-correo" required>
                    <small class="campo__ayuda" id="ayuda-correo">Usaremos este correo para tu solicitud.</small>
                </label>
                <?php $autocompletePassword = 'new-password'; $placeholderPassword = 'Crea una contraseña'; $ayudaPassword = 'Usa al menos 8 caracteres.';
                    require __DIR__ . '/partials/campo-contrasena.php'; ?>
                <label class="campo">Confirmar contraseña
                    <input class="campo__entrada" type="password" name="confirm_password" placeholder="Repite la contraseña" minlength="8" maxlength="72" autocomplete="new-password" required>
                </label>
                <button class="boton boton--dorado boton--completo">Enviar solicitud</button>
                <a class="enlace-texto" href="index.php">Volver al sitio</a>
            </form>
        </section>
    </div>
</main>
