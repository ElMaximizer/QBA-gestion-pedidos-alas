<main id="contenido" class="contenedor panel">
    <div class="titulo-pagina">
        <span class="etiqueta">ADMINISTRACIÓN</span>
        <h1 class="titulo-pagina__titulo">Panel de administración</h1>
        <p class="titulo-pagina__descripcion">Una vista clara de lo que necesita tu atención hoy.</p>
        <p class="panel__cuenta"><strong><?= $escapar($usuario['nombre']) ?></strong><span class="panel__rol">Tu rol: <?= $escapar($nombreRol) ?></span></p>
    </div>
    <div class="panel__estadisticas">
        <article class="tarjeta panel-estadistica">
            <p class="panel-estadistica__descripcion">Pedidos de hoy</p>
            <strong class="panel-estadistica__valor"><?= $resumenPedidos['hoy'] ?></strong>
            <p class="panel-estadistica__detalle"><?= $resumenPedidos['pendientes_hoy'] ?> <?= $resumenPedidos['pendientes_hoy'] === 1 ? 'pendiente' : 'pendientes' ?> de preparar</p>
            <a class="panel-estadistica__enlace" href="<?= $escapar($url('equipo-pedidos', ['fecha' => 'hoy'])) ?>">Ver pedidos de hoy <span aria-hidden="true">→</span></a>
        </article>
        <article class="tarjeta panel-estadistica">
            <p class="panel-estadistica__descripcion">Productos activos</p>
            <strong class="panel-estadistica__valor"><?= $resumenProductos['productos'] ?></strong>
            <p class="panel-estadistica__detalle"><?= $resumenProductos['categorias'] ?> <?= $resumenProductos['categorias'] === 1 ? 'categoría disponible' : 'categorías disponibles' ?></p>
            <?php if ($administraProductos): ?>
                <a class="panel-estadistica__enlace" href="<?= $url('equipo-productos') ?>">Administrar productos <span aria-hidden="true">→</span></a>
            <?php elseif ($usuario['rol'] === 'empleado'): ?>
                <a class="panel-estadistica__enlace" href="<?= $url('equipo-catalogo') ?>">Consultar productos <span aria-hidden="true">→</span></a>
            <?php endif; ?>
        </article>
        <article class="tarjeta panel-estadistica">
            <p class="panel-estadistica__descripcion">Por entregar</p>
            <strong class="panel-estadistica__valor"><?= $resumenPedidos['por_entregar'] ?></strong>
            <p class="panel-estadistica__detalle">Revisá los horarios de entrega y retiro</p>
            <a class="panel-estadistica__enlace" href="<?= $url('equipo-pedidos') ?>">Consultar pedidos <span aria-hidden="true">→</span></a>
        </article>
    </div>
    <section class="panel-atencion" aria-labelledby="titulo-atencion">
        <h2 class="panel-atencion__titulo" id="titulo-atencion">Atención del equipo</h2>
        <div class="acciones panel-atencion__acciones">
            <a class="boton boton--contorno" href="<?= $url('equipo-consultas') ?>">Consultas recibidas (<?= $consultasNuevas ?> <?= $consultasNuevas === 1 ? 'nueva' : 'nuevas' ?>)</a>
            <?php if ($usuario['rol'] === 'administrador'): ?>
                <a class="boton boton--contorno" href="<?= $url('equipo-solicitudes') ?>">Solicitudes de acceso · Whitelist</a>
            <?php endif; ?>
            <?php if ($usuario['rol'] === 'dueno'): ?>
                <a class="boton boton--contorno" href="<?= $url('equipo-permisos') ?>">Administrar personal</a>
                <a class="boton boton--contorno" href="<?= $url('equipo-solicitudes') ?>">Solicitudes de acceso (<?= $solicitudesPendientes ?>)</a>
            <?php endif; ?>
        </div>
    </section>
</main>
