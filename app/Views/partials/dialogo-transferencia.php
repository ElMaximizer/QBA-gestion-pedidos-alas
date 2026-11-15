<dialog class="transferencia-dialogo" data-transferencia-dialogo
    data-id-pedido="<?= (int) ($idPedidoDialogo ?? 0) ?>"
    data-auto-abrir="<?= !empty($autoAbrirTransferencia) ? 'true' : 'false' ?>"
    data-nombre-pedido="<?= $escapar((string) ($nombrePedidoDialogo ?? '')) ?>"
    aria-labelledby="transferencia-dialogo-titulo">
    <?php
    $esPaginaTransferencia = false;
    require __DIR__ . '/transferencia-contenido.php';
    ?>
</dialog>
