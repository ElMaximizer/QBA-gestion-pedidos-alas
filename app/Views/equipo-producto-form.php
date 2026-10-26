<main id="contenido" class="contenedor panel">
    <div class="titulo-pagina">
        <span class="etiqueta">ADMINISTRACIÓN</span>
        <h1 class="titulo-pagina__titulo"><?= $esEdicion ? 'Editar producto' : 'Agregar producto' ?></h1>
        <p class="titulo-pagina__descripcion">Completa los datos y revisa la información antes de guardar.</p>
    </div>
    <?php if ($error): ?>
    <div class="aviso aviso--error" role="alert"><?= $escapar($error) ?></div>
    <?php endif; ?>
    <a class="enlace-volver" href="<?= $url('equipo-productos') ?>">← Volver a productos</a>
    <form class="producto-formulario" method="post" enctype="multipart/form-data" action="<?= $escapar($url($esEdicion ? 'producto-editar' : 'producto-agregar', $esEdicion ? ['id' => $id] : [])) ?>">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="version" value="<?= $escapar($version) ?>">
        <input type="hidden" name="MAX_FILE_SIZE" value="4194304">
        <section class="tarjeta pila">
            <h2 class="tarjeta__titulo">Información del producto</h2>
            <label class="campo">Nombre *
                <input class="campo__entrada" name="nombre" value="<?= $escapar($valores['nombre']) ?>" placeholder="Nombre del producto" maxlength="120" required>
            </label>
            <label class="campo">Categoría *
                <select class="campo__entrada" name="categoria" data-categoria-producto>
                    <?php foreach ($categorias as $categoria): ?>
                    <option <?= $valores['categoria'] === $categoria ? 'selected' : '' ?>><?= $escapar($categoria) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="campo">Descripción
                <textarea class="campo__entrada campo__entrada--multilinea" name="descripcion" rows="3" maxlength="280" placeholder="Describe el sabor, la textura y la presentación."><?= $escapar($valores['descripcion']) ?></textarea>
                <small class="campo__ayuda">Visible en el catálogo.</small>
            </label>
            <label class="campo">Precio *
                <input class="campo__entrada" type="number" name="precio" min="0.01" max="99999999.99" step="0.01" value="<?= $escapar($valores['precio']) ?>" placeholder="0,00" required>
                <small class="campo__ayuda">Importe en la moneda del catálogo.</small>
            </label>
            <label class="campo">Presentación *
                <input class="campo__entrada" name="presentacion" value="<?= $escapar($valores['presentacion']) ?>" placeholder="Unidad, peso o porciones" maxlength="80" required>
            </label>
            <label class="campo">Ingredientes
                <textarea class="campo__entrada campo__entrada--multilinea" name="ingredientes" rows="3" maxlength="5000" placeholder="Indica los ingredientes."><?= $escapar($valores['ingredientes']) ?></textarea>
            </label>
            <label class="campo">Información de alérgenos
                <input class="campo__entrada" name="alergenos" value="<?= $escapar($valores['alergenos']) ?>" placeholder="Por ejemplo: contiene gluten y leche." maxlength="500">
            </label>
            <label class="campo">Estado
                <select class="campo__entrada" name="activo">
                    <option value="1" <?= (string) $valores['activo'] === '1' ? 'selected' : '' ?>>Activo</option>
                    <option value="0" <?= (string) $valores['activo'] === '0' ? 'selected' : '' ?>>Inactivo</option>
                </select>
                <small class="campo__ayuda">Los productos inactivos no aparecen en el catálogo.</small>
            </label>
            <div class="acciones producto-formulario__acciones">
                <button class="boton boton--dorado"><?= $esEdicion ? 'Guardar cambios' : 'Agregar producto' ?></button>
                <a class="boton boton--contorno" href="<?= $url('equipo-productos') ?>">Cancelar</a>
            </div>
        </section>
        <aside class="tarjeta producto-imagen">
            <h2 class="tarjeta__titulo">Imagen del producto</h2>
            <img class="producto-imagen__vista" src="<?= $escapar($recurso('images/' . $imagen)) ?>" alt="Vista previa del producto" width="400" height="245" data-imagen-producto>
            <label class="boton boton--contorno boton--completo producto-imagen__selector"><?= $esEdicion ? 'Cambiar imagen' : 'Seleccionar imagen' ?>
                <input class="producto-imagen__archivo" type="file" name="imagen" accept="image/png,image/jpeg" data-archivo-producto>
            </label>
            <p class="texto-pequeno producto-imagen__ayuda">JPG o PNG, hasta 4 MB. Elige una imagen clara del producto.</p>
        </aside>
    </form>
</main>
