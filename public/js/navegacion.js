(() => {
    'use strict';

    const encabezado = document.querySelector('[data-menu-encabezado]');
    if (!encabezado) return;
    const boton = encabezado.querySelector('[data-menu-boton]');
    const menu = encabezado.querySelector('[data-menu-panel]');
    const compacto = window.matchMedia('(max-width: 1000px)');
    let abierto = false;

    const actualizar = (mostrar, devolverFoco = false) => {
        abierto = compacto.matches && mostrar;
        boton.hidden = !compacto.matches;
        menu.hidden = compacto.matches && !abierto;
        boton.setAttribute('aria-expanded', String(abierto));
        boton.setAttribute('aria-label', abierto ? 'Cerrar menú de navegación' : 'Abrir menú de navegación');
        encabezado.classList.toggle('encabezado--menu-abierto', abierto);
        if (menu.hidden) menu.querySelectorAll('details[open]').forEach((detalle) => { detalle.open = false; });
        if (devolverFoco) {
            (compacto.matches ? boton : menu.querySelector('a'))?.focus();
        }
    };

    boton.addEventListener('click', () => actualizar(!abierto));
    menu.addEventListener('click', (evento) => {
        const enlace = evento.target.closest('a');
        if (abierto && enlace && evento.button === 0 && !evento.ctrlKey && !evento.metaKey && !evento.shiftKey && !evento.altKey) {
            // El carrito puede abrir un diálogo: al cerrarlo, el foco vuelve a un control visible.
            actualizar(false, true);
        }
    });
    document.addEventListener('keydown', (evento) => {
        if (evento.key !== 'Escape' || !abierto) return;
        evento.preventDefault();
        actualizar(false, true);
    });
    document.addEventListener('click', (evento) => {
        if (abierto && !encabezado.contains(evento.target)) actualizar(false, menu.contains(document.activeElement));
    });
    document.addEventListener('focusin', (evento) => {
        if (abierto && !encabezado.contains(evento.target)) actualizar(false);
    });
    compacto.addEventListener('change', () => {
        const focoEnMenu = menu.contains(document.activeElement);
        const focoEnBoton = document.activeElement === boton;
        actualizar(false, (compacto.matches && focoEnMenu) || (!compacto.matches && focoEnBoton));
    });
    actualizar(false);
})();
