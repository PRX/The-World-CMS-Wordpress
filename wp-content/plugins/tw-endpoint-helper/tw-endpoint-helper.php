<?php
/**
 * TW - Endpoint Helper
 *
 * @package       PEH
 * @author        dinkuminteractive
 *
 * @wordpress-plugin
 * Plugin Name:   TW Endpoint Helper
 * Plugin URI:    https://www.dinkuminteractive.com/
 * Description:   Endpoint helper.
 * Version:       1.0.2
 * Author:        dinkuminteractive
 * Author URI:    https://www.dinkuminteractive.com/
 * Update URI:    https://www.dinkuminteractive.com/
 * Text Domain:   peh
 */

/**
 * Get includes.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-peh-alias-object.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-peh-url-to-query.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-peh-url-to-query-item.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/peh-get-object-wild.php';

/**
 * Route definitions.
 */
define( 'PEH_MAIN_ROUTE', 'tw/v2' );
define( 'PEH_ROUTE_ALIAS', '/alias' );

/**
 * Initialize custom REST.
 *
 * @return void
 */
function peh_rest_api_init() {

	$args_alias = array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'peh_route_alias',
		'permission_callback' => '__return_true',
		'args'                => array(
			// slug is the alias.
			'slug' => array(
				'validate_callback' => 'peh_args_validation_callback',
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'peh_data_arg_sanitize_callback',
			),
		),
	);

	register_rest_route(
		PEH_MAIN_ROUTE,
		PEH_ROUTE_ALIAS,
		$args_alias
	);
}
add_action( 'rest_api_init', 'peh_rest_api_init' );

/**
 * Validate param.
 *
 * @param string          $value
 * @param WP_REST_Request $request
 * @param string          $key
 * @return bool
 */
function peh_args_validation_callback( $value, $request, $key ) {

	$value = sanitize_text_field( $value );

	// If the 'filter' argument is not a string return an error.
	if ( ! is_string( $value ) || ! $value ) {
		return new WP_Error( 'rest_invalid_param', esc_html__( 'TW - Alias or type supplied is required or invalid.', 'peh' ), array( 'status' => 400 ) );
	}

	return true;
}

/**
 * Resolve an url to an array of WP_Query.
 *
 * @param string $url       Url to resolve
 * @param type   $query_vars  Query variables to be added to the url
 * @return array|\WP_Error  Resolved query or WP_Error is something goes wrong
 * @staticvar \GM\UrlToQuery $resolver
 */
function peh_url_to_query( $url = '', array $query_vars = array() ) {
	static $resolver = null;
	if ( is_null( $resolver ) ) {
		$resolver = new GM\UrlToQuery();
	}
	return $resolver->resolve( $url, $query_vars );
}

/**
 * Sanitize function.
 *
 * @param string          $value
 * @param WP_REST_Request $request
 * @param string          $param
 * @return void
 */
function peh_data_arg_sanitize_callback( $value, $request, $param ) {
	// It is as simple as returning the sanitized value.
	return sanitize_text_field( $value );
}

/**
 * Custom rest callback.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function peh_route_alias( WP_REST_Request $request ) {

	$response = array(
		'status' => 200,
		'data'   => array(),
	);

	// Slug is the alias.
	$slug = $request->get_param( 'slug' );

	if ( empty( $slug ) ) {

		$response['status'] = 400;

	} else {

		if ( is_numeric( $slug ) ) {
			// Slug could be a nid or a fid...

			// Check for node with slug as nid.
			$alias_object = _peh_get_object_with_nid( $slug );

			if ( ! $alias_object instanceof Peh_Alias_Object ) {
				// Check for media with slug as fid.
				$alias_object = _peh_get_object_with_fid( $slug );
			}
		} else {
			// Check from redirect table.
			$alias_object = _peh_get_object( $slug );
		}

		if ( $alias_object instanceof Peh_Alias_Object ) {

			if ( $alias_object->is_external() ) {

				$response = $alias_object->get_external_response_array();

			} else {

				// Load entity.
				$route = $alias_object->get_rest_route();

				if ( $route ) {

					try {

						// Do WP REST request.
						$request  = new WP_REST_Request( 'GET', $route );
						$response = rest_do_request( $request );
						// Return the response as it is since it is a WordPress API response or a WordPress error from its API.
						return rest_ensure_response( $response );

					} catch ( \Exception $e ) {

						$response['exception'] = 'true';

						if ( $e && method_exists( $e, 'getCode' ) ) {

							$response['status'] = $e->getCode();

						} else {

							$response['status'] = 500;
						}
					}
				} else {

					$response['status'] = 404;
				}
			}
		} else {

			$response['status'] = 404;
		}
	}

	return peh_get_response( $response );
}

/**
 * Get standardized object of alias.
 *
 * @param string $slug
 * @return mixed
 */
function _peh_get_object( $slug ) {

	$alias_object = apply_filters( 'peh_get_object_filters', false, $slug );

	if ( isset( $alias_object->id, $alias_object->type ) ) {

		$alias_object = new Peh_Alias_Object( $alias_object );
	}

	return $alias_object;
}

/**
 * Filter object.
 *
 * @param mixed  $object
 * @param string $slug
 * @return mixed
 */
function peh_maybe_object_wp_migrated_legacy_redirect_db( $object, $slug ) {

	return $object ? $object : _peh_get_object_by_wp_migrated_legacy_redirect_db( $slug );
}
add_filter( 'peh_get_object_filters', 'peh_maybe_object_wp_migrated_legacy_redirect_db', 0, 2 );

/**
 * Filter object.
 *
 * @param mixed  $object
 * @param string $slug
 * @return mixed
 */
function peh_maybe_object_wp_migrated_legacy_alias_db( $object, $slug ) {

	return $object ? $object : _peh_get_object_by_wp_migrated_legacy_alias_db( $slug );
}
add_filter( 'peh_get_object_filters', 'peh_maybe_object_wp_migrated_legacy_alias_db', 3, 2 );

/**
 * Filter object.
 *
 * @param mixed  $object
 * @param string $slug
 * @return mixed
 */
function peh_maybe_object_wp_fg_redirect_db( $object, $slug ) {

	return $object ? $object : _peh_get_object_by_wp_fg_redirect_db( $slug );
}
add_filter( 'peh_get_object_filters', 'peh_maybe_object_wp_fg_redirect_db', 5, 2 );

/**
 * Filter object.
 *
 * @param mixed  $object
 * @param string $slug
 * @return mixed
 */
function peh_maybe_object_post( $object, $slug ) {

	return $object ? $object : _peh_get_object_by_slug( $slug );
}
add_filter( 'peh_get_object_filters', 'peh_maybe_object_post', 10, 2 );

/**
 * Filter object.
 *
 * @param mixed  $object
 * @param string $slug
 * @return mixed
 */
function peh_maybe_object_terms( $object, $slug ) {

	return $object ? $object : _peh_get_object_by_taxonomy( $slug );
}
add_filter( 'peh_get_object_filters', 'peh_maybe_object_terms', 15, 2 );

/**
 * Filter object for posts migrated as terms.
 *
 * @param mixed  $object
 * @param string $slug
 * @return mixed
 */
function peh_maybe_post_moved_to_object_terms( $object ) {

	if ( isset( $object->type ) && in_array( $object->type, array( 'program', 'contributor' ) ) ) {
		$object_type = $object->type === 'contributor' ? 'person' : $object->type;
		$args        = array(
			'hide_empty' => false, // also retrieve terms which are not used yet
			'meta_query' => array(
				array(
					'key'     => "_pri_old_wp_{$object_type}_id",
					'value'   => $object->id,
					'compare' => '=',
				),
			),
			'fields'     => 'ids',
		);
		$terms       = get_terms( $args );
		if ( $terms ) {
			$object->id = $terms[0];
		}
	}
	return $object;
}
add_filter( 'peh_get_object_filters', 'peh_maybe_post_moved_to_object_terms', 20, 2 );

/**
 * Filter object.
 *
 * @param mixed  $object
 * @param string $slug
 * @return mixed
 */
function peh_maybe_object_wild( $object, $slug ) {

	return $object ? $object : _peh_get_object_wild( $slug );
}
add_filter( 'peh_get_object_filters', 'peh_maybe_object_wild', 25, 2 );

/**
 * Get post id from wp_fg_redirect table.
 *
 * @param array $slug string
 * @return bool|int
 */
function _peh_get_object_by_wp_migrated_legacy_redirect_db( $slug ) {

	global $wpdb;

	$row = $wpdb->get_row( $wpdb->prepare( "SELECT `uid` AS `id`, `type`, `redirect` FROM `wp_migrated_legacy_redirect` WHERE `source` = '%s' LIMIT 1;", $slug ) );
	if ( isset( $row->type ) && 'redirect' === $row->type && wp_http_validate_url( $row->redirect ) ) {
		$row->is_external = true;
	} elseif ( $row && isset( $row->redirect ) && $row->redirect ) {
		$row = apply_filters( 'peh_get_object_filters', false, $row->redirect );
	}
	return $row;
}

/**
 * Get post id from wp_fg_redirect table.
 *
 * @param array $slug string
 * @return bool|int
 */
function _peh_get_object_by_wp_fg_redirect_db( $slug ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( "SELECT `id`, `type` FROM `wp_fg_redirect` WHERE `old_url` = '%s' LIMIT 1;", $slug ) );
}

/**
 * Get post id from wp_fg_redirect table.
 *
 * @param string $slug string
 * @return bool|int
 */
function _peh_get_object_by_wp_migrated_legacy_alias_db( $slug ) {

	global $wpdb;

	// Check if it starts with media/ to try to select file alias as well.
	if ( 0 === strpos( $slug, 'media/' ) ) {
		$slug_file = str_replace( 'media/', 'file/', $slug );
		$row       = $wpdb->get_row( $wpdb->prepare( "SELECT `source`, `alias` FROM `wp_migrated_legacy_url_alias` WHERE `alias` IN ('%s', '%s') LIMIT 1;", $slug, $slug_file ) );
	} else {
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT `source`, `alias` FROM `wp_migrated_legacy_url_alias` WHERE `alias` = '%s' LIMIT 1;", $slug ) );
	}

	if (
		isset( $row->source ) && $row->source
		&&
		isset( $row->alias ) && $row->alias
	) {

		$id      = '';
		$type    = '';
		$wp_type = '';

		// Check for node or taxonomy record type.
		$type_checks = array(
			array(
				'name'    => 'node',
				'replace' => 'node/',
			),
			array(
				'name'    => 'file',
				'replace' => 'file/',
			),
			array(
				'name'    => 'taxonomy',
				'replace' => 'taxonomy/term/',
			),
		);

		foreach ( $type_checks as $check ) {

			if ( str_contains( $row->source, $check['name'] ) ) {

				// Search from start to the first occurence of "/".
				preg_match( '/^.+?(?=\/)/', $row->alias, $matches );

				$type = $matches ? $matches[0] : $row->alias;
				$id   = str_replace( $check['replace'], '', $row->source );
				// stop lookin for other types.
				break;
			}
		}

		// Return only if something is found.
		if ( $id && $type ) {

			$wp_id       = '';
			$wp_type     = '';
			$wp_meta_key = 'nid';

			// Convert type from node to post_type if needed.
			switch ( $type ) {

				case 'people':
					$wp_type = 'person';
					break;

				case 'stories':
					$wp_type = 'post';
					break;

				case 'media':
				case 'file':
					$wp_type     = 'segment';
					$wp_meta_key = 'fid';
					break;

				default:
					$wp_type = $type;
					break;
			}

			// Get WP Posts by node id.
			$s_args = array(
				'post_type'              => $wp_type,
				'meta_key'               => $wp_meta_key,
				'meta_value'             => $id,
				'fields'                 => 'ids',
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			);

			$posts = get_posts( $s_args );

			if ( $posts && ! is_wp_error( $posts ) ) {

				$wp_id = $posts[0];
			}

			if ( $wp_id && $wp_type ) {

				$object       = new stdClass();
				$object->id   = $wp_id;
				$object->type = $wp_type;

				return $object;
			}
		}
	}

	return false;
}


/**
 * Get post by slug.
 *
 * @param string $slug
 * @param array  $extra_args
 * @return void
 */
function _peh_get_object_by_slug( $slug, $extra_args = array() ) {

	$args = array(
		'name'                   => $slug,
		'post_status'            => 'publish',
		'posts_per_page'         => 1,
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	);

	if ( $extra_args ) {

		$args = wp_parse_args( $extra_args, $args );
	}

	$objects = get_posts( $args );

	if ( $objects ) {

		$object       = new stdClass();
		$object->id   = $objects[0]->ID;
		$object->type = $objects[0]->post_type;

		return $object;
	}

	return false;
}

/**
 * Get tax by slug.
 *
 * @param string $slug
 * @param string $taxonomy
 * @return void
 */
function _peh_get_object_by_taxonomy( $slug, $tax = '' ) {

	$object = false;

	$term_id = $tax ? term_exists( $slug, $tax ) : term_exists( $slug );

	// depending of the resource type, check in taxonomy.
	if ( $term_id ) {

		$taxonomy = $tax ? $tax : get_term_field( 'taxonomy', $term_id );
		$object   = new stdClass();

		$object->id   = $tax ? $term_id['term_id'] : $term_id;
		$object->type = $taxonomy;
	}

	return $object;
}

/**
 * Assume random object url structure.
 *
 * @param string $slug
 * @return void
 */
function _peh_get_object_wild( $slug ) {

	$object = false;

	if ( $slug ) {
		$url_query = peh_url_to_query( $slug );
		if ( ! is_wp_error( $url_query ) ) {

			$object = apply_filters( 'peh_get_object_wild', $object, $url_query );
		}
	}

	return $object;
}

/**
 * Assume slug is an nid.
 *
 * @param string $slug
 * @return void
 */
function _peh_get_object_with_nid( $slug ) {

	global $wpdb;

	$object = false;

	$result = $wpdb->get_row( $wpdb->prepare( "SELECT p.ID, p.post_type, p.post_mime_type FROM `wp_postmeta` `pm` LEFT JOIN `wp_posts` `p` ON p.ID = pm.post_id WHERE pm.meta_key = 'nid' AND pm.meta_value = %s;", $slug ) );

	if ( $result ) {
		$alias_object       = new stdClass();
		$alias_object->id   = $result->ID;
		$alias_object->type = $result->post_type;

		$object = new Peh_Alias_Object( $alias_object );
	}

	return $object;
}

/**
 * Assume slug is an fid.
 *
 * @param string $slug
 * @return void
 */
function _peh_get_object_with_fid( $slug ) {

	global $wpdb;

	$object = false;

	$results = $wpdb->get_results( $wpdb->prepare( "SELECT p.ID, p.post_type, p.post_mime_type FROM `wp_postmeta` `pm` LEFT JOIN `wp_posts` `p` ON p.ID = pm.post_id WHERE pm.meta_key = 'fid' AND pm.meta_value = %s;", $slug ) );

	if ( is_array( $results ) ) {
		// Favor segment post type when attachment is audio.
		$object_attachments = array_filter( $results, fn( $row ) => 'attachment' === $row->post_type );
		$object_segments    = array_filter( $results, fn( $row ) => 'segment' === $row->post_type );
		$object_attachment  = array_pop( $object_attachments );
		$object_segment     = array_pop( $object_segments );

		if ( 0 === strpos( $object_attachment->post_mime_type, 'audio/' ) && $object_segment ) {
			$object = $object_segment;
		} else {
			$object = $object_attachment;
		}
	} elseif ( $results ) {
		$object = $results;
	}

	if ( $object ) {
		$alias_object       = new stdClass();
		$alias_object->id   = $object->ID;
		$alias_object->type = $object->post_type;

		$object = new Peh_Alias_Object( $alias_object );
	}

	return $object;
}




/**
 * Function to get response array based on $response argument.
 *
 * @param array $response
 * @return array $response
 */
function peh_get_response( array $response ) {

	$status = $response['status'];

	$errors = array(
		400 => array( 'rest_no_route', 'TW - No argument supplied.' ),
		403 => array( 'rest_no_route', 'TW - Resource not found or forbiden.' ),
		404 => array( 'rest_no_route', 'TW - No route was found matching the URL and request method.' ),
		500 => array( 'rest_internal_error', 'TW - 500 Internal Error.' ),
	);

	$error = isset( $errors[ $status ] ) ? $errors[ $status ] : false;

	return $error
		? new WP_Error( $error[0], __( $error[1], 'peh' ), $response )
		: new WP_REST_Response( $response['data'], $status );
}
