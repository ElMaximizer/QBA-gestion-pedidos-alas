    <?php if ($esPanel): require __DIR__ . '/equipo-pie.php'; else: ?>
    <footer class="pie-pagina">
        <div class="contenedor">
            <div class="pie-pagina__columnas">
                <div>
                    <span class="etiqueta pie-pagina__etiqueta">PANADERÍA ARTESANAL</span>
                    <h2 class="pie-pagina__titulo">PANADERÍA ALAS</h2>
                    <p class="pie-pagina__lema">El sabor de siempre,<br>hecho para compartir.</p>
                </div>
                <nav aria-label="Explora Alas">
                    <span class="etiqueta pie-pagina__etiqueta">EXPLORA ALAS</span>
                    <div class="pie-pagina__enlaces">
                        <a class="pie-pagina__enlace" href="<?= $url('inicio') ?>">Inicio</a>
                        <a class="pie-pagina__enlace" href="<?= $url('productos') ?>">Productos</a>
                        <a class="pie-pagina__enlace" role="link" aria-disabled="true">Nosotros</a>
                        <a class="pie-pagina__enlace" role="link" aria-disabled="true">Contacto</a>
                    </div>
                </nav>
                <div class="pie-pagina__consulta">
                    <span class="etiqueta pie-pagina__etiqueta">CONVERSEMOS</span>
                    <h3 class="pie-pagina__subtitulo">¿Tienes un pedido en mente?</h3>
                    <p class="pie-pagina__descripcion">Consulta por disponibilidad, productos y preparaciones especiales.</p>
                    <a class="boton boton--dorado pie-pagina__boton" role="link" aria-disabled="true">Hacer una consulta →</a>
                </div>
            </div>
            <div class="pie-pagina__base">
                <small class="pie-pagina__legal">© <?= date('Y') ?> Panadería Alas</small>
                <a class="pie-pagina__enlace" href="<?= $url($equipoActual ? 'equipo-panel' : 'equipo-login') ?>">Acceso al equipo →</a>
            </div>
        </div>
    </footer>
    <?php endif; ?>
</body>
</html>
