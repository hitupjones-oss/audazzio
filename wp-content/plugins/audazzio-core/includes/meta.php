<?php
/**
 * Search and sharing: a box on every page for the title and the description that search results and link
 * previews show (the theme prints them from _az_title and _az_description). When Yoast SEO or Rank Math is
 * active, that plugin does this job: the box is hidden and the theme prints neither.
 */

defined( 'ABSPATH' ) || exit;

function az_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' );
}

add_action( 'add_meta_boxes_page', function () {
	if ( ! az_seo_plugin_active() ) {
		add_meta_box( 'az-seo', 'Search and sharing', 'az_seo_box', 'page', 'side' );
	}
} );

function az_seo_box( $post ) {
	wp_nonce_field( 'az_seo_' . $post->ID, 'az_seo_nonce' );
	$title = (string) get_post_meta( $post->ID, '_az_title', true );
	$desc  = (string) get_post_meta( $post->ID, '_az_description', true );
	$def   = (int) get_option( 'page_on_front' ) === (int) $post->ID ? 'Audazzio | It comes in waves.' : ( $post->post_title ? $post->post_title . ' | Audazzio' : '' );
	?>
	<p><label for="az-seo-doc-title"><strong>Title</strong></label><br>
	<input type="text" class="widefat" id="az-seo-doc-title" name="az_seo_title" value="<?php echo esc_attr( $title ); ?>" placeholder="<?php echo esc_attr( $def ); ?>" maxlength="120"></p>
	<p class="description">The browser tab and the search result title. Empty: the page name, then "| Audazzio".</p>
	<p><label for="az-seo-doc-description"><strong>Description</strong></label><br>
	<textarea class="widefat" id="az-seo-doc-description" name="az_seo_description" rows="4" maxlength="320"><?php echo esc_textarea( $desc ); ?></textarea></p>
	<p class="description">One or two sentences, about 150 characters, for search results and link previews. Empty: none (the home page uses the site’s tagline).</p>
	<?php
}

add_action( 'save_post_page', function ( $post_id ) {
	if ( ! isset( $_POST['az_seo_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['az_seo_nonce'] ) ), 'az_seo_' . $post_id ) ) { // phpcs:ignore
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( array( 'az_seo_title' => '_az_title', 'az_seo_description' => '_az_description' ) as $field => $key ) {
		$v = isset( $_POST[ $field ] ) && is_scalar( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( (string) $_POST[ $field ] ) ) : ''; // phpcs:ignore
		if ( '' === $v ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $v );
		}
	}
} );
