function iniciarVistaPreviaProducto() {
    const campoArchivo = document.querySelector('[data-archivo-producto]');
    const vistaPrevia = document.querySelector('[data-imagen-producto]');
    const campoCategoria = document.querySelector('[data-categoria-producto]');

    if (!campoArchivo || !vistaPrevia || !campoCategoria) {
        return;
    }

    const imagenInicial = vistaPrevia.src;
    const tiposPermitidos = ['image/jpeg', 'image/png'];
    const maximoBytes = 4 * 1024 * 1024;
    let direccionTemporal;

    function actualizarIlustracionCategoria() {
        const hayArchivo = campoArchivo.files && campoArchivo.files.length > 0;
        const usaIlustracion = /\/(pan|croissant|torta)\.svg$/.test(vistaPrevia.src);

        if (hayArchivo || !usaIlustracion) {
            return;
        }

        let ilustracion = 'pan.svg';

        if (campoCategoria.value === 'Bizcochos') {
            ilustracion = 'croissant.svg';
        } else if (['Tortas', 'Especiales'].includes(campoCategoria.value)) {
            ilustracion = 'torta.svg';
        }

        vistaPrevia.src = vistaPrevia.src.replace(/[^/]+$/, ilustracion);
    }

    function liberarImagenTemporal() {
        // La vista previa ocupa memoria hasta que se libera su dirección.
        if (direccionTemporal) {
            URL.revokeObjectURL(direccionTemporal);
        }
    }

    campoArchivo.addEventListener('change', function () {
        campoArchivo.setCustomValidity('');
        liberarImagenTemporal();
        vistaPrevia.src = imagenInicial;

        const imagen = campoArchivo.files && campoArchivo.files[0];

        if (!imagen) {
            actualizarIlustracionCategoria();
            return;
        }

        if (!tiposPermitidos.includes(imagen.type) || imagen.size > maximoBytes) {
            campoArchivo.setCustomValidity('Elegí una imagen JPG o PNG de hasta 4 MB.');
            campoArchivo.reportValidity();
            return;
        }

        direccionTemporal = URL.createObjectURL(imagen);
        vistaPrevia.src = direccionTemporal;
    });

    campoCategoria.addEventListener('change', actualizarIlustracionCategoria);
    window.addEventListener('pagehide', liberarImagenTemporal);
}

iniciarVistaPreviaProducto();
