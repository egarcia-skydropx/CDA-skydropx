<?php
/**
 * Navbar principal — lado izquierdo (links + dropdowns).
 * Visible solo en desktop (lg+). El menú mobile vive en main-navbar-drawer.php.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$nav = sxhc_get_nav_data();
?>
<nav class="sxhc-nav hidden lg:flex items-center gap-1" aria-label="Navegación principal">

    <a href="<?php echo esc_url( $nav['principal'][0]['url'] ); ?>" class="sxhc-nav-link">
        <?php echo esc_html( $nav['principal'][0]['nombre'] ); ?>
    </a>

    <div class="sxhc-nav-item" data-sxhc-dropdown>
        <button type="button" class="sxhc-nav-link sxhc-nav-trigger"
                aria-expanded="false" aria-haspopup="true">
            <span>Soluciones</span>
            <?php echo sxhc_nav_icon( 'chevron' ); ?>
        </button>
        <div class="sxhc-nav-panel sxhc-nav-panel--wide" role="menu" aria-label="Soluciones">
            <?php sxhc_render_dropdown_panel( 'Soluciones', $nav['soluciones'], 'two' ); ?>
        </div>
    </div>

    <a href="<?php echo esc_url( $nav['principal'][1]['url'] ); ?>" class="sxhc-nav-link">
        <?php echo esc_html( $nav['principal'][1]['nombre'] ); ?>
    </a>

    <div class="sxhc-nav-item" data-sxhc-dropdown>
        <button type="button" class="sxhc-nav-link sxhc-nav-trigger"
                aria-expanded="false" aria-haspopup="true">
            <span>Recursos</span>
            <?php echo sxhc_nav_icon( 'chevron' ); ?>
        </button>
        <div class="sxhc-nav-panel sxhc-nav-panel--wide" role="menu" aria-label="Recursos">
            <?php sxhc_render_dropdown_panel( 'Recursos', $nav['recursos'], 'two' ); ?>
        </div>
    </div>

    <div class="sxhc-nav-item" data-sxhc-dropdown>
        <button type="button" class="sxhc-nav-link sxhc-nav-trigger"
                aria-expanded="false" aria-haspopup="true">
            <span>Contacto</span>
            <?php echo sxhc_nav_icon( 'chevron' ); ?>
        </button>
        <div class="sxhc-nav-panel sxhc-nav-panel--narrow" role="menu" aria-label="Contacto">
            <?php sxhc_render_dropdown_panel( 'Contacto', $nav['contacto'], 'one' ); ?>
        </div>
    </div>

</nav>
