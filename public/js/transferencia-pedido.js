(function () {
    const dialogo = document.querySelector('[data-transferencia-dialogo]');
    if (!dialogo || typeof dialogo.showModal !== 'function') return;

    const campoId = dialogo.querySelector('[data-transferencia-id]');
    const referencia = dialogo.querySelector('[data-transferencia-referencia]');
    const total = dialogo.querySelector('[data-transferencia-total]');
    const nombre = dialogo.getAttribute('data-nombre-pedido') || '';
    const pasos = Array.from(dialogo.querySelectorAll('[data-transferencia-paso]'));
    const pasoDatos = dialogo.querySelector('[data-transferencia-paso="datos"]');
    const pasoComprobante = dialogo.querySelector('[data-transferencia-paso="comprobante"]');
    const selectorComprobante = dialogo.querySelector('[data-selector-comprobante]');
    const nombreComprobante = dialogo.querySelector('[data-nombre-comprobante]');
    const textoSinArchivo = nombreComprobante?.textContent || '';
    let idActivo = campoId?.value || dialogo.dataset.idPedido || '';
    let controlOrigen = null;

    function mostrarPaso(paso) {
        pasos.forEach(function (seccion) {
            seccion.hidden = seccion !== paso;
        });
        if (paso && paso.querySelector('h2')) {
            dialogo.setAttribute('aria-labelledby', paso.querySelector('h2').id);
        }
    }

    function abrir(idPedido, importe, nombreCliente, origen) {
        if (!idPedido) return false;
        if (idPedido !== idActivo && selectorComprobante) {
            selectorComprobante.value = '';
            nombreComprobante.textContent = textoSinArchivo;
        }
        idActivo = idPedido;
        controlOrigen = origen || document.querySelector('[data-abrir-transferencia]');
        mostrarPaso(pasoDatos);
        if (idPedido && campoId) campoId.value = idPedido;
        if (idPedido && referencia) referencia.textContent = (nombreCliente || '') + ' · Pedido #' + idPedido;
        if (importe && total) total.textContent = importe;
        if (!dialogo.open) dialogo.showModal();
        pasoDatos?.querySelector('h2').focus();
        return true;
    }

    document.querySelectorAll('[data-abrir-transferencia]').forEach(function (boton) {
        boton.addEventListener('click', function (evento) {
            if (evento.ctrlKey || evento.metaKey || evento.shiftKey || evento.altKey || evento.button !== 0) return;
            if (abrir(boton.getAttribute('data-id-pedido'), boton.getAttribute('data-total-pedido'), boton.getAttribute('data-nombre-cliente'), boton)) {
                evento.preventDefault();
            }
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
    if (volverADatos) volverADatos.addEventListener('click', function () {
        mostrarPaso(pasoDatos);
        irAComprobante?.focus();
    });

    const cerrar = dialogo.querySelector('[data-cerrar-transferencia]');
    if (cerrar) cerrar.addEventListener('click', function () { dialogo.close(); });

    dialogo.addEventListener('close', function () {
        mostrarPaso(pasoDatos);
        if (controlOrigen?.isConnected) controlOrigen.focus();
    });

    if (dialogo.getAttribute('data-auto-abrir') === 'true') {
        abrir(campoId ? campoId.value : dialogo.dataset.idPedido, total ? total.textContent : '', nombre);
    }

    if (selectorComprobante && nombreComprobante) {
        selectorComprobante.addEventListener('change', function () {
            nombreComprobante.textContent = selectorComprobante.files.length
                ? selectorComprobante.files[0].name
                : 'Elegí una imagen para adjuntar';
        });
    }
}());
