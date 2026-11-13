<?php $unidadesTotales = (int) array_sum(array_column($pedido['lineas'], 'cantidad')); ?>
<main id="contenido" class="contenedor panel pedido-detalle">
    <a class="enlace-texto" href="<?= $escapar($url('equipo-pedidos', $filtrosListado)) ?>"> Volver a pedidos</a>

    <div class="titulo-pagina">
        <span class="etiqueta">PEDIDO DEL CLIENTE</span>
        <h1 class="titulo-pagina__titulo">Pedido #<?= (int) $pedido['id_pedido'] ?></h1>
        <p class="titulo-pagina__descripcion">
            Revisá los productos, sus cantidades y la fecha prevista para organizar la preparación.
        </p>
    </div>

    <section class="tarjeta pedido-detalle__tarjeta pedido-detalle__planificacion" aria-labelledby="pedido-fecha-titulo">
        <div class="pedido-detalle__cabecera">
            <h2 id="pedido-fecha-titulo"><?= $pedido['modalidad_entrega'] === 'domicilio' ? 'Entrega a domicilio' : 'Retiro en el local' ?></h2>
            <span class="insignia <?= $pedido['estado'] === 'Entregado' ? 'insignia--exito' : 'insignia--neutra' ?>"><?= $escapar($pedido['estado']) ?></span>
        </div>
        <p class="pedido-detalle__fecha"><time datetime="<?= $escapar($pedido['fecha_retiro'] . 'T' . $pedido['hora_retiro']) ?>"><?= $escapar(date('d/m/Y', strtotime($pedido['fecha_retiro']))) ?> · <?= $escapar($pedido['hora_retiro']) ?></time></p>
        <p class="pedido-detalle__ayuda">Cliente: <strong><?= $escapar($pedido['cliente_nombre']) ?></strong></p>
        <?php if ($pedido['estado'] === 'Cancelado'): ?>
            <p class="pedido-detalle__nota-estado">Este pedido está cancelado. Los productos se muestran como parte de su historial.</p>
        <?php elseif ($pedido['estado'] === 'Entregado'): ?>
            <p class="pedido-detalle__nota-estado">Este pedido ya fue entregado.</p>
        <?php endif; ?>
    </section>

    <section class="tarjeta pedido-detalle__tarjeta" aria-labelledby="pedido-productos-titulo">
        <h2 id="pedido-productos-titulo">Productos del pedido</h2>
        <p class="pedido-detalle__ayuda"><?= count($pedido['lineas']) ?> <?= count($pedido['lineas']) === 1 ? 'línea' : 'líneas' ?> · <?= $unidadesTotales ?> <?= $unidadesTotales === 1 ? 'unidad' : 'unidades' ?> en total.</p>
        <?php if (trim((string) $pedido['notas']) !== ''): ?>
            <aside class="pedido-detalle__notas" aria-labelledby="pedido-indicaciones-titulo">
                <h3 id="pedido-indicaciones-titulo">Indicaciones del cliente</h3>
                <p><?= nl2br($escapar($pedido['notas'])) ?></p>
            </aside>
        <?php endif; ?>
        <ul class="pedido-detalle__lineas">
            <?php foreach ($pedido['lineas'] as $linea): ?>
                <li class="pedido-detalle__linea">
                    <div class="pedido-detalle__producto">
                        <h3><?= $escapar($linea['nombre']) ?></h3>
                    </div>
                    <div class="pedido-detalle__cantidad"><span>Cantidad</span><strong><?= (int) $linea['cantidad'] ?></strong></div>
                    <div class="pedido-detalle__subtotal"><span>Subtotal</span><strong><?= $dinero((int) $linea['precio'] * (int) $linea['cantidad']) ?></strong></div>
                </li>
            <?php endforeach; ?>
        </ul>
        <p class="pedido-detalle__total"><span>Total</span><strong><?= $dinero((int) $pedido['total_centavos']) ?></strong></p>
    </section>

    <section class="tarjeta pedido-detalle__tarjeta" aria-labelledby="pedido-datos-titulo">
        <h2 id="pedido-datos-titulo">Contacto y datos de entrega</h2>

        <dl class="pedido-detalle__datos">
            <div>
                <dt>Cliente</dt>
                <dd><?= $escapar($pedido['cliente_nombre']) ?></dd>
            </div>
            <div>
                <dt>Teléfono</dt>
                <dd><?php if ($pedido['telefono'] !== ''): ?><a href="tel:<?= $escapar($pedido['telefono']) ?>"><?= $escapar($pedido['telefono']) ?></a><?php else: ?>No informado<?php endif; ?></dd>
            </div>
            <div>
                <dt>Modalidad</dt>
                <dd>
                    <?php if (($pedido['modalidad_entrega'] ?? 'retiro') === 'domicilio'): ?>
                        Envío a domicilio · sin costo
                    <?php else: ?>
                        Retiro en el local
                    <?php endif; ?>
                </dd>
            </div>
            <div>
                <dt>Forma de pago</dt>
                <dd>
                    <?= ($pedido['metodo_pago'] ?? 'local') === 'transferencia' ? 'Transferencia por el total' : 'Pago en el local' ?>
                    · <?= ($pedido['estado_pago'] ?? 'pendiente') === 'pagado'
                        ? ((($pedido['metodo_pago'] ?? 'local') === 'transferencia') ? 'Aprobada' : 'Realizado')
                        : 'Pendiente' ?>
                </dd>
            </div>
            <?php if (($pedido['modalidad_entrega'] ?? 'retiro') === 'domicilio'): ?>
                <div class="pedido-detalle__direccion">
                    <dt>Dirección de entrega</dt>
                    <dd><?= $escapar($pedido['direccion_entrega']) ?></dd>
                </div>
            <?php endif; ?>
            <?php if (($pedido['estado'] ?? '') === 'Entregado' && !empty($pedido['entregado_en'])): ?>
                <div>
                    <dt>Entrega registrada</dt>
                    <dd><?= $escapar(date('d/m/Y H:i', strtotime($pedido['entregado_en']))) ?></dd>
                </div>
            <?php endif; ?>
        </dl>
    </section>

    <a class="enlace-texto" href="<?= $escapar($url('equipo-pedidos', $filtrosListado)) ?>">Volver a pedidos</a>
</main>
