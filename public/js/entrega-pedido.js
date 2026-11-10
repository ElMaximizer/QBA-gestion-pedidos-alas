(function () {
    const formulario = document.querySelector('[data-entrega-pedido]');
    const seccionDireccion = document.querySelector('[data-direccion-entrega]');

    if (!formulario || !seccionDireccion) {
        return;
    }

    const campoDireccion = seccionDireccion.querySelector('[data-campo-direccion]');
    const opciones = formulario.querySelectorAll('input[name="modalidad_entrega"]');

    function actualizarModalidad() {
        const seleccionada = formulario.querySelector('input[name="modalidad_entrega"]:checked');
        const esDomicilio = seleccionada && seleccionada.value === 'domicilio';
        seccionDireccion.hidden = !esDomicilio;
        campoDireccion.required = Boolean(esDomicilio);
        campoDireccion.disabled = !esDomicilio;
    }

    opciones.forEach(function (opcion) {
        opcion.addEventListener('change', actualizarModalidad);
    });

    actualizarModalidad();
}());