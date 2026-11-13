<main id="contenido" class="contenedor panel pedido-detalle">
    <a class="enlace-texto" href="<?= $url('equipo-pedidos') ?>"> Volver a pedidos</a>

    <div class="titulo-pagina">
        <span class="etiqueta">PEDIDO DEL CLIENTE</span>
        <h1 class="titulo-pagina__titulo">Pedido #<?= (int) $pedido['id_pedido'] ?></h1>
        <p class="titulo-pagina__descripcion">
            Consultá los datos de contacto, entrega e indicaciones del pedido.
        </p>
    </div>

    <section class="tarjeta pedido-detalle__tarjeta" aria-labelledby="pedido-datos-titulo">
        <div class="pedido-detalle__cabecera">
            <h2 id="pedido-datos-titulo">Datos del pedido</h2>
            <span class="insignia <?= $pedido['estado'] === 'Entregado' ? 'insignia--exito' : 'insignia--neutra' ?>">
                <?= $escapar($pedido['estado']) ?>
            </span>
        </div>

        <dl class="pedido-detalle__datos">
            <div>
                <dt>Cliente</dt>
                <dd><?= $escapar($pedido['cliente_nombre']) ?></dd>
            </div>
            <div>
                <dt>Teléfono</dt>
                <dd><a href="tel:<?= $escapar($pedido['telefono']) ?>"><?= $escapar($pedido['telefono']) ?></a></dd>
            </div>
            <div>
                <dt><?= ($pedido['modalidad_entrega'] ?? 'retiro') === 'domicilio' ? 'Fecha de entrega' : 'Fecha de retiro' ?></dt>
                <dd><?= $escapar(date('d/m/Y', strtotime($pedido['fecha_retiro']))) ?> · <?= $escapar(substr((string) $pedido['hora_retiro'], 0, 5)) ?></dd>
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
            <?php if (trim((string) $pedido['notas']) !== ''): ?>
                <div class="pedido-detalle__notas">
                    <dt>Indicaciones del cliente</dt>
                    <dd><?= nl2br($escapar($pedido['notas'])) ?></dd>
                </div>
            <?php endif; ?>
        </dl>
    </section>

    <section class="tarjeta pedido-detalle__tarjeta" aria-labelledby="pedido-productos-titulo">
        <h2 id="pedido-productos-titulo">Productos</h2>
        <div class="pedido-detalle__lineas">
            <?php foreach ($pedido['lineas'] as $linea): ?>
                <div class="pedido-detalle__linea">
                    <span><?= $escapar($linea['nombre']) ?> × <?= (int) $linea['cantidad'] ?></span>
                    <strong><?= $dinero((int) $linea['precio'] * (int) $linea['cantidad']) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="pedido-detalle__total"><span>Total</span><strong><?= $dinero((int) $pedido['total_centavos']) ?></strong></p>
    </section>
</main>
