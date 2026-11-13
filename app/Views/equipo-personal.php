<main id="contenido" class="contenedor panel equipo-personal">
    <?php
    $indicadoresActividad = [
        'pedidos_registrados' => 'Pedidos registrados',
        'pedidos_actualizados' => 'Cambios en pedidos',
        'pedidos_entregados' => 'Pedidos entregados',
        'mercaderia_registrada' => 'Entregas de mercadería',
        'pagos_confirmados' => 'Pagos confirmados',
    ];
    $hayFiltros = $busqueda !== '' || $rol !== '';
    ?>
    <div class="titulo-pagina">
        <span class="etiqueta"><?= $puedeEditarRoles ? 'SOLO DUEÑO' : 'EQUIPO' ?></span>
        <h1 class="titulo-pagina__titulo"><?= $puedeEditarRoles ? 'Administrar personal' : 'Personal del equipo' ?></h1>
        <p class="titulo-pagina__descripcion">
            <?= $puedeEditarRoles
                ? 'Consultá el equipo y asigná a cada cuenta aprobada el rol de empleado o administrador.'
                : 'Consultá las cuentas aprobadas, sus responsabilidades y la actividad registrada en el sistema.' ?>
        </p>
    </div>

    <?php if ($aviso !== ''): ?>
        <p class="equipo-personal__aviso" role="status" tabindex="-1" data-personal-aviso><?= $escapar($aviso) ?></p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="equipo-personal__error" id="personal-error" role="alert" tabindex="-1" data-personal-aviso><?= $escapar($error) ?></p>
    <?php endif; ?>

    <?php if ($puedeEditarRoles): ?>
        <section class="equipo-personal__roles" aria-label="Permisos de cada rol">
            <?php foreach (\App\Models\PersonalEquipo::ROLES as $clave => $nombre): ?>
                <article class="tarjeta equipo-personal__rol">
                    <h2><?= $escapar($nombre) ?></h2>
                    <p><?= $escapar(\App\Models\PersonalEquipo::FUNCIONES[$clave]) ?></p>
                </article>
            <?php endforeach; ?>
            <article class="tarjeta equipo-personal__rol equipo-personal__rol--dueno">
                <h2>Dueño</h2>
                <p>Acceso completo. La cuenta del dueño no se puede modificar desde esta pantalla.</p>
            </article>
        </section>

        <p class="equipo-personal__nota">
            Se muestran cuentas activas y desactivadas. Revisá las cuentas pendientes en
            <a class="enlace-texto" href="<?= $url('equipo-solicitudes') ?>">Solicitudes de acceso</a>.
        </p>
        <details class="tarjeta equipo-personal__crear" id="crear-empleado" <?= ($mostrarAltaEmpleado ?? false) ? 'open' : '' ?>>
            <summary class="equipo-personal__crear-resumen">
                <span class="equipo-personal__crear-intro">
                    <span class="etiqueta">ALTA DIRECTA</span>
                    <strong>Crear cuenta de empleado</strong>
                    <span>Agregá una persona al equipo sin pasar por una solicitud de acceso.</span>
                </span>
                <span class="equipo-personal__crear-accion">
                    <span class="equipo-personal__crear-abrir">Abrir formulario</span>
                    <span class="equipo-personal__crear-cerrar">Cerrar formulario</span>
                    <span class="equipo-personal__crear-icono" aria-hidden="true"></span>
                </span>
            </summary>
            <div class="equipo-personal__crear-contenido">
                <form class="equipo-personal__crear-formulario" method="post" action="<?= $url('equipo-permisos') ?>">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="accion" value="crear_empleado">
                    <div class="equipo-personal__crear-columna">
                        <label class="equipo-personal__campo">
                            <span>Nombre completo</span>
                            <input name="nombre" maxlength="120" autocomplete="name" required
                                value="<?= $escapar($nuevoNombre) ?>">
                        </label>
                        <label class="equipo-personal__campo">
                            <span>Cédula</span>
                            <input name="cedula" inputmode="numeric" pattern="[0-9]{6,8}" minlength="6" maxlength="8"
                                autocomplete="off" aria-describedby="equipo-personal-cedula-ayuda" required
                                value="<?= $escapar($nuevaCedula) ?>">
                            <small id="equipo-personal-cedula-ayuda">De 6 a 8 dígitos, sin puntos ni guiones.</small>
                        </label>
                        <label class="equipo-personal__campo">
                            <span>Correo electrónico</span>
                            <input type="email" name="email" maxlength="190" autocomplete="email" required
                                value="<?= $escapar($nuevoEmail) ?>">
                        </label>
                    </div>
                    <div class="equipo-personal__crear-columna">
                        <label class="equipo-personal__campo">
                            <span>Contraseña inicial</span>
                            <input type="password" name="password" minlength="8" maxlength="72"
                                autocomplete="new-password" required>
                            <small>Debe tener al menos 8 caracteres.</small>
                        </label>
                        <label class="equipo-personal__campo">
                            <span>Repetir contraseña</span>
                            <input type="password" name="confirmacion" minlength="8" maxlength="72"
                                autocomplete="new-password" required>
                        </label>
                    </div>

                    <button class="boton boton--dorado equipo-personal__crear-boton" type="submit">
                        Crear cuenta de empleado
                    </button>
                </form>
            </div>
        </details>
    <?php else: ?>
    <?php endif; ?>

    <form class="equipo-personal__filtros" method="get" action="<?= $url($rutaPersonal) ?>" data-personal-filtros>
        <label class="equipo-personal__campo">
            <span>Buscar por nombre o correo</span>
            <input type="search" name="q" maxlength="120" value="<?= $escapar($busqueda) ?>" autocomplete="off">
        </label>
        <label class="equipo-personal__campo">
            <span>Filtrar por rol</span>
            <select name="rol">
                <option value="" <?= $rol === '' ? 'selected' : '' ?>>Todos los roles</option>
                <?php foreach (\App\Models\PersonalEquipo::ROLES as $clave => $nombre): ?>
                    <option value="<?= $escapar($clave) ?>" <?= $rol === $clave ? 'selected' : '' ?>><?= $escapar($nombre) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="boton boton--dorado" type="submit">Aplicar filtros</button>
        <a class="boton boton--contorno" href="<?= $url($rutaPersonal) ?>">Restablecer filtros</a>
    </form>

    <?php if ($hayFiltros): ?>
        <p class="equipo-personal__filtros-activos">Filtros aplicados: <?= $rol === '' ? 'Todos los roles' : $escapar(\App\Models\PersonalEquipo::ROLES[$rol] ?? 'Rol no válido') ?><?php if ($busqueda !== ''): ?> · Búsqueda: «<?= $escapar($busqueda) ?>»<?php endif; ?>.</p>
    <?php endif; ?>

    <section class="equipo-personal__resultados" aria-labelledby="equipo-personal-titulo">
        <div class="equipo-personal__resumen">
            <h2 id="equipo-personal-titulo"><?= $puedeEditarRoles ? 'Cuentas activas y desactivadas' : 'Cuentas aprobadas' ?></h2>
            <?php if ($error === '' || $puedeEditarRoles): ?>
                <p><?= count($usuarios) ?> <?= count($usuarios) === 1 ? 'cuenta encontrada' : 'cuentas encontradas' ?></p>
            <?php endif; ?>
        </div>
        <p class="equipo-personal__criterio" id="personal-actividad-ayuda">Actividad acumulada, sin filtro por fecha. «Cambios en pedidos» cuenta eventos del historial: un mismo pedido puede tener varios cambios.</p>

        <?php if ($usuarios === []): ?>
            <div class="tarjeta vacio equipo-personal__vacio">
                <?php if ($error !== ''): ?>
                    <h3 class="vacio__titulo">Revisá el aviso antes de continuar</h3>
                    <p class="vacio__descripcion">Podés corregir los filtros o restablecerlos para volver a consultar el personal.</p>
                <?php elseif ($hayFiltros): ?>
                    <h3 class="vacio__titulo">No encontramos cuentas con estos filtros</h3>
                    <p class="vacio__descripcion">Probá cambiar la búsqueda o el rol seleccionado.</p>
                <?php else: ?>
                    <h3 class="vacio__titulo">Todavía no hay cuentas para mostrar</h3>
                    <p class="vacio__descripcion">Las cuentas del equipo aparecerán aquí cuando estén aprobadas.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="tarjeta equipo-personal__tabla-contenedor" role="region" aria-labelledby="equipo-personal-titulo" tabindex="0">
                <table class="equipo-personal__tabla" role="table" aria-describedby="personal-actividad-ayuda">
                    <thead role="rowgroup">
                        <tr role="row">
                            <th scope="col">Nombre</th>
                            <th scope="col">Correo</th>
                            <th scope="col">Rol actual</th>
                            <th scope="col"><?= $puedeEditarRoles ? 'Permisos nuevos' : 'Responsabilidades del rol' ?></th>
                            <th scope="col">Actividad registrada</th>
                            <th scope="col">Estado</th>
                            <?php if ($puedeEditarRoles): ?><th scope="col">Acción</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody role="rowgroup">
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr role="row">
                                <td role="cell"><span class="equipo-personal__etiqueta-movil" aria-hidden="true">Nombre</span><span><?= $escapar($usuario['nombre']) ?></span></td>
                                <td role="cell"><span class="equipo-personal__etiqueta-movil" aria-hidden="true">Correo</span><span><?= $escapar($usuario['email']) ?></span></td>
                                <td role="cell"><span class="equipo-personal__etiqueta-movil" aria-hidden="true">Rol actual</span><span class="equipo-personal__insignia"><?= $escapar($usuario['nombre_rol']) ?></span></td>
                                <td role="cell">
                                    <span class="equipo-personal__etiqueta-movil" aria-hidden="true"><?= $puedeEditarRoles ? 'Permisos nuevos' : 'Responsabilidades del rol' ?></span>
                                    <?php if ($puedeEditarRoles && $usuario['estado'] === 'aprobado'): ?>
                                        <form class="equipo-personal__cambio" method="post" action="<?= $escapar($url($rutaPersonal, $filtrosPersonal ?? [])) ?>">
                                            <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                            <input type="hidden" name="id_usuario" value="<?= (int) $usuario['id_usuario'] ?>">
                                            <input type="hidden" name="accion" value="cambiar_rol">
                                            <label class="equipo-personal__campo equipo-personal__campo--cambio">
                                                <select name="rol" aria-label="Nuevo rol para <?= $escapar($usuario['nombre']) ?>">
                                                    <?php foreach (\App\Models\PersonalEquipo::ROLES as $clave => $nombre): ?>
                                                        <option value="<?= $escapar($clave) ?>" <?= $usuario['rol'] === $clave ? 'selected' : '' ?>><?= $escapar($nombre) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </label>
                                            <button class="boton boton--contorno" type="submit" aria-label="Guardar permisos de <?= $escapar($usuario['nombre']) ?>">Guardar</button>
                                        </form>
                                    <?php else: ?>
                                        <span><?= $escapar($usuario['funciones']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="equipo-personal__celda-actividad" role="cell">
                                    <span class="equipo-personal__etiqueta-movil" aria-hidden="true">Actividad registrada</span>
                                    <ul class="equipo-personal__actividad" aria-label="Tareas realizadas por <?= $escapar($usuario['nombre']) ?>">
                                        <?php $tieneActividad = false; ?>
                                        <?php foreach ($indicadoresActividad as $clave => $nombre): ?>
                                            <?php $tieneActividad = $tieneActividad || (int) $usuario[$clave] > 0; ?>
                                            <li><span><?= $escapar($nombre) ?></span><strong><?= (int) $usuario[$clave] ?></strong></li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <?php if (!$tieneActividad): ?>
                                        <p class="equipo-personal__sin-actividad">Sin actividad registrada para esta cuenta.</p>
                                    <?php endif; ?>
                                </td>
                                <td role="cell">
                                    <span class="equipo-personal__etiqueta-movil" aria-hidden="true">Estado</span>
                                    <span class="equipo-personal__estado <?= $usuario['estado'] === 'aprobado' ? 'equipo-personal__estado--activo' : 'equipo-personal__estado--inactivo' ?>">
                                        <?= $escapar($usuario['nombre_estado']) ?>
                                    </span>
                                </td>
                                <?php if ($puedeEditarRoles): ?>
                                    <td role="cell">
                                        <span class="equipo-personal__etiqueta-movil" aria-hidden="true">Acción</span>
                                        <form method="post" action="<?= $escapar($url($rutaPersonal, $filtrosPersonal ?? [])) ?>">
                                            <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                            <input type="hidden" name="id_usuario" value="<?= (int) $usuario['id_usuario'] ?>">
                                            <input type="hidden" name="accion" value="<?= $usuario['estado'] === 'aprobado' ? 'desactivar' : 'reactivar' ?>">
                                            <button class="boton boton--contorno" type="submit">
                                                <?= $usuario['estado'] === 'aprobado' ? 'Desactivar' : 'Reactivar' ?>
                                            </button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <a class="enlace-texto" href="<?= $url('equipo-panel') ?>">Volver al panel</a>
</main>
