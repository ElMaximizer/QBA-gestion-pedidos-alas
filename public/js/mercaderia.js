function iniciarFormularioMercaderia() {
    const formulario = document.querySelector('[data-mercaderia-form]');

    if (!formulario) {
        return;
    }

    const contenedorLineas = formulario.querySelector('[data-mercaderia-lineas]');
    const botonAgregar = formulario.querySelector('button[value="agregar-linea"]');
    const aviso = formulario.querySelector('[data-mercaderia-aviso]');
    const botonConfirmar = formulario.querySelector('button[value="registrar"]');
    const maximoLineas = Number(formulario.dataset.maximo);
    const textoConfirmar = botonConfirmar.textContent;

    function actualizarLineas() {
        const filas = Array.from(contenedorLineas.children);
        const repeticiones = new Map();
        for (const fila of filas) {
            const valor = fila.querySelector('select').value;
            if (valor !== '') {
                repeticiones.set(valor, (repeticiones.get(valor) || 0) + 1);
            }
        }

        for (let indice = 0; indice < filas.length; indice++) {
            const fila = filas[indice];
            const botonQuitar = fila.querySelector('button');
            const selector = fila.querySelector('select');
            const error = fila.querySelector('[data-mercaderia-error-insumo]');
            const unidad = selector.selectedOptions[0]?.dataset.unidad;
            const repetida = selector.value !== '' && repeticiones.get(selector.value) > 1;
            const mensaje = repetida ? 'Esta materia prima está repetida. Usá una sola fila y reuní su cantidad recibida.' : '';

            fila.querySelector('legend').textContent = `Insumo ${indice + 1}`;
            selector.name = `lineas[${indice}][id_materia]`;
            fila.querySelector('input').name = `lineas[${indice}][cantidad]`;
            fila.querySelector('[data-mercaderia-cantidad-etiqueta]').textContent = unidad
                ? `Cantidad recibida (${unidad})` : 'Cantidad recibida';
            error.id = `mercaderia-error-insumo-${indice}`;
            error.textContent = mensaje;
            error.hidden = !repetida;
            selector.setCustomValidity(mensaje);
            selector.setAttribute('aria-describedby', error.id);
            if (repetida) {
                selector.setAttribute('aria-invalid', 'true');
            } else {
                selector.removeAttribute('aria-invalid');
            }
            botonQuitar.value = String(indice);
            botonQuitar.disabled = filas.length === 1;
            botonQuitar.setAttribute('aria-label', `Quitar insumo ${indice + 1}`);
        }

        botonAgregar.disabled = filas.length >= maximoLineas;
        aviso.textContent = `${filas.length} de ${maximoLineas} insumos en la entrega.`
            + (filas.length >= maximoLineas ? ' Llegaste al máximo permitido.' : '');
    }

    botonAgregar.addEventListener('click', function (evento) {
        evento.preventDefault();

        if (formulario.dataset.enviando === 'true' || contenedorLineas.children.length >= maximoLineas) {
            return;
        }

        const nuevaFila = contenedorLineas.firstElementChild.cloneNode(true);
        nuevaFila.querySelector('select').value = '';
        nuevaFila.querySelector('input').value = '';
        contenedorLineas.append(nuevaFila);

        actualizarLineas();
        nuevaFila.querySelector('select').focus();
    });

    contenedorLineas.addEventListener('click', function (evento) {
        const botonQuitar = evento.target.closest('button[name="quitar_linea"]');

        if (!botonQuitar) {
            return;
        }

        evento.preventDefault();

        if (formulario.dataset.enviando === 'true' || contenedorLineas.children.length <= 1) {
            return;
        }

        const fila = botonQuitar.closest('fieldset');
        const siguiente = fila.nextElementSibling || fila.previousElementSibling;
        fila.remove();
        actualizarLineas();
        siguiente.querySelector('select').focus();
    });

    contenedorLineas.addEventListener('change', function (evento) {
        if (evento.target.matches('select')) {
            actualizarLineas();
        }
    });

    formulario.addEventListener('submit', function (evento) {
        if (evento.defaultPrevented) {
            return;
        }
        if (formulario.dataset.enviando === 'true') {
            evento.preventDefault();
            return;
        }
        if (evento.submitter !== botonConfirmar) {
            return;
        }

        formulario.dataset.enviando = 'true';
        formulario.setAttribute('aria-busy', 'true');
        // Sigue habilitado para enviar accion=registrar junto con el formulario.
        botonConfirmar.setAttribute('aria-disabled', 'true');
        botonConfirmar.textContent = 'Registrando recepción…';
    });

    window.addEventListener('pageshow', function () {
        delete formulario.dataset.enviando;
        formulario.removeAttribute('aria-busy');
        botonConfirmar.removeAttribute('aria-disabled');
        botonConfirmar.textContent = textoConfirmar;
        actualizarLineas();
    });

    actualizarLineas();
}

function iniciarAvisosMercaderia() {
    const aviso = document.querySelector('[data-mercaderia-mensaje][role="alert"]')
        || document.querySelector('[data-mercaderia-mensaje][role="status"]');
    aviso?.focus();
}

iniciarFormularioMercaderia();
iniciarAvisosMercaderia();
