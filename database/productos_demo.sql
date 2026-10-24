-- Muestras de desarrollo, sin importar pedidos ni clientes del proyecto anterior.
-- Esto lo creo gpt, puede llegar a no ser exacto a como lo teniamos en el otro repo
INSERT INTO productos (nombre, descripcion, precio, categoria, presentacion, ingredientes, alergenos, imagen, destacado)
SELECT 'Pan integral', 'Pan artesanal de harina integral, por unidad.', 95.00, 'Panes', 'Unidad',
       'Harina integral de trigo, agua, levadura y sal.', 'Contiene trigo (gluten).', 'pan.svg', 1
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE nombre = 'Pan integral');

INSERT INTO productos (nombre, descripcion, precio, categoria, presentacion, ingredientes, alergenos, imagen, destacado)
SELECT 'Bizcocho de chocolate', 'Un toque dulce para la hora de la merienda.', 65.00, 'Bizcochos', 'Unidad',
       'Harina de trigo, manteca, chocolate, azúcar, leche y huevo.', 'Contiene trigo, leche y huevo.', 'croissant.svg', 1
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE nombre = 'Bizcocho de chocolate');

INSERT INTO productos (nombre, descripcion, precio, categoria, presentacion, ingredientes, alergenos, imagen, destacado)
SELECT 'Torta de cumpleaños', 'Una celebración hecha para compartir.', 1200.00, 'Tortas', '8 porciones',
       'Harina de trigo, huevos, azúcar, leche, crema y frutas.', 'Contiene trigo, leche y huevo.', 'torta.svg', 1
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE nombre = 'Torta de cumpleaños');
