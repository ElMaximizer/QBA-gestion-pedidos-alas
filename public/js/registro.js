function iniciarBotonesContrasena() {
    'use strict';

    const botones = document.querySelectorAll('[data-toggle-password]');

    for (const boton of botones) {
        const campoContrasena = document.getElementById(boton.dataset.togglePassword);

        if (!campoContrasena) {
            continue;
        }

        boton.hidden = false;
        boton.addEventListener('click', function () {
            const mostrarContrasena = campoContrasena.type === 'password';

            campoContrasena.type = mostrarContrasena ? 'text' : 'password';
            boton.textContent = mostrarContrasena ? 'Ocultar' : 'Mostrar';
            boton.setAttribute(
                'aria-label',
                mostrarContrasena ? 'Ocultar contraseña' : 'Mostrar contraseña',
            );
            boton.setAttribute('aria-pressed', String(mostrarContrasena));
        });
    }
}

iniciarBotonesContrasena();
