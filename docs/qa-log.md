# QA Log

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
