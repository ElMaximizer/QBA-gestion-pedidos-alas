(() => {
    const formulario = document.querySelector('[data-pedido-equipo]');
    if (!formulario) return;
    const lineas = formulario.querySelector('[data-pedido-lineas]');
    const agregar = formulario.querySelector('button[value="agregar-linea"]');
    const resumen = formulario.querySelector('[data-pedido-resumen]');
    const incompleto = formulario.querySelector('[data-pedido-incompleto]');
    const total = formulario.querySelector('[data-pedido-total]');
    const retiro = formulario.querySelector('[data-pedido-retiro]');
    const maximo = Number(formulario.dataset.maximo);
    const maximaCantidad = Number(formulario.dataset.cantidadMaxima);
    const dinero = (centavos) => '$ ' + (centavos / 100).toLocaleString('es-UY', {
        minimumFractionDigits: centavos % 100 === 0 ? 0 : 2, maximumFractionDigits: 2,
    });

    const actualizar = () => {
        resumen.replaceChildren();
        const filas = [...lineas.children];
        const ids = filas.map((fila) => fila.querySelector('select').value);
        let importe = 0;
        let completo = true;
        filas.forEach((fila, indice) => {
            const selector = fila.querySelector('select');
            const cantidad = fila.querySelector('input');
            const quitar = fila.querySelector('button');
            fila.querySelector('legend').textContent = `Producto ${indice + 1}`;
            selector.name = `lineas[${indice}][id_producto]`;
            cantidad.name = `lineas[${indice}][cantidad]`;
            quitar.value = String(indice);
            quitar.disabled = filas.length === 1;
            quitar.setAttribute('aria-label', `Quitar producto ${indice + 1}`);
            const repetido = selector.value !== '' && ids.filter((id) => id === selector.value).length > 1;
            selector.setCustomValidity(repetido ? 'Este producto está repetido. Ajustá su cantidad en una sola línea.' : '');
            const opcion = selector.selectedOptions[0];
            const precio = Number(opcion?.dataset.precio);
            const unidades = Number(cantidad.value);
            if (!opcion?.dataset.precio || !Number.isInteger(precio) || !Number.isInteger(unidades)
                || unidades < 1 || unidades > maximaCantidad || repetido) {
                completo = false;
                return;
            }
            const subtotal = precio * unidades;
            importe += subtotal;
            const articulo = document.createElement('li');
            articulo.className = 'pedido-equipo__articulo';
            const nombre = document.createElement('span');
            nombre.textContent = `${opcion.dataset.nombre} × ${unidades}`;
            const valor = document.createElement('strong');
            valor.textContent = dinero(subtotal);
            articulo.append(nombre, valor);
            resumen.append(articulo);
        });
        agregar.disabled = filas.length >= maximo || formulario.querySelector('button[value="registrar"]').disabled;
        incompleto.hidden = completo;
        total.textContent = completo ? dinero(importe) : '—';
        const fecha = formulario.elements.fecha_retiro.value;
        retiro.textContent = `Retiro · ${fecha ? fecha.split('-').reverse().join('/') : 'Elegí una fecha'} · ${formulario.elements.hora_retiro.value || 'Elegí una hora'}`;
        const estado = formulario.querySelector('[data-pedido-estado]');
        if (estado) formulario.querySelector('[data-pedido-estado-resumen]').textContent = `Estado: ${estado.value}`;
    };

    agregar.addEventListener('click', (evento) => {
        evento.preventDefault();
        if (lineas.children.length >= maximo) return;
        const nueva = lineas.firstElementChild.cloneNode(true);
        nueva.querySelector('select').value = '';
        nueva.querySelector('input').value = '1';
        lineas.append(nueva);
        actualizar();
        nueva.querySelector('select').focus();
    });
    lineas.addEventListener('click', (evento) => {
        const quitar = evento.target.closest('button[name="quitar_linea"]');
        if (!quitar) return;
        evento.preventDefault();
        if (lineas.children.length <= 1) return;
        quitar.closest('fieldset').remove();
        actualizar();
        agregar.focus();
    });
    formulario.addEventListener('input', actualizar);
    formulario.addEventListener('change', actualizar);
    // Enter en un campo registra el pedido; no activa los botones auxiliares de las líneas.
    formulario.addEventListener('keydown', (evento) => {
        if (evento.key === 'Enter' && evento.target.matches('input')) {
            evento.preventDefault();
            formulario.requestSubmit(formulario.querySelector('button[value="registrar"]'));
        }
    });
    actualizar();
    document.querySelector('[data-pedido-error]')?.focus();
})();
