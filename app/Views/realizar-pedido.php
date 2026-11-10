<main id="contenido" class="contenedor pagina-pedidos">
    <a class="enlace-volver" href="<?= $url('carrito') ?>">← Volver al carrito</a>
    <div class="titulo-pagina">
        <h1 class="titulo-pagina__titulo">Realizar pedido</h1>
        <p class="titulo-pagina__descripcion">Completá tus datos y elegí cómo querés recibir tu pedido.</p>
    </div>
    <form method="post" action="<?= $url('realizar-pedido') ?>" class="pedido-distribucion" data-entrega-pedido>
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="pedido_token" value="<?= $escapar($token) ?>">
        <div class="tarjeta pila">
            <h2 class="tarjeta__titulo">Contacto y entrega</h2>
            <?php if ($error): ?>
                <div class="aviso aviso--error" role="alert"><?= $escapar($error) ?> </div>
            <?php endif; ?>
            <label class="campo">
                <span class="campo-obligatorio__etiqueta">Nombre del cliente <span class="campo-obligatorio"
                        aria-hidden="true" title="Campo obligatorio">*</span></span>
                <input class="campo__entrada" name="cliente_nombre" value="<?= $escapar($datos['cliente_nombre']) ?>"
                    maxlength="120" required autocomplete="name">
            </label>
            <label class="campo">
                <span class="campo-obligatorio__etiqueta">Teléfono <span class="campo-obligatorio" aria-hidden="true"
                        title="Campo obligatorio">*</span></span>
                <input class="campo__entrada" type="tel" name="telefono" value="<?= $escapar($datos['telefono']) ?>"
                    maxlength="30" required autocomplete="tel">
            </label>
            <fieldset class="entrega-opciones">
                <legend class="entrega-opciones__titulo">¿Cómo querés recibir tu pedido?</legend>
                <label class="entrega-opciones__opcion">
                    <input type="radio" name="modalidad_entrega" value="retiro"
                        <?= ($datos['modalidad_entrega'] ?? 'retiro') === 'retiro' ? 'checked' : '' ?> required>
                    <span><strong>Retiro en el local</strong><small>Coordinamos el horario contigo.</small></span>
                </label>
                <label class="entrega-opciones__opcion">
                    <input type="radio" name="modalidad_entrega" value="domicilio"
                        <?= ($datos['modalidad_entrega'] ?? '') === 'domicilio' ? 'checked' : '' ?> required>
                    <span><strong>Envío a domicilio</strong><small>Sin costo adicional.</small></span>
                </label>
            </fieldset>
            <div data-direccion-entrega>
                <label class="campo">
                    <span class="campo-obligatorio__etiqueta">Dirección de entrega <span class="campo-obligatorio"
                            aria-hidden="true" title="Campo obligatorio">*</span></span>
                    <input class="campo__entrada" name="direccion_entrega"
                        value="<?= $escapar($datos['direccion_entrega'] ?? '') ?>" maxlength="250"
                        autocomplete="street-address" data-campo-direccion>
                </label>
            </div>
            <label class="campo">
                <span class="campo-obligatorio__etiqueta">Fecha de retiro o entrega <span class="campo-obligatorio"
                        aria-hidden="true" title="Campo obligatorio">*</span></span>
                <input class="campo__entrada" type="date" name="fecha_retiro"
                    value="<?= $escapar($datos['fecha_retiro']) ?>" min="<?= date('Y-m-d') ?>" required>
            </label>
            <label class="campo">
                <span class="campo-obligatorio__etiqueta">Hora de retiro o entrega <span class="campo-obligatorio"
                        aria-hidden="true" title="Campo obligatorio">*</span></span>
                <input class="campo__entrada" type="time" name="hora_retiro"
                    value="<?= $escapar($datos['hora_retiro']) ?>" required>
            </label>
            <fieldset class="entrega-opciones">
                <legend class="entrega-opciones__titulo">Forma de pago</legend>
                <label class="entrega-opciones__opcion">
                    <input type="radio" name="metodo_pago" value="transferencia"
                        <?= ($datos['metodo_pago'] ?? 'local') === 'transferencia' ? 'checked' : '' ?> required>
                    <span><strong>Transferencia por el total</strong><small>El equipo coordinará los datos y confirmará la recepción del pago.</small></span>
                </label>
                <label class="entrega-opciones__opcion">
                    <input type="radio" name="metodo_pago" value="local"
                        <?= ($datos['metodo_pago'] ?? 'local') === 'local' ? 'checked' : '' ?> required>
                    <span><strong>Pagar en el local</strong><small>El pago queda pendiente hasta que el equipo lo registre.</small></span>
                </label>
            </fieldset>
            <label class="campo">Notas del pedido <textarea class="campo__entrada campo__entrada--multilinea" name="notas" rows="4"
                    maxlength="2000"><?= $escapar($datos['notas']) ?></textarea>
            </label>
        </div>
        <aside class="tarjeta resumen">
            <h2 class="resumen__titulo">Resumen</h2>
            <?php foreach ($articulos as $articulo): ?>
                <div class="resumen__linea">
                    <span><?= $escapar($articulo['nombre']) ?> × <?= $articulo['cantidad'] ?></span><strong><?= $dinero($articulo['subtotal']) ?></strong>
                </div>
            <?php endforeach; ?>
            <hr class="separador">
            <h2 class="resumen__titulo">Total <?= $dinero($total) ?></h2>
            <?php if ($puedeConfirmar): ?>
                <button class="boton boton--dorado boton--completo">Confirmar pedido</button>
            <?php else: ?>
                <a class="boton boton--dorado boton--completo" href="<?= $url('carrito') ?>">Revisar carrito</a>
            <?php endif; ?>
            <p class="texto-pequeno resumen__texto">El envío a domicilio es gratuito. La transferencia se coordina fuera del sitio y queda pendiente hasta que el equipo confirme su recepción.</p>
        </aside>
    </form>
</main>
