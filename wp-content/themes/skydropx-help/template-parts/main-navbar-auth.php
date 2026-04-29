<?php
/**
 * Navbar principal — lado derecho (Iniciar sesión + Crear cuenta + hamburger).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$nav = sxhc_get_nav_data();
?>

<?php // Auth desktop ?>
<div class="hidden lg:flex items-center gap-3 shrink-0">
    <a href="<?php echo esc_url( $nav['auth']['login']['url'] ); ?>"
       class="text-sm font-medium text-brand hover:text-brand-hover whitespace-nowrap no-underline">
        <?php echo esc_html( $nav['auth']['login']['nombre'] ); ?>
    </a>
    <a href="<?php echo esc_url( $nav['auth']['signup']['url'] ); ?>"
       class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-brand hover:bg-brand-hover text-white text-sm font-semibold whitespace-nowrap no-underline transition-colors">
        <?php echo esc_html( $nav['auth']['signup']['nombre'] ); ?>
    </a>
</div>

<?php // Hamburger mobile ?>
<button type="button"
        id="sxhc-menu-toggle"
        class="lg:hidden inline-flex items-center justify-center w-10 h-10 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors"
        aria-label="Abrir menú"
        aria-expanded="false"
        aria-controls="sxhc-mobile-drawer">
    <?php echo sxhc_nav_icon( 'menu' ); ?>
</button>
