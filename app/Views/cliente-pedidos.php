<main id="contenido" class="contenedor cliente-pedidos">
    <?php if (($avisoTransferencia ?? '') !== ''): ?>
        <p class="aviso aviso--exito" role="status"><?= $escapar($avisoTransferencia) ?></p>
    <?php endif; ?>
    <?php if (($errorTransferencia ?? '') !== ''): ?>
        <p class="aviso aviso--error" role="alert"><?= $escapar($errorTransferencia) ?></p>
    <?php endif; ?>
    <header class="cliente-pedidos__encabezado">
        <span class="etiqueta">TU CUENTA</span>
        <h1>Mis pedidos</h1>
        <p>Consultá el detalle y el estado de tus pedidos actuales y anteriores.</p>
    </header>

    <?php if ($pedidos === []): ?>
        <section class="tarjeta vacio cliente-pedidos__vacio">
            <h2 class="vacio__titulo">Todavía no tenés pedidos</h2>
            <p class="vacio__descripcion">Cuando confirmes un pedido, vas a poder seguirlo desde acá.</p>
            <a class="boton boton--dorado" href="<?= $url('productos') ?>">Ver productos</a>
        </section>
    <?php else: ?>
        <p class="cliente-pedidos__cantidad"><?= count($pedidos) ?> <?= count($pedidos) === 1 ? 'pedido' : 'pedidos' ?></p>
        <div class="cliente-pedidos__lista">
            <?php foreach ($pedidos as $pedido): ?>
                <?php
                $estadoClase = $pedido['estado'] === 'Entregado'
                    ? 'insignia--exito'
                    : ($pedido['estado'] === 'Cancelado' ? 'insignia--alerta' : 'insignia--neutra');
                ?>
                <article class="tarjeta cliente-pedidos__tarjeta">
                    <div class="cliente-pedidos__titulo">
                        <div>
                            <span class="etiqueta">PEDIDO #<?= $pedido['id_pedido'] ?></span>
                            <h2><?= $pedido['modalidad_entrega'] === 'domicilio' ? 'Entrega a domicilio' : 'Retiro en el local' ?>: <?= $escapar(date('d/m/Y', strtotime($pedido['fecha_retiro']))) ?> · <?= $escapar($pedido['hora_retiro']) ?></h2>
                        </div>
                        <span class="insignia <?= $estadoClase ?>"><?= $escapar($pedido['estado']) ?></span>
                    </div>
                    <?php if ($pedido['modalidad_entrega'] === 'domicilio' && trim((string) $pedido['direccion_entrega']) !== ''): ?>
                        <p class="cliente-pedidos__entrega"><strong>Dirección:</strong> <?= $escapar($pedido['direccion_entrega']) ?></p>
                    <?php endif; ?>
                    <?php
                    $estadoRevisionTransferencia = $pedido['estado_pago'] === 'pagado'
                        ? 'aprobada'
                        : ($pedido['estado_revision_transferencia'] ?? null);
                    $estadoPagoCliente = $pedido['metodo_pago'] === 'transferencia'
                        ? ($estadoRevisionTransferencia === 'aprobada'
                            ? 'Aprobada por el equipo'
                            : ($estadoRevisionTransferencia === 'rechazada'
                                ? 'Rechazada: podés volver a enviar el comprobante'
                                : (!empty($pedido['transferencia_enviada_en'])
                                    ? ($estadoRevisionTransferencia === 'pendiente'
                                        ? 'Pendiente de aprobación del equipo'
                                        : 'Comprobante pendiente de procesar')
                                    : 'Pendiente')))
                        : ($pedido['estado_pago'] === 'pagado' ? 'Pagado' : 'Pendiente');
                    ?>
                    <p class="cliente-pedidos__pago"><strong>Pago:</strong> <?= $pedido['metodo_pago'] === 'transferencia' ? 'Transferencia' : 'Pago en el local' ?> · <?= $escapar($estadoPagoCliente) ?></p>
                    <?php if ($pedido['metodo_pago'] === 'transferencia' && $pedido['estado_pago'] !== 'pagado'
                        && (empty($pedido['transferencia_enviada_en'])
                            || $estadoRevisionTransferencia === null
                            || $estadoRevisionTransferencia === 'rechazada')
                        && $pedido['estado'] !== 'Cancelado'): ?>
                        <div class="cliente-pedidos__transferencia">
                            <button class="boton boton--contorno" type="button" data-abrir-transferencia data-id-pedido="<?= (int) $pedido['id_pedido'] ?>" data-total-pedido="<?= $escapar($dinero($pedido['total_centavos'])) ?>" data-nombre-cliente="<?= $escapar($pedido['cliente_nombre']) ?>">Informar transferencia enviada</button>
                        </div>
                    <?php endif; ?>
                    <ul class="cliente-pedidos__lineas">
                        <?php foreach ($pedido['lineas'] as $linea): ?>
                            <li><span><?= $escapar($linea['nombre']) ?> × <?= $linea['cantidad'] ?></span>
                                <strong><?= $dinero($linea['precio_centavos'] * $linea['cantidad']) ?></strong></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if (trim((string) $pedido['notas']) !== ''): ?>
                        <p class="cliente-pedidos__notas"><strong>Indicaciones:</strong> <?= $escapar($pedido['notas']) ?></p>
                    <?php endif; ?>
                    <p class="cliente-pedidos__total"><span>Total</span><strong><?= $dinero($pedido['total_centavos']) ?></strong></p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<?php
$idPedidoDialogo = 0;
$nombrePedidoDialogo = '';
$autoAbrirTransferencia = false;
require __DIR__ . '/partials/dialogo-transferencia.php';
?>
