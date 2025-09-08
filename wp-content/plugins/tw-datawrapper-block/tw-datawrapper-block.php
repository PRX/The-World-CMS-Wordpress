<?php
/**
 * Plugin Name:       Tw Datawrapper Block
 * Description:       Adds Datawrapper variation to core embed block.
 * Requires at least: 6.1
 * Requires PHP:      7.0
 * Version:           0.1.0
 * Author:            The WordPress Contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tw-datawrapper-block
 *
 * @package           create-block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'tw_datawrapper_block_editor_assets' ) ) :
	/**
	 * Setup block editor scripts and styles.
	 *
	 * @uses wp_enqueue_scripts() Enqueue scripts hook.
	 * @uses wp_enqueue_styles() Registers style css file.
	 */
	function tw_datawrapper_block_editor_assets() {
		wp_enqueue_script(
			'tw-datawrapper-embed-variation',
			plugin_dir_url( __FILE__ ) . '/build/index.js',
			array( 'wp-blocks', 'wp-dom-ready', 'wp-edit-post' ),
			'1.0',
			array( 'in_footer' => true )
		);
		wp_enqueue_style(
			'tw-datawrapper-styles',
			plugin_dir_url( __FILE__ ) . '/build/index.css',
			array(),
			'1.0'
		);
	}
endif;
add_action( 'enqueue_block_editor_assets', 'tw_datawrapper_block_editor_assets' );
