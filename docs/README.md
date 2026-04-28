# Skydropx Help Center — Documentación

> WordPress local corriendo en MAMP · `http://localhost:8888/help-center`  
> Desarrollado por: Emmanuel Garcia (Product Designer, Design System)  
> Base de datos: `skydropx_help` · Usuario WP: `root`

---

## Índice

1. [Arquitectura general](#1-arquitectura-general)
2. [Plugin: skydropx-help-center](#2-plugin-skydropx-help-center)
3. [Tema: skydropx-help](#3-tema-skydropx-help)
4. [Importación de contenido](#4-importación-de-contenido)
5. [Buscador](#5-buscador)
6. [Apariencia y personalización](#6-apariencia-y-personalización)
7. [Ordenar categorías](#7-ordenar-categorías)
8. [Acciones masivas](#8-acciones-masivas)
9. [Multi-categoría en artículos](./multi-categoria.md) ← nueva
10. [URLs y estructura de navegación](#10-urls-y-estructura-de-navegación)
11. [Decisiones técnicas](#11-decisiones-técnicas)

---

## 1. Arquitectura general

El help center usa la **Opción B** (Custom Post Type + Custom Taxonomy), que se eligió sobre WordPress nativo y plugins de KB porque los campos personalizados se agregan constantemente.

```
WordPress (MAMP)
├── Plugin: skydropx-help-center   ← toda la lógica de negocio
└── Tema:   skydropx-help          ← frontend con Tailwind CDN
```

**Stack:**
- PHP 7.2+ compatible (sin `match()`, sin arrow functions `fn()`)
- Tailwind CSS via CDN (configuración dinámica con colores brand)
- jQuery + jQuery UI Sortable (bundled con WordPress)
- AJAX para importaciones, búsqueda, ordenamiento y acciones masivas

---

## 2. Plugin: skydropx-help-center

**Ruta:** `wp-content/plugins/skydropx-help-center/`

### Estructura de archivos

```
skydropx-help-center/
├── skydropx-help-center.php          ← Bootstrap principal
└── includes/
    ├── class-sxhc-post-type.php      ← CPT: help_article
    ├── class-sxhc-taxonomy.php       ← Taxonomy: help_category
    ├── class-sxhc-importer.php       ← Importar categorías desde Markdown
    ├── class-sxhc-article-importer.php ← Importar artículos desde CSV (batch AJAX)
    ├── class-sxhc-admin-columns.php  ← Columnas "Categoría" y "Ruta" en listado
    ├── class-sxhc-search.php         ← Motor de búsqueda con normalización
    ├── class-sxhc-bulk-actions.php   ← Acciones masivas + modal agregar categoría
    ├── class-sxhc-appearance.php     ← Integración con WordPress Customizer
    ├── class-sxhc-category-order.php ← Drag & drop para ordenar categorías
    └── class-sxhc-category-meta.php  ← Imagen por categoría raíz
```

### Custom Post Type: `help_article`

- **Slug URL:** `/ayuda/{post-slug}/`
- **Soporta:** title, editor, thumbnail, excerpt, custom-fields, revisions
- **Integrado con:** taxonomía `help_category`
- **REST API:** habilitado (Gutenberg compatible)

### Custom Taxonomy: `help_category`

- **Tipo:** jerárquica (igual que categorías nativas)
- **Slug URL:** `/categoria/{term-slug}/`  
  *(se cambió de `ayuda/categoria` a `categoria` para evitar conflicto con el CPT)*
- **Profundidad:** ilimitada — puede anidarse infinitamente
- **Rewrite:** `hierarchical => true` para URLs anidadas

### Soporte SVG

El plugin habilita SVG en la media library via tres filtros:
- `upload_mimes` — agrega `image/svg+xml`
- `wp_check_filetype_and_ext` — corrige detección MIME
- `wp_prepare_attachment_for_js` — muestra SVGs en la librería

---

## 3. Tema: skydropx-help

**Ruta:** `wp-content/themes/skydropx-help/`

### Estructura de archivos

```
skydropx-help/
├── style.css                        ← Header del tema
├── functions.php                    ← Setup, helpers, sidebar recursivo
├── header.php                       ← Header con logo/nombre + buscador
├── footer.php                       ← wp_footer()
├── index.php                        ← Homepage: hero + buscador + grid de categorías
├── taxonomy-help_category.php       ← Página de categoría/subcategoría (todos los niveles)
├── single-help_article.php          ← Página de artículo con breadcrumb
├── search.php                       ← Resultados de búsqueda completos
├── assets/
│   ├── js/search.js                 ← JS del buscador (dropdown + normalización)
│   └── images/logo-default.svg      ← Logo Skydropx por defecto
└── template-parts/
    ├── header.php                   ← (ver header.php raíz)
    ├── sidebar.php                  ← Sidebar recursivo de categorías
    ├── breadcrumb.php               ← Breadcrumb reutilizable
    └── search-input.php             ← Input de búsqueda (variantes: hero / compact)
```

### Lógica de templates

| URL | Template usado | Descripción |
|-----|---------------|-------------|
| `/` | `index.php` | Grid de categorías raíz |
| `/categoria/{slug}/` | `taxonomy-help_category.php` | Si tiene hijos → cards; si no → lista de artículos |
| `/ayuda/{slug}/` | `single-help_article.php` | Artículo con breadcrumb y relacionados |
| `/?s=query` | `search.php` | Resultados de búsqueda |

### Funciones helper en `functions.php`

```php
sxhc_get_term_breadcrumb( $term )     // Array de WP_Term desde raíz hasta $term
sxhc_get_post_breadcrumb( $post_id )  // Breadcrumb del artículo vía su categoría más profunda
sxhc_render_sidebar( $parent_id, $active_ids, $depth ) // Sidebar recursivo con rama activa expandida
sxhc_count_term_posts_recursive( $term_id )            // Cuenta artículos publicados en todo el árbol
```

### CSS Variables (inyectadas por `SXHC_Appearance`)

```css
--brand          /* Color principal */
--brand-light    /* Color hover/fondos sutiles */
--text           /* Color texto principal */
--muted          /* Color texto secundario */
--border         /* Color de bordes */
--bg-page        /* Fondo de página */
--bg-header      /* Fondo del header */
--bg-hero        /* Fondo del hero */
--bg-sidebar     /* Fondo del sidebar */
--card-radius    /* Radio de bordes en cards */
--font           /* Fuente global */
```

### Logo en el header

Orden de prioridad:
1. Logo personalizado (subido desde Customizer)
2. `assets/images/logo-default.svg` (logo Skydropx)
3. Texto del campo "Nombre de la marca"

---

## 4. Importación de contenido

### Categorías (`SXHC_Importer`)

**Ruta admin:** Help Center → Importar categorías  
**Archivo fuente:** `/indice-categorias.md` (en la raíz de WordPress)

El parser lee los niveles de `#` para determinar la jerarquía:
- `#` = categoría raíz
- `##` = hijo de la anterior `#`
- `###` = hijo del anterior `##`
- etc. (profundidad ilimitada)

Es idempotente: si se reimporta, omite los que ya existen.

### Artículos (`SXHC_Article_Importer`)

**Ruta admin:** Help Center → Importar artículos (CSV)  
**Archivo fuente:** `CDA Skydropx Pro - Articulos-CDAs - 6717cf7217bfd6fb2b0dbec6.csv`

**Columnas del CSV usadas:**

| Columna | Campo WordPress |
|---------|----------------|
| `Name` | `post_title` |
| `Slug` | `post_name` |
| `Contenido` | `post_content` (HTML) |
| `Description Metadata` | `post_excerpt` |
| `Draft` | `post_status` (true→draft, false→publish) |
| `Categoria` | Término raíz en `help_category` |
| `SubCategoria` | Término hijo en `help_category` |
| `Secciones` | Término nieto en `help_category` (nivel 3) |
| `Title Metadata` | `_sxhc_seo_title` (post meta) |
| `KEYWORDS SEARCH` | `_sxhc_keywords` (post meta) |
| `Tags` | `_sxhc_tags` (post meta) |

**Procesamiento:** lotes de 50 artículos via AJAX. El progreso se guarda en `wp_options` (`sxhc_import_progress`), permitiendo pausar y reanudar.

**Asignación de categoría:** se asigna solo el término más específico disponible:
`Secciones > SubCategoria > Categoria`

**Mapeo de slugs CSV → nombres WP:** el array `CATEGORY_MAP` en la clase traduce slugs como `paqueterias` → `Transportadoras`, `cotizar-y-crear` → `Creación y cotización`, etc.

**Meta normalizado:** al importar cada artículo se guarda `_sxhc_title_normalized` con el título sin acentos, espacios ni puntuación, para la búsqueda.

---

## 5. Buscador

**Clase:** `SXHC_Search`  
**AJAX action:** `sxhc_search` (público, sin login)  
**JS:** `assets/js/search.js`

### Normalización de texto

```php
SXHC_Search::normalize( $text )
// "Envíos Internacionales" → "enviosinternacionales"
// Pasos: lowercase → iconv transliterar → strip non-alphanumeric
```

Esto permite buscar sin importar:
- Acentos: `envios` encuentra `Envíos`
- Espacios: `centrodeayuda` encuentra `Centro de ayuda`
- Mayúsculas: `SHOPIFY` encuentra `Shopify`
- Puntuación: `cotizar,crear` encuentra `Cotizar y crear`

### Estrategias de búsqueda (fusionadas por score)

1. **WP_Query nativa** (score +10) — busca en título y contenido
2. **Meta normalizado LIKE** (score +8) — busca `_sxhc_title_normalized` en la DB
3. **Por categoría** (score +5) — si el query coincide con un nombre de taxonomía

### Dropdown en vivo

- Debounce de 280ms
- Máximo 8 resultados
- Muestra título + ruta de categoría (`Envíos / Creación y cotización`)
- Highlight de la coincidencia en el título
- Navegación con ↑ ↓ Enter Escape
- Botón `×` para limpiar
- Spinner durante la petición
- "Ver todos los resultados" → redirige a `search.php`

### Variantes del input

- `hero` — grande, centrado, en la homepage
- `compact` — pequeño, en el header (se oculta en la homepage para no redundar)

---

## 6. Apariencia y personalización

**Clase:** `SXHC_Appearance`  
**Ruta admin:** Apariencia → Personalizar → **Help Center**  
**Almacenamiento:** WordPress theme_mods (`get_theme_mod()`)

### Secciones del Customizer

| Sección | Campos |
|---------|--------|
| **Identidad** | Logo (media), ancho del logo, nombre de la marca |
| **Colores** | Brand, brand light, texto, muted, bordes |
| **Fondos** | Página, header, hero, sidebar |
| **Tipografía y Cards** | Fuente (5 opciones), radio de cards (slider 0-32px) |

> Nota: la sección "Hero" (título + subtítulo) se eliminó porque el `index.php` ahora renderiza un Lottie + buscador sin texto editable.

Todos los cambios se aplican en **tiempo real** en el preview del Customizer via `postMessage`.

### Imagen por categoría (`SXHC_Category_Meta`)

- Solo disponible en **categorías raíz** (sin padre)
- Al editar una categoría: se muestra/oculta según el campo "Categoría padre"
- En el modal de agregar: el campo imagen desaparece si se selecciona un padre
- Se guarda como `term_meta` con key `sxhc_category_image` (ID del attachment)
- En la homepage: se muestra con `max-height: 60px; width: auto`

---

## 7. Ordenar categorías

**Clase:** `SXHC_Category_Order`  
**Ruta admin:** Help Center → Ordenar categorías  
**Almacenamiento:** `term_meta` con key `sxhc_term_order`

### Comportamiento

- Vista jerárquica completa con todos los niveles anidados
- **Drag & drop** con jQuery UI Sortable (handle `⠿` a la izquierda)
- Guarda automáticamente al soltar (AJAX, sin recargar)
- El orden se aplica en: homepage, sidebar, páginas de categoría

### Botón `+` para agregar

- **Ítems hoja** (sin hijos): botón `+` a la derecha del header → abre form inline debajo
- **Ítems padre** (con hijos): botón `+` centrado al fondo del contenedor
- **Nivel raíz**: botón `+` al fondo de toda la lista
- El form inline tiene input de texto + Agregar + Cancelar (también funciona con Enter/Escape)
- Al guardar: crea el término via AJAX y añade la fila sin recargar

### Método público

```php
SXHC_Category_Order::get_ordered_terms( $parent_id )
// Devuelve términos hijos ordenados por sxhc_term_order
// Fallback: alfabético para los sin orden guardado
```

---

## 8. Acciones masivas

**Clase:** `SXHC_Bulk_Actions`

### En listado de artículos (`edit-help_article`)

**Acción:** "Cambiar categoría"  
Al seleccionarla, aparece un panel debajo de la barra con un `<select>` jerárquico. Al aplicar, mueve todos los artículos seleccionados a esa categoría (reemplaza la anterior).

### En listado de categorías (`edit-help_category`)

**Acción:** "Cambiar categoría padre"  
Mueve las categorías seleccionadas bajo un nuevo padre. "Sin padre" las mueve al nivel raíz. Incluye protección anti-circular (no puede ser hija de sí misma ni de sus descendientes).

### Modal "Agregar categoría"

En la pantalla de categorías:
- El formulario lateral nativo está **oculto** (tabla full width)
- Botón "Agregar categoría" junto al título de la página
- Abre un modal con: Nombre*, Categoría padre, Slug, Descripción
- Si se selecciona un padre, el campo imagen se oculta
- Al guardar: crea el término y recarga la página

---

## 9. URLs y estructura de navegación

```
/                                          → Homepage (grid categorías raíz)
/categoria/{slug}/                         → Categoría raíz (cards de subcategorías)
/categoria/{slug}/{subslug}/               → Subcategoría (cards o lista de artículos)
/categoria/{slug}/{subslug}/{subsubslug}/  → Sub-subcategoría (lista de artículos)
/ayuda/{article-slug}/                     → Artículo
/?s=query&post_type=help_article           → Resultados de búsqueda
```

**Flush de rewrite rules:** se ejecuta automáticamente cuando `sxhc_rewrite_version !== '2'`. Para forzarlo manualmente: Ajustes → Enlaces permanentes → Guardar.

---

## 10. Decisiones técnicas

| Decisión | Alternativas descartadas | Razón |
|----------|--------------------------|-------|
| Custom Post Type + Custom Taxonomy | WP nativo, plugin KB | Campos personalizados se agregan constantemente |
| Tailwind CDN | Bootstrap, CSS propio | Preferencia del equipo de diseño |
| Importación batch AJAX | WP-CLI, archivo PHP directo | WP-CLI no disponible; batch evita timeouts en 3,127 artículos |
| Taxonomía slug `categoria` | `ayuda/categoria` | El slug anterior conflictuaba con el CPT `ayuda/` y enviaba al artículo incorrecto |
| `iconv` para normalización | Array de caracteres | Más robusto, maneja todos los diacríticos automáticamente |
| Customizer para apariencia | Página admin propia | Integración nativa con preview en vivo y `postMessage` |
| `term_meta` para orden | `wp_options` con array | Cada término guarda su propio orden, más escalable |
| PHP 7.2 compatible | PHP 8.0 features | MAMP puede correr PHP 7.x; se evita `match()` y `fn()` |

---

*Última actualización: sesión activa de desarrollo*
