<?php
/**
 * Setup admin page UI.
 *
 * @package tw_previews
 */

define( 'TW_PREVIEWS_APP_CONTAINER_ID', 'tw-episode-importer-app' );

if ( ! function_exists( 'tw_previews_faust_exclude_redirect_preview_route' ) ) {
	/**
	 * Prevent Faust from redirecting preview route to the front-end domain.
	 *
	 * @param array $excluded Array of excluded paths.
	 * @return array Array of excluded paths.
	 *
	 * @package tw_previews
	 */
	function tw_previews_faust_exclude_redirect_preview_route( $excluded ) {

		$route = add_query_arg( null, null );

		if ( strpos( $route, 'preview/' ) > -1 ) {
			// Faust uses `basename` on the current URL path to compare with exclusion list.
			// `basename` is meant for use with file paths, and will only grab the last segment of the path, including query string.
			// Since the last segment of our preview URL is dynamic, and we have no idea if query string params have been added,
			// we have to add our exclude route dynamically using the same URL parsing Faust uses.
			// TODO: Keep an eye on this when Faust is updated. They may improve or alter their logic for path exlcusion.
			$excluded = array_merge(
				$excluded,
				array(
					basename( $route ),
				)
			);
		}

		return $excluded;
	}
}
add_filter(
	'faustwp_exclude_from_public_redirect',
	'tw_previews_faust_exclude_redirect_preview_route',
	20,
	1
);

if ( ! function_exists( 'tw_previews_template_include_preview' ) ) {
	/**
	 * Use custom template for previews.
	 *
	 * @param string $template Current template path.
	 * @return string
	 */
	function tw_previews_template_include_preview( $template ) {
		$route = add_query_arg( null, null );

		if ( strpos( $route, 'preview/' ) > -1 ) {
			if ( ! is_user_logged_in() ) {
				header( 'Status: 403 Forbidden', true, 403 );
				exit();
			}
			return TW_PREVIEWS_PATH . '/preview/preview.php';
		}

		return $template;
	}
}
add_action( 'template_include', 'tw_previews_template_include_preview' );

if ( ! function_exists( 'tw_previews_preview_post_link' ) ) {
	/**
	 * Update preview post link.
	 *
	 * @param string  $preview_link Current preview link.
	 * @param WP_Post $post Post object preview link is being created for.
	 *
	 * @return string New post preview link URL.
	 */
	function tw_previews_preview_post_link( $preview_link, $post ) {
		// We will work with a global ID that WPGraphQL uses. Will make queries easier down the line.
		$post_id      = base64_encode( "post:{$post->ID}" );
		$preview_path = "/preview/{$post_id}";

		return $preview_path;
	}
}
add_filter( 'preview_post_link', 'tw_previews_preview_post_link', 100000, 2 );
add_filter( 'revisionary_preview_url', 'tw_previews_preview_post_link', 100000, 2 );

/**
 * Render HTML and enqueue scripts for admin page.
 *
 * @return void
 */
function tw_previews_preview_page_html() {

	global $wp;

	$not_preview = ! preg_match( '~^preview/~', $wp->request );

	if ( ! $wp->request || $not_preview ) {
		return;
	}

	list(, $post_id)          = explode( '/', $wp->request );
	list(, $post_database_id) = explode( ':', base64_decode( $post_id ) );
	$post                     = get_post( $post_database_id );

	if ( in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft' ) ) ) {
		$post->filter = 'sample';
	}

	$permalink   = get_permalink( $post, true );
	$preview_url = preg_replace( '~(?<=/)[^/]+$~', "preview/{$post_id}", $permalink );

	if ( function_exists( 'WPE\FaustWP\Replacement\equivalent_frontend_url' ) ) {
		$preview_url = WPE\FaustWP\Replacement\equivalent_frontend_url( $preview_url );
	}

	wp_enqueue_style( 'tw-previews-preview-ui', TW_PREVIEWS_URL . 'preview/ui/ui.css', array(), wp_rand() );
	wp_enqueue_script( 'tw-previews-preview-ui', TW_PREVIEWS_URL . 'preview/ui/dist/bundle.js', array(), wp_rand(), array( 'strategy' => 'defer' ) );
	wp_localize_script(
		'tw-previews-preview-ui',
		'appLocalizer',
		array(
			'appContainerId' => TW_PREVIEWS_APP_CONTAINER_ID,
			'restUrl'        => home_url( '/wp-json/wp/v2/' ),
			'gqlUrl'         => trailingslashit( site_url() ) . \WPGraphQL\Router::$route,
			'nonce'          => wp_create_nonce( 'wp_rest' ),
			'id'             => $post_id,
			'previewUrl'     => $preview_url,
		)
	);
}
add_action( 'wp_print_scripts', 'tw_previews_preview_page_html' );
