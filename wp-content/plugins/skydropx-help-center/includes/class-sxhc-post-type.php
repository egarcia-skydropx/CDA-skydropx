<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SXHC_Post_Type {

    public static function register() {
        $labels = array(
            'name'               => 'Artículos de ayuda',
            'singular_name'      => 'Artículo de ayuda',
            'add_new'            => 'Agregar artículo',
            'add_new_item'       => 'Agregar artículo de ayuda',
            'edit_item'          => 'Editar artículo',
            'new_item'           => 'Nuevo artículo',
            'view_item'          => 'Ver artículo',
            'search_items'       => 'Buscar artículos',
            'not_found'          => 'No se encontraron artículos',
            'not_found_in_trash' => 'No hay artículos en la papelera',
            'menu_name'          => 'Help Center',
            'all_items'          => 'Todos los artículos',
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_rest'        => true,   // Habilita el editor de bloques
            'query_var'           => true,
            'rewrite'             => array( 'slug' => 'ayuda' ),
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-sos',
            'supports'            => array(
                'title',
                'editor',
                'author',
                'thumbnail',
                'excerpt',
                'custom-fields',  // Habilita campos personalizados nativos
                'revisions',
            ),
            'taxonomies'          => array( 'help_category' ),
        );

        register_post_type( 'help_article', $args );
    }
}
