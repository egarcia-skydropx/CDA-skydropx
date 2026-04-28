---
name: help-center-qa
description: >
  QA completo del proyecto Skydropx Help Center WordPress. Úsalo siempre que el usuario pida
  "QA", "revisar bugs", "limpiar código", "depurar", "auditar" o "el mismo QA de antes".
  Cubre: código muerto PHP, variables no usadas en el tema, compatibilidad PHP 7.2,
  bugs de lógica conocidos, y documentación desactualizada en docs/.
---

# QA — Skydropx Help Center

## Contexto del proyecto

- **Ruta:** `/Applications/MAMP/htdocs/help-center/`
- **Plugin:** `wp-content/plugins/skydropx-help-center/includes/`
- **Tema:** `wp-content/themes/skydropx-help/`
- **Docs:** `docs/` (guardar nueva documentación aquí siempre)
- **PHP target:** 7.2+ — prohibido `match()`, arrow functions `fn()`, `??=`

---

## Checklist de QA

Ejecuta todos los pasos en orden. Crea tareas (`TaskCreate`) para trackear el progreso.

### 1. Auditoría de código muerto (PHP)

Para cada archivo en `includes/`:

- Métodos `public/private/protected static` declarados pero nunca llamados desde fuera ni desde hooks
- Hooks `add_action` / `add_filter` cuya acción personalizada nunca se dispara en el codebase
- Constantes definidas y no usadas
- Variables asignadas y nunca leídas

**Cómo detectar:** Grep el método en todo el proyecto. Si solo aparece en su declaración → muerto.

### 2. Auditoría del tema

Para cada archivo `.php` del tema:

- Variables PHP computadas pero no renderizadas
- `get_template_part` con `$args` que la parte no consume
- CSS classes dinámicas de Tailwind que no existen en CDN

### 3. Compatibilidad PHP 7.2

Busca en todos los archivos PHP:
- `match(` → PHP 8.0+
- `fn(` → PHP 7.4+
- `??=` → PHP 7.4+
- `str_contains(` → PHP 8.0+
- `str_starts_with(` → PHP 8.0+

### 4. Bugs conocidos / recurrentes

Verifica siempre:

- **Rewrite rules:** `sxhc_rewrite_version` debe ser `'2'`. Si el slug de taxonomía cambia → incrementar.
- **SVG upload:** Los 3 filtros MIME en `skydropx-help-center.php` no deben duplicarse.
- **Meta REST auth:** `_sxhc_primary_category` necesita `auth_callback` en `register_post_meta`.
- **Script cache:** Si `gutenberg-categories.js` se modificó, incrementar la versión en `wp_enqueue_script`.
- **Taxonomía slug:** Debe ser `categoria`, nunca `ayuda/categoria`.

### 5. JavaScript (`gutenberg-categories.js`)

- `console.log` de debug sin limpiar
- Event listeners sin cleanup
- Referencias a `wp.*` sin verificar disponibilidad

### 6. Documentación

Después de limpiar:

- Funcionalidad eliminada → actualizar docs correspondientes
- Funcionalidad nueva → crear `docs/{feature}.md`
- Siempre actualizar `docs/README.md` si hay cambios en el índice
- `docs/estructura-archivos.md` debe reflejar el árbol actual

---

## Proceso de fix

1. Confirmar que el problema es real (no falso positivo)
2. Aplicar el fix mínimo necesario
3. Registrar en `docs/qa-log.md`

---

## Reporte de salida — `docs/qa-log.md`

```markdown
## QA — {fecha}

### Limpieza de código
- `{archivo}:{método}` — {razón}

### Bugs corregidos
- {descripción} → {fix}

### Docs actualizadas
- {archivo} — {qué cambió}

### Sin cambios necesarios
- {lista de lo revisado que estaba OK}
```

---

## Notas críticas

- **NO eliminar** `save_categories()` en `SXHC_Multi_Category` — es fallback para saves clásicos
- **NO tocar** los 3 filtros SVG en `skydropx-help-center.php`
- **Verificar** que `SXHC_URL` y `SXHC_DIR` están disponibles antes de usarlos en `enqueue()`
- La taxonomía usa slug `categoria` — si aparece `ayuda/categoria` en algún lado, es un bug
