<main id="contenido" class="contenedor pagina-pedidos">
    <section class="tarjeta vacio">
        <span class="insignia insignia--exito">Pedido registrado</span>
        <h1 class="vacio__titulo">¡Gracias por tu pedido!</h1>
        <p class="vacio__descripcion vacio__descripcion--principal">Tu número es <strong>#<?= $idPedido ?></strong>. El equipo se comunicará para confirmar disponibilidad y retiro.</p>
        <div class="acciones vacio__acciones">
            <a class="boton boton--dorado" href="<?= $url('productos') ?>">Seguir explorando</a>
            <a class="boton boton--contorno" href="index.php">Volver al inicio</a>
        </div>
    </section>
</main>
