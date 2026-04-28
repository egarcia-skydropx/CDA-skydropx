<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SXHC_Admin_Columns {

    public static function init() {
        add_filter( 'manage_help_article_posts_columns',          array( __CLASS__, 'set_columns' ) );
        add_action( 'manage_help_article_posts_custom_column',    array( __CLASS__, 'render_column' ), 10, 2 );
        add_filter( 'manage_edit-help_article_sortable_columns',  array( __CLASS__, 'sortable_columns' ) );
        add_action( 'pre_get_posts',                              array( __CLASS__, 'handle_sort' ) );
        add_action( 'admin_head',                                 array( __CLASS__, 'column_styles' ) );
    }

    // ─── Definir columnas ────────────────────────────────────────────────────

    public static function set_columns( $columns ) {
        unset( $columns['taxonomy-help_category'] );

        // Orden deseado: cb, title, sxhc_category, sxhc_category_path, author, date
        $new = array();

        if ( isset( $columns['cb'] ) )     $new['cb']    = $columns['cb'];
        if ( isset( $columns['title'] ) )  $new['title'] = $columns['title'];

        $new['sxhc_category']      = 'Categoría';
        $new['sxhc_category_path'] = 'Ruta';

        if ( isset( $columns['author'] ) ) $new['author'] = $columns['author'];
        if ( isset( $columns['date'] ) )   $new['date']   = $columns['date'];

        return $new;
    }

    // ─── Renderizar columnas ─────────────────────────────────────────────────

    public static function render_column( $column, $post_id ) {
        $terms = get_the_terms( $post_id, 'help_category' );

        if ( ! $terms || is_wp_error( $terms ) ) {
            echo '<span style="color:#ccc;">—</span>';
            return;
        }

        $deepest = self::get_deepest_term( $terms );

        if ( $column === 'sxhc_category' ) {
            self::render_category_chip( $deepest );
        }

        if ( $column === 'sxhc_category_path' ) {
            echo wp_kses_post( self::get_term_breadcrumb( $deepest ) );
        }
    }

    /**
     * Muestra el nombre del término más específico como un enlace
     * que filtra la lista por esa categoría.
     */
    private static function render_category_chip( $term ) {
        $filter_url = add_query_arg( array(
            'post_type'     => 'help_article',
            'help_category' => $term->slug,
        ), admin_url( 'edit.php' ) );

        printf(
            '<a href="%s" class="sxhc-chip">%s</a>',
            esc_url( $filter_url ),
            esc_html( $term->name )
        );
    }

    // ─── Ordenación por categoría ────────────────────────────────────────────

    public static function sortable_columns( $columns ) {
        $columns['sxhc_category'] = 'sxhc_category';
        return $columns;
    }

    public static function handle_sort( $query ) {
        if ( ! is_admin() || ! $query->is_main_query() ) return;
        if ( $query->get( 'orderby' ) !== 'sxhc_category' ) return;

        $query->set( 'orderby',  'taxonomy' );
        $query->set( 'tax_query', array(
            array(
                'taxonomy' => 'help_category',
                'operator' => 'EXISTS',
            ),
        ) );
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private static function get_deepest_term( $terms ) {
        $deepest = null;
        $max     = -1;
        foreach ( $terms as $term ) {
            $depth = count( get_ancestors( $term->term_id, 'help_category', 'taxonomy' ) );
            if ( $depth > $max ) {
                $max     = $depth;
                $deepest = $term;
            }
        }
        return $deepest;
    }

    private static function get_term_breadcrumb( $term ) {
        $ancestor_ids = array_reverse( get_ancestors( $term->term_id, 'help_category', 'taxonomy' ) );
        $parts        = array();

        foreach ( $ancestor_ids as $id ) {
            $ancestor = get_term( $id, 'help_category' );
            if ( $ancestor && ! is_wp_error( $ancestor ) ) {
                $parts[] = '<span class="sxhc-crumb">' . esc_html( $ancestor->name ) . '</span>';
            }
        }

        $parts[] = '<span class="sxhc-crumb">' . esc_html( $term->name ) . '</span>';

        return '<span class="sxhc-breadcrumb">' . implode( '', $parts ) . '</span>';
    }

    // ─── Estilos ─────────────────────────────────────────────────────────────

    public static function column_styles() {
        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== 'help_article' ) return;
        ?>
        <style>
            /* Anchos */
            .column-sxhc_category      { width: 200px; }
            .column-sxhc_category_path { width: 180px; text-align: right !important; }

            /* Chip — columna Categoría */
            .sxhc-chip {
                display: inline-block;
                padding: 2px 10px;
                border-radius: 20px;
                background: #f0f4ff;
                color: #2271b1;
                font-size: 11px;
                font-weight: 600;
                text-decoration: none;
                white-space: nowrap;
                transition: background .15s;
            }
            .sxhc-chip:hover {
                background: #2271b1;
                color: #fff;
            }

            /* Breadcrumb — columna Ruta */
            .sxhc-breadcrumb {
                display: flex;
                flex-direction: column;
                align-items: flex-end;
            }

            .sxhc-crumb {
                font-size: 12px;
                color: #2271b1;
                line-height: 1.7;
            }
        </style>
        <?php
    }
}
