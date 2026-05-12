<?php
/**
 * Plugin Name:  SXHC Announcement Banner
 * Description:  Muestra un banner de anuncio debajo del navbar. Totalmente configurable desde el admin.
 * Version:      1.0.0
 * Author:       Skydropx
 * Text Domain:  sxhc-banner
 * Requires at least: 6.0
 * Requires PHP: 7.2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SXHC_BANNER_VERSION', '1.0.0' );
define( 'SXHC_BANNER_OPT',     'sxhc_announcement_banner' );

class SXHC_Announcement_Banner {

    // ── Bootstrap ─────────────────────────────────────────────────────────

    public static function init() {
        add_action( 'admin_menu',        array( __CLASS__, 'add_settings_page' ) );
        add_action( 'admin_init',        array( __CLASS__, 'register_settings' ) );
        add_action( 'sxhc_after_header', array( __CLASS__, 'render_banner' ) );
    }

    // ── Defaults & helpers ────────────────────────────────────────────────

    public static function get_defaults() {
        return array(
            'enabled'       => '0',
            'badge_enabled' => '1',
            'badge_text'    => 'Nueva versión',
            'description'   => '¿Tienes dudas sobre la actualización de tu cuenta?',
            'cta_text'      => 'Preguntas frecuentes',
            'cta_url'       => '',
        );
    }

    public static function get_options() {
        return wp_parse_args(
            (array) get_option( SXHC_BANNER_OPT, array() ),
            self::get_defaults()
        );
    }

    // ── Settings page ─────────────────────────────────────────────────────

    public static function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=help_article',
            'Banner de anuncio',
            'Banner de anuncio',
            'manage_options',
            'sxhc-announcement-banner',
            array( __CLASS__, 'render_settings_page' )
        );
    }

    public static function register_settings() {
        register_setting(
            'sxhc_banner_group',
            SXHC_BANNER_OPT,
            array( 'sanitize_callback' => array( __CLASS__, 'sanitize_options' ) )
        );
    }

    public static function sanitize_options( $input ) {
        return array(
            'enabled'       => ! empty( $input['enabled'] )       ? '1' : '0',
            'badge_enabled' => ! empty( $input['badge_enabled'] ) ? '1' : '0',
            'badge_text'    => isset( $input['badge_text'] )    ? sanitize_text_field( wp_unslash( $input['badge_text'] ) )    : '',
            'description'   => isset( $input['description'] )   ? sanitize_text_field( wp_unslash( $input['description'] ) )   : '',
            'cta_text'      => isset( $input['cta_text'] )      ? sanitize_text_field( wp_unslash( $input['cta_text'] ) )      : '',
            'cta_url'       => isset( $input['cta_url'] )       ? esc_url_raw( wp_unslash( $input['cta_url'] ) )               : '',
        );
    }

    // ── Admin page render ─────────────────────────────────────────────────

    public static function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $opts = self::get_options();
        ?>
        <div class="wrap">
            <h1 style="display:flex; align-items:center; gap:10px;">
                Banner de anuncio
                <?php if ( $opts['enabled'] === '1' ) : ?>
                    <span style="font-size:12px; font-weight:600; padding:3px 10px; border-radius:9999px;
                                 background:#d1fae5; color:#065f46;">Activo</span>
                <?php else : ?>
                    <span style="font-size:12px; font-weight:600; padding:3px 10px; border-radius:9999px;
                                 background:#f1f5f9; color:#64748b;">Inactivo</span>
                <?php endif; ?>
            </h1>
            <p style="color:#666; max-width:640px; margin-bottom:24px;">
                Configura el banner que aparece debajo del navbar en el frontend del sitio.
            </p>

            <?php if ( isset( $_GET['settings-updated'] ) ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p>Cambios guardados correctamente.</p>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php settings_fields( 'sxhc_banner_group' ); ?>

                <table class="form-table" role="presentation" style="max-width:640px;">

                    <tr>
                        <th scope="row">Estado del banner</th>
                        <td>
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox"
                                       name="<?php echo esc_attr( SXHC_BANNER_OPT ); ?>[enabled]"
                                       value="1"
                                       <?php checked( $opts['enabled'], '1' ); ?>>
                                <span>Mostrar el banner en el sitio</span>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Badge</th>
                        <td>
                            <label style="display:flex; align-items:center; gap:8px; margin-bottom:10px; cursor:pointer;">
                                <input type="checkbox"
                                       name="<?php echo esc_attr( SXHC_BANNER_OPT ); ?>[badge_enabled]"
                                       value="1"
                                       <?php checked( $opts['badge_enabled'], '1' ); ?>>
                                <span>Mostrar badge</span>
                            </label>
                            <input type="text"
                                   name="<?php echo esc_attr( SXHC_BANNER_OPT ); ?>[badge_text]"
                                   value="<?php echo esc_attr( $opts['badge_text'] ); ?>"
                                   class="regular-text"
                                   placeholder="Nueva versión">
                            <p class="description">Texto que aparece dentro del badge.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="sxhc_banner_desc">Descripción</label></th>
                        <td>
                            <input type="text"
                                   id="sxhc_banner_desc"
                                   name="<?php echo esc_attr( SXHC_BANNER_OPT ); ?>[description]"
                                   value="<?php echo esc_attr( $opts['description'] ); ?>"
                                   class="large-text"
                                   placeholder="¿Tienes dudas sobre la actualización de tu cuenta?">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Botón CTA</th>
                        <td>
                            <input type="text"
                                   name="<?php echo esc_attr( SXHC_BANNER_OPT ); ?>[cta_text]"
                                   value="<?php echo esc_attr( $opts['cta_text'] ); ?>"
                                   class="regular-text"
                                   placeholder="Preguntas frecuentes"
                                   style="margin-bottom:8px; display:block;">
                            <input type="url"
                                   name="<?php echo esc_attr( SXHC_BANNER_OPT ); ?>[cta_url]"
                                   value="<?php echo esc_attr( $opts['cta_url'] ); ?>"
                                   class="large-text"
                                   placeholder="https://help.skydropx.com/...">
                            <p class="description">Deja el campo URL vacío para ocultar el botón.</p>
                        </td>
                    </tr>

                </table>

                <!-- Vista previa ─────────────────────────────────────────── -->
                <h2 style="margin-top:32px; font-size:15px; font-weight:600;">Vista previa</h2>
                <div style="border:1px solid #dcdcde; border-radius:6px; overflow:hidden;
                            max-width:640px; margin-bottom:28px;">
                    <?php self::print_banner_styles(); ?>
                    <?php self::render_banner_html( $opts ); ?>
                </div>

                <?php submit_button( 'Guardar cambios' ); ?>
            </form>
        </div>
        <?php
    }

    // ── Frontend output ───────────────────────────────────────────────────

    public static function render_banner() {
        // Solo se muestra en el home del help center.
        if ( ! is_front_page() ) return;

        $opts = self::get_options();
        if ( $opts['enabled'] !== '1' ) return;
        self::print_banner_styles();
        self::render_banner_html( $opts );
    }

    // ── Shared: CSS & HTML ────────────────────────────────────────────────

    private static function print_banner_styles() {
        static $printed = false;
        if ( $printed ) return;
        $printed = true;
        ?>
        <style id="sxhc-banner-style">
        .sxhc-announcement-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 9px 24px;
            background: linear-gradient(90deg, #3b0764 0%, #6d28d9 55%, #7c3aed 100%);
        }
        .sxhc-banner-left {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            min-width: 0;
        }
        .sxhc-banner-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 10px;
            border-radius: 9999px;
            border: 1.5px solid rgba(255,255,255,0.55);
            background: rgba(255,255,255,0.1);
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
            letter-spacing: .01em;
        }
        .sxhc-banner-description {
            color: rgba(255,255,255,0.9);
            font-size: 14px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .sxhc-banner-cta {
            display: inline-flex;
            align-items: center;
            padding: 6px 16px;
            border-radius: 6px;
            background: rgba(255,255,255,0.12);
            border: 1.5px solid rgba(255,255,255,0.45);
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            white-space: nowrap;
            transition: background .15s, border-color .15s;
            flex-shrink: 0;
        }
        .sxhc-banner-cta:hover {
            background: rgba(255,255,255,0.22);
            border-color: rgba(255,255,255,0.75);
            color: #fff;
            text-decoration: none;
        }
        @media (max-width: 640px) {
            .sxhc-announcement-banner {
                flex-wrap: wrap;
                gap: 8px;
                padding: 10px 16px;
            }
            .sxhc-banner-description { white-space: normal; }
        }
        </style>
        <?php
    }

    private static function render_banner_html( $opts ) {
        $has_badge = ( $opts['badge_enabled'] === '1' && ! empty( $opts['badge_text'] ) );
        $has_cta   = ( ! empty( $opts['cta_text'] ) && ! empty( $opts['cta_url'] ) );
        ?>
        <div class="sxhc-announcement-banner">
            <div class="sxhc-banner-left">
                <?php if ( $has_badge ) : ?>
                    <span class="sxhc-banner-badge">
                        <?php echo esc_html( $opts['badge_text'] ); ?>
                    </span>
                <?php endif; ?>
                <?php if ( ! empty( $opts['description'] ) ) : ?>
                    <span class="sxhc-banner-description">
                        <?php echo esc_html( $opts['description'] ); ?>
                    </span>
                <?php endif; ?>
            </div>
            <?php if ( $has_cta ) : ?>
                <a href="<?php echo esc_url( $opts['cta_url'] ); ?>"
                   class="sxhc-banner-cta">
                    <?php echo esc_html( $opts['cta_text'] ); ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
    }
}

SXHC_Announcement_Banner::init();
