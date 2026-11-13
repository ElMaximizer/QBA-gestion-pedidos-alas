function iniciarConsultaPersonal() {
    const formulario = document.querySelector('[data-personal-filtros]');
    const boton = formulario?.querySelector('button[type="submit"]');

    if (!formulario || !boton) {
        return;
    }

    const textoInicial = boton.textContent;
    formulario.addEventListener('submit', function (evento) {
        if (evento.defaultPrevented) {
            return;
        }
        if (formulario.dataset.consultando === 'true') {
            evento.preventDefault();
            return;
        }

        formulario.dataset.consultando = 'true';
        formulario.setAttribute('aria-busy', 'true');
        boton.disabled = true;
        boton.textContent = 'Consultando…';
    });

    window.addEventListener('pageshow', function () {
        delete formulario.dataset.consultando;
        formulario.removeAttribute('aria-busy');
        boton.disabled = false;
        boton.textContent = textoInicial;
    });

    const aviso = document.querySelector('[data-personal-aviso][role="alert"]')
        || document.querySelector('[data-personal-aviso][role="status"]');
    aviso?.focus();
}

iniciarConsultaPersonal();
