<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Panes, bizcochos y tortas elaborados con dedicación en Panadería Alas.">
    <title><?= $escapar($tituloPagina) ?></title>
    <link rel="stylesheet" href="css/inicio.css">
    <?php foreach ($estilos as $estilo): ?>
    <link rel="stylesheet" href="<?= $escapar($estilo) ?>">
    <?php endforeach; ?>
    <?php foreach ($scripts as $script): ?>
    <script src="<?= $escapar($script) ?>" defer></script>
    <?php endforeach; ?>
</head>
<body class="pagina<?= $esPanel ? ' pagina--equipo' : '' ?>">
    <a class="salto-contenido" href="#contenido">Ir al contenido</a>
    <?php if ($esPanel): require __DIR__ . '/equipo-encabezado.php'; else: ?>
    <header class="encabezado">
        <div class="contenedor encabezado__contenido">
            <a class="marca" href="index.php">
                <strong class="marca__nombre">PANADERÍA ALAS</strong>
                <span class="marca__descripcion">PANADERÍA ARTESANAL</span>
            </a>
            <nav class="navegacion" aria-label="Navegación principal">
                <a class="navegacion__enlace" href="index.php" <?= $seccionActiva === 'inicio' ? 'aria-current="page"' : '' ?>>Inicio</a>
                <a class="navegacion__enlace" href="<?= $url('productos') ?>" <?= $seccionActiva === 'productos' ? 'aria-current="page"' : '' ?>>Productos</a>
                <a class="navegacion__enlace" role="link" aria-disabled="true">Nosotros</a>
                <a class="navegacion__enlace" role="link" aria-disabled="true">Contacto</a>
                <a class="carrito" href="<?= $url('carrito') ?>">
                    <img class="carrito__icono" src="images/carrito.svg" width="20" height="20" alt="">
                    Carrito <span class="carrito__cantidad"><?= $cantidadCarrito ?></span>
                </a>
            </nav>
            <?php $cuentaActual = $equipoActual ?? $clienteActual; if ($cuentaActual): ?>
            <div class="cuenta">
                <details class="cuenta__menu">
                    <summary class="cuenta__activador"><span>Hola, <strong class="cuenta__nombre"><?= $escapar($cuentaActual['nombre']) ?></strong></span><span class="cuenta__flecha" aria-hidden="true">⌄</span></summary>
                    <div class="cuenta__desplegable">
                        <?php if ($equipoActual): ?>
                        <a class="cuenta__enlace" href="<?= $url('equipo-panel') ?>">Panel del equipo</a>
                        <?php endif; ?>
                        <form class="cuenta__formulario" method="post" action="<?= $url($equipoActual ? 'equipo-cerrar-sesion' : 'cerrar-sesion') ?>">
                            <input type="hidden" name="csrf" value="<?= $csrf ?>">
                            <button class="cuenta__salir">Cerrar sesión</button>
                        </form>
                    </div>
                </details>
            </div>
            <?php else: ?>
            <a class="boton boton--dorado encabezado__acceso" href="<?= $url('cliente-login') ?>">Acceso clientes</a>
            <?php endif; ?>
        </div>
    </header>
    <?php endif; ?>
