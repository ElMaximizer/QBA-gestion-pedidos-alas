(() => {
    'use strict';

    const panel = document.querySelector('[data-carrito-panel]');
    if (!panel || typeof panel.showModal !== 'function' || !window.fetch) return;

    const contenido = panel.querySelector('[data-carrito-contenido]');
    const estado = panel.querySelector('[data-carrito-estado]');
    const error = panel.querySelector('[data-carrito-error]');
    const cerrar = panel.querySelector('[data-carrito-cerrar]');
    let ocupado = false;
    let origen;

    const abrir = () => {
        if (panel.open) return;
        origen = document.activeElement;
        panel.showModal();
        document.body.classList.add('pagina--carrito-abierto');
    };

    const cargar = async (formulario = null) => {
        if (ocupado) return;
        ocupado = true;
        const accion = formulario?.elements.namedItem('accion')?.value;
        const producto = formulario?.elements.namedItem('id_producto')?.value;
        const conservarFoco = formulario && panel.contains(formulario);
        const botones = formulario ? [...formulario.querySelectorAll('button')] : [];
        botones.forEach((boton) => { boton.disabled = true; });
        contenido.inert = true;
        contenido.setAttribute('aria-busy', 'true');
        estado.textContent = formulario ? 'Actualizando tu carrito…' : 'Cargando tu carrito…';
        error.hidden = true;

        try {
            const respuesta = await fetch(panel.dataset.carritoUrl, {
                method: formulario ? 'POST' : 'GET',
                body: formulario ? new FormData(formulario) : undefined,
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                cache: 'no-store',
            });
            if (![200, 422].includes(respuesta.status)
                || !respuesta.headers.get('Content-Type')?.includes('application/json')) {
                throw new Error('Respuesta no disponible');
            }
            const datos = await respuesta.json();
            if (typeof datos.html !== 'string' || !Number.isInteger(datos.unidades)) {
                throw new Error('Carrito no disponible');
            }
            contenido.innerHTML = datos.html;
            const pagina = document.querySelector('[data-carrito-pagina]');
            if (pagina) pagina.innerHTML = datos.html;
            document.querySelectorAll('.carrito__cantidad').forEach((contador) => {
                contador.textContent = String(datos.unidades);
            });
            document.querySelectorAll('[data-carrito-form] input[name="csrf"]').forEach((entrada) => {
                entrada.value = datos.csrf;
            });
            const mensajes = { agregar: 'Producto agregado al carrito.', actualizar: 'Cantidad actualizada.',
                quitar: 'Producto quitado del carrito.', vaciar: 'Carrito vacío.' };
            estado.textContent = datos.error ? 'Revisá el aviso del carrito.' : (mensajes[accion] || 'Carrito actualizado.');
            contenido.inert = false;
            if (conservarFoco && panel.open) {
                const nuevo = [...contenido.querySelectorAll('[data-carrito-form]')].find((form) =>
                    form.elements.namedItem('accion')?.value === accion
                    && form.elements.namedItem('id_producto')?.value === producto);
                (nuevo?.querySelector('input[name="cantidad"], button')
                    || contenido.querySelector('button, a') || cerrar).focus();
            }
        } catch {
            estado.textContent = '';
            error.hidden = false;
        } finally {
            ocupado = false;
            contenido.inert = false;
            contenido.removeAttribute('aria-busy');
            botones.forEach((boton) => { boton.disabled = false; });
        }
    };

    document.querySelectorAll('[data-carrito-abrir]').forEach((enlace) => {
        enlace.setAttribute('aria-haspopup', 'dialog');
        enlace.setAttribute('aria-controls', panel.id);
    });

    document.addEventListener('click', (evento) => {
        const enlace = evento.target.closest('[data-carrito-abrir]');
        if (!enlace || evento.button !== 0 || evento.ctrlKey || evento.metaKey || evento.shiftKey || evento.altKey) return;
        evento.preventDefault();
        abrir();
        cargar();
    });

    document.addEventListener('submit', (evento) => {
        const formulario = evento.target.closest('[data-carrito-form]');
        if (!formulario) return;
        evento.preventDefault();
        if (ocupado) return;
        abrir();
        cargar(formulario);
    });

    cerrar.addEventListener('click', () => panel.close());
    panel.querySelector('[data-carrito-reintentar]').addEventListener('click', () => cargar());
    panel.addEventListener('click', (evento) => {
        if (evento.target !== panel) return;
        const limites = panel.getBoundingClientRect();
        if (evento.clientX < limites.left || evento.clientX > limites.right
            || evento.clientY < limites.top || evento.clientY > limites.bottom) panel.close();
    });
    panel.addEventListener('close', () => {
        document.body.classList.remove('pagina--carrito-abierto');
        (origen?.isConnected ? origen : document.querySelector('[data-carrito-abrir]'))?.focus();
    });
})();
