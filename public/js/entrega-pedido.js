function iniciarEntregaPedido() {
    'use strict';
    const formulario = document.querySelector('[data-entrega-pedido]');
    const seccionDireccion = document.querySelector('[data-direccion-entrega]');

    if (!formulario || !seccionDireccion) {
        return;
    }

    const campoDireccion = seccionDireccion.querySelector('[data-campo-direccion]');
    const opciones = formulario.querySelectorAll('input[name="modalidad_entrega"]');
    const direccionGuardada = formulario.querySelector('[data-direccion-guardada]');
    let direccionManual = direccionGuardada && direccionGuardada.value !== '' ? '' : campoDireccion.value;

    function actualizarDireccion() {
        const seleccionada = direccionGuardada && direccionGuardada.selectedOptions[0];
        const usaGuardada = Boolean(seleccionada && direccionGuardada.value !== '');
        campoDireccion.readOnly = usaGuardada;
        campoDireccion.value = usaGuardada ? seleccionada.dataset.direccion : direccionManual;
    }

    function actualizarModalidad() {
        const seleccionada = formulario.querySelector('input[name="modalidad_entrega"]:checked');
        const esDomicilio = seleccionada && seleccionada.value === 'domicilio';
        seccionDireccion.hidden = !esDomicilio;
        campoDireccion.required = Boolean(esDomicilio && (!direccionGuardada || direccionGuardada.value === ''));
        campoDireccion.disabled = !esDomicilio;
        if (direccionGuardada) {
            direccionGuardada.disabled = !esDomicilio;
        }
    }

    for (const opcion of opciones) {
        opcion.addEventListener('change', actualizarModalidad);
    }

    campoDireccion.addEventListener('input', function () {
        if (!campoDireccion.readOnly) {
            direccionManual = campoDireccion.value;
        }
    });
    if (direccionGuardada) {
        direccionGuardada.addEventListener('change', function () {
            actualizarDireccion();
            actualizarModalidad();
        });
    }

    actualizarDireccion();
    actualizarModalidad();
}

iniciarEntregaPedido();
