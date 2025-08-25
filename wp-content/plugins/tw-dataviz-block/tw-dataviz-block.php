<?php
/**
 * Plugin Name:       Tw Dataviz Block
 * Description:       Adds DataViz variation to core embed block.
 * Requires at least: 6.1
 * Requires PHP:      7.0
 * Version:           0.1.1
 * Author:            The WordPress Contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tw-dataviz-block
 *
 * @package           create-block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'tw_dataviz_block_editor_assets' ) ) :
	/**
	 * Setup block editor scripts and styles.
	 *
	 * @uses wp_enqueue_scripts() Enqueue scripts hook.
	 * @uses wp_enqueue_styles() Registers style css file.
	 */
	function tw_dataviz_block_editor_assets() {
		wp_enqueue_script(
			'tw-dataviz-embed-variation',
			plugin_dir_url( __FILE__ ) . '/build/index.js',
			array( 'wp-blocks', 'wp-dom-ready', 'wp-edit-post' ),
			'0.1.1',
			array( 'in_footer' => true )
		);
		wp_enqueue_style(
			'tw-dataviz-styles',
			plugin_dir_url( __FILE__ ) . '/build/index.css',
			array(),
			'0.1.1'
		);
	}
endif;
add_action( 'enqueue_block_editor_assets', 'tw_dataviz_block_editor_assets', 9 );

/**
 * Init embed handlers.
 *
 * @return void
 */
function tw_dataviz_block_init() {
	wp_embed_register_handler(
		'tw-dataviz',
		'#https?://interactive.pri.org/.*#i',
		'tw_dataviz_block_embed_handler',
		9999
	);
}
add_action( 'init', 'tw_dataviz_block_init' );

/**
 * Render data viz embeds.
 *
 * @param array  $matches Matchs from regex.
 * @param string $attr Duno.
 * @param string $url Embed URL.
 * @return string
 */
function tw_dataviz_block_embed_handler( $matches, $attr, $url ) {

	$params = wp_parse_args( wp_parse_url( $url, PHP_URL_QUERY ) );
	$height = isset( $params['height'] ) && ! empty( $params['height'] ) ? $params['height'] : 500;
	$embed  = sprintf( '<iframe width="%1$s" height="%2$s" src="%3$s" frameborder="0"></iframe>', '100%', $height, $url );

	return $embed;
}
