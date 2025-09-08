<?php
/**
 * Plugin Name: TW Resource Development Tags
 * Description: Creates the Resource Development custom taxonomy
 *
 * @package tw_resource_development_tags
 */

/**
 * Register Resource Development Tags Taxonomy
 *
 * @return void
 */
function tw_resource_development_tags_taxonomy() {
	/**
	 * Taxonomy: Resource Developments.
	 */

	$labels = array(
		'name'                       => esc_html__( 'Resource Developments', 'newspack' ),
		'singular_name'              => esc_html__( 'Resource Development', 'newspack' ),
		'search_items'               => esc_html__( 'Search Resource Developments', 'newspack' ),
		'popular_items'              => esc_html__( 'Popular Resource Developments', 'newspack' ),
		'all_items'                  => esc_html__( 'All Resource Developments', 'newspack' ),
		'parent_item'                => esc_html__( 'Parent Resource Development', 'newspack' ),
		'parent_item_colon'          => esc_html__( 'Parent Resource Development:', 'newspack' ),
		'edit_item'                  => esc_html__( 'Edit Resource Development', 'newspack' ),
		'view_item'                  => esc_html__( 'View Resource Development', 'newspack' ),
		'update_item'                => esc_html__( 'Update Resource Development', 'newspack' ),
		'add_new_item'               => esc_html__( 'Add New Resource Development', 'newspack' ),
		'new_item_name'              => esc_html__( 'New Resource Development Name', 'newspack' ),
		'separate_items_with_commas' => esc_html__( 'Separate Resource Developments with commas', 'newspack' ),
		'add_or_remove_items'        => esc_html__( 'Add or remove Resource Developments', 'newspack' ),
		'choose_from_most_used'      => esc_html__( 'Choose from the most used Resource Developments', 'newspack' ),
		'not_found'                  => esc_html__( 'No Resource Developments found.', 'newspack' ),
		'no_terms'                   => esc_html__( 'No Resource Developments', 'newspack' ),
		'items_list_navigation'      => esc_html__( 'Resource Developments list navigation', 'newspack' ),
		'items_list'                 => esc_html__( 'Resource Developments list', 'newspack' ),
		'menu_name'                  => esc_html__( 'Resource Developments', 'newspack' ),
		'name_admin_bar'             => esc_html__( 'Resource Developments', 'newspack' ),
	);

	$args = array(
		'label'                 => esc_html__( 'Resource Developments', 'newspack' ),
		'labels'                => $labels,
		'public'                => false,
		'publicly_queryable'    => true,
		'hierarchical'          => false,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'show_in_nav_menus'     => true,
		'query_var'             => true,
		'rewrite'               => array(
			'slug'         => 'resource_development',
			'with_front'   => true,
			'hierarchical' => true,
		),
		'show_admin_column'     => true,
		'show_in_rest'          => true,
		'show_tagcloud'         => false,
		'rest_base'             => 'resource_development',
		'rest_controller_class' => 'WP_REST_Terms_Controller',
		'rest_namespace'        => 'wp/v2',
		'show_in_quick_edit'    => true,
		'sort'                  => false,
		'show_in_graphql'       => true,
		'graphql_single_name'   => 'resourceDevelopmentTag',
		'graphql_plural_name'   => 'resourceDevelopmentTags',
	);
	register_taxonomy( 'resource_development', array( 'post', 'episode', 'segment' ), $args );
}
add_action( 'init', 'tw_resource_development_tags_taxonomy', 0 );
