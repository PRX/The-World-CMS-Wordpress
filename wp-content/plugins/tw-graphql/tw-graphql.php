<?php
/**
 * Plugin Name: TW GraphQL
 * Description: Customize WP GraphQL API
 *
 * @package tw_graphql
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

use WPGraphQL\AppContext;
use WPGraphQL\Model\Term;

/**
 * Register GraphQL fields.
 */
add_action(
	'graphql_register_types',
	function () {
		$customposttype_graphql_single_name = 'Post'; // Replace this with your custom post type single name in PascalCase.

		// Registering the 'categorySlug' argument in the 'where' clause.
		// Feel free to change the name 'categorySlug' to something that suits your requirements.
		register_graphql_field(
			'RootQueryTo' . $customposttype_graphql_single_name . 'ConnectionWhereArgs',
			'programNotIn',
			array(
				'type'        => array( 'list_of' => 'ID' ), // To accept multiple strings.
				'description' => __( 'Filter by post objects that do not have the specified programs.', 'text_domain' ),
			)
		);

		// Add primary term field to post types for hierachical taxonomies.
		$post_types = \WPGraphQL::get_allowed_post_types();
		$taxonomies = \WPGraphQL::get_allowed_taxonomies();

		if ( ! empty( $post_types ) && is_array( $post_types ) ) {
			// Loop through each post type...
			foreach ( $post_types as $post_type ) {
				$post_type_object = get_post_type_object( $post_type );

				// Only add field to post types that are configured for graphql.
				if ( isset( $post_type_object->graphql_single_name ) ) {
					$taxonomies_post_object = get_object_taxonomies( $post_type, 'objects' );
					$post_name_key          = wp_gql_seo_get_field_key( $post_type_object->graphql_single_name );

					// Loop through each taxomony...
					foreach ( $taxonomies_post_object as $tax ) {
						$is_hierarchical_taxonomy = isset( $tax->hierarchical ) && $tax->hierarchical;
						$post_type_uses_taxonomy  = in_array( $post_type_object->name, $tax->object_type, true );

						// Only add field for taxonomies that are configured for graphql, are hierachical, and are used by the post type.
						if ( $is_hierarchical_taxonomy && $post_type_uses_taxonomy && isset( $tax->graphql_single_name ) ) {
							$tax_name_key = wp_gql_seo_get_field_key( $tax->graphql_single_name );

							register_graphql_field(
								ucfirst( $post_name_key ),
								'primary' . ucfirst( $tax->graphql_single_name ),
								array(
									'type'        => ucfirst( $tax_name_key ),
									'description' => __( 'The Yoast SEO Primary', 'text_domain' ) . ' ' . ucfirst( $tax->name ),
									'resolve'     => function ( $item, array $args, AppContext $context ) use ( $tax, $tax_name_key ) {
										$post_id = $item->ID;

										$wpseo_primary_term = new WPSEO_Primary_Term( $tax->name, $post_id );
										$primary_tax_id       = $wpseo_primary_term->get_primary_term();

										return $context->get_loader( 'term' )->load_deferred( absint( $primary_tax_id ) );
									},
								)
							);
						}
					}
				}
			}
		}
	}
);

/**
 * Modify GraphQL queries too support registered fields.
 */
add_filter(
	'graphql_post_object_connection_query_args',
	function ( $query_args, $source, $args ) {

		if ( ! isset( $args['where'] ) ) {
			return $query_args;
		}

		if ( isset( $args['where']['programNotIn'] ) ) {
			// If the 'programNotIn' argument is provided, we add it to the tax_query.
			// For more details, refer to the WP_Query class documentation at https://developer.wordpress.org/reference/classes/wp_query/.

			$excluded_program_ids = $args['where']['programNotIn'];

			// Decode hashed ids.
			$ids = array_map(
				function ( $id ) {
					if ( ! is_numeric( $id ) ) {
						// Decode hashed id.
						// phpcs:ignore
						$decoded_id  = base64_decode( $id );
						list( , $id) = explode( ':', $decoded_id );
					}
					return $id;
				},
				$excluded_program_ids
			);

			// phpcs:ignore
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'program',
					'field'    => 'term_id',
					'terms'    => $ids,
					'operator' => 'NOT IN',
				),
			);
		}

		return $query_args;
	},
	10,
	5
);

/**
 * Add `duation` metadata to MediaItem type.
 */
add_action(
	'graphql_register_types',
	function () {
		register_graphql_field(
			'MediaItem',
			'duration',
			array(
				'type'        => 'Int',
				'description' => __( 'Duration of audio in seconds.', 'tw' ),
				'resolve'     => function ( $attachment ) {
					// Get the attachment ID.
					$attachment_id = $attachment->ID;
					// Retrieve the meta value (replace 'my_meta_key' with your actual key).
					$meta_data = wp_get_attachment_metadata( $attachment_id );

					if ( ! isset( $meta_data['duration'] ) ) {
						return null;
					}

					return $meta_data['duration'];
				},
			)
		);
	}
);
