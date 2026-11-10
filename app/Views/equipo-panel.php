<main id="contenido" class="contenedor panel">
    <div class="titulo-pagina">
        <span class="etiqueta">ADMINISTRACIÓN</span>
        <h1 class="titulo-pagina__titulo">Panel de administración</h1>
        <p class="titulo-pagina__descripcion">Una vista clara de lo que necesita tu atención hoy.</p>
        <p class="panel__cuenta"><strong><?= $escapar($usuario['nombre']) ?></strong> · Tu rol: <?= $escapar($nombreRol) ?>.</p>
    </div>
    <div class="panel__estadisticas">
        <article class="tarjeta panel-estadistica">
            <p class="panel-estadistica__descripcion">Pedidos de hoy</p>
            <strong class="panel-estadistica__valor"><?= $resumenPedidos['hoy'] ?></strong>
            <p class="panel-estadistica__detalle"><?= $resumenPedidos['pendientes_hoy'] ?> <?= $resumenPedidos['pendientes_hoy'] === 1 ? 'pendiente' : 'pendientes' ?> de preparar</p>
        </article>
        <article class="tarjeta panel-estadistica">
            <p class="panel-estadistica__descripcion">Productos activos</p>
            <strong class="panel-estadistica__valor"><?= $resumenProductos['productos'] ?></strong>
            <p class="panel-estadistica__detalle"><?= $resumenProductos['categorias'] ?> <?= $resumenProductos['categorias'] === 1 ? 'categoría disponible' : 'categorías disponibles' ?></p>
        </article>
        <article class="tarjeta panel-estadistica">
            <p class="panel-estadistica__descripcion">Por entregar</p>
            <strong class="panel-estadistica__valor"><?= $resumenPedidos['por_entregar'] ?></strong>
            <p class="panel-estadistica__detalle">Revisa los horarios de retiro</p>
        </article>
    </div>
    <div class="acciones">
        <a class="boton boton--dorado" href="<?= $url('equipo-pedido-registrar') ?>">+ Registrar pedido</a>
        <a class="boton boton--contorno" href="<?= $url('equipo-pedidos') ?>">Consultar pedidos</a>
        <?php if (in_array($usuario['rol'], ['administrador', 'dueno'], true)): ?>
            <a class="boton boton--contorno" href="<?= $url('equipo-ventas') ?>">Consultar ventas</a>
        <?php endif; ?>
    </div>
    <section class="panel-atencion" aria-labelledby="titulo-productos-solicitados">
        <h2 class="panel-atencion__titulo" id="titulo-productos-solicitados">Productos más solicitados</h2>
        <?php if ($productosMasSolicitados === []): ?>
            <p class="vacio__descripcion">Todavía no hay pedidos para mostrar estadísticas.</p>
        <?php else: ?>
            <ol class="panel__productos-solicitados">
                <?php foreach ($productosMasSolicitados as $productoSolicitado): ?>
                    <li><strong><?= $escapar($productoSolicitado['nombre_producto']) ?></strong>
                        <span><?= (int) $productoSolicitado['unidades'] ?> unidades en <?= (int) $productoSolicitado['pedidos'] ?> pedidos</span>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </section>
    <section class="panel-atencion" aria-labelledby="titulo-atencion">
        <h2 class="panel-atencion__titulo" id="titulo-atencion">Atención del equipo</h2>
        <div class="acciones panel-atencion__acciones">
            <a class="boton boton--contorno" href="<?= $url('equipo-consultas') ?>">Consultas recibidas (<?= $consultasNuevas ?> <?= $consultasNuevas === 1 ? 'nueva' : 'nuevas' ?>)</a>
            <?php if ($usuario['rol'] === 'dueno'): ?>
                <a class="boton boton--contorno" href="<?= $url('equipo-permisos') ?>">Administrar personal</a>
                <a class="boton boton--contorno" href="<?= $url('equipo-solicitudes') ?>">Solicitudes de acceso (<?= $solicitudesPendientes ?>)</a>
            <?php endif; ?>
        </div>
    </section>
</main>
