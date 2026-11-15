<?php
$transferenciaConfigurada = \App\Core\Transferencia::configurada($transferencia ?? []);
$esPaginaTransferencia = $esPaginaTransferencia ?? false;
?>
    <?php if ($transferenciaConfigurada): ?>
        <section class="transferencia-dialogo__contenido" data-transferencia-paso="datos">
            <span class="etiqueta">PAGO POR TRANSFERENCIA</span>
            <h2 class="transferencia-dialogo__titulo" id="transferencia-dialogo-titulo" tabindex="-1">Datos para realizar el pago</h2>
            <p class="transferencia-dialogo__texto">Transferí el total del pedido a esta cuenta. En la descripción, escribí el nombre con el que quedó el pedido y su número.</p>
            <dl class="transferencia-dialogo__datos">
                <div><dt>Banco</dt><dd><?= $escapar($transferencia['banco']) ?></dd></div>
                <div><dt>Titular</dt><dd><?= $escapar($transferencia['titular']) ?></dd></div>
                <div><dt>Tipo de cuenta</dt><dd><?= $escapar($transferencia['tipo_cuenta']) ?></dd></div>
                <div><dt>Número de cuenta</dt><dd><?= $escapar($transferencia['numero_cuenta']) ?></dd></div>
                <?php if (($transferencia['alias'] ?? '') !== ''): ?><div><dt>Alias</dt><dd><?= $escapar($transferencia['alias']) ?></dd></div><?php endif; ?>
                <div><dt>Moneda</dt><dd><?= $escapar($transferencia['moneda']) ?></dd></div>
            </dl>
            <p class="transferencia-dialogo__total">Total a transferir: <strong data-transferencia-total><?= $dinero((int) ($totalPedidoDialogo ?? 0), true) ?></strong></p>
            <p class="transferencia-dialogo__referencia">Descripción: <strong data-transferencia-referencia><?= $escapar((string) ($nombrePedidoDialogo ?? '')) ?> · Pedido #<?= (int) ($idPedidoDialogo ?? 0) ?></strong></p>
            <p class="transferencia-dialogo__nota">Cuando completes la transferencia, adjuntá el comprobante. El pago quedará pendiente hasta que el equipo lo revise.</p>
            <?php if (!$esPaginaTransferencia): ?>
            <div class="transferencia-dialogo__acciones">
                <button class="boton boton--contorno" type="button" data-cerrar-transferencia>Volver</button>
                <button class="boton boton--dorado" type="button" data-ir-a-comprobante>Transferencia enviada</button>
            </div>
            <?php endif; ?>
        </section>

        <section class="transferencia-dialogo__contenido" data-transferencia-paso="comprobante" aria-labelledby="transferencia-comprobante-titulo" <?= !$esPaginaTransferencia ? 'hidden' : '' ?>>
            <span class="etiqueta">PAGO POR TRANSFERENCIA</span>
            <h2 class="transferencia-dialogo__titulo" id="transferencia-comprobante-titulo">Cargar comprobante</h2>
            <p class="transferencia-dialogo__texto">Adjuntá una imagen del comprobante de la transferencia del pedido.</p>
            <form class="transferencia-dialogo__formulario" method="post" enctype="multipart/form-data" action="<?= $escapar($url('cliente-transferencia-enviada')) ?>">
                <input type="hidden" name="csrf" value="<?= $escapar($csrf) ?>">
                <input type="hidden" name="id_pedido" value="<?= (int) ($idPedidoDialogo ?? 0) ?>" data-transferencia-id>
                <label class="transferencia-dialogo__archivo">Comprobante de transferencia
                    <input class="transferencia-dialogo__selector" type="file" name="comprobante" accept="image/jpeg,image/png,image/webp" required data-selector-comprobante>
                    <span class="transferencia-dialogo__carga" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false"><path d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5M5 14.5v4A1.5 1.5 0 0 0 6.5 20h11a1.5 1.5 0 0 0 1.5-1.5v-4" /></svg>
                        <span data-nombre-comprobante>Elegí una imagen para adjuntar</span>
                        <span class="transferencia-dialogo__buscar">Buscar archivo</span>
                    </span>
                </label>
                <small class="transferencia-dialogo__nota">Imagen JPG, PNG o WebP de hasta 2 MB.</small>
                <div class="transferencia-dialogo__acciones">
                    <?php if (!$esPaginaTransferencia): ?><button class="boton boton--contorno" type="button" data-volver-a-datos>Volver</button><?php endif; ?>
                    <button class="boton boton--dorado" type="submit">Enviar comprobante</button>
                </div>
            </form>
        </section>
    <?php else: ?>
        <section class="transferencia-dialogo__contenido" data-transferencia-paso="datos">
            <span class="etiqueta">PAGO POR TRANSFERENCIA</span>
            <h2 class="transferencia-dialogo__titulo" id="transferencia-dialogo-titulo" tabindex="-1">Datos para realizar el pago</h2>
            <p class="transferencia-dialogo__texto">La panadería todavía no configuró los datos bancarios. Contactá al equipo para coordinar el pago por transferencia.</p>
            <div class="transferencia-dialogo__acciones">
                <?php if (!$esPaginaTransferencia): ?><button class="boton boton--contorno" type="button" data-cerrar-transferencia>Volver</button><?php endif; ?>
                <a class="boton boton--contorno" href="<?= $escapar($url('contacto')) ?>">Consultar a la panadería</a>
            </div>
        </section>
    <?php endif; ?>
