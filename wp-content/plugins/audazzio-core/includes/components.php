<?php
/**
 * Component registry.
 *
 * Every section of the site is a component: one schema (fields + defaults) and one PHP renderer in
 * includes/components/. The same schema builds the Elementor widget controls (elementor/widgets.php)
 * and the shortcode fallback, so editors always start from the real copy.
 *
 * Field types: text, textarea, url, switch, select, number, media, repeater, and note (a panel note, its HTML
 * as the default). 'condition' => array( 'field' => 'value' ) shows a field, or a repeater's section, only then.
 * News, case studies and logos come from the shared lists by default (includes/lists.php, az_list_for()).
 * In headlines a line break (or |) starts a new line and *asterisks* set words in the quiet second tone.
 */

defined( 'ABSPATH' ) || exit;

/** Field shorthand. */
function az_f( $type, $label, $default = '', $extra = array() ) {
	return array_merge( array( 'type' => $type, 'label' => $label, 'default' => $default ), $extra );
}

/** The fields nearly every section starts with. */
function az_head_fields( $eyebrow = '', $title = '', $intro = '', $tone = 'white' ) {
	return array(
		'tone'    => az_f( 'select', 'Background', $tone, array( 'options' => az_tones() ) ),
		'anchor'  => az_f( 'text', 'Anchor (for links like #how)', '' ),
		'eyebrow' => az_f( 'text', 'Small line above the headline', $eyebrow ),
		'title'   => az_f( 'textarea', 'Headline', $title ),
		'intro'   => az_f( 'textarea', 'Intro', $intro ),
	);
}

function az_tones() {
	return array( 'white' => 'White', 'mist' => 'Light grey', 'ink' => 'Night (dark)' );
}

/** A media default: a file that ships with the plugin. */
function az_ship( $rel ) {
	return array( 'url' => az_asset( $rel ), 'id' => '' );
}

foreach ( array( 'heroes', 'explain', 'try', 'proof', 'media', 'solutions', 'press', 'company', 'join' ) as $az_file ) {
	require_once AZ_DIR . 'includes/components/' . $az_file . '.php';
}

function az_component_names() {
	return array( 'hero', 'page-hero', 'steps', 'flow', 'canvas', 'try', 'numbers', 'videos', 'solutions', 'solution', 'cases', 'logos', 'press', 'quotes', 'values', 'story', 'people', 'cta', 'join', 'faq', 'prose' );
}

function az_component_schemas() {
	static $schemas = null;
	if ( null !== $schemas ) {
		return $schemas;
	}
	$schemas = array();
	foreach ( az_component_names() as $n ) {
		$fn = 'az_schema_' . str_replace( '-', '_', $n );
		if ( function_exists( $fn ) ) {
			$schemas[ $n ] = $fn();
		}
	}
	return apply_filters( 'az_component_schemas', $schemas );
}

function az_component_defaults( $name ) {
	$schema = az_component_schemas()[ $name ] ?? array( 'fields' => array() );
	$out    = array();
	foreach ( $schema['fields'] as $key => $f ) {
		$out[ $key ] = $f['default'] ?? ( 'repeater' === $f['type'] ? array() : '' );
	}
	return $out;
}

/** Render a component to a string. Missing args fall back to the schema defaults. */
function az_component( $name, $args = array() ) {
	$fn = 'az_render_' . str_replace( '-', '_', $name );
	if ( ! function_exists( $fn ) ) {
		return '';
	}
	$args = wp_parse_args( array_filter( (array) $args, function ( $v ) {
		return null !== $v;
	} ), az_component_defaults( $name ) );
	ob_start();
	$fn( $args );
	return (string) ob_get_clean();
}

/** The opening tag every section shares: tone, anchor, extra classes. */
function az_open( $args, $class, $extra = '' ) {
	$tone   = sanitize_key( $args['tone'] ?? 'white' );
	$anchor = sanitize_title( $args['anchor'] ?? '' );
	printf( '<section class="az-sec az-sec--%s %s"%s%s>', esc_attr( $tone ?: 'white' ), esc_attr( $class ), $anchor ? ' id="' . esc_attr( $anchor ) . '"' : '', $extra ? ' ' . $extra : '' ); // phpcs:ignore
}

/** Eyebrow, headline and intro, in the order every section uses them. */
function az_head( $args, $class = '', $h = 'h2' ) {
	$out = '';
	if ( ! empty( $args['eyebrow'] ) ) {
		$out .= '<p class="az-eyebrow" data-az-rise>' . esc_html( $args['eyebrow'] ) . '</p>';
	}
	if ( ! empty( $args['title'] ) ) {
		$out .= sprintf( '<%1$s class="az-title" data-az-rise>%2$s</%1$s>', tag_escape( $h ), az_hl( $args['title'] ) );
	}
	if ( ! empty( $args['intro'] ) ) {
		$out .= '<div class="az-intro" data-az-rise>' . az_paras( $args['intro'] ) . '</div>';
	}
	return $out ? '<header class="az-head ' . esc_attr( $class ) . '">' . $out . '</header>' : '';
}
