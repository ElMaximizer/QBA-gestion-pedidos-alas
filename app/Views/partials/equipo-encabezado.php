    <header class="encabezado encabezado-equipo">
        <div class="contenedor encabezado__contenido">
            <a class="marca" href="<?= $url('equipo-panel') ?>">
                <strong class="marca__nombre">PANADERÍA ALAS</strong>
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
            <a class="boton boton--contorno navegacion-equipo__enlace" href="index.php">Inicio del sitio</a>
            <a class="boton boton--azul navegacion-equipo__enlace" href="<?= $url('equipo-panel') ?>" aria-current="page">Panel administrativo</a>
            <a class="boton boton--contorno navegacion-equipo__enlace" role="link" aria-disabled="true" title="Próximamente">Productos</a>
            <a class="boton boton--contorno navegacion-equipo__enlace" role="link" aria-disabled="true" title="Próximamente">Pedidos</a>
        </div>
    </nav>
