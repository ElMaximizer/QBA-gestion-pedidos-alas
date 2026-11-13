<?php
$telefonoOpcional = $esEdicion && $pedido['id_cliente'] !== null;
$idCampo = static fn(string $campo): string => match ($campo) {
    'direccion_entrega' => 'direccion-entrega-equipo',
    'comprobante' => 'comprobante-equipo',
    default => 'pedido-campo-' . $campo,
};
$atributosCampo = static function (string $campo, string $ayuda = '') use ($erroresCampos, $idCampo, $escapar): string {
    $descripcion = trim($ayuda . ' ' . $idCampo($campo) . '-error');
    return 'id="' . $escapar($idCampo($campo)) . '" data-pedido-campo="' . $escapar($campo)
        . '" aria-describedby="' . $escapar($descripcion) . '"'
        . (isset($erroresCampos[$campo]) ? ' aria-invalid="true"' : '');
};
$errorCampo = static function (string $campo) use ($erroresCampos, $idCampo, $escapar): string {
    return '<span class="pedido-equipo__error-campo" id="' . $escapar($idCampo($campo))
        . '-error" data-error-campo="' . $escapar($campo) . '"'
        . (isset($erroresCampos[$campo]) ? '' : ' hidden') . '>'
        . $escapar($erroresCampos[$campo] ?? '') . '</span>';
};
?>
<main id="contenido" class="contenedor panel pedido-equipo">
    <div class="titulo-pagina">
        <span class="etiqueta"><?= $esEdicion ? 'ADMINISTRACIÓN' : 'ATENCIÓN DEL EQUIPO' ?> · PEDIDOS</span>
        <h1 class="titulo-pagina__titulo"><?= $esEdicion ? 'Modificar pedido #' . $pedido['id_pedido'] : 'Registrar pedido' ?></h1>
        <p class="titulo-pagina__descripcion"> <?= $esEdicion ? 'Consultá el detalle y corregí la información del pedido. Cada modificación se conserva en el historial.' : 'Completá los datos del cliente y revisá el pedido antes de guardarlo.' ?> </p>
    </div>
    <a class="enlace-volver" href="<?= $escapar($url('equipo-pedidos', $filtrosListado)) ?>"> Volver a pedidos</a>
    <div class="aviso aviso--error pedido-equipo__errores" role="alert" tabindex="-1" data-pedido-error
        data-error-general="<?= $errorGeneral ? 'true' : 'false' ?>" <?= $error === '' ? 'hidden' : '' ?>>
        <p data-pedido-error-texto><?= $escapar($error) ?></p>
        <ul data-pedido-errores-lista <?= !$erroresCampos ? 'hidden' : '' ?>>
            <?php foreach ($erroresCampos as $campo => $mensaje): ?>
                <li><a href="#<?= $escapar($idCampo($campo)) ?>"><?= $escapar($mensaje) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php if (!$productos): ?>
        <section class="tarjeta vacio pedido-equipo__vacio">
            <h2 class="vacio__titulo">No hay productos disponibles</h2>
            <?php if ($administraProductos): ?>
                <p class="vacio__descripcion">Agregá o activá productos del catálogo para registrar un pedido.</p>
                <a class="boton boton--dorado" href="<?= $url('equipo-productos') ?>">Administrar productos</a>
            <?php else: ?>
                <p class="vacio__descripcion">Pedile al administrador o al dueño que agregue o active los productos del catálogo.</p>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <?php if ($esEdicion && in_array($pedido['estado'], ['Entregado', 'Cancelado'], true)): ?>
            <p class="recuadro pedido-equipo__ayuda">Este pedido figura como <strong><?= $escapar($pedido['estado']) ?></strong>. Podés corregirlo como dueño; los datos anteriores quedarán guardados. </p>
        <?php endif; ?>
        <form class="pedido-equipo__formulario" method="post" enctype="multipart/form-data"
            action="<?= $escapar($url($rutaFormulario, $parametrosFormulario)) ?>"
            data-pedido-equipo data-pedido-nuevo="<?= $esEdicion ? 'false' : 'true' ?>" data-maximo="<?= $maximoProductos ?>" data-cantidad-maxima="<?= $maximaCantidad ?>">
            <input type="hidden" name="csrf" value="<?= $escapar($csrf) ?>">
            <input type="hidden" name="pedido_token" value="<?= $escapar($token) ?>">
            <section class="tarjeta pila pedido-equipo__datos" aria-labelledby="pedido-cliente">
                <h2 class="pedido-equipo__titulo" id="pedido-cliente">1. Cliente y entrega</h2>
                <p class="pedido-equipo__ayuda"> <?= $escapar($descripcionCliente) ?> </p>
                <label class="campo">
                    <span class="campo-obligatorio__etiqueta">Nombre del cliente <span class="campo-obligatorio"
                            aria-hidden="true" title="Campo obligatorio">*</span></span>
                    <input class="campo__entrada" name="cliente_nombre" maxlength="120" required autocomplete="off" <?= $atributosCampo('cliente_nombre') ?>
                        value="<?= $escapar($valores['cliente_nombre']) ?>" placeholder="Nombre y apellido">
                    <?= $errorCampo('cliente_nombre') ?>
                </label>
                <label class="campo">
                    <span class="campo-obligatorio__etiqueta">Teléfono<?= $telefonoOpcional ? ' (opcional)' : '' ?>
                        <?php if (!$telefonoOpcional): ?><span class="campo-obligatorio" aria-hidden="true" title="Campo obligatorio">*</span><?php endif; ?>
                    </span>
                    <input class="campo__entrada" type="tel" name="telefono" maxlength="30" <?= $telefonoOpcional ? '' : 'required' ?> autocomplete="off"
                        value="<?= $escapar($valores['telefono']) ?>" placeholder="Número de contacto"
                        <?= $atributosCampo('telefono', 'pedido-telefono-ayuda') ?>>
                    <span class="campo__ayuda" id="pedido-telefono-ayuda">Para coordinar el retiro o la entrega.</span>
                    <?= $errorCampo('telefono') ?>
                </label>
                <fieldset class="pedido-equipo__modalidad" data-modalidad-entrega tabindex="-1" <?= $atributosCampo('modalidad_entrega') ?>>
                    <legend class="pedido-equipo__leyenda">Modalidad de entrega</legend>
                    <label class="pedido-equipo__modalidad-opcion">
                        <input type="radio" name="modalidad_entrega" value="retiro"
                            <?= ($valores['modalidad_entrega'] ?? 'retiro') === 'retiro' ? 'checked' : '' ?> required>
                        <span>Retiro en el local</span>
                    </label>
                    <label class="pedido-equipo__modalidad-opcion">
                        <input type="radio" name="modalidad_entrega" value="domicilio"
                            <?= ($valores['modalidad_entrega'] ?? '') === 'domicilio' ? 'checked' : '' ?> required>
                        <span>Envío a domicilio</span>
                    </label>
                    <?= $errorCampo('modalidad_entrega') ?>
                </fieldset>
                <div class="campo pedido-equipo__direccion" data-direccion-envio>
                    <label for="direccion-entrega-equipo">Dirección de entrega <span class="campo-obligatorio" aria-hidden="true">*</span></label>
                    <input class="campo__entrada" name="direccion_entrega" <?= $atributosCampo('direccion_entrega') ?>
                        maxlength="250" autocomplete="street-address"
                        value="<?= $escapar($valores['direccion_entrega'] ?? '') ?>">
                    <span class="campo__ayuda">Obligatoria cuando el pedido se envía a domicilio.</span>
                    <?= $errorCampo('direccion_entrega') ?>
                </div>
                <div class="pedido-equipo__retiro">
                    <label class="campo">
                        <span class="campo-obligatorio__etiqueta"><span data-modalidad-fecha><?= ($valores['modalidad_entrega'] ?? 'retiro') === 'domicilio' ? 'Fecha de entrega' : 'Fecha de retiro' ?></span> <span class="campo-obligatorio"
                                aria-hidden="true" title="Campo obligatorio">*</span></span>
                        <input class="campo__entrada" type="date" name="fecha_retiro" <?= $atributosCampo('fecha_retiro') ?>
                            min="<?= $esEdicion ? '1000-01-01' : $hoy ?>" required
                            value="<?= $escapar($valores['fecha_retiro']) ?>">
                        <?= $errorCampo('fecha_retiro') ?>
                    </label>
                    <label class="campo">
                        <span class="campo-obligatorio__etiqueta"><span data-modalidad-hora><?= ($valores['modalidad_entrega'] ?? 'retiro') === 'domicilio' ? 'Hora de entrega' : 'Hora de retiro' ?></span> <span class="campo-obligatorio"
                                aria-hidden="true" title="Campo obligatorio">*</span></span>
                        <input class="campo__entrada" type="time" name="hora_retiro" required <?= $atributosCampo('hora_retiro') ?>
                            value="<?= $escapar($valores['hora_retiro']) ?>">
                        <?= $errorCampo('hora_retiro') ?>
                    </label>
                </div>
                <?php if (!$esEdicion): ?>
                    <fieldset class="pedido-equipo__modalidad" data-metodo-pago tabindex="-1" <?= $atributosCampo('metodo_pago') ?>>
                        <legend class="pedido-equipo__leyenda">Forma de pago</legend>
                        <label class="pedido-equipo__modalidad-opcion">
                            <input type="radio" name="metodo_pago" value="local"
                                <?= ($valores['metodo_pago'] ?? 'local') === 'local' ? 'checked' : '' ?> required>
                            <span>Efectivo en el local</span>
                        </label>
                        <label class="pedido-equipo__modalidad-opcion">
                            <input type="radio" name="metodo_pago" value="transferencia"
                                <?= ($valores['metodo_pago'] ?? '') === 'transferencia' ? 'checked' : '' ?> required>
                            <span>Transferencia bancaria</span>
                        </label>
                        <?= $errorCampo('metodo_pago') ?>
                    </fieldset>
                    <div class="pedido-equipo__comprobante" data-comprobante-equipo>
                        <div class="pedido-equipo__comprobante-intro">
                            <label class="pedido-equipo__comprobante-titulo" for="comprobante-equipo">
                                Comprobante de transferencia <span class="campo-obligatorio" aria-hidden="true">*</span>
                            </label>
                            <p>Obligatorio si elegís transferencia. Adjuntá la imagen para que el administrador o el dueño revise el pago.</p>
                        </div>
                        <input class="pedido-equipo__archivo-input" type="file" name="comprobante" <?= $atributosCampo('comprobante', 'pedido-comprobante-ayuda') ?>
                            accept="image/jpeg,image/png,image/webp">
                        <label class="pedido-equipo__archivo-zona" for="comprobante-equipo">
                            <span class="pedido-equipo__archivo-icono" aria-hidden="true">
                                <svg viewBox="0 0 24 24" focusable="false">
                                    <path d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5M5 14.5v4A1.5 1.5 0 0 0 6.5 20h11a1.5 1.5 0 0 0 1.5-1.5v-4" />
                                </svg>
                            </span>
                            <span class="pedido-equipo__archivo-texto">
                                <strong data-nombre-comprobante>Elegí la imagen del comprobante</strong>
                                <small>JPG, PNG o WebP · máximo 2 MB</small>
                            </span>
                            <span class="pedido-equipo__archivo-boton">Seleccionar archivo</span>
                        </label>
                        <?= $errorCampo('comprobante') ?>
                        <p class="pedido-equipo__comprobante-nota" id="pedido-comprobante-ayuda">JPG, PNG o WebP, hasta 2 MB. El pago quedará pendiente hasta que el equipo revise el comprobante.</p>
                    </div>
                <?php else: ?>
                    <p class="pedido-equipo__ayuda">Forma de pago: <strong><?= $valores['metodo_pago'] === 'transferencia' ? 'Transferencia bancaria' : 'Efectivo en el local' ?></strong></p>
                <?php endif; ?>
                <hr class="separador pedido-equipo__separador">
                <h2 class="pedido-equipo__titulo" id="pedido-productos">2. Productos</h2>
                <p class="pedido-equipo__ayuda">Seleccioná cada producto una sola vez. El precio corresponde a la presentación indicada.</p>
                <?php if (!$esEdicion): ?>
                    <p class="pedido-equipo__ayuda">Hasta <?= $maximoProductos ?> productos distintos, de 1 a <?= $maximaCantidad ?> unidades por producto.</p>
                <?php endif; ?>
                <?php if ($esEdicion): ?>
                    <p class="pedido-equipo__ayuda">Los productos que ya estaban en el pedido conservan su nombre y precio originales. Los productos nuevos usan el precio actual del catálogo.</p>
                <?php endif; ?>
                <div class="pedido-equipo__lineas" data-pedido-lineas aria-labelledby="pedido-productos">
                    <?php foreach ($valores['lineas'] as $indice => $linea): ?>
                        <fieldset class="pedido-equipo__linea">
                            <legend class="pedido-equipo__leyenda">Producto <?= $indice + 1 ?></legend>
                            <label class="campo">
                                <span class="campo-obligatorio__etiqueta">Producto <span class="campo-obligatorio"
                                        aria-hidden="true" title="Campo obligatorio">*</span></span>
                                <select class="campo__entrada" name="lineas[<?= $indice ?>][id_producto]" required
                                    data-pedido-producto <?= $atributosCampo('lineas-' . $indice . '-id_producto') ?>>
                                    <option value="">Seleccioná un producto</option>
                                    <?php if ($linea['id_producto'] !== '' && !isset($precios[$linea['id_producto']])): ?>
                                        <option value="<?= $escapar($linea['id_producto']) ?>" selected>Producto no disponible · elegí otro</option>
                                    <?php endif; ?>
                                    <?php foreach ($productos as $producto): ?>
                                        <option value="<?= $escapar($producto['id_producto']) ?>" data-precio="<?= $producto['precio'] ?>" data-nombre="<?= $escapar($producto['nombre']) ?>" <?= (string) $producto['id_producto'] === $linea['id_producto'] ? 'selected' : '' ?>> <?= $escapar($producto['nombre'] . ' · ' . $producto['presentacion']) ?> · <?= $dinero($producto['precio'], true) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?= $errorCampo('lineas-' . $indice . '-id_producto') ?>
                            </label>
                            <label class="campo">
                                <span class="campo-obligatorio__etiqueta">Cantidad <span class="campo-obligatorio"
                                        aria-hidden="true" title="Campo obligatorio">*</span></span>
                                <input class="campo__entrada" type="number" inputmode="numeric"
                                    name="lineas[<?= $indice ?>][cantidad]" min="1" max="<?= $maximaCantidad ?>"
                                    step="1" required value="<?= $escapar($linea['cantidad']) ?>" data-pedido-cantidad <?= $atributosCampo('lineas-' . $indice . '-cantidad') ?>>
                                <?= $errorCampo('lineas-' . $indice . '-cantidad') ?>
                            </label>
                            <button class="boton-texto boton-texto--rojo pedido-equipo__quitar" type="submit"
                                name="quitar_linea" value="<?= $indice ?>" formnovalidate
                                aria-label="Quitar producto <?= $indice + 1 ?>"
                                <?= count($valores['lineas']) === 1 ? 'disabled' : '' ?>>Quitar línea</button>
                        </fieldset>
                    <?php endforeach; ?>
                </div>
                <button class="boton boton--contorno" type="submit" name="accion" value="agregar-linea" formnovalidate
                    <?= count($valores['lineas']) >= $maximoProductos || !$puedeConfirmar ? 'disabled' : '' ?>>+ Agregar otra línea</button>
                <?php if (!$esEdicion): ?>
                    <p class="pedido-equipo__contador" role="status" data-pedido-contador><?= count($valores['lineas']) ?> de <?= $maximoProductos ?> líneas en el pedido.</p>
                <?php endif; ?>
                <label class="campo">Notas del pedido (opcional) <textarea class="campo__entrada campo__entrada--multilinea" name="notas" rows="4" maxlength="2000" <?= $atributosCampo('notas') ?>
                        placeholder="Indicaciones para preparar el pedido"><?= $escapar($valores['notas']) ?></textarea>
                    <?= $errorCampo('notas') ?>
                </label>
                <?php if ($esEdicion): ?>
                    <label class="campo">
                        <span class="campo-obligatorio__etiqueta">Estado del pedido <span class="campo-obligatorio"
                                aria-hidden="true" title="Campo obligatorio">*</span></span>
                        <select class="campo__entrada" name="estado" required data-pedido-estado <?= $atributosCampo('estado') ?>>
                            <?php foreach ($estados as $opcionEstado): ?>
                                <option value="<?= $escapar($opcionEstado) ?>" <?= $valores['estado'] === $opcionEstado ? 'selected' : '' ?>> <?= $escapar($opcionEstado) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= $errorCampo('estado') ?>
                    </label>
                    <label class="campo">Motivo de la modificación (opcional) <textarea class="campo__entrada campo__entrada--multilinea" name="motivo" rows="2"
                            maxlength="500"
                            placeholder="Explicá la corrección para dejarla en el historial" <?= $atributosCampo('motivo') ?>><?= $escapar($valores['motivo']) ?></textarea>
                        <?= $errorCampo('motivo') ?>
                    </label>
                <?php endif; ?>
            </section>
            <aside class="tarjeta pedido-equipo__resumen" aria-labelledby="pedido-resumen">
                <h2 class="pedido-equipo__titulo" id="pedido-resumen">Resumen</h2>
                <?php if (!$esEdicion): ?>
                    <dl class="pedido-equipo__confirmacion-datos">
                        <div><dt>Cliente</dt><dd data-pedido-cliente><?= $escapar($valores['cliente_nombre'] !== '' ? $valores['cliente_nombre'] : 'Completá el nombre del cliente') ?></dd></div>
                        <div><dt>Forma de pago</dt><dd data-pedido-pago-resumen><?= $valores['metodo_pago'] === 'transferencia' ? 'Transferencia pendiente de revisión' : 'Efectivo en el local' ?></dd></div>
                    </dl>
                <?php endif; ?>
                <ul class="pedido-equipo__detalle" data-pedido-resumen>
                    <?php foreach ($resumen as $linea): ?>
                        <li class="pedido-equipo__articulo">
                            <span><?= $escapar($linea['nombre']) ?> × <?= $linea['cantidad'] ?></span><strong><?= $dinero($linea['subtotal'], true) ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p class="pedido-equipo__ayuda" data-pedido-incompleto <?= $resumenCompleto ? 'hidden' : '' ?>>Completá las líneas para ver el total del pedido.</p>
                <p class="pedido-equipo__total">Total <strong data-pedido-total aria-live="polite" aria-atomic="true"><?= $resumenCompleto ? $dinero($total, true) : '—' ?></strong>
                </p>
                <p class="pedido-equipo__ayuda" data-pedido-retiro><?= $valores['modalidad_entrega'] === 'domicilio' ? 'Envío a domicilio' : 'Retiro' ?> · <?= $escapar($valores['fecha_retiro']) ?> · <?= $escapar($valores['hora_retiro']) ?></p>
                <?php if (!$esEdicion): ?>
                    <p class="pedido-equipo__ayuda">Registrar el pedido conserva el pago como pendiente.</p>
                <?php endif; ?>
                <span class="insignia insignia--neutra"
                    data-pedido-estado-resumen><?= $esEdicion ? 'Estado: ' . $escapar($valores['estado']) : 'Estado inicial: Pendiente' ?></span>
                <div class="pedido-equipo__acciones">
                    <button class="boton boton--dorado boton--completo" type="submit" name="accion" value="registrar"
                        <?= !$puedeConfirmar ? 'disabled' : '' ?>><?= $esEdicion ? 'Guardar cambios' : 'Registrar pedido' ?></button>
                    <?php if (!$puedeConfirmar): ?>
                        <a class="boton boton--contorno boton--completo"
                            href="<?= $escapar($url($rutaFormulario, $parametrosFormulario)) ?>"><?= $esEdicion ? 'Volver a abrir el pedido' : 'Abrir un nuevo pedido' ?></a>
                    <?php endif; ?>
                    <a class="boton boton--contorno boton--completo" href="<?= $escapar($url('equipo-pedidos', $filtrosListado)) ?>">Volver sin guardar</a>
                </div>
                <p class="pedido-equipo__ayuda"> <?= $esEdicion ? 'Los cambios se aplicarán al pedido #' . $pedido['id_pedido'] . '.' : 'El número de pedido se asigna automáticamente al guardar.' ?> </p>
                <noscript>
                    <p class="pedido-equipo__ayuda">Podés agregar o quitar líneas para actualizar el resumen antes de registrar.</p>
                    <?php if (!$esEdicion): ?>
                        <p class="pedido-equipo__ayuda">Si elegiste un comprobante y después agregás o quitás líneas, volvé a seleccionar el archivo antes de registrar.</p>
                    <?php endif; ?>
                </noscript>
            </aside>
        </form>
    <?php endif; ?>
    <?php if ($esEdicion): ?>
        <?php require __DIR__ . '/partials/pedido-historial.php'; ?>
    <?php endif; ?>
</main>
