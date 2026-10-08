<?php
/**
 * A page: Elementor's layout. When Elementor is not active, the page is drawn from its saved Elementor layout
 * by the core plugin (every section with its settings); a page that was never built in Elementor shows its content.
 */

defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) {
	the_post();
	$az_html = ! did_action( 'elementor/loaded' ) && function_exists( 'az_render_elementor_data' ) ? az_render_elementor_data( get_the_ID() ) : '';
	if ( '' !== $az_html ) {
		echo $az_html; // phpcs:ignore -- sections escape their own output.
	} else {
		the_content();
	}
}
get_footer();
