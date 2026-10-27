<section class="pedido-equipo__historial" aria-labelledby="historial-pedido">
    <h2 class="pedido-equipo__titulo" id="historial-pedido">Historial de modificaciones</h2>
    <p class="pedido-equipo__ayuda">Se conservan los valores anteriores y nuevos de cada corrección.</p>
    <?php if (!$historial['cambios']): ?>
    <p class="pedido-equipo__ayuda">Este pedido todavía no tiene modificaciones registradas.</p>
    <?php endif; ?>
    <?php foreach ($historial['cambios'] as $cambio): ?>
    <details class="tarjeta pedido-equipo__cambio">
        <summary class="pedido-equipo__cambio-titulo"><?= $escapar(date('d/m/Y H:i', (int) $cambio['creado_en_unix'])) ?> · <?= $escapar($cambio['nombre_responsable']) ?></summary>
        <?php if ($cambio['motivo'] !== ''): ?>
        <p class="pedido-equipo__motivo"><strong>Motivo:</strong> <?= $escapar($cambio['motivo']) ?></p>
        <?php endif; ?>
        <dl class="pedido-equipo__diferencias">
            <?php foreach (['cliente_nombre' => 'Cliente', 'telefono' => 'Teléfono', 'fecha_retiro' => 'Fecha de retiro',
                'hora_retiro' => 'Hora de retiro', 'notas' => 'Notas', 'estado' => 'Estado', 'total_centavos' => 'Total'] as $campo => $etiqueta): ?>
            <?php if ($cambio['antes'][$campo] === $cambio['despues'][$campo]) continue; ?>
            <div class="pedido-equipo__diferencia">
                <dt><?= $escapar($etiqueta) ?></dt>
                <?php foreach (['antes' => 'Antes', 'despues' => 'Después'] as $momento => $nombreMomento): ?>
                <?php $valor = $cambio[$momento][$campo]; ?>
                <dd><strong><?= $nombreMomento ?>:</strong> <?= $campo === 'total_centavos' ? $dinero($valor) : $escapar($valor === '' ? 'Sin información' : $valor) ?></dd>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </dl>
        <?php if ($cambio['antes']['lineas'] !== $cambio['despues']['lineas']): ?>
        <div class="pedido-equipo__comparacion">
            <?php foreach (['antes' => 'Productos anteriores', 'despues' => 'Productos después del cambio'] as $momento => $nombreMomento): ?>
            <div>
                <h3 class="pedido-equipo__subtitulo"><?= $nombreMomento ?></h3>
                <ul class="pedido-equipo__detalle">
                    <?php foreach ($cambio[$momento]['lineas'] as $linea): ?>
                    <li><?= $escapar($linea['nombre']) ?> × <?= $linea['cantidad'] ?> · <?= $dinero($linea['precio']) ?> c/u</li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </details>
    <?php endforeach; ?>
    <?php if ($historial['paginas'] > 1): ?>
    <nav class="acciones" aria-label="Páginas del historial">
        <?php if ($historial['pagina'] > 1): ?>
        <a class="boton boton--contorno" href="<?= $escapar($url('equipo-pedido-editar', ['id' => $pedido['id_pedido'], 'historial_pagina' => $historial['pagina'] - 1])) ?>#historial-pedido">← Anterior</a>
        <?php endif; ?>
        <span>Página <?= $historial['pagina'] ?> de <?= $historial['paginas'] ?></span>
        <?php if ($historial['pagina'] < $historial['paginas']): ?>
        <a class="boton boton--contorno" href="<?= $escapar($url('equipo-pedido-editar', ['id' => $pedido['id_pedido'], 'historial_pagina' => $historial['pagina'] + 1])) ?>#historial-pedido">Siguiente →</a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>
</section>
