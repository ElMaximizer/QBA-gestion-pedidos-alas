<main id="contenido" class="contenedor pagina-pedidos cuenta-ajustes cuenta-ajustes--confirmacion">
    <a class="enlace-volver" href="<?= $url('cliente-cuenta') ?>">Volver a mi cuenta</a>
    <header class="cuenta-ajustes__cabecera">
        <div>
            <span class="etiqueta">TU ESPACIO EN RUSTIC</span>
            <h1 class="cuenta-ajustes__titulo">Eliminar mi cuenta</h1>
            <p class="cuenta-ajustes__descripcion">Revisá esta información antes de confirmar.</p>
        </div>
    </header>
    <section class="tarjeta cuenta-ajustes__eliminacion" aria-labelledby="eliminar-titulo">
        <div class="cuenta-ajustes__advertencia">
            <h2 class="tarjeta__titulo" id="eliminar-titulo">¿Querés eliminar tu cuenta?</h2>
            <p>Esta acción es permanente. Se eliminarán tus datos de perfil y se cerrarán todas las sesiones de la cuenta.</p>
            <p>Los pedidos y comprobantes del negocio se conservarán. Los pedidos pendientes seguirán vigentes; para cancelarlos, contactá a la panadería.</p>
        </div>
        <?php if ($aviso): ?>
            <div class="aviso aviso--exito" role="status"><?= $escapar($aviso) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="aviso aviso--error" role="alert"><?= $escapar($error) ?></div>
        <?php endif; ?>
        <form class="pila cuenta-ajustes__formulario-eliminar" method="post" action="<?= $url('cliente-cuenta-eliminar') ?>">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <input type="hidden" name="accion" value="enviar-codigo">
            <p class="cuenta-ajustes__descripcion">Para continuar, verificá tu contraseña actual. Enviaremos un código a <strong><?= $escapar($emailCuenta) ?></strong>.</p>
            <?php
            $campoContrasena = ['id' => 'eliminar-password', 'nombre' => 'password', 'etiqueta' => 'Contraseña actual', 'autocomplete' => 'current-password'];
            require __DIR__ . '/partials/cuenta-contrasena.php';
            ?>
            <button class="boton boton--contorno cuenta-ajustes__guardar"><?= $eliminacionPendiente ? 'Enviar un nuevo código' : 'Enviar código' ?></button>
            <small class="campo__ayuda">Esperá un minuto antes de solicitar otro. Pedir un código no elimina tu cuenta.</small>
        </form>
        <?php if ($eliminacionPendiente): ?>
            <form class="pila cuenta-ajustes__formulario-eliminar" method="post" action="<?= $url('cliente-cuenta-eliminar') ?>">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                <input type="hidden" name="accion" value="eliminar">
                <label class="campo">Código de verificación
                    <input class="campo__entrada" name="codigo" inputmode="numeric" autocomplete="one-time-code"
                        pattern="[0-9]{6}" minlength="6" maxlength="6" required>
                    <small class="campo__ayuda">El código vence en 10 minutos y admite cinco intentos.</small>
                </label>
                <label class="cuenta-ajustes__confirmar">
                    <input type="checkbox" name="confirmar" value="1" required>
                    Entiendo que perderé el acceso a esta cuenta y quiero eliminarla.
                </label>
                <button class="boton boton--rojo cuenta-ajustes__guardar">Confirmar eliminación de mi cuenta</button>
            </form>
        <?php endif; ?>
        <form class="cuenta-ajustes__acciones-eliminar" method="post" action="<?= $url('cliente-cuenta-eliminar') ?>">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <input type="hidden" name="accion" value="cancelar">
            <button class="boton boton--contorno">Conservar mi cuenta</button>
        </form>
    </section>
</main>
