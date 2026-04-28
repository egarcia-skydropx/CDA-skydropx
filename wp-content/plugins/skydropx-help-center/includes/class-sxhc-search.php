<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SXHC_Search {

    public static function init() {
        add_action( 'wp_ajax_sxhc_search',        array( __CLASS__, 'handle_search' ) );
        add_action( 'wp_ajax_nopriv_sxhc_search', array( __CLASS__, 'handle_search' ) );
        add_action( 'save_post_help_article',      array( __CLASS__, 'update_normalized_meta' ) );
    }

    // ------------------------------------------------------------------ //
    //  Normalizacion                                                       //
    // ------------------------------------------------------------------ //

    /**
     * Convierte cualquier texto en una cadena minuscula, sin acentos,
     * sin espacios ni signos de puntuacion.
     *
     * Ejemplos:
     *   "Centro de Atencion"  -> "centrodeatencion"
     *   "CENTRO DE ATENCION"  -> "centrodeatencion"
     *   "centrodeatencion"    -> "centrodeatencion"
     *   "Envios"              -> "envios"
     *   "Envios internac."    -> "enviosinternac"
     */
    public static function normalize( $text ) {
        // 1. Minusculas en UTF-8
        $text = mb_strtolower( $text, 'UTF-8' );

        // 2. Transliteracion: convierte caracteres acentuados a su base ASCII
        //    (a -> a, e -> e, n -> n, u -> u, etc.)
        //    iconv con TRANSLIT maneja todos los diacriticos automaticamente.
        $translit = @iconv( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', $text );
        if ( false !== $translit ) {
            $text = $translit;
        }

        // 3. Eliminar todo lo que no sea letra o numero
        $text = preg_replace( '/[^a-z0-9]/', '', $text );

        return $text;
    }

    /**
     * Guarda el titulo normalizado como meta para busquedas rapidas.
     */
    public static function update_normalized_meta( $post_id ) {
        $title = get_the_title( $post_id );
        update_post_meta( $post_id, '_sxhc_title_normalized', self::normalize( $title ) );
    }

    // ------------------------------------------------------------------ //
    //  AJAX endpoint                                                       //
    // ------------------------------------------------------------------ //

    public static function handle_search() {
        $raw = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
        $raw = trim( $raw );

        if ( strlen( $raw ) < 2 ) {
            wp_send_json_success( array( 'results' => array(), 'total' => 0 ) );
        }

        $normalized_q = self::normalize( $raw );
        $results      = self::search( $raw, $normalized_q, 8 );

        wp_send_json_success( array(
            'results' => $results,
            'total'   => count( $results ),
            'query'   => $raw,
        ) );
    }

    // ------------------------------------------------------------------ //
    //  Logica de busqueda (3 estrategias fusionadas)                      //
    // ------------------------------------------------------------------ //

    /**
     * 1. WP_Query nativa  -> coincidencias en titulo y contenido
     * 2. Meta normalizado -> busqueda sin acentos/espacios via LIKE
     * 3. Taxonomia        -> si el query coincide con una categoria
     */
    private static function search( $raw, $normalized_q, $limit = 8 ) {
        global $wpdb;
        $found = array();

        // -- 1. Busqueda estandar de WordPress --------------------------------
        $std = new WP_Query( array(
            'post_type'      => 'help_article',
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            's'              => $raw,
            'fields'         => 'ids',
        ) );
        foreach ( $std->posts as $id ) {
            $found[ $id ] = ( isset( $found[ $id ] ) ? $found[ $id ] : 0 ) + 10;
        }

        // -- 2. Busqueda por meta normalizado ---------------------------------
        $like     = '%' . $wpdb->esc_like( $normalized_q ) . '%';
        $meta_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta}
             WHERE meta_key = '_sxhc_title_normalized'
               AND meta_value LIKE %s
             LIMIT %d",
            $like,
            $limit * 2
        ) );

        if ( $meta_ids ) {
            $placeholders = implode( ',', array_fill( 0, count( $meta_ids ), '%d' ) );
            $valid = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts}
                     WHERE ID IN ($placeholders)
                       AND post_type = 'help_article'
                       AND post_status = 'publish'",
                    ...$meta_ids
                )
            );
            foreach ( $valid as $id ) {
                $found[ $id ] = ( isset( $found[ $id ] ) ? $found[ $id ] : 0 ) + 8;
            }
        }

        // -- 3. Busqueda por nombre de categoria ------------------------------
        $tax_terms = get_terms( array(
            'taxonomy'   => 'help_category',
            'hide_empty' => false,
            'name__like' => $raw,
        ) );

        if ( ! empty( $tax_terms ) && ! is_wp_error( $tax_terms ) ) {
            $term_ids  = wp_list_pluck( $tax_terms, 'term_id' );
            $cat_posts = new WP_Query( array(
                'post_type'      => 'help_article',
                'post_status'    => 'publish',
                'posts_per_page' => $limit,
                'fields'         => 'ids',
                'tax_query'      => array( array(
                    'taxonomy' => 'help_category',
                    'field'    => 'term_id',
                    'terms'    => $term_ids,
                ) ),
            ) );
            foreach ( $cat_posts->posts as $id ) {
                $found[ $id ] = ( isset( $found[ $id ] ) ? $found[ $id ] : 0 ) + 5;
            }
        }

        // -- Ordenar por score y limitar --------------------------------------
        arsort( $found );
        $top_ids = array_slice( array_keys( $found ), 0, $limit );

        if ( empty( $top_ids ) ) return array();

        $output = array();
        foreach ( $top_ids as $post_id ) {
            $post   = get_post( $post_id );
            $output[] = array(
                'id'    => $post_id,
                'title' => $post->post_title,
                'url'   => get_permalink( $post_id ),
                'crumb' => self::get_post_crumb_string( $post_id ),
            );
        }

        return $output;
    }

    /**
     * Devuelve la ruta de categoria como string: "Envios / Creacion y cotizacion"
     */
    private static function get_post_crumb_string( $post_id ) {
        $terms = get_the_terms( $post_id, 'help_category' );
        if ( ! $terms || is_wp_error( $terms ) ) return '';

        $deepest = null;
        $max     = -1;
        foreach ( $terms as $t ) {
            $depth = count( get_ancestors( $t->term_id, 'help_category', 'taxonomy' ) );
            if ( $depth > $max ) { $max = $depth; $deepest = $t; }
        }

        $ids   = array_reverse( get_ancestors( $deepest->term_id, 'help_category', 'taxonomy' ) );
        $names = array();
        foreach ( $ids as $id ) {
            $t = get_term( $id, 'help_category' );
            if ( $t && ! is_wp_error( $t ) ) $names[] = $t->name;
        }
        $names[] = $deepest->name;

        return implode( ' / ', $names );
    }
}
