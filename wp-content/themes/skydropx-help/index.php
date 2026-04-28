<?php get_header(); ?>

<?php
$appearance = class_exists( 'SXHC_Appearance' ) ? SXHC_Appearance::get() : array();
$hero_title    = ! empty( $appearance['hero_title'] )    ? $appearance['hero_title']    : 'Centro de ayuda';
$hero_subtitle = ! empty( $appearance['hero_subtitle'] ) ? $appearance['hero_subtitle'] : '¿En qué podemos ayudarte?';
?>

<!-- Hero + Search ─────────────────────────────────────────────────────────── -->
<section style="background:var(--bg-hero)" class="border-b border-gray-100 py-16 px-6 text-center">
    <h1 class="sxhc-hero-title text-3xl font-bold text-gray-900 mb-2"><?php echo esc_html( $hero_title ); ?></h1>
    <p class="sxhc-hero-subtitle text-gray-400 mb-8"><?php echo esc_html( $hero_subtitle ); ?></p>
    <?php get_template_part( 'template-parts/search-input', null, array( 'variant' => 'hero' ) ); ?>
</section>

<!-- Categorías raíz ────────────────────────────────────────────────────────── -->
<section class="max-w-5xl mx-auto px-6 py-12">
    <?php
    // Usar el orden personalizado si existe
    $categories = class_exists( 'SXHC_Category_Order' )
        ? SXHC_Category_Order::get_ordered_terms( 0 )
        : get_terms( array( 'taxonomy' => 'help_category', 'parent' => 0, 'hide_empty' => false ) );
    ?>
    <?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ( $categories as $cat ) :
            $child_count = count( get_terms( array(
                'taxonomy'   => 'help_category',
                'parent'     => $cat->term_id,
                'fields'     => 'ids',
                'hide_empty' => false,
            ) ) );
        ?>
        <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>"
           class="group flex flex-col bg-white border border-gray-200 rounded-2xl p-6
                  hover:border-brand hover:shadow-md transition-all duration-200">
            <h2 class="font-semibold text-gray-900 group-hover:text-brand transition-colors mb-1">
                <?php echo esc_html( $cat->name ); ?>
            </h2>
            <p class="text-xs text-gray-400 mt-auto pt-3">
                <?php echo $child_count; ?> subcategoría<?php echo $child_count !== 1 ? 's' : ''; ?>
            </p>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<?php get_footer(); ?>
