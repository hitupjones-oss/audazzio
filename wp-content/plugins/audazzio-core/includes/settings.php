<?php
/**
 * Audazzio > Settings: where Join the Wave inquiries go, the app store links, the Try Audazzio demo and
 * popup, the company links, and the footer and bar lines. The starter values are saved once (az_settings_seed),
 * and from then on what is saved is what the site uses: an emptied optional box stays empty. Only the texts
 * the site cannot do without fall back to their default when emptied; each box's help text says which.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings, their labels, help text and defaults: key => array( label, default, help, 'text'|'textarea', keep ).
 * keep = true: an empty box shows the default. Otherwise an empty box means "none".
 */
function az_settings_schema() {
	$s = array(
		'leads'  => array(
			'title'  => 'Join the Wave',
			'fields' => array(
				'leads_email'    => array( 'Send new inquiries to', '', 'One or more email addresses, separated by commas. Empty: the site administrator’s address.' ),
				'leads_a_email'  => array( 'Also send grade A inquiries to', '', 'Optional, empty for none. For example the CEO, so the best-fit inquiries are seen first.' ),
				'hubspot_portal' => array( 'HubSpot portal ID', '', 'Optional, empty for none. With a portal ID and a form ID, every inquiry is also sent to that HubSpot form (name, title, company, email, phone, and the answers and grade in the message).' ),
				'hubspot_form'   => array( 'HubSpot form ID', '', 'Optional, empty for none. The questions, points, step titles and the thank-you line are under Join the Wave form.' ),
			),
		),
		// Edited on the Join the Wave form screen (includes/join-settings.php), stored with the other settings.
		'form'   => array(
			'title'  => 'After sending',
			'screen' => 'form',
			'fields' => array(
				'thanks_line' => array( 'Thank-you line', 'A member of our team will be in touch shortly.', 'Under the success headline once an inquiry is sent. Empty: the default.', 'text', true ),
			),
		),
		'try'    => array(
			'title'  => 'Try Audazzio',
			'fields' => array(
				'listener_url' => array( 'Phone listener link', '', 'The page a phone opens to listen (Audazzio’s web listener). The Try Audazzio steps link to it and its QR code points to it. Empty: the default.', 'text', true ),
				'app_store'    => array( 'App Store link', '', 'Optional, empty for none. When there is an Audazzio app on the App Store, paste its link and an App Store button appears beside the listener.' ),
				'play_store'   => array( 'Google Play link', '', 'Optional, empty for none. The same for Google Play.' ),
				'demo_video'   => array( 'Demo clip', '', 'The clip the Try Audazzio player plays first: a YouTube link, or the address of an MP4 uploaded to the Media Library. Its soundtrack must carry the signal. An MP4 is safest: video sites re-encode sound and can strip the high frequencies the signal rides on. Empty: the default.', 'text', true ),
				'demo_label'   => array( 'Demo clip name', '', 'Optional. A short name for the first clip, shown on its button when there is more than one clip (for example "Formula 1").' ),
				'demo_more'    => array( 'More demo clips', '', 'Optional, empty for none. One per line: Name | link. Each becomes a button above the player.', 'textarea' ),
				'demo_poster'  => array( 'Demo poster', '', 'Optional. The picture shown before the demo plays (address of an image in the Media Library). Empty: YouTube’s own picture for a YouTube clip, none for an MP4.' ),
				'try_title'    => array( 'Popup headline', 'Your second screen,|*in four steps.*', 'The headline of the Try Audazzio popup, which the notification, "Try it" and every link to /try/ open. A | starts a new line; *asterisks* set words in the lighter tone. Empty: the default.', 'text', true ),
				'try_intro'    => array( 'Popup intro', '', 'Optional, empty for none. A sentence under the popup headline.', 'textarea' ),
				'try_steps'    => array( 'Popup steps', "Open the listener on your phone | Scan the code with your phone’s camera, or tap the button if you are on your phone. It opens in the browser: nothing to download.\nTap the logo, allow the microphone | Tap the Audazzio logo in the middle of the screen and allow microphone access. The peach circle means your phone is listening.\nTurn your speakers on | Volume up on this computer, TV or tablet, loud enough to be heard. Keep your phone close by.\nPress play | Start a demo here and watch your phone. New content arrives in sync with the video, with a ding.", 'One step per line: Title | text. The listener button and its QR code sit under the first step. Empty: the default steps.', 'textarea', true ),
				'try_note'     => array( 'Popup note for phone visitors', 'On your phone right now? Open the listener here, then play the demo on a computer, TV or tablet: audazzio.com/try.', 'Shown under the popup steps. Empty: the default.', 'textarea', true ),
				'notify'       => array( 'Show the "Try Audazzio now" notification', 'yes', '"yes" or "no". The notification slides in on the home page after a few seconds. Empty: yes.', 'text', true ),
				'notify_delay' => array( 'Notification delay (seconds)', '4', 'Empty: the default.', 'text', true ),
				'notify_title' => array( 'Notification title', 'Try Audazzio now', 'Empty: the default.', 'text', true ),
				'notify_text'  => array( 'Notification text', 'Turn your speakers on and watch your phone light up.', 'Empty: the default.', 'text', true ),
			),
		),
		'site'   => array(
			'title'  => 'Company',
			'fields' => array(
				'linkedin'    => array( 'LinkedIn', 'https://www.linkedin.com/company/audazzio', 'Optional. Empty: no LinkedIn link in the footer.' ),
				'privacy_url' => array( 'Privacy Policy link', '/privacy/', 'A page on this site or a PDF. Empty: the default.', 'text', true ),
				'email'       => array( 'Public email', '', 'Optional, empty for none. Shown on the Join the Wave page.' ),
				'phone'       => array( 'Public phone', '', 'Optional, empty for none. Shown on the Join the Wave page.' ),
				'location'    => array( 'Location line', '', 'Optional, empty for none. For example "San Antonio, Texas".' ),
			),
		),
		'chrome' => array(
			'title'  => 'Footer and bar',
			'fields' => array(
				'footer_line'  => array( 'Footer line', 'It comes in waves.', 'Under the logo in the footer. Empty: the default.', 'text', true ),
				'footer_sub'   => array( 'Footer sub-line', 'Live QR™ second-screen technology for broadcasts and live events.', 'Optional, empty for none. Under the footer line.' ),
				'footer_marks' => array( 'Trademark sentence', 'Audazzio® and It Comes in Waves® are registered trademarks of Audazzio, Inc. Live QR™ is a trademark of Audazzio, Inc.', 'Optional, empty for none. After the copyright line at the foot of every page.', 'textarea' ),
				'bar_line'     => array( 'Join the Wave bar line', 'It comes in waves.', 'The line beside the Join the Wave button in the bar at the foot of the screen. Empty: the default.', 'text', true ),
			),
		),
	);
	return apply_filters( 'az_settings_schema', $s );
}

/** Every field as key => its schema row. */
function az_settings_fields() {
	$out = array();
	foreach ( az_settings_schema() as $group ) {
		$out += $group['fields'];
	}
	return $out;
}

function az_settings_defaults() {
	static $d = null;
	if ( null !== $d ) {
		return $d;
	}
	$d = array();
	foreach ( az_settings_fields() as $k => $f ) {
		$d[ $k ] = $f[1];
	}
	// Ships with the site's starter data (seed/site.json): the store links and the demo placeholder found today.
	$company = az_data( 'company' );
	foreach ( array( 'email', 'phone', 'location', 'linkedin' ) as $k ) {
		if ( ! empty( $company[ $k ] ) ) {
			$d[ $k ] = $company[ $k ];
		}
	}
	$apps = az_data( 'apps' );
	foreach ( array( 'listener_url' => 'listener', 'app_store' => 'app_store', 'play_store' => 'play_store', 'demo_video' => 'demo_video', 'demo_label' => 'demo_label', 'demo_more' => 'demo_more', 'demo_poster' => 'demo_poster' ) as $k => $from ) {
		if ( '' === $d[ $k ] && ! empty( $apps[ $from ] ) ) {
			$d[ $k ] = $apps[ $from ];
		}
	}
	return $d;
}

/** Whether an emptied box falls back to the default (true) or stays empty (false). */
function az_setting_keeps_default( $key ) {
	return ! empty( az_settings_fields()[ $key ][4] );
}

/** One setting: what was saved (an empty optional box stays empty), else the default. */
function az_opt( $key ) {
	$saved = get_option( 'az_settings', array() );
	$saved = is_array( $saved ) ? $saved : array();
	$def   = az_settings_defaults()[ $key ] ?? '';
	$v     = array_key_exists( $key, $saved ) ? $saved[ $key ] : $def;
	if ( '' === trim( (string) $v ) && az_setting_keeps_default( $key ) ) {
		$v = $def;
	}
	return in_array( $key, array( 'privacy_url' ), true ) ? az_url( $v ) : $v;
}

/**
 * Save the starter values once (on activation, on the seed, or on the first admin visit after an update),
 * so the site keeps showing them until someone changes them. Settings saved by an earlier version, where an
 * empty box meant "the default", get the default written in.
 */
function az_settings_seed() {
	if ( get_option( 'az_settings_seeded' ) ) {
		return;
	}
	$saved = get_option( 'az_settings', array() );
	$saved = is_array( $saved ) ? $saved : array();
	foreach ( az_settings_defaults() as $k => $v ) {
		if ( ! isset( $saved[ $k ] ) || '' === trim( (string) $saved[ $k ] ) ) {
			$saved[ $k ] = $v;
		}
	}
	update_option( 'az_settings', $saved );
	update_option( 'az_settings_seeded', AZ_VERSION );
}
add_action( 'admin_init', 'az_settings_seed', 5 );

add_action( 'admin_menu', function () {
	$icon = str_replace( 'currentColor', '#a7aaad', (string) @file_get_contents( AZ_DIR . 'assets/brand/audazzio-symbol.svg' ) ); // phpcs:ignore
	add_menu_page( 'Audazzio', 'Audazzio', 'edit_pages', 'audazzio', 'az_leads_page', 'data:image/svg+xml;base64,' . base64_encode( $icon ), 26 );
	add_submenu_page( 'audazzio', 'Join the Wave inbox', 'Inbox', 'edit_pages', 'audazzio', 'az_leads_page' );
	add_submenu_page( 'audazzio', 'Audazzio settings', 'Settings', 'manage_options', 'audazzio-settings', 'az_settings_page' );
	add_submenu_page( 'audazzio', 'Join the Wave form', 'Join the Wave form', 'manage_options', 'audazzio-form', 'az_join_form_page' );
} );

add_action( 'admin_init', function () {
	register_setting( 'az_settings', 'az_settings', array(
		'type'              => 'array',
		'sanitize_callback' => function ( $in ) {
			$in   = is_array( $in ) ? $in : array();
			$old  = get_option( 'az_settings', array() );
			$old  = is_array( $old ) ? $old : array();
			$out  = array();
			foreach ( az_settings_fields() as $k => $f ) {
				if ( ! isset( $in[ $k ] ) || ! is_scalar( $in[ $k ] ) ) {
					$out[ $k ] = $old[ $k ] ?? ( az_settings_defaults()[ $k ] ?? '' );
					continue;
				}
				$out[ $k ] = 'textarea' === ( $f[3] ?? '' ) ? sanitize_textarea_field( str_replace( "\r\n", "\n", (string) $in[ $k ] ) ) : sanitize_text_field( (string) $in[ $k ] );
			}
			return $out;
		},
	) );
} );

/** The two settings screens share a row of tabs. */
function az_settings_tabs( $current ) {
	echo '<nav class="nav-tab-wrapper" aria-label="Audazzio settings">';
	foreach ( array( 'audazzio-settings' => 'Settings', 'audazzio-form' => 'Join the Wave form' ) as $slug => $label ) {
		printf( '<a class="nav-tab%s" href="%s"%s>%s</a>', $slug === $current ? ' nav-tab-active' : '', esc_url( admin_url( 'admin.php?page=' . $slug ) ), $slug === $current ? ' aria-current="page"' : '', esc_html( $label ) );
	}
	echo '</nav>';
}

function az_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$saved = get_option( 'az_settings', array() );
	$saved = is_array( $saved ) ? $saved : array();
	echo '<div class="wrap az-admin"><h1>Audazzio settings</h1>';
	az_settings_tabs( 'audazzio-settings' );
	echo '<p>What is in a box is what the site shows. Boxes marked optional can be left empty to hide that item; the others show their default (in grey) when empty.</p><form method="post" action="options.php">';
	settings_fields( 'az_settings' );
	foreach ( az_settings_schema() as $group ) {
		if ( 'form' === ( $group['screen'] ?? '' ) ) {
			continue;
		}
		echo '<h2>' . esc_html( $group['title'] ) . '</h2><table class="form-table" role="presentation">';
		foreach ( $group['fields'] as $k => $f ) {
			$def = az_settings_defaults()[ $k ] ?? '';
			az_admin_row( 'az-' . $k, 'az_settings[' . $k . ']', $f[0], array_key_exists( $k, $saved ) ? (string) $saved[ $k ] : $def, az_setting_keeps_default( $k ) ? $def : '', $f[2], 'textarea' === ( $f[3] ?? '' ) );
		}
		echo '</table>';
	}
	submit_button();
	echo '</form></div>';
}

/** One labelled box in a settings table. */
function az_admin_row( $id, $name, $label, $value, $placeholder = '', $help = '', $area = false, $extra = '' ) {
	$field = $area
		? sprintf( '<textarea class="large-text" rows="%5$d" id="%1$s" name="%2$s" placeholder="%4$s"%6$s>%3$s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( $value ), esc_attr( $placeholder ), max( 3, min( 10, count( az_lines( $value ) ) + 1 ) ), $extra )
		: sprintf( '<input class="%6$s" type="text" id="%1$s" name="%2$s" value="%3$s" placeholder="%4$s"%5$s>', esc_attr( $id ), esc_attr( $name ), esc_attr( $value ), esc_attr( $placeholder ), $extra, max( mb_strlen( $value ), mb_strlen( $placeholder ) ) > 40 ? 'large-text' : 'regular-text' );
	printf( '<tr><th scope="row"><label for="%1$s">%2$s</label></th><td>%3$s%4$s</td></tr>', esc_attr( $id ), esc_html( $label ), $field, $help ? '<p class="description">' . esc_html( $help ) . '</p>' : '' ); // phpcs:ignore
}
