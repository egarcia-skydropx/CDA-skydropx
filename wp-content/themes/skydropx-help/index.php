<?php get_header(); ?>


<!-- Lottie library -->
<script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.9.10/dist/dotlottie-wc.js" type="module"></script>

<!-- Hero + Search ─────────────────────────────────────────────────────────── -->
<section style="background:var(--bg-hero)" class="border-b border-gray-100 py-12 px-6 text-center">

    <!-- Lottie animation -->
    <div class="flex justify-center mb-2">
        <dotlottie-wc
            id="sxhc-hero-lottie"
            src="https://lottie.host/cc938e19-2b46-4685-8d74-c3f94b17c039/K9syE8eUJx.lottie"
            style="width:auto; height:250px;"
            loop>
        </dotlottie-wc>
    </div>

    <script type="module">
        const lottie = document.getElementById('sxhc-hero-lottie');
        const input  = document.getElementById('sxhc-search-input');

        if ( lottie && input ) {
            input.addEventListener('focus', () => {
                lottie.dotLottie ? lottie.dotLottie.play() : lottie.play?.();
            });

            input.addEventListener('blur', () => {
                lottie.dotLottie ? lottie.dotLottie.stop() : lottie.stop?.();
            });
        }
    </script>

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
        <?php
        $img_url = class_exists( 'SXHC_Category_Meta' )
            ? SXHC_Category_Meta::get_image_url( $cat->term_id, 'medium' )
            : '';
        ?>
        <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>"
           class="group flex flex-col bg-white border border-gray-200 rounded-2xl p-6
                  hover:border-brand hover:shadow-md transition-all duration-200">

            <?php if ( $img_url ) : ?>
                <div class="mb-4">
                    <img src="<?php echo esc_url( $img_url ); ?>"
                         alt="<?php echo esc_attr( $cat->name ); ?>"
                         style="max-height:60px; width:auto; display:block;">
                </div>
            <?php endif; ?>

            <h2 class="font-semibold text-gray-900 group-hover:text-brand transition-colors mb-1">
                <?php echo esc_html( $cat->name ); ?>
            </h2>

            <?php if ( $cat->description ) : ?>
                <p class="text-sm text-gray-500 mt-1 mb-0">
                    <?php echo esc_html( $cat->description ); ?>
                </p>
            <?php endif; ?>

            <p class="text-xs text-gray-400 mt-auto pt-3">
                <?php echo $child_count; ?> subcategoría<?php echo $child_count !== 1 ? 's' : ''; ?>
            </p>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<?php get_footer(); ?>
