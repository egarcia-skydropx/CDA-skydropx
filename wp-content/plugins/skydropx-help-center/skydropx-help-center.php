<?php
/**
 * Plugin Name: Skydropx Help Center
 * Plugin URI:  https://skydropx.com
 * Description: Custom Post Type y taxonomía jerárquica para el centro de ayuda de Skydropx. Soporta categorías anidadas infinitamente y campos personalizados.
 * Version:     1.0.0
 * Author:      Skydropx
 * Text Domain: skydropx-hc
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SXHC_VERSION',  '1.0.0' );
define( 'SXHC_DIR',      plugin_dir_path( __FILE__ ) );
define( 'SXHC_URL',      plugin_dir_url( __FILE__ ) );

require_once SXHC_DIR . 'includes/class-sxhc-post-type.php';
require_once SXHC_DIR . 'includes/class-sxhc-taxonomy.php';
require_once SXHC_DIR . 'includes/class-sxhc-importer.php';
require_once SXHC_DIR . 'includes/class-sxhc-article-importer.php';
require_once SXHC_DIR . 'includes/class-sxhc-admin-columns.php';
require_once SXHC_DIR . 'includes/class-sxhc-search.php';
require_once SXHC_DIR . 'includes/class-sxhc-bulk-actions.php';
require_once SXHC_DIR . 'includes/class-sxhc-appearance.php';
require_once SXHC_DIR . 'includes/class-sxhc-category-order.php';
require_once SXHC_DIR . 'includes/class-sxhc-category-meta.php';
require_once SXHC_DIR . 'includes/class-sxhc-multi-category.php';
require_once SXHC_DIR . 'includes/class-sxhc-alert-block.php';
require_once SXHC_DIR . 'includes/class-sxhc-views.php';
require_once SXHC_DIR . 'includes/class-sxhc-quick-create.php';

register_activation_hook( __FILE__,   'sxhc_activate' );
register_deactivation_hook( __FILE__, 'sxhc_deactivate' );

function sxhc_activate() {
    SXHC_Post_Type::register();
    SXHC_Taxonomy::register();
    flush_rewrite_rules();
}

function sxhc_deactivate() {
    flush_rewrite_rules();
}

add_action( 'init', array( 'SXHC_Post_Type',        'register' ) );
add_action( 'init', array( 'SXHC_Taxonomy',         'register' ) );
add_action( 'init', array( 'SXHC_Importer',         'init' ) );
add_action( 'init', array( 'SXHC_Article_Importer', 'init' ) );
add_action( 'init', array( 'SXHC_Admin_Columns',    'init' ) );
add_action( 'init', array( 'SXHC_Search',           'init' ) );
add_action( 'init', array( 'SXHC_Bulk_Actions',     'init' ) );
add_action( 'init', array( 'SXHC_Appearance',      'init' ) );
add_action( 'init', array( 'SXHC_Category_Order',  'init' ) );
add_action( 'init', array( 'SXHC_Category_Meta',    'init' ) );
add_action( 'init', array( 'SXHC_Multi_Category',  'init' ) );
add_action( 'init', array( 'SXHC_Alert_Block',     'init' ) );
add_action( 'init', array( 'SXHC_Views',           'init' ) );
add_action( 'init', array( 'SXHC_Quick_Create',    'init' ) );

// ── Soporte SVG en la media library ──────────────────────────────────────────
add_filter( 'upload_mimes', function( $mimes ) {
    $mimes['svg']  = 'image/svg+xml';
    $mimes['svgz'] = 'image/svg+xml';
    return $mimes;
} );

// Corregir el tipo MIME al subir (WordPress no lo detecta bien por defecto)
add_filter( 'wp_check_filetype_and_ext', function( $data, $file, $filename, $mimes ) {
    if ( ! $data['type'] ) {
        $ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
        if ( $ext === 'svg' || $ext === 'svgz' ) {
            $data['type'] = 'image/svg+xml';
            $data['ext']  = $ext;
        }
    }
    return $data;
}, 10, 4 );

// Mostrar SVG como imagen en la media library (sin thumbnail nativo)
add_filter( 'wp_prepare_attachment_for_js', function( $response ) {
    if ( $response['mime'] === 'image/svg+xml' ) {
        $response['sizes'] = array(
            'full' => array(
                'url'         => $response['url'],
                'width'       => 0,
                'height'      => 0,
                'orientation' => 'landscape',
            ),
        );
    }
    return $response;
} );

// Ocultar "Entradas" del menú admin (no se usa en el help center)
add_action( 'admin_menu', function() {
    remove_menu_page( 'edit.php' );
} );

// ── Reordenar y renombrar el menú admin (prioridad alta = se ejecuta al final) ──
add_action( 'admin_menu', function() {
    global $submenu;

    $key = 'edit.php?post_type=help_article';
    if ( empty( $submenu[ $key ] ) ) return;

    // Indexar por URL para fácil acceso
    $indexed = array();
    foreach ( $submenu[ $key ] as $item ) {
        $indexed[ $item[2] ] = $item;
    }

    // Renombrar entradas
    if ( isset( $indexed['post-new.php?post_type=help_article'] ) ) {
        $indexed['post-new.php?post_type=help_article'][0] = 'Nuevo Artículo';
    }
    if ( isset( $indexed['edit.php?post_type=help_article'] ) ) {
        $indexed['edit.php?post_type=help_article'][0] = 'Todos los artículos';
    }
    $tax_key = 'edit-tags.php?taxonomy=help_category&post_type=help_article';
    if ( isset( $indexed[ $tax_key ] ) ) {
        $indexed[ $tax_key ][0] = 'Categorías';
    }
    if ( isset( $indexed['sxhc-category-order'] ) ) {
        $indexed['sxhc-category-order'][0] = 'Ordenar Categorías';
    }
    if ( isset( $indexed['sxhc-article-importer'] ) ) {
        $indexed['sxhc-article-importer'][0] = 'Importar Artículos';
    }
    if ( isset( $indexed['sxhc-importer'] ) ) {
        $indexed['sxhc-importer'][0] = 'Importar Categorías';
    }

    // Orden deseado
    $order = array(
        'post-new.php?post_type=help_article',
        'edit.php?post_type=help_article',
        $tax_key,
        'sxhc-category-order',
        // 'Banner Home' — pendiente de implementar
        'sxhc-article-importer',
        'sxhc-importer',
    );

    $new_submenu = array();
    foreach ( $order as $slug ) {
        if ( isset( $indexed[ $slug ] ) ) {
            $new_submenu[] = $indexed[ $slug ];
        }
    }
    // Añadir cualquier item no contemplado al final
    foreach ( $indexed as $slug => $item ) {
        if ( ! in_array( $slug, $order, true ) ) {
            $new_submenu[] = $item;
        }
    }

    $submenu[ $key ] = $new_submenu;

}, 999 );

// Flush rewrite rules si el slug de la taxonomía cambió
add_action( 'init', function() {
    if ( get_option( 'sxhc_rewrite_version' ) !== '2' ) {
        flush_rewrite_rules();
        update_option( 'sxhc_rewrite_version', '2' );
    }
}, 99 );
