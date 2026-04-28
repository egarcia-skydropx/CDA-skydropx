<?php
get_header();

$post_id    = get_the_ID();
$crumbs     = sxhc_get_post_breadcrumb( $post_id );
$active_ids = array();

if ( ! empty( $crumbs ) ) {
    $deepest    = end( $crumbs );
    $active_ids = array_merge(
        array( $deepest->term_id ),
        get_ancestors( $deepest->term_id, 'help_category', 'taxonomy' )
    );
}

// Todas las categorías asignadas al artículo
$all_terms = wp_get_object_terms( $post_id, 'help_category', array( 'fields' => 'all' ) );
$all_terms = ( ! empty( $all_terms ) && ! is_wp_error( $all_terms ) ) ? $all_terms : array();

// Categoría activa en este contexto
$context_term_id = class_exists( 'SXHC_Multi_Category' )
    ? SXHC_Multi_Category::get_context_term_id( $post_id )
    : 0;
?>

<div class="max-w-7xl mx-auto px-6 py-8 flex gap-10">

    <?php get_template_part( 'template-parts/sidebar', null, array( 'active_ids' => $active_ids ) ); ?>

    <main class="flex-1 min-w-0">

        <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>

            <?php get_template_part( 'template-parts/breadcrumb', null, array( 'crumbs' => $crumbs ) ); ?>

            <article>
                <h1 class="text-2xl font-bold text-gray-900 mb-4"><?php the_title(); ?></h1>

                <!-- Categorías asignadas (badges) -->
                <?php if ( count( $all_terms ) > 0 ) : ?>
                <div class="flex flex-wrap gap-2 mb-6">
                    <?php foreach ( $all_terms as $t ) :
                        $is_active = ( $t->term_id === $context_term_id );
                        $cat_url   = add_query_arg( 'cat', $t->term_id, get_permalink() );
                        $path      = class_exists( 'SXHC_Multi_Category' )
                            ? SXHC_Multi_Category::get_term_path( $t )
                            : $t->name;
                    ?>
                        <a href="<?php echo esc_url( get_term_link( $t ) ); ?>"
                           title="<?php echo esc_attr( $path ); ?>"
                           class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium
                                  transition-colors
                                  <?php echo $is_active
                                      ? 'bg-brand text-white'
                                      : 'bg-brand-light text-brand hover:bg-brand hover:text-white'; ?>">
                            <?php echo esc_html( $t->name ); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Contenido del artículo -->
                <div class="prose prose-gray max-w-none
                            prose-headings:font-semibold prose-headings:text-gray-900
                            prose-a:text-brand prose-a:no-underline hover:prose-a:underline
                            prose-img:rounded-lg prose-img:border prose-img:border-gray-100
                            prose-code:text-brand prose-code:bg-brand-light prose-code:px-1 prose-code:rounded">
                    <?php the_content(); ?>
                </div>

                <!-- Este artículo también está en... -->
                <?php if ( count( $all_terms ) > 1 ) : ?>
                <div class="mt-10 pt-6 border-t border-gray-100">
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-3">
                        Este artículo también está en
                    </p>
                    <ul class="space-y-1.5">
                        <?php foreach ( $all_terms as $t ) :
                            if ( $t->term_id === $context_term_id ) continue;
                            $path = class_exists( 'SXHC_Multi_Category' )
                                ? SXHC_Multi_Category::get_term_path( $t )
                                : $t->name;
                            $url  = add_query_arg( 'cat', $t->term_id, get_permalink() );
                        ?>
                            <li>
                                <a href="<?php echo esc_url( $url ); ?>"
                                   class="text-sm text-brand hover:underline flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 shrink-0 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/>
                                    </svg>
                                    <?php echo esc_html( $path ); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Artículos relacionados -->
                <?php if ( ! empty( $active_ids ) ) :
                    $related = new WP_Query( array(
                        'post_type'      => 'help_article',
                        'post_status'    => 'publish',
                        'posts_per_page' => 4,
                        'post__not_in'   => array( $post_id ),
                        'tax_query'      => array( array(
                            'taxonomy'         => 'help_category',
                            'field'            => 'term_id',
                            'terms'            => $active_ids,
                            'include_children' => false,
                        ) ),
                    ) );
                    if ( $related->have_posts() ) : ?>
                        <div class="mt-10 pt-6 border-t border-gray-100">
                            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-3">
                                Artículos relacionados
                            </p>
                            <ul class="space-y-1.5">
                                <?php while ( $related->have_posts() ) : $related->the_post(); ?>
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
