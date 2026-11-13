<main id="contenido" class="contenedor pagina-pedidos">
    <section class="tarjeta vacio">
        <span class="insignia insignia--exito">Pedido registrado</span>
        <h1 class="vacio__titulo">¡Gracias por tu pedido!</h1>
        <p class="vacio__descripcion vacio__descripcion--principal">Tu número es <strong>#<?= $idPedido ?></strong>. El equipo se comunicará para coordinar la preparación y la entrega o el retiro.</p>
        <?php if ($metodoPago === 'transferencia'): ?>
            <p class="vacio__descripcion">Elegiste transferencia por el total. Informá el envío desde la ventana de instrucciones; el pago quedará pendiente hasta que el equipo confirme la recepción.</p>
            <button class="boton boton--contorno" type="button" data-abrir-transferencia data-id-pedido="<?= (int) $idPedido ?>">Ver instrucciones de transferencia</button>
        <?php else: ?>
            <p class="vacio__descripcion">Elegiste pagar en el local. El equipo coordinará contigo el pedido.</p>
        <?php endif; ?>
        <div class="acciones vacio__acciones">
            <?php if ($clienteActual !== null): ?>
                <a class="boton boton--dorado" href="<?= $url('cliente-pedidos') ?>">Ver mis pedidos</a>
            <?php endif; ?>
            <a class="boton boton--dorado" href="<?= $url('productos') ?>">Seguir explorando</a>
            <a class="boton boton--contorno" href="<?= $url('inicio') ?>">Volver al inicio</a>
        </div>
    </section>
</main>
<?php
$idPedidoDialogo = (int) $idPedido;
$totalPedidoDialogo = (int) ($totalPedidoCentavos ?? 0);
$autoAbrirTransferencia = $metodoPago === 'transferencia';
require __DIR__ . '/partials/dialogo-transferencia.php';
?>
