(() => {
    'use strict';
    const carrusel = document.querySelector('[data-carrusel]');
    if (!carrusel) return;
    const diapositivas = [...carrusel.querySelectorAll('[data-diapositiva]')];
    if (diapositivas.length < 2) return;
    const contador = carrusel.querySelector('[data-contador]');
    const pausa = carrusel.querySelector('[data-pausa]');
    const movimientoReducido = window.matchMedia('(prefers-reduced-motion: reduce)');
    let actual = 0;
    let pausado = movimientoReducido.matches;
    let temporizador;
    let punteroEncima = false;

    const detener = () => {
        clearInterval(temporizador);
        temporizador = undefined;
        contador.setAttribute('aria-live', 'polite');
    };
    const iniciar = () => {
        detener();
        if (!pausado && !punteroEncima && !document.hidden && !carrusel.contains(document.activeElement)) {
            contador.setAttribute('aria-live', 'off');
            temporizador = setInterval(() => mostrar(actual + 1), 6000);
        }
    };
    const mostrar = (indice) => {
        actual = (indice + diapositivas.length) % diapositivas.length;
        diapositivas.forEach((diapositiva, posicion) => { diapositiva.hidden = posicion !== actual; });
        contador.textContent = `${actual + 1} / ${diapositivas.length}`;
    };
    const cambiarPausa = () => { pausa.textContent = pausado ? 'Reanudar' : 'Pausar'; iniciar(); };

    carrusel.querySelector('[data-controles-carrusel]').hidden = false;
    carrusel.querySelector('[data-anterior]').addEventListener('click', () => { detener(); mostrar(actual - 1); iniciar(); });
    carrusel.querySelector('[data-siguiente]').addEventListener('click', () => { detener(); mostrar(actual + 1); iniciar(); });
    pausa.addEventListener('click', () => { pausado = !pausado; cambiarPausa(); });
    carrusel.addEventListener('mouseenter', () => { punteroEncima = true; detener(); });
    carrusel.addEventListener('mouseleave', () => { punteroEncima = false; iniciar(); });
    carrusel.addEventListener('focusin', detener);
    carrusel.addEventListener('focusout', (evento) => { if (!carrusel.contains(evento.relatedTarget)) queueMicrotask(iniciar); });
    document.addEventListener('visibilitychange', iniciar);
    movimientoReducido.addEventListener('change', () => { pausado = movimientoReducido.matches; cambiarPausa(); });
    window.addEventListener('pagehide', detener);
    window.addEventListener('pageshow', iniciar);
    cambiarPausa();
})();
