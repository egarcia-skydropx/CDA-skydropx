<?php
/**
 * Breadcrumb.
 * Espera $args['crumbs'] — array de WP_Term, o strings con 'label' y 'url'.
 */
$crumbs = isset( $args['crumbs'] ) ? $args['crumbs'] : array();
if ( empty( $crumbs ) ) return;
?>
<nav aria-label="Breadcrumb" class="flex items-center gap-1.5 text-sm text-gray-400 mb-6 flex-wrap">
    <a href="<?php echo esc_url( home_url() ); ?>" class="hover:text-brand transition-colors">Inicio</a>

    <?php foreach ( $crumbs as $i => $crumb ) :
        $is_last = ( $i === count( $crumbs ) - 1 );
        $label   = is_object( $crumb ) ? $crumb->name : $crumb['label'];
        $url     = is_object( $crumb ) ? get_term_link( $crumb ) : $crumb['url'];
    ?>
        <span class="text-gray-300">/</span>
        <?php if ( $is_last ) : ?>
            <span class="text-gray-700 font-medium"><?php echo esc_html( $label ); ?></span>
        <?php else : ?>
            <a href="<?php echo esc_url( $url ); ?>" class="hover:text-brand transition-colors">
                <?php echo esc_html( $label ); ?>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
