<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SXHC_Category_Order {

    const META_KEY  = 'sxhc_term_order';
    const AJAX_SAVE = 'sxhc_save_category_order';

    public static function init() {
        add_action( 'admin_menu',                           array( __CLASS__, 'add_page' ) );
        add_action( 'admin_enqueue_scripts',                array( __CLASS__, 'enqueue' ) );
        add_action( 'wp_ajax_' . self::AJAX_SAVE,          array( __CLASS__, 'handle_save' ) );
    }

    // ── Página admin ──────────────────────────────────────────────────────

    public static function add_page() {
        add_submenu_page(
            'edit.php?post_type=help_article',
            'Ordenar categorías',
            'Ordenar categorías',
            'manage_options',
            'sxhc-category-order',
            array( __CLASS__, 'render_page' )
        );
    }

    public static function enqueue( $hook ) {
        if ( strpos( $hook, 'sxhc-category-order' ) === false ) return;
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_script( 'jquery-ui-accordion' );
    }

    // ── Guardar orden vía AJAX ────────────────────────────────────────────

    public static function handle_save() {
        check_ajax_referer( self::AJAX_SAVE, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Sin permisos.' );

        $items = isset( $_POST['order'] ) ? (array) $_POST['order'] : array();

        foreach ( $items as $position => $term_id ) {
            update_term_meta( absint( $term_id ), self::META_KEY, (int) $position );
        }

        wp_send_json_success( array( 'saved' => count( $items ) ) );
    }

    // ── Obtener términos ordenados ────────────────────────────────────────

    /**
     * Devuelve los hijos directos de $parent_id ordenados por sxhc_term_order.
     * Si no tienen orden guardado, usa orden alfabético.
     */
    public static function get_ordered_terms( $parent_id = 0, $extra_args = array() ) {
        $args = array_merge( array(
            'taxonomy'   => 'help_category',
            'parent'     => $parent_id,
            'hide_empty' => false,
            'orderby'    => 'meta_value_num',
            'order'      => 'ASC',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key'     => self::META_KEY,
                    'compare' => 'EXISTS',
                ),
                array(
                    'key'     => self::META_KEY,
                    'compare' => 'NOT EXISTS',
                ),
            ),
        ), $extra_args );

        $terms = get_terms( $args );

        if ( is_wp_error( $terms ) || empty( $terms ) ) return array();

        // Separar los que tienen orden y los que no
        $with_order    = array();
        $without_order = array();
        foreach ( $terms as $t ) {
            $order = get_term_meta( $t->term_id, self::META_KEY, true );
            if ( $order !== '' ) {
                $t->sxhc_order  = (int) $order;
                $with_order[]   = $t;
            } else {
                $without_order[] = $t;
            }
        }

        // Ordenar el grupo con meta
        usort( $with_order, function( $a, $b ) { return $a->sxhc_order - $b->sxhc_order; } );

        // Alfabético para los sin orden
        usort( $without_order, function( $a, $b ) { return strcmp( $a->name, $b->name ); } );

        return array_merge( $with_order, $without_order );
    }

    // ── Render de la página ───────────────────────────────────────────────

    public static function render_page() {
        $root_terms = self::get_ordered_terms( 0 );
        ?>
        <div class="wrap">
            <h1>Ordenar categorías</h1>
            <p style="color:#666; max-width:600px;">
                Arrastra las categorías para definir el orden en que aparecen en la página principal
                y en el sidebar. El orden se guarda automáticamente al soltar.
            </p>

            <div id="sxhc-order-status" style="display:none; margin:8px 0; padding:8px 14px;
                 background:#f0f9e8; border:1px solid #7ad03a; border-radius:4px; font-size:13px;">
                ✅ Orden guardado
            </div>

            <div id="sxhc-order-root" style="max-width:680px; margin-top:16px;">
                <?php self::render_level( $root_terms, 0 ); ?>
            </div>
        </div>

        <style>
            .sxhc-cat-item {
                background: #fff;
                border: 1px solid #dcdcde;
                border-radius: 4px;
                margin-bottom: 4px;
            }
            .sxhc-cat-header {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 10px 14px;
                cursor: default;
            }
            .sxhc-drag-handle {
                cursor: grab;
                color: #aaa;
                font-size: 18px;
                line-height: 1;
                user-select: none;
                flex-shrink: 0;
            }
            .sxhc-drag-handle:active { cursor: grabbing; }
            .sxhc-cat-name {
                font-size: 13px;
                font-weight: 600;
                flex: 1;
            }
            .sxhc-cat-meta {
                font-size: 11px;
                color: #888;
            }
            .sxhc-cat-children {
                border-top: 1px solid #f0f0f0;
                padding: 8px 8px 8px 36px;
                background: #fafafa;
            }
            .sxhc-cat-item.ui-sortable-helper {
                box-shadow: 0 4px 16px rgba(0,0,0,.12);
                border-color: #2271b1;
            }
            .sxhc-sortable-placeholder {
                border: 2px dashed #2271b1;
                background: #f0f4ff;
                border-radius: 4px;
                margin-bottom: 4px;
                visibility: visible !important;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            var nonce   = '<?php echo esc_js( wp_create_nonce( self::AJAX_SAVE ) ); ?>';
            var ajaxUrl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
            var saveTimer;

            function saveOrder( $list ) {
                var ids = [];
                $list.children('.sxhc-cat-item').each(function() {
                    ids.push( $(this).data('term-id') );
                });

                $.post(ajaxUrl, {
                    action: '<?php echo esc_js( self::AJAX_SAVE ); ?>',
                    nonce:  nonce,
                    order:  ids
                }, function(res) {
                    if (res.success) {
                        var $s = $('#sxhc-order-status');
                        $s.stop(true, true).show().delay(2200).fadeOut(400);
                    }
                });
            }

            function initSortable( $list ) {
                $list.sortable({
                    handle:           '.sxhc-drag-handle',
                    placeholder:      'sxhc-sortable-placeholder',
                    forcePlaceholderSize: true,
                    axis:             'y',
                    tolerance:        'pointer',
                    cursor:           'grabbing',
                    stop: function() {
                        clearTimeout(saveTimer);
                        saveTimer = setTimeout(function() { saveOrder($list); }, 300);
                    }
                });
            }

            // Inicializar cada nivel del árbol
            $('.sxhc-sortable-list').each(function() {
                initSortable($(this));
            });
        });
        </script>
        <?php
    }

    private static function render_level( $terms, $depth ) {
        if ( empty( $terms ) ) return;

        $style = $depth === 0
            ? ''
            : 'margin:0;';
        ?>
        <ul class="sxhc-sortable-list" style="list-style:none; padding:0; margin:0; <?php echo $style; ?>">
            <?php foreach ( $terms as $term ) :
                $children = self::get_ordered_terms( $term->term_id );
                $count    = $term->count;
                ?>
                <li class="sxhc-cat-item" data-term-id="<?php echo esc_attr( $term->term_id ); ?>">
                    <div class="sxhc-cat-header">
                        <span class="sxhc-drag-handle" title="Arrastrar para reordenar">⠿</span>
                        <span class="sxhc-cat-name">
                            <?php echo str_repeat( '&nbsp;', $depth * 2 ); ?>
                            <?php echo esc_html( $term->name ); ?>
                        </span>
                        <span class="sxhc-cat-meta">
                            <?php echo count( $children ); ?> subcategorías · <?php echo $count; ?> artículos directos
                        </span>
                        <a href="<?php echo esc_url( get_edit_term_link( $term->term_id, 'help_category' ) ); ?>"
                           style="font-size:11px; color:#2271b1;">Editar</a>
                    </div>

                    <?php if ( ! empty( $children ) ) : ?>
                        <div class="sxhc-cat-children">
                            <?php self::render_level( $children, $depth + 1 ); ?>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php
    }
}
