<?php
/**
 * Plugin Name: TW Sitemap Mask
 * Description: Mask the sitemap URL with configured URL.
 * Version: 1.0
 * Author: The World
 *
 * @package tw
 */

/**
 * Mask the sitemap URL with configured URL.
 *
 * @param array $sitemap_entry Entry data array.
 *
 * @return array
 */
function tw_sitemap_mask( $sitemap_entry ) {

	if ( isset( $sitemap_entry['loc'] ) ) {

		$base_url = get_bloginfo( 'url' );

		$mask_url = get_option( 'tw_xml_sitemap_custom_path', null );

		if ( ! empty( $mask_url ) ) {
			$sitemap_entry['loc'] = str_replace( $base_url, $mask_url, $sitemap_entry['loc'] );
		}
	}

	return $sitemap_entry;
}
add_filter( 'wp_sitemaps_posts_entry', 'tw_sitemap_mask' );
add_filter( 'wp_sitemaps_taxonomies_entry', 'tw_sitemap_mask' );
add_filter( 'wp_sitemaps_users_entry', 'tw_sitemap_mask' );

/**
 * Add admin sub menu in Settings menu.
 */
function tw_sitemap_mask_menu() {
	add_submenu_page(
		'options-general.php',
		'TW Sitemap Mask',
		'TW Sitemap Mask',
		'manage_options',
		'tw-sitemap-mask',
		'tw_sitemap_mask_page'
	);
}
add_action( 'admin_menu', 'tw_sitemap_mask_menu' );

/**
 * TW Sitemap Mask page.
 *
 * Display URL field to configure the mask URL.
 *
 * @return void
 */
function tw_sitemap_mask_page() {

	if ( isset( $_POST['tw_xml_sitemap_custom_path'] ) ) {

		// Validate.
		$update = trailingslashit( $_POST['tw_xml_sitemap_custom_path'] );
		$update = rtrim( $update, '/' );
		$update = sanitize_text_field( $update );
		$update = sanitize_url( $update );

		update_option( 'tw_xml_sitemap_custom_path', $update );
	}

	$mask_url = get_option( 'tw_xml_sitemap_custom_path', null );
	$mask_url = ! empty( $mask_url ) ? $mask_url : get_bloginfo( 'url' );

	// Default WordPress.
	$site_sitemap_url = get_bloginfo( 'url' ) . '/wp-sitemap.xml';

	?>
	<div class="wrap">

		<h2>TW Sitemap Mask</h2>

		<p>Custom sitemap mask will be applied to posts, taxonomies, and users sitemap URL.</p>
		<p><a href="<?php echo esc_url( $site_sitemap_url ); ?>" target="_blank">View Sitemap</a></p>

		<form method="post">
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="tw_xml_sitemap_custom_path">Custom Sitemap Base URL</label>
					</th>
					<td>
						<input type="url" name="tw_xml_sitemap_custom_path" id="tw_xml_sitemap_custom_path" value="<?php echo esc_attr( $mask_url ); ?>" class="regular-text">
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Update post query args used to get posts in sitemaps.
 *
 * @param array $args Array of WP_Query arguments.
 * @return array
 */
function tw_sitemap_mask_post_query_args( $args ) {

	// Remove meta query args.
	unset( $args['meta_query'] );
	$args['update_post_meta_cache'] = false;

	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'tw_sitemap_mask_post_query_args', 999, 1 );
