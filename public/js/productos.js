function iniciarVistaPreviaProducto() {
    const campoArchivo = document.querySelector('[data-archivo-producto]');
    const vistaPrevia = document.querySelector('[data-imagen-producto]');
    const campoCategoria = document.querySelector('[data-categoria-producto]');
    const nombreArchivo = document.querySelector('[data-nombre-imagen]');
    const errorArchivo = document.querySelector('[data-error-imagen]');

    if (!campoArchivo || !vistaPrevia || !campoCategoria || !nombreArchivo || !errorArchivo) {
        return;
    }

    campoArchivo.closest('.producto-imagen__selector').classList.add('producto-imagen__selector--interactivo');
    nombreArchivo.hidden = false;

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
            direccionTemporal = undefined;
        }
    }

    campoArchivo.addEventListener('change', function () {
        campoArchivo.setCustomValidity('');
        campoArchivo.removeAttribute('aria-invalid');
        errorArchivo.hidden = true;
        errorArchivo.textContent = '';
        nombreArchivo.textContent = 'Sin archivo nuevo seleccionado.';
        liberarImagenTemporal();
        vistaPrevia.src = imagenInicial;

        const imagen = campoArchivo.files && campoArchivo.files[0];

        if (!imagen) {
            actualizarIlustracionCategoria();
            return;
        }

        if (!tiposPermitidos.includes(imagen.type) || imagen.size < 1 || imagen.size > maximoBytes) {
            const mensaje = 'Elegí una imagen JPG o PNG de hasta 4 MB.';
            campoArchivo.setCustomValidity(mensaje);
            campoArchivo.setAttribute('aria-invalid', 'true');
            errorArchivo.textContent = mensaje;
            errorArchivo.hidden = false;
            campoArchivo.reportValidity();
            return;
        }

        direccionTemporal = URL.createObjectURL(imagen);
        vistaPrevia.src = direccionTemporal;
        nombreArchivo.textContent = `Imagen seleccionada: ${imagen.name}. Se guardará junto con el producto.`;
    });

    campoCategoria.addEventListener('change', actualizarIlustracionCategoria);
    window.addEventListener('pagehide', liberarImagenTemporal);
}

function iniciarInteraccionesGestionProductos() {
    if (!document.querySelector('.gestion-productos')) return;
    const desplegables = document.querySelectorAll('.gestion-producto__cambio-estado');
    for (const desplegable of desplegables) {
        desplegable.addEventListener('toggle', function () {
            if (!desplegable.open) return;
            for (const otro of desplegables) {
                if (otro !== desplegable) otro.open = false;
            }
        });
        desplegable.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape' && desplegable.open) {
                evento.preventDefault();
                desplegable.open = false;
                desplegable.querySelector('summary').focus();
            }
        });
    }
    const formularios = document.querySelectorAll('[data-gestion-guardar]');
    const textos = new Map();
    for (const formulario of formularios) {
        const boton = formulario.querySelector('button');
        textos.set(boton, boton.textContent);
        formulario.addEventListener('submit', function (evento) {
            if (evento.defaultPrevented) return;
            if (formulario.dataset.enviando === 'true') {
                evento.preventDefault();
                return;
            }
            formulario.dataset.enviando = 'true';
            formulario.setAttribute('aria-busy', 'true');
            boton.disabled = true;
            boton.textContent = formulario.dataset.gestionGuardar;
        });
    }
    window.addEventListener('pageshow', function () {
        for (const formulario of formularios) {
            const boton = formulario.querySelector('button');
            delete formulario.dataset.enviando;
            formulario.removeAttribute('aria-busy');
            boton.disabled = false;
            boton.textContent = textos.get(boton);
        }
    });
    const aviso = document.querySelector('[data-aviso-productos][role="alert"]')
        || document.querySelector('[data-aviso-productos][role="status"]');
    aviso?.focus();
}

iniciarVistaPreviaProducto();
iniciarInteraccionesGestionProductos();
