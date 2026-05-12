<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SXHC_Appearance {

    // Valores por defecto — usados también como fallback en get()
    const DEFAULTS = array(
        'logo_id'           => 0,
        'logo_width'        => 140,
        'site_name'         => 'Skydropx',
        'color_brand'       => '#4B47D6',
        'color_brand_light' => '#EEF0FF',
        'color_text'        => '#111827',
        'color_muted'       => '#6B7280',
        'color_border'      => '#E5E7EB',
        'bg_page'           => '#F9FAFB',
        'bg_header'         => '#FFFFFF',
        'bg_hero'           => '#F8F8FC',
        'bg_sidebar'        => '#FFFFFF',
        'font_family'       => 'system-ui, -apple-system, sans-serif',
        'card_radius'       => 16,
    );

    public static function init() {
        add_action( 'customize_register',  array( __CLASS__, 'register_customizer' ) );
        add_action( 'wp_head',             array( __CLASS__, 'inject_css_vars' ), 5 );
        add_action( 'customize_preview_init', array( __CLASS__, 'preview_js' ) );
    }

    // ── Leer un valor (theme_mod con fallback a DEFAULTS) ─────────────────

    public static function get( $key = null ) {
        $out = array();
        foreach ( self::DEFAULTS as $k => $default ) {
            $out[ $k ] = get_theme_mod( 'sxhc_' . $k, $default );
        }
        return $key ? ( isset( $out[ $key ] ) ? $out[ $key ] : null ) : $out;
    }

    // ── Registrar todo en el Customizer ───────────────────────────────────

    public static function register_customizer( $wp_customize ) {

        // ── Panel principal ───────────────────────────────────────────────
        $wp_customize->add_panel( 'sxhc_panel', array(
            'title'    => 'Help Center',
            'priority' => 30,
        ) );

        // ════════════════════════════════════════
        //  Sección: Identidad
        // ════════════════════════════════════════
        $wp_customize->add_section( 'sxhc_identity', array(
            'title' => 'Identidad',
            'panel' => 'sxhc_panel',
        ) );

        // Logo
        $wp_customize->add_setting( 'sxhc_logo_id', array(
            'default'           => 0,
            'sanitize_callback' => 'absint',
            'transport'         => 'postMessage',
        ) );
        $wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'sxhc_logo_id', array(
            'label'     => 'Logo',
            'section'   => 'sxhc_identity',
            'mime_type' => 'image',
        ) ) );

        // Ancho del logo
        $wp_customize->add_setting( 'sxhc_logo_width', array(
            'default'           => 140,
            'sanitize_callback' => 'absint',
            'transport'         => 'postMessage',
        ) );
        $wp_customize->add_control( 'sxhc_logo_width', array(
            'label'       => 'Ancho del logo (px)',
            'section'     => 'sxhc_identity',
            'type'        => 'number',
            'input_attrs' => array( 'min' => 40, 'max' => 400, 'step' => 1 ),
        ) );

        // Nombre del sitio (fallback sin logo)
        $wp_customize->add_setting( 'sxhc_site_name', array(
            'default'           => 'Skydropx',
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'postMessage',
        ) );
        $wp_customize->add_control( 'sxhc_site_name', array(
            'label'       => 'Nombre de la marca',
            'description' => 'Se muestra en el header si no hay logo.',
            'section'     => 'sxhc_identity',
            'type'        => 'text',
        ) );

        // ════════════════════════════════════════
        //  Sección: Colores
        // ════════════════════════════════════════
        $wp_customize->add_section( 'sxhc_colors', array(
            'title' => 'Colores',
            'panel' => 'sxhc_panel',
        ) );

        $color_fields = array(
            'color_brand'       => 'Color principal (brand)',
            'color_brand_light' => 'Color claro (hover / fondos sutiles)',
            'color_text'        => 'Color de texto',
            'color_muted'       => 'Color secundario (descripciones)',
            'color_border'      => 'Color de bordes',
        );
        foreach ( $color_fields as $key => $label ) {
            $wp_customize->add_setting( 'sxhc_' . $key, array(
                'default'           => self::DEFAULTS[ $key ],
                'sanitize_callback' => 'sanitize_hex_color',
                'transport'         => 'postMessage',
            ) );
            $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'sxhc_' . $key, array(
                'label'   => $label,
                'section' => 'sxhc_colors',
            ) ) );
        }

        // ════════════════════════════════════════
        //  Sección: Fondos
        // ════════════════════════════════════════
        $wp_customize->add_section( 'sxhc_backgrounds', array(
            'title' => 'Fondos',
            'panel' => 'sxhc_panel',
        ) );

        $bg_fields = array(
            'bg_page'    => 'Fondo general de la página',
            'bg_header'  => 'Fondo del header',
            'bg_hero'    => 'Fondo del hero (buscador)',
            'bg_sidebar' => 'Fondo del sidebar',
        );
        foreach ( $bg_fields as $key => $label ) {
            $wp_customize->add_setting( 'sxhc_' . $key, array(
                'default'           => self::DEFAULTS[ $key ],
                'sanitize_callback' => 'sanitize_hex_color',
                'transport'         => 'postMessage',
            ) );
            $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'sxhc_' . $key, array(
                'label'   => $label,
                'section' => 'sxhc_backgrounds',
            ) ) );
        }

        // ════════════════════════════════════════
        //  Sección: Tipografía y Cards
        // ════════════════════════════════════════
        $wp_customize->add_section( 'sxhc_typography', array(
            'title' => 'Tipografía y Cards',
            'panel' => 'sxhc_panel',
        ) );

        $wp_customize->add_setting( 'sxhc_font_family', array(
            'default'           => 'system-ui, -apple-system, sans-serif',
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'postMessage',
        ) );
        $wp_customize->add_control( 'sxhc_font_family', array(
            'label'   => 'Fuente',
            'section' => 'sxhc_typography',
            'type'    => 'select',
            'choices' => array(
                'system-ui, -apple-system, sans-serif'    => 'System UI (por defecto)',
                'Inter, sans-serif'                       => 'Inter',
                'DM Sans, sans-serif'                     => 'DM Sans',
                "'Helvetica Neue', Helvetica, sans-serif" => 'Helvetica Neue',
                'Georgia, serif'                          => 'Georgia',
            ),
        ) );

        $wp_customize->add_setting( 'sxhc_card_radius', array(
            'default'           => 16,
            'sanitize_callback' => 'absint',
            'transport'         => 'postMessage',
        ) );
        $wp_customize->add_control( 'sxhc_card_radius', array(
            'label'       => 'Radio de cards (px)',
            'section'     => 'sxhc_typography',
            'type'        => 'range',
            'input_attrs' => array( 'min' => 0, 'max' => 32, 'step' => 1 ),
        ) );
    }

    // ── CSS variables en el frontend ──────────────────────────────────────

    public static function inject_css_vars() {
        $s        = self::get();
        $logo_url = $s['logo_id'] ? wp_get_attachment_image_url( $s['logo_id'], 'full' ) : '';
        ?>
<style id="sxhc-css-vars">
:root {
    --brand:       <?php echo esc_attr( $s['color_brand'] ); ?>;
    --brand-light: <?php echo esc_attr( $s['color_brand_light'] ); ?>;
    --text:        <?php echo esc_attr( $s['color_text'] ); ?>;
    --muted:       <?php echo esc_attr( $s['color_muted'] ); ?>;
    --border:      <?php echo esc_attr( $s['color_border'] ); ?>;
    --bg-page:     <?php echo esc_attr( $s['bg_page'] ); ?>;
    --bg-header:   <?php echo esc_attr( $s['bg_header'] ); ?>;
    --bg-hero:     <?php echo esc_attr( $s['bg_hero'] ); ?>;
    --bg-sidebar:  <?php echo esc_attr( $s['bg_sidebar'] ); ?>;
    --card-radius: <?php echo absint( $s['card_radius'] ); ?>px;
    --font:        <?php echo esc_attr( $s['font_family'] ); ?>;
}
* { font-family: var(--font); }
body {
    background-color: var(--bg-page);
    background-image: radial-gradient(circle, rgba(0,0,0,0.07) 1px, transparent 1px);
    background-size: 22px 22px;
    color: var(--text);
}
/* El hero puede tener su propio fondo; mantenemos el patrón también */
.sxhc-hero-bg {
    background-color: var(--bg-hero);
    background-image: radial-gradient(circle, rgba(0,0,0,0.06) 1px, transparent 1px);
    background-size: 22px 22px;
}
</style>
<script>
tailwind.config = {
    theme: { extend: { colors: {
        brand: {
            DEFAULT: '<?php echo esc_js( $s['color_brand'] ); ?>',
            light:   '<?php echo esc_js( $s['color_brand_light'] ); ?>'
        }
    } } }
};
</script>
        <?php
    }

    // ── Live preview vía postMessage ──────────────────────────────────────

    public static function preview_js() {
        wp_add_inline_script( 'customize-preview', "
        (function(\$) {
            var cssVars = {
                'sxhc_color_brand':       '--brand',
                'sxhc_color_brand_light': '--brand-light',
                'sxhc_color_text':        '--text',
                'sxhc_color_muted':       '--muted',
                'sxhc_color_border':      '--border',
                'sxhc_bg_page':           '--bg-page',
                'sxhc_bg_header':         '--bg-header',
                'sxhc_bg_hero':           '--bg-hero',
                'sxhc_bg_sidebar':        '--bg-sidebar',
            };

            // Actualizar CSS vars en tiempo real
            Object.keys(cssVars).forEach(function(setting) {
                wp.customize(setting, function(value) {
                    value.bind(function(newVal) {
                        document.documentElement.style.setProperty(cssVars[setting], newVal);
                    });
                });
            });

            // Radio de cards
            wp.customize('sxhc_card_radius', function(value) {
                value.bind(function(v) {
                    document.documentElement.style.setProperty('--card-radius', v + 'px');
                });
            });

            // Ancho del logo
            wp.customize('sxhc_logo_width', function(value) {
                value.bind(function(v) {
                    \$('.sxhc-logo-img').css('width', v + 'px');
                });
            });
        })(jQuery);
        " );
    }
}
