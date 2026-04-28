<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SXHC_Category_Meta {

    const META_IMAGE = 'sxhc_category_image';

    public static function init() {
        // Formulario de NUEVA categoría
        add_action( 'help_category_add_form_fields',  array( __CLASS__, 'add_image_field' ) );
        // Formulario de EDITAR categoría
        add_action( 'help_category_edit_form_fields', array( __CLASS__, 'edit_image_field' ), 10, 2 );

        // Guardar al crear / editar
        add_action( 'created_help_category', array( __CLASS__, 'save_image' ) );
        add_action( 'edited_help_category',  array( __CLASS__, 'save_image' ) );

        // Cargar media uploader en la pantalla de categorías
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
    }

    // ── Encolar media library ─────────────────────────────────────────────

    public static function enqueue( $hook ) {
        if ( $hook !== 'edit-tags.php' && $hook !== 'term.php' ) return;
        $screen = get_current_screen();
        if ( ! $screen || $screen->taxonomy !== 'help_category' ) return;
        wp_enqueue_media();
    }

    // ── Helper: ¿el término es de nivel raíz? ─────────────────────────────

    private static function is_root( $term_id ) {
        $term = get_term( $term_id, 'help_category' );
        return $term && ! is_wp_error( $term ) && (int) $term->parent === 0;
    }

    // ── Campo en formulario de NUEVA categoría ────────────────────────────

    public static function add_image_field() {
        ?>
        <div class="form-field sxhc-image-field" id="sxhc-image-row-new">
            <label><?php esc_html_e( 'Imagen de la categoría', 'skydropx-hc' ); ?></label>
            <?php self::render_uploader( 0, '' ); ?>
            <p class="description">Solo disponible en categorías raíz (sin padre). Recomendado: SVG o PNG transparente.</p>
        </div>
        <?php self::uploader_script(); ?>
        <?php
    }

    // ── Campo en formulario de EDITAR categoría ───────────────────────────

    public static function edit_image_field( $term, $taxonomy ) {
        $is_root  = self::is_root( $term->term_id );
        $image_id = (int) get_term_meta( $term->term_id, self::META_IMAGE, true );
        $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';
        ?>
        <tr class="form-field sxhc-image-field" id="sxhc-image-row-edit"
            style="<?php echo $is_root ? '' : 'display:none;'; ?>">
            <th scope="row">
                <label><?php esc_html_e( 'Imagen de la categoría', 'skydropx-hc' ); ?></label>
            </th>
            <td>
                <?php self::render_uploader( $term->term_id, $image_url, $image_id ); ?>
                <p class="description">Solo disponible en categorías raíz (sin padre).</p>
            </td>
        </tr>
        <?php self::uploader_script(); ?>
        <?php
    }

    // ── UI del uploader ───────────────────────────────────────────────────

    private static function render_uploader( $term_id, $image_url = '', $image_id = 0 ) {
        ?>
        <div class="sxhc-uploader-wrap" style="display:flex; align-items:flex-start; gap:14px; flex-wrap:wrap;">
            <!-- Preview -->
            <div id="sxhc-img-preview" style="width:80px; height:80px; border:1px solid #dcdcde;
                 border-radius:6px; overflow:hidden; background:#f9f9f9; flex-shrink:0;
                 display:flex; align-items:center; justify-content:center;">
                <?php if ( $image_url ) : ?>
                    <img src="<?php echo esc_url( $image_url ); ?>"
                         id="sxhc-img-preview-img"
                         style="width:100%; height:100%; object-fit:contain;">
                <?php else : ?>
                    <span id="sxhc-img-preview-img"
                          style="color:#c3c4c7; font-size:28px; line-height:1;">🖼</span>
                <?php endif; ?>
            </div>

            <!-- Botones + input oculto -->
            <div style="display:flex; flex-direction:column; gap:6px; justify-content:center;">
                <input type="hidden" name="sxhc_category_image"
                       id="sxhc-img-id"
                       value="<?php echo esc_attr( $image_id ); ?>">
                <button type="button" id="sxhc-img-upload" class="button">
                    <?php echo $image_id ? 'Cambiar imagen' : 'Subir imagen'; ?>
                </button>
                <button type="button" id="sxhc-img-remove" class="button-link-delete"
                        style="font-size:12px; <?php echo $image_id ? '' : 'display:none;'; ?>">
                    Quitar imagen
                </button>
            </div>
        </div>
        <?php
    }

    // ── JS del uploader (se imprime una sola vez) ─────────────────────────

    private static function uploader_script() {
        static $printed = false;
        if ( $printed ) return;
        $printed = true;
        ?>
        <script>
        (function($){
            var frame;

            $(document).on('click', '#sxhc-img-upload', function(e) {
                e.preventDefault();
                if (frame) { frame.open(); return; }
                frame = wp.media({
                    title:    'Seleccionar imagen de categoría',
                    button:   { text: 'Usar esta imagen' },
                    multiple: false,
                    library:  { type: 'image' }
                });
                frame.on('select', function() {
                    var att = frame.state().get('selection').first().toJSON();
                    $('#sxhc-img-id').val(att.id);
                    var $preview = $('#sxhc-img-preview');
                    $preview.html('<img src="' + att.url + '" id="sxhc-img-preview-img" style="width:100%;height:100%;object-fit:contain;">');
                    $('#sxhc-img-upload').text('Cambiar imagen');
                    $('#sxhc-img-remove').show();
                });
                frame.open();
            });

            $(document).on('click', '#sxhc-img-remove', function(e) {
                e.preventDefault();
                $('#sxhc-img-id').val('');
                $('#sxhc-img-preview').html('<span style="color:#c3c4c7;font-size:28px;line-height:1;">🖼</span>');
                $('#sxhc-img-upload').text('Subir imagen');
                $(this).hide();
            });

            // En "Agregar categoría": ocultar/mostrar campo imagen según padre
            var $parentSelect = $('#parent');
            if ($parentSelect.length) {
                function toggleImageField() {
                    var val = $parentSelect.val();
                    var isRoot = (!val || val === '0' || val === '-1');
                    $('#sxhc-image-row-new').toggle(isRoot);
                }
                $parentSelect.on('change', toggleImageField);
                toggleImageField();
            }
        })(jQuery);
        </script>
        <?php
    }

    // ── Guardar meta ──────────────────────────────────────────────────────

    public static function save_image( $term_id ) {
        if ( ! isset( $_POST['sxhc_category_image'] ) ) return;
        if ( ! current_user_can( 'manage_options' ) ) return;

        $image_id = absint( $_POST['sxhc_category_image'] );

        // Solo guardar en raíz
        if ( ! self::is_root( $term_id ) ) {
            delete_term_meta( $term_id, self::META_IMAGE );
            return;
        }

        if ( $image_id ) {
            update_term_meta( $term_id, self::META_IMAGE, $image_id );
        } else {
            delete_term_meta( $term_id, self::META_IMAGE );
        }
    }

    // ── Helper público: obtener URL de la imagen ──────────────────────────

    public static function get_image_url( $term_id, $size = 'medium' ) {
        $image_id = (int) get_term_meta( $term_id, self::META_IMAGE, true );
        if ( ! $image_id ) return '';
        return wp_get_attachment_image_url( $image_id, $size ) ?: '';
    }
}
