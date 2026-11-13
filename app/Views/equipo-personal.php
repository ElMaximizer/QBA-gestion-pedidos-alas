<main id="contenido" class="contenedor panel equipo-personal">
    <div class="titulo-pagina">
        <span class="etiqueta"><?= $puedeEditarRoles ? 'SOLO DUEÑO' : 'EQUIPO' ?></span>
        <h1 class="titulo-pagina__titulo"><?= $puedeEditarRoles ? 'Administrar personal' : 'Personal del equipo' ?></h1>
        <p class="titulo-pagina__descripcion"><?= $puedeEditarRoles
            ? 'Consultá el equipo y asigná a cada cuenta aprobada el rol de empleado o administrador.'
            : 'Consultá las cuentas aprobadas y las responsabilidades definidas para cada rol.' ?></p>
    </div>

    <?php if ($aviso !== ''): ?>
        <p class="equipo-personal__aviso" role="status"><?= $escapar($aviso) ?></p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="equipo-personal__error" role="alert"><?= $escapar($error) ?></p>
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
        Solo se muestran cuentas aprobadas. Revisá las cuentas pendientes en <a class="enlace-texto" href="<?= $url('equipo-solicitudes') ?>">Solicitudes de acceso</a>.
    </p>
    <?php else: ?>
    <p class="equipo-personal__nota">Esta consulta no modifica cuentas, estados ni permisos.</p>
    <?php endif; ?>

    <form class="equipo-personal__filtros" method="get" action="<?= $url($rutaPersonal) ?>">
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
        <button class="boton boton--dorado" type="submit">Buscar</button>
        <a class="boton boton--contorno" href="<?= $url($rutaPersonal) ?>">Limpiar</a>
    </form>

    <section class="equipo-personal__resultados" aria-labelledby="equipo-personal-titulo">
        <div class="equipo-personal__resumen">
            <h2 id="equipo-personal-titulo">Cuentas aprobadas</h2>
            <p><?= count($usuarios) ?> <?= count($usuarios) === 1 ? 'cuenta encontrada' : 'cuentas encontradas' ?></p>
        </div>

        <?php if ($usuarios === []): ?>
            <div class="tarjeta vacio equipo-personal__vacio">
                <h3 class="vacio__titulo">No encontramos cuentas</h3>
                <p class="vacio__descripcion">Probá cambiar la búsqueda o el rol seleccionado.</p>
            </div>
        <?php else: ?>
            <div class="tarjeta equipo-personal__tabla-contenedor">
                <table class="equipo-personal__tabla">
                    <thead>
                        <tr>
                            <th scope="col">Nombre</th>
                            <th scope="col">Correo</th>
                            <th scope="col">Rol actual</th>
                            <th scope="col"><?= $puedeEditarRoles ? 'Permisos nuevos' : 'Funciones del rol' ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?= $escapar($usuario['nombre']) ?></td>
                                <td><?= $escapar($usuario['email']) ?></td>
                                <td><span class="equipo-personal__insignia"><?= $escapar($usuario['nombre_rol']) ?></span></td>
                                <td>
                                    <?php if ($puedeEditarRoles): ?>
                                    <form class="equipo-personal__cambio" method="post" action="<?= $url($rutaPersonal) ?>">
                                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                        <input type="hidden" name="id_usuario" value="<?= (int) $usuario['id_usuario'] ?>">
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
                                    <?= $escapar($usuario['funciones']) ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <a class="enlace-texto" href="<?= $url('equipo-panel') ?>">← Volver al panel</a>
</main>
