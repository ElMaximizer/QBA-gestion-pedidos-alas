<main id="contenido" class="contenedor panel equipo-personal">
    <div class="titulo-pagina">
        <span class="etiqueta">EQUIPO</span>
        <h1 class="titulo-pagina__titulo">Personal del equipo</h1>
        <p class="titulo-pagina__descripcion">Consultá las cuentas aprobadas y las responsabilidades definidas para cada rol.</p>
    </div>

    <form class="equipo-personal__filtros" method="get" action="<?= $url('equipo-personal') ?>">
        <label class="equipo-personal__campo">
            <span>Buscar por nombre o correo</span>
            <input type="search" name="q" maxlength="120" value="<?= $escapar($busqueda) ?>" autocomplete="off">
        </label>
        <label class="equipo-personal__campo">
            <span>Filtrar por rol</span>
            <select name="rol">
                <option value="" <?= $rol === '' ? 'selected' : '' ?>>Todos los roles</option>
                <option value="empleado" <?= $rol === 'empleado' ? 'selected' : '' ?>>Empleado</option>
                <option value="administrador" <?= $rol === 'administrador' ? 'selected' : '' ?>>Administrador</option>
            </select>
        </label>
        <button class="boton boton--dorado" type="submit">Buscar</button>
        <a class="boton boton--contorno" href="<?= $url('equipo-personal') ?>">Limpiar</a>
    </form>

    <section class="equipo-personal__resultados" aria-labelledby="equipo-personal-titulo">
        <div class="equipo-personal__resumen">
            <h2 id="equipo-personal-titulo">Cuentas aprobadas</h2>
            <p><?= count($personas) ?> <?= count($personas) === 1 ? 'persona encontrada' : 'personas encontradas' ?></p>
        </div>

        <?php if ($personas === []): ?>
            <div class="tarjeta vacio equipo-personal__vacio">
                <h3 class="vacio__titulo">No encontramos personal</h3>
                <p class="vacio__descripcion">Probá cambiar la búsqueda o el rol seleccionado.</p>
            </div>
        <?php else: ?>
            <div class="tarjeta equipo-personal__tabla-contenedor">
                <table class="equipo-personal__tabla">
                    <thead>
                        <tr>
                            <th scope="col">Nombre</th>
                            <th scope="col">Correo</th>
                            <th scope="col">Rol</th>
                            <th scope="col">Funciones del rol</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($personas as $persona): ?>
                            <tr>
                                <td><?= $escapar($persona['nombre']) ?></td>
                                <td><?= $escapar($persona['email']) ?></td>
                                <td><span class="equipo-personal__rol"><?= $escapar($persona['nombre_rol']) ?></span></td>
                                <td><?= $escapar($persona['funciones']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <p class="equipo-personal__nota">Esta consulta no modifica cuentas, estados ni permisos.</p>
    <a class="enlace-texto" href="<?= $url('equipo-panel') ?>">← Volver al panel</a>
</main>
