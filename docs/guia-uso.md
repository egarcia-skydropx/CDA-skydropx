# Guía de uso

## Setup inicial (primera vez)

1. Asegurarse que MAMP está corriendo con Apache y MySQL activos
2. Ir a `wp-admin → Plugins` y activar **Skydropx Help Center**
3. Ir a `wp-admin → Apariencia → Temas` y activar **Skydropx Help Center**
4. Ir a `Ajustes → Enlaces permanentes` y hacer clic en **Guardar cambios** (flush de rewrite rules)

---

## Flujo de importación

### Paso 1 — Importar categorías

`Help Center → Importar categorías`

- Se muestra una vista previa del `indice-categorias.md` con la jerarquía indentada
- Clic en **Importar categorías**
- Es idempotente: reimportar solo crea los nuevos términos

### Paso 2 — Importar artículos

`Help Center → Importar artículos (CSV)`

- Clic en **Iniciar importación**
- La barra de progreso avanza en lotes de 50
- Si se cierra el navegador, se puede reanudar desde donde quedó
- Al terminar muestra: X creados, Y omitidos (duplicados)

> ⚠️ Para que la búsqueda por meta normalizado funcione en artículos ya importados, usar **Reimportar** para rellenar el campo `_sxhc_title_normalized`.

---

## Gestión de categorías

### Editar una categoría

`Categorías de ayuda → [hover sobre nombre] → Editar`

- Cambiar nombre, slug, padre, descripción
- Para categorías **raíz**: aparece el campo de imagen (SVG recomendado)

### Agregar categoría

Botón **Agregar categoría** en la esquina superior derecha de la pantalla de categorías.  
Se abre un modal sin salir de la página. Campos: Nombre, Padre, Slug, Descripción, Imagen (si es raíz).

### Ordenar categorías

`Help Center → Ordenar categorías`

- Arrastrar desde el ícono `⠿` a la izquierda
- El orden se guarda automáticamente al soltar
- El botón `+` al fondo de cada grupo agrega subcategorías directamente

---

## Acciones masivas en artículos

1. Ir a `Help Center → Todos los artículos`
2. Seleccionar artículos con los checkboxes
3. En **Acciones en lote** elegir **Cambiar categoría**
4. Aparece un selector jerárquico debajo
5. Seleccionar la categoría destino → **Aplicar**

---

## Acciones masivas en categorías

1. Ir a `Categorías de ayuda`
2. Seleccionar categorías con los checkboxes
3. En **Acciones en lote** elegir **Cambiar categoría padre**
4. Seleccionar el nuevo padre (o "Sin padre" para mover a raíz)
5. **Aplicar**

---

## Personalizar apariencia

`Apariencia → Personalizar → Help Center`

Los cambios se ven en tiempo real en el preview.

| Qué cambiar | Sección |
|-------------|---------|
| Logo | Identidad |
| Texto del hero | Hero |
| Colores brand, texto, bordes | Colores |
| Fondos de header, hero, sidebar | Fondos |
| Fuente, radio de cards | Tipografía y Cards |

### Logo

- **Con logo personalizado:** se muestra la imagen subida
- **Sin logo personalizado:** se muestra `logo-default.svg` (Skydropx)
- Si se borra el personalizado → vuelve automáticamente al SVG por defecto

---

## Buscador

El buscador funciona sin configuración adicional. Para mejorar resultados:

1. Asegurarse de que todos los artículos tienen `_sxhc_title_normalized` guardado
2. Agregar descripciones a las categorías raíz (aparecen en las cards del homepage)

### Normalización aplicada

El buscador trata como equivalentes:
- `envios` = `Envíos` = `ENVIOS` = `envios,` = `envíos.`
- `centrodeayuda` = `centro de ayuda` = `Centro De Ayuda`

---

## Agregar campos personalizados a artículos

Al usar CPT con `custom-fields` en `supports`, WordPress expone los post meta en el editor de Gutenberg (panel lateral) y en la API REST.

Para agregar un campo nuevo:
1. En el editor del artículo → "Campos personalizados" (activar desde ⚙️ → Preferencias → Paneles)
2. O registrar el campo con `register_post_meta()` en una nueva clase dentro de `includes/`

---

## Solución de problemas frecuentes

| Problema | Solución |
|----------|---------|
| Las categorías muestran 404 | Ir a Ajustes → Enlaces permanentes → Guardar |
| El drag & drop no funciona | Verificar que jQuery UI Sortable cargó (ver consola del browser) |
| SVG no se sube | El plugin debe estar activo (agrega filtros de MIME) |
| Búsqueda no encuentra artículos | Reimportar el CSV para generar `_sxhc_title_normalized` |
| Las categorías no muestran subcategorías | Verificar que las rewrite rules incluyen `categoria/` |
