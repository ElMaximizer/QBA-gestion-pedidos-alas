UPDATE productos
SET categoria = CASE
    WHEN LOWER(nombre) LIKE '%roll%' OR LOWER(nombre) LIKE 'cup %' THEN 'Rolls y cups'
    WHEN LOWER(nombre) LIKE '%hojaldrad%' OR LOWER(nombre) LIKE '%medialuna%' THEN 'Hojaldres y medialunas'
    WHEN LOWER(nombre) LIKE '%budin%' THEN 'Budines'
    ELSE categoria
END
WHERE categoria IN ('Panes', 'Bizcochos', 'Tortas', 'Especiales')
  AND (
      LOWER(nombre) LIKE '%roll%'
      OR LOWER(nombre) LIKE 'cup %'
      OR LOWER(nombre) LIKE '%hojaldrad%'
      OR LOWER(nombre) LIKE '%medialuna%'
      OR LOWER(nombre) LIKE '%budin%'
  );