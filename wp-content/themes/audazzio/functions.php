<?php
/**
 * The Audazzio theme: the shell only (header, menu, footer). Sections, styles, motion, the Try Audazzio
 * player and the Join the Wave form come from the Audazzio Core plugin.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	register_nav_menus( array(
		'primary' => 'Header (main links)',
		'footer'  => 'Footer (company links)',
	) );
} );

/** The links each menu falls back to until one is assigned under Appearance > Menus. */
function az_theme_default_menu( $location ) {
	$menus = array(
		'primary' => array( 'Live QR' => '/live-qr/', 'Solutions' => '/solutions/', 'Newsroom' => '/newsroom/', 'About' => '/about/' ),
		'footer'  => array( 'Live QR' => '/live-qr/', 'Solutions' => '/solutions/', 'Newsroom' => '/newsroom/', 'About' => '/about/', 'Try Audazzio' => '/try/', 'Join the Wave' => '/join/' ),
	);
	return $menus[ $location ] ?? array();
}

/** A menu as label => url pairs: the assigned menu, else the defaults. */
function az_theme_menu( $location ) {
	$locations = get_nav_menu_locations();
	$out       = array();
	if ( ! empty( $locations[ $location ] ) ) {
		foreach ( (array) wp_get_nav_menu_items( $locations[ $location ] ) as $item ) {
			$out[ $item->title ] = $item->url;
		}
	}
	if ( ! $out ) {
		foreach ( az_theme_default_menu( $location ) as $label => $url ) {
			$out[ $label ] = home_url( $url );
		}
	}
	return $out;
}

function az_theme_is_current( $url ) {
	$here = trailingslashit( strtok( (string) ( $_SERVER['REQUEST_URI'] ?? '/' ), '?' ) ); // phpcs:ignore
	$path = trailingslashit( (string) wp_parse_url( $url, PHP_URL_PATH ) );
	return '/' !== $path && 0 === strpos( $here, $path );
}

/** Print a menu's links. */
function az_theme_links( $location, $class = '' ) {
	foreach ( az_theme_menu( $location ) as $label => $url ) {
		printf(
			'<a class="%s" href="%s"%s>%s</a>',
			esc_attr( $class ),
			esc_url( $url ),
			az_theme_is_current( $url ) ? ' aria-current="page"' : '',
			esc_html( $label )
		);
	}
}

/** The page's own sentence for search engines and link previews. */
add_action( 'wp_head', function () {
	$d = is_singular() ? get_post_meta( get_the_ID(), '_az_description', true ) : '';
	if ( ! $d && is_front_page() ) {
		$d = get_bloginfo( 'description' );
	}
	if ( $d ) {
		printf( '<meta name="description" content="%1$s"><meta property="og:description" content="%1$s">' . "\n", esc_attr( $d ) );
	}
	printf( '<meta property="og:site_name" content="Audazzio"><meta property="og:type" content="website"><meta name="twitter:card" content="summary_large_image">' . "\n" );
}, 2 );

/** Titles: what the page is, then the company. */
add_filter( 'pre_get_document_title', function ( $title ) {
	if ( is_singular() ) {
		$own = get_post_meta( get_the_ID(), '_az_title', true );
		if ( $own ) {
			return $own;
		}
		return is_front_page() ? 'Audazzio | It comes in waves.' : single_post_title( '', false ) . ' | Audazzio';
	}
	return is_404() ? 'Not found | Audazzio' : $title;
} );

/** Without the core plugin there is nothing to style the site: say so in the dashboard. */
add_action( 'admin_notices', function () {
	if ( ! defined( 'AZ_VERSION' ) && current_user_can( 'activate_plugins' ) ) {
		echo '<div class="notice notice-error"><p>The Audazzio theme needs the <strong>Audazzio Core</strong> plugin to be active.</p></div>';
	}
} );

/** The brand marks, inline (they take the colour of the text around them). */
function az_theme_mark( $which ) {
	if ( function_exists( 'az_mark' ) ) {
		return az_mark( $which );
	}
	return '<span>Audazzio</span>';
}
