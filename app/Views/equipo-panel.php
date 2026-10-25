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
            <strong class="panel-estadistica__valor" aria-label="Sin información">—</strong>
            <p class="panel-estadistica__detalle">— pendientes de preparar</p>
        </article>
        <article class="tarjeta panel-estadistica">
            <p class="panel-estadistica__descripcion">Productos activos</p>
            <strong class="panel-estadistica__valor"><?= $resumenProductos['productos'] ?></strong>
            <p class="panel-estadistica__detalle"><?= $resumenProductos['categorias'] ?> <?= $resumenProductos['categorias'] === 1 ? 'categoría disponible' : 'categorías disponibles' ?></p>
        </article>
        <article class="tarjeta panel-estadistica">
            <p class="panel-estadistica__descripcion">Por entregar</p>
            <strong class="panel-estadistica__valor" aria-label="Sin información">—</strong>
            <p class="panel-estadistica__detalle">Revisa los horarios de retiro</p>
        </article>
    </div>
    <div class="acciones">
        <a class="boton boton--dorado" role="link" aria-disabled="true" title="Próximamente">+ Nuevo pedido</a>
        <?php if ($administraProductos): ?>
        <a class="boton boton--contorno" href="<?= $url('producto-agregar') ?>">+ Agregar producto</a>
        <a class="boton boton--contorno" href="<?= $url('equipo-stock') ?>">Controlar stock</a>
        <a class="boton boton--contorno" href="<?= $url('mercaderia-registrar') ?>">+ Registrar mercadería</a>
        <?php endif; ?>
    </div>
    <section class="panel-retiros" aria-labelledby="titulo-retiros">
        <h2 class="panel-retiros__titulo" id="titulo-retiros">Próximos retiros</h2>
        <div class="panel-retiros__cabecera" aria-hidden="true">
            <span>Pedido / cliente</span><span>Retiro</span><span>Productos</span><span>Total</span><span>Estado</span><span></span>
        </div>
        <div class="tarjeta vacio panel-retiros__vacio">
            <h3 class="panel-retiros__aviso">Consultar pedidos</h3>
            <p class="vacio__descripcion">Buscá pedidos por número, cliente, teléfono, producto, fecha o estado.</p>
            <a class="boton boton--dorado" role="link" aria-disabled="true" title="Próximamente">+ Nuevo pedido</a>
        </div>
        <a class="enlace-texto panel-retiros__enlace" href="<?= $url('equipo-pedidos') ?>">Ver todos los pedidos →</a>
    </section>
    <section class="panel-atencion" aria-labelledby="titulo-atencion">
        <h2 class="panel-atencion__titulo" id="titulo-atencion">Atención del equipo</h2>
        <div class="acciones panel-atencion__acciones">
            <a class="boton boton--contorno" role="link" aria-disabled="true" title="Próximamente">Consultas recibidas (— nuevas)</a>
            <?php // La consulta del personal corresponde a HU10; altas y bajas quedan para HU21. ?>
            <?php if (in_array($usuario['rol'], ['administrador', 'dueno'], true)): ?>
            <a class="boton boton--contorno" href="<?= $url('equipo-personal') ?>">Consultar personal</a>
            <?php endif; ?>
            <?php if ($usuario['rol'] === 'dueno'): ?>
            <a class="boton boton--contorno" href="<?= $url('equipo-permisos') ?>">Administrar roles y permisos</a>
            <a class="boton boton--contorno" role="link" aria-disabled="true" title="Próximamente">Gestionar personal</a>
            <a class="boton boton--contorno" role="link" aria-disabled="true" title="Próximamente">Solicitudes de acceso (—)</a>
            <a class="boton boton--contorno" role="link" aria-disabled="true" title="Próximamente">Actividad del personal</a>
            <?php elseif ($usuario['rol'] === 'administrador'): ?>
            <a class="boton boton--contorno" role="link" aria-disabled="true" title="Próximamente">Solicitar alta de personal</a>
            <?php endif; ?>
            <?php if (in_array($usuario['rol'], ['administrador', 'dueno'], true)): ?>
            <a class="boton boton--contorno" role="link" aria-disabled="true" title="Próximamente">Bandeja de correo local</a>
            <?php endif; ?>
        </div>
    </section>
</main>
