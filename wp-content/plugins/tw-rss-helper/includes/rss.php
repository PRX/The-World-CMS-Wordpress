<?php
/**
 * Add support to site RSS feed
 *
 * @since 1.0.0
 */

/**
 * Add audio enclosure to RSS feed.
 *
 * This function will get post_rss_audio meta from a post.
 * WARNING: This function will only work if ACF is active.
 *
 * @since 1.0.0
 */
function tw_rss_helper_audio_enclosure() {

	// Check if ACF is active.
	if ( ! function_exists( 'get_field' ) ) {
		return false;
	}

	global $post;

	// Get audio metadata.
	$audio_id = get_post_meta( $post->ID, 'audio', true );

	$audio_url = wp_get_attachment_url( $audio_id );

	// Bail early if no audio URL is found.
	if ( ! $audio_url ) {
		return false;
	}

	$audio_post     = get_post( $audio_id );
	$audio_metadata = wp_get_attachment_metadata( $audio_id );

	$audio_media = array(
		'url'       => $audio_url,
		'mime_type' => $audio_post->post_mime_type,
	);

	if ( isset( $audio_metadata['filesize'] ) ) {
		$audio_media['filesize'] = $audio_metadata['filesize'];
	} else {
		$audio_media                = tw_rss_helper_get_external_audio_data( $audio_url );
		$audio_metadata['filesize'] = $audio_media['filesize'];
		wp_update_attachment_metadata( $audio_id, $audio_metadata );
	}

	// Build the default enclosure HTML.
	$enclosure_html = sprintf(
		'<enclosure url="%1$s" type="%2$s" length="%3$s"/>',
		$audio_media['url'],
		$audio_media['mime_type'],
		$audio_media['filesize']
	);

	/**
	 * Filter the audio enclosure HTML
	 *
	 * @since 1.0.0
	 *
	 * @param  string $enclosure_html The default enclosure HTML.
	 * @param  int    $post_id        The post ID.
	 * @param  array  $audio_media    The audio media array.
	 *
	 * @return string The audio enclosure HTML.
	 */
	$filtered_html = apply_filters(
		'tw_rss_helper_audio_enclosure',
		$enclosure_html,
		$post->ID,
		$audio_media
	);

	echo wp_kses(
		$filtered_html,
		array(
			'enclosure' => array(
				'url'    => true,
				'type'   => true,
				'length' => true,
			),
		)
	);
}
add_action( 'rss2_item', 'tw_rss_helper_audio_enclosure' );

/**
 * Get external audio URL file size.
 *
 * @since 1.0.0
 *
 * @param  string $url The URL to get the file size of.
 *
 * @return array The audio data.
 */
function tw_rss_helper_get_external_audio_data( $url ) {

	// Default audio data.
	$audio_data = array(
		'url'       => $url,
		'filesize'  => 0,
		'mime_type' => 'audio/mpeg',
	);

	$headers = get_headers( $url, 1 );

	// Try to get the filesize from the Content-Length header.
	if ( isset( $headers['Content-Length'] ) ) {

		$content_length = $headers['Content-Length'];

		if ( is_array( $content_length ) ) {

			$audio_data['filesize'] = max( array_map( 'intval', $content_length ) );

		} else {

			$audio_data['filesize'] = intval( $content_length );
		}
	}

	// Try to get file mime type from the Content-Type header.
	if ( isset( $headers['Content-Type'] ) ) {

		$content_type = $headers['Content-Type'];

		if ( is_array( $content_type ) ) {

			// Get the first value that contains "audio/".
			$audio_data['mime_type'] = reset(
				array_filter(
					$content_type,
					function ( $value ) {
						return strpos( $value, 'audio/' ) === 0;
					}
				)
			);

		} elseif ( is_string( $content_type ) ) {

			$audio_data['mime_type'] = strval( $content_type );
		}
	} else {
		$audio_data['mime_type'] = tw_rss_helper_get_audio_mime_type( $url );
	}

	return $audio_data;
}

/**
 * Get audio mime type from URL.
 *
 * @since 1.0.0
 *
 * @param  string $url The URL to get the mime type of.
 *
 * @return string The audio mime type.
 */
function tw_rss_helper_get_audio_mime_type( $url ) {
	// Extract the file extension.
	$extension = pathinfo( wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION );

	if ( ! empty( $extension ) ) {
		// Check if it's a recognized audio extension in WordPress.
		$file_info = wp_check_filetype( $extension );

		// Validate that it's an audio type.
		if ( ! empty( $file_info['ext'] ) && strpos( $file_info['type'], 'audio/' ) === 0 ) {
			return $file_info['type'];
		}
	}

	// Return audio/mpeg as the default type.
	return 'audio/mpeg';
}
