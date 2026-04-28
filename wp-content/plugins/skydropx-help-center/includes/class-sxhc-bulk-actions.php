<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SXHC_Bulk_Actions {

    public static function init() {

        // ── Pantalla: listado de artículos ────────────────────────────────
        add_filter( 'bulk_actions-edit-help_article',        array( __CLASS__, 'register_post_actions' ) );
        add_filter( 'handle_bulk_actions-edit-help_article', array( __CLASS__, 'handle_post_actions' ), 10, 3 );

        // ── Pantalla: listado de categorías ───────────────────────────────
        add_filter( 'bulk_actions-edit-help_category',        array( __CLASS__, 'register_term_actions' ) );
        add_filter( 'handle_bulk_actions-edit-help_category', array( __CLASS__, 'handle_term_actions' ), 10, 3 );

        // ── Compartidos ───────────────────────────────────────────────────
        add_action( 'admin_notices', array( __CLASS__, 'show_notice' ) );
        add_action( 'admin_footer',  array( __CLASS__, 'inject_ui' ) );
    }

    // ════════════════════════════════════════════════════════════════════════
    //  ARTÍCULOS — cambiar categoría asignada
    // ════════════════════════════════════════════════════════════════════════

    public static function register_post_actions( $actions ) {
        $actions['sxhc_change_category'] = 'Cambiar categoría';
        return $actions;
    }

    public static function handle_post_actions( $redirect_url, $action, $post_ids ) {
        if ( $action !== 'sxhc_change_category' ) return $redirect_url;
        if ( ! current_user_can( 'edit_posts' ) )  return $redirect_url;

        check_admin_referer( 'bulk-posts' );

        $term_id = absint( isset( $_REQUEST['sxhc_target_category'] ) ? $_REQUEST['sxhc_target_category'] : 0 );
        if ( ! $term_id ) return add_query_arg( 'sxhc_bulk_error', 'no_category', $redirect_url );

        $term = get_term( $term_id, 'help_category' );
        if ( ! $term || is_wp_error( $term ) ) return add_query_arg( 'sxhc_bulk_error', 'invalid_category', $redirect_url );

        $updated = 0;
        foreach ( $post_ids as $post_id ) {
            wp_set_object_terms( absint( $post_id ), array( $term_id ), 'help_category' );
            $updated++;
        }

        return add_query_arg( array(
            'sxhc_bulk_updated'  => $updated,
            'sxhc_bulk_category' => $term_id,
            'sxhc_bulk_type'     => 'post',
        ), $redirect_url );
    }

    // ════════════════════════════════════════════════════════════════════════
    //  CATEGORÍAS — cambiar categoría padre
    // ════════════════════════════════════════════════════════════════════════

    public static function register_term_actions( $actions ) {
        $actions['sxhc_change_parent'] = 'Cambiar categoría padre';
        return $actions;
    }

    public static function handle_term_actions( $redirect_url, $action, $term_ids ) {
        if ( $action !== 'sxhc_change_parent' ) return $redirect_url;
        if ( ! current_user_can( 'manage_categories' ) ) return $redirect_url;

        check_admin_referer( 'bulk-tags' );

        // 0 = mover a raíz (sin padre)
        $new_parent = absint( isset( $_REQUEST['sxhc_target_parent'] ) ? $_REQUEST['sxhc_target_parent'] : 0 );

        // Si se eligió un padre concreto, verificar que existe
        if ( $new_parent ) {
            $parent_term = get_term( $new_parent, 'help_category' );
            if ( ! $parent_term || is_wp_error( $parent_term ) ) {
                return add_query_arg( 'sxhc_bulk_error', 'invalid_category', $redirect_url );
            }
        }

        $updated = 0;
        foreach ( $term_ids as $tid ) {
            $tid = absint( $tid );

            // Evitar que un término se convierta en hijo de sí mismo o de sus propios descendientes
            $descendants = get_term_children( $tid, 'help_category' );
            if ( $new_parent && ( $new_parent === $tid || in_array( $new_parent, (array) $descendants ) ) ) {
                continue;
            }

            wp_update_term( $tid, 'help_category', array( 'parent' => $new_parent ) );
            $updated++;
        }

        return add_query_arg( array(
            'sxhc_bulk_updated'  => $updated,
            'sxhc_bulk_category' => $new_parent,
            'sxhc_bulk_type'     => 'term',
        ), $redirect_url );
    }

    // ════════════════════════════════════════════════════════════════════════
    //  NOTIFICACIONES
    // ════════════════════════════════════════════════════════════════════════

    public static function show_notice() {
        $screen = get_current_screen();
        if ( ! $screen ) return;
        if ( ! in_array( $screen->id, array( 'edit-help_article', 'edit-help_category' ) ) ) return;

        if ( isset( $_GET['sxhc_bulk_updated'] ) ) {
            $count    = (int) $_GET['sxhc_bulk_updated'];
            $type     = isset( $_GET['sxhc_bulk_type'] ) ? $_GET['sxhc_bulk_type'] : 'post';
            $cat_id   = (int) ( isset( $_GET['sxhc_bulk_category'] ) ? $_GET['sxhc_bulk_category'] : 0 );
            $cat_term = $cat_id ? get_term( $cat_id, 'help_category' ) : null;
            $cat_name = ( $cat_term && ! is_wp_error( $cat_term ) ) ? $cat_term->name : 'Raíz (sin padre)';

            if ( $type === 'post' ) {
                $msg = sprintf(
                    '<strong>%d artículo%s</strong> movido%s a la categoría <strong>%s</strong>.',
                    $count, $count !== 1 ? 's' : '', $count !== 1 ? 's' : '', esc_html( $cat_name )
                );
            } else {
                $msg = sprintf(
                    '<strong>%d categoría%s</strong> movida%s bajo <strong>%s</strong>.',
                    $count, $count !== 1 ? 's' : '', $count !== 1 ? 's' : '', esc_html( $cat_name )
                );
            }

            echo '<div class="notice notice-success is-dismissible"><p>' . $msg . '</p></div>';
        }

        if ( isset( $_GET['sxhc_bulk_error'] ) ) {
            $err = $_GET['sxhc_bulk_error'];
            if ( $err === 'no_category' ) {
                $msg = 'Debes seleccionar una categoría de destino.';
            } elseif ( $err === 'invalid_category' ) {
                $msg = 'La categoría seleccionada no es válida.';
            } else {
                $msg = 'Ocurrió un error al procesar la acción.';
            }
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    //  UI: panel + selector + JS  (se adapta según la pantalla)
    // ════════════════════════════════════════════════════════════════════════

    public static function inject_ui() {
        $screen = get_current_screen();
        if ( ! $screen ) return;

        if ( $screen->id === 'edit-help_article' ) {
            self::inject_post_ui();
        } elseif ( $screen->id === 'edit-help_category' ) {
            self::inject_term_ui();
        }
    }

    // ── Panel para artículos ──────────────────────────────────────────────
    private static function inject_post_ui() {
        ?>
        <div id="sxhc-bulk-panel" style="display:none; align-items:center; gap:10px; margin:6px 0;
             padding:12px 16px; background:#f9f9f9; border:1px solid #dcdcde; border-radius:4px; flex-wrap:wrap;">
            <label for="sxhc_target_category" style="font-size:13px; font-weight:600; white-space:nowrap;">
                Mover artículos a:
            </label>
            <?php echo self::build_select( 'sxhc_target_category', '— Selecciona categoría destino —' ); ?>
            <span style="font-size:11px; color:#888;">La categoría anterior será reemplazada.</span>
        </div>
        <?php self::inject_js( '#posts-filter', 'sxhc_change_category', 'sxhc_bulk-panel', 'bulk-posts' ); ?>
        <?php
    }

    // ── Panel para categorías ─────────────────────────────────────────────
    private static function inject_term_ui() {
        ?>
        <div id="sxhc-bulk-panel" style="display:none; align-items:center; gap:10px; margin:6px 0;
             padding:12px 16px; background:#f9f9f9; border:1px solid #dcdcde; border-radius:4px; flex-wrap:wrap;">
            <label for="sxhc_target_parent" style="font-size:13px; font-weight:600; white-space:nowrap;">
                Mover categorías bajo:
            </label>
            <?php echo self::build_select( 'sxhc_target_parent', '— Sin padre (nivel raíz) —', true ); ?>
            <span style="font-size:11px; color:#888;">
                Las categorías seleccionadas pasarán a ser hijas de la categoría elegida.
                Elige "Sin padre" para moverlas al nivel raíz.
            </span>
        </div>
        <?php self::inject_js( '#posts-filter', 'sxhc_change_parent', 'sxhc-bulk-panel', 'bulk-tags' ); ?>
        <?php
    }

    // ── JS compartido ─────────────────────────────────────────────────────
    private static function inject_js( $form_selector, $action_value, $panel_id, $nonce_action ) {
        ?>
        <script>
        (function () {
            var panel = document.getElementById('<?php echo esc_js( $panel_id ); ?>');

            document.addEventListener('DOMContentLoaded', function () {
                var tablenav = document.querySelector('.tablenav.top');
                if (panel && tablenav) tablenav.insertAdjacentElement('afterend', panel);
            });

            function isActionSelected() {
                return Array.from(
                    document.querySelectorAll('select[name="action"], select[name="action2"]')
                ).some(function (s) { return s.value === '<?php echo esc_js( $action_value ); ?>'; });
            }

            document.addEventListener('change', function (e) {
                if (!e.target.matches('select[name="action"], select[name="action2"]')) return;
                if (isActionSelected()) {
                    panel.style.display = 'flex';
                    document.querySelectorAll('select[name="action"], select[name="action2"]')
                        .forEach(function (s) {
                            if (s !== e.target) s.value = '<?php echo esc_js( $action_value ); ?>';
                        });
                } else {
                    panel.style.display = 'none';
                }
            });

            document.addEventListener('submit', function (e) {
                if (!e.target.matches('<?php echo esc_js( $form_selector ); ?>')) return;
                if (!isActionSelected()) return;

                // Para términos, "sin padre" (value="") es válido — no validar
                <?php if ( $action_value === 'sxhc_change_category' ) : ?>
                var sel = panel.querySelector('select');
                if (sel && !sel.value) {
                    e.preventDefault();
                    sel.style.outline = '2px solid #d63638';
                    sel.focus();
                    sel.addEventListener('change', function () { sel.style.outline = ''; }, { once: true });
                }
                <?php endif; ?>
            });
        })();
        </script>
        <?php
    }

    // ════════════════════════════════════════════════════════════════════════
    //  HELPERS
    // ════════════════════════════════════════════════════════════════════════

    private static function build_select( $name, $placeholder, $allow_empty = false ) {
        $terms = get_terms( array(
            'taxonomy'   => 'help_category',
            'hide_empty' => false,
            'number'     => 0,
        ) );

        if ( empty( $terms ) || is_wp_error( $terms ) ) return '<em>No hay categorías.</em>';

        $tree    = self::build_tree( $terms );
        $options = '<option value="">' . esc_html( $placeholder ) . '</option>';
        $options .= self::render_options( $tree, 0 );

        return sprintf(
            '<select id="%1$s" name="%1$s" style="min-width:280px; font-size:13px;">%2$s</select>',
            esc_attr( $name ),
            $options
        );
    }

    private static function build_tree( $terms, $parent = 0 ) {
        $branch = array();
        foreach ( $terms as $term ) {
            if ( (int) $term->parent === $parent ) {
                $term->children = self::build_tree( $terms, $term->term_id );
                $branch[]       = $term;
            }
        }
        usort( $branch, fn( $a, $b ) => strcmp( $a->name, $b->name ) );
        return $branch;
    }

    private static function render_options( $tree, $depth ) {
        $html = '';
        foreach ( $tree as $term ) {
            $prefix = str_repeat( '&nbsp;&nbsp;&nbsp;', $depth ) . ( $depth > 0 ? '↳ ' : '' );
            $html  .= sprintf(
                '<option value="%d">%s%s</option>',
                $term->term_id,
                $prefix,
                esc_html( $term->name )
            );
            if ( ! empty( $term->children ) ) {
                $html .= self::render_options( $term->children, $depth + 1 );
            }
        }
        return $html;
    }
}
