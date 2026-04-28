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

        // ── Mejorar pantalla de categorías ────────────────────────────────
        add_action( 'admin_head',    array( __CLASS__, 'category_screen_styles' ) );
        add_action( 'admin_footer',  array( __CLASS__, 'inject_category_modal' ) );
        add_action( 'wp_ajax_sxhc_create_category', array( __CLASS__, 'handle_create_category' ) );
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

    // ════════════════════════════════════════════════════════════════════════
    //  PANTALLA DE CATEGORÍAS — tabla full width + modal agregar
    // ════════════════════════════════════════════════════════════════════════

    public static function category_screen_styles() {
        $screen = get_current_screen();
        if ( ! $screen || $screen->id !== 'edit-help_category' ) return;
        wp_enqueue_media(); // necesario para el uploader en el modal
        ?>
        <style>
            /* Ocultar columna izquierda (form nativo) y hacer tabla full width */
            #col-left  { display: none !important; }
            #col-right { float: none !important; width: 100% !important; }
            #col-container { display: block !important; }

            /* Modal overlay */
            #sxhc-cat-modal-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,.5);
                z-index: 100000;
                align-items: center;
                justify-content: center;
            }
            #sxhc-cat-modal-overlay.is-open {
                display: flex;
            }
            #sxhc-cat-modal {
                background: #fff;
                border-radius: 8px;
                width: 100%;
                max-width: 480px;
                box-shadow: 0 20px 60px rgba(0,0,0,.25);
                overflow: hidden;
            }
            #sxhc-cat-modal-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 16px 20px;
                border-bottom: 1px solid #dcdcde;
            }
            #sxhc-cat-modal-header h2 {
                margin: 0;
                font-size: 15px;
                font-weight: 600;
                color: #1d2327;
            }
            #sxhc-cat-modal-close {
                background: none;
                border: none;
                font-size: 22px;
                line-height: 1;
                color: #72777c;
                cursor: pointer;
                padding: 0 4px;
            }
            #sxhc-cat-modal-close:hover { color: #d63638; }
            #sxhc-cat-modal-body {
                padding: 20px;
            }
            #sxhc-cat-modal-body label {
                display: block;
                font-size: 13px;
                font-weight: 600;
                margin-bottom: 4px;
                color: #1d2327;
            }
            #sxhc-cat-modal-body input[type="text"],
            #sxhc-cat-modal-body select,
            #sxhc-cat-modal-body textarea {
                width: 100%;
                padding: 8px 10px;
                font-size: 13px;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                box-sizing: border-box;
                margin-bottom: 14px;
                color: #1d2327;
            }
            #sxhc-cat-modal-body input:focus,
            #sxhc-cat-modal-body select:focus,
            #sxhc-cat-modal-body textarea:focus {
                border-color: #2271b1;
                box-shadow: 0 0 0 1px #2271b1;
                outline: none;
            }
            #sxhc-cat-modal-body textarea { height: 72px; resize: vertical; }
            #sxhc-cat-modal-footer {
                padding: 12px 20px;
                border-top: 1px solid #f0f0f1;
                display: flex;
                justify-content: flex-end;
                gap: 8px;
                background: #f9f9f9;
            }
            #sxhc-cat-modal-error {
                display: none;
                padding: 8px 12px;
                background: #fce8e8;
                border: 1px solid #d63638;
                border-radius: 4px;
                font-size: 12px;
                color: #d63638;
                margin-bottom: 14px;
            }
        </style>
        <?php
    }

    // ── AJAX: crear categoría desde el modal ──────────────────────────────

    public static function handle_create_category() {
        check_ajax_referer( 'sxhc_create_category', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Sin permisos.' );

        $name   = isset( $_POST['name'] )        ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $parent = isset( $_POST['parent'] )      ? absint( $_POST['parent'] ) : 0;
        $desc   = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
        $slug   = isset( $_POST['slug'] )        ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';

        if ( empty( $name ) ) wp_send_json_error( 'El nombre no puede estar vacío.' );

        $args = array( 'parent' => $parent, 'description' => $desc );
        if ( $slug ) $args['slug'] = $slug;

        $result = wp_insert_term( $name, 'help_category', $args );

        if ( is_wp_error( $result ) ) wp_send_json_error( $result->get_error_message() );

        wp_send_json_success( array(
            'term_id' => $result['term_id'],
            'name'    => $name,
            'reload'  => true,
        ) );
    }

    // ── Inyectar botón + modal en la pantalla de categorías ───────────────

    public static function inject_category_modal() {
        $screen = get_current_screen();
        if ( ! $screen || $screen->id !== 'edit-help_category' ) return;

        $parent_select = self::build_select( 'sxhc_modal_parent', '— Sin padre (nivel raíz) —', true );
        $nonce         = wp_create_nonce( 'sxhc_create_category' );
        $ajax_url      = admin_url( 'admin-ajax.php' );
        ?>

        <!-- Botón en la cabecera -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var heading = document.querySelector('.wp-heading-inline');
            if (!heading) return;

            var btn = document.createElement('a');
            btn.href = '#';
            btn.id   = 'sxhc-open-modal';
            btn.className = 'page-title-action';
            btn.textContent = 'Agregar categoría';
            heading.parentNode.insertBefore(btn, heading.nextSibling);
        });
        </script>

        <!-- Modal -->
        <div id="sxhc-cat-modal-overlay">
            <div id="sxhc-cat-modal" role="dialog" aria-modal="true">
                <div id="sxhc-cat-modal-header">
                    <h2>Agregar categoría</h2>
                    <button id="sxhc-cat-modal-close" aria-label="Cerrar">&times;</button>
                </div>
                <div id="sxhc-cat-modal-body">
                    <div id="sxhc-cat-modal-error"></div>

                    <label for="sxhc-modal-name">Nombre <span style="color:#d63638;">*</span></label>
                    <input type="text" id="sxhc-modal-name" placeholder="Nombre de la categoría" autocomplete="off">

                    <label for="sxhc_modal_parent">Categoría padre</label>
                    <?php echo $parent_select; ?>

                    <label for="sxhc-modal-slug">Slug <span style="color:#999; font-weight:400;">(opcional)</span></label>
                    <input type="text" id="sxhc-modal-slug" placeholder="se-genera-automaticamente" autocomplete="off">

                    <label for="sxhc-modal-desc">Descripción <span style="color:#999; font-weight:400;">(opcional)</span></label>
                    <textarea id="sxhc-modal-desc" placeholder="Descripción breve…"></textarea>

                    <!-- Imagen: solo visible cuando no hay padre seleccionado -->
                    <div id="sxhc-modal-image-row" style="display:block;">
                        <label>Imagen <span style="color:#999; font-weight:400;">(solo categorías raíz)</span></label>
                        <div style="display:flex; align-items:center; gap:10px; margin-bottom:14px;">
                            <div id="sxhc-modal-img-preview"
                                 style="width:52px; height:52px; border:1px solid #dcdcde; border-radius:6px;
                                        background:#f9f9f9; display:flex; align-items:center; justify-content:center;
                                        flex-shrink:0; overflow:hidden;">
                                <span style="color:#c3c4c7; font-size:22px;">🖼</span>
                            </div>
                            <input type="hidden" id="sxhc-modal-img-id" value="">
                            <button type="button" id="sxhc-modal-img-upload"
                                    style="padding:5px 12px; border:1px solid #c3c4c7; border-radius:4px;
                                           background:#fff; cursor:pointer; font-size:12px;">
                                Subir imagen
                            </button>
                            <button type="button" id="sxhc-modal-img-remove"
                                    style="display:none; font-size:12px; color:#d63638; background:none;
                                           border:none; cursor:pointer; padding:0;">
                                Quitar
                            </button>
                        </div>
                    </div>
                </div>
                <div id="sxhc-cat-modal-footer">
                    <button type="button" id="sxhc-modal-cancel"
                            style="padding:7px 14px; background:transparent; border:1px solid #c3c4c7;
                                   border-radius:4px; cursor:pointer; font-size:13px; color:#1d2327;">
                        Cancelar
                    </button>
                    <button type="button" id="sxhc-modal-save"
                            style="padding:7px 16px; background:#2271b1; color:#fff; border:none;
                                   border-radius:4px; cursor:pointer; font-size:13px; font-weight:600;">
                        Agregar categoría
                    </button>
                </div>
            </div>
        </div>

        <script>
        (function($) {
            var overlay  = $('#sxhc-cat-modal-overlay');
            var $error   = $('#sxhc-cat-modal-error');

            function openModal() {
                overlay.addClass('is-open');
                $('#sxhc-modal-name').focus();
                $error.hide();
            }
            function closeModal() {
                overlay.removeClass('is-open');
                $('#sxhc-modal-name, #sxhc-modal-slug, #sxhc-modal-desc').val('');
                $('#sxhc_modal_parent').val('');
            }

            $(document).on('click', '#sxhc-open-modal', function(e) {
                e.preventDefault();
                openModal();
            });
            $('#sxhc-cat-modal-close, #sxhc-modal-cancel').on('click', closeModal);

            // Cerrar al clic fuera del modal
            overlay.on('click', function(e) {
                if ($(e.target).is(overlay)) closeModal();
            });

            // Cerrar con Escape
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && overlay.hasClass('is-open')) closeModal();
            });

            // Guardar
            $('#sxhc-modal-save').on('click', function() {
                var name   = $('#sxhc-modal-name').val().trim();
                var parent = $('#sxhc_modal_parent').val();
                var slug   = $('#sxhc-modal-slug').val().trim();
                var desc   = $('#sxhc-modal-desc').val().trim();

                if (!name) {
                    $error.text('El nombre es obligatorio.').show();
                    $('#sxhc-modal-name').focus();
                    return;
                }

                var $btn = $(this).prop('disabled', true).text('Guardando…');

                $.post('<?php echo esc_js( $ajax_url ); ?>', {
                    action:      'sxhc_create_category',
                    nonce:       '<?php echo esc_js( $nonce ); ?>',
                    name:        name,
                    parent:      parent,
                    slug:        slug,
                    description: desc
                })
                .done(function(res) {
                    if (res.success) {
                        // Recargar para mostrar la nueva categoría en la tabla
                        window.location.reload();
                    } else {
                        $error.text(res.data).show();
                        $btn.prop('disabled', false).text('Agregar categoría');
                    }
                })
                .fail(function() {
                    $error.text('Error de red. Intenta de nuevo.').show();
                    $btn.prop('disabled', false).text('Agregar categoría');
                });
            });

            // Enter en el nombre
            $('#sxhc-modal-name').on('keydown', function(e) {
                if (e.key === 'Enter') { e.preventDefault(); $('#sxhc-modal-save').click(); }
            });

            // Mostrar/ocultar imagen según padre seleccionado
            $('#sxhc_modal_parent').on('change', function() {
                var isRoot = !$(this).val() || $(this).val() === '0';
                $('#sxhc-modal-image-row').toggle(isRoot);
            });

            // Media uploader en el modal
            var modalFrame;
            $('#sxhc-modal-img-upload').on('click', function(e) {
                e.preventDefault();
                if (!window.wp || !wp.media) return;
                if (modalFrame) { modalFrame.open(); return; }
                modalFrame = wp.media({
                    title:    'Seleccionar imagen',
                    button:   { text: 'Usar esta imagen' },
                    multiple: false,
                    library:  { type: 'image' }
                });
                modalFrame.on('select', function() {
                    var att = modalFrame.state().get('selection').first().toJSON();
                    $('#sxhc-modal-img-id').val(att.id);
                    $('#sxhc-modal-img-preview').html(
                        '<img src="' + att.url + '" style="width:100%;height:100%;object-fit:contain;">'
                    );
                    $('#sxhc-modal-img-upload').text('Cambiar imagen');
                    $('#sxhc-modal-img-remove').show();
                });
                modalFrame.open();
            });

            $('#sxhc-modal-img-remove').on('click', function() {
                $('#sxhc-modal-img-id').val('');
                $('#sxhc-modal-img-preview').html('<span style="color:#c3c4c7;font-size:22px;">🖼</span>');
                $('#sxhc-modal-img-upload').text('Subir imagen');
                $(this).hide();
            });

            // Resetear imagen al cerrar modal
            overlay.on('click', closeModal);
            $('#sxhc-cat-modal-close, #sxhc-modal-cancel').on('click', function() {
                $('#sxhc-modal-img-id').val('');
                $('#sxhc-modal-img-preview').html('<span style="color:#c3c4c7;font-size:22px;">🖼</span>');
                $('#sxhc-modal-img-upload').text('Subir imagen');
                $('#sxhc-modal-img-remove').hide();
                $('#sxhc-modal-image-row').show();
            });

        })(jQuery);
        </script>
        <?php
    }
}
