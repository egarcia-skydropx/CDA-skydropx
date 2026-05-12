# QA Log

---

## QA — 2026-05-04 (Quick Create)

### Funcionalidad nueva

- **Botón "Crear otro X" en el header del editor** — `SXHC_Quick_Create` (clase nueva). En el editor de bloques de `help_article` o `sxhc_faq`, inyecta un `<button class="components-button is-secondary">` al inicio del contenedor de acciones del header. El destino es siempre el MISMO post type que se está editando:
  - Editando un artículo → "Nuevo artículo" → `post-new.php?post_type=help_article`
  - Editando una FAQ     → "Nueva pregunta" → `post-new.php?post_type=sxhc_faq`
- Si la entrada actual está sucia (`isEditedPostDirty()`), pide confirmación con `window.confirm()` antes de navegar. No hereda nada al destino.

### Archivos creados

- `wp-content/plugins/skydropx-help-center/includes/class-sxhc-quick-create.php`
- `wp-content/plugins/skydropx-help-center/assets/js/quick-create.js`

### Archivos modificados

- `wp-content/plugins/skydropx-help-center/skydropx-help-center.php` — `require_once` de la clase nueva (línea 32) + `add_action( 'init', array( 'SXHC_Quick_Create', 'init' ) );` (línea 60)

### Bugs corregidos durante el QA

- **Docstring desactualizado** — `quick-create.js:1-9` decía "bidireccional / Nueva FAQ" (versión inicial del feature antes del cambio de alcance) → corregido a "mismo post type / Nueva pregunta"
- **Branch vacío redundante** — `quick-create.js:95-97` tenía `if ( injectButton() ) { /* comentario sin efecto */ }` → simplificado a `injectButton();`
- **Falta de capability check** — `class-sxhc-quick-create.php:enqueue()` no validaba `current_user_can( 'edit_posts' )` (defensa en profundidad). Agregado. Ambos CPT usan `capability_type = 'post'` (verificado en `class-sxhc-post-type.php:33` y default de `sxhc-faq.php`), así que `edit_posts` es la cap correcta para ambos.

### Sin cambios necesarios

- **PHP 7.2 compat** — `class-sxhc-quick-create.php` no usa `match()`, `fn()`, `??=`, `str_contains()`, `str_starts_with()`. Solo `array(...)`, `sprintf()`, `method_exists()`, callables de array `array(__CLASS__, ...)`. Limpio.
- **JS estilo legacy** — `quick-create.js` usa `var`, `function() {}`, sin arrow functions, sin template literals. Compatible con browsers viejos y consistente con `gutenberg-categories.js`.
- **Sin `console.log` de debug** — verificado.
- **`SXHC_URL` disponible** — definido en línea 17 del bootstrap, usado en `enqueue()` que corre en `admin_enqueue_scripts` (posterior a `init`). Ordering correcto.
- **Hooks personalizados** — no hay; solo `add_action( 'admin_enqueue_scripts', ... )` que es nativo de WP.
- **MutationObserver con cleanup** — `useEffect` retorna función de teardown que llama `clearInterval`, `observer.disconnect()` y `removeButton()`. No hay leak.
- **`sxhc-faq.php`** — durante la sesión se intentó cambiar el layout a row-by-row, pero se revirtió a petición del usuario. Verificado: `gap: 24px`, dos `.sxhc-faq-col` wrappers (líneas 407 y 413), regla CSS `.sxhc-faq-col` presente (línea 473), algoritmo greedy intacto. Estado pre-edición restaurado.

### Edge cases conocidos / aceptados

- **Doble confirm posible** si Gutenberg tiene activo su propio `beforeunload`. El usuario eligió explícitamente "misma pestaña con confirmación" cuando se le preguntó. Aceptado.
- **Reintentos del setInterval terminan a los 8 s.** Si el header de Gutenberg monta después de los 8 s (extremadamente raro), el botón no se inyectará hasta el próximo render que dispare el MutationObserver. Aceptado por simplicidad.

---

## QA — 2026-04-28 (sesión 3)

### Bugs corregidos

- **PHP 7.2 incompatible** — `class-sxhc-bulk-actions.php:282` usaba `fn( $a, $b ) => strcmp(...)` (PHP 7.4+) → convertido a `function( $a, $b ) { return strcmp(...); }`
- **PHP 7.2 incompatible** — `theme/search.php:23` usaba `fn($t) => $t->name` → convertido a `function( $t ) { return $t->name; }`

### Limpieza de código

- `class-sxhc-appearance.php:DEFAULTS` — removidos `hero_title` y `hero_subtitle` (settings huérfanos)
- `class-sxhc-appearance.php:register_customizer()` — eliminada la sección "Hero" completa (settings + controls de `sxhc_hero_title` y `sxhc_hero_subtitle`)
- `class-sxhc-appearance.php:preview_js()` — removidas las bindings de `sxhc_hero_title`, `sxhc_hero_subtitle` y `sxhc_site_name` (apuntaban a clases `.sxhc-hero-title`, `.sxhc-hero-subtitle`, `.sxhc-site-name` que no existen en el tema)
- `gutenberg-categories.js` — removido `console.log( '[SXHC] Panel de categorías registrado.' )` final (debug innecesario en cada carga del editor)
- `class-sxhc-multi-category.php:enqueue()` — versión del script bumped `1.4` → `1.5` (porque cambió `gutenberg-categories.js`)

### Docs actualizadas

- `docs/README.md` — tabla del Customizer: removida la fila "Hero", agregada nota explicando por qué
- `docs/estructura-archivos.md` — agregadas las clases que faltaban en el árbol (`class-sxhc-multi-category.php`, `class-sxhc-alert-block.php`, `class-sxhc-views.php`) y la carpeta `assets/` del plugin (con `alerts.css`, `alert-block.js`, `gutenberg-categories.js`); agregadas meta keys faltantes (`_sxhc_primary_category`, `_sxhc_views`)

### Sin cambios necesarios

- `sxhc_rewrite_version` sigue en `'2'` (slug taxonomía no cambió)
- 3 filtros SVG en `skydropx-help-center.php` — únicos, no duplicados
- `auth_callback` en `register_post_meta` para `_sxhc_primary_category` — presente
- Slug de taxonomía: `categoria` (correcto, sin `ayuda/categoria`)
- `console.warn` en `gutenberg-categories.js:14` — útil para detectar entornos sin `PluginDocumentSettingPanel`, se mantiene
- `SXHC_Multi_Category::save_categories()` — fallback documentado para saves clásicos, no se elimina

---

## QA — 2026-04-28 (sesión 2)

### Limpieza de código

- `index.php:$hero_title` — variable computada pero no renderizada (título eliminado del hero)
- `index.php:$hero_subtitle` — ídem subtítulo
- `index.php: pt-10 pb-16` → `py-12` — padding ajustado al quitar el título

### Funcionalidad nueva

- Lottie animation (`dotlottie-wc`) agregada sobre el buscador en el hero
- Comportamiento: play al hacer focus en el input, stop al perder el focus
- Script cargado como `type="module"` desde unpkg CDN

### Docs actualizadas

- `docs/qa-log.md` — creado (este archivo)
- `docs/skill-help-center-qa/SKILL.md` — skill de QA creado para reutilización

### Sin cambios necesarios

- `class-sxhc-multi-category.php` — limpio desde QA anterior
- `class-sxhc-bulk-actions.php` — sin cambios en esta sesión
- `taxonomy-help_category.php` — sin cambios en esta sesión
- Compatibilidad PHP 7.2 — verificada, sin issues nuevos

---

## QA — 2026-04-28 (sesión 1)

### Limpieza de código

- `class-sxhc-multi-category.php:render_meta_box()` — meta box reemplazado por panel Gutenberg
- `class-sxhc-multi-category.php:get_all_category_paths()` — sin referencias en el codebase
- `class-sxhc-multi-category.php:set_primary_if_missing()` — hook `sxhc_after_import_article` nunca se dispara
- `class-sxhc-multi-category.php:build_options()` — solo lo usaba render_meta_box
- `class-sxhc-multi-category.php:render_options()` — solo lo usaba build_options
- `class-sxhc-multi-category.php:updateRemoveButtons()` — función JS vacía con comentario

### Bugs corregidos

- **Aviso de copia de seguridad del navegador** → `delete_autosave()` en PHP + limpieza de localStorage en JS
- **Permiso denegado al publicar** (`_sxhc_primary_category`) → agregado `auth_callback` en `register_post_meta`

### Docs actualizadas

- `docs/multi-categoria.md` — creado, documenta la funcionalidad completa
- `docs/README.md` — índice actualizado con enlace a multi-categoria.md
