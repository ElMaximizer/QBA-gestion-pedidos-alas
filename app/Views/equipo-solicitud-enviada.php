<main id="contenido" class="contenedor registro-pagina">
    <a class="enlace-volver" href="index.php">← Volver al sitio</a>
    <div class="registro">
        <?php require __DIR__ . '/partials/equipo-historia.php'; ?>
        <section class="tarjeta registro__tarjeta">
            <span class="insignia insignia--exito registro__insignia">Solicitud recibida</span>
            <span class="etiqueta registro__etiqueta">ACCESO DEL EQUIPO</span>
            <h1 class="registro__titulo">Solicitud registrada</h1>
            <p>Recibimos tus datos. El dueño de Panadería Alas debe aprobar tu solicitud antes de que puedas acceder al sistema del equipo.</p>
            <?php if (!$correoEnviado): ?>
            <div class="aviso aviso--error" role="alert">Tu solicitud quedó guardada, pero no pudimos enviar el correo de confirmación. Consultá con el dueño; no hace falta registrarte nuevamente.</div>
            <?php endif; ?>
            <aside class="recuadro">
                <h2 class="recuadro__titulo">Pendiente de aprobación</h2>
                <p class="recuadro__descripcion">El dueño revisará tus datos para continuar.</p>
            </aside>
            <a class="boton boton--dorado boton--completo" href="index.php">Volver al sitio</a>
        </section>
    </div>
</main>
