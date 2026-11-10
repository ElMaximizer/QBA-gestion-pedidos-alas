(function () {
    const dialogo = document.querySelector('[data-transferencia-dialogo]');
    if (!dialogo) return;

    const campoId = dialogo.querySelector('[data-transferencia-id]');
    const referencia = dialogo.querySelector('[data-transferencia-referencia]');
    const total = dialogo.querySelector('[data-transferencia-total]');
    const nombre = dialogo.getAttribute('data-nombre-pedido') || '';
    const pasos = Array.from(dialogo.querySelectorAll('[data-transferencia-paso]'));
    const pasoDatos = dialogo.querySelector('[data-transferencia-paso="datos"]');
    const pasoComprobante = dialogo.querySelector('[data-transferencia-paso="comprobante"]');

    function mostrarPaso(paso) {
        pasos.forEach(function (seccion) {
            seccion.hidden = seccion !== paso;
        });
        if (paso && paso.querySelector('h2')) {
            dialogo.setAttribute('aria-labelledby', paso.querySelector('h2').id);
        }
    }

    function abrir(idPedido, importe, nombreCliente) {
        mostrarPaso(pasoDatos);
        if (idPedido && campoId) campoId.value = idPedido;
        if (idPedido && referencia) referencia.textContent = (nombreCliente || '') + ' · Pedido #' + idPedido;
        if (importe && total) total.textContent = importe;
        if (typeof dialogo.showModal === 'function' && !dialogo.open) dialogo.showModal();
    }

    document.querySelectorAll('[data-abrir-transferencia]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            abrir(boton.getAttribute('data-id-pedido'), boton.getAttribute('data-total-pedido'), boton.getAttribute('data-nombre-cliente'));
        });
    });

    const irAComprobante = dialogo.querySelector('[data-ir-a-comprobante]');
    if (irAComprobante && pasoComprobante) {
        irAComprobante.addEventListener('click', function () {
            mostrarPaso(pasoComprobante);
            const selectorArchivo = pasoComprobante.querySelector('input[type="file"]');
            if (selectorArchivo) selectorArchivo.focus();
        });
    }

    const volverADatos = dialogo.querySelector('[data-volver-a-datos]');
    if (volverADatos) volverADatos.addEventListener('click', function () { mostrarPaso(pasoDatos); });

    const cerrar = dialogo.querySelector('[data-cerrar-transferencia]');
    if (cerrar) cerrar.addEventListener('click', function () { dialogo.close(); });

    dialogo.addEventListener('close', function () { mostrarPaso(pasoDatos); });

    if (dialogo.getAttribute('data-auto-abrir') === 'true') {
        abrir(campoId ? campoId.value : '', total ? total.textContent : '', nombre);
    }

    const selectorComprobante = dialogo.querySelector('[data-selector-comprobante]');
    const nombreComprobante = dialogo.querySelector('[data-nombre-comprobante]');
    if (selectorComprobante && nombreComprobante) {
        selectorComprobante.addEventListener('change', function () {
            nombreComprobante.textContent = selectorComprobante.files.length
                ? selectorComprobante.files[0].name
                : 'Elegí una imagen para adjuntar';
        });
    }
}());
