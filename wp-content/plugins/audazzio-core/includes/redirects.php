<?php
/**
 * Today's addresses (the HubSpot site) sent to their new homes, so links in press releases, emails,
 * QR codes and search results keep working after the move. Permanent (301) redirects.
 */

defined( 'ABSPATH' ) || exit;

function az_redirect_map() {
	return apply_filters( 'az_redirects', array(
		'liveqr'             => '/live-qr/',
		'applications'       => '/solutions/#beyond',
		'broadcasters'       => '/solutions/#broadcasters',
		'teams-leagues'      => '/solutions/#teams',
		'sponsors-brands'    => '/solutions/#sponsors',
		'resources-audazzio' => '/newsroom/',
		'resources'          => '/newsroom/',
		'about-0-0'          => '/about/',
		'contact'            => '/join/',
		'contact-typ'        => '/join/',
		'demo'               => '/try/',
		'explainer'          => '/live-qr/#explainer',
	) );
}

add_action( 'template_redirect', function () {
	if ( ! is_404() ) {
		return;
	}
	$path = trim( (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ), '/' ); // phpcs:ignore
	$map  = az_redirect_map();
	if ( isset( $map[ $path ] ) ) {
		wp_safe_redirect( home_url( $map[ $path ] ), 301 );
		exit;
	}
} );
