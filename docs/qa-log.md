# QA Log

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
