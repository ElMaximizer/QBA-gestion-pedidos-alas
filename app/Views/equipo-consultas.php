<main id="contenido" class="contenedor panel bandeja-equipo">
    <div class="titulo-pagina">
        <span class="etiqueta">ATENCIÓN DEL EQUIPO</span>
        <h1 class="titulo-pagina__titulo">Consultas recibidas</h1>
        <p class="titulo-pagina__descripcion">Los mensajes del formulario de contacto, en un solo lugar.</p>
    </div>
    <?php if ($error !== ''): ?><p class="aviso aviso--error" role="alert"><?= $escapar($error) ?></p><?php endif; ?>
    <?php if ($aviso !== ''): ?><p class="aviso aviso--exito" role="status"><?= $escapar($aviso) ?></p><?php endif; ?>

    <nav class="bandeja-equipo__filtros" aria-label="Filtrar consultas">
        <?php foreach (['' => ['Todas', 'total'], 'nuevas' => ['Nuevas', 'nuevas'], 'leidas' => ['Leídas', 'leidas']] as $filtro => [$etiqueta, $contador]): ?>
        <a class="boton <?= $estado === $filtro ? 'boton--azul' : 'boton--contorno' ?>" href="<?= $escapar($url('equipo-consultas', $filtro === '' ? [] : ['estado' => $filtro])) ?>" <?= $estado === $filtro ? 'aria-current="page"' : '' ?>><?= $etiqueta ?> <span class="bandeja-equipo__cantidad"><?= $bandeja['resumen'][$contador] ?></span></a>
        <?php endforeach; ?>
    </nav>
    <section class="bandeja-equipo__lista" aria-label="Mensajes recibidos">
        <?php if ($bandeja['consultas'] === []): ?>
        <div class="tarjeta vacio">
            <h2 class="vacio__titulo"><?= $estado === '' ? 'Todavía no hay consultas' : 'No hay consultas ' . ($estado === 'nuevas' ? 'nuevas' : 'leídas') ?></h2>
            <p class="vacio__descripcion"><?= $estado === '' ? 'Cuando alguien envíe el formulario de contacto, su mensaje aparecerá acá.' : 'Cambiá el filtro para ver los demás mensajes.' ?></p>
        </div>
        <?php else: ?>
        <p class="bandeja-equipo__ayuda"><?= $bandeja['total'] ?> <?= $bandeja['total'] === 1 ? 'consulta encontrada' : 'consultas encontradas' ?>. Abrí un mensaje para ver su contenido. Las más recientes aparecen primero.</p>
        <?php foreach ($bandeja['consultas'] as $consulta): $nueva = (int) $consulta['leido'] === 0; $fecha = new DateTimeImmutable($consulta['creado_en']); ?>
        <details class="tarjeta bandeja-equipo__item" id="consulta-<?= (int) $consulta['id_mensaje'] ?>">
            <summary class="bandeja-equipo__cabecera">
                <span class="bandeja-equipo__identidad">
                    <strong class="bandeja-equipo__titulo"><?= $escapar($consulta['motivo']) ?></strong>
                    <span><?= $escapar($consulta['nombre']) ?> · Consulta #<?= (int) $consulta['id_mensaje'] ?></span>
                </span>
                <span class="bandeja-equipo__datos">
                    <span class="insignia <?= $nueva ? 'insignia--exito' : 'insignia--neutra' ?>"><?= $nueva ? 'Nueva' : 'Leída' ?></span>
                    <time datetime="<?= $escapar($fecha->format(DATE_ATOM)) ?>"><?= $fecha->format('d/m/Y · H:i') ?></time>
                </span>
            </summary>
            <div class="bandeja-equipo__contenido">
                <p class="bandeja-equipo__correo">Correo de contacto: <a href="mailto:<?= $escapar(rawurlencode($consulta['email'])) ?>"><?= $escapar($consulta['email']) ?></a></p>
                <p class="bandeja-equipo__texto"><?= $escapar($consulta['mensaje']) ?></p>
                <?php if ($nueva): ?>
                <form class="bandeja-equipo__acciones" method="post" action="<?= $escapar($url('equipo-consultas', ['estado' => $estado, 'pagina' => $bandeja['pagina']])) ?>">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="id_mensaje" value="<?= (int) $consulta['id_mensaje'] ?>">
                    <input type="hidden" name="accion" value="marcar-leida">
                    <button class="boton boton--contorno" type="submit" aria-label="Marcar consulta #<?= (int) $consulta['id_mensaje'] ?> como leída">Marcar como leída</button>
                </form>
                <?php endif; ?>
            </div>
        </details>
        <?php endforeach; ?>
        <?php endif; ?>
    </section>
    <?php require __DIR__ . '/partials/bandeja-equipo-paginacion.php'; ?>
    <p class="bandeja-equipo__nota">Marcar una consulta como leída actualiza su estado para todo el equipo.</p>
    <a class="enlace-texto" href="<?= $url('equipo-panel') ?>">← Volver al panel</a>
</main>
