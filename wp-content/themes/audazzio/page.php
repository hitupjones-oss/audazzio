<?php
/**
 * A page: Elementor's layout, or the content's shortcodes when Elementor is not active.
 */

defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) {
	the_post();
	the_content();
}
get_footer();
