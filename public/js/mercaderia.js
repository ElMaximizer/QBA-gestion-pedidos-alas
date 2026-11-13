(() => {
    const formulario = document.querySelector('[data-mercaderia-form]');
    if (!formulario) return;
    const lineas = formulario.querySelector('[data-mercaderia-lineas]');
    const agregar = formulario.querySelector('button[value="agregar-linea"]');
    const aviso = formulario.querySelector('[data-mercaderia-aviso]');
    const maximo = Number(formulario.dataset.maximo);
    const actualizar = () => {
        const filas = [...lineas.children];
        filas.forEach((fila, indice) => {
            fila.querySelector('legend').textContent = `Insumo ${indice + 1}`;
            fila.querySelector('select').name = `lineas[${indice}][id_materia]`;
            fila.querySelector('input').name = `lineas[${indice}][cantidad]`;
            const quitar = fila.querySelector('button');
            quitar.value = String(indice);
            quitar.disabled = filas.length === 1;
            quitar.setAttribute('aria-label', `Quitar insumo ${indice + 1}`);
        });
        agregar.disabled = filas.length >= maximo;
        aviso.textContent = `${filas.length} ${filas.length === 1 ? 'insumo' : 'insumos'} en la entrega.`;
    };
    agregar.addEventListener('click', (evento) => {
        evento.preventDefault();
        if (lineas.children.length >= maximo) return;
        const nueva = lineas.firstElementChild.cloneNode(true);
        nueva.querySelector('select').value = '';
        nueva.querySelector('input').value = '';
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
    actualizar();
})();
