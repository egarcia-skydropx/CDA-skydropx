<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class( 'antialiased' ); ?>>
<?php wp_body_open(); ?>

<?php
$logo_id      = class_exists( 'SXHC_Appearance' ) ? (int) get_theme_mod( 'sxhc_logo_id', 0 ) : 0;
$logo_width   = class_exists( 'SXHC_Appearance' ) ? (int) get_theme_mod( 'sxhc_logo_width', 140 ) : 140;
$site_name    = class_exists( 'SXHC_Appearance' ) ? get_theme_mod( 'sxhc_site_name', '' ) : '';
$logo_url     = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';
$default_logo = get_template_directory_uri() . '/assets/images/logo-default.svg';

// Prioridad: logo personalizado → logo por defecto → texto
if ( ! $logo_url ) {
    $logo_url   = $default_logo;
    $logo_width = 140; // ancho fijo para el logo por defecto
}
?>

<header style="background:var(--bg-header);" class="border-b border-gray-200 sticky top-0 z-30">
    <div class="w-full px-6 lg:px-10 h-16 flex items-center gap-6">

        <?php // ── Bloque izquierdo: logo + nav ─────────────────────────── ?>
        <div class="flex items-center gap-8 min-w-0">
            <a href="<?php echo esc_url( home_url() ); ?>" class="flex items-center gap-2 shrink-0 no-underline">
                <img src="<?php echo esc_url( $logo_url ); ?>"
                     alt="<?php echo esc_attr( $site_name ?: 'Skydropx' ); ?>"
                     class="sxhc-logo-img"
                     style="width:<?php echo absint( $logo_width ); ?>px; max-height:36px; object-fit:contain; display:block;">
                <span class="text-xs text-gray-400 font-normal hidden sm:inline">Centro de ayuda</span>
            </a>

            <?php get_template_part( 'template-parts/main-navbar' ); ?>
        </div>

        <?php // ── Espaciador flexible ─────────────────────────────────── ?>
        <div class="flex-1"></div>

        <?php // ── Bloque derecho: auth/hamburger ───────────────────────── ?>
        <?php // El buscador se movió al sidebar (template-parts/sidebar.php) ?>
        <div class="flex items-center gap-4 shrink-0">
            <?php get_template_part( 'template-parts/main-navbar-auth' ); ?>
        </div>

    </div>
</header>

<?php do_action( 'sxhc_after_header' ); ?>
<?php get_template_part( 'template-parts/main-navbar-drawer' ); ?>
