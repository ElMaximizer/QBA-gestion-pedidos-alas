    <?php if ($esPanel): require __DIR__ . '/equipo-pie.php'; else: ?>
    <footer class="pie-pagina">
        <div class="contenedor">
            <div class="pie-pagina__columnas">
                <div class="pie-pagina__identidad">
                    <span class="etiqueta pie-pagina__etiqueta">PANADERÍA ARTESANAL</span>
                    <h2 class="pie-pagina__titulo"><a class="pie-pagina__marca" href="<?= $url('inicio') ?>">PANADERÍA RUSTIC</a></h2>
                    <p class="pie-pagina__lema">El sabor de siempre,<br>hecho para compartir.</p>
                </div>
                <div class="pie-pagina__navegacion">
                    <nav aria-labelledby="pie-legal">
                        <h3 class="pie-pagina__titulo-seccion" id="pie-legal">Aviso legal</h3>
                        <ul class="pie-pagina__enlaces">
                            <li><a class="pie-pagina__enlace" href="<?= $url('aviso-legal') ?>#terminos">Términos y condiciones</a></li>
                            <li><a class="pie-pagina__enlace" href="<?= $url('aviso-legal') ?>#privacidad">Política de privacidad</a></li>
                        </ul>
                    </nav>
                    <nav aria-labelledby="pie-comprar">
                        <h3 class="pie-pagina__titulo-seccion" id="pie-comprar">Comprar</h3>
                        <ul class="pie-pagina__enlaces">
                            <li><a class="pie-pagina__enlace" href="<?= $url('como-comprar') ?>#como-comprar">¿Cómo comprar?</a></li>
                            <li><a class="pie-pagina__enlace" href="<?= $url('como-comprar') ?>#metodos-de-pago">Métodos de pago</a></li>
                            <li><a class="pie-pagina__enlace" href="<?= $url('como-comprar') ?>#envios">Envíos</a></li>
                            <li><a class="pie-pagina__enlace" href="<?= $url('como-comprar') ?>#devoluciones">Devoluciones</a></li>
                        </ul>
                    </nav>
                    <nav class="pie-pagina__secciones" aria-labelledby="pie-secciones">
                        <h3 class="pie-pagina__titulo-seccion" id="pie-secciones">Secciones</h3>
                        <ul class="pie-pagina__enlaces">
                            <li><a class="pie-pagina__enlace" href="<?= $url('inicio') ?>">Inicio</a></li>
                            <li><a class="pie-pagina__enlace" href="<?= $url('productos') ?>">Productos</a></li>
                            <li><a class="pie-pagina__enlace" href="<?= $url('nosotros') ?>">Nosotros</a></li>
                            <li><a class="pie-pagina__enlace" href="<?= $url('contacto') ?>">Contacto</a></li>
                        </ul>
                    </nav>
                </div>
                <section class="pie-pagina__contacto" aria-labelledby="pie-contacto">
                    <h3 class="pie-pagina__titulo-seccion" id="pie-contacto">Contacto</h3>
                    <div class="pie-pagina__datos">
                        <address class="pie-pagina__descripcion">Uruguay 1486 | MAR a DOM 15:30 a 19:30<br>Salto, Uruguay</address>
                        <div class="pie-pagina__canales">
                            <a class="pie-pagina__enlace" href="tel:+59898788807" aria-label="Llamar al 098 788 807">098 788 807</a>
                            <a class="pie-pagina__enlace pie-pagina__red" href="https://www.instagram.com/rustic.pan.salto/" target="_blank" rel="noopener noreferrer" aria-label="Panadería Rustic en Instagram (se abre en otra pestaña)" title="Instagram">
                                <svg class="pie-pagina__icono-red" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" focusable="false">
                                    <rect x="3" y="3" width="18" height="18" rx="5" />
                                    <circle cx="12" cy="12" r="4" />
                                    <circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none" />
                                </svg>
                            </a>
                        </div>
                    </div>
                    <a class="boton boton--dorado pie-pagina__boton" href="<?= $url('contacto') ?>">Escribinos <span aria-hidden="true">↗</span></a>
                </section>
            </div>
            <div class="pie-pagina__base">
                <small class="pie-pagina__legal">© <?= date('Y') ?> Panadería Rustic. Todos los derechos reservados.</small>
                <a class="pie-pagina__acceso" href="<?= $url($equipoActual ? 'equipo-panel' : 'equipo-login') ?>">Acceso al equipo <span aria-hidden="true">→</span></a>
            </div>
        </div>
    </footer>
    <?php require __DIR__ . '/carrito-panel.php'; ?>
    <?php endif; ?>
</body>
</html>
