(() => {
    const carrusel = document.querySelector('[data-carrusel]');
    if (!carrusel) return;
    const diapositivas = Array.from(carrusel.querySelectorAll('[data-diapositiva]'));
    const indicadores = Array.from(carrusel.querySelectorAll('[data-ir-a]'));
    if (diapositivas.length < 2) return;

    const movimientoReducido = window.matchMedia('(prefers-reduced-motion: reduce)');
    let actual = 0;
    let temporizador = null;

    const mostrar = indice => {
        actual = (indice + diapositivas.length) % diapositivas.length;
        diapositivas.forEach((diapositiva, posicion) => {
            const activa = posicion === actual;
            diapositiva.classList.toggle('portada__diapositiva--activa', activa);
            diapositiva.setAttribute('aria-hidden', activa ? 'false' : 'true');
            diapositiva.tabIndex = activa ? 0 : -1;
        });
        indicadores.forEach((indicador, posicion) => {
            const activo = posicion === actual;
            indicador.classList.toggle('portada__indicador--activo', activo);
            indicador.setAttribute('aria-current', activo ? 'true' : 'false');
        });
    };

    const detener = () => {
        if (temporizador !== null) window.clearInterval(temporizador);
        temporizador = null;
    };
    const iniciar = () => {
        detener();
        if (!movimientoReducido.matches && !document.hidden) {
            temporizador = window.setInterval(() => mostrar(actual + 1), 1000);
        }
    };

    carrusel.querySelector('[data-anterior]')?.addEventListener('click', () => { mostrar(actual - 1); iniciar(); });
    carrusel.querySelector('[data-siguiente]')?.addEventListener('click', () => { mostrar(actual + 1); iniciar(); });
    indicadores.forEach(indicador => indicador.addEventListener('click', () => {
        mostrar(Number(indicador.dataset.irA));
        iniciar();
    }));
    carrusel.addEventListener('mouseenter', detener);
    carrusel.addEventListener('mouseleave', iniciar);
    carrusel.addEventListener('focusin', detener);
    carrusel.addEventListener('focusout', iniciar);
    document.addEventListener('visibilitychange', iniciar);
    movimientoReducido.addEventListener?.('change', iniciar);
    iniciar();
})();
