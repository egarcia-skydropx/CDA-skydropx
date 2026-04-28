# Referencia de clases del plugin

## SXHC_Post_Type
**Archivo:** `includes/class-sxhc-post-type.php`  
**Hook:** `init`

Registra el CPT `help_article`. Soporta: title, editor, thumbnail, excerpt, custom-fields, revisions. REST habilitado para Gutenberg.

---

## SXHC_Taxonomy
**Archivo:** `includes/class-sxhc-taxonomy.php`  
**Hook:** `init`

Registra la taxonomía jerárquica `help_category` con slug de rewrite `categoria`.

---

## SXHC_Importer
**Archivo:** `includes/class-sxhc-importer.php`  
**Hook:** `init` → `admin_menu`  
**Página admin:** Help Center → Importar categorías

Parsea `indice-categorias.md` (raíz de WP) y crea términos en `help_category`. Usa un stack de padres indexado por nivel de `#` para construir la jerarquía.

---

## SXHC_Article_Importer
**Archivo:** `includes/class-sxhc-article-importer.php`  
**AJAX actions:** `sxhc_import_batch`, `sxhc_import_reset`  
**Constantes:**
- `CSV_FILE` — ruta absoluta al CSV
- `BATCH_SIZE = 50`
- `OPTION_KEY = 'sxhc_import_progress'`

**Mapeo de categorías:** `CATEGORY_MAP[]` — traduce slugs del CSV a nombres de términos WP.

**Método clave:**
```php
resolve_term( $csv_slug, $parent_id ) 
// Busca: 1) en CATEGORY_MAP → por nombre bajo $parent_id
//        2) por nombre global
//        3) por slug directo  
//        4) crea el término nuevo bajo $parent_id
```

---

## SXHC_Admin_Columns
**Archivo:** `includes/class-sxhc-admin-columns.php`  
**Hook:** `init`

Reemplaza la columna de taxonomía auto-generada por dos columnas en el listado de `help_article`:

- **Categoría** — chip clickeable (filtra la lista). Sortable.
- **Ruta** — breadcrumb completo alineado a la derecha, cada nivel en su línea, alineado a la derecha.

---

## SXHC_Search
**Archivo:** `includes/class-sxhc-search.php`  
**AJAX actions:** `sxhc_search` (público)  
**Hook:** `save_post_help_article` → guarda `_sxhc_title_normalized`

**Método estático:**
```php
SXHC_Search::normalize( $text ) // → string sin acentos, espacios ni puntuación
```

---

## SXHC_Bulk_Actions
**Archivo:** `includes/class-sxhc-bulk-actions.php`

| Hook | Acción |
|------|--------|
| `bulk_actions-edit-help_article` | Registra "Cambiar categoría" |
| `handle_bulk_actions-edit-help_article` | Procesa cambio de categoría en posts |
| `bulk_actions-edit-help_category` | Registra "Cambiar categoría padre" |
| `handle_bulk_actions-edit-help_category` | Procesa cambio de padre en términos |
| `admin_head` | CSS: oculta sidebar izquierdo en pantalla de categorías |
| `admin_footer` | Panel de bulk + Modal "Agregar categoría" |
| `wp_ajax_sxhc_create_category` | Crea término vía AJAX desde el modal |

---

## SXHC_Appearance
**Archivo:** `includes/class-sxhc-appearance.php`  
**Hook:** `customize_register`  
**Storage:** `get_theme_mod( 'sxhc_{key}' )`

**Método público:**
```php
SXHC_Appearance::get( $key ) // Lee theme_mod con fallback a DEFAULTS[]
```

**Panel Customizer:** "Help Center" con secciones: Identidad, Hero, Colores, Fondos, Tipografía y Cards.

**Transport:** `postMessage` en todos los campos → live preview sin recarga.

---

## SXHC_Category_Order
**Archivo:** `includes/class-sxhc-category-order.php`  
**AJAX actions:** `sxhc_save_category_order`, `sxhc_add_category`  
**Meta key:** `sxhc_term_order`  
**Página admin:** Help Center → Ordenar categorías

**Método público:**
```php
SXHC_Category_Order::get_ordered_terms( $parent_id, $extra_args )
// Devuelve términos ordenados por sxhc_term_order
// Términos sin orden guardado van al final, ordenados alfabéticamente
```

---

## SXHC_Category_Meta
**Archivo:** `includes/class-sxhc-category-meta.php`  
**Hooks:** `help_category_add_form_fields`, `help_category_edit_form_fields`, `created_help_category`, `edited_help_category`  
**Meta key:** `sxhc_category_image` (ID del attachment)

**Método público:**
```php
SXHC_Category_Meta::get_image_url( $term_id, $size ) // → URL o string vacío
```

Solo guarda imagen en términos raíz (`parent = 0`). Si se cambia el padre a un valor distinto de 0, el meta se elimina automáticamente al guardar.
