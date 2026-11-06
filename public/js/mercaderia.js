function iniciarFormularioMercaderia() {
    const formulario = document.querySelector('[data-mercaderia-form]');

    if (!formulario) {
        return;
    }

    const contenedorLineas = formulario.querySelector('[data-mercaderia-lineas]');
    const botonAgregar = formulario.querySelector('button[value="agregar-linea"]');
    const aviso = formulario.querySelector('[data-mercaderia-aviso]');
    const maximoLineas = Number(formulario.dataset.maximo);

    function actualizarLineas() {
        const filas = Array.from(contenedorLineas.children);

        for (let indice = 0; indice < filas.length; indice++) {
            const fila = filas[indice];
            const botonQuitar = fila.querySelector('button');

            fila.querySelector('legend').textContent = `Insumo ${indice + 1}`;
            fila.querySelector('select').name = `lineas[${indice}][id_materia]`;
            fila.querySelector('input').name = `lineas[${indice}][cantidad]`;
            botonQuitar.value = String(indice);
            botonQuitar.disabled = filas.length === 1;
            botonQuitar.setAttribute('aria-label', `Quitar insumo ${indice + 1}`);
        }

        const nombreCantidad = filas.length === 1 ? 'insumo' : 'insumos';
        botonAgregar.disabled = filas.length >= maximoLineas;
        aviso.textContent = `${filas.length} ${nombreCantidad} en la entrega.`;
    }

    botonAgregar.addEventListener('click', function (evento) {
        evento.preventDefault();

        if (contenedorLineas.children.length >= maximoLineas) {
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

        if (contenedorLineas.children.length <= 1) {
            return;
        }

        botonQuitar.closest('fieldset').remove();
        actualizarLineas();
        botonAgregar.focus();
    });

    actualizarLineas();
}

iniciarFormularioMercaderia();
