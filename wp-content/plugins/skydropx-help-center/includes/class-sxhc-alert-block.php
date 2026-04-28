<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Bloque Gutenberg de Alertas — Caelus Design System
 *
 * Tipos: success, danger, info, warning, default, orange
 * Propiedades: heading, body (RichText), botón CTA, link
 */
class SXHC_Alert_Block {

    public static function init() {
        // register_block se llama directo — no via hook dentro de init
        self::register_block();
        add_action( 'enqueue_block_assets',        array( __CLASS__, 'enqueue_styles' ) );
        add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_editor' ) );
    }

    // ── Registrar el bloque ───────────────────────────────────────────────

    public static function register_block() {
        if ( ! function_exists( 'register_block_type' ) ) return;

        register_block_type( 'sxhc/alert', array(
            'editor_script' => 'sxhc-alert-block',
            'editor_style'  => 'sxhc-alerts-css',
            'style'         => 'sxhc-alerts-css',
            'attributes'    => array(
                'alertType'   => array( 'type' => 'string',  'default' => 'info' ),
                'showHeading' => array( 'type' => 'boolean', 'default' => true ),
                'heading'     => array( 'type' => 'string',  'default' => '' ),
                'showBody'    => array( 'type' => 'boolean', 'default' => true ),
                'body'        => array( 'type' => 'string',  'default' => '' ),
                'showButton'  => array( 'type' => 'boolean', 'default' => false ),
                'buttonText'  => array( 'type' => 'string',  'default' => 'View more' ),
                'buttonUrl'   => array( 'type' => 'string',  'default' => '' ),
                'showLink'    => array( 'type' => 'boolean', 'default' => false ),
                'linkText'    => array( 'type' => 'string',  'default' => 'Link example info here' ),
                'linkUrl'     => array( 'type' => 'string',  'default' => '' ),
            ),
        ) );
    }

    // ── Estilos (frontend + editor) ───────────────────────────────────────

    public static function enqueue_styles() {
        wp_register_style(
            'sxhc-alerts-css',
            SXHC_URL . 'assets/css/alerts.css',
            array(),
            '1.0'
        );
    }

    // ── Script del editor ─────────────────────────────────────────────────

    public static function enqueue_editor() {
        wp_enqueue_script(
            'sxhc-alert-block',
            SXHC_URL . 'assets/js/alert-block.js',
            array(
                'wp-blocks',
                'wp-block-editor',
                'wp-element',
                'wp-components',
                'wp-i18n',
            ),
            '1.2',
            true
        );
    }
}
