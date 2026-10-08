<?php
/**
 * Try Audazzio: the demo player, the four steps (open the listener, allow the microphone, speakers on, press play),
 * the phone that shows what arrives, and the listener link with a QR code for desktop visitors (store buttons
 * only once an app is published).
 * The same pieces build the inline section and the sheet that opens from the "Try Audazzio now" notification.
 */

defined( 'ABSPATH' ) || exit;

function az_try_default_steps() {
	return array(
		array( 'title' => 'Open the listener on your phone', 'text' => 'Scan the code with your phone’s camera, or tap the button if you are on your phone. It opens in the browser: nothing to download.' ),
		array( 'title' => 'Tap the logo, allow the microphone', 'text' => 'Tap the Audazzio logo in the middle of the screen and allow microphone access. The peach circle means your phone is listening.' ),
		array( 'title' => 'Turn your speakers on', 'text' => 'Volume up on this computer, TV or tablet, loud enough to be heard. Keep your phone close by.' ),
		array( 'title' => 'Press play', 'text' => 'Start a demo here and watch your phone. New content arrives in sync with the video, with a ding.' ),
	);
}

/** The steps set under Audazzio > Settings ("Title | text" lines), else the four above. The popup shows these, and so does any Try section whose own list is empty. */
function az_try_shared_steps() {
	$out = array();
	foreach ( az_pairs( (string) az_opt( 'try_steps' ) ) as $p ) {
		if ( '' !== $p[0] ) {
			$out[] = array( 'title' => $p[0], 'text' => $p[1] );
		}
	}
	return $out ? $out : az_try_default_steps();
}

/** A text set under Audazzio > Settings, else the one it has always had. */
function az_try_text( $key, $fallback ) {
	$v = trim( (string) az_opt( $key ) );
	return '' !== $v ? $v : $fallback;
}

/** The phone screens the simulation cycles through (Audazzio's own second-screen mock-ups). */
function az_try_screens() {
	$out = array();
	foreach ( (array) az_data( 'screens' ) as $s ) {
		$out[] = array( 'src' => az_asset( 'img/' . $s['file'] ), 'label' => $s['label'] ?? '' );
	}
	return $out;
}

function az_store_buttons( $class = '' ) {
	$ios = (string) az_opt( 'app_store' );
	$and = (string) az_opt( 'play_store' );
	$apple = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M16.37 12.6c-.02-2.2 1.8-3.26 1.88-3.31-1.03-1.5-2.62-1.7-3.18-1.73-1.35-.14-2.65.8-3.33.8-.69 0-1.75-.78-2.88-.76-1.48.02-2.85.86-3.61 2.19-1.54 2.67-.39 6.62 1.1 8.79.73 1.06 1.6 2.25 2.74 2.2 1.1-.04 1.52-.71 2.85-.71 1.33 0 1.7.71 2.87.69 1.19-.02 1.94-1.08 2.66-2.15.84-1.23 1.19-2.42 1.2-2.48-.03-.01-2.3-.88-2.3-3.53ZM14.2 6.13c.6-.74 1.01-1.75.9-2.77-.87.04-1.93.58-2.55 1.31-.56.64-1.05 1.68-.92 2.67.97.07 1.96-.49 2.57-1.21Z"/></svg>';
	$play  = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M4.2 2.6c-.2.2-.3.55-.3.98v16.84c0 .43.1.78.3.98l.05.05 9.43-9.43v-.04L4.25 2.55l-.05.05Zm12.62 12.57-3.14-3.15v-.04l3.14-3.15.07.04 3.72 2.11c1.06.6 1.06 1.59 0 2.2l-3.72 2.11-.07.04Zm-.07.04-3.21-3.21-9.48 9.48c.35.37.93.42 1.58.05l11.11-6.32Zm0-6.43L5.64 2.47c-.65-.37-1.23-.32-1.58.05l9.48 9.48 3.21-3.22Z"/></svg>';
	if ( ! $ios && ! $and ) {
		return '';
	}
	$h = '<div class="az-stores ' . esc_attr( $class ) . '">';
	if ( $ios ) {
		$h .= sprintf( '<a class="az-store" href="%s" target="_blank" rel="noopener">%s<span><small>Download on the</small>App Store</span></a>', esc_url( $ios ), $apple );
	}
	if ( $and ) {
		$h .= sprintf( '<a class="az-store" href="%s" target="_blank" rel="noopener">%s<span><small>Get it on</small>Google Play</span></a>', esc_url( $and ), $play );
	}
	return $h . '</div>';
}

/** The demo player: a screen with a poster and a big play button; the clip loads only when played. */
function az_player( $poster = '' ) {
	$cfg    = az_front_config();
	$poster = $poster ? $poster : ( $cfg['demo']['poster'] ? $cfg['demo']['poster'] : ( $cfg['demo']['id'] ? az_asset( 'img/yt-' . $cfg['demo']['id'] . '.jpg' ) : '' ) );
	$clips  = az_demo_clips();
	$hint   = 'az-player-hint-' . wp_unique_id();
	ob_start();
	?>
	<div class="az-player" data-az-player>
		<?php if ( count( $clips ) > 1 ) : ?>
			<div class="az-player__pick" role="group" aria-label="Pick a demo">
				<span class="az-label">Pick a demo</span>
				<?php foreach ( $clips as $i => $c ) : ?><button type="button" class="az-player__clip" aria-pressed="<?php echo 0 === $i ? 'true' : 'false'; ?>" data-az-clip="<?php echo (int) $i; ?>"><?php echo esc_html( $c['label'] ? $c['label'] : 'Demo ' . ( $i + 1 ) ); ?></button><?php endforeach; ?>
			</div>
		<?php endif; ?>
		<div class="az-player__screen">
			<?php if ( $poster ) : ?><img class="az-player__poster" src="<?php echo esc_url( $poster ); ?>" alt="" loading="lazy" decoding="async"><?php endif; ?>
			<div class="az-player__media" data-az-player-media></div>
			<span class="az-player__wait" aria-hidden="true"></span>
			<button class="az-player__play" type="button" data-az-play aria-label="Play the Audazzio demo" aria-describedby="<?php echo esc_attr( $hint ); ?>">
				<span class="az-player__ring" aria-hidden="true"></span>
				<span class="az-player__ico"><?php echo az_icon( 'play' ); // phpcs:ignore ?></span>
			</button>
			<p class="az-player__live az-label" aria-hidden="true"><i></i><span>Signal on air</span></p>
		</div>
		<div class="az-player__bar">
			<span class="az-player__meter" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span>
			<span class="az-player__hint" id="<?php echo esc_attr( $hint ); ?>"><?php echo az_icon( 'speaker' ); // phpcs:ignore ?><span class="az-player__hint-d">Speakers on, volume up</span><span class="az-player__hint-m">Play this on a computer or TV</span></span>
			<button class="az-player__toggle" type="button" data-az-toggle hidden><?php echo az_icon( 'pause' ); // phpcs:ignore ?><span>Pause</span></button>
		</div>
	</div>
	<?php
	return (string) ob_get_clean();
}

/** The phone beside the player: what a visitor's phone shows when the signal lands (a simulation). */
function az_phone_sim() {
	$screens = az_try_screens();
	if ( ! $screens ) {
		return '';
	}
	ob_start();
	?>
	<div class="az-sim" data-az-sim role="figure" aria-label="What your phone shows when the signal lands">
		<div class="az-sim__phone">
			<div class="az-sim__screen">
				<div class="az-sim__idle"><span class="az-sim__sym"><?php echo az_mark( 'symbol' ); // phpcs:ignore ?></span><span class="az-label">Listening</span><span class="az-sim__eq" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span></div>
				<?php foreach ( $screens as $i => $s ) : ?>
					<img class="az-sim__shot" src="<?php echo esc_url( $s['src'] ); ?>" alt="<?php echo esc_attr( $s['label'] ); ?>" loading="lazy" decoding="async" data-az-shot="<?php echo (int) $i; ?>">
				<?php endforeach; ?>
				<div class="az-sim__toast"><span class="az-sim__toast-ico"><?php echo az_mark( 'symbol' ); // phpcs:ignore ?></span><span><b>Audazzio</b><span data-az-sim-label>Content received</span></span></div>
			</div>
		</div>
		<p class="az-sim__cap az-label">No phone handy? This is what it shows.</p>
	</div>
	<?php
	return (string) ob_get_clean();
}

/** The four steps, as a checklist; the store buttons and the QR code sit under the first. No steps given: the shared ones. */
function az_try_steps( $steps = null ) {
	$steps = array_values( array_filter( (array) $steps, function ( $s ) {
		return is_array( $s ) && '' !== trim( (string) ( $s['title'] ?? '' ) . (string) ( $s['text'] ?? '' ) );
	} ) );
	$steps = $steps ? $steps : az_try_shared_steps();
	ob_start();
	?>
	<ol class="az-trysteps" data-az-trysteps>
		<?php foreach ( $steps as $i => $s ) : ?>
			<li class="az-trysteps__i<?php echo 0 === $i ? ' is-on' : ''; ?>" data-az-trystep="<?php echo (int) $i; ?>">
				<span class="az-trysteps__n" aria-hidden="true"><b><?php echo (int) $i + 1; ?></b><?php echo az_icon( 'check' ); // phpcs:ignore ?></span>
				<div class="az-trysteps__c">
					<h3 class="az-trysteps__t"><?php echo esc_html( $s['title'] ?? '' ); ?></h3>
					<p class="az-trysteps__p"><?php echo esc_html( $s['text'] ?? '' ); ?></p>
					<?php if ( 0 === $i ) : $listener = (string) az_opt( 'listener_url' ); ?>
						<div class="az-trysteps__get">
							<?php if ( $listener ) : ?>
								<a class="az-btn az-btn--ink az-btn--sm az-trysteps__open" href="<?php echo esc_url( $listener ); ?>" target="_blank" rel="noopener"><span>Open the Audazzio listener</span><?php echo az_icon( 'arrow-ur' ); // phpcs:ignore ?></a>
								<div class="az-qr" data-az-qr="<?php echo esc_attr( $listener ); ?>"><span class="az-qr__code" aria-hidden="true"></span><span class="az-qr__cap">On a computer? Point your phone’s camera here to open the listener.</span></div>
							<?php endif; ?>
							<?php echo az_store_buttons(); // phpcs:ignore ?>
						</div>
					<?php endif; ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ol>
	<?php
	return (string) ob_get_clean();
}

function az_schema_try() {
	return array(
		'title'  => 'Try Audazzio (player)',
		'icon'   => 'eicon-play',
		'note'   => 'The listener link, the demo clips, the poster and any App Store or Google Play links are set once under Audazzio > Settings, and every player on the site uses them. Leave Steps empty to show the steps set under Audazzio > Settings, which the Try Audazzio popup shows too; add steps here only for a list this section alone should show.',
		'fields' => az_head_fields( 'Try Audazzio now', "Turn your speakers on.\n*Watch your phone.*", 'No app to download. Open the Audazzio listener on your phone, press play here, and see Live QR work from this page’s sound.', 'mist' ) + array(
			'steps'  => az_f( 'repeater', 'Steps', array(), array( 'fields' => array( 'title' => az_f( 'text', 'Step', '' ), 'text' => az_f( 'textarea', 'Text', '' ) ), 'title' => '{{{ title }}}' ) ),
			'mobile' => az_f( 'textarea', 'Note for phone visitors', 'On your phone right now? Open the listener here, then play the demo on a computer, TV or tablet: audazzio.com/try.' ),
		),
	);
}

function az_render_try( $a ) {
	if ( empty( $a['anchor'] ) ) {
		$a['anchor'] = 'try';
	}
	az_open( $a, 'az-try', 'data-az-try-section' );
	?>
	<div class="az-wrap">
		<?php echo az_head( $a, 'az-head--center' ); // phpcs:ignore ?>
		<div class="az-try__grid">
			<div class="az-try__stage" data-az-rise>
				<?php echo az_player(); // phpcs:ignore ?>
				<?php echo az_phone_sim(); // phpcs:ignore ?>
			</div>
			<div class="az-try__steps" data-az-rise>
				<?php echo az_try_steps( (array) $a['steps'] ); // phpcs:ignore ?>
				<?php if ( $a['mobile'] ) : ?><p class="az-try__mobile"><?php echo az_icon( 'info' ) . esc_html( $a['mobile'] ); // phpcs:ignore ?></p><?php endif; ?>
			</div>
		</div>
	</div>
	</section>
	<?php
}

/**
 * The sheet that opens from the notification, "Try it" in the header, or any link to /try/. Its headline,
 * intro, steps and phone note are set under Audazzio > Settings. On a phone the note and the listener button
 * come first, since the demo has to play on another screen.
 */
function az_try_sheet() {
	$intro    = trim( (string) az_opt( 'try_intro' ) );
	$note     = az_try_text( 'try_note', 'On your phone right now? Open the listener here, then play the demo on a computer, TV or tablet: audazzio.com/try.' );
	$listener = (string) az_opt( 'listener_url' );
	ob_start();
	?>
	<div class="az-sheet__head">
		<p class="az-eyebrow">Try Audazzio now</p>
		<h2 class="az-sheet__title" id="az-try-title"><?php echo az_hl( az_try_text( 'try_title', "Your second screen,\n*in four steps.*" ) ); // phpcs:ignore ?></h2>
		<?php if ( '' !== $intro ) : ?><div class="az-sheet__intro"><?php echo az_paras( $intro ); // phpcs:ignore ?></div><?php endif; ?>
	</div>
	<div class="az-sheet__phone">
		<p class="az-try__mobile"><?php echo az_icon( 'info' ) . esc_html( $note ); // phpcs:ignore ?></p>
		<?php if ( $listener ) : ?><a class="az-btn az-btn--ink az-sheet__open" href="<?php echo esc_url( $listener ); ?>" target="_blank" rel="noopener"><span>Open the Audazzio listener</span><?php echo az_icon( 'arrow-ur' ); // phpcs:ignore ?></a><?php endif; ?>
	</div>
	<div class="az-sheet__grid az-sheet__grid--try">
		<div class="az-sheet__stage"><?php echo az_player() . az_phone_sim(); // phpcs:ignore ?></div>
		<div class="az-sheet__steps"><?php echo az_try_steps(); // phpcs:ignore ?><p class="az-try__mobile az-sheet__note"><?php echo az_icon( 'info' ) . esc_html( $note ); // phpcs:ignore ?></p></div>
	</div>
	<?php
	return (string) ob_get_clean();
}
