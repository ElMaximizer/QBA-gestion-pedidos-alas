'use strict';

document.querySelectorAll('[data-toggle-password]').forEach((boton) => {
    const entrada = document.getElementById(boton.dataset.togglePassword);
    if (!entrada) return;

    boton.hidden = false;
    boton.addEventListener('click', () => {
        const visible = entrada.type === 'password';
        entrada.type = visible ? 'text' : 'password';
        boton.textContent = visible ? 'Ocultar' : 'Mostrar';
        boton.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
        boton.setAttribute('aria-pressed', String(visible));
    });
});
