<?php
/**
 * [az name="hero" title="..."]: any section, outside Elementor. If Elementor is ever switched off, the theme
 * draws each page from its saved Elementor layout instead (az_render_elementor_data below), with every setting.
 */

defined( 'ABSPATH' ) || exit;

add_shortcode( 'az', function ( $atts ) {
	$atts = (array) $atts;
	$name = sanitize_key( $atts['name'] ?? '' );
	unset( $atts['name'] );
	return $name ? az_component( $name, $atts ) : '';
} );

/**
 * A page built in Elementor, drawn without Elementor: every Audazzio widget of its saved layout, in order and
 * with its saved settings (plus the text of Elementor's own heading and text widgets). The theme's page.php
 * uses it when Elementor is not active, so pages keep their real content.
 */
function az_render_elementor_data( $post_id ) {
	if ( 'builder' !== get_post_meta( $post_id, '_elementor_edit_mode', true ) ) {
		return '';
	}
	$data = get_post_meta( $post_id, '_elementor_data', true );
	$data = is_array( $data ) ? $data : json_decode( (string) $data, true );
	if ( ! is_array( $data ) ) {
		return '';
	}
	$walk = function ( $elements ) use ( &$walk ) {
		$out = '';
		foreach ( $elements as $el ) {
			if ( ! is_array( $el ) ) {
				continue;
			}
			$type = (string) ( $el['widgetType'] ?? '' );
			$set  = is_array( $el['settings'] ?? null ) ? $el['settings'] : array();
			if ( 0 === strpos( $type, 'az-' ) ) {
				$out .= az_component( substr( $type, 3 ), $set );
			} elseif ( 'heading' === $type && ! empty( $set['title'] ) ) {
				$out .= '<div class="az-wrap"><h2>' . wp_kses_post( $set['title'] ) . '</h2></div>';
			} elseif ( 'text-editor' === $type && ! empty( $set['editor'] ) ) {
				$out .= '<div class="az-wrap az-prose">' . wp_kses_post( wpautop( $set['editor'] ) ) . '</div>';
			}
			if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
				$out .= $walk( $el['elements'] );
			}
		}
		return $out;
	};
	return $walk( $data );
}
