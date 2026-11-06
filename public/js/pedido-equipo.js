function iniciarFormularioPedidoEquipo() {
    const formulario = document.querySelector('[data-pedido-equipo]');

    if (!formulario) {
        return;
    }

    const contenedorLineas = formulario.querySelector('[data-pedido-lineas]');
    const botonAgregar = formulario.querySelector('button[value="agregar-linea"]');
    const botonRegistrar = formulario.querySelector('button[value="registrar"]');
    const listaResumen = formulario.querySelector('[data-pedido-resumen]');
    const avisoIncompleto = formulario.querySelector('[data-pedido-incompleto]');
    const textoTotal = formulario.querySelector('[data-pedido-total]');
    const textoRetiro = formulario.querySelector('[data-pedido-retiro]');
    const maximoProductos = Number(formulario.dataset.maximo);
    const maximaCantidad = Number(formulario.dataset.cantidadMaxima);

    function formatearDinero(centavos) {
        const pesos = centavos / 100;

        return (
            '$ ' +
            pesos.toLocaleString('es-UY', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            })
        );
    }

    function agregarProductoAlResumen(nombreProducto, unidades, subtotal) {
        const articulo = document.createElement('li');
        const nombre = document.createElement('span');
        const importe = document.createElement('strong');

        articulo.className = 'pedido-equipo__articulo';
        nombre.textContent = `${nombreProducto} × ${unidades}`;
        importe.textContent = formatearDinero(subtotal);
        articulo.append(nombre, importe);
        listaResumen.append(articulo);
    }

    function actualizarDatosRetiro() {
        const fecha = formulario.elements.fecha_retiro.value;
        const hora = formulario.elements.hora_retiro.value;
        let fechaVisible = 'Elegí una fecha';

        if (fecha) {
            fechaVisible = fecha.split('-').reverse().join('/');
        }

        textoRetiro.textContent = `Retiro · ${fechaVisible} · ${hora || 'Elegí una hora'}`;

        const campoEstado = formulario.querySelector('[data-pedido-estado]');

        if (campoEstado) {
            const textoEstado = formulario.querySelector('[data-pedido-estado-resumen]');
            textoEstado.textContent = `Estado: ${campoEstado.value}`;
        }
    }

    function actualizarPedido() {
        listaResumen.replaceChildren();

        const filas = Array.from(contenedorLineas.children);
        const vecesSeleccionado = {};

        // Contamos primero las selecciones para avisar en todas las filas repetidas.
        for (const fila of filas) {
            const idProducto = fila.querySelector('select').value;
            vecesSeleccionado[idProducto] = (vecesSeleccionado[idProducto] || 0) + 1;
        }

        let totalCentavos = 0;
        let pedidoCompleto = true;

        for (let indice = 0; indice < filas.length; indice++) {
            const fila = filas[indice];
            const selectorProducto = fila.querySelector('select');
            const campoCantidad = fila.querySelector('input');
            const botonQuitar = fila.querySelector('button');

            fila.querySelector('legend').textContent = `Producto ${indice + 1}`;
            selectorProducto.name = `lineas[${indice}][id_producto]`;
            campoCantidad.name = `lineas[${indice}][cantidad]`;
            botonQuitar.value = String(indice);
            botonQuitar.disabled = filas.length === 1;
            botonQuitar.setAttribute('aria-label', `Quitar producto ${indice + 1}`);

            const idProducto = selectorProducto.value;
            const productoRepetido = idProducto !== '' && vecesSeleccionado[idProducto] > 1;
            let errorProducto = '';

            if (productoRepetido) {
                errorProducto =
                    'Este producto está repetido. Ajustá su cantidad en una sola línea.';
            }

            selectorProducto.setCustomValidity(errorProducto);

            const opcionSeleccionada = selectorProducto.selectedOptions[0];

            if (!opcionSeleccionada || !opcionSeleccionada.dataset.precio) {
                pedidoCompleto = false;
                continue;
            }

            const precioCentavos = Number(opcionSeleccionada.dataset.precio);
            const unidades = Number(campoCantidad.value);
            const cantidadValida =
                Number.isInteger(unidades) && unidades >= 1 && unidades <= maximaCantidad;

            if (!Number.isInteger(precioCentavos) || !cantidadValida || productoRepetido) {
                pedidoCompleto = false;
                continue;
            }

            const subtotal = precioCentavos * unidades;
            totalCentavos += subtotal;
            agregarProductoAlResumen(opcionSeleccionada.dataset.nombre, unidades, subtotal);
        }

        botonAgregar.disabled = filas.length >= maximoProductos || botonRegistrar.disabled;
        avisoIncompleto.hidden = pedidoCompleto;
        textoTotal.textContent = pedidoCompleto ? formatearDinero(totalCentavos) : '—';
        actualizarDatosRetiro();
    }

    botonAgregar.addEventListener('click', function (evento) {
        evento.preventDefault();

        if (contenedorLineas.children.length >= maximoProductos) {
            return;
        }

        const nuevaFila = contenedorLineas.firstElementChild.cloneNode(true);
        nuevaFila.querySelector('select').value = '';
        nuevaFila.querySelector('input').value = '1';
        contenedorLineas.append(nuevaFila);

        actualizarPedido();
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
        actualizarPedido();
        botonAgregar.focus();
    });

    formulario.addEventListener('input', actualizarPedido);
    formulario.addEventListener('change', actualizarPedido);
    formulario.addEventListener('keydown', function (evento) {
        // Enter registra el pedido; los botones para agregar o quitar requieren un clic.
        if (evento.key === 'Enter' && evento.target.matches('input')) {
            evento.preventDefault();
            formulario.requestSubmit(botonRegistrar);
        }
    });

    actualizarPedido();

    const avisoError = document.querySelector('[data-pedido-error]');

    if (avisoError) {
        avisoError.focus();
    }
}

iniciarFormularioPedidoEquipo();
