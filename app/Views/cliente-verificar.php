<main id="contenido" class="contenedor registro-pagina">
    <a class="enlace-volver" href="<?= $url('registro') ?>">← Volver al registro de clientes</a>
    <div class="registro">
        <section class="registro__historia">
            <span class="etiqueta">ACTIVACIÓN DE CUENTA</span>
            <h1 class="registro__titulo-historia">Un paso más<br>y ya está.</h1>
            <p class="registro__descripcion">Usá el código de seis dígitos del mensaje de verificación para activar tu cuenta.</p>
        </section>
        <section class="tarjeta registro__tarjeta">
            <span class="etiqueta registro__etiqueta">VERIFICAR CORREO</span>
            <h1 class="registro__titulo">Activá tu cuenta</h1>
            <?php if ($mensaje): ?>
            <div class="aviso aviso--exito" role="status"><?= $escapar($mensaje) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="aviso aviso--error" role="alert">
                <strong class="aviso__titulo">No pudimos verificar la cuenta</strong>
                <p class="aviso__descripcion"><?= $escapar($error) ?></p>
            </div>
            <?php endif; ?>
            <form id="verificacion-cuenta" method="post" action="<?= $url('verificar-cuenta') ?>" class="pila">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                <label class="campo">Correo electrónico
                    <input class="campo__entrada" type="email" name="email" value="<?= $escapar($email) ?>" maxlength="190" autocomplete="email" required>
                </label>
                <label class="campo">Código de verificación
                    <input class="campo__entrada" name="codigo" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="123456" required>
                </label>
                <button class="boton boton--dorado boton--completo" name="accion" value="verificar">Verificar y activar</button>
            </form>
            <div class="pila registro__reenvio">
                <button type="submit" form="verificacion-cuenta" formnovalidate class="boton boton--contorno boton--completo" name="accion" value="reenviar">Reenviar código</button>
            </div>
        </section>
    </div>
</main>
