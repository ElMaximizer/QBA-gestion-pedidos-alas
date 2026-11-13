function iniciarControlStock() {
    const pantalla = document.querySelector('.equipo-stock');

    if (!pantalla) {
        return;
    }

    const formularios = new Map();
    for (const formulario of pantalla.querySelectorAll('[data-stock-guardar]')) {
        const boton = formulario.querySelector('button');
        if (!boton) {
            continue;
        }

        formularios.set(formulario, {
            boton,
            texto: boton.textContent,
            etiqueta: boton.getAttribute('aria-label'),
        });
        formulario.addEventListener('submit', function (evento) {
            if (evento.defaultPrevented) {
                return;
            }
            if (formulario.dataset.enviando === 'true') {
                evento.preventDefault();
                return;
            }

            formulario.dataset.enviando = 'true';
            formulario.setAttribute('aria-busy', 'true');
            boton.disabled = true;
            boton.textContent = formulario.dataset.stockGuardar;
            boton.setAttribute('aria-label', formulario.dataset.stockGuardar);
        });
    }

    window.addEventListener('pageshow', function () {
        for (const [formulario, estado] of formularios) {
            delete formulario.dataset.enviando;
            formulario.removeAttribute('aria-busy');
            estado.boton.disabled = false;
            estado.boton.textContent = estado.texto;
            if (estado.etiqueta === null) {
                estado.boton.removeAttribute('aria-label');
            } else {
                estado.boton.setAttribute('aria-label', estado.etiqueta);
            }
        }
    });

    const aviso = pantalla.querySelector('[data-stock-aviso][role="alert"]')
        || pantalla.querySelector('[data-stock-aviso][role="status"]');
    aviso?.focus();
}

iniciarControlStock();
