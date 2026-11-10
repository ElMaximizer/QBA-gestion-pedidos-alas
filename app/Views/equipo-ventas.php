<main id="contenido" class="contenedor panel equipo-ventas">
    <div class="titulo-pagina">
        <span class="etiqueta">ADMINISTRACIÓN</span>
        <h1 class="titulo-pagina__titulo">Consultar ventas</h1>
        <p class="titulo-pagina__descripcion">Revisá los pagos confirmados y los pedidos que todavía tienen el pago pendiente.</p>
    </div>

    <?php if ($error !== ''): ?>
        <p class="aviso aviso--error" role="alert"><?= $escapar($error) ?></p>
    <?php endif; ?>

    <form class="tarjeta equipo-ventas__filtros" method="get" action="<?= $escapar($url('equipo-ventas')) ?>">
        <label class="campo">Desde <input class="campo__entrada" type="date" name="desde" value="<?= $escapar($desde) ?>" required></label>
        <label class="campo">Hasta <input class="campo__entrada" type="date" name="hasta" value="<?= $escapar($hasta) ?>" required></label>
        <button class="boton boton--dorado" type="submit">Consultar período</button>
    </form>

    <?php if ($error === ''): ?>
        <section class="equipo-ventas__resumen" aria-label="Resumen del período">
            <article class="tarjeta equipo-ventas__indicador">
                <h2>Ingresos confirmados</h2>
                <strong><?= $dinero($resumen['ingresos_centavos']) ?></strong>
                <p><?= $resumen['pedidos_pagados'] ?> pedidos pagados</p>
            </article>
            <article class="tarjeta equipo-ventas__indicador">
                <h2>Pagos pendientes</h2>
                <strong><?= $dinero($resumen['saldo_pendiente_centavos']) ?></strong>
                <p><?= $resumen['pedidos_pendientes'] ?> pedidos pendientes, registrados en el período</p>
            </article>
            <article class="tarjeta equipo-ventas__indicador">
                <h2>Medios recibidos</h2>
                <strong><?= $resumen['transferencias'] ?> transferencias</strong>
                <p><?= $resumen['pagos_local'] ?> pagos en el local</p>
            </article>
        </section>

        <section class="equipo-ventas__detalle" aria-labelledby="ventas-detalle-titulo">
            <h2 id="ventas-detalle-titulo">Pagos confirmados</h2>
            <?php if ($pedidos === []): ?>
                <div class="tarjeta vacio"><h3 class="vacio__titulo">No hay pagos confirmados en este período</h3><p class="vacio__descripcion">Podés elegir otro rango de fechas.</p></div>
            <?php else: ?>
                <div class="tarjeta equipo-ventas__tabla-contenedor">
                    <table class="equipo-ventas__tabla">
                        <thead><tr><th>Pedido</th><th>Fecha de pago</th><th>Cliente</th><th>Productos</th><th>Medio</th><th>Total recibido</th></tr></thead>
                        <tbody>
                            <?php foreach ($pedidos as $pedido): ?>
                                <tr>
                                    <td>#<?= (int) $pedido['id_pedido'] ?></td>
                                    <td><?= $escapar(date('d/m/Y H:i', strtotime($pedido['pago_confirmado_en']))) ?></td>
                                    <td><?= $escapar($pedido['cliente_nombre']) ?></td>
                                    <td><?= $escapar($pedido['productos']) ?></td>
                                    <td><?= $pedido['metodo_pago'] === 'transferencia' ? 'Transferencia' : 'En el local' ?></td>
                                    <td><?= $dinero($pedido['total_centavos']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (count($pedidos) === 100): ?><p class="equipo-ventas__nota">Se muestran los últimos 100 pagos del período.</p><?php endif; ?>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <a class="enlace-texto" href="<?= $escapar($url('equipo-panel')) ?>">← Volver al panel</a>
</main>
