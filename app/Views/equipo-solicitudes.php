<main id="contenido" class="contenedor panel bandeja-equipo">
    <div class="titulo-pagina">
        <span class="etiqueta">SOLO DUEÑO</span>
        <h1 class="titulo-pagina__titulo">Solicitudes de acceso</h1>
        <p class="titulo-pagina__descripcion">Revisá los datos de quienes solicitaron sumarse al equipo y decidí si pueden ingresar.</p>
    </div>
    <?php if ($error !== ''): ?><p class="aviso aviso--error" role="alert"><?= $escapar($error) ?></p><?php endif; ?>
    <?php if ($aviso !== ''): ?><p class="aviso aviso--exito" role="status"><?= $escapar($aviso) ?></p><?php endif; ?>

    <section class="bandeja-equipo__lista" aria-label="Solicitudes pendientes">
        <?php if ($bandeja['solicitudes'] === []): ?>
        <div class="tarjeta vacio">
            <h2 class="vacio__titulo">No hay solicitudes pendientes</h2>
            <p class="vacio__descripcion">Las nuevas solicitudes del registro del equipo aparecerán acá.</p>
        </div>
        <?php else: ?>
        <p class="bandeja-equipo__ayuda"><?= $bandeja['total'] ?> <?= $bandeja['total'] === 1 ? 'solicitud pendiente' : 'solicitudes pendientes' ?>. Abrí una solicitud para revisar sus datos antes de decidir.</p>
        <?php foreach ($bandeja['solicitudes'] as $solicitud): $fecha = new DateTimeImmutable($solicitud['creado_en']); ?>
        <details class="tarjeta bandeja-equipo__item" id="solicitud-<?= (int) $solicitud['id_usuario'] ?>">
            <summary class="bandeja-equipo__cabecera">
                <span class="bandeja-equipo__identidad">
                    <strong class="bandeja-equipo__titulo"><?= $escapar($solicitud['nombre']) ?></strong>
                    <span><?= $escapar($solicitud['email']) ?></span>
                </span>
                <span class="bandeja-equipo__datos">
                    <span class="insignia insignia--neutra">Pendiente</span>
                    <time datetime="<?= $escapar($fecha->format(DATE_ATOM)) ?>"><?= $fecha->format('d/m/Y · H:i') ?></time>
                </span>
            </summary>
            <div class="bandeja-equipo__contenido">
                <dl class="bandeja-equipo__ficha">
                    <div><dt>Nombre</dt><dd><?= $escapar($solicitud['nombre']) ?></dd></div>
                    <div><dt>Cédula</dt><dd><?= $escapar($solicitud['cedula']) ?></dd></div>
                    <div><dt>Correo</dt><dd><?= $escapar($solicitud['email']) ?></dd></div>
                </dl>
                <p class="bandeja-equipo__nota">Al aprobar, la persona podrá iniciar sesión como empleado. Podés cambiar su rol desde Administrar personal. Rechazar conserva la solicitud sin habilitar el acceso.</p>
                <form class="bandeja-equipo__acciones" method="post" action="<?= $escapar($url('equipo-solicitudes', ['pagina' => $bandeja['pagina']])) ?>">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="id_usuario" value="<?= (int) $solicitud['id_usuario'] ?>">
                    <button class="boton boton--dorado" type="submit" name="accion" value="aprobar" aria-label="Aprobar acceso de <?= $escapar($solicitud['nombre']) ?>">Aprobar acceso</button>
                    <button class="boton boton--rojo" type="submit" name="accion" value="rechazar" aria-label="Rechazar solicitud de <?= $escapar($solicitud['nombre']) ?>">Rechazar solicitud</button>
                </form>
            </div>
        </details>
        <?php endforeach; ?>
        <?php endif; ?>
    </section>
    <?php require __DIR__ . '/partials/bandeja-equipo-paginacion.php'; ?>
    <p class="bandeja-equipo__nota">Solo se muestran las solicitudes que todavía necesitan una decisión. Podés consultar las cuentas aprobadas y cambiar sus roles en <a class="enlace-texto" href="<?= $url('equipo-permisos') ?>">Administrar personal</a>.</p>
    <a class="enlace-texto" href="<?= $url('equipo-panel') ?>">← Volver al panel</a>
</main>
