<?php
/**
 * Everything that is not a page built in Elementor: a plain column of content.
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<section class="az-sec az-sec--prose"><div class="az-wrap az-prose">
<?php
if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();
		echo '<h1 class="az-h2">' . esc_html( get_the_title() ) . '</h1>';
		the_content();
	}
} else {
	echo '<h1 class="az-h2">Nothing here.</h1><p><a class="az-link" href="' . esc_url( home_url( '/' ) ) . '">Back to the home page</a></p>';
}
?>
</div></section>
<?php
get_footer();
