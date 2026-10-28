<header class="encabezado encabezado-equipo">
    <div class="contenedor encabezado__contenido">
        <a class="marca" href="<?= $url('equipo-panel') ?>">
            <strong class="marca__nombre">PANADERÍA RUSTIC</strong>
            <span class="marca__descripcion">GESTIÓN DE PRODUCTOS Y PEDIDOS</span>
        </a>
        <form class="encabezado-equipo__salida" method="post" action="<?= $url('equipo-cerrar-sesion') ?>">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <button class="boton boton--dorado encabezado-equipo__boton">Salir</button>
        </form>
    </div>
</header>
<nav class="navegacion-equipo" aria-label="Administración">
    <div class="contenedor navegacion-equipo__contenido">
        <a class="boton boton--contorno navegacion-equipo__enlace" href="<?= $url('inicio') ?>">Inicio del sitio</a>
        <a class="boton <?= $seccionActiva === 'resumen' ? 'boton--azul' : 'boton--contorno' ?> navegacion-equipo__enlace" href="<?= $url('equipo-panel') ?>" <?= $seccionActiva === 'resumen' ? 'aria-current="page"' : '' ?>>Panel administrativo</a>
        <?php if ($administraProductos): ?>
        <a class="boton <?= $seccionActiva === 'productos' ? 'boton--azul' : 'boton--contorno' ?> navegacion-equipo__enlace" href="<?= $url('equipo-productos') ?>" <?= $seccionActiva === 'productos' ? 'aria-current="page"' : '' ?>>Productos</a>
        <?php else: ?>
        <a class="boton boton--contorno navegacion-equipo__enlace" role="link" aria-disabled="true" title="Próximamente">Productos</a>
        <?php endif; ?>
        <a class="boton <?= $seccionActiva === 'pedidos' ? 'boton--azul' : 'boton--contorno' ?> navegacion-equipo__enlace" href="<?= $url('equipo-pedidos') ?>" <?= $seccionActiva === 'pedidos' ? 'aria-current="page"' : '' ?>>Pedidos</a>
        <?php if ($administraProductos): ?>
        <a class="boton <?= $seccionActiva === 'stock' ? 'boton--azul' : 'boton--contorno' ?> navegacion-equipo__enlace" href="<?= $url('equipo-stock') ?>" <?= $seccionActiva === 'stock' ? 'aria-current="page"' : '' ?>>Stock</a>
        <?php endif; ?>
    </div>
</nav>