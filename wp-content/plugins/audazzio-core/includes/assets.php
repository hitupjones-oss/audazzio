<?php
/**
 * Styles, scripts, fonts and the head tags: one stylesheet and one script for the whole site
 * (built from wp-src/ by scripts/wp/build.mjs), the two faces preloaded, the icons and the share card.
 */

defined( 'ABSPATH' ) || exit;

function az_ver( $rel ) {
	$f = AZ_DIR . 'assets/' . $rel;
	return is_readable( $f ) ? AZ_VERSION . '.' . filemtime( $f ) : AZ_VERSION;
}

/** What the front end needs to know: where to send the form, the store links, the demo clip. */
/** A clip setting as what the player needs: YouTube id, or the file address. */
function az_clip( $url, $label = '' ) {
	$yt = az_youtube_id( $url );
	return array(
		'type'  => $yt ? 'youtube' : ( $url ? 'file' : '' ),
		'id'    => $yt,
		'src'   => $yt ? '' : esc_url_raw( az_media( $url ) ),
		'label' => (string) $label,
	);
}

/** The demo clips: the main one, then "Name | link" lines. */
function az_demo_clips() {
	$clips = array();
	$main  = (string) az_opt( 'demo_video' );
	if ( $main ) {
		$clips[] = az_clip( $main, (string) az_opt( 'demo_label' ) );
	}
	foreach ( az_pairs( (string) az_opt( 'demo_more' ) ) as $p ) {
		if ( ! empty( $p[1] ) ) {
			$clips[] = az_clip( $p[1], $p[0] );
		}
	}
	return $clips;
}

/** What the front end needs to know: where to send the form, the listener, the demo clips. */
function az_front_config() {
	$clips = az_demo_clips();
	return array(
		'rest'     => esc_url_raw( rest_url( 'audazzio/v1/join' ) ),
		'assets'   => esc_url_raw( AZ_URL . 'assets/' ),
		'home'     => esc_url_raw( home_url( '/' ) ),
		'listener' => esc_url_raw( (string) az_opt( 'listener_url' ) ),
		'apps'     => array(
			'ios'     => esc_url_raw( (string) az_opt( 'app_store' ) ),
			'android' => esc_url_raw( (string) az_opt( 'play_store' ) ),
		),
		'demos'    => $clips,
		// No poster set: a YouTube clip shows YouTube's own picture of it.
		'demo'     => array_merge( $clips ? $clips[0] : az_clip( '' ), array( 'poster' => esc_url_raw( az_media( az_opt( 'demo_poster' ) ) ?: ( ! empty( $clips[0]['id'] ) ? 'https://i.ytimg.com/vi/' . rawurlencode( $clips[0]['id'] ) . '/hqdefault.jpg' : '' ) ) ) ),
		'notify'   => array(
			'on'    => 'no' !== strtolower( trim( (string) az_opt( 'notify' ) ) ),
			'delay' => max( 0, (float) az_opt( 'notify_delay' ) ),
		),
		'thanks'   => (string) az_opt( 'thanks_line' ),
	);
}

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'az', AZ_URL . 'assets/css/az.css', array(), az_ver( 'css/az.css' ) );
	wp_enqueue_script( 'az', AZ_URL . 'assets/js/az.js', array(), az_ver( 'js/az.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_add_inline_script( 'az', 'window.AZ_CONFIG=' . wp_json_encode( az_front_config() ) . ';', 'before' );
	// The block editor's front-end styles are not used: every page is built from these sections.
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
}, 20 );

/** The site's stylesheet is printed after Elementor's so equal-weight rules land on the site's side. */
add_action( 'elementor/frontend/after_enqueue_styles', function () {
	wp_dequeue_style( 'az' );
	wp_enqueue_style( 'az', AZ_URL . 'assets/css/az.css', array( 'elementor-frontend' ), az_ver( 'css/az.css' ) );
} );

/** Sections that rise into view start hidden only when script is running (set before anything paints). */
add_action( 'wp_head', function () {
	echo "<script>document.documentElement.classList.add('az-js')</script>\n";
}, 1 );

add_action( 'wp_head', function () {
	foreach ( array( 'inter-latin-wght-normal.woff2', 'inter-tight-latin-wght-normal.woff2' ) as $f ) {
		printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( az_asset( 'fonts/' . $f ) ) );
	}
	printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url( az_asset( 'brand/favicon.svg' ) ) );
	printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( az_asset( 'brand/touch.png' ) ) );
	printf( '<meta property="og:image" content="%s"><meta property="og:image:width" content="1200"><meta property="og:image:height" content="630">' . "\n", esc_url( az_asset( 'brand/og.jpg' ) ) );
}, 3 );

// Fewer requests and nothing the site does not use.
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'emoji_svg_url', '__return_false' );
