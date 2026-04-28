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

// Flush rewrite rules si el slug de la taxonomía cambió
add_action( 'init', function() {
    if ( get_option( 'sxhc_rewrite_version' ) !== '2' ) {
        flush_rewrite_rules();
        update_option( 'sxhc_rewrite_version', '2' );
    }
}, 99 );
