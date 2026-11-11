<main id="contenido" class="contenedor panel equipo-estadisticas">
    <div class="titulo-pagina">
        <span class="etiqueta">ADMINISTRACIÓN</span>
        <h1 class="titulo-pagina__titulo">Estadísticas de pedidos</h1>
        <p class="titulo-pagina__descripcion">Los productos más solicitados, según los pedidos no cancelados.</p>
    </div>

    <?php if ($productosMasSolicitados === []): ?>
        <section class="tarjeta vacio equipo-estadisticas__vacio">
            <h2 class="vacio__titulo">Todavía no hay datos para mostrar</h2>
            <p class="vacio__descripcion">Cuando se registren pedidos, vas a ver aquí los productos más solicitados.</p>
        </section>
    <?php else: ?>
        <ol class="equipo-estadisticas__lista" aria-label="Ranking de productos más solicitados">
            <?php foreach ($productosMasSolicitados as $indice => $producto): ?>
                <li class="equipo-estadisticas__fila">
                    <span class="equipo-estadisticas__puesto" aria-label="Puesto <?= $indice + 1 ?>">
                        <?= str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT) ?>
                    </span>
                    <div class="equipo-estadisticas__producto">
                        <h2><?= $escapar($producto['nombre_producto']) ?></h2>
                        <p>En <?= (int) $producto['pedidos'] ?> <?= (int) $producto['pedidos'] === 1 ? 'pedido' : 'pedidos' ?></p>
                    </div>
                    <div class="equipo-estadisticas__cantidad">
                        <strong><?= (int) $producto['unidades'] ?></strong>
                        <span><?= (int) $producto['unidades'] === 1 ? 'unidad' : 'unidades' ?></span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>

    <a class="boton boton--contorno equipo-estadisticas__volver" href="<?= $url('equipo-panel') ?>">Volver al panel</a>
</main>
