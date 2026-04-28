# Multi-categoría en artículos

## Objetivo

Un artículo puede vivir en **una o más categorías** simultáneamente, generando dos tipos de rutas:

1. **Ruta canónica** → `/ayuda/{slug}/` — URL permanente del artículo, breadcrumb de la categoría primaria
2. **Ruta contextual** → `/ayuda/{slug}/?cat={term_id}` — mismo artículo con breadcrumb y sidebar de la categoría desde donde navegaste

El usuario encuentra el mismo artículo en diferentes partes de la documentación sin duplicar contenido.

---

## Cómo funciona

### En el editor (Gutenberg)

El panel **"Categoría"** aparece en el sidebar derecho del editor bajo la pestaña "Artículo":

```
Categoría
[Seleccionar categoría ▾]

+ Agregar otra categoría
```

- Cada fila = una ruta donde aparecerá el artículo
- El botón `×` quita una ruta
- "+ Agregar otra categoría" añade una nueva fila
- Si solo queda una fila y se hace `×`, limpia el select sin eliminar la fila

La primera categoría con valor se guarda como **primaria** automáticamente.

### En las páginas de categoría (frontend)

Los artículos aparecen en el listado de **todas sus categorías asignadas**.  
Al hacer clic en un artículo desde una categoría, la URL lleva `?cat={term_id}`:

```
/categoria/envios/ordenes/
  → link al artículo: /ayuda/por-que-mis-ordenes/?cat=15
```

### En la página del artículo (frontend)

- **Badges de categorías** encima del título — una pastilla por cada categoría
- La pastilla activa (categoría desde donde llegaste) se resalta en azul sólido
- **"Este artículo también está en"** — lista las otras rutas disponibles
- **Breadcrumb** refleja la categoría del contexto actual
- **Sidebar** destaca la categoría activa

---

## Clase: `SXHC_Multi_Category`

**Archivo:** `includes/class-sxhc-multi-category.php`  
**Script Gutenberg:** `assets/js/gutenberg-categories.js`

### Métodos públicos

```php
// term_id de la categoría primaria (con fallback al término más profundo)
SXHC_Multi_Category::get_primary_term_id( $post_id )

// term_id según contexto: ?cat= si es válido, si no → primaria
SXHC_Multi_Category::get_context_term_id( $post_id )

// Ruta legible: "Envíos / Creación y cotización / Crear envío"
SXHC_Multi_Category::get_term_path( $term )
```

### Meta guardada

| Meta key | Tipo | Descripción |
|----------|------|-------------|
| `_sxhc_primary_category` | `integer` | term_id de la categoría primaria. Expuesto en REST API. |

### Hooks registrados

| Hook | Método | Descripción |
|------|--------|-------------|
| `add_meta_boxes` | `remove_native_meta_box()` | Quita el checkbox list nativo de WP |
| `save_post_help_article` | `save_categories()` | Fallback para saves clásicos |
| `admin_enqueue_scripts` | `enqueue()` | Carga el panel Gutenberg |
| `load-post.php` | `delete_autosave()` | Elimina borradores del servidor al abrir el editor |
| `load-post-new.php` | `delete_autosave()` | Ídem para nuevos artículos |

---

## Guardado en Gutenberg

El panel usa la API de bloques de WordPress para guardar directamente:

```javascript
// Categorías asignadas (array de term_ids)
dispatch.editPost({ help_category: [5, 12, 28] })

// Categoría primaria (meta)
dispatch.editPost({ meta: { _sxhc_primary_category: 5 } })
```

Ambas operaciones se sincronizan al dar **Guardar** o **Publicar** vía REST API.

---

## Aviso "copia de seguridad del navegador"

**Causa:** WordPress guarda borradores en localStorage. Si el servidor tiene una versión diferente, aparece el aviso.

**Fix aplicado (doble):**
1. **PHP** — `delete_autosave()` elimina el autosave del servidor al abrir el editor
2. **JS** — Script inline limpia las keys de `localStorage` que contengan "autosave" al montar el editor

---

## Columna "Categoría" en el admin

La columna muestra **un chip por cada categoría asignada** al artículo. Cada chip es clickeable y filtra la lista por esa categoría.

---

## URLs de las dos rutas

| Tipo | URL | Contexto |
|------|-----|---------|
| Canónica | `/ayuda/{slug}/` | Categoría primaria |
| Contextual | `/ayuda/{slug}/?cat={term_id}` | Categoría específica |

Ambas URLs renderizan el mismo contenido HTML, pero con diferente breadcrumb y categoría activa en el sidebar.
