<?php
/**
 * Load Front-End Assets
 *
 * @package   book-database
 * @copyright Copyright (c) 2017, Ashley Gibson
 * @license   GPL2+
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load Assets
 *
 * @since 1.0.0
 * @return void
 */
function bdb_load_assets() {

	global $post;

	// Only load assets on the review page and when using the `[book-grid]` shortcode.
	$review_page_id = bdb_get_option( 'reviews_page' );


	if ( ( ! $review_page_id || get_the_ID() != $review_page_id ) && ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'book-grid' ) ) ) {
		return;
	}

	$js_dir  = BDB_URL . 'assets/js/';
	$css_dir = BDB_URL . 'assets/css/';

	// Use minified libraries if SCRIPT_DEBUG is turned off
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	// CSS
	wp_enqueue_style( 'book-database', $css_dir . 'front-end' . $suffix . '.css', array(), BDB_VERSION );

}

add_action( 'wp_enqueue_scripts', 'bdb_load_assets' );