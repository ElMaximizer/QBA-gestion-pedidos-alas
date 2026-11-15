<main id="contenido" class="contenedor pagina-pedidos">
    <a class="enlace-volver" href="<?= $escapar($url('cliente-pedidos')) ?>">Volver a mis pedidos</a>
    <h1 class="titulo-pagina__titulo">Transferencia del pedido #<?= (int) $idPedidoDialogo ?></h1>
    <div class="transferencia-dialogo transferencia-dialogo--pagina">
        <?php
        $esPaginaTransferencia = true;
        require __DIR__ . '/partials/transferencia-contenido.php';
        ?>
    </div>
</main>
