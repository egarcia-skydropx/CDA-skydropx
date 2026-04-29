<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ─── Includes ────────────────────────────────────────────────────────────────
require_once get_template_directory() . '/inc/navbar-data.php';

// ─── Soporte del tema ────────────────────────────────────────────────────────
add_action( 'after_setup_theme', function() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', array( 'search-form', 'comment-form', 'gallery' ) );
} );

// ─── Scripts y estilos ───────────────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style( 'sxhc-theme', get_stylesheet_uri() );

    // Navbar
    wp_enqueue_style(
        'sxhc-navbar',
        get_template_directory_uri() . '/assets/css/navbar.css',
        array(),
        '1.0.0'
    );
    wp_enqueue_script(
        'sxhc-navbar',
        get_template_directory_uri() . '/assets/js/navbar.js',
        array(),
        '1.0.0',
        true
    );

    wp_enqueue_script(
        'sxhc-search',
        get_template_directory_uri() . '/assets/js/search.js',
        array(),
        '1.0.0',
        true // en el footer
    );

    wp_localize_script( 'sxhc-search', 'sxhcData', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'homeUrl' => home_url( '/' ),
    ) );
} );

add_action( 'wp_head', function() {
    echo '<script src="https://cdn.tailwindcss.com"></script>' . "\n";
    echo '<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    brand: { DEFAULT: "#4B47D6", light: "#EEF0FF", hover: "#3d3ab5" }
                }
            }
        }
    }
    </script>' . "\n";
    echo '<style>
    /* Espaciado entre elementos en el cuerpo del artículo */
    .article-content img,
    .article-content figure,
    .article-content table,
    .article-content .sxhc-alert,
    .article-content iframe,
    .article-content video,
    .article-content pre,
    .article-content blockquote {
        margin-top: 2rem;
        margin-bottom: 2rem;
    }
    .article-content figure figcaption {
        margin-top: 0;
    }
    /* Links con color primario para contraste */
    .article-content a {
        color: var(--brand);
        text-decoration: underline;
        text-underline-offset: 2px;
    }
    .article-content a:hover {
        opacity: 0.8;
    }
    </style>' . "\n";
} );

// ─── Helpers de categorías ───────────────────────────────────────────────────

/**
 * Devuelve el breadcrumb de un término como array de objetos WP_Term,
 * desde la raíz hasta el término actual.
 */
function sxhc_get_term_breadcrumb( $term ) {
    $crumbs    = array();
    $ancestor_ids = array_reverse( get_ancestors( $term->term_id, 'help_category', 'taxonomy' ) );
    foreach ( $ancestor_ids as $id ) {
        $t = get_term( $id, 'help_category' );
        if ( $t && ! is_wp_error( $t ) ) $crumbs[] = $t;
    }
    $crumbs[] = $term;
    return $crumbs;
}

/**
 * Devuelve el breadcrumb de un artículo como array de objetos WP_Term.
 * Usa la categoría contextual (?cat=) si está disponible y es válida,
 * de lo contrario usa la categoría primaria marcada en el editor.
 */
function sxhc_get_post_breadcrumb( $post_id ) {
    // Usar el sistema multi-categoría si está disponible
    if ( class_exists( 'SXHC_Multi_Category' ) ) {
        $term_id = SXHC_Multi_Category::get_context_term_id( $post_id );
        if ( $term_id ) {
            $term = get_term( $term_id, 'help_category' );
            if ( $term && ! is_wp_error( $term ) ) {
                return sxhc_get_term_breadcrumb( $term );
            }
        }
    }

    // Fallback: término más profundo
    $terms = get_the_terms( $post_id, 'help_category' );
    if ( ! $terms || is_wp_error( $terms ) ) return array();

    $deepest = null;
    $max     = -1;
    foreach ( $terms as $t ) {
        $depth = count( get_ancestors( $t->term_id, 'help_category', 'taxonomy' ) );
        if ( $depth > $max ) { $max = $depth; $deepest = $t; }
    }
    return sxhc_get_term_breadcrumb( $deepest );
}

/**
 * Cuenta artículos publicados en un término y todos sus descendientes.
 */
function sxhc_count_term_posts_recursive( $term_id ) {
    $count    = 0;
    $children = get_terms( array(
        'taxonomy'   => 'help_category',
        'parent'     => $term_id,
        'hide_empty' => false,
        'fields'     => 'ids',
    ) );

    // Posts directamente asignados a este término
    $direct = new WP_Query( array(
        'post_type'      => 'help_article',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'tax_query'      => array( array(
            'taxonomy'         => 'help_category',
            'field'            => 'term_id',
            'terms'            => $term_id,
            'include_children' => false,
        ) ),
    ) );
    $count += $direct->found_posts;

    // Posts en hijos recursivamente
    if ( ! empty( $children ) && ! is_wp_error( $children ) ) {
        foreach ( $children as $child_id ) {
            $count += sxhc_count_term_posts_recursive( $child_id );
        }
    }

    return $count;
}

/**
 * Renderiza el sidebar recursivo de categorías.
 * Expande la rama activa (término actual + sus ancestros).
 */
function sxhc_render_sidebar( $parent_id = 0, $active_ids = array(), $depth = 0 ) {
    $terms = class_exists( 'SXHC_Category_Order' )
        ? SXHC_Category_Order::get_ordered_terms( $parent_id )
        : get_terms( array( 'taxonomy' => 'help_category', 'parent' => $parent_id, 'hide_empty' => false ) );

    if ( empty( $terms ) || is_wp_error( $terms ) ) return;

    $indent = $depth > 0 ? 'pl-3 border-l border-gray-100' : '';
    echo '<ul class="' . esc_attr( $indent ) . ' space-y-0.5">';

    foreach ( $terms as $term ) {
        // Un término es "activo" si es el término actual O un ancestro de él
        $is_active = in_array( $term->term_id, $active_ids );

        $has_children = (bool) get_terms( array(
            'taxonomy'   => 'help_category',
            'parent'     => $term->term_id,
            'hide_empty' => false,
            'number'     => 1,
            'fields'     => 'ids',
        ) );

        $link_class  = 'flex items-center gap-1.5 py-1 px-2 rounded text-sm transition-colors ';
        $link_class .= $is_active
            ? 'bg-brand-light text-brand font-semibold'
            : 'text-gray-600 hover:text-brand hover:bg-brand-light';

        echo '<li>';
        printf(
            '<a href="%s" class="%s">%s</a>',
            esc_url( get_term_link( $term ) ),
            esc_attr( $link_class ),
            esc_html( $term->name )
        );

        // Expandir hijos solo si este término está en la ruta activa
        if ( $has_children && $is_active ) {
            sxhc_render_sidebar( $term->term_id, $active_ids, $depth + 1 );
        }

        echo '</li>';
    }

    echo '</ul>';
}
