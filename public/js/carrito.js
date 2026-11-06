function iniciarPanelCarrito() {
    'use strict';

    const panel = document.querySelector('[data-carrito-panel]');

    if (!panel || typeof panel.showModal !== 'function' || !window.fetch) {
        return;
    }

    const contenido = panel.querySelector('[data-carrito-contenido]');
    const textoEstado = panel.querySelector('[data-carrito-estado]');
    const avisoError = panel.querySelector('[data-carrito-error]');
    const botonCerrar = panel.querySelector('[data-carrito-cerrar]');
    let cargando = false;
    let elementoOrigen;

    function abrirPanel() {
        if (panel.open) {
            return;
        }

        elementoOrigen = document.activeElement;
        panel.showModal();
        document.body.classList.add('pagina--carrito-abierto');
    }

    function valorCampo(formulario, nombre) {
        if (!formulario) {
            return undefined;
        }

        const campo = formulario.elements.namedItem(nombre);

        if (campo) {
            return campo.value;
        }

        return undefined;
    }

    function actualizarPagina(datos) {
        contenido.innerHTML = datos.html;

        const carritoEnPagina = document.querySelector('[data-carrito-pagina]');

        if (carritoEnPagina) {
            carritoEnPagina.innerHTML = datos.html;
        }

        for (const contador of document.querySelectorAll('.carrito__cantidad')) {
            contador.textContent = String(datos.unidades);
        }

        for (const campo of document.querySelectorAll('[data-carrito-form] input[name="csrf"]')) {
            campo.value = datos.csrf;
        }
    }

    function recuperarFoco(accion, idProducto) {
        // El servidor reemplaza el contenido: buscamos el control equivalente en el HTML nuevo.
        let destinoFoco;

        for (const formulario of contenido.querySelectorAll('[data-carrito-form]')) {
            const mismaAccion = valorCampo(formulario, 'accion') === accion;
            const mismoProducto = valorCampo(formulario, 'id_producto') === idProducto;

            if (mismaAccion && mismoProducto) {
                destinoFoco = formulario.querySelector('input[name="cantidad"], button');
                break;
            }
        }

        if (!destinoFoco) {
            destinoFoco = contenido.querySelector('button, a') || botonCerrar;
        }

        destinoFoco.focus();
    }

    async function cargarCarrito(formulario = null) {
        if (cargando) {
            return;
        }

        cargando = true;

        const accion = valorCampo(formulario, 'accion');
        const idProducto = valorCampo(formulario, 'id_producto');
        const conservarFoco = formulario && panel.contains(formulario);
        let botones = [];

        if (formulario) {
            botones = Array.from(formulario.querySelectorAll('button'));
        }

        for (const boton of botones) {
            boton.disabled = true;
        }

        contenido.inert = true;
        contenido.setAttribute('aria-busy', 'true');
        textoEstado.textContent = formulario ? 'Actualizando tu carrito…' : 'Cargando tu carrito…';
        avisoError.hidden = true;

        try {
            const respuesta = await fetch(panel.dataset.carritoUrl, {
                method: formulario ? 'POST' : 'GET',
                body: formulario ? new FormData(formulario) : undefined,
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                cache: 'no-store',
            });

            const tipoContenido = respuesta.headers.get('Content-Type');
            const esJson = tipoContenido && tipoContenido.includes('application/json');
            const estadoAceptado = [200, 422].includes(respuesta.status);

            if (!estadoAceptado || !esJson) {
                throw new Error('Respuesta no disponible');
            }

            const datos = await respuesta.json();

            if (typeof datos.html !== 'string' || !Number.isInteger(datos.unidades)) {
                throw new Error('Carrito no disponible');
            }

            actualizarPagina(datos);

            const mensajes = {
                agregar: 'Producto agregado al carrito.',
                actualizar: 'Cantidad actualizada.',
                quitar: 'Producto quitado del carrito.',
                vaciar: 'Carrito vacío.',
            };

            textoEstado.textContent = mensajes[accion] || 'Carrito actualizado.';

            if (datos.error) {
                textoEstado.textContent = 'Revisá el aviso del carrito.';
            }

            contenido.inert = false;

            if (conservarFoco && panel.open) {
                recuperarFoco(accion, idProducto);
            }
        } catch {
            textoEstado.textContent = '';
            avisoError.hidden = false;
        } finally {
            cargando = false;
            contenido.inert = false;
            contenido.removeAttribute('aria-busy');

            for (const boton of botones) {
                boton.disabled = false;
            }
        }
    }

    for (const enlace of document.querySelectorAll('[data-carrito-abrir]')) {
        enlace.setAttribute('aria-haspopup', 'dialog');
        enlace.setAttribute('aria-controls', panel.id);
    }

    document.addEventListener('click', function (evento) {
        const enlace = evento.target.closest('[data-carrito-abrir]');
        const usaModificador = evento.ctrlKey || evento.metaKey || evento.shiftKey || evento.altKey;

        if (!enlace || evento.button !== 0 || usaModificador) {
            return;
        }

        evento.preventDefault();
        abrirPanel();
        cargarCarrito();
    });

    document.addEventListener('submit', function (evento) {
        const formulario = evento.target.closest('[data-carrito-form]');

        if (!formulario) {
            return;
        }

        evento.preventDefault();

        if (cargando) {
            return;
        }

        abrirPanel();
        cargarCarrito(formulario);
    });

    botonCerrar.addEventListener('click', function () {
        panel.close();
    });

    panel.querySelector('[data-carrito-reintentar]').addEventListener('click', function () {
        cargarCarrito();
    });

    panel.addEventListener('click', function (evento) {
        if (evento.target !== panel) {
            return;
        }

        const limites = panel.getBoundingClientRect();
        const clicFuera =
            evento.clientX < limites.left ||
            evento.clientX > limites.right ||
            evento.clientY < limites.top ||
            evento.clientY > limites.bottom;

        if (clicFuera) {
            panel.close();
        }
    });

    panel.addEventListener('close', function () {
        document.body.classList.remove('pagina--carrito-abierto');

        let destinoFoco = document.querySelector('[data-carrito-abrir]');

        if (elementoOrigen && elementoOrigen.isConnected) {
            destinoFoco = elementoOrigen;
        }

        if (destinoFoco) {
            destinoFoco.focus();
        }
    });
}

iniciarPanelCarrito();
