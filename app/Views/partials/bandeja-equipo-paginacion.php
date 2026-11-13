<?php if ($bandeja['paginas'] > 1): ?>
<nav class="bandeja-equipo__paginacion" aria-label="Páginas de resultados">
    <?php if ($bandeja['pagina'] > 1): ?>
    <a class="boton boton--contorno" rel="prev" href="<?= $escapar($url($rutaBandeja, array_replace($parametrosBandeja, ['pagina' => $bandeja['pagina'] - 1]))) ?>">← Anterior</a>
    <?php endif; ?>
    <span>Página <?= $bandeja['pagina'] ?> de <?= $bandeja['paginas'] ?></span>
    <?php if ($bandeja['pagina'] < $bandeja['paginas']): ?>
    <a class="boton boton--contorno" rel="next" href="<?= $escapar($url($rutaBandeja, array_replace($parametrosBandeja, ['pagina' => $bandeja['pagina'] + 1]))) ?>">Siguiente →</a>
    <?php endif; ?>
</nav>
<?php endif; ?>
