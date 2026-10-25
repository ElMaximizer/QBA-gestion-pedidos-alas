<main id="contenido" class="contenedor panel equipo-permisos">
    <div class="titulo-pagina">
        <span class="etiqueta">SOLO DUEÑO</span>
        <h1 class="titulo-pagina__titulo">Roles y permisos</h1>
        <p class="titulo-pagina__descripcion">Asigná a cada cuenta aprobada los permisos de empleado o administrador.</p>
    </div>

    <?php if ($aviso !== ''): ?>
        <p class="equipo-permisos__aviso" role="status"><?= $escapar($aviso) ?></p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="equipo-permisos__error" role="alert"><?= $escapar($error) ?></p>
    <?php endif; ?>

    <section class="equipo-permisos__roles" aria-label="Permisos de cada rol">
        <?php foreach (\App\Models\PermisosEquipo::ROLES as $clave => $nombre): ?>
            <article class="tarjeta equipo-permisos__rol">
                <h2><?= $escapar($nombre) ?></h2>
                <p><?= $escapar(\App\Models\PermisosEquipo::FUNCIONES[$clave]) ?></p>
            </article>
        <?php endforeach; ?>
        <article class="tarjeta equipo-permisos__rol equipo-permisos__rol--dueno">
            <h2>Dueño</h2>
            <p>Acceso completo. La cuenta del dueño no se puede modificar desde esta pantalla.</p>
        </article>
    </section>

    <p class="equipo-permisos__nota">
        Solo se muestran cuentas aprobadas. Esta pantalla asigna roles; las cuentas pendientes, las altas y las bajas se administran en sus funciones correspondientes.
        Cada módulo aplica los permisos del rol que tiene implementado.
    </p>

    <form class="equipo-permisos__filtros" method="get" action="<?= $url('equipo-permisos') ?>">
        <label class="equipo-permisos__campo">
            <span>Buscar por nombre o correo</span>
            <input type="search" name="q" maxlength="120" value="<?= $escapar($busqueda) ?>" autocomplete="off">
        </label>
        <label class="equipo-permisos__campo">
            <span>Filtrar por rol</span>
            <select name="rol">
                <option value="" <?= $rol === '' ? 'selected' : '' ?>>Todos los roles</option>
                <?php foreach (\App\Models\PermisosEquipo::ROLES as $clave => $nombre): ?>
                    <option value="<?= $escapar($clave) ?>" <?= $rol === $clave ? 'selected' : '' ?>><?= $escapar($nombre) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="boton boton--dorado" type="submit">Buscar</button>
        <a class="boton boton--contorno" href="<?= $url('equipo-permisos') ?>">Limpiar</a>
    </form>

    <section class="equipo-permisos__resultados" aria-labelledby="equipo-permisos-titulo">
        <div class="equipo-permisos__resumen">
            <h2 id="equipo-permisos-titulo">Cuentas aprobadas</h2>
            <p><?= count($usuarios) ?> <?= count($usuarios) === 1 ? 'cuenta encontrada' : 'cuentas encontradas' ?></p>
        </div>

        <?php if ($usuarios === []): ?>
            <div class="tarjeta vacio equipo-permisos__vacio">
                <h3 class="vacio__titulo">No encontramos cuentas</h3>
                <p class="vacio__descripcion">Probá cambiar la búsqueda o el rol seleccionado.</p>
            </div>
        <?php else: ?>
            <div class="tarjeta equipo-permisos__tabla-contenedor">
                <table class="equipo-permisos__tabla">
                    <thead>
                        <tr>
                            <th scope="col">Nombre</th>
                            <th scope="col">Correo</th>
                            <th scope="col">Rol actual</th>
                            <th scope="col">Permisos nuevos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?= $escapar($usuario['nombre']) ?></td>
                                <td><?= $escapar($usuario['email']) ?></td>
                                <td><span class="equipo-permisos__insignia"><?= $escapar($usuario['nombre_rol']) ?></span></td>
                                <td>
                                    <form class="equipo-permisos__cambio" method="post" action="<?= $url('equipo-permisos') ?>">
                                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                        <input type="hidden" name="id_usuario" value="<?= (int) $usuario['id_usuario'] ?>">
                                        <label class="equipo-permisos__campo equipo-permisos__campo--cambio">
                                            <span class="solo-lectores">Nuevo rol para <?= $escapar($usuario['nombre']) ?></span>
                                            <select name="rol" aria-label="Nuevo rol para <?= $escapar($usuario['nombre']) ?>">
                                                <?php foreach (\App\Models\PermisosEquipo::ROLES as $clave => $nombre): ?>
                                                    <option value="<?= $escapar($clave) ?>" <?= $usuario['rol'] === $clave ? 'selected' : '' ?>><?= $escapar($nombre) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <button class="boton boton--contorno" type="submit" aria-label="Guardar permisos de <?= $escapar($usuario['nombre']) ?>">Guardar</button>
                                    </form>
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
