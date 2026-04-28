<?php get_header(); ?>

<div class="max-w-3xl mx-auto px-6 py-12">

    <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Resultados de búsqueda</p>
    <h1 class="text-2xl font-bold text-gray-900 mb-8">
        "<?php echo esc_html( get_search_query() ); ?>"
    </h1>

    <?php if ( have_posts() ) : ?>
        <ul class="divide-y divide-gray-100 bg-white border border-gray-200 rounded-2xl overflow-hidden">
            <?php while ( have_posts() ) : the_post(); ?>
            <li>
                <a href="<?php the_permalink(); ?>"
                   class="flex items-start justify-between px-5 py-4 hover:bg-brand-light group transition-colors">
                    <div>
                        <p class="text-sm font-medium text-gray-900 group-hover:text-brand transition-colors">
                            <?php the_title(); ?>
                        </p>
                        <?php
                        $crumbs = sxhc_get_post_breadcrumb( get_the_ID() );
                        if ( $crumbs ) :
                            $names = array_map( function( $t ) { return $t->name; }, $crumbs );
                        ?>
                        <p class="text-xs text-gray-400 mt-0.5"><?php echo esc_html( implode( ' / ', $names ) ); ?></p>
                        <?php endif; ?>
                    </div>
                    <svg class="w-4 h-4 text-gray-300 group-hover:text-brand shrink-0 ml-4 mt-0.5 transition-colors"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </li>
            <?php endwhile; ?>
        </ul>

        <div class="mt-6">
            <?php the_posts_pagination( array( 'mid_size' => 2 ) ); ?>
        </div>

    <?php else : ?>
        <div class="text-center py-16 text-gray-400">
            <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
            </svg>
            <p class="text-sm">No encontramos artículos para esta búsqueda.</p>
            <a href="<?php echo esc_url( home_url() ); ?>"
               class="mt-4 inline-block text-sm text-brand hover:underline">
                Volver al inicio
            </a>
        </div>
    <?php endif; ?>

</div>

<?php get_footer(); ?>
