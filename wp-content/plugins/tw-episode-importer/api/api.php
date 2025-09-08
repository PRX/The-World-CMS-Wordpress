<?php
/**
 * Register API routes.
 *
 * @package tw_episode_importer
 */

define( 'TW_EPISODE_IMPORTER_API_ENDPOINT', 'episode-importer' );

/**
 * Initialize REST route for API episodes.
 *
 * @return void
 */
function tw_episode_importer_rest_api_init() {

	register_rest_route(
		TW_API_ROUTE_BASE,
		TW_EPISODE_IMPORTER_API_ENDPOINT . '/episodes',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'tw_episode_importer_api_route_epsisodes',
			'permission_callback' => '__return_true',
			'args'                => array(
				'before' => array(
					'validate_callback' => 'tw_episode_importer_args_date_validation_callback',
					'type'              => 'string',
					'required'          => false,
					'sanitize_callback' => 'tw_episode_importer_args_date_sanitization_callback',
				),
				'after'  => array(
					'validate_callback' => 'tw_episode_importer_args_date_validation_callback',
					'type'              => 'string',
					'required'          => false,
					'sanitize_callback' => 'tw_episode_importer_args_date_sanitization_callback',
				),
				'on'     => array(
					'validate_callback' => 'tw_episode_importer_args_date_validation_callback',
					'type'              => 'string',
					'required'          => false,
					'sanitize_callback' => 'tw_episode_importer_args_date_sanitization_callback',
				),
			),
		)
	);
	register_rest_route(
		TW_API_ROUTE_BASE,
		TW_EPISODE_IMPORTER_API_ENDPOINT . '/episodes',
		array(
			'methods'             => WP_REST_Server::DELETABLE,
			'callback'            => 'tw_episode_importer_api_route_epsisodes_rollback',
			'permission_callback' => '__return_true',
			'args'                => array(
				'before' => array(
					'validate_callback' => 'tw_episode_importer_args_date_validation_callback',
					'type'              => 'string',
					'required'          => false,
					'sanitize_callback' => 'tw_episode_importer_args_date_sanitization_callback',
				),
				'after'  => array(
					'validate_callback' => 'tw_episode_importer_args_date_validation_callback',
					'type'              => 'string',
					'required'          => false,
					'sanitize_callback' => 'tw_episode_importer_args_date_sanitization_callback',
				),
				'on'     => array(
					'validate_callback' => 'tw_episode_importer_args_date_validation_callback',
					'type'              => 'string',
					'required'          => false,
					'sanitize_callback' => 'tw_episode_importer_args_date_sanitization_callback',
				),
			),
		)
	);
	register_rest_route(
		TW_API_ROUTE_BASE,
		TW_EPISODE_IMPORTER_API_ENDPOINT . '/episodes/(?P<id>[a-f0-9-]+)',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'tw_episode_importer_api_route_epsisode_get',
			'permission_callback' => '__return_true',
			'args'                => array(),
		)
	);
	register_rest_route(
		TW_API_ROUTE_BASE,
		TW_EPISODE_IMPORTER_API_ENDPOINT . '/episodes/(?P<id>[a-f0-9-]+)',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'tw_episode_importer_api_route_epsisode_create',
			'permission_callback' => 'tw_episode_importer_api_route_import_permissions_check',
			'args'                => array(),
		)
	);
	register_rest_route(
		TW_API_ROUTE_BASE,
		TW_EPISODE_IMPORTER_API_ENDPOINT . '/episodes/(?P<id>[a-f0-9-]+)',
		array(
			'methods'             => WP_REST_Server::EDITABLE,
			'callback'            => 'tw_episode_importer_api_route_epsisode_update',
			'permission_callback' => 'tw_episode_importer_api_route_import_permissions_check',
			'args'                => array(),
		)
	);
	register_rest_route(
		TW_API_ROUTE_BASE,
		TW_EPISODE_IMPORTER_API_ENDPOINT . '/episodes/(?P<id>[a-f0-9-]+)',
		array(
			'methods'             => WP_REST_Server::DELETABLE,
			'callback'            => 'tw_episode_importer_api_route_epsisode_delete',
			'permission_callback' => 'tw_episode_importer_api_route_import_permissions_check',
			'args'                => array(),
		)
	);

	register_rest_route(
		TW_API_ROUTE_BASE,
		TW_EPISODE_IMPORTER_API_ENDPOINT . '/segments',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'tw_episode_importer_api_route_segments',
			'permission_callback' => '__return_true',
			'args'                => array(
				'before' => array(
					'validate_callback' => 'tw_episode_importer_args_date_validation_callback',
					'type'              => 'string',
					'required'          => false,
					'sanitize_callback' => 'tw_episode_importer_args_date_sanitization_callback',
				),
				'after'  => array(
					'validate_callback' => 'tw_episode_importer_args_date_validation_callback',
					'type'              => 'string',
					'required'          => false,
					'sanitize_callback' => 'tw_episode_importer_args_date_sanitization_callback',
				),
				'on'     => array(
					'validate_callback' => 'tw_episode_importer_args_date_validation_callback',
					'type'              => 'string',
					'required'          => false,
					'sanitize_callback' => 'tw_episode_importer_args_date_sanitization_callback',
				),
			),
		)
	);
	register_rest_route(
		TW_API_ROUTE_BASE,
		TW_EPISODE_IMPORTER_API_ENDPOINT . '/segments',
		array(
			'methods'             => WP_REST_Server::DELETABLE,
			'callback'            => 'tw_episode_importer_api_route_segments_rollback',
			'permission_callback' => '__return_true',
			'args'                => array(
				'before' => array(
					'validate_callback' => 'tw_episode_importer_args_date_validation_callback',
					'type'              => 'string',
					'required'          => false,
					'sanitize_callback' => 'tw_episode_importer_args_date_sanitization_callback',
				),
				'after'  => array(
					'validate_callback' => 'tw_episode_importer_args_date_validation_callback',
					'type'              => 'string',
					'required'          => false,
					'sanitize_callback' => 'tw_episode_importer_args_date_sanitization_callback',
				),
				'on'     => array(
					'validate_callback' => 'tw_episode_importer_args_date_validation_callback',
					'type'              => 'string',
					'required'          => false,
					'sanitize_callback' => 'tw_episode_importer_args_date_sanitization_callback',
				),
			),
		)
	);
	register_rest_route(
		TW_API_ROUTE_BASE,
		TW_EPISODE_IMPORTER_API_ENDPOINT . '/segments/(?P<id>[a-f0-9-]+)',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'tw_episode_importer_api_route_segment_get',
			'permission_callback' => '__return_true',
			'args'                => array(),
		)
	);
	register_rest_route(
		TW_API_ROUTE_BASE,
		TW_EPISODE_IMPORTER_API_ENDPOINT . '/segments/(?P<id>[a-f0-9-]+)',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'tw_episode_importer_api_route_segment_create',
			'permission_callback' => 'tw_episode_importer_api_route_import_permissions_check',
			'args'                => array(),
		)
	);
	register_rest_route(
		TW_API_ROUTE_BASE,
		TW_EPISODE_IMPORTER_API_ENDPOINT . '/segments/(?P<id>[a-f0-9-]+)',
		array(
			'methods'             => WP_REST_Server::EDITABLE,
			'callback'            => 'tw_episode_importer_api_route_segment_update',
			'permission_callback' => 'tw_episode_importer_api_route_import_permissions_check',
			'args'                => array(),
		)
	);
	register_rest_route(
		TW_API_ROUTE_BASE,
		TW_EPISODE_IMPORTER_API_ENDPOINT . '/segments/(?P<id>[a-f0-9-]+)',
		array(
			'methods'             => WP_REST_Server::DELETABLE,
			'callback'            => 'tw_episode_importer_api_route_segment_delete',
			'permission_callback' => 'tw_episode_importer_api_route_import_permissions_check',
			'args'                => array(),
		)
	);

	register_rest_route(
		TW_API_ROUTE_BASE,
		TW_EPISODE_IMPORTER_API_ENDPOINT . '/taxonomies',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'tw_episode_importer_api_route_taxonomies',
			'permission_callback' => '__return_true',
			'args'                => array(),
		)
	);
}
add_action( 'rest_api_init', 'tw_episode_importer_rest_api_init' );

/**
 * Validate date param.
 *
 * @param string          $value    Value to validate.
 * @param WP_REST_Request $request  Request object.
 * @param string          $key      Parameter key.
 * @return bool
 */
// phpcs:ignore
function tw_episode_importer_args_date_validation_callback( $value, $request, $key ) {

	try {
		$after = new DateTime( $value );
	} catch ( \Throwable $th ) {
		return new WP_Error( 'rest_invalid_param', esc_html__( 'TW - Invalid date provided.', 'tw-text' ), array( 'status' => 400 ) );
	}

	return true;
}

/**
 * Sanitize function.
 *
 * @param string          $value    Value to validate.
 * @param WP_REST_Request $request  Request object.
 * @param string          $param      Parameter key.
 * @return string
 */
// phpcs:ignore
function tw_episode_importer_args_date_sanitization_callback( $value, $request, $param ) {
	// It is as simple as returning the sanitized value.
	return sanitize_text_field( $value );
}

/**
 * API Episodes route callback.
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response
 */
// phpcs:ignore
function tw_episode_importer_api_route_epsisodes( WP_REST_Request $request ) {

	// Request API data.
	$settings           = get_option( TW_EPISODE_IMPORTER_SETTINGS_API );
	$api_url            = $settings[ TW_EPISODE_IMPORTER_EPISODES_API_URL_KEY ];
	$after_date_string  = $request->get_param( 'after' );
	$before_date_string = $request->get_param( 'before' );
	$on_date_string     = $request->get_param( 'on' );
	$on_date            = $on_date_string ? new DateTime( $on_date_string ) : new DateTime();
	$one_day            = new DateInterval( 'P1D' );
	$after              = $after_date_string ? $after_date_string : $on_date->format( 'Y-m-d' );
	$before             = $before_date_string ? $before_date_string : $on_date->add( $one_day )->format( 'Y-m-d' );
	$api_response       = wp_remote_get( $api_url . '?after=' . $after . '&before=' . $before . '&per=365' );
	$status             = wp_remote_retrieve_response_code( $api_response );

	$response = array(
		'status' => $status,
		'data'   => array(),
	);

	if ( 200 === $status ) {

		$api_body = json_decode( wp_remote_retrieve_body( $api_response ) );
		$episodes = tw_episode_importer_parse_api_items( $api_body, 'episode' );

		$response['status'] = 200;
		$response['data']   = $episodes ?? array();

	}

	return tw_episode_importer_get_response( $response );
}

/**
 * API Episodes rollback route callback.
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response
 */
// phpcs:ignore
function tw_episode_importer_api_route_epsisodes_rollback( WP_REST_Request $request ) {

	// Request API data.
	$settings           = get_option( TW_EPISODE_IMPORTER_SETTINGS_API );
	$api_url            = $settings[ TW_EPISODE_IMPORTER_EPISODES_API_URL_KEY ];
	$after_date_string  = $request->get_param( 'after' );
	$before_date_string = $request->get_param( 'before' );
	$on_date_string     = $request->get_param( 'on' );
	$on_date            = $on_date_string ? new DateTime( $on_date_string ) : new DateTime();
	$one_day            = new DateInterval( 'P1D' );
	$after              = $after_date_string ? $after_date_string : $on_date->format( 'Y-m-d' );
	$before             = $before_date_string ? $before_date_string : $on_date->add( $one_day )->format( 'Y-m-d' );
	$api_response       = wp_remote_get( $api_url . '?after=' . $after . '&before=' . $before . '&per=365' );
	$status             = wp_remote_retrieve_response_code( $api_response );

	$response = array(
		'status' => $status,
		'data'   => array(),
	);

	if ( 200 === $status ) {

		$api_body = json_decode( wp_remote_retrieve_body( $api_response ) );
		$episodes = tw_episode_importer_parse_api_items( $api_body, 'episode' );

		$episodes = tw_episode_importer_rollback_episodes( $episodes );

		$response['status'] = 200;
		$response['data']   = $episodes ?? array();

	}

	return tw_episode_importer_get_response( $response );
}

/**
 * API Epsiode READABLE route callback.
 *
 * @param WP_Request $request This function accepts a rest request to process data.
 * @return WP_REST_Response
 */
function tw_episode_importer_api_route_epsisode_get( $request ) {

	// Request API data.
	$settings     = get_option( TW_EPISODE_IMPORTER_SETTINGS_API );
	$id           = $request->get_param( 'id' );
	$api_url      = $settings[ TW_EPISODE_IMPORTER_EPISODES_API_URL_KEY ] . '/' . $id;
	$api_response = wp_remote_get( $api_url );
	$status       = wp_remote_retrieve_response_code( $api_response );

	$response = array(
		'status' => $status,
		'data'   => array(),
	);

	if ( 200 === $status ) {
		$api_body = json_decode( wp_remote_retrieve_body( $api_response ) );
		$episode  = tw_episode_importer_parse_api_item( $api_body, 'episode' );

		$response['data'] = $episode ?? array();
	}

	return tw_episode_importer_get_response( $response );
}

/**
 * API Epsiode CREATABLE route callback.
 *
 * @param WP_Request $request This function accepts a rest request to process data.
 * @return WP_REST_Response
 */
function tw_episode_importer_api_route_epsisode_create( $request ) {

	// Request API data.
	$settings     = get_option( TW_EPISODE_IMPORTER_SETTINGS_API );
	$id           = $request->get_param( 'id' );
	$api_url      = $settings[ TW_EPISODE_IMPORTER_EPISODES_API_URL_KEY ] . '/' . $id;
	$api_response = wp_remote_get( $api_url );
	$status       = wp_remote_retrieve_response_code( $api_response );

	$response = array(
		'status' => $status,
		'data'   => array(),
	);

	if ( 200 === $status ) {
		$api_body = json_decode( wp_remote_retrieve_body( $api_response ) );
		$episode  = tw_episode_importer_parse_api_item( $api_body, 'episode' );
		$body     = $request->get_body();
		$options  = json_decode( $body );

		if ( $episode ) {
			tw_episode_importer_audio_create( $episode, 'program-episode', $options );
			// Only create episode post for broadcast related audio.
			// Audio will be considered broadcast audio if a date key was extracted from the filename during parsing.
			// When we have extra episodes, they will not usually need an episode and that audio would be attached to a story post.
			if ( $episode['dateKey'] ) {
				tw_episode_importer_episode_create( $episode, $options );
			}
		}

		$response['data'] = $episode ?? array();
	}

	return tw_episode_importer_get_response( $response );
}

/**
 * API Epsiode EDITABLE route callback.
 *
 * @param WP_Request $request This function accepts a rest request to process data.
 * @return WP_REST_Response
 */
function tw_episode_importer_api_route_epsisode_update( $request ) {

	// Request API data.
	$settings     = get_option( TW_EPISODE_IMPORTER_SETTINGS_API );
	$id           = $request->get_param( 'id' );
	$api_url      = $settings[ TW_EPISODE_IMPORTER_EPISODES_API_URL_KEY ] . '/' . $id;
	$api_response = wp_remote_get( $api_url );
	$status       = wp_remote_retrieve_response_code( $api_response );

	$response = array(
		'status' => $status,
		'data'   => array(),
	);

	if ( 200 === $status ) {
		$api_body = json_decode( wp_remote_retrieve_body( $api_response ) );
		$episode  = tw_episode_importer_parse_api_item( $api_body, 'episode' );
		$body     = $request->get_body();
		$options  = json_decode( $body );

		if ( $episode ) {
			tw_episode_importer_audio_update( $episode, 'program-episode', $options );
			tw_episode_importer_episode_update( $episode, $options );
		}

		$response['data'] = $episode ?? array();
	}

	return tw_episode_importer_get_response( $response );
}

/**
 * API Epsiode DELETABLE route callback.
 *
 * @param WP_Request $request This function accepts a rest request to process data.
 * @return WP_REST_Response
 */
function tw_episode_importer_api_route_epsisode_delete( $request ) {

	// Request API data.
	$settings     = get_option( TW_EPISODE_IMPORTER_SETTINGS_API );
	$id           = $request->get_param( 'id' );
	$api_url      = $settings[ TW_EPISODE_IMPORTER_EPISODES_API_URL_KEY ] . '/' . $id;
	$api_response = wp_remote_get( $api_url );
	$status       = wp_remote_retrieve_response_code( $api_response );

	$response = array(
		'status' => $status,
		'data'   => array(),
	);

	if ( 200 === $status ) {
		$api_body = json_decode( wp_remote_retrieve_body( $api_response ) );
		$episode  = tw_episode_importer_parse_api_item( $api_body, 'episode' );
		$body     = $request->get_body();
		$options  = json_decode( $body );

		if ( $episode ) {
			// Always delete audio unless flagged not to.
			$delete_audio = property_exists( $options, 'deleteAudio' ) ? $options->deleteAudio : true; //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			if ( $delete_audio ) {
				tw_episode_importer_audio_delete( $episode );
			}
			// Delete parent if flagged to.
			$delete_parent = property_exists( $options, 'deleteParent' ) ? $options->deleteParent : false; //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			if ( $delete_parent && $episode['existingPost'] ) {
				tw_episode_importer_episode_delete( $episode );
			}
		}

		$response['data'] = $episode ?? array();
	}

	return tw_episode_importer_get_response( $response );
}

/**
 * API Segments route callback.
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response
 */
// phpcs:ignore
function tw_episode_importer_api_route_segments( WP_REST_Request $request ) {

	// Request API data.
	$settings           = get_option( TW_EPISODE_IMPORTER_SETTINGS_API );
	$api_url            = $settings[ TW_EPISODE_IMPORTER_SEGMENTS_API_URL_KEY ];
	$after_date_string  = $request->get_param( 'after' );
	$before_date_string = $request->get_param( 'before' );
	$on_date_string     = $request->get_param( 'on' );
	$on_date            = $on_date_string ? new DateTime( $on_date_string ) : new DateTime();
	$one_day            = new DateInterval( 'P1D' );
	$after              = $after_date_string ? $after_date_string : $on_date->format( 'Y-m-d' );
	$before             = $before_date_string ? $before_date_string : $on_date->add( $one_day )->format( 'Y-m-d' );
	$api_response       = wp_remote_get( $api_url . '?after=' . $after . '&before=' . $before . '&per=3650' );
	$status             = wp_remote_retrieve_response_code( $api_response );

	$response = array(
		'status' => $status,
		'data'   => array(),
	);

	if ( 200 === $status ) {

		$api_body = json_decode( wp_remote_retrieve_body( $api_response ) );
		$segments = tw_episode_importer_parse_api_items( $api_body, 'segment' );

		$response['status'] = 200;
		$response['data']   = $segments ?? array();

	}

	return tw_episode_importer_get_response( $response );
}

/**
 * API Segments rollback route callback.
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response
 */
// phpcs:ignore
function tw_episode_importer_api_route_segments_rollback( WP_REST_Request $request ) {

	// Request API data.
	$settings           = get_option( TW_EPISODE_IMPORTER_SETTINGS_API );
	$api_url            = $settings[ TW_EPISODE_IMPORTER_SEGMENTS_API_URL_KEY ];
	$after_date_string  = $request->get_param( 'after' );
	$before_date_string = $request->get_param( 'before' );
	$on_date_string     = $request->get_param( 'on' );
	$on_date            = $on_date_string ? new DateTime( $on_date_string ) : new DateTime();
	$one_day            = new DateInterval( 'P1D' );
	$after              = $after_date_string ? $after_date_string : $on_date->format( 'Y-m-d' );
	$before             = $before_date_string ? $before_date_string : $on_date->add( $one_day )->format( 'Y-m-d' );
	$api_response       = wp_remote_get( $api_url . '?after=' . $after . '&before=' . $before . '&per=365' );
	$status             = wp_remote_retrieve_response_code( $api_response );

	$response = array(
		'status' => $status,
		'data'   => array(),
	);

	if ( 200 === $status ) {

		$api_body = json_decode( wp_remote_retrieve_body( $api_response ) );
		$segments = tw_episode_importer_parse_api_items( $api_body, 'segment' );

		$segments = tw_episode_importer_rollback_segments( $segments );

		$response['status'] = 200;
		$response['data']   = $segments ?? array();

	}

	return tw_episode_importer_get_response( $response );
}

/**
 * API Segment READABLE route callback.
 *
 * @param WP_REST_Request $request This function accepts a rest request to process data.
 * @return WP_REST_Response
 */
function tw_episode_importer_api_route_segment_get( WP_REST_Request $request ) {

	// Request API data.
	$settings     = get_option( TW_EPISODE_IMPORTER_SETTINGS_API );
	$id           = $request->get_param( 'id' );
	$api_url      = $settings[ TW_EPISODE_IMPORTER_SEGMENTS_API_URL_KEY ] . '/' . $id;
	$api_response = wp_remote_get( $api_url );
	$status       = wp_remote_retrieve_response_code( $api_response );

	$response = array(
		'status' => $status,
		'data'   => array(),
	);

	if ( 200 === $status ) {
		$api_body = json_decode( wp_remote_retrieve_body( $api_response ) );
		$segment  = tw_episode_importer_parse_api_item( $api_body, 'segment' );

		$response['data'] = $segment ?? array();
	}

	return tw_episode_importer_get_response( $response );
}

/**
 * API Segment CREATABLE route callback.
 *
 * @param WP_REST_Request $request This function accepts a rest request to process data.
 * @return WP_REST_Response
 */
function tw_episode_importer_api_route_segment_create( WP_REST_Request $request ) {

	// Request API data.
	$settings     = get_option( TW_EPISODE_IMPORTER_SETTINGS_API );
	$id           = $request->get_param( 'id' );
	$api_url      = $settings[ TW_EPISODE_IMPORTER_SEGMENTS_API_URL_KEY ] . '/' . $id;
	$api_response = wp_remote_get( $api_url );
	$status       = wp_remote_retrieve_response_code( $api_response );

	$response = array(
		'status' => $status,
		'data'   => array(),
	);

	if ( 200 === $status ) {
		$api_body = json_decode( wp_remote_retrieve_body( $api_response ) );
		$segment  = tw_episode_importer_parse_api_item( $api_body, 'segment' );
		$body     = $request->get_body();
		$options  = json_decode( $body );

		if ( $segment ) {
			tw_episode_importer_audio_create( $segment, 'program-segment', $options );
			tw_episode_importer_segment_create( $segment, $options );
		}

		$response['data'] = $segment ?? array();
	}

	return tw_episode_importer_get_response( $response );
}

/**
 * API Segment EDITABLE route callback.
 *
 * @param WP_REST_Request $request This function accepts a rest request to process data.
 * @return WP_REST_Response
 */
function tw_episode_importer_api_route_segment_update( WP_REST_Request $request ) {

	// Request API data.
	$settings     = get_option( TW_EPISODE_IMPORTER_SETTINGS_API );
	$id           = $request->get_param( 'id' );
	$api_url      = $settings[ TW_EPISODE_IMPORTER_SEGMENTS_API_URL_KEY ] . '/' . $id;
	$api_response = wp_remote_get( $api_url );
	$status       = wp_remote_retrieve_response_code( $api_response );

	$response = array(
		'status' => $status,
		'data'   => array(),
	);

	if ( 200 === $status ) {
		$api_body = json_decode( wp_remote_retrieve_body( $api_response ) );
		$segment  = tw_episode_importer_parse_api_item( $api_body, 'segment' );
		$body     = $request->get_body();
		$options  = json_decode( $body );

		if ( $segment ) {
			tw_episode_importer_audio_update( $segment, 'program-segment', $options );
			tw_episode_importer_segment_update( $segment, $options );
		}

		$response['data'] = $segment ?? array();
	}

	return tw_episode_importer_get_response( $response );
}

/**
 * API Segment DELETABLE route callback.
 *
 * @param WP_REST_Request $request This function accepts a rest request to process data.
 * @return WP_REST_Response
 */
function tw_episode_importer_api_route_segment_delete( WP_REST_Request $request ) {

	// Request API data.
	$settings     = get_option( TW_EPISODE_IMPORTER_SETTINGS_API );
	$id           = $request->get_param( 'id' );
	$api_url      = $settings[ TW_EPISODE_IMPORTER_SEGMENTS_API_URL_KEY ] . '/' . $id;
	$api_response = wp_remote_get( $api_url );
	$status       = wp_remote_retrieve_response_code( $api_response );

	$response = array(
		'status' => $status,
		'data'   => array(),
	);

	if ( 200 === $status ) {
		$api_body = json_decode( wp_remote_retrieve_body( $api_response ) );
		$segment  = tw_episode_importer_parse_api_item( $api_body, 'segment' );
		$body     = $request->get_body();
		$options  = json_decode( $body );

		if ( $segment ) {
			// Always delete audio unless flagged not to.
			$delete_audio = property_exists( $options, 'deleteAudio' ) ? $options->deleteAudio : true; //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			if ( $delete_audio ) {
				tw_episode_importer_audio_delete( $segment );
			}
			// Delete parent if flagged to.
			$delete_parent = property_exists( $options, 'deleteParent' ) ? $options->deleteParent : false; //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			if ( $delete_parent && $segment['existingPost'] ) {
				tw_episode_importer_segment_delete( $segment );
			}
		}

		$response['data'] = $segment ?? array();
	}

	return tw_episode_importer_get_response( $response );
}

/**
 * Create media post for item audio, and update items existing audio prop.
 *
 * @param array  $item Import item too create media post for and update.
 * @param string $audio_type Type of audio being created.
 * @param object $options Request data.
 * @return void
 */
function tw_episode_importer_audio_create( &$item, $audio_type, $options ) {
	if ( $item['existingAudio'] ) {
		return;
	}

	$segment_ids     = property_exists( $options, 'segments' ) ? $options->segments : null;
	$tax_input       = property_exists( $options, 'terms' ) ? (array) $options->terms : array();
	$contributor_ids = $tax_input && isset( $tax_input['contributor'] ) ? $tax_input['contributor'] : null;
	$settings        = get_option( TW_EPISODE_IMPORTER_SETTINGS_API );
	$author_id       = (int) $settings[ TW_EPISODE_IMPORTER_AUTHOR_USER_ID_KEY ];
	$program_id      = (int) $settings[ TW_EPISODE_IMPORTER_PROGRAM_ID_KEY ];
	$enclosure       = $item['enclosure'];
	$date_broadcast  = new DateTime( $item['dateBroadcast'] );
	$date_today      = new DateTime();

	$args = array(
		'guid'           => $item['guid'],
		'post_date'      => $date_broadcast->format( 'Y-m-d H:i:s' ),
		'post_modified'  => $date_today->format( 'Y-m-d H:i:s' ),
		'post_author'    => $author_id,
		'post_title'     => $enclosure['audioKey'],
		'post_mime_type' => $enclosure['type'],
		'post_content'   => $item['content'],
		'meta_input'     => array(
			'original_uri'   => $enclosure['href'],
			'audio_title'    => $item['title'],
			'audio_type'     => $audio_type,
			'broadcast_date' => $date_broadcast->format( 'Y-m-d H:i:s' ),
			'program'        => $program_id,
		),
	);

	if ( 'program-episode' === $audio_type && $segment_ids ) {
		$args['meta_input']['segments_list'] = $segment_ids;
	}

	if ( is_array( $contributor_ids ) ) {
		$args['tax_input'] = array(
			'contributor' => $contributor_ids,
		);
	} elseif ( isset( $item['author'] ) && is_array( $item['author'] ) ) {
		$contributor_ids   = array_map(
			fn ( $author ) => isset( $author['id'] ) ? $author['id'] : $author['name'],
			$item['author']
		);
		$args['tax_input'] = array(
			'contributor' => $contributor_ids,
		);
	}

	$audio_id = wp_insert_attachment( $args, $enclosure['filename'] );

	if ( $audio_id ) {
		// Cache ID for the item guid.
		$audio_id_cache_key = TW_EPISODE_IMPORTER_CACHE_AUDIO_ID_KEY_PREFIX . ':' . $item['guid'];
		wp_cache_set( $audio_id_cache_key, $audio_id, TW_EPISODE_IMPORTER_CACHE_GROUP );

		// Overwrite GUID created by WP to Dovetail GUID.
		global $wpdb;
		$wpdb->update(
			$wpdb->posts,
			array(
				'guid' => $item['guid'],
			),
			array( 'ID' => $audio_id )
		);

		// Update audio metadata.
		$metadata             = wp_get_attachment_metadata( $audio_id );
		$metadata             = $metadata ? $metadata : array();
		$metadata['file']     = $enclosure['filename'];
		$metadata['filesize'] = $enclosure['size'];
		$metadata['duration'] = $enclosure['duration'];

		wp_update_attachment_metadata( $audio_id, $metadata );

		// Add audio data to import item.
		$audio_post     = get_post( $audio_id );
		$audio_metadata = get_metadata( 'post', $audio_id );

		$item['existingAudio'] = array(
			'guid'          => $item['guid'],
			'databaseId'    => $audio_post->ID,
			'editLink'      => get_edit_post_link( $audio_post, 'link' ),
			'datePublished' => $audio_post->post_date,
			'dateUpdated'   => $audio_post->post_modified,
			'url'           => $audio_metadata['original_uri'][0],
		);

		// Ensure parent posts reference the new audio post ID.
		if ( is_array( $item['existingPosts'] ) ) {
			foreach ( $item['existingPosts'] as $post_data ) {
				$pid = $post_data['databaseId'];
				wp_update_post(
					array(
						'ID'         => $pid,
						'meta_input' => array(
							'audio' => $audio_id,
						),
					)
				);
			}
		}
	}
}

/**
 * Update item's existing audio post.
 *
 * @param array  $item Import item being updated.
 * @param string $audio_type Type of audio being updated.
 * @param object $options Request data.
 * @return void
 */
function tw_episode_importer_audio_update( &$item, $audio_type, $options ) {
	if ( ! $item['existingAudio'] ) {
		return;
	}

	$segment_ids          = property_exists( $options, 'segments' ) ? $options->segments : null;
	$audio_id             = $item['existingAudio']['databaseId'];
	$enclosure            = $item['enclosure'];
	$metadata             = wp_get_attachment_metadata( $audio_id );
	$metadata             = $metadata ? $metadata : array();
	$metadata['file']     = $enclosure['filename'];
	$metadata['filesize'] = $enclosure['size'];
	$metadata['duration'] = $enclosure['duration'];
	$date_broadcast       = new DateTime( $item['dateBroadcast'] );

	wp_update_attachment_metadata( $audio_id, $metadata );

	$args = array(
		'ID'         => $audio_id,
		'post_title' => $enclosure['filename'],
		'meta_input' => array(
			'original_uri'   => $item['enclosure']['href'],
			'broadcast_date' => $date_broadcast->format( 'Y-m-d H:i:s' ),
		),
	);

	if ( 'program-episode' === $audio_type && $segment_ids ) {
		$args['meta_input']['segments_list'] = $segment_ids;
	}

	wp_update_post( $args );

	$audio_metadata = get_metadata( 'post', $audio_id );

	$item['existingAudio']['url'] = $audio_metadata['original_uri'][0];
}

/**
 * Delete item's existing audio post.
 *
 * @param array $item Import item being updated.
 * @return void
 */
function tw_episode_importer_audio_delete( &$item ) {
	$settings  = get_option( TW_EPISODE_IMPORTER_SETTINGS_API );
	$author_id = (int) $settings[ TW_EPISODE_IMPORTER_AUTHOR_USER_ID_KEY ];

	// Bail when there is no existing audio or the existing audio was not created by the importer.
	if ( ! $item['existingAudio'] || ! $item['existingAudio']['imported'] ) {
		return;
	}

	$audio_id = $item['existingAudio']['databaseId'];

	if ( wp_delete_attachment( $audio_id, true ) ) {
		$item['existingAudio']   = null;
		$item['wasImported']     = false;
		$item['hasUpdatedAudio'] = false;
	}
}

/**
 * Create segment from item data.
 *
 * @param array  $item Import item to create segment from.
 * @param object $options Request data.
 * @return void
 */
function tw_episode_importer_segment_create( &$item, $options ) {
	if ( $item['existingPost'] ) {
		return;
	}

	$tax_input       = property_exists( $options, 'terms' ) ? (array) $options->terms : array();
	$contributor_ids = $tax_input && isset( $tax_input['contributor'] ) ? $tax_input['contributor'] : null;
	$settings        = get_option( TW_EPISODE_IMPORTER_SETTINGS_API );
	$author_id       = (int) $settings[ TW_EPISODE_IMPORTER_AUTHOR_USER_ID_KEY ];
	$program_id      = (int) $settings[ TW_EPISODE_IMPORTER_PROGRAM_ID_KEY ];
	$audio_id        = $item['existingAudio']['databaseId'];
	$date_broadcast  = new DateTime( $item['dateBroadcast'] );
	$date_today      = new DateTime();

	$tax_input['program'][] = $program_id;

	if ( is_array( $contributor_ids ) ) {
		$tax_input['contributor'] = $contributor_ids;
	} elseif ( ! is_array( $contributor_ids ) && isset( $item['author'] ) && is_array( $item['author'] ) ) {
		$contributor_ids          = array_map(
			fn ( $author ) => isset( $author['id'] ) ? $author['id'] : $author['name'],
			$item['author']
		);
		$tax_input['contributor'] = $contributor_ids;
	}

	$args = array(
		'post_date'     => $date_broadcast->format( 'Y-m-d H:i:s' ),
		'post_modified' => $date_today->format( 'Y-m-d H:i:s' ),
		'post_author'   => $author_id,
		'post_type'     => 'segment',
		'post_title'    => $item['title'],
		'post_content'  => $item['content'],
		'post_status'   => 'publish',
		'tax_input'     => $tax_input,
		'meta_input'    => array(
			'audio'          => $audio_id,
			'broadcast_date' => $date_broadcast->format( 'Y-m-d H:i:s' ),
		),
	);

	$segment_id = wp_insert_post( $args );

	if ( $segment_id ) {
		// Cache ID for the item guid.
		$post_ids_cache_key = TW_EPISODE_IMPORTER_CACHE_POST_IDS_KEY_PREFIX . ':' . $item['guid'];
		$ids                = wp_cache_get( $post_ids_cache_key, TW_EPISODE_IMPORTER_CACHE_GROUP );

		if ( ! $ids ) {
			$ids = array();
		}

		array_unshift( $ids, $segment_id );
		wp_cache_set( $post_ids_cache_key, $ids, TW_EPISODE_IMPORTER_CACHE_GROUP );

		// Update item authors with created contributor ids.
		if ( is_array( $item['author'] ) ) {
			$item['author'] = array_map(
				function ( $author ) {
					if ( ! isset( $author['id'] ) ) {
						$contributor_terms = get_terms(
							array(
								'taxonomy' => 'contributor',
								'name'     => $author['name'],
							)
						);

						if ( ! empty( $contributor_terms ) ) {
							$contributor_term = $contributor_terms[0];
							$author['id']     = $contributor_term->term_id;
						}
					}

					return $author;
				},
				$item['author']
			);
		}

		// Add existing post to import item.
		$post          = get_post( $segment_id );
		$existing_post = array(
			'guid'          => $post->guid,
			'databaseId'    => $post->ID,
			'type'          => $post->post_type,
			'status'        => $post->post_status,
			'imported'      => (int) $post->post_author === $author_id,
			'editLink'      => get_edit_post_link( $post, 'link' ),
			'datePublished' => $post->post_date,
			'dateUpdated'   => $post->post_modified,
		);

		$item['wasImported']     = true;
		$item['existingPost']    = $existing_post;
		$item['existingPosts'][] = $existing_post;
	}
}

/**
 * Update segment from item data.
 *
 * @param array  $item Import item to create episode from.
 * @param object $options Request data.
 * @return void
 */
function tw_episode_importer_segment_update( &$item, $options ) {
	if ( ! $item['existingPost'] ) {
		return;
	}

	// TODO: Use options data to update episode post. Probably editing of terms and contibutors.
}

/**
 * Delete segment's existing post.
 *
 * @param array $segment Import item being updated.
 * @return void
 */
function tw_episode_importer_segment_delete( &$segment ) {
	$settings  = get_option( TW_EPISODE_IMPORTER_SETTINGS_API );
	$author_id = (int) $settings[ TW_EPISODE_IMPORTER_AUTHOR_USER_ID_KEY ];

	// Bail when there is no existing post or the existing post was not created by the importer.
	if ( ! $segment['existingPost'] || ! $segment['existingPost']['imported'] ) {
		return;
	}

	$segment_id = $segment['existingPost']['databaseId'];

	if ( wp_delete_post( $segment_id, true ) ) {
		$segment['existingPost']  = null;
		$existing_posts           = array_filter(
			$segment['existingPosts'],
			function ( $p ) use ( $segment_id ) {
				return $p['databaseId'] !== $segment_id;
			}
		);
		$segment['existingPosts'] = ! empty( $existing_posts ) ? $existing_posts : null;
	}
}

/**
 * Create episode from item data.
 *
 * @param array  $item Import item to create episode from.
 * @param object $options Request data.
 * @return void
 */
function tw_episode_importer_episode_create( &$item, $options ) {
	if ( $item['existingPost'] ) {
		return;
	}

	$tax_input      = property_exists( $options, 'terms' ) ? (array) $options->terms : array();
	$settings       = get_option( TW_EPISODE_IMPORTER_SETTINGS_API );
	$author_id      = (int) $settings[ TW_EPISODE_IMPORTER_AUTHOR_USER_ID_KEY ];
	$program_id     = (int) $settings[ TW_EPISODE_IMPORTER_PROGRAM_ID_KEY ];
	$audio_id       = $item['existingAudio']['databaseId'];
	$date_broadcast = new DateTime( $item['dateBroadcast'] );
	$date_today     = new DateTime();

	$tax_input['program'][] = $program_id;

	$args = array(
		'post_date'     => $date_broadcast->format( 'Y-m-d H:i:s' ),
		'post_modified' => $date_today->format( 'Y-m-d H:i:s' ),
		'post_author'   => $author_id,
		'post_type'     => 'episode',
		'post_title'    => $item['title'],
		'post_content'  => $item['content'],
		'tax_input'     => $tax_input,
		'meta_input'    => array(
			'audio'          => $audio_id,
			'broadcast_date' => $date_broadcast->format( 'Y-m-d H:i:s' ),
		),
	);

	$episode_id = wp_insert_post( $args );

	if ( $episode_id ) {
		// Cache ID for the item guid.
		$post_ids_cache_key = TW_EPISODE_IMPORTER_CACHE_POST_IDS_KEY_PREFIX . ':' . $item['guid'];
		$ids                = wp_cache_get( $post_ids_cache_key, TW_EPISODE_IMPORTER_CACHE_GROUP );

		if ( ! $ids ) {
			$ids = array();
		}

		array_unshift( $ids, $episode_id );
		wp_cache_set( $post_ids_cache_key, $ids, TW_EPISODE_IMPORTER_CACHE_GROUP );

		// Set episode as audio attachment parent.
		wp_update_post(
			array(
				'ID'          => $item['existingAudio']['databaseId'],
				'post_parent' => $episode_id,
			)
		);

		// Update item authors with created contributor ids.
		if ( is_array( $item['author'] ) ) {
			$item['author'] = array_map(
				function ( $author ) {
					if ( ! isset( $author['id'] ) ) {
						$contributor_terms = get_terms(
							array(
								'taxonomy' => 'contributor',
								'name'     => $author['name'],
							)
						);

						if ( ! empty( $contributor_terms ) ) {
							$contributor_term = $contributor_terms[0];
							$author['id']     = $contributor_term->term_id;
						}
					}

					return $author;
				},
				$item['author']
			);
		}

		// Add existing post to import item.
		$post          = get_post( $episode_id );
		$existing_post = array(
			'guid'          => $post->guid,
			'databaseId'    => $post->ID,
			'type'          => $post->post_type,
			'status'        => $post->post_status,
			'imported'      => (int) $post->post_author === $author_id,
			'editLink'      => get_edit_post_link( $post, 'link' ),
			'datePublished' => $post->post_date,
			'dateUpdated'   => $post->post_modified,
		);

		$item['wasImported']     = true;
		$item['existingPost']    = $existing_post;
		$item['existingPosts'][] = $existing_post;
	}
}

/**
 * Update episode from item data.
 *
 * @param array  $item Import item to create episode from.
 * @param object $options Request data.
 * @return void
 */
function tw_episode_importer_episode_update( &$item, $options ) {
	if ( ! $item['existingPost'] ) {
		return;
	}

	// TODO: Use options data to update episode post. Probably editing of terms and contibutors.
}

/**
 * Delete episode's existing post.
 *
 * @param array $episode Import item being updated.
 * @return void
 */
function tw_episode_importer_episode_delete( &$episode ) {
	$settings  = get_option( TW_EPISODE_IMPORTER_SETTINGS_API );
	$author_id = (int) $settings[ TW_EPISODE_IMPORTER_AUTHOR_USER_ID_KEY ];

	// Bail when there is no existing post or the existing post was not created by the importer.
	if ( ! $episode['existingPost'] || ! $episode['existingPost']['imported'] ) {
		return;
	}

	$episode_id = $episode['existingPost']['databaseId'];

	if ( wp_delete_post( $episode_id, true ) ) {
		$episode['existingPost']  = null;
		$existing_posts           = array_filter(
			$episode['existingPosts'],
			function ( $p ) use ( $episode_id ) {
				return $p['databaseId'] !== $episode_id;
			}
		);
		$episode['existingPosts'] = ! empty( $existing_posts ) ? $existing_posts : null;
	}
}

/**
 * Rollback import of segments by deleting the audio and segment posts associated with the Dovetail episode.
 *
 * @param array $segments Segments to be rolled back.
 * @return array Updated segments data.
 */
function tw_episode_importer_rollback_segments( $segments ) {
	$result = array();

	foreach ( $segments as $segment ) {
		// Delete audio and segment.
		tw_episode_importer_segment_delete( $segment );
		tw_episode_importer_audio_delete( $segment );
		$result[] = $segment;

		// Remove caches related to this guid.
		$guid               = $segment['guid'];
		$audio_id_cache_key = TW_EPISODE_IMPORTER_CACHE_AUDIO_ID_KEY_PREFIX . ':' . $guid;
		wp_cache_delete( $audio_id_cache_key, TW_EPISODE_IMPORTER_CACHE_GROUP );
	}

	return $result;
}
/**
 * Rollback import of episodes by deleting the audio and episode posts associated with the Dovetail episode.
 *
 * @param array $episodes Episodes to be rolled back.
 * @return array Updated episodes data.
 */
function tw_episode_importer_rollback_episodes( $episodes ) {
	$result = array();

	foreach ( $episodes as $episode ) {
		// Delete audio and episode.
		tw_episode_importer_episode_delete( $episode );
		tw_episode_importer_audio_delete( $episode );
		$result[] = $episode;

		// Remove caches related to this guid.
		$guid               = $episode['guid'];
		$audio_id_cache_key = TW_EPISODE_IMPORTER_CACHE_AUDIO_ID_KEY_PREFIX . ':' . $guid;
		wp_cache_delete( $audio_id_cache_key, TW_EPISODE_IMPORTER_CACHE_GROUP );
	}

	return $result;
}

/**
 * Permissions check for import routes.
 *
 * @return boolean
 */
function tw_episode_importer_api_route_import_permissions_check() {
	return current_user_can( 'edit_posts' ) && current_user_can( 'publish_posts' ) && current_user_can( 'delete_posts' ) && current_user_can( 'upload_files' );
}

/**
 * Get taxonomies data.
 *
 * @return WP_REST_Response
 */
function tw_episode_importer_api_route_taxonomies() {

	// Get public taxonomies.
	$taxonomies = get_taxonomies(
		array(
			'public' => true,
		),
		'objects'
	);

	// Get private taxonomies we want to map categories to.
	$taxonomies['resource_development'] = get_taxonomy( 'resource_development' );

	ksort( $taxonomies );
	$data = array_map(
		fn( $taxonomy ) => array(
			'name'   => $taxonomy->name,
			'label'  => $taxonomy->label,
			'labels' => $taxonomy->labels,
		),
		$taxonomies
	);

	// Unset this taxonomy since we want to keep `post_tags` and
	// quering for `'_builtin' = false` would not include it in the results.
	unset( $data['post_format'] );

	$response = array(
		'status' => 200,
		'data'   => $data,
	);

	return tw_episode_importer_get_response( $response );
}

/**
 * Parse API item into normalized data.
 *
 * @param array  $api_item Item from API request.
 * @param string $post_type Post type to check for existing data.
 * @return array Normalized item data as associative array.
 */
function tw_episode_importer_parse_api_item( $api_item, $post_type ) {

	$guid                     = $api_item->guid;
	$title                    = $api_item->title;
	$date_published           = $api_item->publishedAt; //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$date_updated             = $api_item->updatedAt; //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$audio_url                = $api_item->_links->enclosure->href;
	$audio_url_segments       = explode( '/', $audio_url );
	$audio_filename           = array_pop( $audio_url_segments );
	list($audio_name)         = explode( '.', $audio_filename );
	$episode_key              = null;
	$audio_key                = null;
	$audio_year               = null;
	$audio_month              = null;
	$audio_day                = null;
	$audio_segment            = null;
	$audio_version            = 'original';
	$audio_broadcast_date_key = null;
	$audio_broadcast_date     = null;
	$audio_name_matches       = array();

	if ( preg_match( '~^.*?((\d{4})[_-]?(\d{1,2})[_-]?(\d{1,2})(?:[_-](\d{1,2}|seg[_-]?\d|full|.+)))(?:[_-]?(.+))?$~i', $audio_name, $audio_name_matches ) ) {
		if ( count( $audio_name_matches ) > 6 ) {
			list(, $audio_key, $audio_year, $audio_month, $audio_day, $audio_segment, $audio_version) = $audio_name_matches;
		} else {
			list(, $audio_key, $audio_year, $audio_month, $audio_day, $audio_segment) = $audio_name_matches;
		}

		$episode_key_segments = array(
			$audio_year,
			$audio_month,
			$audio_day,
		);

		if ( is_numeric( $audio_segment ) || preg_match( '~^seg[_-]?\d|full$~i', $audio_segment ) ) {
			$episode_key_segments[] = 'TW';
		} else {
			$episode_key_segments[] = strtoupper( $audio_segment );
		}

		$episode_key = implode( '_', $episode_key_segments );

		$audio_broadcast_date_key = $audio_year . '-' . $audio_month . '-' . $audio_day;
		$audio_broadcast_date     = new DateTime( $audio_broadcast_date_key );
		$audio_query              = new WP_Query(
			array(
				'post_type'              => 'attachment',
				'name'                   => $audio_key,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);
		$audio_post               = $audio_query->have_posts() ? reset( $audio_query->posts ) : null;
	}

	$posts              = tw_episode_importer_get_existing_post_data( $post_type, $guid, $audio_key );
	$audio              = is_array( $posts ) && isset( $posts['audio'] ) ? $posts['audio'] : null;
	$posts              = is_array( $posts ) && isset( $posts['posts'] ) ? $posts['posts'] : null;
	$post               = $posts ? array_filter(
		$posts,
		function ( $p ) use ( $post_type ) {
			return $p['type'] === $post_type;
		}
	) : null;
	$post               = is_array( $post ) && ! empty( $post ) ? array_shift( $post ) : null;
	$was_imported       = ( ! $audio_broadcast_date_key || is_array( $post ) ) && is_array( $audio ) ? true : false;
	$audio_url_segments = isset( $audio['url'] ) ? explode( '/', $audio['url'] ) : array();
	$audio_is_different = is_array( $audio ) && isset( $audio['url'] ) && array_pop( $audio_url_segments ) !== $audio_filename;
	$has_updated_audio  = is_array( $audio ) &&
		isset( $audio['dateUpdated'] ) &&
		$audio_is_different;

	$item = array(
		'existingPosts'   => $posts,
		'existingPost'    => $post,
		'existingAudio'   => $audio,
		'wasImported'     => $was_imported,
		'hasUpdatedAudio' => $has_updated_audio,
		'type'            => $post_type,
		'id'              => $api_item->id,
		'guid'            => $guid,
		'title'           => trim( $title ),
		'excerpt'         => property_exists( $api_item, 'subtitle' ) ? trim( $api_item->subtitle ) : null,
		'content'         => property_exists( $api_item, 'description' ) ? trim( $api_item->description ) : null,
		'datePublished'   => ( new DateTime( $date_published ) )->format( 'c' ),
		'dateUpdated'     => $date_updated ? ( new DateTime( $date_published ) )->format( 'c' ) : null,
		'dateBroadcast'   => $audio_broadcast_date ? $audio_broadcast_date->format( 'c' ) : null,
		'dateKey'         => $audio_broadcast_date_key,
		'author'          => property_exists( $api_item, 'author' ) && property_exists( $api_item->author, 'name' ) ? (array) $api_item->author : null,
		'enclosure'       => array_merge(
			(array) $api_item->_links->enclosure,
			array(
				'filename'   => $audio_filename,
				'episodeKey' => $episode_key,
				'audioKey'   => $audio_key ? $audio_key : $audio_name,
				'segment'    => (int) $audio_segment,
				'version'    => $audio_version,
			)
		),
	);

	if ( $item['author'] ) {
		$item['author'] = array_map(
			function ( $contributor_name ) {
				$contributor_terms = get_terms(
					array(
						'taxonomy'   => 'contributor',
						'name'       => $contributor_name,
						'hide_empty' => false,
					)
				);

				if ( empty( $contributor_terms ) ) {
					return array( 'name' => $contributor_name );
				}

				$contributor_term = $contributor_terms[0];
				$author           = array(
					'id'   => $contributor_term->term_id,
					'name' => $contributor_term->name,
				);

				$contributor_image = get_field( 'image', 'contributor_' . $contributor_term->term_id );

				if ( is_numeric( $contributor_image ) ) {
					$contributor_image = wp_get_attachment_image_url( $contributor_image );
				}

				if ( $contributor_image ) {
					$author['image'] = is_array( $contributor_image ) ? $contributor_image['url'] : $contributor_image;
				}

				return $author;
			},
			preg_split( '~,\s?|\sand\s~i', $item['author']['name'] )
		);
	}

	$categories         = $api_item->categories;
	$item['categories'] = ! $categories || empty( $categories ) ? null : array_map(
		function ( $term_name ) {
			$result = array(
				'name' => $term_name,
			);

			// Get existing terms by the same name.
			$args  = array(
				'name'       => trim( $term_name ),
				'hide_empty' => false,
			);
			$terms = array_values( get_terms( $args ) );

			// Pick the needed props for the term and extend taxonomy data.
			if ( $terms && ! empty( $terms ) ) {
				$result['existingTerms'] = array_map(
					fn( $term ) => array_merge(
						array( 'id' => $term->term_id ),
						array_intersect_key( (array) $term, array_flip( array( 'name', 'taxonomy', 'count' ) ) ),
						array( 'taxonomy' => array_intersect_key( (array) get_taxonomy( $term->taxonomy ), array_flip( array( 'name', 'label', 'labels' ) ) ) )
					),
					$terms
				);
			}

			return $result;
		},
		// Ensure terms with comma separated terms are broken out into separate terms.
		// TODO: Remove this split logic when feeder is updated to treat comma separated input as separate terms.
		array_unique(
			array_reduce(
				$categories,
				fn( $carry, $term_name ) => array_merge( $carry, explode( ',', $term_name ) ),
				array()
			)
		)
	);

	return $item;
}

/**
 * Parse API response body into normalized data.
 *
 * @param array  $api_body Response body from API request.
 * @param string $post_type Post type to check for existing data.
 * @return array Normalized item data as associative arrays.
 */
function tw_episode_importer_parse_api_items( $api_body, $post_type ) {

	$items = $api_body->_embedded->{'prx:items'};

	if ( ! $items || empty( $items ) ) {
		return null;
	}

	$episodes = array_map(
		function ( $item ) use ( $post_type ) {
			return tw_episode_importer_parse_api_item( $item, $post_type );
		},
		$items
	);

	return $episodes;
}

/**
 * Get post id using GUID.
 *
 * @param string $post_type Post type to lookup ID for.
 * @param string $guid Post guid to lookup ID for.
 * @param string $audio_key Audio key to lookup ID for.
 * @return array|null
 */
function tw_episode_importer_get_existing_post_data( $post_type, $guid, $audio_key ) {
	global $wpdb;

	$settings             = get_option( TW_EPISODE_IMPORTER_SETTINGS_API );
	$author_id            = (int) $settings[ TW_EPISODE_IMPORTER_AUTHOR_USER_ID_KEY ];
	$post_ids_cache_key   = TW_EPISODE_IMPORTER_CACHE_POST_IDS_KEY_PREFIX . ':' . $guid;
	$audio_id_cache_key   = TW_EPISODE_IMPORTER_CACHE_AUDIO_ID_KEY_PREFIX . ':' . $guid;
	$ids                  = wp_cache_get( $post_ids_cache_key, TW_EPISODE_IMPORTER_CACHE_GROUP );
	$audio_id             = wp_cache_get( $audio_id_cache_key, TW_EPISODE_IMPORTER_CACHE_GROUP );
	$audio_broadcast_date = null;
	$audio_post           = null;
	$audio_metadata       = null;
	$post                 = null;
	$result               = null;

	if ( $audio_id ) {
		$audio_post = get_post( $audio_id );

		if ( ! $audio_post ) {
			// Post for the cached id no longer exists. Remove cache.
			wp_cache_delete( $audio_id_cache_key, TW_EPISODE_IMPORTER_CACHE_GROUP );
		}
	}

	// Attempt to get imported audio by guid.
	if ( is_null( $audio_post ) ) {
		$audio_id   = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid IN (%s, %s)", array( "{$guid}", "http://{$guid}" ) ) );
		$audio_post = get_post( $audio_id );
	}

	// At this point, if we don't have an audio post, this is either new audio, or from a date prior to the importer being used.
	// Attempt to get existing audio by using audio key derived from filename.
	if ( is_null( $audio_post ) && $audio_key ) {
			$row = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT p.*
					FROM wp_posts p
					WHERE p.post_name LIKE %s AND p.post_type = %s;',
					array( preg_replace( '~(?<=^\d{4}[_-]\d{2})[_-]?(?=\d\d[_-])~', '%', $audio_key ) . '%', 'attachment' ),
				)
			);

			// Only use post found using filename if its guid is from WordPress.
			// WordPress guids are URL's. Dovetail guids are UUID's, though WordPress could have added a protocol to the UUID when creating the post.
			// WordPress guids could have different domains depending on what site it was created on at what time, but will always contain a period.
			// Dovetail guids should not contain a period by default.
		if ( $row && strpos( $row->guid, '.' ) >= 0 ) {
			// The found post is not from a previous import and should be safe to work with.
			$audio_id   = $row->ID;
			$audio_post = $row;
		}
	}

	// At this point, if we have an audio post, add it to the results and ID cache.
	if ( ! is_null( $audio_post ) ) {
		$audio_metadata  = get_metadata( 'post', $audio_post->ID );
		$audio_url       = isset( $audio_metadata['original_uri'] ) ? $audio_metadata['original_uri'][0] : null;
		$result['audio'] = array(
			'guid'          => $audio_post->guid,
			'databaseId'    => $audio_post->ID,
			'imported'      => (int) $audio_post->post_author === $author_id,
			'editLink'      => get_edit_post_link( $audio_post, 'link' ),
			'datePublished' => $audio_post->post_date,
			'dateUpdated'   => $audio_post->post_modified,
			'url'           => $audio_url,
		);
		wp_cache_set( $audio_id_cache_key, $audio_post->ID, TW_EPISODE_IMPORTER_CACHE_GROUP );
	}

	// Get ids of parent posts that audio is attached to via their 'audio' meta field.
	if ( empty( $ids ) && ! is_null( $audio_post ) ) {
		$posts_query = new WP_Query(
			array(
				// Audio will always be attached to the episode or segment post,
				// but may also be attached to a story post.
				'post_type'              => array( $post_type, 'post' ),
				'post_status'            => 'any',
				'meta_key'               => 'audio',
				'meta_value'             => $audio_post->ID,
				'fields'                 => 'ids',
				'orderby'                => 'type',
				'update_post_term_cache' => false,
			)
		);

		if ( $posts_query->have_posts() ) {
			$ids = $posts_query->posts;
			wp_cache_set( $post_ids_cache_key, $ids, TW_EPISODE_IMPORTER_CACHE_GROUP );
		}
	}

	// Add parent posts' data to results.
	if ( is_array( $ids ) && ! empty( $ids ) ) {
		// At this point, ids will have been newly cached or came from a cache.

		// Parent posts may have been deleted after id's were cached. Create a list of ids to keep in cache.
		$cached_ids = array();

		$posts_args   = array(
			'include'                => $ids,
			'post_type'              => array( $post_type, 'post' ),
			'post_status'            => 'any',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);
		$parent_posts = get_posts( $posts_args );

		foreach ( $parent_posts as $post ) {
			$post_data = array(
				'guid'          => $post->guid,
				'databaseId'    => $post->ID,
				'type'          => $post->post_type,
				'status'        => $post->post_status,
				'imported'      => (int) $post->post_author === $author_id,
				'editLink'      => get_edit_post_link( $post, 'link' ),
				'datePublished' => $post->post_date,
				'dateUpdated'   => $post->post_modified,
			);

			$post_audio_id = get_field( 'audio', $post->ID );

			if ( is_array( $post_audio_id ) ) {
				$post_audio_id = $post_audio_id['ID'];
			}

			// Include post if:
			// - Audio ID is missing at this point. Means it was probably imported then deleted via the admin.
			// - If the audio ID matches the currently referenced ID, meaning the parent's audio wasn't changed to another.
			$include_post = ! $audio_id || (int) $post_audio_id === (int) $audio_id;
			if ( $include_post ) {
				// Add post data to results and cache id.
				$result['posts'][] = $post_data;
				$cached_ids[]      = $post->ID;
			}
		}

		// Update cached post ids in case audio was detached or deleted since cache was created.
		wp_cache_set( $post_ids_cache_key, $cached_ids, TW_EPISODE_IMPORTER_CACHE_GROUP );
	}

	return $result;
}

/**
 * Function to get response array based on $response argument.
 *
 * @param array $response   Repsonse object.
 * @return WP_REST_Response
 */
function tw_episode_importer_get_response( array $response ) {

	$status = $response['status'];

	switch ( $status ) {
		case 400:
			return new WP_Error( 'rest_no_route', __( 'TW - API URL not configured or could not be loaded.', 'tw-text' ), $response );

		case 403:
			return new WP_Error( 'rest_no_route', __( 'TW - Resource not found or forbiden.', 'tw-text' ), $response );

		case 404:
			return new WP_Error( 'rest_no_route', __( 'TW - No route was found matching the URL and request method.', 'tw-text' ), $response );

		case 500:
			return new WP_Error( 'rest_internal_error', __( 'TW - 500 Internal Error.', 'tw-text' ), $response );

		default:
			return new WP_REST_Response( $response['data'], $status );
	}
}
