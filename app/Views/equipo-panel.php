<main id="contenido" class="contenedor registro-pagina">
    <a class="enlace-volver" href="index.php">← Volver al sitio</a>
    <div class="registro">
        <?php require __DIR__ . '/partials/equipo-historia.php'; ?>
        <section class="tarjeta registro__tarjeta">
            <span class="etiqueta registro__etiqueta">ACCESO DEL EQUIPO</span>
            <h1 class="registro__titulo">Hola, <?= $escapar($usuario['nombre']) ?></h1>
            <p>Ingresaste al equipo de Panadería Alas.</p>
            <div class="recuadro">
                <strong>Cuenta aprobada</strong>
                <p class="texto-pequeno">Tu rol: <?= $escapar($nombreRol) ?>.</p>
            </div>
            <form method="post" action="<?= $url('equipo-cerrar-sesion') ?>" class="pila">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                <button class="boton boton--contorno boton--completo">Cerrar sesión del equipo</button>
            </form>
        </section>
    </div>
</main>
