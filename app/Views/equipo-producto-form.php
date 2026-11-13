<main id="contenido" class="contenedor panel gestion-productos">
    <div class="titulo-pagina">
        <span class="etiqueta">ADMINISTRACIÓN</span>
        <h1 class="titulo-pagina__titulo"><?= $esEdicion ? 'Editar producto' : 'Agregar producto' ?></h1>
        <p class="titulo-pagina__descripcion">Completá los datos y revisá la información antes de guardar.</p>
    </div>
    <?php if ($error): ?>
        <div class="aviso aviso--error" role="alert" tabindex="-1" data-aviso-productos><?= $escapar($error) ?> </div>
    <?php endif; ?>
    <a class="enlace-volver" href="<?= $escapar($url('equipo-productos', $filtrosListado)) ?>"> Volver a productos</a>
    <form class="producto-formulario" method="post" enctype="multipart/form-data" data-gestion-guardar="Guardando producto…"
        action="<?= $escapar($url($esEdicion ? 'producto-editar' : 'producto-agregar', ($esEdicion ? ['id' => $id] : []) + $filtrosListado)) ?>">
        <input type="hidden" name="csrf" value="<?= $escapar($csrf) ?>">
        <input type="hidden" name="version" value="<?= $escapar($version) ?>">
        <input type="hidden" name="MAX_FILE_SIZE" value="4194304">
        <section class="tarjeta pila">
            <h2 class="tarjeta__titulo">Información del producto</h2>
            <label class="campo">
                <span class="campo-obligatorio__etiqueta">Nombre <span class="campo-obligatorio" aria-hidden="true"
                        title="Campo obligatorio">*</span></span>
                <input class="campo__entrada" name="nombre" value="<?= $escapar($valores['nombre']) ?>"
                    placeholder="Nombre del producto" maxlength="120" required>
            </label>
            <label class="campo">
                <span class="campo-obligatorio__etiqueta">Categoría <span class="campo-obligatorio" aria-hidden="true"
                        title="Campo obligatorio">*</span></span>
                <select class="campo__entrada" name="categoria" required data-categoria-producto>
                    <?php foreach ($categorias as $categoria): ?>
                        <option <?= $valores['categoria'] === $categoria ? 'selected' : '' ?>> <?= $escapar($categoria) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="campo">Descripción <textarea class="campo__entrada campo__entrada--multilinea" name="descripcion" rows="3" maxlength="280"
                    placeholder="Describí el sabor, la textura y la presentación."><?= $escapar($valores['descripcion']) ?></textarea>
                <small class="campo__ayuda">Visible en el catálogo.</small>
            </label>
            <label class="campo">
                <span class="campo-obligatorio__etiqueta">Precio <span class="campo-obligatorio" aria-hidden="true"
                        title="Campo obligatorio">*</span></span>
                <input class="campo__entrada" type="text" inputmode="decimal" name="precio" maxlength="11"
                    pattern="(?!0+([.,]0{1,2})?$)[0-9]{1,8}([.,][0-9]{1,2})?" aria-describedby="producto-precio-ayuda"
                    value="<?= $escapar($valores['precio']) ?>" placeholder="0,00" required>
                <small class="campo__ayuda" id="producto-precio-ayuda">Ingresá un precio mayor que cero, con coma o punto y hasta dos decimales. Ejemplo: 125,50.</small>
            </label>
            <label class="campo">
                <span class="campo-obligatorio__etiqueta">Presentación <span class="campo-obligatorio"
                        aria-hidden="true" title="Campo obligatorio">*</span></span>
                <input class="campo__entrada" name="presentacion" value="<?= $escapar($valores['presentacion']) ?>"
                    placeholder="Unidad, peso o porciones" maxlength="80" required>
            </label>
            <label class="campo">Ingredientes <textarea class="campo__entrada campo__entrada--multilinea" name="ingredientes" rows="3"
                    maxlength="5000"
                    placeholder="Indicá los ingredientes."><?= $escapar($valores['ingredientes']) ?></textarea>
            </label>
            <label class="campo">Información de alérgenos <input class="campo__entrada" name="alergenos" value="<?= $escapar($valores['alergenos']) ?>"
                    placeholder="Por ejemplo: contiene gluten y leche." maxlength="500">
            </label>
            <label class="campo">Estado <select class="campo__entrada" id="producto-activo" name="activo">
                    <option value="1" <?= (string) $valores['activo'] === '1' ? 'selected' : '' ?>>Activo</option>
                    <option value="0" <?= (string) $valores['activo'] === '0' ? 'selected' : '' ?>>Inactivo</option>
                </select>
                <small class="campo__ayuda">Los productos inactivos no aparecen en el catálogo.</small>
            </label>
            <div class="acciones producto-formulario__acciones">
                <button class="boton boton--dorado"><?= $esEdicion ? 'Guardar cambios' : 'Agregar producto' ?></button>
                <a class="boton boton--contorno" href="<?= $escapar($url('equipo-productos', $filtrosListado)) ?>">Volver sin guardar</a>
            </div>
        </section>
        <aside class="tarjeta producto-imagen">
            <h2 class="tarjeta__titulo">Imagen del producto</h2>
            <img class="producto-imagen__vista" src="<?= $escapar($recurso('images/' . $imagen)) ?>"
                alt="Vista previa del producto" width="400" height="245" data-imagen-producto>
            <label
                class="boton boton--contorno boton--completo producto-imagen__selector"><?= $esEdicion ? 'Cambiar imagen' : 'Seleccionar imagen' ?> <input class="producto-imagen__archivo" type="file" name="imagen" accept="image/png,image/jpeg"
                    aria-describedby="producto-imagen-ayuda producto-imagen-error" data-archivo-producto>
            </label>
            <p class="texto-pequeno producto-imagen__ayuda" id="producto-imagen-ayuda">JPG o PNG, hasta 4 MB. La imagen es opcional: si no elegís una nueva, se conserva la actual.</p>
            <p class="producto-imagen__archivo-nombre" role="status" data-nombre-imagen hidden>Sin archivo nuevo seleccionado.</p>
            <p class="producto-imagen__error" id="producto-imagen-error" role="alert" data-error-imagen hidden></p>
            <?php if ($error): ?>
                <p class="producto-imagen__ayuda">Si habías seleccionado una imagen nueva, volvé a elegirla antes de guardar.</p>
            <?php endif; ?>
        </aside>
    </form>
</main>
