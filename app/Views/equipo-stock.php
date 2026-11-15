<main id="contenido" class="contenedor panel equipo-stock">
    <?php
    $estadosVisibles = ['' => 'Todas las materias primas', 'bajo' => 'Necesitan reposición', 'correcto' => 'Stock suficiente'];
    $nombresUnidades = ['kg' => 'Kilogramos (kg)', 'g' => 'Gramos (g)', 'l' => 'Litros (l)', 'ml' => 'Mililitros (ml)', 'unidad' => 'Unidad', 'paquete' => 'Paquete'];
    ?>
    <div class="titulo-pagina">
        <span class="etiqueta">ADMINISTRACIÓN</span>
        <h1 class="titulo-pagina__titulo">Controlar stock</h1>
        <p class="titulo-pagina__descripcion">Consultá las materias primas y actualizá las cantidades disponibles y mínimas.</p>
    </div>

    <div class="acciones">
        <a class="boton boton--dorado" href="<?= $url('mercaderia-registrar') ?>">+ Registrar mercadería</a>
        <a class="boton boton--contorno" href="<?= $url('equipo-mercaderia') ?>">Ver entregas de proveedores</a>
    </div>

    <?php if ($aviso !== ''): ?>
        <p class="equipo-stock__mensaje equipo-stock__mensaje--exito" role="status" tabindex="-1" data-stock-aviso><?= $escapar($aviso) ?></p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="equipo-stock__mensaje equipo-stock__mensaje--error" role="alert" tabindex="-1" data-stock-aviso>
            <p><?= $escapar($error) ?></p>
            <?php if ($ajusteFallido !== null): ?>
                <?php if (!$ajusteVisible): ?>
                    <p>La materia prima del ajuste no está en esta página. Cantidades enviadas: disponible <?= $escapar($ajusteFallido['stock_actual']) ?> · mínimo <?= $escapar($ajusteFallido['stock_minimo']) ?>.</p>
                    <a class="enlace-texto" href="<?= $escapar($url('equipo-stock')) ?>">Ver todo el inventario</a>
                <?php endif; ?>
                <a class="enlace-texto" href="<?= $escapar($url('equipo-stock', $filtrosListado)) ?>">Recargar los valores guardados</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <aside class="equipo-stock__orientacion" aria-label="Cómo actualizar el inventario">
        <p id="stock-ajuste-ayuda"><strong>El ajuste manual reemplaza la cantidad disponible.</strong> Si recibiste insumos de un proveedor, usá Registrar mercadería para sumar la entrega al stock.</p>
        <p id="stock-cantidades-ayuda">Usá coma o punto y hasta tres decimales, sin separador de miles. Se permite cero. Se solicita reposición cuando la cantidad disponible es igual o menor que el mínimo.</p>
    </aside>

    <details class="tarjeta equipo-stock__alta" <?= $altaAbierta ? 'open' : '' ?>>
        <summary class="equipo-stock__alta-activador"><h2>Agregar materia prima</h2><span aria-hidden="true">+</span></summary>
        <p class="equipo-stock__ayuda">Creá un insumo del inventario indicando su unidad y sus cantidades iniciales.</p>
        <form class="equipo-stock__alta-form" method="post" action="<?= $escapar($url('equipo-stock', $filtrosListado)) ?>" data-stock-guardar="Agregando materia prima…">
            <input type="hidden" name="csrf" value="<?= $escapar($csrf) ?>">
            <input type="hidden" name="accion" value="agregar">
            <label class="campo">
                <span class="campo-obligatorio__etiqueta">Nombre <span class="campo-obligatorio" aria-hidden="true"
                        title="Campo obligatorio">*</span></span>
                <input class="campo__entrada" type="text" name="nombre" maxlength="100" required
                    value="<?= $escapar($valores['nombre']) ?>" placeholder="Harina de trigo">
            </label>
            <label class="campo">
                <span class="campo-obligatorio__etiqueta">Unidad <span class="campo-obligatorio" aria-hidden="true"
                        title="Campo obligatorio">*</span></span>
                <select class="campo__entrada" name="unidad" required>
                    <?php foreach ($unidades as $unidad): ?>
                        <option value="<?= $escapar($unidad) ?>" <?= $valores['unidad'] === $unidad ? 'selected' : '' ?>><?= $escapar($nombresUnidades[$unidad]) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="campo">
                <span class="campo-obligatorio__etiqueta">Cantidad actual <span class="campo-obligatorio"
                        aria-hidden="true" title="Campo obligatorio">*</span></span>
                <input class="campo__entrada" type="text" inputmode="decimal" name="stock_actual" maxlength="13"
                    pattern="[0-9]{1,9}([.,][0-9]{1,3})?" aria-describedby="stock-cantidades-ayuda"
                    required value="<?= $escapar($valores['stock_actual']) ?>">
            </label>
            <label class="campo">
                <span class="campo-obligatorio__etiqueta">Mínimo para reponer <span class="campo-obligatorio"
                        aria-hidden="true" title="Campo obligatorio">*</span></span>
                <input class="campo__entrada" type="text" inputmode="decimal" name="stock_minimo" maxlength="13"
                    pattern="[0-9]{1,9}([.,][0-9]{1,3})?" aria-describedby="stock-cantidades-ayuda"
                    required value="<?= $escapar($valores['stock_minimo']) ?>">
            </label>
            <button class="boton boton--dorado equipo-stock__alta-boton">Agregar al inventario</button>
        </form>
    </details>

    <form class="equipo-stock__filtros" method="get" action="<?= $escapar($url('equipo-stock')) ?>">
        <label class="campo equipo-stock__busqueda">Buscar materia prima <input class="campo__entrada" type="search" name="q" maxlength="100" value="<?= $escapar($busqueda) ?>"
                placeholder="Harina, azúcar, leche…">
        </label>
        <label class="campo">Disponibilidad <select class="campo__entrada" name="estado">
                <option value="" <?= $estado === '' ? 'selected' : '' ?>>Todas</option>
                <option value="bajo" <?= $estado === 'bajo' ? 'selected' : '' ?>>Necesitan reposición</option>
                <option value="correcto" <?= $estado === 'correcto' ? 'selected' : '' ?>>Stock suficiente</option>
            </select>
        </label>
        <button class="boton boton--contorno equipo-stock__buscar">Aplicar filtros</button>
    </form>

    <?php if ($busqueda !== '' || $estado !== ''): ?>
        <div class="equipo-stock__filtros-activos">
            <p><?= $escapar($estadosVisibles[$estado]) ?><?php if ($busqueda !== ''): ?> · Búsqueda: «<?= $escapar($busqueda) ?>»<?php endif; ?></p>
            <a class="boton boton--contorno" href="<?= $escapar($url('equipo-stock')) ?>">Restablecer filtros</a>
        </div>
    <?php endif; ?>

    <p class="equipo-stock__cantidad"><?= $total ?> <?= $total === 1 ? 'materia prima' : 'materias primas' ?> · <?= $escapar($estadosVisibles[$estado]) ?></p>
    <div class="equipo-stock__lista">
        <?php foreach ($materias as $materia): ?>
            <?php
            $esAjusteFallido = $ajusteFallido !== null && $ajusteFallido['id_materia'] === (int) $materia['id_materia'];
            $cantidades = $esAjusteFallido ? $ajusteFallido : $materia;
            // Un error no renueva la versión de los valores que todavía no se guardaron.
            $version = $esAjusteFallido ? $ajusteFallido['version'] : \App\Models\StockMateriaPrima::version($materia);
            ?>
            <article class="tarjeta equipo-stock__item <?= $materia['stock_bajo'] ? 'equipo-stock__item--bajo' : '' ?> <?= $esAjusteFallido ? 'equipo-stock__item--error' : '' ?>"
                aria-labelledby="stock-nombre-<?= (int) $materia['id_materia'] ?>">
                <div class="equipo-stock__item-titulo">
                    <div>
                        <h2 id="stock-nombre-<?= (int) $materia['id_materia'] ?>"><?= $escapar($materia['nombre']) ?></h2>
                        <p>Actualizado <?= $escapar(date('d/m/Y H:i', strtotime($materia['actualizado_en']))) ?></p>
                    </div>
                    <span
                        class="insignia <?= $materia['stock_bajo'] ? 'insignia--alerta' : 'insignia--exito' ?>"><?= $materia['stock_bajo'] ? 'Necesita reposición' : 'Stock suficiente' ?></span>
                </div>
                <p class="equipo-stock__guardado">Guardado: <?= $escapar(str_replace('.', ',', $materia['stock_actual'])) ?> <?= $escapar($materia['unidad']) ?> disponibles · mínimo <?= $escapar(str_replace('.', ',', $materia['stock_minimo'])) ?> <?= $escapar($materia['unidad']) ?>.</p>
                <?php if ($esAjusteFallido): ?>
                    <p class="equipo-stock__error-ajuste" id="stock-error-<?= (int) $materia['id_materia'] ?>">El ajuste no se guardó. Revisá el aviso y las cantidades ingresadas. Para cargar los valores guardados, usá el enlace del aviso.</p>
                <?php endif; ?>
                <form class="equipo-stock__actualizar" method="post" action="<?= $escapar($url('equipo-stock', $filtrosListado)) ?>"
                    aria-labelledby="stock-nombre-<?= (int) $materia['id_materia'] ?>"
                    aria-describedby="stock-ajuste-ayuda<?= $esAjusteFallido ? ' stock-error-' . (int) $materia['id_materia'] : '' ?>"
                    data-stock-guardar="Guardando stock…">
                    <input type="hidden" name="csrf" value="<?= $escapar($csrf) ?>">
                    <input type="hidden" name="accion" value="actualizar">
                    <input type="hidden" name="id_materia" value="<?= (int) $materia['id_materia'] ?>">
                    <input type="hidden" name="version"
                        value="<?= $escapar($version) ?>">
                    <label class="campo">
                        <span class="campo-obligatorio__etiqueta">Disponible (<?= $escapar($materia['unidad']) ?>) <span
                                class="campo-obligatorio" aria-hidden="true" title="Campo obligatorio">*</span></span>
                        <input class="campo__entrada" type="text" inputmode="decimal" name="stock_actual" maxlength="13"
                            pattern="[0-9]{1,9}([.,][0-9]{1,3})?" aria-describedby="stock-cantidades-ayuda"
                            required value="<?= $escapar($cantidades['stock_actual']) ?>">
                    </label>
                    <label class="campo">
                        <span class="campo-obligatorio__etiqueta">Mínimo (<?= $escapar($materia['unidad']) ?>) <span
                                class="campo-obligatorio" aria-hidden="true" title="Campo obligatorio">*</span></span>
                        <input class="campo__entrada" type="text" inputmode="decimal" name="stock_minimo" maxlength="13"
                            pattern="[0-9]{1,9}([.,][0-9]{1,3})?" aria-describedby="stock-cantidades-ayuda"
                            required value="<?= $escapar($cantidades['stock_minimo']) ?>">
                    </label>
                    <button class="boton boton--contorno" aria-label="Guardar stock de <?= $escapar($materia['nombre']) ?>">Guardar stock</button>
                </form>
            </article>
        <?php endforeach; ?>
        <?php if (!$materias): ?>
            <section class="tarjeta vacio equipo-stock__vacio">
                <h2 class="vacio__titulo">No hay materias primas para mostrar</h2>
                <p class="vacio__descripcion"> <?= $total === 0 && $busqueda === '' && $estado === '' ? 'Agregá la primera materia prima para comenzar a controlar el inventario.' : 'Probá con otra búsqueda o quitá algún filtro.' ?> </p>
                <?php if ($busqueda !== '' || $estado !== ''): ?>
                    <a class="boton boton--contorno" href="<?= $escapar($url('equipo-stock')) ?>">Ver todo el inventario</a>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>

    <?php if ($paginas > 1): ?>
        <nav class="paginacion" aria-label="Páginas del stock">
            <?php if ($pagina > 1): ?>
                <a class="boton boton--contorno"
                    href="<?= $escapar($url('equipo-stock', ['q' => $busqueda, 'estado' => $estado, 'pagina' => $pagina - 1])) ?>"> Anterior</a>
            <?php endif; ?>
            <span>Página <?= $pagina ?> de <?= $paginas ?></span>
            <?php if ($pagina < $paginas): ?>
                <a class="boton boton--contorno"
                    href="<?= $escapar($url('equipo-stock', ['q' => $busqueda, 'estado' => $estado, 'pagina' => $pagina + 1])) ?>">Siguiente </a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</main>
