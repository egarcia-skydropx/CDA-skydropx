# Skydropx Help Center

Centro de ayuda personalizado construido sobre WordPress con Custom Post Type, taxonomía jerárquica ilimitada, buscador inteligente y panel de administración propio.

![WordPress](https://img.shields.io/badge/WordPress-6.x-21759b?logo=wordpress) ![PHP](https://img.shields.io/badge/PHP-7.2%2B-777bb3?logo=php) ![Tailwind CSS](https://img.shields.io/badge/Tailwind-CDN-38bdf8?logo=tailwindcss)

---

## Stack

| Capa | Tecnología |
|------|-----------|
| CMS | WordPress 6.x |
| Backend | PHP 7.2+ |
| Frontend | Tailwind CSS (CDN) |
| Base de datos | MySQL (via MAMP) |
| Local server | MAMP |

---

## Requisitos

- MAMP (o cualquier servidor local con Apache + MySQL + PHP 7.2+)
- WordPress instalado en `htdocs/help-center`
- Base de datos: `skydropx_help`

---

## Instalación

```bash
# 1. Clonar el repositorio en la carpeta de WordPress
git clone <repo-url> /Applications/MAMP/htdocs/help-center

# 2. Iniciar MAMP y verificar que Apache + MySQL estén activos

# 3. Activar el plugin desde wp-admin
wp-admin → Plugins → Skydropx Help Center → Activar

# 4. Activar el tema
wp-admin → Apariencia → Temas → Skydropx Help Center → Activar

# 5. Flush de rewrite rules
wp-admin → Ajustes → Enlaces permanentes → Guardar cambios
```

---

## Arquitectura

El proyecto usa **Custom Post Type + Custom Taxonomy** para máxima flexibilidad, ya que los campos personalizados se agregan continuamente.

```
Plugin: skydropx-help-center   →   toda la lógica de negocio
Tema:   skydropx-help          →   frontend con Tailwind
```

### Custom Post Type: `help_article`
Artículos del centro de ayuda. URL: `/ayuda/{slug}/`

### Custom Taxonomy: `help_category`
Categorías jerárquicas sin límite de profundidad. URL: `/categoria/{slug}/`

```
Envíos                        ← categoría raíz
└── Creación y cotización      ← subcategoría
    └── Crear envío            ← sub-subcategoría → artículos aquí
```

---

## Estructura del proyecto

```
help-center/
├── docs/                          ← Documentación técnica completa
│   ├── README.md                  ← Arquitectura y decisiones técnicas
│   ├── plugin-clases.md           ← Referencia de clases PHP
│   ├── estructura-archivos.md     ← Árbol de archivos y base de datos
│   └── guia-uso.md                ← Guías paso a paso
├── wp-content/
│   ├── imports/                   ← Datos fuente del importador
│   │   ├── indice-categorias.md   ← Fuente de categorías (Markdown)
│   │   └── *.csv                  ← Fuente de artículos
│   ├── plugins/
│   │   └── skydropx-help-center/  ← Plugin principal
│   └── themes/
│       └── skydropx-help/         ← Tema personalizado
├── README.md                      ← Este archivo
└── (archivos core de WordPress)
```

---

## Funcionalidades

### Importación de contenido
- **Categorías** desde `indice-categorias.md` — jerarquía definida con niveles de `#`
- **Artículos** desde CSV de Webflow — 3,127 artículos con batch AJAX de 50 en 50, reanudable

### Buscador inteligente
- Búsqueda sin fricción: sin acentos, sin espacios, sin mayúsculas
- `envios` = `Envíos` = `ENVÍOS` = `envíos.`
- Dropdown en vivo con debounce (280ms), highlight de coincidencias, navegación por teclado
- Dos variantes: hero (homepage) y compact (header)

### Panel de administración
- **Ordenar categorías** — drag & drop jerárquico con guardado automático
- **Agregar categorías** — modal inline sin salir de la pantalla
- **Acciones masivas** — cambiar categoría de artículos o mover categorías de padre en bulk
- **Columnas personalizadas** — "Categoría" (chip clickeable) y "Ruta" (breadcrumb)

### Apariencia
- **Customizer de WordPress** — panel "Help Center" con preview en tiempo real
- Logo, colores, fondos, tipografía, radio de cards, textos del hero
- Logo Skydropx por defecto — reemplazable sin código
- **Imagen por categoría** — solo en categorías raíz, se oculta al anidar

---

## Documentación técnica

Ver la carpeta [`docs/`](./docs/README.md) para documentación completa:

- [Arquitectura y decisiones técnicas](./docs/README.md)
- [Referencia de clases PHP](./docs/plugin-clases.md)
- [Estructura de archivos y base de datos](./docs/estructura-archivos.md)
- [Guía de uso paso a paso](./docs/guia-uso.md)

---

## Desarrollo

### Convenciones
- Prefijo de clases PHP: `SXHC_`
- Prefijo de opciones/meta: `sxhc_`
- PHP 7.2+ compatible — sin `match()` ni arrow functions `fn()`

### Agregar una nueva clase al plugin

1. Crear `includes/class-sxhc-nueva-clase.php`
2. Agregar `require_once` en `skydropx-help-center.php`
3. Agregar `add_action( 'init', array( 'SXHC_Nueva_Clase', 'init' ) )`

### Agregar un campo personalizado a artículos

Los artículos ya tienen soporte para `custom-fields`. En el editor de Gutenberg, activar "Campos personalizados" desde ⚙️ → Preferencias → Paneles.

Para registrar un campo con tipo y validación:

```php
register_post_meta( 'help_article', 'mi_campo', array(
    'type'         => 'string',
    'single'       => true,
    'show_in_rest' => true,
    'sanitize_callback' => 'sanitize_text_field',
) );
```

---

## Autor

**Emmanuel Garcia** — Product Designer, Design System @ Skydropx
