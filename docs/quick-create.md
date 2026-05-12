# Quick Create — Botón "Crear otro X" en el editor

> Atajo en el header del editor de bloques que abre un editor en blanco del **mismo post type** que se está editando, sin tener que volver al listado.

---

## Qué hace

En las pantallas `post.php` y `post-new.php` del editor de bloques de:

| Post type     | Etiqueta del botón | URL destino                                  |
|---------------|--------------------|----------------------------------------------|
| `help_article`| "Nuevo artículo"   | `post-new.php?post_type=help_article`        |
| `sxhc_faq`    | "Nueva pregunta"   | `post-new.php?post_type=sxhc_faq`            |

El botón se inyecta a la **izquierda** del bloque "Guardar como borrador / Publicar". Si la entrada actual tiene cambios sin guardar (`isEditedPostDirty()`), pide confirmación con `window.confirm()` antes de navegar. **No hereda nada** al destino: el editor abre en blanco.

---

## Archivos

```
wp-content/plugins/skydropx-help-center/
├── includes/
│   └── class-sxhc-quick-create.php   ← clase, enqueue + localize
└── assets/js/
    └── quick-create.js               ← Gutenberg plugin que inyecta el <button>
```

Bootstrap: `skydropx-help-center.php` carga la clase con `require_once` y la inicializa con `add_action( 'init', array( 'SXHC_Quick_Create', 'init' ) )`.

---

## Decisiones técnicas

- **Por qué inyección DOM en lugar de un slot oficial:** Gutenberg no expone un slot público para "antes del botón Publicar". Las opciones nativas (`PluginPostStatusInfo`, `PluginMoreMenuItem`, `PluginDocumentSettingPanel`) viven en el sidebar o en menús ocultos. Usar inyección DOM directa con `MutationObserver` es la opción más estable que mantiene el botón visible siempre.
- **Por qué `<button>` nativo + clases `components-button is-secondary`:** hereda el styling de Gutenberg sin tener que duplicar CSS, y evita problemas de reconciliation de React si Gutenberg vuelve a renderizar el header.
- **Por qué un setInterval de 8 s + MutationObserver:** el header puede montar antes o después de que nuestro plugin se cargue. El interval cubre la carga inicial; el observer cubre re-renders posteriores.
- **Capability check:** `current_user_can( 'edit_posts' )`. Ambos CPT usan `capability_type = 'post'` (verificado en `class-sxhc-post-type.php:33` y default de `sxhc-faq.php`), así que `edit_posts` aplica para ambos.
- **PHP 7.2-compat:** `array(...)`, sin closures innecesarias, sin `match`/`fn`.

---

## Posibles ampliaciones

- **Pre-llenar la categoría seleccionada** en el destino (descartado en la primera iteración: el usuario prefirió "empezar limpio").
- **Botón unificado "Crear contenido" con dropdown** si se agregan más CPTs.
- **Atajo de teclado** (`Cmd+Shift+N`) — lo siguiente lógico cuando el patrón se use mucho.

---

## Ver también

- `docs/qa-log.md` — entrada del 4 may 2026.
- `docs/estructura-archivos.md` — árbol actualizado.
