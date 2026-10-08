<?php
/**
 * How it works: the three-step explainer with its live diagram (home), the seven-step Live QR flow with
 * its Broadcast / Live event switch (Live QR page), and the canvas of what a phone can show.
 */

defined( 'ABSPATH' ) || exit;

function az_icon_options() {
	$o = array();
	foreach ( array( 'wave', 'speaker', 'tv', 'stadium', 'phone', 'mic', 'cloud', 'link', 'image', 'video', 'trophy', 'bag', 'dice', 'gift', 'chart', 'vote', 'replay', 'info', 'ticket', 'shield', 'users', 'calendar', 'qr', 'spark', 'app', 'doc' ) as $i ) {
		$o[ $i ] = ucfirst( $i );
	}
	return $o;
}

/** The link drawn between a screen and a phone: speaker, rings, the travelling signal, the phone lighting up. */
function az_link_diagram() {
	ob_start();
	?>
	<svg class="az-link" viewBox="0 0 640 380" role="img" aria-label="A TV sends an inaudible signal; a phone hears it and shows the content.">
		<defs>
			<linearGradient id="az-link-card" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#FFB27F"/><stop offset="1" stop-color="#F26A2E"/></linearGradient>
			<clipPath id="az-link-screen"><rect x="452" y="70" width="128" height="250" rx="18"/></clipPath>
		</defs>
		<g class="az-link__tv">
			<rect x="34" y="82" width="270" height="172" rx="14" class="az-link__frame"/>
			<rect x="48" y="96" width="242" height="144" rx="6" class="az-link__glass"/>
			<path d="M150 254v26M188 254v26M120 282h98" class="az-link__frame"/>
			<path class="az-link__audio" d="M66 168h14l6-22 8 44 8-58 8 70 8-48 8 30 8-18 8 8 8-4h14l6-16 8 32 8-40 8 50 8-30 8 14 8-6h14l6-10 8 20 8-26 8 32 8-18 8 8h12"/>
			<text x="48" y="318" class="az-link__lab">01 · EMBED</text>
		</g>
		<g class="az-link__rings">
			<path d="M318 140a34 34 0 0 1 0 56"/>
			<path d="M332 122a58 58 0 0 1 0 92"/>
			<path d="M346 104a82 82 0 0 1 0 128"/>
		</g>
		<path class="az-link__path" d="M326 168C362 120 386 216 414 168S440 140 452 160"/>
		<circle class="az-link__packet" r="4.5" cx="0" cy="0"><animateMotion dur="2.4s" repeatCount="indefinite" path="M326 168C362 120 386 216 414 168S440 140 452 160" keyPoints="0;1" keyTimes="0;1" calcMode="linear"/></circle>
		<text x="336" y="318" class="az-link__lab az-link__lab--play">02 · PLAY</text>
		<g class="az-link__phone">
			<rect x="446" y="62" width="140" height="266" rx="24" class="az-link__frame"/>
			<rect x="452" y="70" width="128" height="250" rx="18" class="az-link__glass"/>
			<rect x="497" y="76" width="38" height="9" rx="4.5" class="az-link__notch"/>
			<g clip-path="url(#az-link-screen)" class="az-link__content">
				<rect x="462" y="104" width="108" height="92" rx="10" fill="url(#az-link-card)"/>
				<path d="M474 178h52M474 166h80" class="az-link__ink"/>
				<rect x="462" y="206" width="108" height="10" rx="5" class="az-link__line"/>
				<rect x="462" y="224" width="80" height="10" rx="5" class="az-link__line"/>
				<rect x="462" y="262" width="108" height="34" rx="17" class="az-link__btn"/>
			</g>
			<g class="az-link__mic"><circle cx="516" cy="350" r="0"/></g>
			<text x="452" y="356" class="az-link__lab">03 · DELIVER</text>
		</g>
	</svg>
	<?php
	return (string) ob_get_clean();
}

function az_schema_steps() {
	return array(
		'title'  => 'How it works (3 steps)',
		'icon'   => 'eicon-number-field',
		'fields' => az_head_fields( 'How it works', "It’s like a QR code.\n*No one has to scan it.*", 'Audazzio uses inaudible micro-signaling, embedded in the audio of a broadcast or live event, to deliver content to fans’ phones the moment it matters.', 'white' ) + array(
			'steps'   => az_f( 'repeater', 'Steps', array(
				array( 'title' => 'Embed', 'text' => 'We embed an inaudible micro-signal in the audio of your broadcast, stream or venue sound.' ),
				array( 'title' => 'Play', 'text' => 'TV speakers or the seating bowl’s sound system carry the signal to every phone in range.' ),
				array( 'title' => 'Deliver', 'text' => 'Phones listening with the app hear it and load the content you chose, in under a second.' ),
			), array( 'fields' => array( 'title' => az_f( 'text', 'Step', '' ), 'text' => az_f( 'textarea', 'Text', '' ) ), 'title' => '{{{ title }}}' ) ),
			'cta'     => az_f( 'text', 'Link', 'How Live QR works' ),
			'cta_url' => az_f( 'url', 'Link address', '/live-qr/' ),
		),
	);
}

function az_render_steps( $a ) {
	if ( empty( $a['anchor'] ) ) {
		$a['anchor'] = 'how';
	}
	az_open( $a, 'az-steps', 'data-az-steps' );
	?>
	<div class="az-wrap">
		<?php echo az_head( $a, 'az-head--center' ); // phpcs:ignore ?>
		<div class="az-steps__grid">
			<figure class="az-steps__fig" data-az-rise><?php echo az_link_diagram(); // phpcs:ignore ?></figure>
			<ol class="az-steps__list">
				<?php $uid = 'az-steps-' . wp_unique_id(); ?>
				<?php foreach ( (array) $a['steps'] as $i => $s ) : ?>
					<li class="az-steps__item<?php echo 0 === $i ? ' is-on' : ''; ?>" data-az-step="<?php echo (int) $i; ?>" data-az-rise>
						<span class="az-steps__n az-label"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span>
						<h3 class="az-steps__t"><button class="az-steps__b" type="button" aria-controls="<?php echo esc_attr( $uid . '-' . $i ); ?>"><?php echo esc_html( $s['title'] ?? '' ); ?></button></h3>
						<p class="az-steps__p" id="<?php echo esc_attr( $uid . '-' . $i ); ?>"><?php echo esc_html( $s['text'] ?? '' ); ?></p>
						<span class="az-steps__bar" aria-hidden="true"><i></i></span>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
		<?php if ( $a['cta'] ) : ?><p class="az-center" data-az-rise><?php echo az_btn( $a['cta'], $a['cta_url'], 'link' ); // phpcs:ignore ?></p><?php endif; ?>
	</div>
	</section>
	<?php
}

function az_schema_flow() {
	return array(
		'title'  => 'Live QR flow (Broadcast / Live event)',
		'icon'   => 'eicon-flow',
		'fields' => az_head_fields( 'Step by step', "From the speaker\n*to the screen.*", 'The same seven steps run at home and in the stands. Only the speaker changes.', 'mist' ) + array(
			'tab_a'   => az_f( 'text', 'First tab', 'Broadcast' ),
			'steps_a' => az_f( 'textarea', 'First tab steps (one per line: icon | step)', "mic | The viewer’s phone microphone is on\ntv | The TV speakers send out an embedded micro-signal\nphone | The phone’s microphone hears the micro-signal\nlink | The TV speakers and the phone connect\nqr | The signal asks the phone to load a preset URL\ncloud | The Audazzio® cloud directs the phone to the desired content\nimage | The viewer sees the content on their phone", array( 'rows' => 8 ) ),
			'tab_b'   => az_f( 'text', 'Second tab', 'Live event' ),
			'steps_b' => az_f( 'textarea', 'Second tab steps (one per line: icon | step)', "mic | The fan’s phone microphone is on\nstadium | The seating bowl sound system sends out an embedded micro-signal\nphone | The phone’s microphone hears the micro-signal\nlink | The sound system and the phone connect\nqr | The signal asks the phone to load a preset URL\ncloud | The Audazzio® cloud directs the phone to the desired content\nimage | The fan sees the content on their phone", array( 'rows' => 8 ) ),
		),
	);
}

function az_render_flow( $a ) {
	if ( empty( $a['anchor'] ) ) {
		$a['anchor'] = 'flow';
	}
	$tabs = array( array( $a['tab_a'], az_pairs( $a['steps_a'] ) ), array( $a['tab_b'], az_pairs( $a['steps_b'] ) ) );
	$uid  = 'az-flow-' . wp_unique_id();
	$on   = $tabs[0][1] ? 0 : 1;
	az_open( $a, 'az-flow', 'data-az-flow' );
	?>
	<div class="az-wrap">
		<?php echo az_head( $a, 'az-head--center' ); // phpcs:ignore ?>
		<div class="az-seg" role="tablist" aria-label="Where it plays" data-az-rise>
			<?php foreach ( $tabs as $i => $t ) : if ( ! $t[1] ) { continue; } ?>
				<button class="az-seg__b" type="button" role="tab" id="<?php echo esc_attr( $uid . '-t' . $i ); ?>" aria-controls="<?php echo esc_attr( $uid . '-p' . $i ); ?>" aria-selected="<?php echo $on === $i ? 'true' : 'false'; ?>"<?php echo $on === $i ? '' : ' tabindex="-1"'; ?>><?php echo az_icon( 0 === $i ? 'tv' : 'stadium' ) . esc_html( $t[0] ); // phpcs:ignore ?></button>
			<?php endforeach; ?>
			<span class="az-seg__thumb" aria-hidden="true"></span>
		</div>
		<?php foreach ( $tabs as $i => $t ) : if ( ! $t[1] ) { continue; } ?>
			<div class="az-flow__panel" role="tabpanel" tabindex="0" id="<?php echo esc_attr( $uid . '-p' . $i ); ?>" aria-labelledby="<?php echo esc_attr( $uid . '-t' . $i ); ?>"<?php echo $on === $i ? '' : ' hidden'; ?>>
				<ol class="az-flow__steps">
					<?php foreach ( $t[1] as $j => $s ) : $icon = isset( $s[1] ) && '' !== $s[1] ? $s[0] : 'wave'; $txt = isset( $s[1] ) && '' !== $s[1] ? $s[1] : $s[0]; ?>
						<li class="az-flow__step<?php echo 0 === $j ? ' is-on' : ''; ?>">
							<span class="az-flow__ico"><?php echo az_icon( sanitize_key( $icon ) ) ?: az_icon( 'wave' ); // phpcs:ignore ?></span>
							<span class="az-flow__n az-label"><?php echo esc_html( sprintf( '%02d', $j + 1 ) ); ?></span>
							<span class="az-flow__t"><?php echo esc_html( $txt ); ?></span>
						</li>
					<?php endforeach; ?>
				</ol>
				<div class="az-flow__track" aria-hidden="true"><i></i></div>
			</div>
		<?php endforeach; ?>
	</div>
	</section>
	<?php
}

function az_schema_canvas() {
	return array(
		'title'  => 'What phones can show',
		'icon'   => 'eicon-gallery-grid',
		'fields' => az_head_fields( 'A fan engagement tool', "A second screen\n*is a blank canvas.*", 'Audazzio can deliver any web page, at any time, to any device within range of the inaudible signal.', 'white' ) + array(
			'items' => az_f( 'repeater', 'Tiles', array(
				array( 'icon' => 'image', 'label' => 'Images', 'text' => 'Player profiles, stats, replays and scenic extensions.' ),
				array( 'icon' => 'video', 'label' => 'Videos', 'text' => 'Exclusive angles and moments, pushed as they happen.' ),
				array( 'icon' => 'trophy', 'label' => 'Contests', 'text' => 'Competition entry the instant a promotion airs.' ),
				array( 'icon' => 'bag', 'label' => 'Shopping', 'text' => 'Merch, retail and concession offers, in context.' ),
				array( 'icon' => 'dice', 'label' => 'Betting and gaming', 'text' => 'Frictionless wagering, voting and polling.' ),
				array( 'icon' => 'gift', 'label' => 'Rewards', 'text' => 'Rewards and incentives for the people watching.' ),
			), array( 'fields' => array( 'icon' => az_f( 'select', 'Icon', 'image', array( 'options' => az_icon_options() ) ), 'label' => az_f( 'text', 'Label', '' ), 'text' => az_f( 'textarea', 'Text', '' ) ), 'title' => '{{{ label }}}' ) ),
			'kicker' => az_f( 'textarea', 'Closing line', "*Creating new inventory.* The Audazzio Live QR® technology creates a new marketing channel, with an all-new advertising inventory for broadcasters and event producers to sell." ),
		),
	);
}

function az_render_canvas( $a ) {
	az_open( $a, 'az-canvas' );
	?>
	<div class="az-wrap">
		<?php echo az_head( $a ); // phpcs:ignore ?>
		<ul class="az-tiles">
			<?php foreach ( (array) $a['items'] as $it ) : ?>
				<li class="az-tile" data-az-rise>
					<span class="az-tile__ico"><?php echo az_icon( sanitize_key( $it['icon'] ?? 'image' ) ); // phpcs:ignore ?></span>
					<h3 class="az-tile__t"><?php echo esc_html( $it['label'] ?? '' ); ?></h3>
					<p class="az-tile__p"><?php echo esc_html( $it['text'] ?? '' ); ?></p>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php if ( $a['kicker'] ) : ?><div class="az-kicker" data-az-rise><?php echo az_paras( $a['kicker'] ); // phpcs:ignore ?></div><?php endif; ?>
	</div>
	</section>
	<?php
}
