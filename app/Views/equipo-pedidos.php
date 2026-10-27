<main id="contenido" class="contenedor panel equipo-pedidos">
    <div class="titulo-pagina">
        <span class="etiqueta">ADMINISTRACIÓN</span>
        <h1 class="titulo-pagina__titulo">Consultar pedidos</h1>
        <p class="titulo-pagina__descripcion">Buscá pedidos y revisá sus productos, fecha de retiro y estado.</p>
    </div>
    <?php if ($puedeRegistrarPedido): ?>
    <div class="acciones">
        <a class="boton boton--dorado" href="<?= $url('equipo-pedido-registrar') ?>">+ Nuevo pedido</a>
    </div>
    <?php endif; ?>

    <?php if ($aviso !== ''): ?>
    <p class="equipo-pedidos__mensaje equipo-pedidos__mensaje--exito" role="status"><?= $escapar($aviso) ?></p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
    <p class="equipo-pedidos__mensaje equipo-pedidos__mensaje--error" role="alert"><?= $escapar($error) ?></p>
    <?php endif; ?>

    <form class="equipo-pedidos__filtros" method="get" action="<?= $escapar($url('equipo-pedidos')) ?>">
        <label class="campo equipo-pedidos__busqueda">Buscar pedido
            <input class="campo__entrada" type="search" name="q" maxlength="100"
                value="<?= $escapar($busqueda) ?>" placeholder="Número, cliente, teléfono o producto">
        </label>
        <label class="campo">Estado
            <select class="campo__entrada" name="estado">
                <?php foreach (['' => 'Todos', 'Pendiente' => 'Pendiente', 'En preparación' => 'En preparación',
                    'Listo para retirar' => 'Listo para retirar', 'Entregado' => 'Entregado', 'Cancelado' => 'Cancelado'] as $valor => $etiqueta): ?>
                <option value="<?= $escapar($valor) ?>" <?= $estado === $valor ? 'selected' : '' ?>><?= $escapar($etiqueta) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="campo">Fecha de retiro
            <select class="campo__entrada" name="fecha">
                <option value="" <?= $fecha === '' ? 'selected' : '' ?>>Todas las fechas</option>
                <option value="hoy" <?= $fecha === 'hoy' ? 'selected' : '' ?>>Hoy</option>
                <option value="proximos" <?= $fecha === 'proximos' ? 'selected' : '' ?>>Desde hoy</option>
                <option value="anteriores" <?= $fecha === 'anteriores' ? 'selected' : '' ?>>Anteriores</option>
            </select>
        </label>
        <button class="boton boton--contorno equipo-pedidos__buscar">Aplicar filtros</button>
    </form>

    <p class="equipo-pedidos__cantidad"><?= $total ?> <?= $total === 1 ? 'pedido encontrado' : 'pedidos encontrados' ?></p>

    <div class="pila">
        <?php foreach ($pedidos as $pedido): ?>
        <?php $claseEstado = match ($pedido['estado']) {
            'Entregado' => 'insignia--exito', default => 'insignia--neutra',
        }; ?>
        <article class="tarjeta equipo-pedido">
            <div class="equipo-pedido__titulo">
                <h2>Pedido #<?= $pedido['id_pedido'] ?></h2>
                <span class="insignia <?= $claseEstado ?>"><?= $escapar($pedido['estado']) ?></span>
            </div>
            <dl class="equipo-pedido__datos">
                <div><dt>Cliente</dt><dd><?= $escapar($pedido['cliente_nombre']) ?></dd></div>
                <div><dt>Teléfono</dt><dd><?= $escapar($pedido['telefono']) ?></dd></div>
                <div><dt>Retiro</dt><dd><?= $escapar(date('d/m/Y', strtotime($pedido['fecha_retiro']))) ?> · <?= $escapar(substr((string) $pedido['hora_retiro'], 0, 5)) ?></dd></div>
                <div><dt>Total</dt><dd><?= $dinero($pedido['total_centavos']) ?></dd></div>
                <div class="equipo-pedido__sena"><dt>Seña</dt><dd>
                    <?php if ($puedeRegistrarSena): ?>
                    <form class="equipo-pedido__sena-form" method="post" action="<?= $escapar($url('equipo-pedidos')) ?>">
                        <input type="hidden" name="csrf" value="<?= $escapar($csrf) ?>">
                        <input type="hidden" name="id_pedido" value="<?= $pedido['id_pedido'] ?>">
                        <input type="hidden" name="sena_recibida" value="0">
                        <label class="equipo-pedido__sena-opcion">
                            <input type="checkbox" name="sena_recibida" value="1" <?= $pedido['sena_recibida'] ? 'checked' : '' ?>>
                            Seña recibida
                        </label>
                        <button class="boton boton--contorno equipo-pedido__sena-guardar">Guardar</button>
                    </form>
                    <?php else: ?>
                    <?= $pedido['sena_recibida'] ? 'Recibida' : 'Pendiente' ?>
                    <?php endif; ?>
                </dd></div>
                <div class="equipo-pedido__productos"><dt>Productos</dt><dd><?= $escapar($pedido['productos']) ?></dd></div>
            </dl>
        </article>
        <?php endforeach; ?>
        <?php if (!$pedidos): ?>
        <section class="tarjeta vacio">
            <h2 class="vacio__titulo">No encontramos pedidos</h2>
            <p class="vacio__descripcion">Probá con otra búsqueda o quitá alguno de los filtros.</p>
            <a class="boton boton--contorno" href="<?= $escapar($url('equipo-pedidos')) ?>">Ver todos los pedidos</a>
        </section>
        <?php endif; ?>
    </div>

    <?php if ($paginas > 1): ?>
    <nav class="paginacion" aria-label="Páginas de pedidos">
        <?php if ($pagina > 1): ?>
        <a class="boton boton--contorno" href="<?= $escapar($url('equipo-pedidos', ['q' => $busqueda, 'estado' => $estado, 'fecha' => $fecha, 'pagina' => $pagina - 1])) ?>">← Anterior</a>
        <?php endif; ?>
        <span>Página <?= $pagina ?> de <?= $paginas ?></span>
        <?php if ($pagina < $paginas): ?>
        <a class="boton boton--contorno" href="<?= $escapar($url('equipo-pedidos', ['q' => $busqueda, 'estado' => $estado, 'fecha' => $fecha, 'pagina' => $pagina + 1])) ?>">Siguiente →</a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>
</main>
