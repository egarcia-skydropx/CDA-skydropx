<?php
/**
 * Datos y helpers compartidos del navbar principal.
 *
 * Centralizados aquí para que los template-parts (main-navbar,
 * main-navbar-auth y main-navbar-drawer) consuman la misma fuente.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Devuelve los datos de navegación.
 */
function sxhc_get_nav_data() {
    static $cache = null;
    if ( $cache !== null ) {
        return $cache;
    }

    $cache = array(
        'principal' => array(
            array( 'nombre' => 'Qué hacemos',   'url' => 'https://www.skydropx.com.co/que-hacemos/' ),
            array( 'nombre' => 'Quiénes somos', 'url' => 'https://www.skydropx.com.co/quienes-somos/' ),
        ),
        'auth' => array(
            'login'  => array( 'nombre' => 'Iniciar sesión', 'url' => 'https://app.skydropx.com/users/sign_in' ),
            'signup' => array( 'nombre' => 'Crear cuenta',   'url' => 'https://app.skydropx.com/users/sign_up' ),
        ),
        'soluciones' => array(
            array(
                'nombre'      => 'Creación de envíos',
                'url'         => 'https://www.skydropx.com.co/soluciones/creacion-de-envios/',
                'descripcion' => 'Gestiona tus envíos y organiza tu logística sin complicaciones, todo desde una misma plataforma.',
                'icon'        => 'package',
            ),
            array(
                'nombre'      => 'Cotizador',
                'url'         => 'https://www.skydropx.com.co/soluciones/cotizador/',
                'descripcion' => 'Descubre las tarifas más competitivas de las mejores paqueterías con nuestro cotizador integrado.',
                'icon'        => 'calculator',
            ),
            array(
                'nombre'      => 'Rastreo',
                'url'         => 'https://www.skydropx.com.co/soluciones/rastreo/',
                'descripcion' => 'Conoce y comparte el estatus de tus envíos con rastreo.',
                'icon'        => 'pin',
            ),
            array(
                'nombre'      => 'Conexiones',
                'url'         => 'https://www.skydropx.com.co/soluciones/conexiones/',
                'descripcion' => 'Conecta tus tiendas en línea con Skydropx de manera fácil y rápida.',
                'icon'        => 'puzzle',
            ),
            array(
                'nombre'      => 'API',
                'url'         => '#',
                'descripcion' => 'Automatiza y gestiona las operaciones de envío desde la creación de órdenes y cotización hasta las recolecciones e impresión.',
                'icon'        => 'code',
            ),
            array(
                'nombre'      => 'Fulfillment',
                'url'         => '#',
                'descripcion' => 'Potencia tu negocio simplificando la gestión, procesamiento, embalaje y envío de pedidos.',
                'icon'        => 'box',
            ),
        ),
        'recursos' => array(
            array(
                'nombre'      => 'Centro de ayuda',
                'url'         => 'https://ayuda.skydropx.com/',
                'descripcion' => 'Soporte técnico y dudas frecuentes.',
                'icon'        => 'help',
            ),
            array(
                'nombre'      => 'Blog',
                'url'         => 'https://blog.skydropx.com/',
                'descripcion' => 'Artículos sobre logística y e-commerce.',
                'icon'        => 'doc',
            ),
            array(
                'nombre'      => 'Descargables',
                'url'         => 'https://negocios.skydropx.com/book/ebooks',
                'descripcion' => 'Ebooks y guías para tu negocio.',
                'icon'        => 'download',
            ),
            array(
                'nombre'      => 'Novedades de transportadoras',
                'url'         => 'https://estatus.skydropx.com/?country=2&date=',
                'descripcion' => 'Estatus y noticias de las paqueterías.',
                'icon'        => 'bell',
            ),
        ),
        'contacto' => array(
            array(
                'nombre'      => 'Email',
                'url'         => 'mailto:holacolombia@skydropx.com',
                'descripcion' => 'Escríbenos a holacolombia@skydropx.com',
                'icon'        => 'mail',
            ),
            array(
                'nombre'      => 'WhatsApp',
                'url'         => 'https://api.whatsapp.com/send?phone=573147789488',
                'descripcion' => 'Chatea con nuestro equipo al +57 314 778 9488.',
                'icon'        => 'whatsapp',
            ),
            array(
                'nombre'      => '¿Tienes dudas con tu paquete?',
                'url'         => 'https://rastreo.skydropx.com/',
                'descripcion' => 'Enlace directo al sistema de rastreo de paquetes.',
                'icon'        => 'pin',
                'highlight'   => true,
            ),
        ),
    );

    return $cache;
}

/**
 * Renderiza un ícono SVG inline.
 */
function sxhc_nav_icon( $name ) {
    $base = 'class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"';
    switch ( $name ) {
        case 'package':
            return '<svg ' . $base . '><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>';
        case 'calculator':
            return '<svg ' . $base . '><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="14" x2="8" y2="14"/><line x1="12" y1="14" x2="12" y2="14"/><line x1="16" y1="14" x2="16" y2="14"/><line x1="8" y1="18" x2="8" y2="18"/><line x1="12" y1="18" x2="12" y2="18"/><line x1="16" y1="18" x2="16" y2="18"/></svg>';
        case 'pin':
            return '<svg ' . $base . '><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>';
        case 'puzzle':
            return '<svg ' . $base . '><path d="M19.5 13.5h-1a2 2 0 1 1 0-4h1a1.5 1.5 0 0 0 1.5-1.5V6a2 2 0 0 0-2-2h-2.5a1.5 1.5 0 0 1-1.5-1.5V2H10v.5A1.5 1.5 0 0 1 8.5 4H6a2 2 0 0 0-2 2v2.5A1.5 1.5 0 0 0 5.5 10h.5a2 2 0 1 1 0 4h-.5A1.5 1.5 0 0 0 4 15.5V18a2 2 0 0 0 2 2h2.5A1.5 1.5 0 0 1 10 21.5V22h5v-.5a1.5 1.5 0 0 1 1.5-1.5H19a2 2 0 0 0 2-2v-2.5a1.5 1.5 0 0 0-1.5-2z"/></svg>';
        case 'code':
            return '<svg ' . $base . '><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>';
        case 'box':
            return '<svg ' . $base . '><path d="M21 8H3l1-4h16l1 4z"/><path d="M3 8v11a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V8"/><line x1="10" y1="12" x2="14" y2="12"/></svg>';
        case 'help':
            return '<svg ' . $base . '><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12" y2="17"/></svg>';
        case 'doc':
            return '<svg ' . $base . '><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="14" y2="17"/></svg>';
        case 'download':
            return '<svg ' . $base . '><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>';
        case 'bell':
            return '<svg ' . $base . '><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>';
        case 'mail':
            return '<svg ' . $base . '><path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>';
        case 'whatsapp':
            return '<svg ' . $base . '><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>';
        case 'chevron':
            return '<svg class="sxhc-chev w-3.5 h-3.5 shrink-0 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>';
        case 'menu':
            return '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="20" y2="17"/></svg>';
        case 'close':
            return '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="6" y1="6" x2="18" y2="18"/><line x1="6" y1="18" x2="18" y2="6"/></svg>';
    }
    return '';
}

/**
 * Renderiza el panel de un dropdown (con grid de items + título).
 */
function sxhc_render_dropdown_panel( $title, $items, $cols = 'two' ) {
    $grid_class = $cols === 'two'
        ? 'grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-5'
        : 'flex flex-col gap-2';
    ?>
    <div class="sxhc-nav-panel-inner p-6">
        <p class="text-brand text-sm font-semibold mb-4 pb-3 border-b border-gray-100">
            <?php echo esc_html( $title ); ?>
        </p>
        <div class="<?php echo esc_attr( $grid_class ); ?>">
            <?php foreach ( $items as $item ) :
                $icon        = isset( $item['icon'] ) ? $item['icon'] : '';
                $descripcion = isset( $item['descripcion'] ) ? $item['descripcion'] : '';
                $highlight   = ! empty( $item['highlight'] );
                $card_class  = 'sxhc-nav-card group flex gap-3 p-2 -m-2 rounded-lg transition-colors hover:bg-brand-light';
                if ( $highlight ) {
                    $card_class .= ' bg-brand-light/40';
                }
            ?>
                <a href="<?php echo esc_url( $item['url'] ); ?>"
                   class="<?php echo esc_attr( $card_class ); ?>">
                    <span class="text-brand mt-0.5"><?php echo sxhc_nav_icon( $icon ); ?></span>
                    <span class="flex-1 min-w-0">
                        <span class="block text-sm font-semibold text-gray-900 group-hover:text-brand">
                            <?php echo esc_html( $item['nombre'] ); ?>
                        </span>
                        <?php if ( $descripcion ) : ?>
                            <span class="block text-xs text-gray-500 leading-snug mt-0.5">
                                <?php echo esc_html( $descripcion ); ?>
                            </span>
                        <?php endif; ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}
