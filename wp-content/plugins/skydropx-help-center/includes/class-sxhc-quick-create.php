<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Botón "Crear otro X" en el header del editor de bloques.
 *
 * Aparece a la izquierda del bloque "Guardar como borrador / Publicar" y
 * abre un editor en blanco del MISMO post type que estás editando ahora:
 *   - Editando un help_article  → botón "Nuevo artículo"      (post-new.php?post_type=help_article)
 *   - Editando un sxhc_faq      → botón "Nueva pregunta"      (post-new.php?post_type=sxhc_faq)
 *
 * Si la entrada actual tiene cambios sin guardar pide confirmación con
 * window.confirm() antes de navegar. No pre-llena nada en el destino.
 *
 * Compatible con PHP 7.2 y con el editor de bloques (Gutenberg).
 */
class SXHC_Quick_Create {

    const SCRIPT_HANDLE  = 'sxhc-quick-create';
    const SCRIPT_VERSION = '1.0';

    public static function init() {
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
    }

    public static function enqueue( $hook ) {
        if ( $hook !== 'post.php' && $hook !== 'post-new.php' ) {
            return;
        }

        $screen = get_current_screen();
        if ( ! $screen ) {
            return;
        }

        $current = $screen->post_type;
        if ( $current !== 'help_article' && $current !== 'sxhc_faq' ) {
            return;
        }

        // Solo cuando estamos en el editor de bloques.
        if ( method_exists( $screen, 'is_block_editor' ) && ! $screen->is_block_editor() ) {
            return;
        }

        // Defensa en profundidad: si el usuario no puede crear entradas, no mostramos el botón.
        if ( ! current_user_can( 'edit_posts' ) ) {
            return;
        }

        // El destino es siempre el MISMO post type que se está editando.
        $target_slug = $current;

        if ( $current === 'help_article' ) {
            $button_label = 'Nuevo artículo';
        } else {
            $button_label = 'Nueva pregunta';
        }

        wp_enqueue_script(
            self::SCRIPT_HANDLE,
            SXHC_URL . 'assets/js/quick-create.js',
            array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-data', 'wp-components', 'wp-dom-ready' ),
            self::SCRIPT_VERSION,
            true
        );

        wp_localize_script(
            self::SCRIPT_HANDLE,
            'sxhcQuickCreate',
            array(
                'targetUrl'   => admin_url( 'post-new.php?post_type=' . $target_slug ),
                'buttonLabel' => $button_label,
                'confirmText' => sprintf(
                    'Tienes cambios sin guardar en esta entrada. ¿Salir igual y abrir el editor de %s?',
                    strtolower( $button_label )
                ),
            )
        );

        // Margen pequeño para que no quede pegado al botón "Guardar como borrador".
        wp_add_inline_style(
            'wp-edit-post',
            '#sxhc-quick-create-btn { margin-right: 8px; }'
        );
    }
}
