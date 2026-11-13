function iniciarConsultaVentas() {
    const formulario = document.querySelector('[data-ventas-filtros]');
    const boton = formulario?.querySelector('[data-ventas-consultar]');

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

    document.querySelector('[data-ventas-error]')?.focus();
}

iniciarConsultaVentas();
