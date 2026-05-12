<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SXHC_Category_Order {

    const META_KEY  = 'sxhc_term_order';
    const AJAX_SAVE = 'sxhc_save_category_order';
    const AJAX_ADD  = 'sxhc_add_category';
    const AJAX_MOVE = 'sxhc_move_category';

    public static function init() {
        add_action( 'admin_menu',                          array( __CLASS__, 'add_page' ) );
        add_action( 'admin_enqueue_scripts',               array( __CLASS__, 'enqueue' ) );
        add_action( 'wp_ajax_' . self::AJAX_SAVE,         array( __CLASS__, 'handle_save' ) );
        add_action( 'wp_ajax_' . self::AJAX_ADD,          array( __CLASS__, 'handle_add' ) );
        add_action( 'wp_ajax_' . self::AJAX_MOVE,         array( __CLASS__, 'handle_move' ) );
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
        // El hook real es: help_article_page_sxhc-category-order
        // Usamos get_current_screen() como método más confiable
        $screen = get_current_screen();
        if ( ! $screen ) return;
        if ( strpos( $screen->id, 'sxhc-category-order' ) === false ) return;

        wp_enqueue_script( 'jquery'            ); // asegurar jQuery
        wp_enqueue_script( 'jquery-ui-core'    );
        wp_enqueue_script( 'jquery-ui-widget'  );
        wp_enqueue_script( 'jquery-ui-mouse'   );
        wp_enqueue_script( 'jquery-ui-sortable');
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

    // ── Mover categoría a otro nivel vía AJAX ────────────────────────────

    public static function handle_move() {
        check_ajax_referer( self::AJAX_MOVE, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Sin permisos.' );

        $term_id    = isset( $_POST['term_id'] )    ? absint( $_POST['term_id'] )    : 0;
        $new_parent = isset( $_POST['new_parent'] ) ? absint( $_POST['new_parent'] ) : 0;
        $dest_order = isset( $_POST['dest_order'] ) ? (array) $_POST['dest_order']  : array();
        $src_order  = isset( $_POST['src_order'] )  ? (array) $_POST['src_order']   : array();

        if ( ! $term_id ) {
            wp_send_json_error( 'term_id inválido.' );
        }

        $term = get_term( $term_id, 'help_category' );
        if ( ! $term || is_wp_error( $term ) ) {
            wp_send_json_error( 'Categoría no encontrada.' );
        }

        // Actualizar el parent del término en la taxonomía
        $result = wp_update_term( $term_id, 'help_category', array( 'parent' => $new_parent ) );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        // Actualizar el orden en la lista destino
        foreach ( $dest_order as $position => $tid ) {
            update_term_meta( absint( $tid ), self::META_KEY, (int) $position );
        }

        // Actualizar el orden en la lista origen
        foreach ( $src_order as $position => $tid ) {
            update_term_meta( absint( $tid ), self::META_KEY, (int) $position );
        }

        wp_send_json_success( array(
            'moved'      => $term_id,
            'new_parent' => $new_parent,
        ) );
    }

    // ── Crear nueva categoría/subcategoría vía AJAX ───────────────────────

    public static function handle_add() {
        check_ajax_referer( self::AJAX_ADD, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Sin permisos.' );

        $name      = isset( $_POST['name'] )   ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $parent_id = isset( $_POST['parent'] ) ? absint( $_POST['parent'] ) : 0;

        if ( empty( $name ) ) {
            wp_send_json_error( 'El nombre no puede estar vacío.' );
        }

        // Verificar que no existe ya con ese nombre bajo el mismo padre
        $existing = get_term_by( 'name', $name, 'help_category' );
        if ( $existing && (int) $existing->parent === $parent_id ) {
            wp_send_json_error( 'Ya existe una categoría con ese nombre en este nivel.' );
        }

        $result = wp_insert_term( $name, 'help_category', array( 'parent' => $parent_id ) );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        $term_id = $result['term_id'];

        // Asignar orden al final de la lista
        $siblings = get_terms( array(
            'taxonomy'   => 'help_category',
            'parent'     => $parent_id,
            'hide_empty' => false,
            'fields'     => 'ids',
        ) );
        $last_pos = is_array( $siblings ) ? count( $siblings ) - 1 : 0;
        update_term_meta( $term_id, self::META_KEY, $last_pos );

        $term     = get_term( $term_id, 'help_category' );
        $edit_url = get_edit_term_link( $term_id, 'help_category' );

        wp_send_json_success( array(
            'term_id'  => $term_id,
            'name'     => $term->name,
            'edit_url' => $edit_url,
            'parent'   => $parent_id,
        ) );
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
            <h1 style="display:flex; align-items:center; gap:12px;">
                Ordenar categorías
            </h1>
            <p style="color:#666; max-width:600px; margin-bottom:16px;">
                Arrastra para reordenar. Usa el botón <strong>+</strong> para agregar subcategorías directamente desde aquí.
            </p>

            <div id="sxhc-order-status" style="display:none; margin:8px 0; padding:8px 14px;
                 border-radius:4px; font-size:13px; max-width:680px;">
            </div>

            <div id="sxhc-order-root" style="max-width:680px; margin-top:8px;">
                <?php self::render_level( $root_terms, 0 ); ?>

                <!-- Formulario para agregar categoría raíz -->
                <?php self::render_add_form( 0 ); ?>
            </div>
        </div>

        <style>
            #sxhc-order-root { font-family: -apple-system, BlinkMacSystemFont, sans-serif; }

            /* Item ─────────────────────────────────── */
            .sxhc-cat-item {
                background: #fff;
                border: 1px solid #dcdcde;
                border-radius: 6px;
                margin-bottom: 6px;
            }
            .sxhc-cat-item.ui-sortable-helper {
                box-shadow: 0 6px 20px rgba(0,0,0,.13);
                border-color: #2271b1;
                opacity: .95;
            }
            .sxhc-sortable-placeholder {
                border: 2px dashed #72aee6;
                background: #f0f6fc;
                border-radius: 6px;
                margin-bottom: 6px;
                visibility: visible !important;
                height: 40px;
            }

            /* Header ────────────────────────────────── */
            .sxhc-cat-header {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 10px 12px;
            }
            .sxhc-drag-handle {
                cursor: grab;
                color: #c3c4c7;
                font-size: 16px;
                user-select: none;
                flex-shrink: 0;
                line-height: 1;
            }
            .sxhc-drag-handle:hover { color: #72777c; }
            .sxhc-drag-handle:active { cursor: grabbing; }
            .sxhc-cat-name {
                font-size: 13px;
                font-weight: 500;
                flex: 1;
                color: #1d2327;
            }
            .sxhc-cat-edit {
                font-size: 11px;
                color: #72777c;
                text-decoration: none;
                opacity: 0;
                transition: opacity .15s;
            }
            .sxhc-cat-item:hover > .sxhc-cat-header .sxhc-cat-edit { opacity: 1; }

            /* Botón + inline (leaf) ─────────────────── */
            .sxhc-btn-add {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 26px;
                height: 26px;
                border-radius: 50%;
                border: 1.5px solid #c3c4c7;
                background: #fff;
                color: #72777c;
                font-size: 18px;
                line-height: 1;
                cursor: pointer;
                flex-shrink: 0;
                transition: all .15s;
                padding: 0;
            }
            .sxhc-btn-add:hover {
                border-color: #2271b1;
                color: #2271b1;
                background: #f0f6fc;
            }

            /* Children container ───────────────────── */
            .sxhc-cat-children {
                border-top: 1px solid #f0f0f1;
                padding: 8px 8px 0 28px;
                background: #f9f9f9;
                border-radius: 0 0 6px 6px;
            }

            /* Botón + al fondo de un grupo ─────────── */
            .sxhc-add-trigger {
                display: flex;
                justify-content: center;
                padding: 6px 0 8px;
            }
            .sxhc-add-trigger button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 26px;
                height: 26px;
                border-radius: 50%;
                border: 1.5px solid #c3c4c7;
                background: #fff;
                color: #72777c;
                font-size: 18px;
                line-height: 1;
                cursor: pointer;
                transition: all .15s;
                padding: 0;
            }
            .sxhc-add-trigger button:hover {
                border-color: #2271b1;
                color: #2271b1;
                background: #f0f6fc;
            }

            /* Formulario inline ─────────────────────── */
            .sxhc-add-form {
                display: none;
                align-items: center;
                gap: 6px;
                padding: 8px 10px;
                background: #f0f6fc;
                border: 1.5px dashed #72aee6;
                border-radius: 4px;
                margin-bottom: 6px;
            }
            .sxhc-add-form.is-open { display: flex; }
            .sxhc-add-form input[type="text"] {
                flex: 1;
                min-width: 0;
                padding: 6px 8px;
                font-size: 13px;
                border: 1px solid #c3c4c7;
                border-radius: 3px;
                outline: none;
                background: #fff;
            }
            .sxhc-add-form input[type="text"]:focus {
                border-color: #2271b1;
                box-shadow: 0 0 0 1px #2271b1;
            }
            .sxhc-save-btn {
                padding: 6px 14px;
                font-size: 12px;
                font-weight: 600;
                background: #2271b1;
                color: #fff;
                border: none;
                border-radius: 3px;
                cursor: pointer;
                white-space: nowrap;
            }
            .sxhc-save-btn:hover { background: #135e96; }
            .sxhc-cancel-btn {
                padding: 6px 8px;
                font-size: 12px;
                background: transparent;
                color: #72777c;
                border: none;
                cursor: pointer;
            }
            .sxhc-cancel-btn:hover { color: #d63638; }
        </style>

        <script>
        jQuery(document).ready(function($) {

            // Verificar que jQuery UI Sortable está disponible
            if ( typeof $.fn.sortable === 'undefined' ) {
                $('#sxhc-order-root').before(
                    '<div class="notice notice-error"><p>' +
                    '<strong>Error:</strong> jQuery UI Sortable no cargó. ' +
                    'Intenta recargar la página.</p></div>'
                );
                return;
            }

            var nonce     = '<?php echo esc_js( wp_create_nonce( self::AJAX_SAVE ) ); ?>';
            var nonceMove = '<?php echo esc_js( wp_create_nonce( self::AJAX_MOVE ) ); ?>';
            var ajaxUrl   = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
            var moveTimer;   // timer del AJAX_MOVE (cross-list)
            var orderTimer;  // timer del AJAX_SAVE (same-list reorder)

            function showStatus( msg, ok ) {
                var $s = $('#sxhc-order-status');
                $s.stop(true, true)
                  .css('background', ok ? '#f0f9e8' : '#fde8e8')
                  .css('border-color', ok ? '#7ad03a' : '#d63638')
                  .text( msg )
                  .show()
                  .delay(2500)
                  .fadeOut(400);
            }

            function saveOrder( $list ) {
                var ids = [];
                $list.children('.sxhc-cat-item').each(function() {
                    ids.push( $(this).data('term-id') );
                });

                $.post(ajaxUrl, {
                    action: '<?php echo esc_js( self::AJAX_SAVE ); ?>',
                    nonce:  nonce,
                    order:  ids
                })
                .done(function(res) {
                    if (res.success) {
                        showStatus('✅ Orden guardado (' + res.data.saved + ' categorías)', true);
                    } else {
                        showStatus('❌ Error al guardar', false);
                    }
                })
                .fail(function() {
                    showStatus('❌ Error de red', false);
                });
            }

            // Retorna el term_id del padre de una lista (0 = raíz)
            function getListParentId( $list ) {
                var $parentItem = $list.closest('.sxhc-cat-item');
                return $parentItem.length ? parseInt( $parentItem.data('term-id'), 10 ) : 0;
            }

            // Retorna array de term_ids de los hijos directos de una lista
            function getListOrder( $list ) {
                var ids = [];
                $list.children('.sxhc-cat-item').each(function() {
                    ids.push( parseInt( $(this).data('term-id'), 10 ) );
                });
                return ids;
            }

            // Limpia el contenedor padre si quedó vacío tras mover el último hijo
            function cleanupEmptyContainer( $srcList ) {
                if ( $srcList.children('.sxhc-cat-item').length > 0 ) return;

                var $srcParentItem = $srcList.closest('.sxhc-cat-item');
                var $container     = $srcList.closest('.sxhc-cat-children');

                if ( $container.length && $srcParentItem.length ) {
                    var parentId = $srcParentItem.data('term-id');
                    $container.remove();

                    // Restaurar botón + en el header del padre (ahora es hoja)
                    var $header = $srcParentItem.find('> .sxhc-cat-header');
                    if ( ! $header.find('.sxhc-btn-add').length ) {
                        $header.append(
                            '<button type="button" class="sxhc-btn-add" ' +
                            'data-parent="' + parentId + '" ' +
                            'title="Agregar subcategoría">+</button>'
                        );
                    }

                    // Restaurar formulario oculto para hoja
                    if ( ! $srcParentItem.find('> .sxhc-add-form').length ) {
                        $srcParentItem.append(
                            '<div class="sxhc-add-form" data-parent="' + parentId + '">' +
                                '<input type="text" placeholder="Nueva subcategoría…" ' +
                                       'class="sxhc-new-name" autocomplete="off"/>' +
                                '<button type="button" class="sxhc-save-btn">Agregar</button>' +
                                '<button type="button" class="sxhc-cancel-btn">Cancelar</button>' +
                            '</div>'
                        );
                    }
                }
            }

            function initSortable( $list ) {
                $list.sortable({
                    handle:               '.sxhc-drag-handle',
                    connectWith:          '.sxhc-sortable-list',
                    placeholder:          'sxhc-sortable-placeholder',
                    forcePlaceholderSize: true,
                    tolerance:            'pointer',
                    cursor:               'grabbing',
                    opacity:              0.85,

                    // Fired en la lista DESTINO cuando llega un ítem de otra lista
                    receive: function( event, ui ) {
                        var $destList = $( this );
                        var $srcList  = ui.sender;
                        var $item     = ui.item;
                        var termId    = parseInt( $item.data('term-id'), 10 );
                        var newParent = getListParentId( $destList );
                        var destOrder = getListOrder( $destList );
                        var srcOrder  = getListOrder( $srcList );

                        cleanupEmptyContainer( $srcList );

                        clearTimeout( moveTimer );
                        moveTimer = setTimeout(function() {
                            $.post( ajaxUrl, {
                                action:     '<?php echo esc_js( self::AJAX_MOVE ); ?>',
                                nonce:      nonceMove,
                                term_id:    termId,
                                new_parent: newParent,
                                dest_order: destOrder,
                                src_order:  srcOrder
                            })
                            .done(function( res ) {
                                if ( res.success ) {
                                    showStatus( '✅ Categoría movida correctamente', true );
                                } else {
                                    showStatus( '❌ ' + res.data, false );
                                }
                            })
                            .fail(function() {
                                showStatus( '❌ Error de red', false );
                            });
                        }, 400 );
                    },

                    // Fired al terminar el drag (en la lista origen).
                    // Detectamos same-list reorder verificando que el item siga en ESTA lista.
                    // En cross-list moves, el item ya no está aquí → lo maneja `receive`.
                    stop: function( event, ui ) {
                        var listEl = this;
                        if ( ! $.contains( listEl, ui.item[0] ) ) return;

                        clearTimeout( orderTimer );
                        var $self = $( listEl );
                        orderTimer = setTimeout(function() { saveOrder( $self ); }, 400 );
                    }
                });
                $list.disableSelection();
            }

            var count = 0;
            $('.sxhc-sortable-list').each(function() {
                initSortable($(this));
                count++;
            });

            if (count === 0) {
                $('#sxhc-order-root').prepend(
                    '<p style="color:#888;">No se encontraron categorías para ordenar.</p>'
                );
            }

            // ── Agregar categoría / subcategoría ──────────────────────────

            var nonceAdd = '<?php echo esc_js( wp_create_nonce( self::AJAX_ADD ) ); ?>';

            // Abrir formulario al hacer clic en + de una fila
            $(document).on('click', '.sxhc-btn-add', function() {
                var parentId = $(this).data('parent');
                var $form    = $('.sxhc-add-form[data-parent="' + parentId + '"]').first();
                var isOpen   = $form.hasClass('is-open');

                // Cerrar todos primero
                $('.sxhc-add-form').removeClass('is-open');
                $('.sxhc-add-root').show();

                if (!isOpen) {
                    $form.addClass('is-open').find('.sxhc-new-name').val('').focus();
                    if (parentId == 0) $(this).hide(); // ocultar el botón raíz mientras está abierto
                }
            });

            // Cancelar
            $(document).on('click', '.sxhc-cancel-btn', function() {
                var $form = $(this).closest('.sxhc-add-form');
                $form.removeClass('is-open');
                $('.sxhc-add-root').show();
            });

            // Guardar nueva categoría
            $(document).on('click', '.sxhc-save-btn', function() {
                var $form    = $(this).closest('.sxhc-add-form');
                var $input   = $form.find('.sxhc-new-name');
                var name     = $input.val().trim();
                var parentId = $form.data('parent');

                if (!name) {
                    $input.css('border-color', '#d63638').focus();
                    setTimeout(function() { $input.css('border-color', ''); }, 1500);
                    return;
                }

                var $btn = $(this).prop('disabled', true).text('Guardando…');

                $.post(ajaxUrl, {
                    action: '<?php echo esc_js( self::AJAX_ADD ); ?>',
                    nonce:  nonceAdd,
                    name:   name,
                    parent: parentId
                })
                .done(function(res) {
                    if (!res.success) {
                        showStatus('❌ ' + res.data, false);
                        $btn.prop('disabled', false).text('Agregar');
                        return;
                    }

                    var d = res.data;

                    // Construir la nueva fila HTML
                    var $newItem = $(
                        '<li class="sxhc-cat-item" data-term-id="' + d.term_id + '">' +
                            '<div class="sxhc-cat-header">' +
                                '<span class="sxhc-drag-handle" title="Arrastrar para reordenar">⠿</span>' +
                                '<span class="sxhc-cat-name">' + $('<span>').text(d.name).html() + '</span>' +
                                '<span class="sxhc-cat-meta">0 sub · 0 arts.</span>' +
                                '<a href="' + d.edit_url + '" style="font-size:11px;color:#2271b1;white-space:nowrap;">Editar</a>' +
                                '<button type="button" class="sxhc-btn-add" data-parent="' + d.term_id + '" title="Agregar subcategoría">+</button>' +
                            '</div>' +
                            '<div class="sxhc-add-form" data-parent="' + d.term_id + '">' +
                                '<input type="text" placeholder="Nombre de la subcategoría" class="sxhc-new-name" autocomplete="off"/>' +
                                '<button type="button" class="sxhc-save-btn">Agregar</button>' +
                                '<button type="button" class="sxhc-cancel-btn">Cancelar</button>' +
                            '</div>' +
                        '</li>'
                    );

                    // Insertar antes del formulario de agregar del padre
                    $form.before($newItem);

                    // Inicializar sortable si la lista padre ya existe, si no crearla
                    var $parentItem = parentId == 0
                        ? null
                        : $('.sxhc-cat-item[data-term-id="' + parentId + '"]');

                    if (parentId != 0 && $parentItem.length) {
                        var $children = $parentItem.find('> .sxhc-cat-children');
                        if (!$children.length) {
                            $children = $('<div class="sxhc-cat-children"></div>');
                            $parentItem.append($children);
                        }
                        var $list = $children.find('> .sxhc-sortable-list');
                        if (!$list.length) {
                            $list = $('<ul class="sxhc-sortable-list" style="list-style:none;padding:0;margin:0;"></ul>');
                            $children.prepend($list);
                            initSortable($list);
                        }
                        $list.append($newItem);
                        $parentItem.find('> .sxhc-cat-header .sxhc-cat-meta').text(
                            ($list.children('.sxhc-cat-item').length) + ' sub · ' +
                            $parentItem.find('> .sxhc-cat-header .sxhc-cat-meta').text().split('·')[1]
                        );
                    }

                    // Cerrar formulario y resetear
                    $form.removeClass('is-open');
                    $('.sxhc-add-root').show();
                    $btn.prop('disabled', false).text('Agregar');

                    // Highlight breve
                    $newItem.css('background', '#f0f6fc');
                    setTimeout(function() { $newItem.css('background', ''); }, 1200);

                    showStatus('✅ "' + d.name + '" creada correctamente', true);
                })
                .fail(function() {
                    showStatus('❌ Error de red', false);
                    $btn.prop('disabled', false).text('Agregar');
                });
            });

            // Guardar con Enter en el input
            $(document).on('keydown', '.sxhc-new-name', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    $(this).siblings('.sxhc-save-btn').click();
                }
                if (e.key === 'Escape') {
                    $(this).siblings('.sxhc-cancel-btn').click();
                }
            });

        });
        </script>
        <?php
    }

    /**
     * Renderiza un nivel del árbol.
     *
     * Regla visual:
     * - Ítems HOJA (sin hijos): el botón + aparece a la DERECHA del header
     * - Ítems PADRE (con hijos): el botón + aparece ABAJO del último hijo
     */
    private static function render_level( $terms, $depth ) {
        if ( empty( $terms ) ) return;
        ?>
        <ul class="sxhc-sortable-list" style="list-style:none; padding:0; margin:0;">
            <?php foreach ( $terms as $term ) :
                $children    = self::get_ordered_terms( $term->term_id );
                $has_children = ! empty( $children );
                $edit_url    = get_edit_term_link( $term->term_id, 'help_category' );
                ?>
                <li class="sxhc-cat-item" data-term-id="<?php echo esc_attr( $term->term_id ); ?>">

                    <div class="sxhc-cat-header">
                        <span class="sxhc-drag-handle" title="Arrastrar">⠿</span>
                        <span class="sxhc-cat-name"><?php echo esc_html( $term->name ); ?></span>
                        <a href="<?php echo esc_url( $edit_url ); ?>"
                           class="sxhc-cat-edit">Editar</a>

                        <?php if ( ! $has_children ) : ?>
                            <!-- Hoja: + en el header -->
                            <button type="button"
                                    class="sxhc-btn-add"
                                    data-parent="<?php echo esc_attr( $term->term_id ); ?>"
                                    title="Agregar subcategoría">+</button>
                        <?php endif; ?>
                    </div>

                    <?php if ( $has_children ) : ?>
                        <div class="sxhc-cat-children">
                            <?php self::render_level( $children, $depth + 1 ); ?>
                            <!-- Padre: formulario + botón + al fondo -->
                            <?php self::render_add_trigger( $term->term_id ); ?>
                        </div>
                    <?php else : ?>
                        <!-- Formulario oculto para hoja -->
                        <?php self::render_add_form( $term->term_id ); ?>
                    <?php endif; ?>

                </li>
            <?php endforeach; ?>
        </ul>
        <?php
    }

    /** Botón circular + centrado al fondo de un contenedor con hijos */
    private static function render_add_trigger( $parent_id ) {
        ?>
        <div class="sxhc-add-form" data-parent="<?php echo esc_attr( $parent_id ); ?>">
            <input type="text" placeholder="Nueva subcategoría…"
                   class="sxhc-new-name" autocomplete="off"/>
            <button type="button" class="sxhc-save-btn">Agregar</button>
            <button type="button" class="sxhc-cancel-btn">Cancelar</button>
        </div>
        <div class="sxhc-add-trigger">
            <button type="button" class="sxhc-btn-add"
                    data-parent="<?php echo esc_attr( $parent_id ); ?>"
                    title="Agregar subcategoría">+</button>
        </div>
        <?php
    }

    /** Formulario oculto para ítems hoja (se muestra debajo del header) */
    private static function render_add_form( $parent_id ) {
        $is_root = ( $parent_id === 0 );
        $placeholder = $is_root ? 'Nueva categoría…' : 'Nueva subcategoría…';
        ?>
        <div class="sxhc-add-form" data-parent="<?php echo esc_attr( $parent_id ); ?>">
            <input type="text" placeholder="<?php echo esc_attr( $placeholder ); ?>"
                   class="sxhc-new-name" autocomplete="off"/>
            <button type="button" class="sxhc-save-btn">Agregar</button>
            <button type="button" class="sxhc-cancel-btn">Cancelar</button>
        </div>
        <?php if ( $is_root ) : ?>
            <!-- Botón + raíz al final de todo -->
            <div class="sxhc-add-trigger" style="padding:10px 0 4px;">
                <button type="button" class="sxhc-btn-add" data-parent="0"
                        title="Nueva categoría raíz">+</button>
            </div>
        <?php endif; ?>
        <?php
    }
}
