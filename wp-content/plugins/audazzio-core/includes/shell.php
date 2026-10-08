<?php
/**
 * What sits on every page, outside the sections: the Join the Wave bar that stays at the foot of the
 * screen, the "Try Audazzio now" notification, and the dialogs (Join the Wave, Try Audazzio, a film,
 * a biography).
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'body_class', function ( $c ) {
	$c[] = 'az';
	if ( is_front_page() ) {
		$c[] = 'az-home';
	}
	if ( is_page() ) {
		$c[] = 'az-page-' . sanitize_html_class( (string) get_post_field( 'post_name', get_queried_object_id() ) );
	}
	return $c;
} );

/** A dialog shell: a frosted sheet with a close button. */
function az_dialog( $id, $label_id, $body, $class = '' ) {
	return sprintf(
		'<dialog class="az-dialog %1$s" id="%2$s" aria-labelledby="%3$s" data-az-dialog><div class="az-dialog__sheet"><button class="az-dialog__x" type="button" data-az-close aria-label="Close">%4$s</button><div class="az-dialog__body">%5$s</div></div></dialog>',
		esc_attr( $class ),
		esc_attr( $id ),
		esc_attr( $label_id ),
		az_icon( 'close' ),
		$body
	);
}

add_action( 'wp_footer', function () {
	if ( is_admin() ) {
		return;
	}
	$on_join = is_page( 'join' );
	?>
	<div class="az-bar" data-az-bar aria-hidden="true">
		<div class="az-bar__in">
			<p class="az-bar__line"><span class="az-bar__wave" aria-hidden="true"><canvas data-az-waves="mini"></canvas></span><span><?php echo esc_html( az_opt( 'bar_line' ) ); ?></span></p>
			<a class="az-btn az-btn--wave az-bar__cta" href="<?php echo esc_url( home_url( '/join/' ) ); ?>" data-az-join tabindex="-1"><span>Join the Wave</span><?php echo az_icon( 'arrow' ); // phpcs:ignore ?></a>
		</div>
	</div>

	<?php if ( 'no' !== strtolower( trim( (string) az_opt( 'notify' ) ) ) ) : ?>
	<div class="az-notify" data-az-notify hidden>
		<button class="az-notify__card" type="button" data-az-try>
			<span class="az-notify__app" aria-hidden="true"><?php echo az_mark( 'symbol' ); // phpcs:ignore ?></span>
			<span class="az-notify__body">
				<span class="az-notify__meta"><b>Audazzio</b><i>now</i></span>
				<strong class="az-notify__t">Try Audazzio now</strong>
				<span class="az-notify__p">Turn your speakers on and watch your phone light up.</span>
			</span>
		</button>
		<button class="az-notify__x" type="button" data-az-notify-x aria-label="Dismiss"><?php echo az_icon( 'close' ); // phpcs:ignore ?></button>
	</div>
	<?php endif; ?>

	<?php
	echo az_dialog( 'az-try', 'az-try-title', az_try_sheet(), 'az-dialog--try' ); // phpcs:ignore
	if ( ! $on_join ) {
		echo az_dialog( 'az-join', 'az-join-title', '<div class="az-sheet__head"><p class="az-eyebrow">Join the Wave</p><h2 class="az-sheet__title" id="az-join-title">' . az_hl( "Let’s make\n*some waves.*" ) . '</h2></div>' . az_join_form( 'sheet' ), 'az-dialog--join' ); // phpcs:ignore
	}
	echo az_dialog( 'az-film', 'az-film-title', '<h2 class="az-vh" id="az-film-title">Film</h2><div class="az-filmbox" data-az-filmbox></div>', 'az-dialog--film' ); // phpcs:ignore
	echo az_dialog( 'az-bio', 'az-bio-title', '<h2 class="az-vh" id="az-bio-title">Biography</h2><div data-az-biobox></div>', 'az-dialog--bio' ); // phpcs:ignore
	?>
	<script type="application/json" id="az-join-config"><?php echo wp_json_encode( az_join_public_config() ); ?></script>
	<?php
}, 5 );
