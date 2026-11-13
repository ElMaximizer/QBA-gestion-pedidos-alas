function iniciarCarruselInicio() {
    'use strict';

    const carrusel = document.querySelector('[data-carrusel]');

    if (!carrusel) {
        return;
    }

    const diapositivas = Array.from(carrusel.querySelectorAll('[data-diapositiva]'));

    if (diapositivas.length < 2) {
        return;
    }

    const contador = carrusel.querySelector('[data-contador]');
    const botonPausa = carrusel.querySelector('[data-pausa]');
    const movimientoReducido = window.matchMedia('(prefers-reduced-motion: reduce)');
    const intervaloMilisegundos = 6000;
    let indiceActual = 0;
    let pausado = movimientoReducido.matches;
    let temporizador;
    let punteroEncima = false;

    function detenerAvance() {
        clearInterval(temporizador);
        temporizador = undefined;
        contador.setAttribute('aria-live', 'polite');
    }

    function iniciarAvance() {
        detenerAvance();

        const focoEnCarrusel = carrusel.contains(document.activeElement);

        if (!pausado && !punteroEncima && !document.hidden && !focoEnCarrusel) {
            contador.setAttribute('aria-live', 'off');
            temporizador = setInterval(function () {
                mostrarDiapositiva(indiceActual + 1);
            }, intervaloMilisegundos);
        }
    }

    function mostrarDiapositiva(indice) {
        // Después del último producto volvemos al primero; al retroceder hacemos lo inverso.
        indiceActual = (indice + diapositivas.length) % diapositivas.length;

        for (let posicion = 0; posicion < diapositivas.length; posicion++) {
            diapositivas[posicion].hidden = posicion !== indiceActual;
        }

        contador.textContent = `${indiceActual + 1} / ${diapositivas.length}`;
    }

    function actualizarPausa() {
        botonPausa.textContent = pausado ? 'Reanudar' : 'Pausar';
        iniciarAvance();
    }

    carrusel.querySelector('[data-controles-carrusel]').hidden = false;

    carrusel.querySelector('[data-anterior]').addEventListener('click', function () {
        detenerAvance();
        mostrarDiapositiva(indiceActual - 1);
        iniciarAvance();
    });

    carrusel.querySelector('[data-siguiente]').addEventListener('click', function () {
        detenerAvance();
        mostrarDiapositiva(indiceActual + 1);
        iniciarAvance();
    });

    botonPausa.addEventListener('click', function () {
        pausado = !pausado;
        actualizarPausa();
    });

    carrusel.addEventListener('mouseenter', function () {
        punteroEncima = true;
        detenerAvance();
    });

    carrusel.addEventListener('mouseleave', function () {
        punteroEncima = false;
        iniciarAvance();
    });

    carrusel.addEventListener('focusin', detenerAvance);
    carrusel.addEventListener('focusout', function (evento) {
        if (!carrusel.contains(evento.relatedTarget)) {
            // Esperamos a que termine el cambio de foco antes de decidir si vuelve a avanzar.
            queueMicrotask(iniciarAvance);
        }
    });

    document.addEventListener('visibilitychange', iniciarAvance);
    movimientoReducido.addEventListener('change', function () {
        pausado = movimientoReducido.matches;
        actualizarPausa();
    });
    window.addEventListener('pagehide', detenerAvance);
    window.addEventListener('pageshow', iniciarAvance);

    actualizarPausa();
}

iniciarCarruselInicio();
