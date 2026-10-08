<?php
/**
 * Join the Wave: the closing band, and the four-step form that qualifies and grades each inquiry
 * (who, what for, how big, send). The questions, their answers and the points each answer is worth live
 * in az_join_questions(): the starter table below, with the edits saved under Audazzio > Join the Wave form
 * (option az_join_form, see includes/join-settings.php). The server grades every inquiry from that table
 * (includes/leads.php); the static preview grades with the same table in the browser.
 */

defined( 'ABSPATH' ) || exit;

/** The edits saved under Audazzio > Join the Wave form. */
function az_join_saved() {
	$s = get_option( 'az_join_form', array() );
	return is_array( $s ) ? $s : array();
}

/** Every question after "who": label, kind, the step it sits on, and options as key => [label, points]. */
function az_join_questions() {
	$q     = az_join_default_questions();
	$saved = az_join_saved()['questions'] ?? array();
	foreach ( $q as $k => $def ) {
		$s = $saved[ $k ] ?? array();
		if ( ! empty( $s['label'] ) && is_string( $s['label'] ) ) {
			$q[ $k ]['label'] = $s['label'];
		}
		if ( ! empty( $s['options'] ) && is_array( $s['options'] ) ) {
			$opts = array();
			foreach ( $s['options'] as $ok => $o ) {
				if ( is_array( $o ) && isset( $o[0] ) && '' !== (string) $o[0] ) {
					$opts[ (string) $ok ] = array( (string) $o[0], (int) ( $o[1] ?? 0 ) );
				}
			}
			if ( $opts ) {
				$q[ $k ]['options'] = $opts;
			}
		}
	}
	return apply_filters( 'az_join_questions', $q );
}

/** The starter questions, answers and points. */
function az_join_default_questions() {
	return array(
		'org'       => array(
			'label'   => 'Which best describes your organization?',
			'type'    => 'radio',
			'step'    => 2,
			'options' => array(
				'broadcaster' => array( 'Broadcaster or network', 20 ),
				'team'        => array( 'Team, league or federation', 18 ),
				'brand'       => array( 'Brand, sponsor or agency', 16 ),
				'venue'       => array( 'Venue or live event producer', 16 ),
				'tech'        => array( 'Technology or platform partner', 12 ),
				'other'       => array( 'Something else', 6 ),
			),
		),
		'uses'      => array(
			'label'   => 'What would you like Audazzio to do? Pick any.',
			'type'    => 'checkbox',
			'step'    => 2,
			'options' => array(
				'broadcast' => array( 'Live sports broadcast', 5 ),
				'venue'     => array( 'Fans in the venue', 5 ),
				'sponsor'   => array( 'Sponsor activation', 5 ),
				'studio'    => array( 'Studio or entertainment show', 3 ),
				'concert'   => array( 'Concert or tour', 3 ),
				'corporate' => array( 'Corporate meeting', 3 ),
				'casino'    => array( 'Casino or resort', 3 ),
				'park'      => array( 'Theme park', 3 ),
				'polling'   => array( 'Voting and polling', 3 ),
				'other'     => array( 'Something else', 1 ),
			),
		),
		'audience'  => array(
			'label'   => 'How many people watch or attend each broadcast or event?',
			'type'    => 'radio',
			'step'    => 3,
			'options' => array(
				'xl' => array( 'Over 1 million', 20 ),
				'l'  => array( '100,000 to 1 million', 15 ),
				'm'  => array( '10,000 to 100,000', 10 ),
				's'  => array( 'Under 10,000', 5 ),
				'na' => array( 'Not sure yet', 4 ),
			),
		),
		'frequency' => array(
			'label'   => 'How many broadcasts or events a year?',
			'type'    => 'radio',
			'step'    => 3,
			'options' => array(
				'many'   => array( 'More than 50', 10 ),
				'season' => array( '11 to 50', 8 ),
				'few'    => array( '2 to 10', 5 ),
				'one'    => array( 'One event', 3 ),
			),
		),
		'budget'    => array(
			'label'   => 'What budget have you set aside?',
			'type'    => 'radio',
			'step'    => 3,
			'options' => array(
				'xl'  => array( '$150K or more', 20 ),
				'l'   => array( '$50K to $150K', 15 ),
				'm'   => array( '$10K to $50K', 9 ),
				's'   => array( 'Under $10K', 3 ),
				'tbd' => array( 'Not defined yet', 5 ),
			),
		),
		'timeline'  => array(
			'label'   => 'When would you like to launch?',
			'type'    => 'radio',
			'step'    => 3,
			'options' => array(
				'now'     => array( 'Within 30 days', 10 ),
				'soon'    => array( 'In 1 to 3 months', 8 ),
				'later'   => array( 'In 3 to 6 months', 5 ),
				'explore' => array( 'Just exploring', 2 ),
			),
		),
		'role'      => array(
			'label'   => 'Your part in the decision',
			'type'    => 'radio',
			'step'    => 3,
			'options' => array(
				'decide'    => array( 'I make the decision', 10 ),
				'recommend' => array( 'I recommend', 6 ),
				'research'  => array( 'I’m researching', 2 ),
			),
		),
	);
}

/** Grade thresholds (score out of 100) and what each grade means to the team. A, B and C minimums can be edited. */
function az_join_grades() {
	$g     = array(
		'A' => array( 75, 'Priority', 'Strong fit, real scale and a near date. Call within one business day.' ),
		'B' => array( 55, 'Qualified', 'Good fit. Book a discovery call.' ),
		'C' => array( 35, 'Nurture', 'Early or small. Send the case studies and stay in touch.' ),
		'D' => array( 0, 'Early', 'Exploring. Answer the question and add to updates.' ),
	);
	$saved = az_join_saved()['grades'] ?? array();
	foreach ( array( 'A', 'B', 'C' ) as $k ) {
		if ( isset( $saved[ $k ] ) && is_numeric( $saved[ $k ] ) ) {
			$g[ $k ][0] = max( 0, min( 100, (int) $saved[ $k ] ) );
		}
	}
	return apply_filters( 'az_join_grades', $g );
}

/** The engagement each organization type usually means, used to name the inquiry for the team. */
function az_join_natures() {
	$n     = array(
		'broadcaster' => 'Broadcast partnership',
		'team'        => 'Rights holder program',
		'brand'       => 'Sponsor campaign',
		'venue'       => 'Live event activation',
		'tech'        => 'Technology partnership',
		'other'       => 'General inquiry',
	);
	$saved = az_join_saved()['questions']['org']['options'] ?? array();
	foreach ( (array) $saved as $k => $o ) {
		if ( is_array( $o ) && ! empty( $o[2] ) ) {
			$n[ (string) $k ] = (string) $o[2];
		}
	}
	return $n;
}

/** The step names in the progress bar, the step headings and the success headline. */
function az_join_default_texts() {
	return array(
		'name1' => 'You',
		'name2' => 'Your idea',
		'name3' => 'Scale',
		'name4' => 'Send',
		'step1' => 'First, who’s making waves?',
		'step2' => 'What do you want to use Audazzio for?',
		'step3' => 'How big is the wave?',
		'step4' => 'Ready to send?',
		'done'  => 'You’re on the wave.',
	);
}

/** One of those texts: the saved one, else the default. */
function az_join_text( $key ) {
	$saved = az_join_saved()['texts'][ $key ] ?? '';
	return is_string( $saved ) && '' !== trim( $saved ) ? $saved : ( az_join_default_texts()[ $key ] ?? '' );
}

/** The progress bar: step number => name. */
function az_join_step_names() {
	$out = array();
	for ( $n = 1; $n <= 4; $n++ ) {
		$out[ $n ] = az_join_text( 'name' . $n );
	}
	return $out;
}

function az_join_free_domains() {
	return array( 'gmail.com', 'googlemail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'live.com', 'aol.com', 'icloud.com', 'me.com', 'mac.com', 'msn.com', 'proton.me', 'protonmail.com', 'gmx.com', 'ymail.com' );
}

/** True only for the static exporter (scripts/deploy/export.mjs), which asks from this machine with x-az-static: 1. */
function az_is_static_export() {
	$ip = (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ); // phpcs:ignore
	return '1' === (string) ( $_SERVER['HTTP_X_AZ_STATIC'] ?? '' ) && in_array( $ip, array( '127.0.0.1', '::1' ), true ); // phpcs:ignore
}

/**
 * What the page tells the form script. The live site grades on the server, so the page carries only the
 * question and answer labels (for the review step). The static preview has no server: the exporter gets the
 * full table (points, grades, natures) so the browser can grade.
 */
function az_join_public_config() {
	if ( az_is_static_export() ) {
		return array( 'questions' => az_join_questions(), 'grades' => az_join_grades(), 'natures' => az_join_natures(), 'free' => az_join_free_domains() );
	}
	$qs = array();
	foreach ( az_join_questions() as $k => $q ) {
		$opts = array();
		foreach ( $q['options'] as $ok => $o ) {
			$opts[ $ok ] = array( $o[0] );
		}
		$qs[ $k ] = array( 'label' => $q['label'], 'type' => $q['type'], 'options' => $opts );
	}
	return array( 'questions' => $qs );
}

/** A question as a group of chips. Its error line is there from the start (empty), and every chip points to it. */
function az_join_choice( $name, $q ) {
	$type = 'checkbox' === $q['type'] ? 'checkbox' : 'radio';
	$err  = 'az-j-' . sanitize_key( $name ) . '-err-' . wp_unique_id();
	$h    = '<fieldset class="az-q" data-az-q="' . esc_attr( $name ) . '" aria-describedby="' . esc_attr( $err ) . '"><legend class="az-q__l">' . esc_html( $q['label'] ) . '</legend><div class="az-chipset">';
	foreach ( $q['options'] as $k => $o ) {
		$h .= sprintf( '<label class="az-chip"><input type="%1$s" name="%2$s" value="%3$s"%4$s aria-describedby="%6$s"><span>%5$s</span></label>', $type, esc_attr( 'checkbox' === $type ? $name . '[]' : $name ), esc_attr( $k ), 'radio' === $type ? ' required' : '', esc_html( $o[0] ), esc_attr( $err ) );
	}
	return $h . '</div><span class="az-field__err" id="' . esc_attr( $err ) . '" aria-live="polite"></span></fieldset>';
}

function az_join_field( $name, $label, $type = 'text', $auto = '', $extra = '' ) {
	return sprintf(
		'<label class="az-field"><input class="az-field__in" type="%1$s" name="%2$s" id="az-j-%2$s-%6$s" placeholder=" " autocomplete="%3$s" required %4$s><span class="az-field__l">%5$s</span><span class="az-field__err" aria-live="polite"></span></label>',
		esc_attr( $type ),
		esc_attr( $name ),
		esc_attr( $auto ),
		$extra,
		esc_html( $label ),
		esc_attr( wp_unique_id() )
	);
}

/** The form. $context: "page" or "sheet" (the dialog opened from any Join the Wave button). */
function az_join_form( $context = 'page' ) {
	$qs    = az_join_questions();
	$steps = az_join_step_names();
	ob_start();
	?>
	<form class="az-join az-join--<?php echo esc_attr( $context ); ?>" data-az-form action="<?php echo esc_url( rest_url( 'audazzio/v1/join' ) ); ?>" method="post" novalidate>
		<div class="az-join__top">
			<ol class="az-join__progress" aria-label="Progress">
				<?php foreach ( $steps as $n => $label ) : ?><li class="<?php echo 1 === $n ? 'is-on' : ''; ?>" data-az-dot="<?php echo (int) $n; ?>"><span><?php echo (int) $n; ?></span><b><?php echo esc_html( $label ); ?></b></li><?php endforeach; ?>
			</ol>
			<div class="az-join__bar" aria-hidden="true"><i></i></div>
		</div>
		<div class="az-join__steps">
			<section class="az-join__step is-on" data-az-jstep="1" aria-label="About you">
				<h3 class="az-join__q"><?php echo esc_html( az_join_text( 'step1' ) ); ?></h3>
				<div class="az-join__fields">
					<?php
					echo az_join_field( 'name', 'Full name', 'text', 'name' ); // phpcs:ignore
					echo az_join_field( 'position', 'Position or title', 'text', 'organization-title' ); // phpcs:ignore
					echo az_join_field( 'company', 'Company or organization', 'text', 'organization' ); // phpcs:ignore
					echo az_join_field( 'email', 'Work email', 'email', 'email' ); // phpcs:ignore
					echo az_join_field( 'phone', 'Phone', 'tel', 'tel', 'inputmode="tel"' ); // phpcs:ignore
					?>
				</div>
			</section>
			<section class="az-join__step" data-az-jstep="2" aria-label="Your idea" hidden>
				<h3 class="az-join__q"><?php echo esc_html( az_join_text( 'step2' ) ); ?></h3>
				<?php echo az_join_choice( 'org', $qs['org'] ) . az_join_choice( 'uses', $qs['uses'] ); // phpcs:ignore ?>
				<label class="az-field az-field--area"><textarea class="az-field__in" name="details" rows="4" placeholder=" " required minlength="20"></textarea><span class="az-field__l">Tell us about it: the event or show, the moment, what fans should see</span><span class="az-field__err" aria-live="polite"></span></label>
			</section>
			<section class="az-join__step" data-az-jstep="3" aria-label="Scale and budget" hidden>
				<h3 class="az-join__q"><?php echo esc_html( az_join_text( 'step3' ) ); ?></h3>
				<?php foreach ( array( 'audience', 'frequency', 'budget', 'timeline', 'role' ) as $k ) { echo az_join_choice( $k, $qs[ $k ] ); } // phpcs:ignore ?>
			</section>
			<section class="az-join__step" data-az-jstep="4" aria-label="Review and send" hidden>
				<h3 class="az-join__q"><?php echo esc_html( az_join_text( 'step4' ) ); ?></h3>
				<dl class="az-join__review" data-az-review></dl>
				<?php $consent = 'az-j-consent-' . wp_unique_id(); ?>
				<div class="az-check"><input type="checkbox" name="consent" value="1" required id="<?php echo esc_attr( $consent ); ?>" aria-describedby="<?php echo esc_attr( $consent ); ?>-err"><label for="<?php echo esc_attr( $consent ); ?>">Audazzio may contact me about this inquiry. See the <a href="<?php echo esc_url( az_opt( 'privacy_url' ) ); ?>" target="_blank" rel="noopener">Privacy Policy</a>.</label><span class="az-field__err" id="<?php echo esc_attr( $consent ); ?>-err" aria-live="polite"></span></div>
			</section>
		</div>
		<div class="az-join__hp" aria-hidden="true"><label>Leave this empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
		<input type="hidden" name="t" value="" data-az-t>
		<input type="hidden" name="source" value="" data-az-source>
		<p class="az-join__msg" role="alert" aria-live="assertive" hidden></p>
		<div class="az-join__nav">
			<button class="az-btn az-btn--ghost az-join__back" type="button" data-az-back hidden><?php echo az_icon( 'arrow' ); // phpcs:ignore ?><span>Back</span></button>
			<button class="az-btn az-btn--ink az-join__next" type="button" data-az-next><span>Continue</span><?php echo az_icon( 'arrow' ); // phpcs:ignore ?></button>
			<button class="az-btn az-btn--wave az-join__send" type="submit" data-az-send hidden><span>Send to Audazzio</span><?php echo az_icon( 'arrow' ); // phpcs:ignore ?></button>
		</div>
		<div class="az-join__done" data-az-done hidden tabindex="-1">
			<span class="az-join__done-ico"><?php echo az_icon( 'check' ); // phpcs:ignore ?></span>
			<h3 class="az-join__q"><?php echo esc_html( az_join_text( 'done' ) ); ?></h3>
			<p class="az-join__thanks" data-az-thanks><?php echo esc_html( az_opt( 'thanks_line' ) ); ?></p>
			<div class="az-join__grade" data-az-grade hidden></div>
			<div class="az-join__next-steps">
				<a class="az-btn az-btn--ghost" href="<?php echo esc_url( home_url( '/newsroom/' ) ); ?>"><span>Read the case studies</span><?php echo az_icon( 'arrow' ); // phpcs:ignore ?></a>
				<a class="az-btn az-btn--ghost" href="<?php echo esc_url( home_url( '/try/' ) ); ?>" data-az-try><span>Try Audazzio now</span><?php echo az_icon( 'arrow' ); // phpcs:ignore ?></a>
			</div>
		</div>
	</form>
	<?php
	return (string) ob_get_clean();
}

function az_schema_join() {
	return array(
		'title'  => 'Join the Wave (form)',
		'icon'   => 'eicon-form-horizontal',
		'note'   => 'Inquiries arrive under Audazzio > Inbox with their grade. Where they are emailed, and the optional HubSpot connection, are set under Audazzio > Settings.',
		'fields' => az_head_fields( 'Join the Wave', "Let’s make\n*some waves.*", 'Tell us who you are, what you have in mind and how big the audience is. Four short steps, about two minutes. The right person at Audazzio will be in touch.', 'white' ) + array(
			'points' => az_f( 'textarea', 'Beside the form (one per line)', "Broadcasters, teams, leagues and federations\nSponsors, brands and agencies\nVenues, concerts, studio shows and live events" ),
		),
	);
}

function az_render_join( $a ) {
	if ( empty( $a['anchor'] ) ) {
		$a['anchor'] = 'join';
	}
	az_open( $a, 'az-joinsec' );
	$email = (string) az_opt( 'email' );
	$phone = (string) az_opt( 'phone' );
	$loc   = (string) az_opt( 'location' );
	?>
	<div class="az-wrap az-joinsec__grid">
		<div class="az-joinsec__side">
			<?php echo az_head( $a, '', 'h1' ); // phpcs:ignore ?>
			<?php $pts = az_lines( $a['points'] ); if ( $pts ) : ?><ul class="az-checks"><?php foreach ( $pts as $p ) : ?><li><?php echo az_icon( 'check' ) . esc_html( $p ); // phpcs:ignore ?></li><?php endforeach; ?></ul><?php endif; ?>
			<?php if ( $email || $phone || $loc ) : ?>
				<ul class="az-joinsec__contact">
					<?php if ( $email ) : ?><li><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></li><?php endif; ?>
					<?php if ( $phone ) : ?><li><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></li><?php endif; ?>
					<?php if ( $loc ) : ?><li><?php echo esc_html( $loc ); ?></li><?php endif; ?>
				</ul>
			<?php endif; ?>
		</div>
		<div class="az-joinsec__form"><?php echo az_join_form( 'page' ); // phpcs:ignore ?></div>
	</div>
	</section>
	<?php
}

function az_schema_cta() {
	return array(
		'title'  => 'Join the Wave band',
		'icon'   => 'eicon-call-to-action',
		'fields' => array(
			'tone'    => az_f( 'select', 'Background', 'white', array( 'options' => az_tones() ) ),
			'eyebrow' => az_f( 'text', 'Small line', 'It comes in waves.' ),
			'title'   => az_f( 'textarea', 'Headline', "Join the Wave." ),
			'intro'   => az_f( 'textarea', 'Intro', 'Tell us about your broadcast, venue or brand, and see what Live QR can do for your audience.' ),
			'cta'     => az_f( 'text', 'Button', 'Join the Wave' ),
			'cta_url' => az_f( 'url', 'Button link', '/join/' ),
			'alt'     => az_f( 'text', 'Second link', 'Try Audazzio now' ),
			'alt_url' => az_f( 'url', 'Second link address', '/try/' ),
		),
	);
}

function az_render_cta( $a ) {
	az_open( $a, 'az-cta' );
	?>
	<div class="az-cta__waves" aria-hidden="true"><canvas data-az-waves="cta"></canvas></div>
	<div class="az-wrap az-cta__in">
		<?php if ( $a['eyebrow'] ) : ?><p class="az-eyebrow" data-az-rise><?php echo esc_html( $a['eyebrow'] ); ?></p><?php endif; ?>
		<h2 class="az-cta__title" data-az-rise><?php echo az_hl( $a['title'] ); // phpcs:ignore ?></h2>
		<?php if ( $a['intro'] ) : ?><div class="az-cta__intro" data-az-rise><?php echo az_paras( $a['intro'] ); // phpcs:ignore ?></div><?php endif; ?>
		<div class="az-cta__ctas" data-az-rise><?php echo az_btn( $a['cta'], $a['cta_url'], 'wave az-btn--lg' ) . az_btn( $a['alt'], $a['alt_url'], 'link' ); // phpcs:ignore ?></div>
	</div>
	</section>
	<?php
}
