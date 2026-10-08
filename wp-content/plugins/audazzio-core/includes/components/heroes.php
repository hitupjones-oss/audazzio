<?php
/**
 * The home hero (the whole idea in one screen, over the moving wave field) and the inner-page hero.
 */

defined( 'ABSPATH' ) || exit;

function az_schema_hero() {
	return array(
		'title'  => 'Home hero',
		'icon'   => 'eicon-banner',
		'fields' => array(
			'eyebrow'   => az_f( 'text', 'Small line above the headline', 'Live QR® second-screen technology' ),
			'title'     => az_f( 'textarea', 'Headline', "It comes\nin waves." ),
			'intro'     => az_f( 'textarea', 'Intro', 'Audazzio hides inaudible signals inside the sound of a broadcast or live event. Every phone that hears one shows the right content at the exact moment. No scanning, no searching.' ),
			'cta'       => az_f( 'text', 'First button', 'Try it now' ),
			'cta_url'   => az_f( 'url', 'First button link', '/try/' ),
			'alt'       => az_f( 'text', 'Second button', 'Join the Wave' ),
			'alt_url'   => az_f( 'url', 'Second button link', '/join/' ),
			'proof'     => az_f( 'text', 'Line under the buttons', 'Selected for the inaugural Comcast NBCUniversal SportsTech Accelerator' ),
			'proof_url' => az_f( 'url', 'Line link', '/about/#story' ),
			'readout'   => az_f( 'text', 'Signal label on the waves', 'Live QR · listening' ),
		),
	);
}

function az_render_hero( $a ) {
	?>
	<section class="az-hero" data-az-hero>
		<div class="az-hero__waves" aria-hidden="true"><canvas data-az-waves="hero"></canvas></div>
		<div class="az-hero__in">
			<?php if ( $a['eyebrow'] ) : ?><p class="az-eyebrow az-hero__eyebrow" data-az-rise><?php echo esc_html( $a['eyebrow'] ); ?></p><?php endif; ?>
			<h1 class="az-hero__title" data-az-rise><?php echo az_hl( $a['title'] ); // phpcs:ignore ?></h1>
			<?php if ( $a['intro'] ) : ?><div class="az-hero__intro" data-az-rise><?php echo az_paras( $a['intro'] ); // phpcs:ignore ?></div><?php endif; ?>
			<div class="az-hero__ctas" data-az-rise>
				<?php echo az_btn( $a['cta'], $a['cta_url'], 'ink' ); // phpcs:ignore ?>
				<?php echo az_btn( $a['alt'], $a['alt_url'], 'wave' ); // phpcs:ignore ?>
			</div>
			<?php if ( $a['proof'] ) : ?>
				<a class="az-hero__proof" href="<?php echo esc_url( az_url( $a['proof_url'] ) ); ?>" data-az-rise><span class="az-hero__dot" aria-hidden="true"></span><?php echo esc_html( $a['proof'] ); ?><?php echo az_icon( 'arrow' ); // phpcs:ignore ?></a>
			<?php endif; ?>
		</div>
		<?php if ( $a['readout'] ) : ?><p class="az-hero__readout az-label" aria-hidden="true"><i></i><?php echo esc_html( $a['readout'] ); ?></p><?php endif; ?>
		<a class="az-hero__scroll" href="#how" aria-label="Scroll to how it works"><span></span></a>
	</section>
	<?php
}

function az_schema_page_hero() {
	return array(
		'title'  => 'Page hero',
		'icon'   => 'eicon-header',
		'fields' => array(
			'eyebrow' => az_f( 'text', 'Small line above the headline', '' ),
			'title'   => az_f( 'textarea', 'Headline', '' ),
			'intro'   => az_f( 'textarea', 'Intro', '' ),
			'cta'     => az_f( 'text', 'First button', '' ),
			'cta_url' => az_f( 'url', 'First button link', '' ),
			'alt'     => az_f( 'text', 'Second button', '' ),
			'alt_url' => az_f( 'url', 'Second button link', '' ),
			'waves'   => az_f( 'select', 'Motion behind the headline', 'lines', array( 'options' => array( 'lines' => 'Wave lines', 'rings' => 'Signal rings', 'spectrum' => 'Frequency bars', 'none' => 'None' ) ) ),
			'links'   => az_f( 'textarea', 'Jump links (Label | #anchor, one per line)', '' ),
		),
	);
}

function az_render_page_hero( $a ) {
	$waves = sanitize_key( $a['waves'] ?: 'lines' );
	?>
	<section class="az-phero az-phero--<?php echo esc_attr( $waves ); ?>">
		<?php if ( 'none' !== $waves ) : ?><div class="az-phero__waves" aria-hidden="true"><canvas data-az-waves="<?php echo esc_attr( $waves ); ?>"></canvas></div><?php endif; ?>
		<div class="az-wrap az-phero__in">
			<?php if ( $a['eyebrow'] ) : ?><p class="az-eyebrow" data-az-rise><?php echo esc_html( $a['eyebrow'] ); ?></p><?php endif; ?>
			<h1 class="az-phero__title" data-az-rise><?php echo az_hl( $a['title'] ); // phpcs:ignore ?></h1>
			<?php if ( $a['intro'] ) : ?><div class="az-phero__intro" data-az-rise><?php echo az_paras( $a['intro'] ); // phpcs:ignore ?></div><?php endif; ?>
			<?php if ( $a['cta'] || $a['alt'] ) : ?>
				<div class="az-phero__ctas" data-az-rise><?php echo az_btn( $a['cta'], $a['cta_url'], 'ink' ) . az_btn( $a['alt'], $a['alt_url'], 'link' ); // phpcs:ignore ?></div>
			<?php endif; ?>
		</div>
		<?php $links = az_pairs( $a['links'] ); if ( $links ) : ?>
			<nav class="az-subnav" aria-label="On this page" data-az-subnav><div class="az-subnav__in">
				<?php foreach ( $links as $l ) : ?><a class="az-subnav__a" href="<?php echo esc_url( $l[1] ); ?>"><?php echo esc_html( $l[0] ); ?></a><?php endforeach; ?>
			</div></nav>
		<?php endif; ?>
	</section>
	<?php
}
