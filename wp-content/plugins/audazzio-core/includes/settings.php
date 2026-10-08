<?php
/**
 * Audazzio > Settings: where Join the Wave inquiries go, the app store links, the Try Audazzio demo,
 * and the company links. Every box has a working default, so an empty box never breaks the site.
 */

defined( 'ABSPATH' ) || exit;

/** Settings, their labels, help text and defaults (the defaults are what the site shows until changed). */
function az_settings_schema() {
	$s = array(
		'leads' => array(
			'title'  => 'Join the Wave',
			'fields' => array(
				'leads_email'     => array( 'Send new inquiries to', '', 'One or more email addresses, separated by commas. Empty: the site administrator\'s address.' ),
				'leads_a_email'   => array( 'Also send grade A inquiries to', '', 'Optional. For example the CEO, so the best-fit inquiries are seen first.' ),
				'hubspot_portal'  => array( 'HubSpot portal ID', '', 'Optional. With a portal ID and a form ID, every inquiry is also sent to that HubSpot form (name, title, company, email, phone, and the answers and grade in the message).' ),
				'hubspot_form'    => array( 'HubSpot form ID', '', '' ),
				'thanks_line'     => array( 'Thank-you line', 'A member of our team will be in touch shortly.', 'Shown after an inquiry is sent.' ),
			),
		),
		'try'   => array(
			'title'  => 'Try Audazzio',
			'fields' => array(
				'listener_url'    => array( 'Phone listener link', '', 'The page a phone opens to listen (Audazzio\'s web listener). The Try Audazzio steps link to it and its QR code points to it.' ),
				'app_store'       => array( 'App Store link (optional)', '', 'When there is an Audazzio app on the App Store, paste its link and an App Store button appears beside the listener.' ),
				'play_store'      => array( 'Google Play link (optional)', '', 'The same for Google Play.' ),
				'demo_video'      => array( 'Demo clip', '', 'The clip the Try Audazzio player plays first: a YouTube link, or the address of an MP4 uploaded to the Media Library. Its soundtrack must carry the signal. An MP4 is safest: video sites re-encode sound and can strip the high frequencies the signal rides on.' ),
				'demo_label'      => array( 'Demo clip name', '', 'A short name for the first clip, shown on its button (for example "Formula 1").' ),
				'demo_more'       => array( 'More demo clips', '', 'Optional. One per line: Name | link. Each becomes a button above the player.', 'textarea' ),
				'demo_poster'     => array( 'Demo poster', '', 'Optional. The picture shown before the demo plays (address of an image in the Media Library).' ),
				'notify'          => array( 'Show the "Try Audazzio now" notification', 'yes', '"yes" or "no". The notification slides in on the home page after a few seconds.' ),
				'notify_delay'    => array( 'Notification delay (seconds)', '4', '' ),
			),
		),
		'site'  => array(
			'title'  => 'Company',
			'fields' => array(
				'linkedin'        => array( 'LinkedIn', 'https://www.linkedin.com/company/audazzio', '' ),
				'privacy_url'     => array( 'Privacy Policy link', '/privacy/', 'A page on this site or a PDF.' ),
				'email'           => array( 'Public email', '', 'Optional. Shown on the Join the Wave page.' ),
				'phone'           => array( 'Public phone', '', 'Optional. Shown on the Join the Wave page.' ),
				'location'        => array( 'Location line', '', 'Optional, for example "San Antonio, Texas".' ),
			),
		),
	);
	return apply_filters( 'az_settings_schema', $s );
}

function az_settings_defaults() {
	$d = array();
	foreach ( az_settings_schema() as $group ) {
		foreach ( $group['fields'] as $k => $f ) {
			$d[ $k ] = $f[1];
		}
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
			continue;
		}
	}
	return $d;
}

/** One setting: what was saved, else the default. */
function az_opt( $key ) {
	$saved = (array) get_option( 'az_settings', array() );
	if ( isset( $saved[ $key ] ) && '' !== trim( (string) $saved[ $key ] ) ) {
		$v = $saved[ $key ];
	} else {
		$v = az_settings_defaults()[ $key ] ?? '';
	}
	return in_array( $key, array( 'privacy_url' ), true ) ? az_url( $v ) : $v;
}

add_action( 'admin_menu', function () {
	$icon = str_replace( 'currentColor', '#a7aaad', (string) @file_get_contents( AZ_DIR . 'assets/brand/audazzio-symbol.svg' ) ); // phpcs:ignore
	add_menu_page( 'Audazzio', 'Audazzio', 'edit_pages', 'audazzio', 'az_leads_page', 'data:image/svg+xml;base64,' . base64_encode( $icon ), 26 );
	add_submenu_page( 'audazzio', 'Join the Wave inbox', 'Inbox', 'edit_pages', 'audazzio', 'az_leads_page' );
	add_submenu_page( 'audazzio', 'Audazzio settings', 'Settings', 'manage_options', 'audazzio-settings', 'az_settings_page' );
} );

add_action( 'admin_init', function () {
	register_setting( 'az_settings', 'az_settings', array(
		'type'              => 'array',
		'sanitize_callback' => function ( $in ) {
			$out = array();
			foreach ( az_settings_schema() as $gk => $group ) {
				foreach ( array_keys( $group['fields'] ) as $k ) {
					$f         = az_settings_schema()[ $gk ]['fields'][ $k ];
					$out[ $k ] = isset( $in[ $k ] ) ? ( 'textarea' === ( $f[3] ?? '' ) ? sanitize_textarea_field( wp_unslash( $in[ $k ] ) ) : sanitize_text_field( wp_unslash( $in[ $k ] ) ) ) : '';
				}
			}
			return $out;
		},
	) );
} );

function az_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$saved = (array) get_option( 'az_settings', array() );
	echo '<div class="wrap az-admin"><h1>Audazzio settings</h1><p>Every box has a default (shown in grey). Leave a box empty to keep it.</p><form method="post" action="options.php">';
	settings_fields( 'az_settings' );
	foreach ( az_settings_schema() as $group ) {
		echo '<h2>' . esc_html( $group['title'] ) . '</h2><table class="form-table" role="presentation">';
		foreach ( $group['fields'] as $k => $f ) {
			$def = az_settings_defaults()[ $k ] ?? '';
			$area = 'textarea' === ( $f[3] ?? '' );
			printf(
				$area ? '<tr><th scope="row"><label for="az-%1$s">%2$s</label></th><td><textarea class="large-text" rows="4" id="az-%1$s" name="az_settings[%1$s]" placeholder="%4$s">%3$s</textarea>%5$s</td></tr>' : '<tr><th scope="row"><label for="az-%1$s">%2$s</label></th><td><input class="regular-text" type="text" id="az-%1$s" name="az_settings[%1$s]" value="%3$s" placeholder="%4$s">%5$s</td></tr>',
				esc_attr( $k ),
				esc_html( $f[0] ),
				esc_attr( $saved[ $k ] ?? '' ),
				esc_attr( $def ),
				$f[2] ? '<p class="description">' . esc_html( $f[2] ) . '</p>' : ''
			);
		}
		echo '</table>';
	}
	submit_button();
	echo '</form></div>';
}
