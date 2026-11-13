<!doctype html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Panes, bizcochos y tortas elaborados con dedicación en Panadería Rustic.">
        <title><?= $escapar($tituloPagina) ?></title>
        <link rel="stylesheet" href="<?= $escapar($recurso('css/inicio.css')) ?>">
        <?php foreach ($estilos as $estilo): ?>
            <?php
            $esTemaAnterior = in_array($estilo, ['css/tema-rustic.css', 'css/tema-rustic-americana.css', 'css/tema-rustic-pophams.css'], true);
            if (!$esPanel && $esTemaAnterior) {
                continue;
            }
            $versionEstilo = match ($estilo) {
                'css/equipo-pedidos.css' => '?v=20261113-4',
                'css/equipo-pedido-detalle.css' => '?v=20261113-1',
                'css/gestion-productos.css' => '?v=20261113-1',
                'css/equipo-stock.css' => '?v=20261113-2',
                'css/equipo-ventas.css' => '?v=20261113-1',
                'css/mercaderia.css' => '?v=20261113-1',
                'css/equipo-personal.css' => '?v=20261113-2',
                'css/equipo-pedido-form.css' => '?v=20261113-1',
                default => '',
            };
            ?>
            <link rel="stylesheet" href="<?= $escapar($recurso($estilo) . $versionEstilo) ?>">
        <?php endforeach; ?>
        <?php foreach ($scripts as $script): ?>
            <?php $versionScript = in_array($script, ['js/productos.js', 'js/mercaderia.js', 'js/pedido-equipo.js'], true) ? '?v=20261113-1' : ''; ?>
            <script src="<?= $escapar($recurso($script) . $versionScript) ?>" defer></script>
        <?php endforeach; ?>
        <?php if (!$esPanel): ?>
            <link rel="stylesheet" href="<?= $escapar($recurso('css/rustic-nuevo.css')) ?>?v=20261112-1">
        <?php endif; ?>
        <link rel="stylesheet" href="<?= $escapar($recurso('css/rustic-identidad.css')) ?>?v=3">
        <link rel="stylesheet" href="<?= $escapar($recurso('css/campos-obligatorios.css')) ?>?v=1">
    </head>
    <body class="pagina<?= $esPanel ? ' pagina--equipo' : '' ?>">
        <a class="salto-contenido" href="#contenido">Ir al contenido</a>
        <?php if ($esPanel): ?>
            <?php require __DIR__ . '/equipo-encabezado.php'; ?>
        <?php else: ?>
            <header class="encabezado encabezado--publico<?= $vista === 'inicio' ? ' encabezado--inicio' : '' ?>" data-menu-encabezado>
                <div class="contenedor encabezado__contenido">
                    <a class="marca" href="<?= $url('inicio') ?>">
                        <span class="marca__logo-marco"><img class="marca__logo"
                                src="<?= $escapar($recurso('images/rustic/logo-negro.svg')) ?>"
                                alt="Panadería artesanal Rustic, Salto Uruguay" width="1200" height="1200"></span>
                        <span class="marca-rustic__nombre">RUSTIC<span>PANADERÍA ARTESANAL</span></span>
                    </a>
                    <a class="carrito encabezado__carrito-movil" href="<?= $url('carrito') ?>" data-carrito-abrir>
                        <img class="carrito__icono" src="<?= $escapar($recurso('images/carrito.svg')) ?>"
                            width="20" height="20" alt="Carrito">
                        <span class="carrito__cantidad"><?= $cantidadCarrito ?></span>
                    </a>
                    <button class="encabezado__menu-boton" type="button" aria-label="Abrir menú de navegación"
                        aria-expanded="false" aria-controls="menu-principal" data-menu-boton hidden>
                        <span class="encabezado__menu-icono" aria-hidden="true"></span>
                    </button>
                    <div class="encabezado__menu" id="menu-principal" data-menu-panel>
                        <nav class="navegacion" aria-label="Navegación principal">
                            <a class="navegacion__enlace" href="<?= $url('inicio') ?>"
                                <?= $seccionActiva === 'inicio' ? 'aria-current="page"' : '' ?>>Inicio</a>
                            <a class="navegacion__enlace" href="<?= $url('productos') ?>"
                                <?= $seccionActiva === 'productos' ? 'aria-current="page"' : '' ?>>Productos</a>
                            <a class="navegacion__enlace" href="<?= $url('nosotros') ?>"
                                <?= $seccionActiva === 'nosotros' ? 'aria-current="page"' : '' ?>>Nosotros</a>
                            <a class="navegacion__enlace" href="<?= $url('contacto') ?>"
                                <?= $seccionActiva === 'contacto' ? 'aria-current="page"' : '' ?>>Contacto</a>
                            <a class="carrito" href="<?= $url('carrito') ?>" data-carrito-abrir>
                                <img class="carrito__icono" src="<?= $escapar($recurso('images/carrito.svg')) ?>"
                                    width="20" height="20" alt=""> Carrito <span class="carrito__cantidad"><?= $cantidadCarrito ?></span>
                            </a>
                        </nav>
                        <?php $cuentaActual = $equipoActual ?? $clienteActual; ?>
                        <?php if ($cuentaActual): ?>
                            <div class="cuenta">
                                <details class="cuenta__menu">
                                    <summary class="cuenta__activador">
                                        <span>Hola, <strong class="cuenta__nombre"><?= $escapar($cuentaActual['nombre']) ?></strong></span><span
                                            class="cuenta__flecha" aria-hidden="true">⌄</span>
                                    </summary>
                                    <div class="cuenta__desplegable">
                                        <?php if ($clienteActual): ?>
                                            <a class="cuenta__enlace" href="<?= $url('cliente-pedidos') ?>">Mis pedidos</a>
                                            <a class="cuenta__enlace" href="<?= $url('cliente-cuenta') ?>">Configurar mi cuenta</a>
                                        <?php endif; ?>
                                        <?php if ($equipoActual): ?>
                                            <a class="cuenta__enlace" href="<?= $url('equipo-panel') ?>">Panel del equipo</a>
                                        <?php endif; ?>
                                        <form class="cuenta__formulario" method="post"
                                            action="<?= $url($equipoActual ? 'equipo-cerrar-sesion' : 'cerrar-sesion') ?>">
                                            <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                            <button class="cuenta__salir">Cerrar sesión</button>
                                        </form>
                                    </div>
                                </details>
                            </div>
                        <?php else: ?>
                            <a class="boton boton--dorado encabezado__acceso" href="<?= $url('cliente-login') ?>">Mi cuenta </a>
                        <?php endif; ?>
                    </div>
                </div>
            </header>
        <?php endif; ?>
