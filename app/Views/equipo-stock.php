<main id="contenido" class="contenedor panel equipo-stock">
    <div class="titulo-pagina">
        <span class="etiqueta">ADMINISTRACIÓN</span>
        <h1 class="titulo-pagina__titulo">Controlar stock</h1>
        <p class="titulo-pagina__descripcion">Consultá las materias primas y actualizá las cantidades disponibles y mínimas.</p>
    </div>

    <div class="acciones">
        <a class="boton boton--dorado" href="<?= $url('mercaderia-registrar') ?>">+ Registrar mercadería</a>
        <a class="boton boton--contorno" href="<?= $url('equipo-mercaderia') ?>">Ver entregas de proveedores</a>
    </div>

    <?php if ($aviso !== ''): ?><p class="equipo-stock__mensaje equipo-stock__mensaje--exito" role="status"><?= $escapar($aviso) ?></p><?php endif; ?>
    <?php if ($error !== ''): ?><p class="equipo-stock__mensaje equipo-stock__mensaje--error" role="alert"><?= $escapar($error) ?></p><?php endif; ?>

    <section class="tarjeta equipo-stock__alta" aria-labelledby="equipo-stock-alta-titulo">
        <h2 id="equipo-stock-alta-titulo">Agregar materia prima</h2>
        <form class="equipo-stock__alta-form" method="post" action="<?= $escapar($url('equipo-stock')) ?>">
            <input type="hidden" name="csrf" value="<?= $escapar($csrf) ?>">
            <input type="hidden" name="accion" value="agregar">
            <label class="campo">Nombre
                <input class="campo__entrada" type="text" name="nombre" maxlength="100" required value="<?= $escapar($valores['nombre']) ?>" placeholder="Harina de trigo">
            </label>
            <label class="campo">Unidad
                <select class="campo__entrada" name="unidad" required>
                    <?php foreach ($unidades as $unidad): ?><option value="<?= $escapar($unidad) ?>" <?= $valores['unidad'] === $unidad ? 'selected' : '' ?>><?= $escapar($unidad) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label class="campo">Cantidad actual
                <input class="campo__entrada" type="number" name="stock_actual" min="0" max="999999999.999" step="0.001" required value="<?= $escapar($valores['stock_actual']) ?>">
            </label>
            <label class="campo">Mínimo para reponer
                <input class="campo__entrada" type="number" name="stock_minimo" min="0" max="999999999.999" step="0.001" required value="<?= $escapar($valores['stock_minimo']) ?>">
            </label>
            <button class="boton boton--dorado equipo-stock__alta-boton">Agregar al inventario</button>
        </form>
    </section>

    <form class="equipo-stock__filtros" method="get" action="<?= $escapar($url('equipo-stock')) ?>">
        <label class="campo equipo-stock__busqueda">Buscar materia prima
            <input class="campo__entrada" type="search" name="q" maxlength="100" value="<?= $escapar($busqueda) ?>" placeholder="Harina, azúcar, leche…">
        </label>
        <label class="campo">Disponibilidad
            <select class="campo__entrada" name="estado">
                <option value="" <?= $estado === '' ? 'selected' : '' ?>>Todas</option>
                <option value="bajo" <?= $estado === 'bajo' ? 'selected' : '' ?>>Necesitan reposición</option>
                <option value="correcto" <?= $estado === 'correcto' ? 'selected' : '' ?>>Stock suficiente</option>
            </select>
        </label>
        <button class="boton boton--contorno equipo-stock__buscar">Aplicar filtros</button>
    </form>

    <p class="equipo-stock__cantidad"><?= $total ?> <?= $total === 1 ? 'materia prima' : 'materias primas' ?></p>
    <div class="equipo-stock__lista">
        <?php foreach ($materias as $materia): ?>
        <article class="tarjeta equipo-stock__item <?= $materia['stock_bajo'] ? 'equipo-stock__item--bajo' : '' ?>">
            <div class="equipo-stock__item-titulo">
                <div><h2><?= $escapar($materia['nombre']) ?></h2><p>Actualizado <?= $escapar(date('d/m/Y H:i', strtotime($materia['actualizado_en']))) ?></p></div>
                <span class="insignia <?= $materia['stock_bajo'] ? 'insignia--alerta' : 'insignia--exito' ?>"><?= $materia['stock_bajo'] ? 'Necesita reposición' : 'Stock suficiente' ?></span>
            </div>
            <form class="equipo-stock__actualizar" method="post" action="<?= $escapar($url('equipo-stock')) ?>">
                <input type="hidden" name="csrf" value="<?= $escapar($csrf) ?>">
                <input type="hidden" name="accion" value="actualizar">
                <input type="hidden" name="id_materia" value="<?= (int) $materia['id_materia'] ?>">
                <input type="hidden" name="version" value="<?= $escapar(\App\Models\StockMateriaPrima::version($materia)) ?>">
                <label class="campo">Disponible (<?= $escapar($materia['unidad']) ?>)
                    <input class="campo__entrada" type="number" name="stock_actual" min="0" max="999999999.999" step="0.001" required value="<?= $escapar($materia['stock_actual']) ?>">
                </label>
                <label class="campo">Mínimo (<?= $escapar($materia['unidad']) ?>)
                    <input class="campo__entrada" type="number" name="stock_minimo" min="0" max="999999999.999" step="0.001" required value="<?= $escapar($materia['stock_minimo']) ?>">
                </label>
                <button class="boton boton--contorno">Guardar stock</button>
            </form>
        </article>
        <?php endforeach; ?>
        <?php if (!$materias): ?>
        <section class="tarjeta vacio equipo-stock__vacio"><h2 class="vacio__titulo">No hay materias primas para mostrar</h2>
            <p class="vacio__descripcion"><?= $total === 0 && $busqueda === '' && $estado === '' ? 'Agregá la primera materia prima para comenzar a controlar el inventario.' : 'Probá con otra búsqueda o quitá algún filtro.' ?></p>
        </section>
        <?php endif; ?>
    </div>

    <?php if ($paginas > 1): ?><nav class="paginacion" aria-label="Páginas del stock">
        <?php if ($pagina > 1): ?><a class="boton boton--contorno" href="<?= $escapar($url('equipo-stock', ['q' => $busqueda, 'estado' => $estado, 'pagina' => $pagina - 1])) ?>">← Anterior</a><?php endif; ?>
        <span>Página <?= $pagina ?> de <?= $paginas ?></span>
        <?php if ($pagina < $paginas): ?><a class="boton boton--contorno" href="<?= $escapar($url('equipo-stock', ['q' => $busqueda, 'estado' => $estado, 'pagina' => $pagina + 1])) ?>">Siguiente →</a><?php endif; ?>
    </nav><?php endif; ?>
</main>
