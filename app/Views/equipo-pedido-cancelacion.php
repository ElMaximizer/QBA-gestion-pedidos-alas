<main id="contenido" class="contenedor panel">
    <section class="confirmacion-cancelacion confirmacion-cancelacion--pagina" aria-labelledby="cancelar-pedido-titulo">
        <div class="confirmacion-cancelacion__contenido">
            <span class="etiqueta">CANCELAR PEDIDO</span>
            <h1 class="confirmacion-cancelacion__titulo" id="cancelar-pedido-titulo">¿Cancelar el pedido #<?= (int) $pedido['id_pedido'] ?>?</h1>
            <p class="confirmacion-cancelacion__cliente"><?= $escapar($pedido['cliente_nombre']) ?> · Estado actual: <?= $escapar($pedido['estado']) ?></p>
            <p class="confirmacion-cancelacion__mensaje">El pedido quedará como Cancelado. Sus datos y su historial se conservan.</p>
            <form method="post" action="<?= $escapar($url('equipo-pedidos', $filtrosListado)) ?>">
                <input type="hidden" name="csrf" value="<?= $escapar($csrf) ?>">
                <input type="hidden" name="accion" value="<?= $escapar($accion) ?>">
                <input type="hidden" name="id_pedido" value="<?= (int) $pedido['id_pedido'] ?>">
                <input type="hidden" name="confirmar_cancelacion" value="1">
                <?php if ($accion === 'actualizar_estado'): ?>
                    <input type="hidden" name="estado_nuevo" value="Cancelado">
                <?php endif; ?>
                <div class="confirmacion-cancelacion__acciones">
                    <a class="boton boton--contorno" href="<?= $escapar($url('equipo-pedidos', $filtrosListado)) ?>">Volver</a>
                    <button class="boton confirmacion-cancelacion__confirmar" type="submit">Sí, cancelar pedido</button>
                </div>
            </form>
        </div>
    </section>
</main>
