<?php
/**
 * Not found: a calm page with the wave and the way back.
 */

defined( 'ABSPATH' ) || exit;
status_header( 404 );
get_header();
if ( function_exists( 'az_component' ) ) {
	echo az_component( 'page-hero', array( 'eyebrow' => 'Error 404', 'title' => "This page\n*missed the wave.*", 'intro' => 'The address may have changed when the site was rebuilt. Everything Audazzio does is one click away.', 'cta' => 'Back home', 'cta_url' => '/', 'alt' => 'How Live QR works', 'alt_url' => '/live-qr/' ) ); // phpcs:ignore
}
get_footer();
