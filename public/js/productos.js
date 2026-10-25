(() => {
    const archivo = document.querySelector('[data-archivo-producto]');
    const vista = document.querySelector('[data-imagen-producto]');
    const categoria = document.querySelector('[data-categoria-producto]');
    if (!archivo || !vista || !categoria) return;

    const imagenInicial = vista.src;
    let temporal;
    const actualizarCategoria = () => {
        if (archivo.files?.length || !/\/(pan|croissant|torta)\.svg$/.test(vista.src)) return;
        const ilustracion = categoria.value === 'Bizcochos' ? 'croissant.svg'
            : ['Tortas', 'Especiales'].includes(categoria.value) ? 'torta.svg' : 'pan.svg';
        vista.src = vista.src.replace(/[^/]+$/, ilustracion);
    };

    archivo.addEventListener('change', () => {
        archivo.setCustomValidity('');
        if (temporal) URL.revokeObjectURL(temporal);
        vista.src = imagenInicial;
        const imagen = archivo.files?.[0];
        if (!imagen) {
            actualizarCategoria();
            return;
        }
        if (!['image/jpeg', 'image/png'].includes(imagen.type) || imagen.size > 4 * 1024 * 1024) {
            archivo.setCustomValidity('Elegí una imagen JPG o PNG de hasta 4 MB.');
            archivo.reportValidity();
            return;
        }
        temporal = URL.createObjectURL(imagen);
        vista.src = temporal;
    });
    categoria.addEventListener('change', actualizarCategoria);
    window.addEventListener('pagehide', () => { if (temporal) URL.revokeObjectURL(temporal); });
})();
