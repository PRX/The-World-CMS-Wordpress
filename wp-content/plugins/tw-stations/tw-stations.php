<?php
/**
 * Plugin Name: TW Stations
 * Description: Manages the station custom post type
 *
 * @package tw_stations
 */

/**
 * Register Station Post Type.
 *
 * @return void
 */
function tw_stations_post_type() {

	$labels = array(
		'name'                  => _x( 'Stations', 'Post Type General Name', 'text_domain' ),
		'singular_name'         => _x( 'Station', 'Post Type Singular Name', 'text_domain' ),
		'menu_name'             => __( 'Stations', 'text_domain' ),
		'name_admin_bar'        => __( 'Stations', 'text_domain' ),
		'archives'              => __( 'Item Archives', 'text_domain' ),
		'attributes'            => __( 'Item Attributes', 'text_domain' ),
		'parent_item_colon'     => __( 'Parent Item:', 'text_domain' ),
		'all_items'             => __( 'All stations', 'text_domain' ),
		'add_new_item'          => __( 'Add New station', 'text_domain' ),
		'add_new'               => __( 'Add New', 'text_domain' ),
		'new_item'              => __( 'New station', 'text_domain' ),
		'edit_item'             => __( 'Edit station', 'text_domain' ),
		'update_item'           => __( 'Update station', 'text_domain' ),
		'view_item'             => __( 'View station', 'text_domain' ),
		'view_items'            => __( 'View station', 'text_domain' ),
		'search_items'          => __( 'Search station', 'text_domain' ),
		'not_found'             => __( 'Not found', 'text_domain' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'text_domain' ),
		'featured_image'        => __( 'Featured Image', 'text_domain' ),
		'set_featured_image'    => __( 'Set featured image', 'text_domain' ),
		'remove_featured_image' => __( 'Remove featured image', 'text_domain' ),
		'use_featured_image'    => __( 'Use as featured image', 'text_domain' ),
		'insert_into_item'      => __( 'Insert into item', 'text_domain' ),
		'uploaded_to_this_item' => __( 'Uploaded to this item', 'text_domain' ),
		'items_list'            => __( 'Items list', 'text_domain' ),
		'items_list_navigation' => __( 'Items list navigation', 'text_domain' ),
		'filter_items_list'     => __( 'Filter items list', 'text_domain' ),
	);
	$args   = array(
		'label'               => __( 'Stations', 'text_domain' ),
		'description'         => __( 'Manages the list of Stations that air The World', 'text_domain' ),
		'labels'              => $labels,
		'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
		'taxonomies'          => array(),
		'rewrite'             => array(
			'slug'       => 'stations',
			'with_front' => false,
		),
		'hierarchical'        => false,
		'public'              => true,
		'publicly_queryable'  => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_position'       => 10,
		'menu_icon'           => 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="black"><path d="M208-321q-52-53-82-122T96-592q0-80 30-149.5T208-864l52 51q-43 42-67.5 99T168-592q0 65 24.5 121t66.5 99l-51 51Zm104-103q-32-32-51-75t-19-93q0-50 19-93t51-75l51 51q-23 23-36 52.5T314-592q0 35 13 64.5t36 52.5l-51 51Zm-24 312 132-406q-17-14-26.5-32.5T384-592q0-40 28-68t68-28q40 0 68 28t28 68q0 24-10.5 43T537-516l111 404h-72l-20-72H384l-24 72h-72Zm119-144h129l-59-216-70 216Zm241-168-51-51q23-23 36-52.5t13-64.5q0-35-13-64.5T597-709l51-51q32 32 51 75t19 93q0 50-19 93t-51 75Zm103 103-51-51q43-42 67.5-98.5T792-592q0-65-24.5-121.5T701-813l51-51q52 53 82 122.5T864-592q0 80-30 149.5T751-321Z"/></svg>' ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		'show_in_admin_bar'   => true,
		'show_in_nav_menus'   => true,
		'can_export'          => true,
		'has_archive'         => false,
		'exclude_from_search' => true,
		'capability_type'     => array( 'station', 'stations' ),
		'capabilities'        => array(
			'create_posts' => 'create_stations',
		),
		'map_meta_cap'        => true,
		'show_in_rest'        => true,
		'show_in_graphql'     => true,
		'graphql_single_name' => 'station',
		'graphql_plural_name' => 'stations',
	);
	register_post_type( 'station', $args );
}
add_action( 'init', 'tw_stations_post_type', 0 );
