<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Soporte de múltiples categorías por artículo.
 *
 * El panel de selección vive en el sidebar de Gutenberg (PluginDocumentSettingPanel).
 * Cada artículo puede tener N categorías; una se marca como "primaria" y define
 * el breadcrumb canónico. Al navegar desde una categoría se usa ?cat={term_id}
 * para mostrar el contexto correcto.
 *
 * Meta key: _sxhc_primary_category  →  term_id de la categoría primaria
 * Taxonomía: help_category           →  wp_term_relationships nativa
 */
class SXHC_Multi_Category {

    const META_PRIMARY = '_sxhc_primary_category';

    public static function init() {
        // Quitar meta box nativo de taxonomía (lo reemplaza el panel Gutenberg)
        add_action( 'add_meta_boxes',        array( __CLASS__, 'remove_native_meta_box' ) );

        // Guardar (fallback para editor clásico / saves directos)
        add_action( 'save_post_help_article', array( __CLASS__, 'save_categories' ), 10, 2 );

        // Enqueue del panel Gutenberg + fix autosave
        add_action( 'admin_enqueue_scripts',  array( __CLASS__, 'enqueue' ) );

        // Fix autosave: eliminar borradores del navegador al abrir el editor
        add_action( 'load-post.php',     array( __CLASS__, 'delete_autosave' ) );
        add_action( 'load-post-new.php', array( __CLASS__, 'delete_autosave' ) );

        // Registrar meta para REST API (requerido por Gutenberg)
        // auth_callback necesario porque el campo empieza con _ (protegido por WP por defecto)
        register_post_meta( 'help_article', self::META_PRIMARY, array(
            'type'              => 'integer',
            'single'            => true,
            'show_in_rest'      => true,
            'default'           => 0,
            'sanitize_callback' => 'absint',
            'auth_callback'     => function() {
                return current_user_can( 'edit_posts' );
            },
        ) );
    }

    // ── Fix: aviso "copia de seguridad del navegador" ─────────────────────

    /**
     * Elimina el autosave del servidor al abrir el editor para evitar el aviso
     * "La copia de seguridad de esta entrada de tu navegador es diferente..."
     */
    public static function delete_autosave() {
        $post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
        if ( ! $post_id ) return;
        if ( get_post_type( $post_id ) !== 'help_article' ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $autosave = wp_get_post_autosave( $post_id );
        if ( $autosave ) {
            wp_delete_post( $autosave->ID, true );
        }
    }

    // ── Enqueue ───────────────────────────────────────────────────────────

    public static function enqueue( $hook ) {
        if ( $hook !== 'post.php' && $hook !== 'post-new.php' ) return;
        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== 'help_article' ) return;

        // Ocultar panel nativo de taxonomía en Gutenberg
        wp_add_inline_style( 'wp-edit-post', '
            .editor-post-taxonomies__hierarchical-terms-panel,
            .components-panel__body:has(.editor-post-taxonomies__hierarchical-terms-choice) {
                display: none !important;
            }
        ' );

        // Panel Gutenberg de categorías
        wp_enqueue_script(
            'sxhc-gutenberg-categories',
            SXHC_URL . 'assets/js/gutenberg-categories.js',
            array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-data', 'wp-components' ),
            '1.4',
            true
        );

        wp_localize_script( 'sxhc-gutenberg-categories', 'sxhcEditorData', array(
            'categoryOptions' => self::get_category_options_for_js(),
        ) );

        // Limpiar localStorage autosave para evitar el aviso del navegador
        wp_add_inline_script( 'sxhc-gutenberg-categories',
            'wp.domReady(function(){
                try {
                    Object.keys(localStorage).forEach(function(k){
                        if(k.indexOf("autosave")>-1){ localStorage.removeItem(k); }
                    });
                } catch(e){}
            });',
            'after'
        );
    }

    // ── Quitar meta box nativo ────────────────────────────────────────────

    public static function remove_native_meta_box() {
        remove_meta_box( 'help_categorydiv', 'help_article', 'side' );
    }

    // ── Guardar (fallback clásico) ────────────────────────────────────────

    public static function save_categories( $post_id, $post ) {
        if ( ! isset( $_POST['sxhc_categories_nonce'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['sxhc_categories_nonce'], 'sxhc_save_categories' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $raw      = isset( $_POST['sxhc_categories'] ) ? (array) $_POST['sxhc_categories'] : array();
        $term_ids = array_values( array_unique( array_filter( array_map( 'absint', $raw ) ) ) );
        wp_set_object_terms( $post_id, $term_ids, 'help_category' );

        $primary = isset( $_POST['sxhc_primary_category'] ) ? absint( $_POST['sxhc_primary_category'] ) : 0;
        if ( ! $primary || ! in_array( $primary, $term_ids ) ) {
            $primary = ! empty( $term_ids ) ? $term_ids[0] : 0;
        }

        if ( $primary ) {
            update_post_meta( $post_id, self::META_PRIMARY, $primary );
        } else {
            delete_post_meta( $post_id, self::META_PRIMARY );
        }
    }

    // ── Helpers públicos ──────────────────────────────────────────────────

    /**
     * Devuelve el term_id de la categoría primaria.
     * Fallback: término más profundo de los asignados.
     */
    public static function get_primary_term_id( $post_id ) {
        $primary = (int) get_post_meta( $post_id, self::META_PRIMARY, true );
        if ( $primary ) {
            $term = get_term( $primary, 'help_category' );
            if ( $term && ! is_wp_error( $term ) ) return $primary;
        }

        $terms = wp_get_object_terms( $post_id, 'help_category', array( 'fields' => 'ids' ) );
        if ( empty( $terms ) || is_wp_error( $terms ) ) return 0;

        $deepest = null;
        $max     = -1;
        foreach ( $terms as $tid ) {
            $depth = count( get_ancestors( $tid, 'help_category', 'taxonomy' ) );
            if ( $depth > $max ) { $max = $depth; $deepest = $tid; }
        }
        return $deepest ?: 0;
    }

    /**
     * Devuelve el term_id a usar como contexto activo.
     * Prioridad: ?cat={id} en URL (si el artículo pertenece a esa cat) → primaria.
     */
    public static function get_context_term_id( $post_id ) {
        $cat_param = isset( $_GET['cat'] ) ? absint( $_GET['cat'] ) : 0;
        if ( $cat_param ) {
            $assigned = wp_get_object_terms( $post_id, 'help_category', array( 'fields' => 'ids' ) );
            if ( is_array( $assigned ) && in_array( $cat_param, $assigned ) ) {
                return $cat_param;
            }
        }
        return self::get_primary_term_id( $post_id );
    }

    /**
     * Construye la ruta legible de un término: "Envíos / Creación y cotización"
     */
    public static function get_term_path( $term ) {
        $ancestors = array_reverse( get_ancestors( $term->term_id, 'help_category', 'taxonomy' ) );
        $parts     = array();
        foreach ( $ancestors as $id ) {
            $t = get_term( $id, 'help_category' );
            if ( $t && ! is_wp_error( $t ) ) $parts[] = $t->name;
        }
        $parts[] = $term->name;
        return implode( ' / ', $parts );
    }

    // ── Helpers privados ──────────────────────────────────────────────────

    private static function get_category_options_for_js() {
        $terms = get_terms( array( 'taxonomy' => 'help_category', 'hide_empty' => false, 'number' => 0 ) );
        if ( empty( $terms ) || is_wp_error( $terms ) ) return array();

        $tree    = self::build_tree( $terms );
        $options = array();
        self::flatten_tree_for_js( $tree, $options, 0 );
        return $options;
    }

    private static function build_tree( $terms, $parent = 0 ) {
        $branch = array();
        foreach ( $terms as $t ) {
            if ( (int) $t->parent === $parent ) {
                $t->children = self::build_tree( $terms, $t->term_id );
                $branch[]    = $t;
            }
        }
        usort( $branch, function( $a, $b ) { return strcmp( $a->name, $b->name ); } );
        return $branch;
    }

    private static function flatten_tree_for_js( $tree, &$options, $depth ) {
        foreach ( $tree as $term ) {
            $indent    = str_repeat( '  ', $depth ) . ( $depth > 0 ? '↳ ' : '' );
            $options[] = array( 'value' => (string) $term->term_id, 'label' => $indent . $term->name );
            if ( ! empty( $term->children ) ) {
                self::flatten_tree_for_js( $term->children, $options, $depth + 1 );
            }
        }
    }
}
