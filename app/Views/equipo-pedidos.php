<main id="contenido" class="contenedor panel equipo-pedidos">
    <?php
    $datosCuentaTransferencia = \App\Core\Transferencia::configuracion();
    $hayFiltros = $busqueda !== '' || $estado !== '' || $fecha !== '';
    $filtrosLimpios = $modoVista === 'lista' ? ['vista' => 'lista'] : [];
    $fechasVisibles = ['hoy' => 'Hoy', 'proximos' => 'Por entregar desde hoy', 'anteriores' => 'Anteriores'];
    ?>
    <div class="titulo-pagina">
        <span class="etiqueta">ADMINISTRACIÓN</span>
        <h1 class="titulo-pagina__titulo">Consultar pedidos</h1>
        <p class="titulo-pagina__descripcion">Buscá pedidos y revisá los datos del cliente, la entrega, los productos y el estado.</p>
    </div>
    <div class="acciones equipo-pedidos__acciones">
        <?php if ($puedeRegistrarPedido): ?>
            <a class="boton boton--dorado" href="<?= $url('equipo-pedido-registrar') ?>">Nuevo pedido</a>
        <?php endif; ?>
        <?php if ($puedeVerEstadisticas): ?>
            <a class="boton boton--contorno" href="<?= $url('equipo-estadisticas') ?>">Estad&#xED;sticas</a>
        <?php endif; ?>
    </div>

    <?php if ($aviso !== ''): ?>
        <p class="equipo-pedidos__mensaje equipo-pedidos__mensaje--exito" role="status" tabindex="-1" data-resultado-pedidos><?= $escapar($aviso) ?></p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="equipo-pedidos__mensaje equipo-pedidos__mensaje--error" role="alert" tabindex="-1" data-resultado-pedidos><?= $escapar($error) ?></p>
    <?php endif; ?>

    <form class="equipo-pedidos__filtros" method="get" action="<?= $escapar($url('equipo-pedidos')) ?>">
        <input type="hidden" name="vista" value="<?= $escapar($modoVista) ?>">
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
                <option value="proximos" <?= $fecha === 'proximos' ? 'selected' : '' ?>>Por entregar desde hoy</option>
                <option value="anteriores" <?= $fecha === 'anteriores' ? 'selected' : '' ?>>Anteriores</option>
            </select>
        </label>
        <button class="boton boton--contorno equipo-pedidos__buscar">Aplicar filtros</button>
    </form>
    <?php if ($hayFiltros): ?>
        <div class="equipo-pedidos__filtros-activos" role="group" aria-label="Filtros aplicados">
            <p>Filtros aplicados:
                <?php if ($busqueda !== ''): ?><span>Búsqueda: «<?= $escapar($busqueda) ?>»</span><?php endif; ?>
                <?php if ($estado !== ''): ?><span>Estado: <?= $escapar($estado) ?></span><?php endif; ?>
                <?php if ($fecha !== ''): ?><span>Fecha: <?= $escapar($fechasVisibles[$fecha]) ?></span><?php endif; ?>
            </p>
            <a class="boton boton--contorno" href="<?= $escapar($url('equipo-pedidos', $filtrosLimpios)) ?>">Limpiar filtros</a>
        </div>
    <?php endif; ?>

    <div class="equipo-pedidos__barra-listado">
        <p class="equipo-pedidos__cantidad"><?= $total ?> <?= $total === 1 ? 'pedido encontrado' : 'pedidos encontrados' ?></p>
        <nav class="equipo-pedidos__selector-vista" aria-label="Modo de visualización de pedidos">
            <a href="<?= $escapar($url('equipo-pedidos', array_replace($filtrosListado, ['vista' => 'cuadricula']))) ?>" <?= $modoVista === 'cuadricula' ? 'aria-current="page"' : '' ?>>Cuadr&#xED;cula</a>
            <a href="<?= $escapar($url('equipo-pedidos', array_replace($filtrosListado, ['vista' => 'lista']))) ?>" <?= $modoVista === 'lista' ? 'aria-current="page"' : '' ?>>Lista</a>
        </nav>
    </div>

    <div class="pila equipo-pedidos__resultados <?= $modoVista === 'lista' ? 'equipo-pedidos__resultados--lista' : 'equipo-pedidos__resultados--cuadricula' ?>">
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
                        <dd><?= $pedido['telefono'] !== '' ? $escapar($pedido['telefono']) : 'No informado' ?> </dd>
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
                        <dt>Forma de pago</dt>
                        <dd>
                            <span class="equipo-pedido__metodo-pago">
                                <?= $pedido['metodo_pago'] === 'transferencia' ? 'Transferencia' : 'Pago en el local' ?>
                            </span>
                            <?php
                            $esTransferencia = $pedido['metodo_pago'] === 'transferencia';
                            $pedidoCancelado = $pedido['estado'] === 'Cancelado';
                            $estadoRevision = $pedido['estado_pago'] === 'pagado'
                                ? 'aprobada'
                                : ($pedido['estado_revision_transferencia'] ?? null);
                            $comprobanteEnviado = $esTransferencia
                                && !empty($pedido['estado_revision_transferencia']);
                            $transferenciaInformada = $comprobanteEnviado && $estadoRevision === 'pendiente';
                            $pagoConfirmado = $pedido['estado_pago'] === 'pagado' || ($esTransferencia && $estadoRevision === 'aprobada');
                            if (!$esTransferencia) {
                                $textoEstadoPago = $pagoConfirmado ? 'Pago realizado' : 'Pago pendiente';
                            } elseif ($estadoRevision === 'aprobada') {
                                $textoEstadoPago = 'Transferencia aprobada';
                            } elseif ($estadoRevision === 'rechazada') {
                                $textoEstadoPago = 'Comprobante rechazado';
                            } elseif ($comprobanteEnviado) {
                                $textoEstadoPago = 'Comprobante por revisar';
                            } else {
                                $textoEstadoPago = !empty($pedido['transferencia_enviada_en']) ? 'Comprobante no disponible' : 'Comprobante pendiente';
                            }
                            ?>
                            <span class="insignia <?= $pagoConfirmado ? 'insignia--exito' : 'insignia--neutra' ?><?= $esTransferencia && $estadoRevision === 'rechazada' ? ' equipo-pedido__transferencia-rechazada' : '' ?>">
                                <?= $escapar($textoEstadoPago) ?>
                            </span>
                            <?php if ($pagoConfirmado && !empty($pedido['pago_confirmado_en'])): ?>
                                <small>Confirmado el <?= $escapar(date('d/m/Y H:i', strtotime($pedido['pago_confirmado_en']))) ?>.</small>
                            <?php endif; ?>
                            <?php if (!$esTransferencia && !$pagoConfirmado && !$pedidoCancelado && $puedeRegistrarPagoLocal): ?>
                                <small>Confirmá esta acción únicamente cuando hayas recibido el total del pedido en efectivo.</small>
                                <div class="equipo-pedido__transferencia-acciones">
                                    <form method="post" action="<?= $escapar($url('equipo-pedidos', $filtrosListado)) ?>" data-pago-equipo>
                                        <input type="hidden" name="csrf" value="<?= $escapar($csrf) ?>">
                                        <input type="hidden" name="id_pedido" value="<?= (int) $pedido['id_pedido'] ?>">
                                        <input type="hidden" name="accion" value="registrar_pago_local">
                                        <button class="boton boton--dorado" type="submit" aria-label="Confirmar efectivo recibido del pedido #<?= (int) $pedido['id_pedido'] ?>">Confirmar efectivo recibido</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                            <?php if ($esTransferencia && $puedeVerTransferencias): ?>
                                <details class="equipo-pedido__transferencia-datos">
                                    <summary>Ver datos de la transferencia</summary>
                                    <?php if (\App\Core\Transferencia::configurada($datosCuentaTransferencia)): ?>
                                        <dl>
                                            <div><dt>Banco</dt><dd><?= $escapar($datosCuentaTransferencia['banco']) ?></dd></div>
                                            <div><dt>Titular</dt><dd><?= $escapar($datosCuentaTransferencia['titular']) ?></dd></div>
                                            <div><dt>Tipo de cuenta</dt><dd><?= $escapar($datosCuentaTransferencia['tipo_cuenta']) ?></dd></div>
                                            <div><dt>Número de cuenta</dt><dd><?= $escapar($datosCuentaTransferencia['numero_cuenta']) ?></dd></div>
                                            <?php if ($datosCuentaTransferencia['alias'] !== ''): ?><div><dt>Alias</dt><dd><?= $escapar($datosCuentaTransferencia['alias']) ?></dd></div><?php endif; ?>
                                            <div><dt>Moneda</dt><dd><?= $escapar($datosCuentaTransferencia['moneda']) ?></dd></div>
                                        </dl>
                                    <?php else: ?>
                                        <p>La cuenta bancaria todavía no está configurada.</p>
                                    <?php endif; ?>
                                </details>
                            <?php endif; ?>
                            <?php if ($pedidoCancelado): ?>
                                <small>Este pedido está cancelado. No admite nuevos pagos ni revisiones de comprobantes.</small>
                            <?php elseif ($transferenciaInformada): ?>
                                <small>El cliente adjuntó el comprobante y espera la revisión del pago.</small>
                                <?php if ($puedeDecidirTransferencias): ?>
                                    <small>Revisá el comprobante y verificá el ingreso del importe en la cuenta antes de aprobar el pago.</small>
                                <?php endif; ?>
                            <?php elseif ($esTransferencia && $estadoRevision === 'rechazada'): ?>
                                <small>El comprobante fue rechazado. El cliente podrá enviar uno nuevo.</small>
                            <?php elseif ($esTransferencia && !$comprobanteEnviado && !empty($pedido['transferencia_enviada_en'])): ?>
                                <small>El cliente informó la transferencia, pero no encontramos un comprobante adjunto.</small>
                            <?php elseif ($esTransferencia && !$comprobanteEnviado && $pedido['estado_pago'] !== 'pagado'): ?>
                                <small>El cliente todavía no envió el comprobante.</small>
                            <?php endif; ?>
                            <?php if ($comprobanteEnviado && $puedeVerTransferencias): ?>
                                <div class="equipo-pedido__transferencia-acciones">
                                    <a class="boton boton--contorno" href="<?= $escapar($url('equipo-comprobante-transferencia', ['id' => $pedido['id_pedido']])) ?>" target="_blank" rel="noopener" aria-label="Ver comprobante del pedido #<?= (int) $pedido['id_pedido'] ?> (se abre en una pestaña nueva)">Ver comprobante</a>
                                    <?php if ($puedeDecidirTransferencias && $transferenciaInformada && !$pedidoCancelado): ?>
                                        <form method="post" action="<?= $escapar($url('equipo-pedidos', $filtrosListado)) ?>" data-pago-equipo>
                                            <input type="hidden" name="csrf" value="<?= $escapar($csrf) ?>">
                                            <input type="hidden" name="id_pedido" value="<?= (int) $pedido['id_pedido'] ?>">
                                            <input type="hidden" name="accion" value="revisar_transferencia">
                                            <input type="hidden" name="decision" value="aprobar">
                                            <button class="boton boton--dorado" type="submit" aria-label="Aprobar transferencia del pedido #<?= (int) $pedido['id_pedido'] ?>">Aprobar transferencia</button>
                                        </form>
                                        <form method="post" action="<?= $escapar($url('equipo-pedidos', $filtrosListado)) ?>" data-pago-equipo>
                                            <input type="hidden" name="csrf" value="<?= $escapar($csrf) ?>">
                                            <input type="hidden" name="id_pedido" value="<?= (int) $pedido['id_pedido'] ?>">
                                            <input type="hidden" name="accion" value="revisar_transferencia">
                                            <input type="hidden" name="decision" value="rechazar">
                                            <button class="boton boton--contorno" type="submit" aria-label="Rechazar comprobante del pedido #<?= (int) $pedido['id_pedido'] ?>">Rechazar comprobante</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <small>El comprobante se abre en una pestaña nueva.</small>
                            <?php elseif ($transferenciaInformada && !$pedidoCancelado): ?>
                                <small>El comprobante está pendiente de revisión por administración.</small>
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
                <?php if ($puedeActualizarEstado && $pedido['estados_actualizables'] !== []): ?>
                    <?php
                    $estadosDisponibles = array_values(array_diff($pedido['estados_actualizables'], [$pedido['estado']]));
                    $estadoSeleccionado = count($estadosDisponibles) === 1 ? $estadosDisponibles[0] : '';
                    $errorEstado = $idEstadoEnviado === $pedido['id_pedido'] ? $error : '';
                    if ($errorEstado !== '' && in_array($estadoEnviado, $estadosDisponibles, true)) {
                        $estadoSeleccionado = $estadoEnviado;
                    }
                    $idControlEstado = 'pedido-estado-' . $pedido['id_pedido'];
                    ?>
                    <form class="equipo-pedido__estado-form" method="post" action="<?= $escapar($url('equipo-pedidos', $filtrosListado)) ?>"
                        data-confirmar-estado data-numero-pedido="<?= (int) $pedido['id_pedido'] ?>"
                        data-cliente-pedido="<?= $escapar($pedido['cliente_nombre']) ?>"
                        data-estado-actual="<?= $escapar($pedido['estado']) ?>" aria-label="Cambiar estado del pedido #<?= (int) $pedido['id_pedido'] ?>">
                        <input type="hidden" name="csrf" value="<?= $escapar($csrf) ?>">
                        <input type="hidden" name="accion" value="actualizar_estado">
                        <input type="hidden" name="id_pedido" value="<?= $pedido['id_pedido'] ?>">
                        <p class="equipo-pedido__estado-contexto" id="<?= $idControlEstado ?>-actual">Estado actual: <strong><?= $escapar($pedido['estado']) ?></strong></p>
                        <label class="campo" for="<?= $idControlEstado ?>">
                            <span>Nuevo estado <span class="solo-lectores">del pedido #<?= (int) $pedido['id_pedido'] ?></span></span>
                            <select class="campo__entrada" name="estado_nuevo" required id="<?= $idControlEstado ?>"
                                aria-describedby="<?= $idControlEstado ?>-actual<?= $errorEstado !== '' ? ' ' . $idControlEstado . '-error' : '' ?>"
                                <?= $errorEstado !== '' ? 'aria-invalid="true"' : '' ?>>
                                <option value="" <?= $estadoSeleccionado === '' ? 'selected' : '' ?> disabled>Elegí un nuevo estado</option>
                                <?php foreach ($estadosDisponibles as $estadoOpcion): ?>
                                    <option value="<?= $escapar($estadoOpcion) ?>" <?= $estadoOpcion === $estadoSeleccionado ? 'selected' : '' ?>>
                                        <?= $escapar($estadoOpcion) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <button class="boton boton--dorado" type="submit" data-guardar-estado>
                            <?= $estadoSeleccionado !== '' ? 'Cambiar a ' . $escapar($estadoSeleccionado) : 'Guardar nuevo estado' ?>
                        </button>
                        <?php if ($errorEstado !== ''): ?>
                            <p class="equipo-pedido__estado-error" id="<?= $idControlEstado ?>-error"><?= $escapar($errorEstado) ?></p>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
                <div class="equipo-pedido__acciones">
                    <a class="boton boton--contorno"
                        href="<?= $escapar($url('equipo-pedido-detalle', ['id' => $pedido['id_pedido']] + $filtrosListado)) ?>">Ver detalle</a>
                    <?php if ($pedido['puede_cancelar']): ?>
                        <form class="equipo-pedido__cancelar-form" method="post"
                            action="<?= $escapar($url('equipo-pedidos', $filtrosListado)) ?>"
                            data-confirmar-cancelacion data-numero-pedido="<?= (int) $pedido['id_pedido'] ?>"
                            data-cliente-pedido="<?= $escapar($pedido['cliente_nombre']) ?>" data-estado-actual="<?= $escapar($pedido['estado']) ?>">
                            <input type="hidden" name="csrf" value="<?= $escapar($csrf) ?>">
                            <input type="hidden" name="accion" value="cancelar_pedido">
                            <input type="hidden" name="id_pedido" value="<?= (int) $pedido['id_pedido'] ?>">
                            <button class="boton equipo-pedido__cancelar" type="submit" aria-label="Cancelar pedido #<?= (int) $pedido['id_pedido'] ?>">Cancelar pedido</button>
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
                <h2 class="vacio__titulo"><?= $hayFiltros ? 'No encontramos pedidos con estos filtros' : 'Todavía no hay pedidos' ?></h2>
                <p class="vacio__descripcion"><?= $hayFiltros ? 'Probá con otra búsqueda o limpiá los filtros para ver todos los pedidos.' : 'Los pedidos registrados aparecerán acá.' ?></p>
                <?php if ($hayFiltros): ?>
                    <a class="boton boton--contorno" href="<?= $escapar($url('equipo-pedidos', $filtrosLimpios)) ?>">Ver todos los pedidos</a>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>

    <?php if ($paginas > 1): ?>
        <nav class="paginacion" aria-label="Páginas de pedidos">
            <?php if ($pagina > 1): ?>
                <a class="boton boton--contorno"
                    href="<?= $escapar($url('equipo-pedidos', array_replace($filtrosListado, ['pagina' => $pagina - 1]))) ?>"> Anterior</a>
            <?php endif; ?>
            <span>Página <?= $pagina ?> de <?= $paginas ?></span>
            <?php if ($pagina < $paginas): ?>
                <a class="boton boton--contorno"
                    href="<?= $escapar($url('equipo-pedidos', array_replace($filtrosListado, ['pagina' => $pagina + 1]))) ?>">Siguiente </a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>

    <dialog class="confirmacion-cancelacion" data-dialogo-cancelacion aria-labelledby="confirmacion-cancelacion-titulo"
        aria-describedby="confirmacion-cancelacion-cliente confirmacion-cancelacion-mensaje">
        <section class="confirmacion-cancelacion__contenido">
            <span class="etiqueta">CANCELAR PEDIDO</span>
            <h2 class="confirmacion-cancelacion__titulo" id="confirmacion-cancelacion-titulo">¿Cancelar el pedido?</h2>
            <p class="confirmacion-cancelacion__cliente" id="confirmacion-cancelacion-cliente" data-cliente-cancelacion></p>
            <p class="confirmacion-cancelacion__mensaje" id="confirmacion-cancelacion-mensaje" data-mensaje-cancelacion>El pedido quedará como Cancelado. Sus datos y su historial se conservan.</p>
            <div class="confirmacion-cancelacion__acciones">
                <button class="boton boton--contorno" type="button" data-cerrar-cancelacion autofocus>Volver</button>
                <button class="boton confirmacion-cancelacion__confirmar" type="button" data-aceptar-cancelacion>Sí, cancelar pedido</button>
            </div>
        </section>
    </dialog>
</main>
