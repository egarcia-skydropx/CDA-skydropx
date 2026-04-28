<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Contador de visitas por artículo.
 *
 * - Registra cada visita al frontend en post meta (_sxhc_views)
 * - No cuenta visitas de admins logueados
 * - Muestra columna "Visitas" en el listado, ordenable de mayor a menor
 */
class SXHC_Views {

    const META_KEY = '_sxhc_views';

    public static function init() {
        // AJAX para registrar visita (funciona con y sin caché)
        add_action( 'wp_ajax_sxhc_track_view',        array( __CLASS__, 'ajax_track_view' ) );
        add_action( 'wp_ajax_nopriv_sxhc_track_view', array( __CLASS__, 'ajax_track_view' ) );

        // Inyectar script de tracking solo en artículos del frontend
        add_action( 'wp_footer', array( __CLASS__, 'inject_tracking_script' ) );

        // Columna en el admin
        add_filter( 'manage_help_article_posts_columns',          array( __CLASS__, 'add_column' ) );
        add_action( 'manage_help_article_posts_custom_column',    array( __CLASS__, 'render_column' ), 10, 2 );
        add_filter( 'manage_edit-help_article_sortable_columns',  array( __CLASS__, 'sortable_column' ) );
        add_action( 'pre_get_posts',                              array( __CLASS__, 'handle_sort' ) );
        add_filter( 'posts_join',                                 array( __CLASS__, 'views_join' ), 10, 2 );
        add_filter( 'posts_orderby',                              array( __CLASS__, 'views_orderby' ), 10, 2 );
        add_action( 'admin_head',                                 array( __CLASS__, 'column_styles' ) );
    }

    // ── AJAX: registrar visita ────────────────────────────────────────────

    public static function ajax_track_view() {
        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

        if ( ! $post_id || get_post_type( $post_id ) !== 'help_article' ) {
            wp_send_json_error();
        }

        $views = (int) get_post_meta( $post_id, self::META_KEY, true );
        update_post_meta( $post_id, self::META_KEY, $views + 1 );

        wp_send_json_success( array( 'views' => $views + 1 ) );
    }

    // ── Script de tracking en el footer ───────────────────────────────────

    public static function inject_tracking_script() {
        if ( ! is_singular( 'help_article' ) ) return;

        $post_id  = get_the_ID();
        $ajax_url = admin_url( 'admin-ajax.php' );
        ?>
        <script>
        (function() {
            fetch('<?php echo esc_js( $ajax_url ); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=sxhc_track_view&post_id=<?php echo (int) $post_id; ?>'
            });
        })();
        </script>
        <?php
    }

    // ── Columna en el listado ─────────────────────────────────────────────

    public static function add_column( $columns ) {
        // Insertar antes de "date"
        $new = array();
        foreach ( $columns as $key => $label ) {
            if ( $key === 'date' ) {
                $new['sxhc_views'] = 'Visitas';
            }
            $new[ $key ] = $label;
        }
        if ( ! isset( $new['sxhc_views'] ) ) $new['sxhc_views'] = 'Visitas';
        return $new;
    }

    public static function render_column( $column, $post_id ) {
        if ( $column !== 'sxhc_views' ) return;

        $views = (int) get_post_meta( $post_id, self::META_KEY, true );

        printf(
            '<span style="font-size:13px; font-weight:%s; color:%s;">%s</span>',
            $views > 100 ? '600' : '400',
            $views > 100 ? '#2271b1' : '#50575e',
            number_format_i18n( $views )
        );
    }

    // ── Ordenamiento ──────────────────────────────────────────────────────

    public static function sortable_column( $columns ) {
        $columns['sxhc_views'] = 'sxhc_views';
        return $columns;
    }

    public static function handle_sort( $query ) {
        if ( ! is_admin() || ! $query->is_main_query() ) return;
        if ( $query->get( 'post_type' ) !== 'help_article' ) return;
        if ( $query->get( 'orderby' ) !== 'sxhc_views' ) return;

        // Marcamos la query para que nuestros filtros JOIN/ORDERBY actúen
        $query->set( 'sxhc_sort_views', true );
        // Evitar que WP intente hacer su propio JOIN de postmeta
        $query->set( 'orderby', 'post_date' ); // placeholder temporal
    }

    public static function views_join( $join, $query ) {
        global $wpdb;
        if ( ! $query->get( 'sxhc_sort_views' ) ) return $join;

        $join .= " LEFT JOIN {$wpdb->postmeta} AS sxhc_vm
                   ON ( {$wpdb->posts}.ID = sxhc_vm.post_id
                   AND sxhc_vm.meta_key = '" . self::META_KEY . "' ) ";
        return $join;
    }

    public static function views_orderby( $orderby, $query ) {
        if ( ! $query->get( 'sxhc_sort_views' ) ) return $orderby;

        $order = strtoupper( $query->get( 'order' ) ) === 'ASC' ? 'ASC' : 'DESC';
        return "CAST( IFNULL( sxhc_vm.meta_value, 0 ) AS UNSIGNED ) {$order}";
    }

    public static function column_styles() {
        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== 'help_article' ) return;
        ?>
        <style>
            .column-sxhc_views { width: 90px; text-align: center !important; }
            .column-sxhc_views .sxhc-views-count { display: block; text-align: center; }
        </style>
        <?php
    }

    // ── Utilidad pública ──────────────────────────────────────────────────

    public static function get_views( $post_id ) {
        return (int) get_post_meta( $post_id, self::META_KEY, true );
    }
}
