function iniciarNavegacion() {
    'use strict';

    const encabezado = document.querySelector('[data-menu-encabezado]');

    if (!encabezado) {
        return;
    }

    const botonMenu = encabezado.querySelector('[data-menu-boton]');
    const panelMenu = encabezado.querySelector('[data-menu-panel]');
    const pantallaCompacta = window.matchMedia('(max-width: 1000px)');
    let menuAbierto = false;

    function actualizarMenu(mostrar, devolverFoco = false) {
        menuAbierto = pantallaCompacta.matches && mostrar;
        botonMenu.hidden = !pantallaCompacta.matches;
        panelMenu.hidden = pantallaCompacta.matches && !menuAbierto;

        botonMenu.setAttribute('aria-expanded', String(menuAbierto));
        botonMenu.setAttribute(
            'aria-label',
            menuAbierto ? 'Cerrar menú de navegación' : 'Abrir menú de navegación',
        );
        encabezado.classList.toggle('encabezado--menu-abierto', menuAbierto);

        if (panelMenu.hidden) {
            for (const detalle of panelMenu.querySelectorAll('details[open]')) {
                detalle.open = false;
            }
        }

        if (devolverFoco) {
            let destinoFoco = panelMenu.querySelector('a');

            if (pantallaCompacta.matches) {
                destinoFoco = botonMenu;
            }

            if (destinoFoco) {
                destinoFoco.focus();
            }
        }
    }

    botonMenu.addEventListener('click', function () {
        actualizarMenu(!menuAbierto);
    });

    panelMenu.addEventListener('click', function (evento) {
        const enlace = evento.target.closest('a');
        const usaModificador = evento.ctrlKey || evento.metaKey || evento.shiftKey || evento.altKey;

        if (menuAbierto && enlace && evento.button === 0 && !usaModificador) {
            // El carrito puede abrir un diálogo: debe poder volver a un control visible.
            actualizarMenu(false, true);
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape' && menuAbierto) {
            evento.preventDefault();
            actualizarMenu(false, true);
        }
    });

    document.addEventListener('click', function (evento) {
        if (menuAbierto && !encabezado.contains(evento.target)) {
            const focoDentroDelMenu = panelMenu.contains(document.activeElement);
            actualizarMenu(false, focoDentroDelMenu);
        }
    });

    document.addEventListener('focusin', function (evento) {
        if (menuAbierto && !encabezado.contains(evento.target)) {
            actualizarMenu(false);
        }
    });

    pantallaCompacta.addEventListener('change', function () {
        const focoEnMenu = panelMenu.contains(document.activeElement);
        const focoEnBoton = document.activeElement === botonMenu;
        const devolverFoco =
            (pantallaCompacta.matches && focoEnMenu) || (!pantallaCompacta.matches && focoEnBoton);

        actualizarMenu(false, devolverFoco);
    });

    actualizarMenu(false);
}

iniciarNavegacion();
