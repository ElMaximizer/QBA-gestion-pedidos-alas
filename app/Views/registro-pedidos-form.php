<?php
$esc = static fn ($valor) => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');

$datos = $datos ?? [];
$datos['cliente_nombre'] = $datos['cliente_nombre'] ?? '';
$datos['telefono'] = $datos['telefono'] ?? '';
$datos['fecha_retiro'] = $datos['fecha_retiro'] ?? date('Y-m-d', strtotime('+1 day'));
$datos['hora_retiro'] = $datos['hora_retiro'] ?? '10:00';
$datos['notas'] = $datos['notas'] ?? '';
?>  
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Pedidos(empleados)</title>
</head>
<body>
<main class="pedido-admin">
    <p>ADMINISTRACIÓN</p>
    <h1 class="pedido-admin__titulo">Agregar pedido</h1>
    <p class="pedido-admin__subtitulo">
        Completa los datos y revisa la información antes de guardar.
    </p>

    <a class="pedido-admin__volver" href="<?= $url('admin-pedidos') ?>">
        ← Volver a pedidos
    </a>

    <?php if (!empty($error)): ?>
        <p class="pedido-admin__error" role="alert"><?= $esc($error) ?></p>
    <?php endif; ?>

    <form id="formulario-pedido" method="post"
          action="<?= $url('admin-pedido-nuevo') ?>">
        <input type="hidden" name="csrf" value="<?= $esc($csrf) ?>">

        <div class="pedido-admin__grid">
            <section class="pedido-admin__tarjeta">
                <h2>1. Cliente y retiro</h2>

                <label class="pedido-admin__campo">
                    Nombre del cliente *
                    <input name="cliente_nombre" maxlength="120" required
                           value="<?= $esc($datos['cliente_nombre']) ?>"
                           placeholder="Nombre y apellido">
                </label>

                <label class="pedido-admin__campo">
                    Teléfono *
                    <input name="telefono" type="tel" maxlength="30" required
                           value="<?= $esc($datos['telefono']) ?>"
                           placeholder="Número de contacto">
                </label>

                <label class="pedido-admin__campo">
                    Fecha de retiro *
                    <input name="fecha_retiro" type="date" required
                           min="<?= date('Y-m-d') ?>"
                           value="<?= $esc($datos['fecha_retiro']) ?>">
                </label>

                <label class="pedido-admin__campo">
                    Hora de retiro *
                    <input name="hora_retiro" type="time" required
                           value="<?= $esc($datos['hora_retiro']) ?>">
                </label>

                <hr>
                <h2>2. Productos</h2>

                <div id="lineas-productos"></div>

                <template id="plantilla-producto">
                    <div class="pedido-admin__linea">
                        <label class="pedido-admin__campo">
                            Producto *
                            <select name="producto_id[]" class="producto" required>
                                <option value="">Selecciona un producto</option>
                                <?php foreach ($productos as $producto): ?>
                                    <option
                                        value="<?= (int) $producto['id_producto'] ?>"
                                        data-price="<?= (int) $producto['precio'] ?>">
                                        <?= $esc($producto['nombre']) ?>
                                        · $ <?= number_format(
                                            $producto['precio'] / 100,
                                            0,
                                            ',',
                                            '.'
                                        ) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label class="pedido-admin__campo">
                            Cantidad *
                            <input name="cantidad[]" class="cantidad"
                                   type="number" min="1" max="99"
                                   value="1" required>
                        </label>

                        <button type="button"
                                class="pedido-admin__boton pedido-admin__boton--quitar quitar-linea">
                            Quitar línea
                        </button>
                    </div>
                </template>

                <button type="button" id="agregar-linea"
                        class="pedido-admin__boton">
                    + Agregar otra línea
                </button>

                <label class="pedido-admin__campo">
                    Notas del pedido
                    <textarea name="notas" maxlength="2000"
                              placeholder="Indicaciones para preparar el pedido"><?= $esc($datos['notas']) ?></textarea>
                </label>
                <small>Opcional.</small>
            </section>

            <aside class="pedido-admin__tarjeta">
                <h2>Resumen</h2>
                <div id="resumen-productos">
                    <p>Agregá productos para ver el resumen.</p>
                </div>

                <hr>
                <p class="pedido-admin__total">
                    Total <span id="total-pedido">$ 0</span>
                </p>
                <p id="resumen-retiro"></p>

                <button type="submit"
                        class="pedido-admin__boton pedido-admin__boton--dorado">
                    Registrar pedido
                </button>

                <p>
                    <a class="pedido-admin__boton"
                       href="<?= $url('admin-pedidos') ?>">
                        Volver sin guardar
                    </a>
                </p>
                <small>El número de pedido se asigna automáticamente.</small>
            </aside>
        </div>
    </form>
</main>
<footer>
    <a class="pedido-admin__boton" href="/QBA-gestion-pedidos-alas/public/index.php?ruta=admin-pedidos">
    ← Volver a pedidos
</a>
</footer>
</body>
</html>
<script>
const contenedor = document.getElementById('lineas-productos');
const plantilla = document.getElementById('plantilla-producto');

function agregarLinea() {
    contenedor.appendChild(plantilla.content.cloneNode(true));
    actualizarResumen();
}

document.getElementById('agregar-linea')
    .addEventListener('click', agregarLinea);

contenedor.addEventListener('click', (evento) => {
    if (evento.target.matches('.quitar-linea')) {
        evento.target.closest('.pedido-admin__linea').remove();
        actualizarResumen();
    }
});

contenedor.addEventListener('input', actualizarResumen);
contenedor.addEventListener('change', actualizarResumen);

function actualizarResumen() {
    const lineas = contenedor.querySelectorAll('.pedido-admin__linea');
    const resumen = document.getElementById('resumen-productos');
    let total = 0;
    let contenido = '';

    lineas.forEach((linea) => {
        const select = linea.querySelector('.producto');
        const cantidad = Number(linea.querySelector('.cantidad').value) || 0;
        const opcion = select.selectedOptions[0];

        if (select.value && cantidad > 0) {
            const precio = Number(opcion.dataset.price) || 0;
            const subtotal = precio * cantidad;
            total += subtotal;

            contenido += `
                <div class="pedido-admin__resumen-linea">
                    <span>${opcion.textContent.trim()} × ${cantidad}</span>
                    <strong>${formatearDinero(subtotal)}</strong>
                </div>`;
        }
    });

    resumen.innerHTML = contenido || '<p>Agregá productos para ver el resumen.</p>';
    document.getElementById('total-pedido').textContent = formatearDinero(total);

    const fecha = document.querySelector('[name="fecha_retiro"]').value;
    const hora = document.querySelector('[name="hora_retiro"]').value;
    document.getElementById('resumen-retiro').textContent =
        fecha ? `Retiro · ${fecha}${hora ? ', ' + hora : ''}` : '';
}

function formatearDinero(centavos) {
    const pesos = Math.round(centavos / 100);
    return '$ ' + new Intl.NumberFormat('es-UY').format(pesos);
}

document.getElementById('formulario-pedido').addEventListener('submit', (evento) => {
    if (contenedor.querySelectorAll('.pedido-admin__linea').length === 0) {
        evento.preventDefault();
        alert('Agregá al menos un producto al pedido.');
    }
});

document.querySelector('[name="fecha_retiro"]').addEventListener('change', actualizarResumen);
document.querySelector('[name="hora_retiro"]').addEventListener('change', actualizarResumen);

agregarLinea();
</script>

