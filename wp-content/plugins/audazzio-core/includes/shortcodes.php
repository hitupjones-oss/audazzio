<?php
/**
 * [az name="hero" title="..."]: any section, outside Elementor. Pages are stored with these as their plain
 * content, so a page still renders if Elementor is ever switched off.
 */

defined( 'ABSPATH' ) || exit;

add_shortcode( 'az', function ( $atts ) {
	$atts = (array) $atts;
	$name = sanitize_key( $atts['name'] ?? '' );
	unset( $atts['name'] );
	return $name ? az_component( $name, $atts ) : '';
} );
