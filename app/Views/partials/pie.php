    <?php if ($esPanel): require __DIR__ . '/equipo-pie.php'; else: ?>
    <footer class="r-footer">
        <div class="r-wrap">
            <div class="r-footer__top"><div><a class="r-footer__logo" href="<?= $url('inicio') ?>"><img src="<?= $escapar($recurso('images/rustic/logo-blanco.png')) ?>" alt="Panadería artesanal Rustic, Salto Uruguay" width="1200" height="1200" loading="lazy"></a><p>El sabor de siempre.<br>Las ganas de compartir.</p></div><nav aria-label="Enlaces de la panadería"><a href="<?= $url('productos') ?>">Nuestros productos ↗</a><a href="<?= $url('nosotros') ?>">Nuestra historia ↗</a><a href="<?= $url('contacto') ?>">Contacto ↗</a><a href="<?= $url('como-comprar') ?>">Cómo comprar ↗</a></nav><div class="r-footer__social"><a href="https://www.instagram.com/rustic.pan.salto/" target="_blank" rel="noopener noreferrer">Instagram ↗</a><a href="https://wa.me/59893555459" target="_blank" rel="noopener noreferrer">WhatsApp ↗</a><a href="tel:+59898734738">098 734 738</a></div></div>
            <div class="r-footer__base"><small>© <?= date('Y') ?> Rustic · Salto, Uruguay</small><a href="<?= $url('aviso-legal') ?>">Términos y privacidad</a><a href="<?= $url($equipoActual ? 'equipo-panel' : 'equipo-login') ?>">Acceso al equipo ↗</a></div>
        </div>
    </footer>
    <?php require __DIR__ . '/carrito-panel.php'; ?>
    <?php endif; ?>
</body>
</html>
