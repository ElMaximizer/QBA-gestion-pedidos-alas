<main id="contenido" class="contenedor panel pedido-equipo">
    <div class="titulo-pagina">
        <span class="etiqueta">ADMINISTRACIÓN · PEDIDOS</span>
        <h1 class="titulo-pagina__titulo"><?= $esEdicion ? 'Modificar pedido #' . $pedido['id_pedido'] : 'Registrar pedido' ?></h1>
        <p class="titulo-pagina__descripcion"><?= $esEdicion ? 'Consultá el detalle y corregí la información del pedido. Cada modificación se conserva en el historial.' : 'Completá los datos del cliente y revisá el pedido antes de guardarlo.' ?></p>
    </div>
    <a class="enlace-volver" href="<?= $url('equipo-pedidos') ?>">← Volver a pedidos</a>
    <?php if ($error !== ''): ?>
    <div class="aviso aviso--error" role="alert" tabindex="-1" data-pedido-error><?= $escapar($error) ?></div>
    <?php endif; ?>
    <?php if (!$productos): ?>
    <section class="tarjeta vacio pedido-equipo__vacio">
        <h2 class="vacio__titulo">No hay productos disponibles</h2>
        <p class="vacio__descripcion">Agregá o activá productos del catálogo para registrar un pedido.</p>
        <a class="boton boton--dorado" href="<?= $url('equipo-productos') ?>">Administrar productos</a>
    </section>
    <?php else: ?>
    <?php if ($esEdicion && in_array($pedido['estado'], ['Entregado', 'Cancelado'], true)): ?>
    <p class="recuadro pedido-equipo__ayuda">Este pedido figura como <strong><?= $escapar($pedido['estado']) ?></strong>. Podés corregirlo como dueño; los datos anteriores quedarán guardados.</p>
    <?php endif; ?>
    <form class="pedido-equipo__formulario" method="post" action="<?= $escapar($url($esEdicion ? 'equipo-pedido-editar' : 'equipo-pedido-registrar', $esEdicion ? ['id' => $pedido['id_pedido']] : [])) ?>"
        data-pedido-equipo data-maximo="<?= $maximoProductos ?>" data-cantidad-maxima="<?= $maximaCantidad ?>">
        <input type="hidden" name="csrf" value="<?= $escapar($csrf) ?>">
        <input type="hidden" name="pedido_token" value="<?= $escapar($token) ?>">
        <section class="tarjeta pila pedido-equipo__datos" aria-labelledby="pedido-cliente">
            <h2 class="pedido-equipo__titulo" id="pedido-cliente">1. Cliente y retiro</h2>
            <p class="pedido-equipo__ayuda"><?= $esEdicion
                ? ($pedido['id_cliente'] === null ? 'Este pedido no tiene una cuenta de cliente asociada.' : 'La cuenta del cliente asociada al pedido se conserva al editar.')
                : 'El cliente no necesita tener una cuenta para un pedido tomado por el equipo.' ?></p>
            <label class="campo">Nombre del cliente
                <input class="campo__entrada" name="cliente_nombre" maxlength="120" required autocomplete="off"
                    value="<?= $escapar($valores['cliente_nombre']) ?>" placeholder="Nombre y apellido">
            </label>
            <label class="campo">Teléfono
                <input class="campo__entrada" type="tel" name="telefono" maxlength="30" required autocomplete="off"
                    value="<?= $escapar($valores['telefono']) ?>" placeholder="Número de contacto" aria-describedby="pedido-telefono-ayuda">
                <span class="campo__ayuda" id="pedido-telefono-ayuda">Para coordinar el retiro.</span>
            </label>
            <div class="pedido-equipo__retiro">
                <label class="campo">Fecha de retiro
                    <input class="campo__entrada" type="date" name="fecha_retiro" min="<?= $esEdicion ? '1000-01-01' : $hoy ?>" required value="<?= $escapar($valores['fecha_retiro']) ?>">
                </label>
                <label class="campo">Hora de retiro
                    <input class="campo__entrada" type="time" name="hora_retiro" required value="<?= $escapar($valores['hora_retiro']) ?>">
                </label>
            </div>
            <hr class="separador pedido-equipo__separador">
            <h2 class="pedido-equipo__titulo" id="pedido-productos">2. Productos</h2>
            <p class="pedido-equipo__ayuda">Seleccioná cada producto una sola vez. El precio corresponde a la presentación indicada.</p>
            <?php if ($esEdicion): ?>
            <p class="pedido-equipo__ayuda">Los productos que ya estaban en el pedido conservan su nombre y precio originales. Los productos nuevos usan el precio actual del catálogo.</p>
            <?php endif; ?>
            <div class="pedido-equipo__lineas" data-pedido-lineas aria-labelledby="pedido-productos">
                <?php foreach ($valores['lineas'] as $indice => $linea): ?>
                <fieldset class="pedido-equipo__linea">
                    <legend class="pedido-equipo__leyenda">Producto <?= $indice + 1 ?></legend>
                    <label class="campo">Producto
                        <select class="campo__entrada" name="lineas[<?= $indice ?>][id_producto]" required data-pedido-producto>
                            <option value="">Seleccioná un producto</option>
                            <?php if ($linea['id_producto'] !== '' && !isset($precios[$linea['id_producto']])): ?>
                            <option value="<?= $escapar($linea['id_producto']) ?>" selected>Producto no disponible · elegí otro</option>
                            <?php endif; ?>
                            <?php foreach ($productos as $producto): ?>
                            <option value="<?= $escapar($producto['id_producto']) ?>" data-precio="<?= $producto['precio'] ?>" data-nombre="<?= $escapar($producto['nombre']) ?>"
                                <?= (string) $producto['id_producto'] === $linea['id_producto'] ? 'selected' : '' ?>><?= $escapar($producto['nombre'] . ' · ' . $producto['presentacion']) ?> · <?= $dinero($producto['precio']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="campo">Cantidad
                        <input class="campo__entrada" type="number" inputmode="numeric" name="lineas[<?= $indice ?>][cantidad]" min="1" max="<?= $maximaCantidad ?>" step="1" required value="<?= $escapar($linea['cantidad']) ?>" data-pedido-cantidad>
                    </label>
                    <button class="boton-texto boton-texto--rojo pedido-equipo__quitar" type="submit" name="quitar_linea" value="<?= $indice ?>"
                        formnovalidate aria-label="Quitar producto <?= $indice + 1 ?>" <?= count($valores['lineas']) === 1 ? 'disabled' : '' ?>>Quitar línea</button>
                </fieldset>
                <?php endforeach; ?>
            </div>
            <button class="boton boton--contorno" type="submit" name="accion" value="agregar-linea" formnovalidate
                <?= count($valores['lineas']) >= $maximoProductos || !$puedeConfirmar ? 'disabled' : '' ?>>+ Agregar otra línea</button>
            <label class="campo">Notas del pedido (opcional)
                <textarea class="campo__entrada campo__entrada--multilinea" name="notas" rows="4" maxlength="2000"
                    placeholder="Indicaciones para preparar el pedido"><?= $escapar($valores['notas']) ?></textarea>
            </label>
            <?php if ($esEdicion): ?>
            <label class="campo">Estado del pedido
                <select class="campo__entrada" name="estado" required data-pedido-estado>
                    <?php foreach ($estados as $opcionEstado): ?>
                    <option value="<?= $escapar($opcionEstado) ?>" <?= $valores['estado'] === $opcionEstado ? 'selected' : '' ?>><?= $escapar($opcionEstado) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="campo">Motivo de la modificación (opcional)
                <textarea class="campo__entrada campo__entrada--multilinea" name="motivo" rows="2" maxlength="500"
                    placeholder="Explicá la corrección para dejarla en el historial"><?= $escapar($valores['motivo']) ?></textarea>
            </label>
            <?php endif; ?>
        </section>
        <aside class="tarjeta pedido-equipo__resumen" aria-labelledby="pedido-resumen">
            <h2 class="pedido-equipo__titulo" id="pedido-resumen">Resumen</h2>
            <ul class="pedido-equipo__detalle" data-pedido-resumen>
                <?php foreach ($resumen as $linea): ?>
                <li class="pedido-equipo__articulo"><span><?= $escapar($linea['nombre']) ?> × <?= $linea['cantidad'] ?></span><strong><?= $dinero($linea['subtotal']) ?></strong></li>
                <?php endforeach; ?>
            </ul>
            <p class="pedido-equipo__ayuda" data-pedido-incompleto <?= $resumenCompleto ? 'hidden' : '' ?>>Completá las líneas para ver el total del pedido.</p>
            <p class="pedido-equipo__total">Total <strong data-pedido-total aria-live="polite" aria-atomic="true"><?= $resumenCompleto ? $dinero($total) : '—' ?></strong></p>
            <p class="pedido-equipo__ayuda" data-pedido-retiro>Retiro · <?= $escapar($valores['fecha_retiro']) ?> · <?= $escapar($valores['hora_retiro']) ?></p>
            <span class="insignia insignia--neutra" data-pedido-estado-resumen><?= $esEdicion ? 'Estado: ' . $escapar($valores['estado']) : 'Estado inicial: Pendiente' ?></span>
            <div class="pedido-equipo__acciones">
                <button class="boton boton--dorado boton--completo" type="submit" name="accion" value="registrar" <?= !$puedeConfirmar ? 'disabled' : '' ?>><?= $esEdicion ? 'Guardar cambios' : 'Registrar pedido' ?></button>
                <?php if (!$puedeConfirmar): ?>
                <a class="boton boton--contorno boton--completo" href="<?= $escapar($url($esEdicion ? 'equipo-pedido-editar' : 'equipo-pedido-registrar', $esEdicion ? ['id' => $pedido['id_pedido']] : [])) ?>"><?= $esEdicion ? 'Volver a abrir el pedido' : 'Abrir un nuevo pedido' ?></a>
                <?php endif; ?>
                <a class="boton boton--contorno boton--completo" href="<?= $url('equipo-pedidos') ?>">Volver sin guardar</a>
            </div>
            <p class="pedido-equipo__ayuda"><?= $esEdicion ? 'Los cambios se aplicarán al pedido #' . $pedido['id_pedido'] . '.' : 'El número de pedido se asigna automáticamente al guardar.' ?></p>
            <noscript><p class="pedido-equipo__ayuda">Podés agregar o quitar líneas para actualizar el resumen antes de registrar.</p></noscript>
        </aside>
    </form>
    <?php endif; ?>
    <?php if ($esEdicion): require __DIR__ . '/partials/pedido-historial.php'; endif; ?>
</main>
