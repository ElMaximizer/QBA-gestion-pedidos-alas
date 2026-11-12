<main id="contenido" class="contenedor pagina-pedidos cuenta-ajustes cuenta-ajustes--confirmacion">
    <a class="enlace-volver" href="<?= $url('cliente-cuenta') ?>">Volver a mi cuenta</a>
    <section class="tarjeta pila cuenta-ajustes__peligro">
        <span class="etiqueta">MI CUENTA</span>
        <h1 class="tarjeta__titulo">¿Querés eliminar tu cuenta?</h1>
        <p>Esta acción es permanente. Se eliminarán tus datos de perfil y se cerrarán todas las sesiones de la cuenta.</p>
        <p>Los pedidos y comprobantes del negocio se conservarán. Los pedidos pendientes seguirán vigentes; para cancelarlos, contactá a la panadería.</p>
        <?php if ($error): ?>
            <div class="aviso aviso--error" role="alert"><?= $escapar($error) ?></div>
        <?php endif; ?>
        <form class="pila" method="post" action="<?= $url('cliente-cuenta-eliminar') ?>">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <?php
            $campoContrasena = ['id' => 'eliminar-password', 'nombre' => 'password', 'etiqueta' => 'Contraseña actual', 'autocomplete' => 'current-password'];
            require __DIR__ . '/partials/cuenta-contrasena.php';
            ?>
            <label class="cuenta-ajustes__confirmar">
                <input type="checkbox" name="confirmar" value="1" required>
                Entiendo que perderé el acceso a esta cuenta y quiero eliminarla.
            </label>
            <button class="boton boton--rojo">Confirmar eliminación de mi cuenta</button>
            <a class="boton boton--contorno" href="<?= $url('cliente-cuenta') ?>">Conservar mi cuenta</a>
        </form>
    </section>
</main>
