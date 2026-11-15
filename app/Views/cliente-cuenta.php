<main id="contenido" class="contenedor pagina-pedidos cuenta-ajustes">
    <div class="cuenta-ajustes__cabecera">
        <div>
            <span class="etiqueta">TU ESPACIO EN RUSTIC</span>
            <h1 class="cuenta-ajustes__titulo">Mi cuenta</h1>
            <p class="cuenta-ajustes__descripcion">Tus datos, tus direcciones y la seguridad de tu cuenta, en un solo lugar.</p>
        </div>
        <a class="enlace-volver" href="<?= $url('inicio') ?>">Volver al sitio</a>
    </div>
    <div class="cuenta-ajustes__distribucion">
        <aside class="cuenta-ajustes__lateral">
            <div class="cuenta-ajustes__identidad">
                <span class="etiqueta">CUENTA DE CLIENTE</span>
                <strong><?= $escapar($datos['nombre']) ?></strong>
                <span><?= $escapar($datos['email']) ?></span>
            </div>
            <nav class="cuenta-ajustes__navegacion" aria-label="Configuración de cuenta">
                <?php foreach ($seccionesCuenta as $clave => $titulo): ?>
                    <a class="cuenta-ajustes__enlace<?= $clave === 'privacidad' ? ' cuenta-ajustes__enlace--peligro' : '' ?>"
                        href="<?= $escapar($url('cliente-cuenta', ['seccion' => $clave])) ?>"
                        <?= $seccionCuenta === $clave ? 'aria-current="page"' : '' ?>><?= $escapar($titulo) ?></a>
                <?php endforeach; ?>
            </nav>
            <a class="cuenta-ajustes__pedidos" href="<?= $url('cliente-pedidos') ?>">Ver mis pedidos <span aria-hidden="true">→</span></a>
        </aside>
        <div class="cuenta-ajustes__contenido">
            <?php if ($aviso): ?>
                <div class="aviso aviso--exito" role="status"><?= $escapar($aviso) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="aviso aviso--error" role="alert"><?= $escapar($error) ?></div>
            <?php endif; ?>
            <?php if ($seccionCuenta === 'perfil'): ?>
                <section class="tarjeta cuenta-ajustes__panel pila" aria-labelledby="datos-cuenta">
                    <div>
                        <h2 class="tarjeta__titulo" id="datos-cuenta">Datos personales</h2>
                        <p class="cuenta-ajustes__descripcion">Así te identificamos en la página. Los pedidos anteriores conservan sus datos.</p>
                    </div>
                    <form class="pila cuenta-ajustes__formulario" method="post" action="<?= $escapar($url('cliente-cuenta', ['seccion' => 'perfil'])) ?>">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                        <input type="hidden" name="accion" value="nombre">
                        <label class="campo">Nombre completo
                            <input class="campo__entrada" name="nombre" autocomplete="name" maxlength="120"
                                value="<?= $escapar($valores['nombre'] ?? $datos['nombre']) ?>" required>
                        </label>
                        <label class="campo">Teléfono de contacto (opcional)
                            <input class="campo__entrada" type="tel" name="telefono" autocomplete="tel" maxlength="30"
                                value="<?= $escapar($valores['telefono'] ?? $datos['telefono']) ?>" placeholder="Por ejemplo, 098 123 456">
                            <small class="campo__ayuda">Lo completamos en tus próximos pedidos. Podés cambiarlo para cada compra.</small>
                        </label>
                        <div class="cuenta-ajustes__dato">
                            <span>Correo electrónico</span>
                            <strong><?= $escapar($datos['email']) ?></strong>
                            <a class="enlace-texto" href="<?= $escapar($url('cliente-cuenta', ['seccion' => 'correo'])) ?>">Cambiar correo</a>
                        </div>
                        <button class="boton boton--dorado cuenta-ajustes__guardar">Guardar cambios</button>
                    </form>
                </section>
            <?php elseif ($seccionCuenta === 'direcciones'): ?>
                <section class="tarjeta cuenta-ajustes__panel pila" aria-labelledby="direcciones-cuenta">
                    <div>
                        <h2 class="tarjeta__titulo" id="direcciones-cuenta">Mis direcciones</h2>
                        <p class="cuenta-ajustes__descripcion">Guardá las direcciones que usás para tus pedidos. La principal aparecerá seleccionada al elegir envío a domicilio.</p>
                    </div>
                    <form class="pila cuenta-ajustes__formulario" method="post" action="<?= $escapar($url('cliente-cuenta', ['seccion' => 'direcciones'])) ?>">
                        <h3 class="cuenta-ajustes__subtitulo"><?= $direccionFormulario['id_direccion'] !== '' ? 'Editar dirección' : 'Agregar una dirección' ?></h3>
                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                        <input type="hidden" name="accion" value="guardar-direccion">
                        <input type="hidden" name="id_direccion" value="<?= $escapar($direccionFormulario['id_direccion']) ?>">
                        <label class="campo">Nombre de la dirección
                            <input class="campo__entrada" name="alias" value="<?= $escapar($direccionFormulario['alias']) ?>"
                                maxlength="50" placeholder="Por ejemplo, Casa o Trabajo" required>
                        </label>
                        <label class="campo">Dirección completa
                            <textarea class="campo__entrada campo__entrada--multilinea" name="direccion" rows="3"
                                maxlength="250" autocomplete="street-address" placeholder="Calle, número, apartamento y localidad" required><?= $escapar($direccionFormulario['direccion']) ?></textarea>
                        </label>
                        <label class="campo">Referencias para llegar (opcional)
                            <textarea class="campo__entrada campo__entrada--multilinea" name="referencias" rows="2"
                                maxlength="120" placeholder="Apartamento, piso, entrecalles o indicaciones"><?= $escapar($direccionFormulario['referencias']) ?></textarea>
                            <small class="campo__ayuda">Hasta 120 caracteres. La dirección y sus referencias juntas admiten hasta 250, incluido el rótulo «Referencias».</small>
                        </label>
                        <div class="acciones">
                            <button class="boton boton--dorado"><?= $direccionFormulario['id_direccion'] !== '' ? 'Guardar dirección' : 'Agregar dirección' ?></button>
                            <?php if ($direccionFormulario['id_direccion'] !== ''): ?>
                                <a class="boton boton--contorno" href="<?= $escapar($url('cliente-cuenta', ['seccion' => 'direcciones'])) ?>">Cancelar edición</a>
                            <?php endif; ?>
                        </div>
                    </form>
                    <div class="cuenta-ajustes__direcciones">
                        <?php foreach ($direcciones as $direccion): ?>
                            <article class="cuenta-direccion">
                                <div class="cuenta-direccion__cabecera">
                                    <h3 class="cuenta-ajustes__subtitulo"><?= $escapar($direccion['alias']) ?></h3>
                                    <?php if ($direccion['principal']): ?><span class="insignia insignia--exito">Principal</span><?php endif; ?>
                                </div>
                                <p class="cuenta-direccion__texto"><?= $escapar($direccion['direccion']) ?></p>
                                <?php if ($direccion['referencias'] !== ''): ?>
                                    <p class="cuenta-direccion__texto"><strong>Referencias:</strong> <?= $escapar($direccion['referencias']) ?></p>
                                <?php endif; ?>
                                <div class="cuenta-direccion__acciones">
                                    <a class="boton boton--contorno" href="<?= $escapar($url('cliente-cuenta', ['seccion' => 'direcciones', 'editar' => $direccion['id_direccion']])) ?>">Editar</a>
                                    <?php if (!$direccion['principal']): ?>
                                        <form method="post" action="<?= $escapar($url('cliente-cuenta', ['seccion' => 'direcciones'])) ?>">
                                            <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                            <input type="hidden" name="accion" value="direccion-principal">
                                            <input type="hidden" name="id_direccion" value="<?= $direccion['id_direccion'] ?>">
                                            <button class="boton boton--contorno">Usar como principal</button>
                                        </form>
                                    <?php endif; ?>
                                    <details class="cuenta-direccion__eliminar">
                                        <summary class="enlace-texto">Eliminar</summary>
                                        <form class="pila" method="post" action="<?= $escapar($url('cliente-cuenta', ['seccion' => 'direcciones'])) ?>">
                                            <p>¿Eliminar «<?= $escapar($direccion['alias']) ?>» de tus direcciones? Los pedidos anteriores conservarán su dirección.</p>
                                            <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                            <input type="hidden" name="accion" value="eliminar-direccion">
                                            <input type="hidden" name="id_direccion" value="<?= $direccion['id_direccion'] ?>">
                                            <button class="boton boton--rojo">Confirmar eliminación</button>
                                        </form>
                                    </details>
                                </div>
                            </article>
                        <?php endforeach; ?>
                        <?php if (!$direcciones): ?>
                            <div class="cuenta-ajustes__vacio">
                                <strong>Todavía no tenés direcciones guardadas</strong>
                                <p>Agregá tu primera dirección para usarla en tus próximos pedidos.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            <?php elseif ($seccionCuenta === 'seguridad'): ?>
                <section class="tarjeta cuenta-ajustes__panel pila" aria-labelledby="password-cuenta">
                    <div>
                        <h2 class="tarjeta__titulo" id="password-cuenta">Cambiar contraseña</h2>
                        <p class="cuenta-ajustes__descripcion">Ingresá tu contraseña actual y la nueva para recibir un código en tu correo. Después solo necesitás ese código para confirmar el cambio. Usá al menos 8 caracteres (máximo 72 bytes). Se cerrarán las otras sesiones y se cancelará cualquier cambio de correo pendiente.</p>
                    </div>
                    <p class="cuenta-ajustes__correo">Correo actual: <strong><?= $escapar($datos['email']) ?></strong></p>
                    <form class="pila cuenta-ajustes__formulario" method="post" action="<?= $escapar($url('cliente-cuenta', ['seccion' => 'seguridad'])) ?>">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                        <input type="hidden" name="accion" value="codigo-password">
                        <?php foreach ([
                            ['id' => 'password-actual', 'nombre' => 'password', 'etiqueta' => 'Contraseña actual', 'autocomplete' => 'current-password'],
                            ['id' => 'password-nueva', 'nombre' => 'new_password', 'etiqueta' => 'Nueva contraseña', 'autocomplete' => 'new-password'],
                            ['id' => 'password-confirmacion', 'nombre' => 'confirm_password', 'etiqueta' => 'Confirmar nueva contraseña', 'autocomplete' => 'new-password'],
                        ] as $campoContrasena): ?>
                            <?php require __DIR__ . '/partials/cuenta-contrasena.php'; ?>
                        <?php endforeach; ?>
                        <button class="boton boton--dorado cuenta-ajustes__guardar"><?= $passwordPendiente ? 'Enviar un nuevo código' : 'Enviar código' ?></button>
                        <small class="campo__ayuda">Esperá un minuto antes de solicitar otro código. Tu contraseña actual sigue vigente hasta confirmar.</small>
                    </form>
                    <?php if ($passwordPendiente): ?>
                        <div class="pila cuenta-ajustes__pendiente cuenta-ajustes__formulario">
                            <p>Tenés un cambio de contraseña pendiente. Ingresá el código enviado a tu correo para confirmarlo.</p>
                            <form class="pila" method="post" action="<?= $escapar($url('cliente-cuenta', ['seccion' => 'seguridad'])) ?>">
                                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                <input type="hidden" name="accion" value="password">
                                <label class="campo">Código de verificación
                                    <input class="campo__entrada" name="codigo" inputmode="numeric" autocomplete="one-time-code"
                                        pattern="[0-9]{6}" minlength="6" maxlength="6" required>
                                </label>
                                <small class="campo__ayuda">El código vence en 10 minutos y admite cinco intentos.</small>
                                <button class="boton boton--dorado">Confirmar nueva contraseña</button>
                            </form>
                            <form method="post" action="<?= $escapar($url('cliente-cuenta', ['seccion' => 'seguridad'])) ?>">
                                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                <input type="hidden" name="accion" value="cancelar-password">
                                <button class="boton boton--contorno boton--completo">Cancelar cambio de contraseña</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </section>
            <?php elseif ($seccionCuenta === 'correo'): ?>
                <section class="tarjeta cuenta-ajustes__panel pila" aria-labelledby="correo-cuenta">
                    <div>
                        <h2 class="tarjeta__titulo" id="correo-cuenta">Cambiar correo electrónico</h2>
                        <p class="cuenta-ajustes__descripcion">Tu correo actual seguirá vigente hasta que verifiques el nuevo. El código vence en 10 minutos y admite hasta cinco intentos.</p>
                    </div>
                    <p class="cuenta-ajustes__correo">Correo actual: <strong><?= $escapar($datos['email']) ?></strong></p>
                    <form class="pila cuenta-ajustes__formulario" method="post" action="<?= $escapar($url('cliente-cuenta', ['seccion' => 'correo'])) ?>">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                        <input type="hidden" name="accion" value="correo">
                        <label class="campo">Nuevo correo electrónico
                            <input class="campo__entrada" type="email" name="email" autocomplete="email" maxlength="190"
                                value="<?= $escapar($valores['nuevo_email'] ?? $datos['email_pendiente'] ?? '') ?>" required>
                        </label>
                        <?php
                        $campoContrasena = ['id' => 'correo-password', 'nombre' => 'password', 'etiqueta' => 'Contraseña actual', 'autocomplete' => 'current-password'];
                        require __DIR__ . '/partials/cuenta-contrasena.php';
                        ?>
                        <button class="boton boton--dorado cuenta-ajustes__guardar"><?= $datos['email_pendiente'] ? 'Enviar un nuevo código' : 'Enviar código' ?></button>
                        <small class="campo__ayuda">Esperá un minuto antes de solicitar otro código.</small>
                    </form>
                    <?php if ($datos['email_pendiente']): ?>
                        <div class="pila cuenta-ajustes__pendiente cuenta-ajustes__formulario">
                            <p class="cuenta-ajustes__correo">Correo pendiente: <strong><?= $escapar($datos['email_pendiente']) ?></strong></p>
                            <form class="pila" method="post" action="<?= $escapar($url('cliente-cuenta', ['seccion' => 'correo'])) ?>">
                                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                <input type="hidden" name="accion" value="verificar-correo">
                                <label class="campo">Código de verificación
                                    <input class="campo__entrada" name="codigo" inputmode="numeric" autocomplete="one-time-code"
                                        pattern="[0-9]{6}" minlength="6" maxlength="6" required>
                                </label>
                                <button class="boton boton--dorado">Confirmar nuevo correo</button>
                            </form>
                            <form method="post" action="<?= $escapar($url('cliente-cuenta', ['seccion' => 'correo'])) ?>">
                                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                <input type="hidden" name="accion" value="cancelar-correo">
                                <button class="boton boton--contorno boton--completo">Cancelar cambio de correo</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </section>
            <?php elseif ($seccionCuenta === 'privacidad'): ?>
                <section class="tarjeta cuenta-ajustes__panel pila cuenta-ajustes__peligro" aria-labelledby="eliminar-cuenta">
                    <h2 class="tarjeta__titulo" id="eliminar-cuenta">Eliminar mi cuenta</h2>
                    <p>Se retirarán tus datos del perfil y tus direcciones guardadas. Perderás el acceso y el historial de tu cuenta.</p>
                    <p>Los pedidos y comprobantes se conservarán para la gestión de la panadería. Eliminar tu cuenta no cancela los pedidos pendientes.</p>
                    <a class="boton boton--rojo cuenta-ajustes__guardar" href="<?= $url('cliente-cuenta-eliminar') ?>">Eliminar mi cuenta</a>
                </section>
            <?php endif; ?>
        </div>
    </div>
</main>
