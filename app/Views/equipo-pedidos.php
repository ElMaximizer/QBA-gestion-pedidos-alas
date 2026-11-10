<main id="contenido" class="contenedor panel equipo-pedidos">
    <div class="titulo-pagina">
        <span class="etiqueta">ADMINISTRACIÓN</span>
        <h1 class="titulo-pagina__titulo">Consultar pedidos</h1>
        <p class="titulo-pagina__descripcion">Buscá pedidos y revisá los datos del cliente, la entrega, los productos y el estado.</p>
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
        <label class="campo equipo-pedidos__busqueda">Buscar pedido <input class="campo__entrada" type="search" name="q" maxlength="100" value="<?= $escapar($busqueda) ?>"
                placeholder="Número, cliente, teléfono o producto">
        </label>
        <label class="campo">Estado <select class="campo__entrada" name="estado">
                <?php foreach (['' => 'Todos'] + array_combine($estados, $estados) as $valor => $etiqueta): ?>
                    <option value="<?= $escapar($valor) ?>" <?= $estado === $valor ? 'selected' : '' ?>> <?= $escapar($etiqueta) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="campo">Fecha de retiro o entrega <select class="campo__entrada" name="fecha">
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
            <?php
            $claseEstado = 'insignia--neutra';
            if ($pedido['estado'] === 'Entregado') {
                $claseEstado = 'insignia--exito';
            }
            ?>
            <article class="tarjeta equipo-pedido">
                <div class="equipo-pedido__titulo">
                    <h2>Pedido #<?= $pedido['id_pedido'] ?></h2>
                    <span class="insignia <?= $claseEstado ?>"><?= $escapar($pedido['estado']) ?></span>
                </div>
                <dl class="equipo-pedido__datos">
                    <div>
                        <dt>Cliente </dt>
                        <dd><?= $escapar($pedido['cliente_nombre']) ?> </dd>
                    </div>
                    <div>
                        <dt>Teléfono </dt>
                        <dd><?= $escapar($pedido['telefono']) ?> </dd>
                    </div>
                    <div>
                        <dt><?= ($pedido['modalidad_entrega'] ?? 'retiro') === 'domicilio' ? 'Entrega' : 'Retiro' ?> </dt>
                        <dd><?= $escapar(date('d/m/Y', strtotime($pedido['fecha_retiro']))) ?> · <?= $escapar(substr((string) $pedido['hora_retiro'], 0, 5)) ?> </dd>
                    </div>
                    <div>
                        <dt>Modalidad</dt>
                        <dd>
                            <?php if (($pedido['modalidad_entrega'] ?? 'retiro') === 'domicilio'): ?>
                                Envío a domicilio · sin costo
                                <br><?= $escapar($pedido['direccion_entrega']) ?>
                            <?php else: ?>
                                Retiro en el local
                            <?php endif; ?>
                        </dd>
                    </div>
                    <div>
                        <dt>Total </dt>
                        <dd><?= $dinero($pedido['total_centavos']) ?> </dd>
                    </div>
                    <div class="equipo-pedido__pago">
                        <dt>Pago total</dt>
                        <dd>
                            <span class="equipo-pedido__metodo-pago">
                                <?= $pedido['metodo_pago'] === 'transferencia' ? 'Transferencia' : 'Pago en el local' ?>
                            </span>
                            <span class="insignia <?= $pedido['estado_pago'] === 'pagado' ? 'insignia--exito' : 'insignia--neutra' ?>">
                                <?= $pedido['estado_pago'] === 'pagado' ? 'Pagado' : 'Pendiente' ?>
                            </span>
                            <?php if ($puedeRegistrarPago): ?>
                                <form class="equipo-pedido__pago-form" method="post"
                                    action="<?= $escapar($url('equipo-pedidos')) ?>">
                                    <input type="hidden" name="csrf" value="<?= $escapar($csrf) ?>">
                                    <input type="hidden" name="id_pedido" value="<?= $pedido['id_pedido'] ?>">
                                    <input type="hidden" name="accion" value="registrar_pago">
                                    <input type="hidden" name="pago_recibido" value="0">
                                    <label class="equipo-pedido__pago-opcion">
                                        <input type="checkbox" name="pago_recibido" value="1"
                                            <?= $pedido['estado_pago'] === 'pagado' ? 'checked' : '' ?>> Pago total recibido
                                    </label>
                                    <button class="boton boton--contorno equipo-pedido__pago-guardar" type="submit">Guardar pago</button>
                                </form>
                            <?php endif; ?>
                        </dd>
                    </div>
                    <div class="equipo-pedido__productos">
                        <dt>Productos </dt>
                        <dd><?= $escapar($pedido['productos']) ?> </dd>
                    </div>
                    <?php if (trim((string) $pedido['notas']) !== ''): ?>
                        <div class="equipo-pedido__notas">
                            <dt>Indicaciones del cliente</dt>
                            <dd><?= nl2br($escapar($pedido['notas'])) ?></dd>
                        </div>
                    <?php endif; ?>
                </dl>
                <?php if ($puedeActualizarEstado && $pedido['estados_siguientes'] !== []): ?>
                    <form class="equipo-pedido__estado-form" method="post" action="<?= $escapar($url('equipo-pedidos')) ?>">
                        <input type="hidden" name="csrf" value="<?= $escapar($csrf) ?>">
                        <input type="hidden" name="accion" value="actualizar_estado">
                        <input type="hidden" name="id_pedido" value="<?= $pedido['id_pedido'] ?>">
                        <label class="campo">
                            <span>Avanzar estado</span>
                            <select class="campo__entrada" name="estado_nuevo" required>
                                <?php foreach ($pedido['estados_siguientes'] as $estadoSiguiente): ?>
                                    <option value="<?= $escapar($estadoSiguiente) ?>"><?= $escapar($estadoSiguiente) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <button class="boton boton--dorado" type="submit">
                            <?= in_array('Entregado', $pedido['estados_siguientes'], true) ? 'Guardar avance / registrar entrega' : 'Actualizar estado' ?>
                        </button>
                    </form>
                <?php endif; ?>
                <div class="equipo-pedido__acciones">
                    <a class="boton boton--contorno"
                        href="<?= $escapar($url('equipo-pedido-detalle', ['id' => $pedido['id_pedido']])) ?>">Ver detalle</a>
                    <?php if ($pedido['puede_cancelar']): ?>
                        <form class="equipo-pedido__cancelar-form" method="post"
                            action="<?= $escapar($url('equipo-pedidos')) ?>"
                            data-confirmar-cancelacion data-numero-pedido="<?= (int) $pedido['id_pedido'] ?>">
                            <input type="hidden" name="csrf" value="<?= $escapar($csrf) ?>">
                            <input type="hidden" name="accion" value="cancelar_pedido">
                            <input type="hidden" name="id_pedido" value="<?= (int) $pedido['id_pedido'] ?>">
                            <button class="boton equipo-pedido__cancelar" type="submit">Cancelar pedido</button>
                        </form>
                    <?php endif; ?>
                <?php if ($pedido['puede_modificar']): ?>
                    <a class="boton boton--contorno"
                        href="<?= $escapar($url('equipo-pedido-editar', ['id' => $pedido['id_pedido']])) ?>">Ver / modificar pedido</a>
                <?php endif; ?>
                </div>
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
                <a class="boton boton--contorno"
                    href="<?= $escapar($url('equipo-pedidos', ['q' => $busqueda, 'estado' => $estado, 'fecha' => $fecha, 'pagina' => $pagina - 1])) ?>">← Anterior</a>
            <?php endif; ?>
            <span>Página <?= $pagina ?> de <?= $paginas ?></span>
            <?php if ($pagina < $paginas): ?>
                <a class="boton boton--contorno"
                    href="<?= $escapar($url('equipo-pedidos', ['q' => $busqueda, 'estado' => $estado, 'fecha' => $fecha, 'pagina' => $pagina + 1])) ?>">Siguiente →</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>

    <dialog class="confirmacion-cancelacion" data-dialogo-cancelacion aria-labelledby="confirmacion-cancelacion-titulo">
        <section class="confirmacion-cancelacion__contenido">
            <span class="etiqueta">CANCELAR PEDIDO</span>
            <h2 class="confirmacion-cancelacion__titulo" id="confirmacion-cancelacion-titulo">¿Estás seguro que quieres cancelar?</h2>
            <p class="confirmacion-cancelacion__mensaje" data-mensaje-cancelacion></p>
            <div class="confirmacion-cancelacion__acciones">
                <button class="boton boton--contorno" type="button" data-cerrar-cancelacion>Volver</button>
                <button class="boton confirmacion-cancelacion__confirmar" type="button" data-aceptar-cancelacion>Sí, cancelar pedido</button>
            </div>
        </section>
    </dialog>
</main>
