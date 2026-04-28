<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class( 'antialiased' ); ?>>

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
    <div class="max-w-7xl mx-auto px-6 h-14 flex items-center justify-between gap-6">

        <a href="<?php echo esc_url( home_url() ); ?>" class="flex items-center gap-2 shrink-0 no-underline">
            <img src="<?php echo esc_url( $logo_url ); ?>"
                 alt="<?php echo esc_attr( $site_name ?: 'Skydropx' ); ?>"
                 class="sxhc-logo-img"
                 style="width:<?php echo absint( $logo_width ); ?>px; max-height:36px; object-fit:contain; display:block;">
            <span class="text-xs text-gray-400 font-normal hidden sm:inline">Centro de ayuda</span>
        </a>

        <?php if ( ! is_front_page() ) : ?>
            <?php get_template_part( 'template-parts/search-input', null, array( 'variant' => 'compact' ) ); ?>
        <?php endif; ?>

    </div>
</header>
