<?php
/**
 * Sidebar de categorías.
 * Espera $args['active_ids'] — array de term_ids activos (término actual + ancestros).
 */
$active_ids = isset( $args['active_ids'] ) ? $args['active_ids'] : array();
?>
<aside class="w-64 shrink-0">
    <div class="sticky top-20">
        <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-3 px-2">
            Categorías
        </p>
        <?php sxhc_render_sidebar( 0, $active_ids ); ?>
    </div>
</aside>
