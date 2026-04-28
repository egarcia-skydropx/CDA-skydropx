<?php
get_header();

$crumbs     = sxhc_get_post_breadcrumb( get_the_ID() );
$active_ids = array();
if ( ! empty( $crumbs ) ) {
    $deepest    = end( $crumbs );
    $active_ids = array_merge(
        array( $deepest->term_id ),
        get_ancestors( $deepest->term_id, 'help_category', 'taxonomy' )
    );
}
?>

<div class="max-w-7xl mx-auto px-6 py-8 flex gap-10">

    <?php get_template_part( 'template-parts/sidebar', null, array( 'active_ids' => $active_ids ) ); ?>

    <main class="flex-1 min-w-0">

        <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>

            <?php get_template_part( 'template-parts/breadcrumb', null, array( 'crumbs' => $crumbs ) ); ?>

            <article>
                <h1 class="text-2xl font-bold text-gray-900 mb-6"><?php the_title(); ?></h1>

                <div class="prose prose-gray max-w-none
                            prose-headings:font-semibold prose-headings:text-gray-900
                            prose-a:text-brand prose-a:no-underline hover:prose-a:underline
                            prose-img:rounded-lg prose-img:border prose-img:border-gray-100
                            prose-code:text-brand prose-code:bg-brand-light prose-code:px-1 prose-code:rounded">
                    <?php the_content(); ?>
                </div>

                <!-- Artículos relacionados (meta guardada en importación) -->
                <?php
                $related_raw = get_post_meta( get_the_ID(), '_sxhc_tags', true );
                if ( $related_raw ) :
                    $related_articles = new WP_Query( array(
                        'post_type'      => 'help_article',
                        'post_status'    => 'publish',
                        'posts_per_page' => 4,
                        'post__not_in'   => array( get_the_ID() ),
                        'tax_query'      => array( array(
                            'taxonomy' => 'help_category',
                            'field'    => 'term_id',
                            'terms'    => $active_ids,
                        ) ),
                    ) );
                    if ( $related_articles->have_posts() ) : ?>
                        <div class="mt-12 pt-8 border-t border-gray-100">
                            <h2 class="text-sm font-semibold uppercase tracking-widest text-gray-400 mb-4">
                                Artículos relacionados
                            </h2>
                            <ul class="space-y-2">
                                <?php while ( $related_articles->have_posts() ) : $related_articles->the_post(); ?>
                                <li>
                                    <a href="<?php the_permalink(); ?>"
                                       class="text-sm text-brand hover:underline flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                        <?php the_title(); ?>
                                    </a>
                                </li>
                                <?php endwhile; wp_reset_postdata(); ?>
                            </ul>
                        </div>
                    <?php endif;
                endif; ?>

            </article>

        <?php endwhile; endif; ?>
    </main>

</div>

<?php get_footer();
