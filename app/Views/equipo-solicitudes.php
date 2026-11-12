<main id="contenido" class="contenedor panel bandeja-equipo">
    <div class="titulo-pagina">
        <span class="etiqueta">ACCESO DEL EQUIPO</span>
        <h1 class="titulo-pagina__titulo">Solicitudes de acceso</h1>
        <p class="titulo-pagina__descripcion">Habilitá las cédulas que pueden solicitar acceso. El dueño revisa y resuelve las solicitudes.</p>
    </div>
    <?php if ($error !== ''): ?>
        <p class="aviso aviso--error" role="alert"><?= $escapar($error) ?></p>
    <?php endif; ?>
    <?php if ($aviso !== ''): ?>
        <p class="aviso aviso--exito" role="status"><?= $escapar($aviso) ?></p>
    <?php endif; ?>

    <section class="tarjeta whitelist-equipo" aria-labelledby="titulo-whitelist">
        <h2 class="tarjeta__titulo" id="titulo-whitelist">Cédulas habilitadas · Whitelist</h2>
        <p class="bandeja-equipo__ayuda">Solo estas cédulas pueden solicitar un usuario de equipo. Habilitar una cédula no crea ni aprueba una cuenta.</p>
        <form class="whitelist-equipo__formulario" method="post" action="<?= $url('equipo-solicitudes') ?>">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <input type="hidden" name="accion" value="habilitar-cedula">
            <label class="campo">Cédula
                <input class="campo__entrada" name="cedula" value="<?= $escapar($cedulaWhitelist) ?>"
                    inputmode="numeric" pattern="[0-9]{6,8}" minlength="6" maxlength="8" required aria-describedby="whitelist-ayuda">
                <small class="campo__ayuda" id="whitelist-ayuda">De 6 a 8 dígitos, sin puntos ni guiones.</small>
            </label>
            <button class="boton boton--dorado">Habilitar cédula</button>
        </form>
        <?php if ($cedulasHabilitadas === []): ?>
            <p class="bandeja-equipo__nota">La whitelist está vacía. Nadie puede enviar nuevas solicitudes hasta que habilites una cédula.</p>
        <?php else: ?>
            <ul class="whitelist-equipo__lista" aria-label="Cédulas de la whitelist">
                <?php foreach ($cedulasHabilitadas as $cedula): ?>
                    <li class="whitelist-equipo__fila">
                        <span><?= $escapar($cedula) ?></span>
                        <form method="post" action="<?= $url('equipo-solicitudes') ?>">
                            <input type="hidden" name="csrf" value="<?= $csrf ?>">
                            <input type="hidden" name="accion" value="quitar-cedula">
                            <input type="hidden" name="cedula" value="<?= $escapar($cedula) ?>">
                            <button class="boton boton--contorno" aria-label="Quitar cédula <?= $escapar($cedula) ?>">Quitar</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <p class="bandeja-equipo__ayuda">Quitar una cédula impide nuevas solicitudes; no desactiva usuarios ni borra solicitudes existentes.</p>
    </section>

    <?php if ($esDueno): ?>
        <section class="bandeja-equipo__lista" aria-label="Solicitudes pendientes">
            <?php if ($bandeja['solicitudes'] === []): ?>
                <div class="tarjeta vacio">
                    <h2 class="vacio__titulo">No hay solicitudes pendientes</h2>
                    <p class="vacio__descripcion">Las nuevas solicitudes del registro del equipo aparecerán acá.</p>
                </div>
            <?php else: ?>
                <p class="bandeja-equipo__ayuda"><?= $bandeja['total'] ?> <?= $bandeja['total'] === 1 ? 'solicitud pendiente' : 'solicitudes pendientes' ?>. Abrí una solicitud para revisar sus datos antes de decidir.</p>
                <?php foreach ($bandeja['solicitudes'] as $solicitud): ?>
                    <?php $fecha = new DateTimeImmutable($solicitud['creado_en']); ?>
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
                                <div>
                                    <dt>Nombre </dt>
                                    <dd><?= $escapar($solicitud['nombre']) ?> </dd>
                                </div>
                                <div>
                                    <dt>Cédula </dt>
                                    <dd><?= $escapar($solicitud['cedula']) ?> </dd>
                                </div>
                                <div>
                                    <dt>Correo </dt>
                                    <dd><?= $escapar($solicitud['email']) ?> </dd>
                                </div>
                            </dl>
                            <p class="bandeja-equipo__nota">Al aprobar, la persona podrá iniciar sesión como empleado. Podés cambiar su rol desde Administrar personal. Rechazar conserva la solicitud sin habilitar el acceso.</p>
                            <form class="bandeja-equipo__acciones" method="post"
                                action="<?= $escapar($url('equipo-solicitudes', ['pagina' => $bandeja['pagina']])) ?>">
                                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                <input type="hidden" name="id_usuario" value="<?= (int) $solicitud['id_usuario'] ?>">
                                <button class="boton boton--dorado" type="submit" name="accion" value="aprobar"
                                    aria-label="Aprobar acceso de <?= $escapar($solicitud['nombre']) ?>">Aprobar acceso</button>
                                <button class="boton boton--rojo" type="submit" name="accion" value="rechazar"
                                    aria-label="Rechazar solicitud de <?= $escapar($solicitud['nombre']) ?>">Rechazar solicitud</button>
                            </form>
                        </div>
                    </details>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
        <?php require __DIR__ . '/partials/bandeja-equipo-paginacion.php'; ?>
        <p class="bandeja-equipo__nota">Solo se muestran las solicitudes que todavía necesitan una decisión. Podés consultar las cuentas aprobadas y cambiar sus roles en <a class="enlace-texto" href="<?= $url('equipo-permisos') ?>">Administrar personal</a>. </p>
    <?php endif; ?>
    <a class="enlace-texto" href="<?= $url('equipo-panel') ?>"> Volver al panel</a>
</main>
