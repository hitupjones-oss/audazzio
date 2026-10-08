<?php
/**
 * Small helpers shared by every section: settings, asset URLs, the brand marks, headline formatting,
 * buttons, icons, media fields and the "Designed by RSPKT" credit.
 */

defined( 'ABSPATH' ) || exit;

/** A file shipped in the plugin's assets folder. */
function az_asset( $rel ) {
	return AZ_URL . 'assets/' . ltrim( $rel, '/' );
}

/** Seed data shipped with the plugin (seed/*.json), read once. */
function az_seed_json( $file ) {
	static $cache = array();
	if ( ! isset( $cache[ $file ] ) ) {
		$path           = AZ_DIR . 'seed/' . $file;
		$cache[ $file ] = is_readable( $path ) ? (array) json_decode( (string) file_get_contents( $path ), true ) : array(); // phpcs:ignore
	}
	return $cache[ $file ];
}

/** One list from seed/site.json (videos, cases, press, logos, people, quotes, numbers ...). */
function az_data( $key ) {
	$site = az_seed_json( 'site.json' );
	return $site[ $key ] ?? array();
}

/**
 * A link written in a field: "/join/" becomes this site's address, "#steps" stays on the page,
 * "asset:docs/file.pdf" is a file shipped with the plugin, anything else is used as written.
 * Files that ship with the plugin are stored with the address of the site they were imported on, so they
 * are pointed at this site's plugin folder again here (a move to a new domain cannot break them).
 * Elementor's URL and media controls hand over an array.
 */
function az_url( $url ) {
	if ( is_array( $url ) ) {
		$url = $url['url'] ?? '';
	}
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return '';
	}
	if ( 0 === strpos( $url, 'asset:' ) ) {
		return az_asset( substr( $url, 6 ) );
	}
	$marker = '/wp-content/plugins/audazzio-core/assets/';
	$at     = strpos( $url, $marker );
	if ( false !== $at ) {
		return az_asset( substr( $url, $at + strlen( $marker ) ) );
	}
	if ( '/' === $url[0] && ( ! isset( $url[1] ) || '/' !== $url[1] ) ) {
		return home_url( $url );
	}
	return $url;
}

/** A picture or file from a media field (see az_url). */
function az_media( $v ) {
	return az_url( $v );
}

/** Escape, then: a line break or "|" starts a new line, *asterisks* set the words in the quiet second tone. */
function az_hl( $text ) {
	$text  = str_replace( '|', "\n", (string) $text );
	$lines = preg_split( '/\r\n|\r|\n/', trim( $text ) );
	$out   = '';
	foreach ( $lines as $line ) {
		$line = esc_html( $line );
		$line = preg_replace( '/\*([^*]+)\*/', '<em class="az-hl">$1</em>', $line );
		$out .= '<span class="az-ln">' . $line . '</span> ';
	}
	return trim( $out );
}

/** Paragraphs from plain text: a blank line starts a new paragraph, *asterisks* stay as emphasis. */
function az_paras( $text, $class = '' ) {
	$text = trim( (string) $text );
	if ( '' === $text ) {
		return '';
	}
	$out = '';
	foreach ( preg_split( '/\n\s*\n/', $text ) as $p ) {
		$p    = nl2br( esc_html( trim( $p ) ) );
		$p    = preg_replace( '/\*([^*]+)\*/', '<strong>$1</strong>', $p );
		$out .= '<p' . ( $class ? ' class="' . esc_attr( $class ) . '"' : '' ) . '>' . $p . '</p>';
	}
	return $out;
}

/** Lines of a textarea as a list of strings (empty lines dropped). */
function az_lines( $text ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $text ) ), 'strlen' ) );
}

/** "Label | value" lines as pairs. */
function az_pairs( $text ) {
	$out = array();
	foreach ( az_lines( $text ) as $l ) {
		$p     = array_map( 'trim', explode( '|', $l, 2 ) );
		$out[] = array( $p[0], $p[1] ?? '' );
	}
	return $out;
}

/** A button. Variants: wave (the accent, for Join the Wave), ink, ghost, link. */
function az_btn( $label, $url, $variant = 'ink', $attrs = '' ) {
	if ( '' === trim( (string) $label ) ) {
		return '';
	}
	$href = az_url( $url );
	$data = '';
	if ( preg_match( '#/join/?($|\#|\?)#', (string) $href ) ) {
		$data = ' data-az-join';
	} elseif ( preg_match( '#/try/?($|\#|\?)#', (string) $href ) ) {
		$data = ' data-az-try';
	}
	$arrow = 'link' === $variant ? az_icon( 'arrow' ) : '';
	return sprintf( '<a class="az-btn az-btn--%s" href="%s"%s%s><span>%s</span>%s</a>', esc_attr( $variant ), esc_url( $href ), $data, $attrs ? ' ' . $attrs : '', esc_html( $label ), $arrow );
}

/** The two brand marks, inline SVG in currentColor. */
function az_mark( $which ) {
	static $cache = array();
	if ( ! isset( $cache[ $which ] ) ) {
		$file            = AZ_DIR . 'assets/brand/audazzio-' . sanitize_key( $which ) . '.svg';
		$svg             = is_readable( $file ) ? (string) file_get_contents( $file ) : ''; // phpcs:ignore
		$cache[ $which ] = str_replace( '<svg ', '<svg focusable="false" aria-hidden="true" ', $svg );
	}
	return $cache[ $which ];
}

/** Line icons (1.5px strokes on a 24 grid). */
function az_icon( $name, $class = 'az-ico' ) {
	$p = array(
		'arrow'    => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'arrow-ur' => '<path d="M7 17 17 7M8 7h9v9"/>',
		'play'     => '<path d="M8 5.5v13l11-6.5-11-6.5Z" fill="currentColor" stroke="none"/>',
		'pause'    => '<path d="M8 5h3v14H8zM13 5h3v14h-3z" fill="currentColor" stroke="none"/>',
		'close'    => '<path d="M6 6l12 12M18 6 6 18"/>',
		'check'    => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
		'download' => '<path d="M12 4v11M7 10.5l5 5 5-5M5 20h14"/>',
		'speaker'  => '<path d="M4 9.5h3.5L12 6v12l-4.5-3.5H4z"/><path d="M15.5 9.2a4 4 0 0 1 0 5.6M18.2 6.6a7.8 7.8 0 0 1 0 10.8"/>',
		'mute'     => '<path d="M4 9.5h3.5L12 6v12l-4.5-3.5H4z"/><path d="m16 9.5 5 5M21 9.5l-5 5"/>',
		'phone'    => '<rect x="7" y="2.5" width="10" height="19" rx="2.5"/><path d="M10.5 18.5h3"/>',
		'mic'      => '<rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5.5 11a6.5 6.5 0 0 0 13 0M12 17.5V21"/>',
		'tv'       => '<rect x="2.5" y="4.5" width="19" height="13" rx="2"/><path d="M8 21h8"/>',
		'stadium'  => '<path d="M3 9c0-2.2 4-4 9-4s9 1.8 9 4v6c0 2.2-4 4-9 4s-9-1.8-9-4z"/><path d="M3 9c0 2.2 4 4 9 4s9-1.8 9-4"/>',
		'app'      => '<rect x="4" y="4" width="16" height="16" rx="4.5"/><path d="M9 15.5c1.4-1 2-2.4 1.6-4.2-.4-1.7.3-2.8 2.4-3.3"/><circle cx="8.6" cy="16" r=".9" fill="currentColor"/>',
		'wave'     => '<path d="M2 12c2 0 2-5 4-5s2 10 4 10 2-12 4-12 2 14 4 14 2-7 4-7"/>',
		'cloud'    => '<path d="M7 18.5h10.5a4 4 0 0 0 .5-7.97A6 6 0 0 0 6.3 9.6 4.5 4.5 0 0 0 7 18.5Z"/>',
		'link'     => '<path d="M10 14a4 4 0 0 0 5.66 0l3-3a4 4 0 0 0-5.66-5.66l-1 1"/><path d="M14 10a4 4 0 0 0-5.66 0l-3 3A4 4 0 0 0 11 18.66l1-1"/>',
		'qr'       => '<path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 18h2v2h-2zM14 18h2M18 14h2"/>',
		'spark'    => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M5.6 5.6l2.8 2.8M15.6 15.6l2.8 2.8M5.6 18.4l2.8-2.8M15.6 8.4l2.8-2.8"/>',
		'image'    => '<rect x="3.5" y="4.5" width="17" height="15" rx="2"/><circle cx="9" cy="10" r="1.6"/><path d="m4 17 5-4.5 4 3.5 3-2.5 4 3.5"/>',
		'video'    => '<rect x="3" y="5.5" width="13" height="13" rx="2"/><path d="m16 10.5 5-3v9l-5-3"/>',
		'trophy'   => '<path d="M8 4h8v5a4 4 0 0 1-8 0zM8 6H5a3 3 0 0 0 3 4M16 6h3a3 3 0 0 1-3 4M12 13v4M8.5 20h7"/>',
		'bag'      => '<path d="M5 8h14l-1 12H6z"/><path d="M9 8V6.5a3 3 0 0 1 6 0V8"/>',
		'dice'     => '<rect x="4" y="4" width="16" height="16" rx="3.5"/><circle cx="9" cy="9" r=".9" fill="currentColor"/><circle cx="15" cy="15" r=".9" fill="currentColor"/><circle cx="15" cy="9" r=".9" fill="currentColor"/><circle cx="9" cy="15" r=".9" fill="currentColor"/>',
		'gift'     => '<path d="M4 9.5h16v4H4zM5.5 13.5h13V20h-13zM12 9.5V20"/><path d="M12 9.5S10.5 5 8.3 5.5 8 9.5 12 9.5ZM12 9.5s1.5-4.5 3.7-4M12 9.5c4 0 4.6-3.6 3.7-4"/>',
		'chart'    => '<path d="M4 20V4M4 20h16M8 16v-4M12 16V8M16 16v-6"/>',
		'vote'     => '<path d="M4 13.5h16V20H4zM8 13.5 12 5l4 2-3 6.5"/>',
		'replay'   => '<path d="M4 12a8 8 0 1 0 2.4-5.7M4 4.5v4h4"/><path d="m10.5 9 4 3-4 3z"/>',
		'info'     => '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5M12 8h.01"/>',
		'ticket'   => '<path d="M4 7h16v3a2 2 0 0 0 0 4v3H4v-3a2 2 0 0 0 0-4z"/><path d="M14 7v10" stroke-dasharray="1.5 2"/>',
		'shield'   => '<path d="M12 3 5 6v5c0 4.5 3 8 7 10 4-2 7-5.5 7-10V6z"/>',
		'users'    => '<circle cx="9" cy="8.5" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0M16 5.8a3 3 0 0 1 0 5.4M17.5 14a5.5 5.5 0 0 1 3 5"/>',
		'pin'      => '<path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0c0 5.4-6.5 11-6.5 11Z"/><circle cx="12" cy="10" r="2.3"/>',
		'calendar' => '<rect x="3.5" y="5" width="17" height="15" rx="2"/><path d="M3.5 9.5h17M8 3v4M16 3v4"/>',
		'plus'     => '<path d="M12 5v14M5 12h14"/>',
		'doc'      => '<path d="M6.5 3h7l4 4v14h-11z"/><path d="M13.5 3v4h4M9 12h6M9 15.5h6"/>',
	);
	if ( ! isset( $p[ $name ] ) ) {
		return '';
	}
	return '<svg class="' . esc_attr( $class ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $p[ $name ] . '</svg>';
}

/** A YouTube id from a link (watch, youtu.be, embed, shorts) or a bare id; '' when it is not YouTube. */
function az_youtube_id( $url ) {
	$url = is_array( $url ) ? ( $url['url'] ?? '' ) : (string) $url;
	if ( preg_match( '~^[A-Za-z0-9_-]{11}$~', $url ) ) {
		return $url;
	}
	if ( preg_match( '~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/))([A-Za-z0-9_-]{11})~', $url, $m ) ) {
		return $m[1];
	}
	return '';
}

/** "Designed by RSPKT": the lettering turns half a circle to show the Joule mark on its back, holds, and turns back. */
function az_credit() {
	$img = esc_url( az_asset( 'brand/rspkt-joule.png' ) );
	return '<a class="az-credit" href="https://rspkt.co" target="_blank" rel="noopener" aria-label="Designed by RSPKT (opens rspkt.co)"><span class="az-credit__by">Designed by</span><span class="az-credit__flip" aria-hidden="true"><span class="az-credit__turn"><span class="az-credit__face"><img src="' . $img . '" alt="" width="100" height="15" loading="lazy" decoding="async"></span><span class="az-credit__face az-credit__face--back"><svg viewBox="0 0 468.02 186.4" focusable="false"><polygon fill="currentColor" points="0 186.4 94.21 65.48 271.59 22.62 266.83 50 468.02 0 206.11 125 209.69 101.19 0 186.4"/></svg></span></span></span></a>';
}
