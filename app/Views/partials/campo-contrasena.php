<label class="campo">Contraseña
    <span class="contrasena">
        <input class="campo__entrada contrasena__entrada" id="password" type="password" name="password" aria-label="Contraseña" minlength="8" maxlength="72" autocomplete="<?= $escapar($autocompletePassword) ?>" <?= !empty($placeholderPassword) ? 'placeholder="' . $escapar($placeholderPassword) . '"' : '' ?> <?= !empty($ayudaPassword) ? 'aria-describedby="ayuda-password"' : '' ?> required>
        <button type="button" class="boton-texto contrasena__boton" data-toggle-password="password" aria-label="Mostrar contraseña" aria-controls="password" aria-pressed="false" hidden>Mostrar</button>
    </span>
    <?php if (!empty($ayudaPassword)): ?>
    <small class="campo__ayuda" id="ayuda-password"><?= $escapar($ayudaPassword) ?></small>
    <?php endif; ?>
</label>
