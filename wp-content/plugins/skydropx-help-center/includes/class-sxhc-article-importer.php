<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SXHC_Article_Importer {

    const CSV_FILE   = ABSPATH . 'CDA Skydropx Pro - Articulos-CDAs - 6717cf7217bfd6fb2b0dbec6.csv';
    const BATCH_SIZE = 50;
    const OPTION_KEY = 'sxhc_import_progress';

    /**
     * Mapeo explícito de slugs del CSV → nombre del término en WP.
     * Necesario cuando el slug del CSV no coincide con el nombre real.
     */
    const CATEGORY_MAP = array(
        // Categorías
        'conexiones'          => 'Conexiones avanzadas',
        'conexiones-avanzadas'=> 'Conexiones avanzadas',
        'configuraciones'     => 'Configuraciones',
        'envios'              => 'Envíos',
        'finanzas'            => 'Finanzas',
        'paqueterias'         => 'Transportadoras',
        'recolecciones'       => 'Recolecciones',
        'reportes'            => 'Reportes',
        'tiendas-en-linea'    => 'Tiendas en línea',
        // Subcategorías
        'api'                              => 'API',
        'automatizaciones'                 => 'Automatizaciones',
        'cancelaciones-e-indemnizaciones'  => 'Cancelaciones e indemnizaciones',
        'cargos-extra'                     => 'Cargos extra',
        'configuraciones-de-las-paqueterias' => 'Configuraciones de las transportadoras',
        'convenios'                        => 'Convenios',
        'cotizar-y-crear'                  => 'Creación y cotización',
        'crear-reporte'                    => 'Crear reporte',
        'creditos-y-movimientos'           => 'Créditos y movimientos',
        'cuenta'                           => 'Cuenta',
        'direcciones'                      => 'Direcciones',
        'envios-externos'                  => 'Envíos externos',
        'envios-internacionales'           => 'Envíos internacionales',
        'facturacion'                      => 'Facturación',
        'impresion'                        => 'Impresión',
        'lineamientos-y-restricciones'     => 'Lineamientos y restricciones',
        'mas-configuraciones'              => 'Más configuraciones',
        'mercado-libre'                    => 'Mercado Libre',
        'ordenes'                          => 'Órdenes',
        'plantillas-de-paquetes'           => 'Plantillas de paquetes',
        'prestashop'                       => 'PrestaShop',
        'productos'                        => 'Productos',
        'programar-recolecciones'          => 'Programar recolecciones',
        'seguimiento-de-envios'            => 'Seguimiento de envíos',
        'shopify'                          => 'Shopify',
        'tarifas-dinamicas'                => 'Tarifas dinámicas',
        'temu'                             => 'Temu',
        'tiendanube'                       => 'Tiendanube',
        'tiendas-en-linea'                 => 'Tiendas en línea',
        'walmart'                          => 'Walmart',
        'webhooks'                         => 'Webhooks',
        'wix'                              => 'Wix',
        'woocommerce'                      => 'WooCommerce',
        // Secciones (nivel 3 — hijos de SubCategoria)
        'crear-envio'                           => 'Crear envío',
        'embalaje'                              => 'Embalaje',
        'primeros-pasos'                        => 'Primeros pasos',
        'datos-para-cotizar-y-crear-envios'     => 'Datos para cotizar y crear envíos',
        'datos-para-envios-internacionales'     => 'Datos para envíos internacionales',
        'rastreo'                               => 'Rastreo',
        'reportes'                              => 'Reportes',
        'creditos'                              => 'Créditos',
        'productos-internacionales'             => 'Productos internacionales',
        'requisitos'                            => 'Requisitos',
    );

    public static function init() {
        add_action( 'admin_menu',     array( __CLASS__, 'add_admin_page' ) );
        add_action( 'wp_ajax_sxhc_import_batch', array( __CLASS__, 'handle_batch' ) );
        add_action( 'wp_ajax_sxhc_import_reset', array( __CLASS__, 'handle_reset' ) );
    }

    public static function add_admin_page() {
        add_submenu_page(
            'edit.php?post_type=help_article',
            'Importar artículos',
            'Importar artículos (CSV)',
            'manage_options',
            'sxhc-article-importer',
            array( __CLASS__, 'render_page' )
        );
    }

    public static function render_page() {
        $progress = get_option( self::OPTION_KEY, array() );
        $total    = isset( $progress['total'] )   ? (int) $progress['total']   : 0;
        $done     = isset( $progress['done'] )    ? (int) $progress['done']    : 0;
        $created  = isset( $progress['created'] ) ? (int) $progress['created'] : 0;
        $skipped  = isset( $progress['skipped'] ) ? (int) $progress['skipped'] : 0;
        $finished = isset( $progress['finished'] ) && $progress['finished'];

        if ( ! $total ) {
            // Contar filas del CSV para mostrar total antes de empezar
            if ( file_exists( self::CSV_FILE ) ) {
                $total = max( 0, self::count_csv_rows() );
            }
        }
        ?>
        <div class="wrap">
            <h1>Importar artículos desde CSV</h1>

            <?php if ( ! file_exists( self::CSV_FILE ) ) : ?>
                <div class="notice notice-error"><p>No se encontró el archivo CSV en la raíz de WordPress.</p></div>
                <?php return; ?>
            <?php endif; ?>

            <p>Archivo: <code><?php echo esc_html( basename( self::CSV_FILE ) ); ?></code>
               — <strong><?php echo number_format( $total ); ?> artículos</strong> en total.</p>

            <?php if ( $finished ) : ?>
                <div class="notice notice-success">
                    <p>✅ Importación completada: <strong><?php echo $created; ?> creados</strong>, <?php echo $skipped; ?> omitidos (ya existían).</p>
                </div>
                <button id="sxhc-reset" class="button button-secondary">Reiniciar para volver a importar</button>
            <?php else : ?>
                <div id="sxhc-progress-wrap" style="margin: 20px 0; <?php echo $done ? '' : 'display:none;'; ?>">
                    <div style="background:#e0e0e0; border-radius:4px; height:24px; overflow:hidden; max-width:600px;">
                        <div id="sxhc-progress-bar"
                             style="background:#2271b1; height:100%; width:<?php echo $total ? round($done/$total*100) : 0; ?>%; transition:width .3s;"></div>
                    </div>
                    <p id="sxhc-progress-text" style="margin-top:8px;">
                        <strong><?php echo $done; ?></strong> / <?php echo $total; ?> artículos procesados
                        — <strong><?php echo $created; ?> creados</strong>, <?php echo $skipped; ?> omitidos
                    </p>
                </div>

                <button id="sxhc-start" class="button button-primary button-large">
                    <?php echo $done ? 'Continuar importación' : 'Iniciar importación'; ?>
                </button>
                <?php if ( $done ) : ?>
                    <button id="sxhc-reset" class="button button-secondary" style="margin-left:10px;">Reiniciar desde cero</button>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <script>
        (function($){
            var total   = <?php echo (int) $total; ?>;
            var done    = <?php echo (int) $done; ?>;
            var created = <?php echo (int) $created; ?>;
            var skipped = <?php echo (int) $skipped; ?>;
            var running = false;

            function updateUI() {
                var pct = total ? Math.round(done / total * 100) : 0;
                $('#sxhc-progress-bar').css('width', pct + '%');
                $('#sxhc-progress-text').html(
                    '<strong>' + done + '</strong> / ' + total +
                    ' artículos procesados — <strong>' + created + ' creados</strong>, ' + skipped + ' omitidos'
                );
            }

            function runBatch() {
                if (!running) return;
                $.post(ajaxurl, {
                    action: 'sxhc_import_batch',
                    nonce:  '<?php echo wp_create_nonce("sxhc_import_batch"); ?>',
                    offset: done
                }, function(res) {
                    if (!res.success) {
                        alert('Error: ' + res.data);
                        running = false;
                        return;
                    }
                    done    += res.data.processed;
                    created += res.data.created;
                    skipped += res.data.skipped;
                    total    = res.data.total;

                    $('#sxhc-progress-wrap').show();
                    updateUI();

                    if (res.data.finished) {
                        running = false;
                        $('#sxhc-start').hide();
                        $('#sxhc-progress-text').prepend('✅ ');
                    } else {
                        runBatch();
                    }
                }).fail(function(){
                    alert('Error de red. Haz clic en Continuar para retomar.');
                    running = false;
                });
            }

            $('#sxhc-start').on('click', function(){
                running = true;
                $(this).prop('disabled', true).text('Importando…');
                runBatch();
            });

            $('#sxhc-reset').on('click', function(){
                if (!confirm('¿Seguro? Esto borrará el progreso guardado (no elimina artículos ya importados).')) return;
                $.post(ajaxurl, {
                    action: 'sxhc_import_reset',
                    nonce:  '<?php echo wp_create_nonce("sxhc_import_reset"); ?>'
                }, function(){ location.reload(); });
            });
        })(jQuery);
        </script>
        <?php
    }

    // ─── AJAX: procesar un lote ──────────────────────────────────────────────

    public static function handle_batch() {
        check_ajax_referer( 'sxhc_import_batch', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Sin permisos.' );
        }

        $offset = max( 0, (int) $_POST['offset'] );
        $result = self::process_batch( $offset, self::BATCH_SIZE );
        wp_send_json_success( $result );
    }

    public static function handle_reset() {
        check_ajax_referer( 'sxhc_import_reset', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Sin permisos.' );
        }
        delete_option( self::OPTION_KEY );
        wp_send_json_success();
    }

    // ─── Lógica de importación ───────────────────────────────────────────────

    private static function process_batch( $offset, $batch_size ) {
        $rows      = self::read_csv_rows( $offset, $batch_size );
        $total     = self::count_csv_rows();
        $created   = 0;
        $skipped   = 0;

        foreach ( $rows as $row ) {
            $slug = sanitize_title( $row['Slug'] );

            // Evitar duplicados por slug
            $existing = get_posts( array(
                'post_type'   => 'help_article',
                'name'        => $slug,
                'post_status' => 'any',
                'numberposts' => 1,
            ) );

            if ( $existing ) {
                $skipped++;
                continue;
            }

            $post_status = ( isset( $row['Draft'] ) && $row['Draft'] === 'true' ) ? 'draft' : 'publish';

            $post_id = wp_insert_post( array(
                'post_type'    => 'help_article',
                'post_title'   => sanitize_text_field( $row['Name'] ),
                'post_name'    => $slug,
                'post_content' => wp_kses_post( $row['Contenido'] ),
                'post_excerpt' => sanitize_text_field( $row['Description Metadata'] ),
                'post_status'  => $post_status,
            ) );

            if ( is_wp_error( $post_id ) ) {
                $skipped++;
                continue;
            }

            // Asignar categoría más específica disponible:
            // Categoria → SubCategoria → Secciones (cada nivel es hijo del anterior)
            $cat_id = null;
            $sub_id = null;
            $sec_id = null;

            if ( ! empty( $row['Categoria'] ) ) {
                $cat_id = self::resolve_term( $row['Categoria'], 0 );
            }

            if ( ! empty( $row['SubCategoria'] ) ) {
                $sub_id = self::resolve_term( $row['SubCategoria'], $cat_id );
            }

            if ( ! empty( $row['Secciones'] ) ) {
                $sec_id = self::resolve_term( $row['Secciones'], $sub_id );
            }

            // Asignar solo el término más específico (el artículo hereda los padres en la navegación)
            $assign = $sec_id ?: ( $sub_id ?: $cat_id );
            if ( $assign ) {
                wp_set_object_terms( $post_id, array( $assign ), 'help_category' );
            }

            // Guardar meta extra
            if ( ! empty( $row['Title Metadata'] ) ) {
                update_post_meta( $post_id, '_sxhc_seo_title', sanitize_text_field( $row['Title Metadata'] ) );
            }
            if ( ! empty( $row['KEYWORDS SEARCH'] ) ) {
                update_post_meta( $post_id, '_sxhc_keywords', sanitize_textarea_field( $row['KEYWORDS SEARCH'] ) );
            }
            if ( ! empty( $row['Tags'] ) ) {
                update_post_meta( $post_id, '_sxhc_tags', sanitize_text_field( $row['Tags'] ) );
            }

            // Título normalizado para búsqueda sin acentos/espacios
            update_post_meta( $post_id, '_sxhc_title_normalized', SXHC_Search::normalize( $row['Name'] ) );

            $created++;
        }

        $processed = $offset + count( $rows );
        $finished  = $processed >= $total;

        // Guardar progreso
        $prev = get_option( self::OPTION_KEY, array() );
        update_option( self::OPTION_KEY, array(
            'total'    => $total,
            'done'     => $processed,
            'created'  => ( isset( $prev['created'] ) ? $prev['created'] : 0 ) + $created,
            'skipped'  => ( isset( $prev['skipped'] ) ? $prev['skipped'] : 0 ) + $skipped,
            'finished' => $finished,
        ) );

        return array(
            'total'     => $total,
            'processed' => count( $rows ),
            'created'   => $created,
            'skipped'   => $skipped,
            'finished'  => $finished,
        );
    }

    /**
     * Resuelve un slug del CSV al term_id en help_category.
     * Respeta la jerarquía: busca primero como hijo del $parent_id dado.
     *
     * @param string   $csv_slug  Slug tal como viene en el CSV.
     * @param int|null $parent_id term_id del padre (0 = raíz).
     * @return int|null
     */
    private static function resolve_term( $csv_slug, $parent_id = 0 ) {
        $csv_slug  = trim( $csv_slug );
        if ( ! $csv_slug ) return null;
        $parent_id = (int) $parent_id;

        // Obtener el nombre canónico del mapa, o usar el slug como nombre
        $name = isset( self::CATEGORY_MAP[ $csv_slug ] )
            ? self::CATEGORY_MAP[ $csv_slug ]
            : $csv_slug;

        // 1. Buscar por nombre dentro de los hijos del padre dado
        if ( $parent_id ) {
            $children = get_terms( array(
                'taxonomy'   => 'help_category',
                'parent'     => $parent_id,
                'hide_empty' => false,
                'fields'     => 'all',
            ) );
            foreach ( $children as $child ) {
                if ( strtolower( $child->name ) === strtolower( $name ) ) {
                    return $child->term_id;
                }
            }
        }

        // 2. Buscar globalmente por nombre (por si ya existe en cualquier nivel)
        $term = get_term_by( 'name', $name, 'help_category' );
        if ( $term ) return $term->term_id;

        // 3. Buscar por slug directo
        $term = get_term_by( 'slug', $csv_slug, 'help_category' );
        if ( $term ) return $term->term_id;

        // 4. Crear el término como hijo del padre indicado
        $args   = $parent_id ? array( 'parent' => $parent_id ) : array();
        $result = wp_insert_term( $name, 'help_category', $args );

        if ( ! is_wp_error( $result ) ) return $result['term_id'];

        return null;
    }

    // ─── Helpers CSV ────────────────────────────────────────────────────────

    private static function count_csv_rows() {
        if ( ! file_exists( self::CSV_FILE ) ) return 0;
        $count = 0;
        $fh = fopen( self::CSV_FILE, 'r' );
        fgetcsv( $fh ); // saltar cabecera
        while ( fgetcsv( $fh ) !== false ) $count++;
        fclose( $fh );
        return $count;
    }

    private static function read_csv_rows( $offset, $limit ) {
        $rows = array();
        if ( ! file_exists( self::CSV_FILE ) ) return $rows;

        $fh      = fopen( self::CSV_FILE, 'r' );
        $headers = fgetcsv( $fh );
        $current = 0;

        while ( ( $line = fgetcsv( $fh ) ) !== false ) {
            if ( $current < $offset ) { $current++; continue; }
            if ( count( $rows ) >= $limit ) break;

            $rows[] = array_combine( $headers, array_pad( $line, count( $headers ), '' ) );
            $current++;
        }

        fclose( $fh );
        return $rows;
    }
}
