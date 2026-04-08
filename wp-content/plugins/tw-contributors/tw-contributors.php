<?php
/**
 * Plugin Name: TW Contributors
 * Description: Creates the Contributors custom taxonomy
 *
 * @package tw_contributors
 */

/**
 * Register Contributors Taxonomy
 *
 * @return void
 */
function tw_contributors_taxonomy() {

	$labels = array(
		'name'                       => _x( 'Contributors', 'Taxonomy General Name', 'text_domain' ),
		'singular_name'              => _x( 'Contributor', 'Taxonomy Singular Name', 'text_domain' ),
		'menu_name'                  => __( 'Contributors', 'text_domain' ),
		'all_items'                  => __( 'All Contributors', 'text_domain' ),
		'parent_item'                => __( 'Parent Item', 'text_domain' ),
		'parent_item_colon'          => __( 'Parent Item:', 'text_domain' ),
		'new_item_name'              => __( 'New Contributor Name', 'text_domain' ),
		'add_new_item'               => __( 'Add New Contributor', 'text_domain' ),
		'edit_item'                  => __( 'Edit Contributor', 'text_domain' ),
		'update_item'                => __( 'Update Contributor', 'text_domain' ),
		'view_item'                  => __( 'View Contributor', 'text_domain' ),
		'separate_items_with_commas' => __( 'Separate contributors with commas', 'text_domain' ),
		'add_or_remove_items'        => __( 'Add or remove contributors', 'text_domain' ),
		'choose_from_most_used'      => __( 'Choose from the most used', 'text_domain' ),
		'popular_items'              => __( 'Popular Items', 'text_domain' ),
		'search_items'               => __( 'Search Contributors', 'text_domain' ),
		'not_found'                  => __( 'Not Found', 'text_domain' ),
		'no_terms'                   => __( 'No items', 'text_domain' ),
		'items_list'                 => __( 'Items list', 'text_domain' ),
		'items_list_navigation'      => __( 'Items list navigation', 'text_domain' ),
	);
	$args   = array(
		'labels'              => $labels,
		'description'         => 'Biographical details of folks that work on content.',
		'rewrite'             => array(
			'slug'       => 'contributors',
			'with_front' => false,
		),
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_admin_column'   => true,
		'show_in_nav_menus'   => true,
		'show_tagcloud'       => false,
		'show_in_rest'        => true,
		'show_in_graphql'     => true,
		'graphql_single_name' => 'contributor',
		'graphql_plural_name' => 'contributors',
	);
	register_taxonomy( 'contributor', array( 'post', 'attachment', 'segment', 'episode' ), $args );
}
add_action( 'init', 'tw_contributors_taxonomy', 0 );

/**
 * Change RSS feed author name.
 *
 * @param string $author Name of author.
 *
 * @return string
 */
function tw_contributors_rss_author( $author ) {

	// Check if we're in a feed.
	if ( is_feed() ) {

		$post_id   = get_the_ID();
		$post_type = get_post_type( $post_id );

		// List of affected post types.
		$affected_post_types = array( 'post', 'episode', 'segment' );

		// Bail early if not an affected post type.
		if ( ! in_array( $post_type, $affected_post_types, true ) ) {
			return $author;
		}

		$contributors = tw_contributors_get_post_contributors( $post_id );

		// Set first contributor as author.
		$author = reset( $contributors );

		// If more than one contributor, add action to rss2 action.
		if ( count( $contributors ) > 1 ) {
			add_action( 'rss2_item', 'tw_contributors_rss_extra_contributors' );
		}
	}

	// Trim whitespace.
	return $author;
}
add_filter( 'the_author', 'tw_contributors_rss_author' );

/**
 * Add extra contributors to RSS feed.
 *
 * @return void
 */
function tw_contributors_rss_extra_contributors() {

	// Get contributors.
	$post_id      = get_the_ID();
	$contributors = tw_contributors_get_post_contributors( $post_id );

	// Bail early if no contributors or only one contributor.
	if ( empty( $contributors ) || 1 === count( $contributors ) ) {
		return;
	}

	// Get all contributor beyond the first one.
	$extra_contributors = array_slice( $contributors, 1 );

	ob_start();

	// Print the remaining contributors.
	foreach ( $extra_contributors as $contributor ) {

		/**
		 * Filter the extra contributors HTML.
		 *
		 * @param string $contributors_html The contributors HTML.
		 * @param string $contributor The contributor name.
		 * @param int    $post_id The post ID.
		 *
		 * @return string
		 */
		$contributor_xml = apply_filters(
			'tw_contributors_rss_extra_contributors',
			wp_sprintf(
				'<dc:creator><![CDATA[%s]]></dc:creator>',
				$contributor
			),
			$contributor,
			$post_id
		);

		// Pretty tabbed output.
		echo "\n\t\t" . $contributor_xml;
	}

	echo ob_get_clean() . "\n";
}

/**
 * Get contributors based on post type.
 *
 * @param int $post_id The post ID.
 *
 * @return array The contributor terms.
 */
function tw_contributors_get_post_contributors( $post_id ) {

	$contributors = array();

	switch ( get_post_type( $post_id ) ) {

		case 'episode':
			$contributors = tw_contributors_get_post_contributors_by_meta(
				$post_id,
				array(
					'hosts',
					'producers',
					'reporters',
					'guests',
				)
			);
			break;

		case 'post':
		case 'segment':
			$contributors = tw_contributors_get_post_contributors_by_taxonomy( $post_id );
			break;
	}

	// Escape HTML and trim whitespace.
	$contributors = array_map( 'esc_html', $contributors );
	$contributors = array_map( 'trim', $contributors );

	return $contributors;
}

/**
 * Get contributor terms by taxonomy.
 *
 * @param int $post_id The post ID.
 *
 * @return array The contributor terms.
 */
function tw_contributors_get_post_contributors_by_taxonomy( $post_id ) {

	$contributors = array();

	// Get contributor taxonomy terms.
	$contributor_terms = get_the_terms( $post_id, 'contributor' );

	// Bail early if no contributor terms are found.
	if (
		! $contributor_terms
		||
		! is_array( $contributor_terms )
	) {
		return $contributors;
	}

	$contributor_names = wp_list_pluck( $contributor_terms, 'name' );

	return $contributor_names;
}

/**
 * Get contributor terms by meta.
 *
 * @param int   $post_id The post ID.
 * @param array $meta_keys The meta keys to get the contributor terms from.
 *
 * @return array The contributor terms.
 */
function tw_contributors_get_post_contributors_by_meta( $post_id, $meta_keys ) {

	$contributors_meta = array();

	// Pool all contributor meta values.
	foreach ( $meta_keys as $meta_key ) {

		$meta_value = get_post_meta( $post_id, $meta_key, true );

		// Continue if no meta value or not an array.
		if ( empty( $meta_value ) || ! is_array( $meta_value ) ) {
			continue;
		}

		foreach ( $meta_value as $contributor_term_id ) {

			// Add term name to contributors meta.
			$term = get_term( $contributor_term_id );

			// Bail if no term.
			if ( ! $term || is_wp_error( $term ) ) {
				continue;
			}

			// Add term to contributors meta.
			$contributors_meta[] = $term;
		}
	}

	// Flatten the contributor meta values.
	$contributors_names = wp_list_pluck( $contributors_meta, 'name' );

	// Remove duplicates.
	$contributors_names = array_unique( $contributors_names );

	return $contributors_names;
}
