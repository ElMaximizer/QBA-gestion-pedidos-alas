<label class="campo" for="<?= $escapar($campoContrasena['id']) ?>">
    <?= $escapar($campoContrasena['etiqueta']) ?>
    <span class="cuenta-ajustes__contrasena">
        <input class="campo__entrada cuenta-ajustes__entrada-password" type="password"
            id="<?= $escapar($campoContrasena['id']) ?>" name="<?= $escapar($campoContrasena['nombre']) ?>"
            autocomplete="<?= $escapar($campoContrasena['autocomplete']) ?>" maxlength="72"
            <?= $campoContrasena['autocomplete'] === 'new-password' ? 'minlength="8"' : '' ?> required>
        <button class="boton-texto cuenta-ajustes__mostrar" type="button"
            data-toggle-password="<?= $escapar($campoContrasena['id']) ?>"
            aria-label="Mostrar contraseña" aria-controls="<?= $escapar($campoContrasena['id']) ?>"
            aria-pressed="false" hidden>Mostrar</button>
    </span>
</label>
