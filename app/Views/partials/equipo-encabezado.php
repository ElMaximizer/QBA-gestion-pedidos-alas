<header class="encabezado encabezado-equipo" data-menu-encabezado>
    <div class="contenedor encabezado__contenido">
        <a class="marca" href="<?= $url('equipo-panel') ?>">
            <strong class="marca__nombre">PANADERÍA RUSTIC</strong>
            <span class="marca__descripcion">GESTIÓN DE PRODUCTOS Y PEDIDOS</span>
        </a>
        <form class="encabezado-equipo__salida" method="post" action="<?= $url('equipo-cerrar-sesion') ?>">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <button class="boton boton--dorado encabezado-equipo__boton">Salir</button>
        </form>
        <button class="encabezado__menu-boton" type="button" aria-label="Abrir menú de navegación" aria-expanded="false" aria-controls="menu-administracion" data-menu-boton hidden>
            <span class="encabezado__menu-icono" aria-hidden="true"></span>
        </button>
    </div>
<nav class="navegacion-equipo" id="menu-administracion" aria-label="Administración" data-menu-panel>
    <div class="contenedor navegacion-equipo__contenido">
        <a class="boton boton--contorno navegacion-equipo__enlace" href="<?= $url('inicio') ?>">Inicio del sitio</a>
        <a class="boton <?= $seccionActiva === 'resumen' ? 'boton--azul' : 'boton--contorno' ?> navegacion-equipo__enlace" href="<?= $url('equipo-panel') ?>" <?= $seccionActiva === 'resumen' ? 'aria-current="page"' : '' ?>>Panel administrativo</a>
        <?php if ($administraProductos): ?>
        <a class="boton <?= $seccionActiva === 'productos' ? 'boton--azul' : 'boton--contorno' ?> navegacion-equipo__enlace" href="<?= $url('equipo-productos') ?>" <?= $seccionActiva === 'productos' ? 'aria-current="page"' : '' ?>>Productos</a>
        <a class="boton <?= $seccionActiva === 'contenido' ? 'boton--azul' : 'boton--contorno' ?> navegacion-equipo__enlace" href="<?= $url('equipo-contenido-inicio') ?>" <?= $seccionActiva === 'contenido' ? 'aria-current="page"' : '' ?>>Contenido del inicio</a>
        <?php endif; ?>
        <a class="boton <?= $seccionActiva === 'pedidos' ? 'boton--azul' : 'boton--contorno' ?> navegacion-equipo__enlace" href="<?= $url('equipo-pedidos') ?>" <?= $seccionActiva === 'pedidos' ? 'aria-current="page"' : '' ?>>Pedidos</a>
        <?php if ($administraProductos): ?>
        <a class="boton <?= $seccionActiva === 'stock' ? 'boton--azul' : 'boton--contorno' ?> navegacion-equipo__enlace" href="<?= $url('equipo-stock') ?>" <?= $seccionActiva === 'stock' ? 'aria-current="page"' : '' ?>>Stock</a>
        <?php endif; ?>
    </div>
</nav>
</header>
