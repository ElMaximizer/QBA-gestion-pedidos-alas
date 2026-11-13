function iniciarConfirmacionCancelacion() {
    const dialogo = document.querySelector('[data-dialogo-cancelacion]');
    if (!dialogo || typeof dialogo.showModal !== 'function') return;

    let formularioParaCancelar = null;
    let controlOrigen = null;
    const botonVolver = dialogo.querySelector('[data-cerrar-cancelacion]');
    const botonConfirmar = dialogo.querySelector('[data-aceptar-cancelacion]');

    dialogo.addEventListener('keydown', function (evento) {
        if (evento.key !== 'Tab') return;
        if (evento.shiftKey && document.activeElement === botonVolver) {
            evento.preventDefault();
            botonConfirmar.focus();
        } else if (!evento.shiftKey && document.activeElement === botonConfirmar) {
            evento.preventDefault();
            botonVolver.focus();
        }
    });

    document.addEventListener('submit', function (evento) {
        const formulario = evento.target.closest('form[data-confirmar-cancelacion], form[data-confirmar-estado]');
        if (!formulario || evento.defaultPrevented) return;
        if (formulario.hasAttribute('data-confirmar-estado') &&
            (formulario.elements.estado_nuevo.value !== 'Cancelado' || formulario.dataset.estadoActual === 'Cancelado')) return;
        if (formulario.dataset.confirmado === 'true') {
            delete formulario.dataset.confirmado;
            return;
        }

        evento.preventDefault();
        formularioParaCancelar = formulario;
        controlOrigen = evento.submitter || document.activeElement;
        dialogo.querySelector('#confirmacion-cancelacion-titulo').textContent =
            `¿Cancelar el pedido #${formulario.dataset.numeroPedido}?`;
        dialogo.querySelector('[data-cliente-cancelacion]').textContent =
            `${formulario.dataset.clientePedido} · Estado actual: ${formulario.dataset.estadoActual}`;
        dialogo.showModal();
        botonVolver.focus();
    });

    botonVolver.addEventListener('click', function () {
        dialogo.close();
    });
    dialogo.addEventListener('close', function () {
        formularioParaCancelar = null;
        if (controlOrigen?.isConnected && !controlOrigen.disabled) controlOrigen.focus();
        controlOrigen = null;
    });
    botonConfirmar.addEventListener('click', function () {
        if (!formularioParaCancelar) return;
        const formulario = formularioParaCancelar;
        formularioParaCancelar = null;
        let confirmacion = formulario.querySelector('[name="confirmar_cancelacion"]');
        if (!confirmacion) {
            confirmacion = document.createElement('input');
            confirmacion.type = 'hidden';
            confirmacion.name = 'confirmar_cancelacion';
            formulario.append(confirmacion);
        }
        confirmacion.value = '1';
        formulario.dataset.confirmado = 'true';
        dialogo.close();
        formulario.requestSubmit();
    });
}

function iniciarPagosEquipo() {
    const formularios = document.querySelectorAll('[data-pago-equipo]');
    if (!formularios.length) return;
    const textosOriginales = new Map();
    for (const formulario of formularios) {
        const boton = formulario.querySelector('button[type="submit"]');
        textosOriginales.set(boton, { texto: boton.textContent, etiqueta: boton.getAttribute('aria-label') });
        formulario.addEventListener('submit', function (evento) {
            if (evento.defaultPrevented) return;
            const pedido = formulario.closest('.equipo-pedido');
            if (pedido.dataset.guardandoPago === 'true') {
                evento.preventDefault();
                return;
            }
            pedido.dataset.guardandoPago = 'true';
            formulario.setAttribute('aria-busy', 'true');
            for (const otroBoton of pedido.querySelectorAll('[data-pago-equipo] button')) {
                otroBoton.disabled = true;
            }
            boton.textContent = formulario.elements.accion.value === 'registrar_pago_local'
                ? 'Registrando pago…' : 'Guardando revisión…';
            boton.setAttribute('aria-label', boton.textContent);
        });
    }
    window.addEventListener('pageshow', function () {
        for (const formulario of formularios) {
            const boton = formulario.querySelector('button[type="submit"]');
            delete formulario.closest('.equipo-pedido').dataset.guardandoPago;
            formulario.removeAttribute('aria-busy');
            boton.disabled = false;
            boton.textContent = textosOriginales.get(boton).texto;
            boton.setAttribute('aria-label', textosOriginales.get(boton).etiqueta);
        }
    });
}

function iniciarCambioEstadoPedidos() {
    const formularios = document.querySelectorAll('[data-confirmar-estado]');
    if (!formularios.length) return;

    for (const formulario of formularios) {
        const campo = formulario.elements.estado_nuevo;
        const boton = formulario.querySelector('[data-guardar-estado]');
        function actualizarAccion() {
            const guardando = formulario.dataset.enviandoEstado === 'true';
            boton.disabled = guardando || campo.value === '';
            if (guardando) {
                boton.textContent = 'Guardando estado…';
            } else if (boton.dataset.textoAvance) {
                boton.textContent = boton.dataset.textoAvance;
            } else {
                boton.textContent = campo.value ? `Cambiar a ${campo.value}` : 'Guardar nuevo estado';
            }
            boton.setAttribute('aria-label', `${boton.textContent} del pedido #${formulario.dataset.numeroPedido}`);
        }
        campo.addEventListener('change', function () {
            campo.removeAttribute('aria-invalid');
            const error = document.getElementById(`${campo.id}-error`);
            if (error) {
                error.hidden = true;
                campo.setAttribute('aria-describedby', `${campo.id}-actual ${campo.id}-ayuda`);
                boton.setAttribute('aria-describedby', `${campo.id}-actual ${campo.id}-ayuda`);
            }
            actualizarAccion();
        });
        document.addEventListener('submit', function (evento) {
            if (evento.target !== formulario || evento.defaultPrevented) return;
            if (formulario.dataset.enviandoEstado === 'true') {
                evento.preventDefault();
                return;
            }
            formulario.dataset.enviandoEstado = 'true';
            formulario.setAttribute('aria-busy', 'true');
            actualizarAccion();
        });
        window.addEventListener('pageshow', function () {
            delete formulario.dataset.enviandoEstado;
            formulario.removeAttribute('aria-busy');
            actualizarAccion();
        });
        actualizarAccion();
    }
}

function iniciarFormularioPedidoEquipo() {
    const formulario = document.querySelector('[data-pedido-equipo]');

    if (!formulario) {
        return;
    }

    const contenedorLineas = formulario.querySelector('[data-pedido-lineas]');
    const botonAgregar = formulario.querySelector('button[value="agregar-linea"]');
    const botonRegistrar = formulario.querySelector('button[value="registrar"]');
    const listaResumen = formulario.querySelector('[data-pedido-resumen]');
    const avisoIncompleto = formulario.querySelector('[data-pedido-incompleto]');
    const textoTotal = formulario.querySelector('[data-pedido-total]');
    const textoRetiro = formulario.querySelector('[data-pedido-retiro]');
    const modalidadEnvio = formulario.querySelector('[data-direccion-envio]');
    const campoDireccion = formulario.elements.direccion_entrega;
    const bloqueComprobante = formulario.querySelector('[data-comprobante-equipo]');
    const campoComprobante = formulario.elements.comprobante;
    const nombreComprobante = formulario.querySelector('[data-nombre-comprobante]');
    const textoFecha = formulario.querySelector('[data-modalidad-fecha]');
    const textoHora = formulario.querySelector('[data-modalidad-hora]');
    const maximoProductos = Number(formulario.dataset.maximo);
    const maximaCantidad = Number(formulario.dataset.cantidadMaxima);
    const avisoError = document.querySelector('[data-pedido-error]');
    const listaErrores = avisoError.querySelector('[data-pedido-errores-lista]');
    const contadorLineas = formulario.querySelector('[data-pedido-contador]');
    const clienteResumen = formulario.querySelector('[data-pedido-cliente]');
    const pagoResumen = formulario.querySelector('[data-pedido-pago-resumen]');
    const esPedidoNuevo = formulario.dataset.pedidoNuevo === 'true';
    const registroHabilitado = !botonRegistrar.disabled;
    const textoRegistrar = botonRegistrar.textContent;
    formulario.classList.add('pedido-equipo__formulario--interactivo');

    function actualizarErrores() {
        listaErrores.replaceChildren();
        for (const mensaje of formulario.querySelectorAll('[data-error-campo]:not([hidden])')) {
            const campo = formulario.querySelector(
                `[data-pedido-campo="${mensaje.dataset.errorCampo}"]`,
            );
            if (!campo || campo.closest('[hidden]')) continue;
            const item = document.createElement('li');
            const enlace = document.createElement('a');
            enlace.href = `#${campo.id}`;
            enlace.textContent = mensaje.textContent;
            item.append(enlace);
            listaErrores.append(item);
        }
        const hayErrores = listaErrores.children.length > 0;
        listaErrores.hidden = !hayErrores;
        avisoError.hidden = !hayErrores && avisoError.dataset.errorGeneral !== 'true';
        if (
            hayErrores &&
            !avisoError.querySelector('[data-pedido-error-texto]').textContent.trim()
        ) {
            avisoError.querySelector('[data-pedido-error-texto]').textContent =
                'Revisá los campos indicados antes de guardar el pedido.';
        }
    }

    function limpiarError(campo) {
        campo.removeAttribute('aria-invalid');
        const mensaje = document.getElementById(`${campo.id}-error`);
        if (mensaje) {
            mensaje.hidden = true;
            mensaje.textContent = '';
        }
    }

    formulario.addEventListener(
        'invalid',
        function (evento) {
            const campo = evento.target;
            const mensaje = document.getElementById(`${campo.id}-error`);
            if (!mensaje) return;
            mensaje.textContent = campo.validationMessage;
            mensaje.hidden = false;
            campo.setAttribute('aria-invalid', 'true');
            actualizarErrores();
        },
        true,
    );

    avisoError.addEventListener('click', function (evento) {
        const enlace = evento.target.closest('a');
        if (!enlace) return;
        const campo = document.getElementById(enlace.hash.slice(1));
        if (campo) {
            evento.preventDefault();
            campo.focus();
        }
    });

    function formatearDinero(centavos) {
        const pesos = centavos / 100;
        const decimales = centavos % 100 === 0 ? 0 : 2;

        return (
            '$ ' +
            pesos.toLocaleString('es-UY', {
                minimumFractionDigits: decimales,
                maximumFractionDigits: decimales,
            })
        );
    }

    function agregarProductoAlResumen(nombreProducto, unidades, subtotal) {
        const articulo = document.createElement('li');
        const nombre = document.createElement('span');
        const importe = document.createElement('strong');

        articulo.className = 'pedido-equipo__articulo';
        nombre.textContent = `${nombreProducto} × ${unidades}`;
        importe.textContent = formatearDinero(subtotal);
        articulo.append(nombre, importe);
        listaResumen.append(articulo);
    }

    function actualizarMetodoPago() {
        if (!bloqueComprobante || !campoComprobante) return;
        const metodo =
            formulario.querySelector('input[name="metodo_pago"]:checked')?.value || 'local';
        const requiereComprobante = metodo === 'transferencia';
        if (pagoResumen) {
            pagoResumen.textContent = requiereComprobante
                ? 'Transferencia pendiente de revisión' : 'Efectivo en el local';
        }
        bloqueComprobante.hidden = !requiereComprobante;
        campoComprobante.required = requiereComprobante;
        if (!requiereComprobante) campoComprobante.value = '';
        if (nombreComprobante && !requiereComprobante) {
            nombreComprobante.textContent = 'Elegí la imagen del comprobante';
        }
    }

    campoComprobante?.addEventListener('change', function () {
        if (nombreComprobante) {
            nombreComprobante.textContent =
                campoComprobante.files[0]?.name || 'Elegí la imagen del comprobante';
        }
    });

    function actualizarDatosRetiro() {
        if (clienteResumen) {
            clienteResumen.textContent = formulario.elements.cliente_nombre.value.trim()
                || 'Completá el nombre del cliente';
        }
        const fecha = formulario.elements.fecha_retiro.value;
        const hora = formulario.elements.hora_retiro.value;
        const modalidad =
            formulario.querySelector('input[name="modalidad_entrega"]:checked')?.value || 'retiro';
        const esDomicilio = modalidad === 'domicilio';
        let fechaVisible = 'Elegí una fecha';

        if (fecha) {
            fechaVisible = fecha.split('-').reverse().join('/');
        }

        modalidadEnvio.hidden = !esDomicilio && campoDireccion.getAttribute('aria-invalid') !== 'true';
        campoDireccion.required = esDomicilio;
        textoFecha.textContent = esDomicilio ? 'Fecha de entrega' : 'Fecha de retiro';
        textoHora.textContent = esDomicilio ? 'Hora de entrega' : 'Hora de retiro';
        textoRetiro.textContent = `${esDomicilio ? 'Envío a domicilio' : 'Retiro'} · ${fechaVisible} · ${hora || 'Elegí una hora'}`;

        const campoEstado = formulario.querySelector('[data-pedido-estado]');

        if (campoEstado) {
            const textoEstado = formulario.querySelector('[data-pedido-estado-resumen]');
            textoEstado.textContent = `Estado: ${campoEstado.value}`;
        }
    }

    function actualizarPedido() {
        listaResumen.replaceChildren();

        const filas = Array.from(contenedorLineas.children);
        const vecesSeleccionado = {};

        // Contamos primero las selecciones para avisar en todas las filas repetidas.
        for (const fila of filas) {
            const idProducto = fila.querySelector('select').value;
            vecesSeleccionado[idProducto] = (vecesSeleccionado[idProducto] || 0) + 1;
        }

        let totalCentavos = 0;
        let pedidoCompleto = true;

        for (let indice = 0; indice < filas.length; indice++) {
            const fila = filas[indice];
            const selectorProducto = fila.querySelector('select');
            const campoCantidad = fila.querySelector('input');
            const botonQuitar = fila.querySelector('button');

            fila.querySelector('legend').textContent = `Producto ${indice + 1}`;
            selectorProducto.name = `lineas[${indice}][id_producto]`;
            campoCantidad.name = `lineas[${indice}][cantidad]`;
            for (const [campo, nombre] of [
                [selectorProducto, 'id_producto'],
                [campoCantidad, 'cantidad'],
            ]) {
                const mensaje = campo.closest('label').querySelector('[data-error-campo]');
                campo.dataset.pedidoCampo = `lineas-${indice}-${nombre}`;
                campo.id = `pedido-campo-${campo.dataset.pedidoCampo}`;
                mensaje.dataset.errorCampo = campo.dataset.pedidoCampo;
                mensaje.id = `${campo.id}-error`;
                campo.setAttribute('aria-describedby', mensaje.id);
            }
            botonQuitar.value = String(indice);
            botonQuitar.disabled = filas.length === 1;
            botonQuitar.setAttribute('aria-label', `Quitar producto ${indice + 1}`);

            const idProducto = selectorProducto.value;
            const productoRepetido = idProducto !== '' && vecesSeleccionado[idProducto] > 1;
            if (selectorProducto.dataset.productoRepetido === 'true' && !productoRepetido) {
                limpiarError(selectorProducto);
            }
            selectorProducto.dataset.productoRepetido = String(productoRepetido);
            let errorProducto = '';

            if (productoRepetido) {
                errorProducto =
                    'Este producto está repetido. Ajustá su cantidad en una sola línea.';
            } else if (idProducto && !selectorProducto.selectedOptions[0]?.dataset.precio) {
                errorProducto = 'Seleccioná un producto disponible.';
            }

            selectorProducto.setCustomValidity(errorProducto);

            const opcionSeleccionada = selectorProducto.selectedOptions[0];

            if (!opcionSeleccionada || !opcionSeleccionada.dataset.precio) {
                pedidoCompleto = false;
                continue;
            }

            const precioCentavos = Number(opcionSeleccionada.dataset.precio);
            const unidades = Number(campoCantidad.value);
            const cantidadValida =
                Number.isInteger(unidades) && unidades >= 1 && unidades <= maximaCantidad;

            if (!Number.isInteger(precioCentavos) || !cantidadValida || productoRepetido) {
                pedidoCompleto = false;
                continue;
            }

            const subtotal = precioCentavos * unidades;
            totalCentavos += subtotal;
            agregarProductoAlResumen(opcionSeleccionada.dataset.nombre, unidades, subtotal);
        }

        botonAgregar.disabled = filas.length >= maximoProductos || botonRegistrar.disabled;
        if (contadorLineas) {
            const texto = `${filas.length} de ${maximoProductos} líneas en el pedido.`
                + (filas.length >= maximoProductos ? ' Llegaste al máximo permitido.' : '');
            if (contadorLineas.textContent !== texto) contadorLineas.textContent = texto;
        }
        avisoIncompleto.hidden = pedidoCompleto;
        textoTotal.textContent = pedidoCompleto ? formatearDinero(totalCentavos) : '—';
        actualizarDatosRetiro();
        actualizarMetodoPago();
        actualizarErrores();
    }

    botonAgregar.addEventListener('click', function (evento) {
        evento.preventDefault();

        if (formulario.dataset.registrando === 'true' || contenedorLineas.children.length >= maximoProductos) {
            return;
        }

        const nuevaFila = contenedorLineas.firstElementChild.cloneNode(true);
        nuevaFila.querySelector('select').value = '';
        nuevaFila.querySelector('input').value = '1';
        for (const campo of nuevaFila.querySelectorAll('[data-pedido-campo]')) {
            campo.removeAttribute('aria-invalid');
        }
        for (const mensaje of nuevaFila.querySelectorAll('[data-error-campo]')) {
            mensaje.hidden = true;
            mensaje.textContent = '';
        }
        contenedorLineas.append(nuevaFila);

        actualizarPedido();
        nuevaFila.querySelector('select').focus();
    });

    contenedorLineas.addEventListener('click', function (evento) {
        const botonQuitar = evento.target.closest('button[name="quitar_linea"]');

        if (!botonQuitar) {
            return;
        }

        evento.preventDefault();

        if (formulario.dataset.registrando === 'true' || contenedorLineas.children.length <= 1) {
            return;
        }

        const fila = botonQuitar.closest('fieldset');
        const siguiente = fila.nextElementSibling || fila.previousElementSibling;
        fila.remove();
        actualizarPedido();
        siguiente.querySelector('select').focus();
    });

    function cambiarCampo(evento) {
        const campo = evento.target.closest('[data-pedido-campo]');
        if (campo) limpiarError(campo);
        if (evento.target.name === 'fecha_retiro' || evento.target.name === 'hora_retiro') {
            limpiarError(formulario.elements.fecha_retiro);
            limpiarError(formulario.elements.hora_retiro);
        }
        actualizarPedido();
    }
    formulario.addEventListener('input', cambiarCampo);
    formulario.addEventListener('change', cambiarCampo);
    formulario.addEventListener('keydown', function (evento) {
        // Enter registra el pedido; los botones para agregar o quitar requieren un clic.
        if (evento.key === 'Enter' && evento.target.matches('input:not([type="file"])')) {
            evento.preventDefault();
            formulario.requestSubmit(botonRegistrar);
        }
    });

    if (esPedidoNuevo) {
        formulario.addEventListener('submit', function (evento) {
            if (evento.defaultPrevented) return;
            if (formulario.dataset.registrando === 'true') {
                evento.preventDefault();
                return;
            }
            if (evento.submitter !== botonRegistrar || !registroHabilitado) return;
            formulario.dataset.registrando = 'true';
            formulario.setAttribute('aria-busy', 'true');
            // El botón envía accion=registrar y debe conservarse habilitado.
            botonRegistrar.setAttribute('aria-disabled', 'true');
            botonRegistrar.textContent = 'Registrando pedido…';
        });
        window.addEventListener('pageshow', function () {
            delete formulario.dataset.registrando;
            formulario.removeAttribute('aria-busy');
            botonRegistrar.removeAttribute('aria-disabled');
            botonRegistrar.textContent = textoRegistrar;
            actualizarPedido();
        });
    }

    actualizarPedido();

    if (!avisoError.hidden) {
        avisoError.focus();
    }
}

iniciarFormularioPedidoEquipo();
iniciarConfirmacionCancelacion();
iniciarPagosEquipo();
iniciarCambioEstadoPedidos();
const resultadoPedidos = document.querySelector('[data-resultado-pedidos][role="alert"]')
    || document.querySelector('[data-resultado-pedidos][role="status"]');
resultadoPedidos?.focus();
