<main id="contenido" class="contenedor panel mercaderia">
    <a class="enlace-volver" href="<?= $url('equipo-stock') ?>">← Controlar stock</a>
    <div class="titulo-pagina">
        <span class="etiqueta">ADMINISTRACIÓN · STOCK</span>
        <h1 class="titulo-pagina__titulo">Entregas de mercadería</h1>
        <p class="titulo-pagina__descripcion">Consultá lo que llegó de cada proveedor y cómo se actualizó el inventario.</p>
    </div>
    <?php if ($aviso !== ''): ?><div class="aviso aviso--exito" role="status"><?= $escapar($aviso) ?></div><?php endif; ?>
    <a class="boton boton--dorado" href="<?= $url('mercaderia-registrar') ?>">+ Registrar mercadería</a>
    <form class="mercaderia__busqueda" method="get" action="<?= $url('equipo-mercaderia') ?>">
        <label class="campo">Buscar entregas
            <input class="campo__entrada" type="search" name="q" maxlength="120" value="<?= $escapar($busqueda) ?>" placeholder="Proveedor, comprobante o materia prima">
        </label>
        <button class="boton boton--contorno">Buscar</button>
    </form>
    <p class="mercaderia__cantidad"><?= $total ?> <?= $total === 1 ? 'entrega registrada' : 'entregas registradas' ?></p>
    <div class="mercaderia__historial">
        <?php foreach ($entregas as $entrega): ?>
        <article class="tarjeta mercaderia__entrega">
            <div class="mercaderia__cabecera">
                <div>
                    <span class="etiqueta">MER-<?= str_pad((string) $entrega['id_entrega'], 3, '0', STR_PAD_LEFT) ?></span>
                    <h2 class="mercaderia__proveedor"><?= $escapar($entrega['proveedor']) ?></h2>
                </div>
                <span class="insignia insignia--exito">Recibida el <?= $escapar(date('d/m/Y', strtotime($entrega['fecha_recepcion']))) ?></span>
            </div>
            <p class="mercaderia__metadatos">Registrada por <?= $escapar($entrega['responsable']) ?> · <?= $escapar((new DateTimeImmutable($entrega['creado_en'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('America/Montevideo'))->format('d/m/Y H:i')) ?></p>
            <?php if ($entrega['comprobante'] !== ''): ?><p class="mercaderia__metadatos">Comprobante: <?= $escapar($entrega['comprobante']) ?></p><?php endif; ?>
            <ul class="mercaderia__detalles">
                <?php foreach ($entrega['detalles'] as $detalle): ?>
                <li class="mercaderia__detalle">
                    <div><strong><?= $escapar($detalle['nombre_materia']) ?></strong><span class="mercaderia__movimiento">Stock: <?= $escapar(str_replace('.', ',', $detalle['stock_anterior']) . ' → ' . str_replace('.', ',', $detalle['stock_resultante']) . ' ' . $detalle['unidad']) ?></span></div>
                    <strong class="mercaderia__recibido">+ <?= $escapar(str_replace('.', ',', $detalle['cantidad']) . ' ' . $detalle['unidad']) ?></strong>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php if ($entrega['observaciones'] !== ''): ?><p class="mercaderia__observaciones"><?= $escapar($entrega['observaciones']) ?></p><?php endif; ?>
        </article>
        <?php endforeach; ?>
        <?php if (!$entregas): ?>
        <section class="tarjeta vacio mercaderia__vacio">
            <h2 class="vacio__titulo"><?= $busqueda === '' ? 'Todavía no hay entregas registradas' : 'No encontramos entregas' ?></h2>
            <p class="vacio__descripcion"><?= $busqueda === '' ? 'Registrá la mercadería recibida para actualizar el stock y conservar su historial.' : 'Probá con otro proveedor, comprobante o materia prima.' ?></p>
        </section>
        <?php endif; ?>
    </div>
    <?php if ($paginas > 1): ?>
    <nav class="paginacion" aria-label="Páginas de entregas">
        <?php if ($pagina > 1): ?><a class="boton boton--contorno" href="<?= $escapar($url('equipo-mercaderia', ['q' => $busqueda, 'pagina' => $pagina - 1])) ?>">← Anterior</a><?php endif; ?>
        <span>Página <?= $pagina ?> de <?= $paginas ?></span>
        <?php if ($pagina < $paginas): ?><a class="boton boton--contorno" href="<?= $escapar($url('equipo-mercaderia', ['q' => $busqueda, 'pagina' => $pagina + 1])) ?>">Siguiente →</a><?php endif; ?>
    </nav>
    <?php endif; ?>
</main>
