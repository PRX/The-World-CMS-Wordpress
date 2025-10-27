<?php
/**
 * Theme functions file.
 *
 * @package the-world
 */

if ( ! function_exists( 'the_world_styles' ) ) :
	/**
	 * Setup theme styles and scripts.
	 *
	 * @uses wp_enqueue_scripts() Enqueue scripts hook.
	 * @uses wp_enqueue_styles() Registers style css file.
	 */
	function the_world_styles() {
		wp_enqueue_style( 'style', get_stylesheet_uri(), array(), 1.0 );
	}
endif;
add_action( 'admin_enqueue_scripts', 'the_world_styles' );

if ( ! function_exists( 'tw_allowed_redirect_hosts' ) ) :
	/**
	 * Add allowed redirect hosts.
	 *
	 * @uses allowed_redirect_hosts() Allowed redirect hosts filter.
	 *
	 * @param string[] $hosts Array of existing allowed hosts.
	 * @return string[] New array of allowed hosts.
	 */
	function tw_allowed_redirect_hosts( $hosts ) {
		if ( in_array( getenv( 'WP_ENVIRONMENT_TYPE' ), array( 'staging', 'production' ), true ) ) {
			$my_hosts = array(
				'theworld.org',
			);
		} else {
			$my_hosts = array(
				'localhost:3000',
			);
		}
		return array_merge( $hosts, $my_hosts );
	}
endif;
add_filter( 'allowed_redirect_hosts', 'tw_allowed_redirect_hosts' );

/**
 * Add filter hooks to extend those provided by Faust plugin to include our custom post types.
 */

if ( function_exists( 'WPE\FaustWP\Replacement\\post_link' ) ) {
	add_filter( 'post_type_link', 'WPE\FaustWP\Replacement\\post_link', 1000 );
}

if ( function_exists( 'WPE\FaustWP\Replacement\\preview_link_in_rest_response' ) ) {
	add_filter( 'rest_prepare_episode', 'WPE\FaustWP\Replacement\\preview_link_in_rest_response', 10, 2 );
	add_filter( 'rest_prepare_newsletter', 'WPE\FaustWP\Replacement\\preview_link_in_rest_response', 10, 2 );
	add_filter( 'rest_prepare_segment', 'WPE\FaustWP\Replacement\\preview_link_in_rest_response', 10, 2 );
}

if ( ! function_exists( 'tw_admin_styles' ) ) :
	/**
	 * Add styles to admin.
	 *
	 * @return void
	 */
	function tw_admin_styles() {
		// Fixes layout heights of some accordion containers after WP 6.3 update.
		echo '<style>
			.wpseo-meta-section, .wpseo-meta-section-react { min-height: unset; }
		</style>';

		// Hides device options from preview menu dropdown. They are basically useless and confusing.
		echo '<style>
			.components-form-token-field__token-text { white-space: unset; }
		    .components-popover__content:has(.editor-preview-dropdown__button-external) .components-menu-group:not(:has(.editor-preview-dropdown__button-external)) { display: none; }
		    .components-popover__content:has(.editor-preview-dropdown__button-external) .components-menu-group:has([target="pp_revisions_copy"]) { display: none; }
			.components-popover__content:has(.editor-preview-dropdown__button-external) .components-menu-group + .components-menu-group:has(.editor-preview-dropdown__button-external) { border-top: none; margin-top: -8px; }
		</style>';
	}
endif;
add_action( 'admin_head', 'tw_admin_styles' );

if ( ! function_exists( 'tw_admin_init_editor_styles' ) ) {
	/**
	 * Add editor styles.
	 *
	 * @return void
	 */
	function tw_admin_init_editor_styles() {
		add_theme_support( 'editor-styles' );
		add_editor_style( 'css/editor.css' );
	}
}
add_filter( 'admin_init', 'tw_admin_init_editor_styles' );

if ( ! function_exists( 'tw_allowed_block_types' ) ) :
	/**
	 * Set allowed Gutenberg blocks.
	 *
	 * @param bool|string[]           $allowed_blocks Array of allowed blocks. Boolean to enable/disable all.
	 * @param WP_Block_Editor_Context $editor_context Current block editor context.
	 *
	 * @return bool|string[] Array of allowed blocks. Boolean to enable/disable all.
	 */
	function tw_allowed_block_types( $allowed_blocks, $editor_context ) {
		$allowed_blocks = array(
			// Text...
			'core/paragraph',
			'core/heading',
			'core/list',
			'core/list-item',
			'core/pullquote',
			'tw/qa-block',

			// Media...
			'core/audio',
			'core/image',
			'tw/scroll-gallery',
			'tw/scroll-gallery-slide',

			// Design...
			'core/separator',

			// Embeds...
			// Specific types of embeds are now variants of the embed block.
			// Variations can be enabled in `./js/blockembed.js`.
			'core/embed',
		);

		if ( 'page' === $editor_context->post->post_type ) {
			$allowed_blocks[] = 'core/shortcode';
		}

		return $allowed_blocks;
	}
endif;
add_action( 'allowed_block_types_all', 'tw_allowed_block_types', 25, 2 );

if ( ! function_exists( 'tw_block_editor_assets' ) ) :
	/**
	 * Setup block editor scripts and styles.
	 *
	 * @uses wp_enqueue_scripts() Enqueue scripts hook.
	 * @uses wp_enqueue_styles() Registers style css file.
	 */
	function tw_block_editor_assets() {
		wp_enqueue_script(
			'tw-deny-list-blocks',
			get_template_directory_uri() . '/js/blockembed.js',
			array( 'wp-blocks', 'wp-dom-ready', 'wp-edit-post' ),
			'1.1',
			array( 'in_footer' => true )
		);
		wp_enqueue_style(
			'tw-block-editor-styles',
			get_template_directory_uri() . '/css/block-editor.css',
			array(),
			'1.1'
		);
	}
endif;
add_action( 'enqueue_block_editor_assets', 'tw_block_editor_assets' );

if ( ! function_exists( 'tw_remove_core_block_patterns' ) ) :
	/**
	 * Remove core block patterns.
	 *
	 * @uses remove_theme_support() Allows a theme to de-register its support of a certain feature.
	 */
	function tw_remove_core_block_patterns() {
		remove_theme_support( 'core-block-patterns' );
	}
endif;
add_action( 'after_setup_theme', 'tw_remove_core_block_patterns' );

/**
 * Prevent remote block patterns from loading.
 */
add_filter( 'should_load_remote_block_patterns', '__return_false' );

// Remove Windows Live Writer manifest link.
remove_action( 'wp_head', 'wlwmanifest_link' );

if ( ! function_exists( 'tw_add_edit_homepage_menu_link' ) ) :
	/**
	 * Add convenient link for editors to edit homepage.
	 *
	 * @uses add_menu_page() Add links to the menu.
	 */
	function tw_add_edit_homepage_menu_link() {
		add_menu_page( 'tw_edit_homepage_link', 'The World Homepage', 'publish_posts', '/term.php?taxonomy=program&tag_ID=12270&post_type=post', '', 'dashicons-admin-site-alt3', 8 );
	}
endif;
add_action( 'admin_menu', 'tw_add_edit_homepage_menu_link' );

if ( ! function_exists( 'tw_oembed_dataparse_youtube_shorts' ) ) {
	/**
	 * Ensure HTML markup for YouTube embeds is in the appropriate orientation.
	 * YouTube oembed API returns data portrait width and height props for shorts, but HTML
	 * with the width and height swapped to landscape. Also, ensure the no cookie domain is used.
	 *
	 * @param string $html Current HTML markup for embed.
	 * @param object $data oEmbed data returned by YouTube API.
	 * @param string $url URL of content to be embedded. NOT the embed iframe URL.
	 * @return string Updated HTML markup in portrait orientation.
	 */
	function tw_oembed_dataparse_youtube_shorts( $html, $data, $url ) {
		// Don't proceed if not a YouTube URL.
		if ( preg_match( '#https?://(((m|www)\.)?youtube(-nocookie)?\.com|youtu.be)#i', $url ) !== 1 ) {
			return $html;
		}

		$src_regex = '/src="(?<SRC>[^"]+)"/i';

		preg_match( $src_regex, $html, $src_matches );

		$src = preg_replace( '/(-nocookie)?\.com/i', '-nocookie.com', $src_matches['SRC'] );

		// Let's replace the attributes.
		$attribute_patterns = array(
			'/width="\d+"/i',
			'/height="\d+"/i',
			$src_regex,
		);

		// Since data width and height seem to be correct, use them as source of truth.
		// Shorts may be able to be filmed in landscape or even square aspect ratios.
		// We just want to make sure the HTML markup is using the correct values.
		$replacement_values = array(
			"width=\"{$data->width}\"",
			"height=\"{$data->height}\"",
			"src=\"{$src}\"",
		);

		$html = preg_replace( $attribute_patterns, $replacement_values, $html );

		return $html;
	}
}
add_filter( 'oembed_dataparse', 'tw_oembed_dataparse_youtube_shorts', 20, 3 );

if ( ! function_exists( 'tw_oembed_dataparse_tiktok' ) ) {
	/**
	 * Sanitize TikTok embed markup to not require scripts and have minimal iframe sandbox attributes.
	 *
	 * @param string $html Current HTML markup for embed.
	 * @param object $data oEmbed data returned by YouTube API.
	 * @param string $url URL of content to be embedded. NOT the embed iframe URL.
	 * @return string Updated HTML markup in portrait orientation.
	 */
	function tw_oembed_dataparse_tiktok( $html, $data, $url ) {
		// Don't proceed if not a TikTok URL.
		if ( preg_match( '#https?://(www\.)?tiktok\.com/(?:.*/video/.*|@.*)#i', $url ) !== 1 ) {
			return $html;
		}

		// Extract useful pieces of HTML.
		preg_match( '~(?<BLOCKQUOTE><blockquote[^>]+>)(?<CONTENT>.*)</blockquote>~', $html, $matches );

		$blockquote_open = $matches['BLOCKQUOTE'];
		$content         = $matches['CONTENT'];

		preg_match( '~cite="(?<LINK_URL>.*?)"~i', $blockquote_open, $blockquote_matches );

		$link_url = $blockquote_matches['LINK_URL'];

		$aspect_ratio = $data->thumbnail_width / $data->thumbnail_height;

		$styles = file_get_contents( __DIR__ . '/css/embed.tiktok.css', true );

		$link_attributes = implode(
			' ',
			array(
				'class="tiktok-embed-link"',
				"href=\"{$link_url}?refer=embed\"",
				'target="_blank"',
				'title="View video on TikTok.com"',
			)
		);
		$link_open       = "<a {$link_attributes}>";

		$image = false;

		if ( $data->thumbnail_url ) {
			$figure_attributes = implode(
				' ',
				array(
					'class="thumbnail"',
				)
			);
			$figure_open       = "<figure {$figure_attributes}>";

			$image_width      = 325;
			$image_height     = round( 325 / $aspect_ratio );
			$image_attributes = implode(
				' ',
				array(
					"src=\"{$data->thumbnail_url}\"",
					"width=\"{$image_width}\"",
					"height=\"{$image_height}\"",
				)
			);
			$image            = "<img {$image_attributes} />";
		}

		// Update blockquote styles.
		$blockquote_open = preg_replace( '~style=".*?"~', '', $blockquote_open );

		$script = implode(
			'',
			array(
				'((videoId, aspectRatio) => {',
				'window.addEventListener("message",(e) => {',
				'if (!e.data.startsWith("{")) return;',
				'const data = JSON.parse(e.data);',
				'if (!data.signalSource || !data.height) return;',
				'const f = document.querySelector(`[name="${data.signalSource}"]`);',
				'f?.style.setProperty("width", data.width + "px");',
				'f?.style.setProperty("height", data.height + "px");',
				'});',
				'const bq = document.querySelector(`blockquote[data-video-id="${videoId}"]`);',
				'const f = document.createElement("iframe");',
				'f.name = `__tt_embed__v${videoId}`;',
				'f.sandbox = "allow-popups allow-popups-to-escape-sandbox allow-scripts allow-top-navigation-by-user-activation";',
				'f.src = `https://www.tiktok.com/embed/v2/${videoId}?lang=${navigator.language}&embedFrom=oembed`;',
				'f.style.setProperty("display", "block");',
				'f.style.setProperty("visibility", "unset");',
				'f.style.setProperty("width", "100%");',
				'f.style.setProperty("aspect-ratio", aspectRatio);',
				'f.style.setProperty("border", "none");',
				'bq.innerHTML = "";',
				'bq.appendChild(f);',
				"})('{$data->embed_product_id}', {$aspect_ratio})",
			)
		);

		$html = implode(
			'',
			! $image ? array(
				"<style>{$styles}</style>",
				$blockquote_open,
				$link_open,
				'</a>',
				$content,
				'</blockquote>',
				"<script>{$script}</script>",
			) : array(
				"<style>{$styles}</style>",
				$blockquote_open,
				$figure_open,
				$link_open,
				$image,
				'</a>',
				'</figure>',
				$content,
				'</blockquote>',
				"<script>{$script}</script>",
			)
		);

		return $html;
	}
}
add_filter( 'oembed_dataparse', 'tw_oembed_dataparse_tiktok', 20, 3 );


/**
 * Alter field settings for relationship fields' configuration.
 *
 * @param array $field Field settings array.
 * @return array Altered field settings array.
 */
function tw_acf_prepare_field_relationship_taxonomy( $field ) {
	// Do not render taxonomy select field. We have too many terms.
	// TODO: Figure out how to render or use a more performant input for this setting.
	if ( 'taxonomy' === $field['_name'] ) {
		return false;
	}

	// Remove taxonomy filtering option.
	if ( 'filters' === $field['_name'] ) {
		unset( $field['choices']['taxonomy'] );
	}

	return $field;
}
add_filter( 'acf/prepare_field', 'tw_acf_prepare_field_relationship_taxonomy', 10 );

/**
 * Alter pre-render settings for relationship fields.
 *
 * @param array $field Field settings array.
 * @return array Altered field settings array.
 */
function tw_acf_pre_render_field_relationship_taxonomy( $field ) {

	if ( 'relationship' === $field['type'] ) {
		// Make sure published posts can be selected.
		$field['post_status'] = array( 'publish' );

		// Make sure taxonomy is not used in queries.
		$field['taxonomy'] = null;

		// Makes sure taxonomy filter input is not rendered.
		$field['filters'] = array_filter(
			$field['filters'],
			static function ( $v ) {
				return 'taxonomy' !== $v;
			}
		);
	}

	return $field;
}
add_filter( 'acf/pre_render_field', 'tw_acf_pre_render_field_relationship_taxonomy', 10 );
