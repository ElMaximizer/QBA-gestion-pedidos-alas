<?php
$esCliente = $tipoRecuperacion === 'cliente';
$rutaLogin = $esCliente ? 'cliente-login' : 'equipo-login';
$textoIdentidad = $esCliente ? 'Correo electrónico' : 'Cédula';
?>
<main id="contenido" class="contenedor registro-pagina recuperacion-pagina">
    <a class="enlace-volver" href="<?= $url('inicio') ?>">Volver al sitio</a>
    <section class="tarjeta registro__tarjeta recuperacion-tarjeta" aria-labelledby="recuperacion-titulo">
        <span class="etiqueta registro__etiqueta">CUENTA <?= $esCliente ? 'DE CLIENTE' : 'DEL EQUIPO' ?></span>
        <?php if ($resultado !== ''): ?>
            <h1 class="registro__titulo" id="recuperacion-titulo">Contraseña actualizada</h1>
            <div class="aviso aviso--exito" role="status"><?= $escapar($resultado) ?></div>
            <a class="boton boton--dorado boton--completo" href="<?= $url($rutaLogin) ?>">Volver al inicio de sesión</a>
        <?php else: ?>
            <h1 class="registro__titulo" id="recuperacion-titulo">Recuperar contraseña</h1>
            <p class="registro__descripcion">Te ayudamos a volver a entrar a tu cuenta con un código enviado al correo registrado.</p>
            <?php if ($error !== ''): ?>
                <div class="aviso aviso--error" role="alert">
                    <strong class="aviso__titulo">No pudimos completar el cambio</strong>
                    <p class="aviso__descripcion"><?= $escapar($error) ?></p>
                </div>
            <?php endif; ?>
            <?php if ($mensaje !== ''): ?>
                <div class="aviso aviso--exito" role="status"><?= $escapar($mensaje) ?></div>
            <?php endif; ?>

            <?php if ($mostrarVerificacion): ?>
                <form method="post" action="<?= $url($esCliente ? 'cliente-recuperar-password' : 'equipo-recuperar-password') ?>" class="pila">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="accion" value="verificar_codigo">
                    <label class="campo">
                        <span class="campo-obligatorio__etiqueta">Código de seis dígitos</span>
                        <input class="campo__entrada" type="text" name="codigo" inputmode="numeric" pattern="[0-9]{6}"
                            minlength="6" maxlength="6" autocomplete="one-time-code" required>
                        <small class="campo__ayuda">El código vence a los 10 minutos.</small>
                    </label>
                    <button class="boton boton--dorado boton--completo" type="submit">Validar código</button>
                </form>
                <div class="recuperacion-acciones">
                    <form method="post" action="<?= $url($esCliente ? 'cliente-recuperar-password' : 'equipo-recuperar-password') ?>">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                        <input type="hidden" name="accion" value="reenviar">
                        <button class="boton boton--contorno boton--completo" type="submit">Reenviar código</button>
                    </form>
                    <a class="recuperacion-cambiar-identidad" href="<?= $url($esCliente ? 'cliente-recuperar-password' : 'equipo-recuperar-password', ['nuevo' => '1']) ?>">Usar otro <?= $esCliente ? 'correo' : 'usuario' ?></a>
                </div>
            <?php elseif ($mostrarCambio): ?>
                <p class="recuperacion-codigo-validado" role="status">Código validado. Ahora elegí una nueva contraseña.</p>
                <form method="post" action="<?= $url($esCliente ? 'cliente-recuperar-password' : 'equipo-recuperar-password') ?>" class="pila">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="accion" value="restablecer">
                    <label class="campo">
                        <span class="campo-obligatorio__etiqueta">Nueva contraseña</span>
                        <input class="campo__entrada" id="nueva-password" type="password" name="password"
                            minlength="8" maxlength="72" autocomplete="new-password"
                            aria-describedby="ayuda-nueva-password" required>
                        <small class="campo__ayuda" id="ayuda-nueva-password">Debe tener al menos 8 caracteres.</small>
                    </label>
                    <label class="campo">
                        <span class="campo-obligatorio__etiqueta">Repetir nueva contraseña</span>
                        <input class="campo__entrada" id="repetir-password" type="password" name="confirm_password"
                            minlength="8" maxlength="72" autocomplete="new-password"
                            aria-describedby="ayuda-repetir-password" required>
                        <small class="campo__ayuda" id="ayuda-repetir-password">Tiene que coincidir con la contraseña nueva.</small>
                    </label>
                    <button class="boton boton--dorado boton--completo" type="submit">Guardar nueva contraseña</button>
                </form>
            <?php else: ?>
                <form method="post" action="<?= $url($esCliente ? 'cliente-recuperar-password' : 'equipo-recuperar-password') ?>" class="pila">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="accion" value="solicitar">
                    <label class="campo">
                        <span class="campo-obligatorio__etiqueta"><?= $textoIdentidad ?></span>
                        <input class="campo__entrada" type="<?= $esCliente ? 'email' : 'text' ?>"
                            name="<?= $esCliente ? 'email' : 'cedula' ?>" value="<?= $escapar($identidad) ?>"
                            <?= $esCliente ? 'autocomplete="email" maxlength="190"' : 'inputmode="numeric" pattern="[0-9]{6,8}" maxlength="8" autocomplete="username"' ?> required>
                        <?php if (!$esCliente): ?><small class="campo__ayuda">Ingresá la cédula asociada a tu cuenta; enviaremos el código al correo registrado.</small><?php endif; ?>
                    </label>
                    <button class="boton boton--dorado boton--completo" type="submit">Enviar código</button>
                    <a class="boton boton--contorno boton--completo" href="<?= $url($rutaLogin) ?>">Volver al acceso</a>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</main>
