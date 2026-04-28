<?php
get_header();

$term       = get_queried_object();
$crumbs     = sxhc_get_term_breadcrumb( $term );
$active_ids = array_merge(
    array( $term->term_id ),
    get_ancestors( $term->term_id, 'help_category', 'taxonomy' )
);

// ¿Tiene subcategorías? (respeta el orden personalizado)
$children = class_exists( 'SXHC_Category_Order' )
    ? SXHC_Category_Order::get_ordered_terms( $term->term_id )
    : get_terms( array( 'taxonomy' => 'help_category', 'parent' => $term->term_id, 'hide_empty' => false ) );
$has_children = ! empty( $children ) && ! is_wp_error( $children );
?>

<div class="max-w-7xl mx-auto px-6 py-8 flex gap-10">

    <?php get_template_part( 'template-parts/sidebar', null, array( 'active_ids' => $active_ids ) ); ?>

    <main class="flex-1 min-w-0">

        <?php get_template_part( 'template-parts/breadcrumb', null, array( 'crumbs' => $crumbs ) ); ?>

        <h1 class="text-2xl font-bold text-gray-900 mb-1"><?php echo esc_html( $term->name ); ?></h1>
        <?php if ( $term->description ) : ?>
            <p class="text-gray-500 mb-8"><?php echo esc_html( $term->description ); ?></p>
        <?php else : ?>
            <div class="mb-8"></div>
        <?php endif; ?>

        <?php if ( $has_children ) : ?>
            <!-- Mostrar subcategorías como cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ( $children as $child ) :
                    $grandchildren = get_terms( array(
                        'taxonomy'   => 'help_category',
                        'parent'     => $child->term_id,
                        'hide_empty' => false,
                        'fields'     => 'ids',
                    ) );
                    $sub_count     = count( $grandchildren );
                    // Contar artículos en todo el árbol del hijo (no solo directos)
                    $article_count = sxhc_count_term_posts_recursive( $child->term_id );
                ?>
                <a href="<?php echo esc_url( get_term_link( $child ) ); ?>"
                   class="group block bg-white border border-gray-200 rounded-xl p-5 hover:border-brand hover:shadow-sm transition-all">
                    <h2 class="font-semibold text-gray-900 group-hover:text-brand transition-colors mb-1">
                        <?php echo esc_html( $child->name ); ?>
                    </h2>
                    <p class="text-xs text-gray-400">
                        <?php if ( $sub_count > 0 ) : ?>
                            <?php echo $sub_count; ?> subcategorías · <?php echo $article_count; ?> artículos
                        <?php else : ?>
                            <?php echo $article_count; ?> artículo<?php echo $article_count !== 1 ? 's' : ''; ?>
                        <?php endif; ?>
                    </p>
                </a>
                <?php endforeach; ?>
            </div>

        <?php else : ?>
            <!-- Mostrar artículos de esta categoría -->
            <?php
            $paged = get_query_var( 'paged' ) ?: 1;
            $query = new WP_Query( array(
                'post_type'      => 'help_article',
                'post_status'    => 'publish',
                'posts_per_page' => 20,
                'paged'          => $paged,
                'tax_query'      => array( array(
                    'taxonomy' => 'help_category',
                    'field'    => 'term_id',
                    'terms'    => $term->term_id,
                ) ),
            ) );
            ?>

            <?php if ( $query->have_posts() ) : ?>
                <ul class="divide-y divide-gray-100 bg-white border border-gray-200 rounded-xl overflow-hidden">
                    <?php while ( $query->have_posts() ) : $query->the_post();
                        // Añadir ?cat para breadcrumb contextual
                        $article_url = add_query_arg( 'cat', $term->term_id, get_permalink() );
                    ?>
                    <li>
                        <a href="<?php echo esc_url( $article_url ); ?>"
                           class="flex items-center justify-between px-5 py-4 hover:bg-brand-light group transition-colors">
                            <span class="text-sm font-medium text-gray-800 group-hover:text-brand transition-colors">
                                <?php the_title(); ?>
                            </span>
                            <svg class="w-4 h-4 text-gray-300 group-hover:text-brand shrink-0 ml-3 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </li>
                    <?php endwhile; wp_reset_postdata(); ?>
                </ul>

                <!-- Paginación -->
                <?php if ( $query->max_num_pages > 1 ) : ?>
                <div class="mt-6 flex justify-center gap-2">
                    <?php echo paginate_links( array(
                        'total'   => $query->max_num_pages,
                        'current' => $paged,
                        'type'    => 'list',
                    ) ); ?>
                </div>
                <?php endif; ?>

            <?php else : ?>
                <p class="text-gray-400 text-sm">No hay artículos en esta categoría aún.</p>
            <?php endif; ?>

        <?php endif; ?>

    </main>
</div>

<?php get_footer();
