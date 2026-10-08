<?php
/**
 * Everything that is not a page built in Elementor: a plain column of content. Search results and archives
 * list each item's title, link and description, never the whole page.
 */

defined( 'ABSPATH' ) || exit;

/** A short description of a post or page: its own description, its excerpt, or the start of its text. */
function az_theme_summary( $id ) {
	$d = (string) get_post_meta( $id, '_az_description', true );
	if ( '' === $d && has_excerpt( $id ) ) {
		$d = (string) get_post_field( 'post_excerpt', $id );
	}
	if ( '' === $d ) {
		$d = wp_trim_words( wp_strip_all_tags( strip_shortcodes( (string) get_post_field( 'post_content', $id ) ) ), 32 );
	}
	return $d;
}

get_header();
?>
<section class="az-sec az-sec--prose"><div class="az-wrap az-prose">
<?php
if ( have_posts() && is_singular() ) {
	while ( have_posts() ) {
		the_post();
		echo '<h1 class="az-h2">' . esc_html( get_the_title() ) . '</h1>';
		the_content();
	}
} elseif ( have_posts() ) {
	echo '<h1 class="az-h2">' . esc_html( is_search() ? 'Results for “' . get_search_query( false ) . '”' : wp_strip_all_tags( get_the_archive_title() ) ) . '</h1>';
	while ( have_posts() ) {
		the_post();
		printf( '<h2><a href="%s">%s</a></h2>', esc_url( get_permalink() ), esc_html( get_the_title() ) );
		$az_sum = az_theme_summary( get_the_ID() );
		if ( '' !== $az_sum ) {
			echo '<p>' . esc_html( $az_sum ) . '</p>';
		}
	}
	the_posts_pagination( array( 'mid_size' => 1 ) );
} else {
	echo '<h1 class="az-h2">' . ( is_search() ? 'Nothing found.' : 'Nothing here.' ) . '</h1><p><a href="' . esc_url( home_url( '/' ) ) . '">Back to the home page</a></p>';
}
?>
</div></section>
<?php
get_footer();
