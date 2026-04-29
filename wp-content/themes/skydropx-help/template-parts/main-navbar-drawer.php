<?php
/**
 * Drawer mobile del navbar.
 * Se imprime fuera del <header> para que el overlay cubra toda la pantalla.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$nav = sxhc_get_nav_data();
?>
<div id="sxhc-mobile-drawer" class="sxhc-drawer lg:hidden" aria-hidden="true">
    <div class="sxhc-drawer__backdrop" data-sxhc-drawer-close></div>

    <aside class="sxhc-drawer__panel" role="dialog" aria-modal="true" aria-label="Menú de navegación">
        <div class="sxhc-drawer__header">
            <span class="text-sm font-semibold text-gray-900">Menú</span>
            <button type="button"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-gray-700 hover:bg-gray-100"
                    aria-label="Cerrar menú"
                    data-sxhc-drawer-close>
                <?php echo sxhc_nav_icon( 'close' ); ?>
            </button>
        </div>

        <div class="sxhc-drawer__body">
            <ul class="py-2">
                <li>
                    <a href="<?php echo esc_url( $nav['principal'][0]['url'] ); ?>" class="sxhc-drawer-link">
                        <?php echo esc_html( $nav['principal'][0]['nombre'] ); ?>
                    </a>
                </li>

                <li class="sxhc-drawer-accordion" data-sxhc-accordion>
                    <button type="button" class="sxhc-drawer-link sxhc-drawer-accordion__trigger" aria-expanded="false">
                        <span>Soluciones</span>
                        <?php echo sxhc_nav_icon( 'chevron' ); ?>
                    </button>
                    <div class="sxhc-drawer-accordion__content">
                        <?php foreach ( $nav['soluciones'] as $item ) : ?>
                            <a href="<?php echo esc_url( $item['url'] ); ?>" class="sxhc-drawer-sublink">
                                <span class="text-brand"><?php echo sxhc_nav_icon( $item['icon'] ); ?></span>
                                <span><?php echo esc_html( $item['nombre'] ); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </li>

                <li>
                    <a href="<?php echo esc_url( $nav['principal'][1]['url'] ); ?>" class="sxhc-drawer-link">
                        <?php echo esc_html( $nav['principal'][1]['nombre'] ); ?>
                    </a>
                </li>

                <li class="sxhc-drawer-accordion" data-sxhc-accordion>
                    <button type="button" class="sxhc-drawer-link sxhc-drawer-accordion__trigger" aria-expanded="false">
                        <span>Recursos</span>
                        <?php echo sxhc_nav_icon( 'chevron' ); ?>
                    </button>
                    <div class="sxhc-drawer-accordion__content">
                        <?php foreach ( $nav['recursos'] as $item ) : ?>
                            <a href="<?php echo esc_url( $item['url'] ); ?>" class="sxhc-drawer-sublink">
                                <span class="text-brand"><?php echo sxhc_nav_icon( $item['icon'] ); ?></span>
                                <span><?php echo esc_html( $item['nombre'] ); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </li>

                <li class="sxhc-drawer-accordion" data-sxhc-accordion>
                    <button type="button" class="sxhc-drawer-link sxhc-drawer-accordion__trigger" aria-expanded="false">
                        <span>Contacto</span>
                        <?php echo sxhc_nav_icon( 'chevron' ); ?>
                    </button>
                    <div class="sxhc-drawer-accordion__content">
                        <?php foreach ( $nav['contacto'] as $item ) : ?>
                            <a href="<?php echo esc_url( $item['url'] ); ?>" class="sxhc-drawer-sublink">
                                <span class="text-brand"><?php echo sxhc_nav_icon( $item['icon'] ); ?></span>
                                <span><?php echo esc_html( $item['nombre'] ); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </li>
            </ul>
        </div>

        <div class="sxhc-drawer__footer">
            <a href="<?php echo esc_url( $nav['auth']['login']['url'] ); ?>"
               class="block w-full text-center py-2.5 rounded-lg border border-brand text-brand text-sm font-semibold no-underline">
                <?php echo esc_html( $nav['auth']['login']['nombre'] ); ?>
            </a>
            <a href="<?php echo esc_url( $nav['auth']['signup']['url'] ); ?>"
               class="block w-full text-center py-2.5 rounded-lg bg-brand hover:bg-brand-hover text-white text-sm font-semibold no-underline transition-colors">
                <?php echo esc_html( $nav['auth']['signup']['nombre'] ); ?>
            </a>
        </div>
    </aside>
</div>
