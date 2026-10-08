<?php
/**
 * The footer: the line, the links, the legal row with "Designed by RSPKT". The Join the Wave bar and the
 * dialogs are printed by the core plugin (wp_footer), the Try Audazzio notification at the top of the body.
 */

defined( 'ABSPATH' ) || exit;
$az_li = function_exists( 'az_opt' ) ? az_opt( 'linkedin' ) : 'https://www.linkedin.com/company/audazzio';
$az_pp = function_exists( 'az_opt' ) ? az_opt( 'privacy_url' ) : home_url( '/privacy/' );
// The lines under the logo and the trademark sentence are set under Audazzio > Settings > Footer and bar.
$az_fl = function_exists( 'az_opt' ) ? (string) az_opt( 'footer_line' ) : 'It comes in waves.';
$az_fs = function_exists( 'az_opt' ) ? (string) az_opt( 'footer_sub' ) : '';
$az_fm = function_exists( 'az_opt' ) ? (string) az_opt( 'footer_marks' ) : '';
?>
</main>
<footer class="az-foot" data-az-foot>
	<div class="az-foot__waves" aria-hidden="true"><canvas data-az-waves="footer"></canvas></div>
	<div class="az-wrap">
		<div class="az-foot__top">
			<div class="az-foot__brand">
				<a class="az-foot__mark" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Audazzio, home">
					<span class="az-foot__sym" aria-hidden="true"><?php echo az_theme_mark( 'symbol' ); // phpcs:ignore ?></span>
					<span class="az-foot__type" aria-hidden="true"><?php echo az_theme_mark( 'logotype' ); // phpcs:ignore ?></span>
				</a>
				<?php if ( '' !== trim( $az_fl ) ) : ?><p class="az-foot__line"><?php echo esc_html( $az_fl ); ?></p><?php endif; ?>
				<?php if ( '' !== trim( $az_fs ) ) : ?><p class="az-foot__sub"><?php echo esc_html( $az_fs ); ?></p><?php endif; ?>
			</div>
			<nav class="az-foot__links" aria-label="Footer">
				<?php az_theme_links( 'footer', 'az-foot__link' ); ?>
			</nav>
			<?php if ( $az_li ) : ?>
			<div class="az-foot__social">
				<a class="az-foot__link az-foot__li" href="<?php echo esc_url( $az_li ); ?>" target="_blank" rel="noopener">
					<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.13 1.45-2.13 2.94v5.67H9.35V9h3.41v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.46v6.28ZM5.34 7.43a2.06 2.06 0 1 1 0-4.13 2.06 2.06 0 0 1 0 4.13ZM7.12 20.45H3.56V9h3.56v11.45ZM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.73C24 .77 23.2 0 22.22 0Z"/></svg>
					<span>Audazzio on LinkedIn</span>
				</a>
			</div>
			<?php endif; ?>
		</div>
		<div class="az-foot__base">
			<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> Audazzio, Inc. All rights reserved.<?php echo '' !== trim( $az_fm ) ? ' ' . esc_html( $az_fm ) : ''; ?></p>
			<?php if ( function_exists( 'az_credit' ) ) { echo az_credit(); } // phpcs:ignore ?>
			<nav aria-label="Legal"><a href="<?php echo esc_url( $az_pp ); ?>">Privacy Policy</a></nav>
		</div>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
