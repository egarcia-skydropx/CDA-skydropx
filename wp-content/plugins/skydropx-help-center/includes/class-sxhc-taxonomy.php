<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SXHC_Taxonomy {

    public static function register() {
        $labels = array(
            'name'              => 'Categorías de ayuda',
            'singular_name'     => 'Categoría de ayuda',
            'search_items'      => 'Buscar categorías',
            'all_items'         => 'Todas las categorías',
            'parent_item'       => 'Categoría padre',
            'parent_item_colon' => 'Categoría padre:',
            'edit_item'         => 'Editar categoría',
            'update_item'       => 'Actualizar categoría',
            'add_new_item'      => 'Agregar categoría',
            'new_item_name'     => 'Nueva categoría',
            'menu_name'         => 'Categorías',
        );

        $args = array(
            'labels'            => $labels,
            'hierarchical'      => true,   // Como categorías, no como tags — anidamiento infinito
            'public'            => true,
            'show_ui'           => true,
            'show_in_menu'      => true,
            'show_in_rest'      => true,
            'show_admin_column' => true,   // Muestra la categoría en la lista de artículos
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'categoria', 'hierarchical' => true ),
        );

        register_taxonomy( 'help_category', array( 'help_article' ), $args );
    }
}
