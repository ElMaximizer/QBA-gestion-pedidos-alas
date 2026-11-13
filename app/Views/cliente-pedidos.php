<main id="contenido" class="contenedor cliente-pedidos">
    <?php if (($avisoTransferencia ?? '') !== ''): ?>
        <p class="aviso aviso--exito" role="status"><?= $escapar($avisoTransferencia) ?></p>
    <?php endif; ?>
    <?php if (($errorTransferencia ?? '') !== ''): ?>
        <p class="aviso aviso--error" role="alert"><?= $escapar($errorTransferencia) ?></p>
    <?php endif; ?>
    <header class="cliente-pedidos__encabezado">
        <div>
            <span class="etiqueta">TU ESPACIO EN RUSTIC</span>
            <h1>Mis pedidos</h1>
            <p>Consultá el detalle y el estado de tus pedidos actuales y anteriores.</p>
        </div>
        <a class="enlace-volver" href="<?= $url('cliente-cuenta') ?>">Volver a mi cuenta</a>
    </header>

    <?php if ($pedidos === []): ?>
        <section class="tarjeta vacio cliente-pedidos__vacio">
            <h2 class="vacio__titulo">Todavía no tenés pedidos</h2>
            <p class="vacio__descripcion">Cuando confirmes un pedido, vas a poder seguirlo desde acá.</p>
            <a class="boton boton--dorado" href="<?= $url('productos') ?>">Ver productos</a>
        </section>
    <?php else: ?>
        <div class="cliente-pedidos__resumen">
            <h2 class="cliente-pedidos__historial">Tu historial</h2>
            <p class="cliente-pedidos__cantidad"><?= count($pedidos) ?> <?= count($pedidos) === 1 ? 'pedido' : 'pedidos' ?></p>
        </div>
        <div class="cliente-pedidos__lista">
            <?php foreach ($pedidos as $pedido): ?>
                <?php
                $estadoClase = $pedido['estado'] === 'Entregado'
                    ? 'insignia--exito'
                    : ($pedido['estado'] === 'Cancelado' ? 'insignia--alerta' : 'insignia--neutra');
                ?>
                <article class="tarjeta cliente-pedidos__tarjeta" aria-labelledby="pedido-<?= (int) $pedido['id_pedido'] ?>">
                    <header class="cliente-pedidos__titulo">
                        <div>
                            <span class="etiqueta">HECHO PARA VOS</span>
                            <h2 id="pedido-<?= (int) $pedido['id_pedido'] ?>">Pedido #<?= (int) $pedido['id_pedido'] ?></h2>
                        </div>
                        <span class="insignia <?= $estadoClase ?>"><?= $escapar($pedido['estado']) ?></span>
                    </header>
                    <div class="cliente-pedidos__contenido">
                        <div class="cliente-pedidos__productos">
                            <h3 class="cliente-pedidos__subtitulo">Productos de tu pedido</h3>
                            <ul class="cliente-pedidos__lineas">
                                <?php foreach ($pedido['lineas'] as $linea): ?>
                                    <li>
                                        <div class="cliente-pedidos__producto">
                                            <span><?= $escapar($linea['nombre']) ?></span>
                                            <small>Cantidad: <?= (int) $linea['cantidad'] ?></small>
                                        </div>
                                        <strong><?= $dinero($linea['precio_centavos'] * $linea['cantidad'], true) ?></strong>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if (trim((string) $pedido['notas']) !== ''): ?>
                                <div class="cliente-pedidos__notas">
                                    <h3 class="cliente-pedidos__subtitulo">Indicaciones especiales</h3>
                                    <p><?= $escapar($pedido['notas']) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <aside class="cliente-pedidos__detalle" aria-label="Entrega y pago del pedido <?= (int) $pedido['id_pedido'] ?>">
                            <h3 class="cliente-pedidos__subtitulo">Entrega y pago</h3>
                            <dl class="cliente-pedidos__datos">
                                <div class="cliente-pedidos__dato">
                                    <dt><?= $pedido['modalidad_entrega'] === 'domicilio' ? 'Entrega a domicilio' : 'Retiro en el local' ?></dt>
                                    <dd><?= $escapar(date('d/m/Y', strtotime($pedido['fecha_retiro']))) ?> · <?= $escapar($pedido['hora_retiro']) ?></dd>
                                </div>
                                <?php if ($pedido['modalidad_entrega'] === 'domicilio' && trim((string) $pedido['direccion_entrega']) !== ''): ?>
                                    <div class="cliente-pedidos__dato"><dt>Dirección</dt><dd><?= $escapar($pedido['direccion_entrega']) ?></dd></div>
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
                                <div class="cliente-pedidos__dato">
                                    <dt>Forma de pago</dt>
                                    <dd><?= $pedido['metodo_pago'] === 'transferencia' ? 'Transferencia' : 'Pago en el local' ?> · <?= $escapar($estadoPagoCliente) ?></dd>
                                </div>
                            </dl>
                            <p class="cliente-pedidos__total"><span>Total del pedido</span><strong><?= $dinero($pedido['total_centavos'], true) ?></strong></p>
                            <?php if (\App\Models\PedidosCliente::puedeInformarTransferencia($pedido)): ?>
                                <div class="cliente-pedidos__transferencia">
                                    <a class="boton boton--contorno" href="<?= $escapar($url('cliente-transferencia', ['id' => $pedido['id_pedido']])) ?>" data-abrir-transferencia data-id-pedido="<?= (int) $pedido['id_pedido'] ?>" data-total-pedido="<?= $escapar($dinero($pedido['total_centavos'], true)) ?>" data-nombre-cliente="<?= $escapar($pedido['cliente_nombre']) ?>">Informar transferencia enviada</a>
                                </div>
                            <?php endif; ?>
                        </aside>
                    </div>
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
