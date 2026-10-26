<main id="contenido" class="contenedor pagina-pedidos">
    <a class="enlace-volver" href="<?= $url('carrito') ?>">← Volver al carrito</a>
    <div class="titulo-pagina">
        <h1 class="titulo-pagina__titulo">Realizar pedido</h1>
        <p class="titulo-pagina__descripcion">Completa tus datos para coordinar el retiro.</p>
    </div>
    <form method="post" action="<?= $url('realizar-pedido') ?>" class="pedido-distribucion">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="pedido_token" value="<?= $escapar($token) ?>">
        <div class="tarjeta pila">
            <h2 class="tarjeta__titulo">Cliente y retiro</h2>
            <?php if ($error): ?>
            <div class="aviso aviso--error" role="alert"><?= $escapar($error) ?></div>
            <?php endif; ?>
            <label class="campo">Nombre del cliente *
                <input class="campo__entrada" name="cliente_nombre" value="<?= $escapar($datos['cliente_nombre']) ?>" maxlength="120" required autocomplete="name">
            </label>
            <label class="campo">Teléfono *
                <input class="campo__entrada" type="tel" name="telefono" value="<?= $escapar($datos['telefono']) ?>" maxlength="30" required autocomplete="tel">
            </label>
            <label class="campo">Fecha de retiro *
                <input class="campo__entrada" type="date" name="fecha_retiro" value="<?= $escapar($datos['fecha_retiro']) ?>" min="<?= date('Y-m-d') ?>" required>
            </label>
            <label class="campo">Hora de retiro *
                <input class="campo__entrada" type="time" name="hora_retiro" value="<?= $escapar($datos['hora_retiro']) ?>" required>
            </label>
            <label class="campo">Notas del pedido
                <textarea class="campo__entrada campo__entrada--multilinea" name="notas" rows="4" maxlength="2000"><?= $escapar($datos['notas']) ?></textarea>
            </label>
        </div>
        <aside class="tarjeta resumen">
            <h2 class="resumen__titulo">Resumen</h2>
            <?php foreach ($articulos as $articulo): ?>
            <div class="resumen__linea"><span><?= $escapar($articulo['nombre']) ?> × <?= $articulo['cantidad'] ?></span><strong><?= $dinero($articulo['subtotal']) ?></strong></div>
            <?php endforeach; ?>
            <hr class="separador">
            <h2 class="resumen__titulo">Total <?= $dinero($total) ?></h2>
            <?php if ($puedeConfirmar): ?>
            <button class="boton boton--dorado boton--completo">Confirmar pedido</button>
            <?php else: ?>
            <a class="boton boton--dorado boton--completo" href="<?= $url('carrito') ?>">Revisar carrito</a>
            <?php endif; ?>
            <p class="texto-pequeno resumen__texto">El pedido queda pendiente de confirmación por el equipo. No se realiza ningún cobro en línea.</p>
        </aside>
    </form>
</main>
