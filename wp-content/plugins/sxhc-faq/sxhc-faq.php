<?php
/**
 * Plugin Name:  SXHC FAQ - Preguntas Frecuentes
 * Description:  Gestión de preguntas frecuentes con layout masonry de 2 columnas y acordeón.
 * Version:      1.0.0
 * Author:       Skydropx
 * Text Domain:  sxhc-faq
 * Requires at least: 6.0
 * Requires PHP: 7.2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SXHC_FAQ_CPT',      'sxhc_faq' );
define( 'SXHC_FAQ_TAX',      'sxhc_faq_cat' );
define( 'SXHC_FAQ_META_ORD', 'sxhc_faq_cat_order' );

class SXHC_FAQ {

    // ── Bootstrap ──────────────────────────────────────────────────────────

    public static function init() {
        add_action( 'init',               array( __CLASS__, 'register_cpt' ) );
        add_action( 'init',               array( __CLASS__, 'register_taxonomy' ) );
        add_action( 'init',               array( __CLASS__, 'register_shortcode' ) );
        add_action( 'init',               array( __CLASS__, 'register_rewrite_rule' ) );
        add_filter( 'query_vars',         array( __CLASS__, 'register_query_var' ) );
        add_action( 'template_redirect',  array( __CLASS__, 'maybe_render_faq_page' ) );
        add_filter( 'document_title_parts', array( __CLASS__, 'faq_page_title' ) );
        add_filter( 'pre_handle_404',     array( __CLASS__, 'prevent_faq_404' ) );
        add_action( 'admin_menu',         array( __CLASS__, 'setup_admin_menu' ) );
        add_action( 'admin_head',         array( __CLASS__, 'force_menu_icon' ) );
        add_action( 'add_meta_boxes_' . SXHC_FAQ_CPT, array( __CLASS__, 'force_taxonomy_metabox' ) );
        add_filter( 'manage_' . SXHC_FAQ_CPT . '_posts_columns',        array( __CLASS__, 'admin_columns' ) );
        add_action( 'manage_' . SXHC_FAQ_CPT . '_posts_custom_column',  array( __CLASS__, 'admin_column_content' ), 10, 2 );
        add_filter( 'manage_edit-' . SXHC_FAQ_CPT . '_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
        register_activation_hook( __FILE__, array( __CLASS__, 'on_activate' ) );
    }

    // ── Activation ─────────────────────────────────────────────────────────

    public static function on_activate() {
        self::register_cpt();
        self::register_taxonomy();
        self::register_rewrite_rule();
        flush_rewrite_rules();
        // Guardar versión para detectar cambios futuros
        update_option( 'sxhc_faq_version', '1.0.0' );
    }

    // ── URL virtual /preguntas-frecuentes ─────────────────────────────────

    public static function register_rewrite_rule() {
        add_rewrite_rule(
            '^preguntas-frecuentes/?$',
            'index.php?sxhc_faq_page=1',
            'top'
        );
    }

    public static function register_query_var( $vars ) {
        $vars[] = 'sxhc_faq_page';
        return $vars;
    }

    private static function is_faq_page() {
        $path = trim( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' );
        // Soporta instalaciones en subdirectorio (ej. /help-center/preguntas-frecuentes)
        $base = trim( parse_url( home_url(), PHP_URL_PATH ), '/' );
        if ( $base ) {
            $path = ltrim( substr( $path, strlen( $base ) ), '/' );
        }
        return $path === 'preguntas-frecuentes';
    }

    public static function maybe_render_faq_page() {
        if ( ! self::is_faq_page() ) return;

        // body_class() y otras funciones del tema necesitan un $post global.
        // Creamos un objeto ficticio para que no haya warnings.
        global $post, $wp_query;

        $dummy = new WP_Post( (object) array(
            'ID'                    => 0,
            'post_status'           => 'publish',
            'post_author'           => 0,
            'post_parent'           => 0,
            'post_type'             => 'page',
            'post_date'             => '',
            'post_date_gmt'         => '',
            'post_modified'         => '',
            'post_modified_gmt'     => '',
            'post_content'          => '',
            'post_title'            => 'Preguntas frecuentes',
            'post_excerpt'          => '',
            'post_content_filtered' => '',
            'post_mime_type'        => '',
            'post_password'         => '',
            'post_name'             => 'preguntas-frecuentes',
            'guid'                  => home_url( '/preguntas-frecuentes/' ),
            'menu_order'            => 0,
            'to_ping'               => '',
            'pinged'                => '',
            'comment_count'         => 0,
            'comment_status'        => 'closed',
            'ping_status'           => 'closed',
            'filter'                => 'raw',
        ) );

        $post = $dummy;

        $wp_query->post              = $dummy;
        $wp_query->posts             = array( $dummy );
        $wp_query->queried_object    = $dummy;
        $wp_query->queried_object_id = 0;
        $wp_query->found_posts       = 1;
        $wp_query->post_count        = 1;
        $wp_query->is_page           = true;
        $wp_query->is_singular       = true;
        $wp_query->is_404            = false;
        $wp_query->is_home           = false;

        setup_postdata( $dummy );
        status_header( 200 );

        // Renderizar usando el header y footer del tema activo
        get_header();
        ?>
        <div style="max-width:1100px; margin:0 auto; padding:40px 24px 64px;">
            <h1 style="font-size:24px; font-weight:700; color:#111827; margin:0 0 8px;">
                Preguntas frecuentes
            </h1>
            <p style="color:#6b7280; font-size:14px; margin:0 0 32px;">
                Encuentra respuestas a las dudas más comunes.
            </p>
            <?php self::render_faq_page( array( 'category' => '' ) ); ?>
        </div>
        <?php
        get_footer();
        exit;
    }

    public static function faq_page_title( $title ) {
        if ( self::is_faq_page() ) {
            $title['title'] = 'Preguntas frecuentes';
        }
        return $title;
    }

    // Evitar que WP lance 404 en esta URL virtual
    public static function prevent_faq_404( $preempt ) {
        if ( self::is_faq_page() ) {
            return true; // le dice a WP que ya manejamos este request
        }
        return $preempt;
    }

    // ── Forzar metabox en editor clásico (fallback) ────────────────────────

    public static function force_taxonomy_metabox() {
        // Cuando el metabox no aparece solo, lo registramos manualmente
        if ( function_exists( 'post_categories_meta_box' ) ) {
            add_meta_box(
                'sxhc-faq-cat-metabox',
                'Categoría',
                'post_categories_meta_box',
                SXHC_FAQ_CPT,
                'side',
                'default',
                array( '__back_compat_meta_box' => false, 'taxonomy' => SXHC_FAQ_TAX )
            );
        }
    }

    // ── CPT: sxhc_faq ──────────────────────────────────────────────────────

    public static function register_cpt() {
        register_post_type( SXHC_FAQ_CPT, array(
            'labels' => array(
                'name'               => 'Preguntas frecuentes',
                'singular_name'      => 'Pregunta frecuente',
                'add_new'            => 'Nueva pregunta',
                'add_new_item'       => 'Agregar nueva pregunta',
                'edit_item'          => 'Editar pregunta',
                'new_item'           => 'Nueva pregunta',
                'view_item'          => 'Ver pregunta',
                'search_items'       => 'Buscar preguntas',
                'not_found'          => 'No se encontraron preguntas.',
                'not_found_in_trash' => 'No hay preguntas en la papelera.',
                'all_items'          => 'Todas las preguntas',
            ),
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'show_in_rest'       => true,
            'supports'           => array( 'title', 'editor', 'page-attributes' ),
            'taxonomies'         => array( SXHC_FAQ_TAX ),
            'menu_icon'          => 'dashicons-format-qa',
            'menu_position'      => 7,
            'rewrite'            => false,
        ) );
    }

    // ── Taxonomy: sxhc_faq_cat ─────────────────────────────────────────────

    public static function register_taxonomy() {
        register_taxonomy( SXHC_FAQ_TAX, SXHC_FAQ_CPT, array(
            'labels' => array(
                'name'              => 'Categorías',
                'singular_name'     => 'Categoría',
                'search_items'      => 'Buscar categorías',
                'all_items'         => 'Todas las categorías',
                'edit_item'         => 'Editar categoría',
                'update_item'       => 'Actualizar categoría',
                'add_new_item'      => 'Agregar categoría',
                'new_item_name'     => 'Nombre de categoría',
                'menu_name'         => 'Categorías',
            ),
            'hierarchical'          => true,   // panel de checkboxes, no tags
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => false,  // lo añadimos manualmente
            'show_in_rest'          => true,   // visible en el editor de bloques
            'show_admin_column'     => true,
            'rewrite'               => false,
            'meta_box_cb'           => null,   // usa el metabox por defecto de WP
        ) );

        // Garantía: asociar la taxonomía al CPT explícitamente
        register_taxonomy_for_object_type( SXHC_FAQ_TAX, SXHC_FAQ_CPT );
    }

    // ── Admin menu ─────────────────────────────────────────────────────────

    public static function setup_admin_menu() {
        // El menú principal lo crea WordPress automáticamente via show_in_menu + menu_icon en el CPT.
        // Solo añadimos los submenús extra aquí.

        // Submenú: todas las preguntas
        add_submenu_page(
            'edit.php?post_type=' . SXHC_FAQ_CPT,
            'Todas las preguntas',
            'Todas las preguntas',
            'edit_posts',
            'edit.php?post_type=' . SXHC_FAQ_CPT
        );

        // Submenú: nueva pregunta
        add_submenu_page(
            'edit.php?post_type=' . SXHC_FAQ_CPT,
            'Nueva pregunta',
            'Nueva pregunta',
            'edit_posts',
            'post-new.php?post_type=' . SXHC_FAQ_CPT
        );

        // Submenú: categorías
        add_submenu_page(
            'edit.php?post_type=' . SXHC_FAQ_CPT,
            'Categorías',
            'Categorías',
            'manage_categories',
            'edit-tags.php?taxonomy=' . SXHC_FAQ_TAX . '&post_type=' . SXHC_FAQ_CPT
        );
    }

    // ── Forzar ícono del menú vía CSS (más confiable que menu_icon en CPT) ───

    public static function force_menu_icon() {
        ?>
        <style>
            #adminmenu .menu-icon-sxhc_faq div.wp-menu-image::before {
                content: "\f223" !important; /* dashicons-editor-help */
                font-family: dashicons !important;
            }
        </style>
        <?php
    }

    // ── Admin list columns ──────────────────────────────────────────────────

    public static function admin_columns( $columns ) {
        $new = array();
        foreach ( $columns as $key => $label ) {
            $new[ $key ] = $label;
            if ( $key === 'title' ) {
                $new['faq_category'] = 'Categoría';
            }
        }
        unset( $new['date'] );
        $new['date'] = 'Fecha';
        return $new;
    }

    public static function admin_column_content( $column, $post_id ) {
        if ( $column === 'faq_category' ) {
            $terms = get_the_terms( $post_id, SXHC_FAQ_TAX );
            if ( $terms && ! is_wp_error( $terms ) ) {
                $names = wp_list_pluck( $terms, 'name' );
                echo esc_html( implode( ', ', $names ) );
            } else {
                echo '<span style="color:#aaa;">—</span>';
            }
        }
    }

    public static function sortable_columns( $columns ) {
        $columns['faq_category'] = 'faq_category';
        return $columns;
    }

    // ── Shortcode ───────────────────────────────────────────────────────────

    public static function register_shortcode() {
        add_shortcode( 'preguntas_frecuentes', array( __CLASS__, 'render_shortcode' ) );
    }

    public static function render_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'category' => '', // slug de categoría para filtrar (opcional)
        ), $atts, 'preguntas_frecuentes' );

        ob_start();
        self::render_faq_page( $atts );
        return ob_get_clean();
    }

    // ── Frontend render ─────────────────────────────────────────────────────

    private static function render_faq_page( $atts ) {
        // 1. Obtener categorías
        $tax_args = array(
            'taxonomy'   => SXHC_FAQ_TAX,
            'hide_empty' => true,
            'orderby'    => 'meta_value_num',
            'order'      => 'ASC',
            'meta_query' => array(
                'relation' => 'OR',
                array( 'key' => SXHC_FAQ_META_ORD, 'compare' => 'EXISTS' ),
                array( 'key' => SXHC_FAQ_META_ORD, 'compare' => 'NOT EXISTS' ),
            ),
        );

        if ( ! empty( $atts['category'] ) ) {
            $tax_args['slug'] = sanitize_text_field( $atts['category'] );
        }

        $categories = get_terms( $tax_args );
        if ( is_wp_error( $categories ) || empty( $categories ) ) {
            echo '<p style="color:#666;">No hay preguntas frecuentes publicadas todavía.</p>';
            return;
        }

        // 2. Cargar preguntas de cada categoría
        $cat_data = array();
        foreach ( $categories as $cat ) {
            $posts = get_posts( array(
                'post_type'      => SXHC_FAQ_CPT,
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'menu_order',
                'order'          => 'ASC',
                'tax_query'      => array( array(
                    'taxonomy' => SXHC_FAQ_TAX,
                    'field'    => 'term_id',
                    'terms'    => $cat->term_id,
                ) ),
            ) );

            if ( ! empty( $posts ) ) {
                $cat_data[] = array(
                    'term'   => $cat,
                    'posts'  => $posts,
                    'weight' => count( $posts ),
                );
            }
        }

        if ( empty( $cat_data ) ) {
            echo '<p style="color:#666;">No hay preguntas frecuentes publicadas todavía.</p>';
            return;
        }

        // 3. Algoritmo greedy para asignar categorías a 2 columnas
        //    Siempre asigna la siguiente categoría a la columna con menos peso acumulado
        usort( $cat_data, function( $a, $b ) {
            return $b['weight'] - $a['weight']; // descendente: primero las más largas
        } );

        $col1 = array();
        $col2 = array();
        $w1   = 0;
        $w2   = 0;

        foreach ( $cat_data as $item ) {
            if ( $w1 <= $w2 ) {
                $col1[] = $item;
                $w1    += $item['weight'];
            } else {
                $col2[] = $item;
                $w2    += $item['weight'];
            }
        }

        // 4. Imprimir estilos (solo una vez)
        self::print_faq_styles();

        // 5. HTML
        ?>
        <div class="sxhc-faq-wrap">
            <div class="sxhc-faq-grid">

                <div class="sxhc-faq-col">
                    <?php foreach ( $col1 as $block ) : ?>
                        <?php self::render_category_block( $block ); ?>
                    <?php endforeach; ?>
                </div>

                <div class="sxhc-faq-col">
                    <?php foreach ( $col2 as $block ) : ?>
                        <?php self::render_category_block( $block ); ?>
                    <?php endforeach; ?>
                </div>

            </div>
        </div>
        <?php
    }

    private static function render_category_block( $block ) {
        $cat   = $block['term'];
        $posts = $block['posts'];
        ?>
        <div class="sxhc-faq-category">
            <h3 class="sxhc-faq-cat-title">
                <?php echo esc_html( $cat->name ); ?>
            </h3>
            <div class="sxhc-faq-items">
                <?php foreach ( $posts as $post ) : ?>
                    <details class="sxhc-faq-item">
                        <summary class="sxhc-faq-question">
                            <span class="sxhc-faq-q-text"><?php echo esc_html( $post->post_title ); ?></span>
                            <span class="sxhc-faq-chevron" aria-hidden="true">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        </summary>
                        <div class="sxhc-faq-answer">
                            <?php echo wp_kses_post( apply_filters( 'the_content', $post->post_content ) ); ?>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    // ── Frontend styles ─────────────────────────────────────────────────────

    private static function print_faq_styles() {
        static $printed = false;
        if ( $printed ) return;
        $printed = true;
        ?>
        <style id="sxhc-faq-styles">

        /* ── Layout ──────────────────────────────────────────────────── */
        .sxhc-faq-wrap {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        .sxhc-faq-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            align-items: start; /* clave: las columnas no se estiran */
        }
        .sxhc-faq-col {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* ── Bloque de categoría ─────────────────────────────────────── */
        .sxhc-faq-category {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
        }
        .sxhc-faq-cat-title {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            padding: 14px 18px;
            font-size: 14px;
            font-weight: 700;
            color: #111827;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
            letter-spacing: .01em;
        }
        .sxhc-faq-items {
            padding: 4px 0;
        }

        /* ── Acordeón ────────────────────────────────────────────────── */
        .sxhc-faq-item {
            border-bottom: 1px solid #f3f4f6;
        }
        .sxhc-faq-item:last-child {
            border-bottom: none;
        }
        /* Zebra: alterna gris/blanco para diferenciar preguntas en la lista */
        .sxhc-faq-item:nth-child(odd) {
            background: #f9fafb;
        }
        .sxhc-faq-question {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 13px 18px;
            cursor: pointer;
            list-style: none;
            user-select: none;
            transition: background .12s;
        }
        .sxhc-faq-question::-webkit-details-marker { display: none; }
        .sxhc-faq-question::marker { display: none; }
        .sxhc-faq-question:hover {
            background: #f3f4f6;
        }
        .sxhc-faq-q-text {
            font-size: 14px;
            font-weight: 500;
            color: #111827;
            line-height: 1.45;
            flex: 1;
        }
        .sxhc-faq-chevron {
            flex-shrink: 0;
            color: #9ca3af;
            display: flex;
            align-items: center;
            transition: transform .2s ease;
        }
        .sxhc-faq-item[open] > .sxhc-faq-question .sxhc-faq-chevron {
            transform: rotate(180deg);
        }
        .sxhc-faq-item[open] > .sxhc-faq-question {
            background: #f3f4f6;
        }
        .sxhc-faq-answer {
            padding: 2px 18px 16px 18px;
            font-size: 14px;
            color: #374151;
            line-height: 1.65;
        }
        .sxhc-faq-answer p { margin: 0 0 .6em; }
        .sxhc-faq-answer p:last-child { margin-bottom: 0; }
        .sxhc-faq-answer a { color: #6d28d9; text-decoration: underline; }
        .sxhc-faq-answer a:hover { color: #4c1d95; }
        .sxhc-faq-answer ul, .sxhc-faq-answer ol {
            padding-left: 20px;
            margin: 4px 0 8px;
        }

        /* ── Responsive ──────────────────────────────────────────────── */
        @media (max-width: 700px) {
            .sxhc-faq-grid {
                grid-template-columns: 1fr;
            }
        }

        </style>
        <?php
    }
}

SXHC_FAQ::init();
