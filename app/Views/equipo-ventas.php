<main id="contenido" class="contenedor panel equipo-ventas">
    <div class="titulo-pagina">
        <span class="etiqueta">ADMINISTRACIÓN</span>
        <h1 class="titulo-pagina__titulo">Consultar ventas</h1>
        <p class="titulo-pagina__descripcion">Revisá los pagos confirmados y los pedidos que todavía tienen el pago pendiente.</p>
    </div>

    <?php if ($error !== ''): ?>
        <p class="aviso aviso--error" id="ventas-error" role="alert" tabindex="-1" data-consulta-aviso><?= $escapar($error) ?></p>
    <?php endif; ?>

    <nav class="equipo-ventas__periodos" aria-label="Períodos de consulta">
        <?php $hayPeriodoActual = false; ?>
        <?php foreach ($periodosRapidos as $periodo): ?>
            <?php
            $esPeriodoActual = !$hayPeriodoActual && $error === '' && $desde === $periodo['desde'] && $hasta === $periodo['hasta'];
            $hayPeriodoActual = $hayPeriodoActual || $esPeriodoActual;
            ?>
            <a class="boton boton--contorno <?= $esPeriodoActual ? 'equipo-ventas__periodo--activo' : '' ?>"
                href="<?= $escapar($url('equipo-ventas', ['desde' => $periodo['desde'], 'hasta' => $periodo['hasta']])) ?>"
                <?= $esPeriodoActual ? 'aria-current="true"' : '' ?>><?= $escapar($periodo['etiqueta']) ?></a>
        <?php endforeach; ?>
    </nav>

    <form class="tarjeta equipo-ventas__filtros" method="get" action="<?= $escapar($url('equipo-ventas')) ?>" data-consulta-filtros>
        <label class="campo">Desde <input class="campo__entrada" type="date" name="desde" value="<?= $escapar($desde) ?>"
                aria-describedby="ventas-fechas-ayuda<?= $error !== '' ? ' ventas-error' : '' ?>" <?= $error !== '' ? 'aria-invalid="true"' : '' ?> required></label>
        <label class="campo">Hasta <input class="campo__entrada" type="date" name="hasta" value="<?= $escapar($hasta) ?>"
                aria-describedby="ventas-fechas-ayuda<?= $error !== '' ? ' ventas-error' : '' ?>" <?= $error !== '' ? 'aria-invalid="true"' : '' ?> required></label>
        <button class="boton boton--dorado" type="submit">Consultar período</button>
        <p class="equipo-ventas__ayuda" id="ventas-fechas-ayuda">Se incluyen ambas fechas. Desde debe ser anterior o igual a Hasta.</p>
    </form>

    <?php if ($error === ''): ?>
        <p class="equipo-ventas__periodo-consultado">Período consultado: <strong><time datetime="<?= $escapar($desde) ?>"><?= $escapar(date('d/m/Y', strtotime($desde))) ?></time> al <time datetime="<?= $escapar($hasta) ?>"><?= $escapar(date('d/m/Y', strtotime($hasta))) ?></time></strong>.</p>
        <p class="equipo-ventas__criterio">Los ingresos corresponden a pagos confirmados en estas fechas. Los pendientes corresponden a pedidos creados en estas fechas que todavía no se pagaron. Se excluyen los pedidos cancelados.</p>

        <section class="equipo-ventas__resumen" aria-label="Resumen del período">
            <article class="tarjeta equipo-ventas__indicador">
                <h2>Ingresos confirmados</h2>
                <strong><?= $dinero($resumen['ingresos_centavos'], true) ?></strong>
                <p><?= $resumen['pedidos_pagados'] ?> <?= $resumen['pedidos_pagados'] === 1 ? 'pedido con pago confirmado' : 'pedidos con pago confirmado' ?></p>
            </article>
            <article class="tarjeta equipo-ventas__indicador">
                <h2>Pagos pendientes</h2>
                <strong><?= $dinero($resumen['saldo_pendiente_centavos'], true) ?></strong>
                <p><?= $resumen['pedidos_pendientes'] ?> <?= $resumen['pedidos_pendientes'] === 1 ? 'pedido pendiente de pago' : 'pedidos pendientes de pago' ?></p>
                <small>Este saldo todavía no forma parte de los ingresos.</small>
            </article>
            <article class="tarjeta equipo-ventas__indicador">
                <h2>Medios de pago confirmados</h2>
                <dl class="equipo-ventas__medios">
                    <div><dt>Transferencia</dt><dd><?= $resumen['transferencias'] ?></dd></div>
                    <div><dt>En el local</dt><dd><?= $resumen['pagos_local'] ?></dd></div>
                </dl>
                <small>Cantidad de pedidos pagados por cada medio.</small>
            </article>
        </section>

        <section class="equipo-ventas__detalle" aria-labelledby="ventas-detalle-titulo">
            <h2 id="ventas-detalle-titulo">Pagos confirmados</h2>
            <?php if ($pedidos === []): ?>
                <div class="tarjeta vacio equipo-ventas__vacio">
                    <h3 class="vacio__titulo">No hay pagos confirmados en este período</h3>
                    <p class="vacio__descripcion">Podés elegir otro rango de fechas. Si hay pagos pendientes, su importe se muestra por separado en el resumen.</p>
                </div>
            <?php else: ?>
                <div class="tarjeta equipo-ventas__tabla-contenedor" role="region" aria-labelledby="ventas-detalle-titulo" tabindex="0">
                    <table class="equipo-ventas__tabla" role="table">
                        <caption>Mostrando <?= count($pedidos) ?> de <?= $resumen['pedidos_pagados'] ?> <?= $resumen['pedidos_pagados'] === 1 ? 'pago confirmado' : 'pagos confirmados' ?>. Ordenados del más reciente al más antiguo.</caption>
                        <thead role="rowgroup"><tr role="row"><th scope="col" role="columnheader">Pedido</th><th scope="col" role="columnheader">Fecha de pago</th><th scope="col" role="columnheader">Cliente</th><th scope="col" role="columnheader">Productos</th><th scope="col" role="columnheader">Medio</th><th scope="col" role="columnheader">Total recibido</th></tr></thead>
                        <tbody role="rowgroup">
                            <?php foreach ($pedidos as $pedido): ?>
                                <tr role="row">
                                    <td role="cell"><span class="equipo-ventas__etiqueta-movil" aria-hidden="true">Pedido</span><strong>#<?= (int) $pedido['id_pedido'] ?></strong></td>
                                    <td role="cell"><span class="equipo-ventas__etiqueta-movil" aria-hidden="true">Fecha de pago</span><time datetime="<?= $escapar(date('Y-m-d\TH:i:s', strtotime($pedido['pago_confirmado_en']))) ?>"><?= $escapar(date('d/m/Y H:i', strtotime($pedido['pago_confirmado_en']))) ?></time></td>
                                    <td role="cell"><span class="equipo-ventas__etiqueta-movil" aria-hidden="true">Cliente</span><span><?= $escapar($pedido['cliente_nombre']) ?></span></td>
                                    <td role="cell"><span class="equipo-ventas__etiqueta-movil" aria-hidden="true">Productos</span><span><?= $escapar($pedido['productos']) ?></span></td>
                                    <td role="cell"><span class="equipo-ventas__etiqueta-movil" aria-hidden="true">Medio</span><span><?= $pedido['metodo_pago'] === 'transferencia' ? 'Transferencia' : 'En el local' ?></span></td>
                                    <td class="equipo-ventas__total" role="cell"><span class="equipo-ventas__etiqueta-movil" aria-hidden="true">Total recibido</span><strong><?= $dinero($pedido['total_centavos'], true) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($resumen['pedidos_pagados'] > count($pedidos)): ?>
                    <p class="equipo-ventas__nota">El detalle muestra los 100 pagos más recientes. El resumen incluye todos los pagos del período. Podés elegir un período más corto para reducir los resultados.</p>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <a class="enlace-texto" href="<?= $escapar($url('equipo-panel')) ?>"> Volver al panel</a>
</main>
