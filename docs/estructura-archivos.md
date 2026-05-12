# Estructura completa de archivos

```
/Applications/MAMP/htdocs/help-center/
│
├── docs/                                          ← Esta documentación
│   ├── README.md
│   ├── plugin-clases.md
│   ├── estructura-archivos.md
│   └── guia-uso.md
│
├── wp-content/
│   │
│   ├── imports/                                   ← Datos fuente del importador
│   │   ├── indice-categorias.md                   ← Fuente de categorías (jerarquía en Markdown)
│   │   └── CDA Skydropx Pro - Articulos-CDAs - *.csv  ← Fuente de artículos (3,127 items)
│   │
│   ├── plugins/
│   │   └── skydropx-help-center/
│   │       ├── skydropx-help-center.php           ← Bootstrap: require + hooks
│   │       ├── assets/
│   │       │   ├── css/
│   │       │   │   └── alerts.css                 ← Estilos del bloque de alertas
│   │       │   └── js/
│   │       │       ├── alert-block.js             ← Bloque Gutenberg de alertas
│   │       │       ├── gutenberg-categories.js    ← Panel de categorías en sidebar
│   │       │       └── quick-create.js            ← Botón "Crear otro X" en header del editor
│   │       └── includes/
│   │           ├── class-sxhc-post-type.php
│   │           ├── class-sxhc-taxonomy.php
│   │           ├── class-sxhc-importer.php
│   │           ├── class-sxhc-article-importer.php
│   │           ├── class-sxhc-admin-columns.php
│   │           ├── class-sxhc-search.php
│   │           ├── class-sxhc-bulk-actions.php
│   │           ├── class-sxhc-appearance.php
│   │           ├── class-sxhc-category-order.php
│   │           ├── class-sxhc-category-meta.php
│   │           ├── class-sxhc-multi-category.php  ← Múltiples categorías por artículo
│   │           ├── class-sxhc-alert-block.php     ← Bloque Gutenberg de alertas
│   │           ├── class-sxhc-views.php           ← Contador de visitas por artículo
│   │           └── class-sxhc-quick-create.php    ← Botón "Crear otro X" en header (FAQ + artículo)
│   │
│   └── themes/
│       └── skydropx-help/
│           ├── style.css                          ← Theme header
│           ├── functions.php                      ← Setup + helpers PHP
│           ├── header.php                         ← <head> + nav con logo
│           ├── footer.php                         ← wp_footer()
│           ├── index.php                          ← Homepage
│           ├── taxonomy-help_category.php         ← Categorías (todos los niveles)
│           ├── single-help_article.php            ← Artículo individual
│           ├── search.php                         ← Resultados de búsqueda
│           ├── assets/
│           │   ├── js/
│           │   │   └── search.js                  ← Buscador frontend
│           │   └── images/
│           │       └── logo-default.svg           ← Logo Skydropx por defecto
│           └── template-parts/
│               ├── sidebar.php                    ← Árbol de categorías
│               ├── breadcrumb.php                 ← Migas de pan
│               └── search-input.php              ← Input reutilizable (hero/compact)
```

## Rutas importantes en el admin

| Página | URL |
|--------|-----|
| Listado de artículos | `/wp-admin/edit.php?post_type=help_article` |
| Listado de categorías | `/wp-admin/edit-tags.php?taxonomy=help_category&post_type=help_article` |
| Importar categorías | `/wp-admin/edit.php?post_type=help_article&page=sxhc-importer` |
| Importar artículos | `/wp-admin/edit.php?post_type=help_article&page=sxhc-article-importer` |
| Ordenar categorías | `/wp-admin/edit.php?post_type=help_article&page=sxhc-category-order` |
| Apariencia | Apariencia → Personalizar → Help Center |

## Opciones guardadas en la base de datos

| wp_options key | Contenido |
|----------------|-----------|
| `sxhc_categories_imported` | Boolean — si ya se importaron categorías |
| `sxhc_import_progress` | Array con progreso de importación de artículos |
| `sxhc_rewrite_version` | Versión actual de las rewrite rules (`'2'`) |
| `theme_mods_{theme}` | Todas las opciones de apariencia del Customizer |

## Meta fields

| Tipo | Meta key | Descripción |
|------|----------|-------------|
| Post | `_sxhc_seo_title` | Título SEO del artículo |
| Post | `_sxhc_keywords` | Keywords de búsqueda |
| Post | `_sxhc_tags` | Tags del artículo |
| Post | `_sxhc_title_normalized` | Título sin acentos/espacios (para búsqueda) |
| Post | `_sxhc_primary_category` | term_id de la categoría primaria (multi-categoría) |
| Post | `_sxhc_views` | Contador de visitas del artículo |
| Term | `sxhc_term_order` | Posición de la categoría en el orden drag & drop |
| Term | `sxhc_category_image` | ID del attachment de la imagen (solo categorías raíz) |
