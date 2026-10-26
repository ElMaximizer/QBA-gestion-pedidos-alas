<main id="contenido" class="contenedor panel mercaderia">
    <a class="enlace-volver" href="<?= $url('equipo-mercaderia') ?>">← Entregas de mercadería</a>
    <div class="titulo-pagina">
        <span class="etiqueta">ADMINISTRACIÓN · STOCK</span>
        <h1 class="titulo-pagina__titulo">Registrar mercadería</h1>
        <p class="titulo-pagina__descripcion">Registrá lo que recibiste del proveedor para sumarlo al inventario.</p>
    </div>
    <?php if ($error !== ''): ?><div class="aviso aviso--error" role="alert"><?= $escapar($error) ?></div><?php endif; ?>
    <?php if (!$materias): ?>
    <section class="tarjeta vacio mercaderia__vacio">
        <h2 class="vacio__titulo">Primero agregá las materias primas</h2>
        <p class="vacio__descripcion">El inventario define el nombre y la unidad de cada insumo que podés recibir.</p>
        <a class="boton boton--dorado" href="<?= $url('equipo-stock') ?>">Ir al stock</a>
    </section>
    <?php else: ?>
    <form class="mercaderia__formulario" method="post" action="<?= $url('mercaderia-registrar') ?>" data-mercaderia-form data-maximo="<?= $maximoLineas ?>">
        <input type="hidden" name="csrf" value="<?= $escapar($csrf) ?>">
        <input type="hidden" name="token_formulario" value="<?= $escapar($token) ?>">
        <section class="tarjeta mercaderia__seccion" aria-labelledby="mercaderia-datos">
            <h2 id="mercaderia-datos">Datos de la entrega</h2>
            <div class="mercaderia__datos">
                <label class="campo">Proveedor
                    <input class="campo__entrada" name="proveedor" maxlength="120" required value="<?= $escapar($valores['proveedor']) ?>" placeholder="Nombre del proveedor">
                </label>
                <label class="campo">Fecha de recepción
                    <input class="campo__entrada" type="date" name="fecha_recepcion" min="1000-01-01" max="<?= $hoy ?>" required value="<?= $escapar($valores['fecha_recepcion']) ?>">
                </label>
                <label class="campo mercaderia__comprobante">Comprobante (opcional)
                    <input class="campo__entrada" name="comprobante" maxlength="80" value="<?= $escapar($valores['comprobante']) ?>" placeholder="Número de remito o factura">
                </label>
            </div>
        </section>
        <section class="tarjeta mercaderia__seccion" aria-labelledby="mercaderia-insumos">
            <h2 id="mercaderia-insumos">Mercadería recibida</h2>
            <p class="mercaderia__ayuda">Ingresá cada cantidad en la unidad indicada. Podés incluir hasta <?= $maximoLineas ?> materias primas, sin repetirlas.</p>
            <div class="mercaderia__lineas" data-mercaderia-lineas>
                <?php foreach ($valores['lineas'] as $indice => $linea): ?>
                <fieldset class="mercaderia__linea">
                    <legend class="mercaderia__leyenda">Insumo <?= $indice + 1 ?></legend>
                    <label class="campo">Materia prima y unidad
                        <select class="campo__entrada" name="lineas[<?= $indice ?>][id_materia]" required>
                            <option value="">Seleccioná una materia prima</option>
                            <?php foreach ($materias as $materia): ?>
                            <option value="<?= (int) $materia['id_materia'] ?>" <?= (string) $materia['id_materia'] === $linea['id_materia'] ? 'selected' : '' ?>><?= $escapar($materia['nombre'] . ' (' . $materia['unidad'] . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="campo">Cantidad recibida
                        <input class="campo__entrada" name="lineas[<?= $indice ?>][cantidad]" inputmode="decimal" maxlength="13" required value="<?= $escapar($linea['cantidad']) ?>" placeholder="Ej.: 25,500">
                    </label>
                    <button class="boton-texto boton-texto--rojo mercaderia__quitar" name="quitar_linea" value="<?= $indice ?>" formnovalidate aria-label="Quitar insumo <?= $indice + 1 ?>" <?= count($valores['lineas']) === 1 ? 'disabled' : '' ?>>Quitar</button>
                </fieldset>
                <?php endforeach; ?>
            </div>
            <button class="boton boton--contorno mercaderia__agregar" name="accion" value="agregar-linea" formnovalidate <?= count($valores['lineas']) >= $maximoLineas ? 'disabled' : '' ?>>+ Agregar insumo</button>
            <p class="solo-lectores" role="status" data-mercaderia-aviso></p>
        </section>
        <section class="tarjeta mercaderia__seccion">
            <label class="campo">Observaciones (opcional)
                <textarea class="campo__entrada" name="observaciones" rows="3" maxlength="1000" placeholder="Notas sobre la mercadería recibida"><?= $escapar($valores['observaciones']) ?></textarea>
            </label>
            <p class="mercaderia__ayuda">Al confirmar, las cantidades se sumarán al stock actual y quedará registrado quién recibió la entrega.</p>
            <div class="acciones mercaderia__acciones">
                <button class="boton boton--dorado" name="accion" value="registrar">Confirmar recepción</button>
                <a class="boton boton--contorno" href="<?= $url('equipo-mercaderia') ?>">Cancelar</a>
            </div>
        </section>
    </form>
    <?php endif; ?>
</main>
