<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SXHC_Importer {

    const MARKDOWN_FILE = WP_CONTENT_DIR . '/imports/indice-categorias.md';

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_admin_page' ) );
        add_action( 'admin_post_sxhc_import_categories', array( __CLASS__, 'handle_import' ) );
    }

    public static function add_admin_page() {
        add_submenu_page(
            'edit.php?post_type=help_article',
            'Importar categorías',
            'Importar categorías',
            'manage_options',
            'sxhc-importer',
            array( __CLASS__, 'render_page' )
        );
    }

    public static function render_page() {
        $already_imported = get_option( 'sxhc_categories_imported', false );
        ?>
        <div class="wrap">
            <h1>Importar categorías desde Markdown</h1>

            <?php if ( isset( $_GET['imported'] ) ) : ?>
                <div class="notice notice-success"><p>
                    <?php echo esc_html( (int) $_GET['imported'] ); ?> categorías importadas correctamente.
                </p></div>
            <?php endif; ?>

            <?php if ( isset( $_GET['error'] ) ) : ?>
                <div class="notice notice-error"><p>
                    Error: <?php echo esc_html( urldecode( $_GET['error'] ) ); ?>
                </p></div>
            <?php endif; ?>

            <p>Este importador lee <strong>indice-categorias.md</strong> desde <code>wp-content/imports/</code>
            y crea todas las categorías con su jerarquía en <em>help_category</em>.</p>

            <?php if ( $already_imported ) : ?>
                <div class="notice notice-warning">
                    <p>Las categorías ya fueron importadas. Puedes volver a importar si actualizaste el archivo Markdown — los términos existentes se omitirán y solo se crearán los nuevos.</p>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'sxhc_import_categories', 'sxhc_nonce' ); ?>
                <input type="hidden" name="action" value="sxhc_import_categories">
                <?php submit_button( $already_imported ? 'Reimportar categorías' : 'Importar categorías', 'primary' ); ?>
            </form>

            <hr>
            <h2>Vista previa del archivo</h2>
            <?php self::render_preview(); ?>
        </div>
        <?php
    }

    public static function handle_import() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Sin permisos.' );
        }

        check_admin_referer( 'sxhc_import_categories', 'sxhc_nonce' );

        if ( ! file_exists( self::MARKDOWN_FILE ) ) {
            $redirect = add_query_arg(
                array( 'page' => 'sxhc-importer', 'error' => urlencode( 'No se encontró indice-categorias.md en wp-content/imports/.' ) ),
                admin_url( 'edit.php?post_type=help_article' )
            );
            wp_redirect( $redirect );
            exit;
        }

        $count = self::parse_and_import( self::MARKDOWN_FILE );
        update_option( 'sxhc_categories_imported', true );

        $redirect = add_query_arg(
            array( 'page' => 'sxhc-importer', 'imported' => $count ),
            admin_url( 'edit.php?post_type=help_article' )
        );
        wp_redirect( $redirect );
        exit;
    }

    /**
     * Parsea el Markdown y crea los términos en help_category.
     * Soporta profundidad infinita (# ## ### #### …).
     *
     * @return int Número de términos creados.
     */
    private static function parse_and_import( $file ) {
        $lines   = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
        $created = 0;

        // Stack de padres indexado por nivel: [ nivel => term_id ]
        $parent_stack = array();

        foreach ( $lines as $line ) {
            // Detectar cuántos # tiene la línea
            if ( ! preg_match( '/^(#+)\s+(.+)$/', $line, $matches ) ) {
                continue;
            }

            $level = strlen( $matches[1] ); // 1 = #, 2 = ##, 3 = ###, etc.
            $name  = trim( $matches[2] );

            // El padre es el término del nivel inmediato superior
            $parent_id = isset( $parent_stack[ $level - 1 ] ) ? $parent_stack[ $level - 1 ] : 0;

            // Insertar o recuperar el término
            $existing = get_term_by( 'name', $name, 'help_category' );

            if ( $existing ) {
                $term_id = $existing->term_id;
            } else {
                $result = wp_insert_term(
                    $name,
                    'help_category',
                    array( 'parent' => $parent_id )
                );

                if ( is_wp_error( $result ) ) {
                    continue;
                }

                $term_id = $result['term_id'];
                $created++;
            }

            // Guardar este nivel en el stack y limpiar niveles más profundos
            $parent_stack[ $level ] = $term_id;
            foreach ( array_keys( $parent_stack ) as $k ) {
                if ( $k > $level ) {
                    unset( $parent_stack[ $k ] );
                }
            }
        }

        return $created;
    }

    private static function render_preview() {
        if ( ! file_exists( self::MARKDOWN_FILE ) ) {
            echo '<p style="color:red;">⚠ No se encontró <code>indice-categorias.md</code> en <code>wp-content/imports/</code>.</p>';
            return;
        }

        $lines = file( self::MARKDOWN_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
        echo '<ul style="font-family:monospace; font-size:13px;">';
        foreach ( $lines as $line ) {
            if ( preg_match( '/^(#+)\s+(.+)$/', $line, $m ) ) {
                $depth   = strlen( $m[1] ) - 1;
                $padding = $depth * 20;
                $label   = esc_html( $m[2] );
                $hash    = esc_html( $m[1] );
                echo "<li style='padding-left:{$padding}px; margin:2px 0;'><span style='color:#999;'>{$hash}</span> {$label}</li>";
            }
        }
        echo '</ul>';
    }
}
