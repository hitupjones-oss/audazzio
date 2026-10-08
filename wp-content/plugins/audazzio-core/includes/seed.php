<?php
/**
 * Starter content: the pages (as Elementor layouts made of Audazzio widgets), the menus and Elementor's
 * global colours and fonts. Built from seed/pages.json (scripts/wp/pages.mjs). Safe to run twice: pages
 * that already exist are left alone.
 */

defined( 'ABSPATH' ) || exit;

/** get_page_by_path() also matches attachments, so look pages up by type and slug. */
function az_seed_find( $slug, $type = 'page' ) {
	$q = get_posts( array( 'post_type' => $type, 'name' => $slug, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) );
	return $q ? (int) $q[0] : 0;
}

function az_run_seed() {
	$report = array();
	az_seed_clear_samples();
	$pages           = az_seed_pages();
	$report['pages'] = count( $pages );
	az_seed_menus( $pages );
	az_seed_elementor_kit();
	update_option( 'blogname', 'Audazzio' );
	update_option( 'blogdescription', 'It comes in waves. Audazzio’s Live QR™ technology hides inaudible signals in the sound of a broadcast or live event, so phones show the right content at the exact moment. No scanning.' );
	update_option( 'az_seeded', time() );
	delete_option( 'az_seed_pending' );
	flush_rewrite_rules();
	return $report;
}

/** WordPress ships a sample page, post and comment. */
function az_seed_clear_samples() {
	foreach ( array( array( 'sample-page', 'page' ), array( 'hello-world', 'post' ), array( 'privacy-policy', 'page' ) ) as $s ) {
		$id = az_seed_find( $s[0], $s[1] );
		if ( $id && ! get_post_meta( $id, '_elementor_data', true ) ) {
			wp_delete_post( $id, true );
		}
	}
}

function az_el_id() {
	return substr( md5( uniqid( '', true ) ), 0, 7 );
}

/** One full-width, zero-padding container holding one Audazzio widget. */
function az_el_section( $widget, $settings = array() ) {
	return array(
		'id'       => az_el_id(),
		'elType'   => 'container',
		'isInner'  => false,
		'settings' => array(
			'content_width' => 'full',
			'flex_gap'      => array( 'unit' => 'px', 'size' => 0, 'column' => '0', 'row' => '0', 'isLinked' => true ),
			'padding'       => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true ),
		),
		'elements' => array(
			array( 'id' => az_el_id(), 'elType' => 'widget', 'widgetType' => 'az-' . $widget, 'settings' => $settings, 'elements' => array() ),
		),
	);
}

/** Widget settings ready for Elementor: URLs wrapped, shipped files given this site's address, rows given ids. */
function az_seed_settings( $widget, $settings ) {
	$schema = az_component_schemas()[ $widget ]['fields'] ?? array();
	// The board is the same widget as leadership, with the board's people.
	if ( 'people' === $widget && 'board' === ( $settings['group'] ?? '' ) && ! isset( $settings['items'] ) ) {
		$settings['items'] = az_people_default( 'board' );
	}
	$fix    = function ( $f, $v ) {
		if ( 'url' === $f['type'] && is_string( $v ) ) {
			return array( 'url' => $v );
		}
		if ( 'media' === $f['type'] && is_string( $v ) ) {
			return 0 === strpos( $v, 'asset:' ) ? az_ship( substr( $v, 6 ) ) : array( 'url' => $v, 'id' => '' );
		}
		return $v;
	};
	foreach ( $schema as $k => $f ) {
		if ( ! isset( $settings[ $k ] ) && 'repeater' === $f['type'] && ! empty( $f['default'] ) ) {
			$settings[ $k ] = $f['default'];
		}
		if ( ! isset( $settings[ $k ] ) ) {
			continue;
		}
		$settings[ $k ] = $fix( $f, $settings[ $k ] );
		if ( 'repeater' === $f['type'] && is_array( $settings[ $k ] ) ) {
			foreach ( $settings[ $k ] as $ri => $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				foreach ( $f['fields'] as $sk => $sf ) {
					if ( isset( $row[ $sk ] ) ) {
						$settings[ $k ][ $ri ][ $sk ] = $fix( $sf, $row[ $sk ] );
					}
				}
				if ( ! isset( $row['_id'] ) ) {
					$settings[ $k ][ $ri ]['_id'] = az_el_id();
				}
			}
		}
	}
	return $settings;
}

function az_seed_pages() {
	$ids = array();
	foreach ( az_seed_json( 'pages.json' ) as $def ) {
		$slug     = $def['slug'];
		$existing = az_seed_find( $slug, 'page' );
		if ( $existing ) {
			$ids[ $slug ] = $existing;
			continue;
		}
		$data     = array();
		$fallback = '';
		foreach ( $def['widgets'] as $w ) {
			$data[]    = az_el_section( $w[0], az_seed_settings( $w[0], $w[1] ?? array() ) );
			$fallback .= '[az name="' . $w[0] . '"]' . "\n";
		}
		$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $def['title'], 'post_name' => $slug, 'post_content' => $fallback, 'menu_order' => (int) ( $def['order'] ?? 0 ) ) );
		if ( ! $id || is_wp_error( $id ) ) {
			continue;
		}
		update_post_meta( $id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $id, '_elementor_template_type', 'wp-page' );
		update_post_meta( $id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.30.0' );
		update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
		update_post_meta( $id, '_elementor_page_settings', array( 'hide_title' => 'yes' ) );
		update_post_meta( $id, '_wp_page_template', 'elementor_header_footer' === ( $def['template'] ?? '' ) ? 'elementor_header_footer' : 'default' );
		if ( ! empty( $def['description'] ) ) {
			update_post_meta( $id, '_az_description', $def['description'] );
		}
		if ( ! empty( $def['doc_title'] ) ) {
			update_post_meta( $id, '_az_title', $def['doc_title'] );
		}
		$ids[ $slug ] = $id;
	}
	if ( ! empty( $ids['home'] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $ids['home'] );
	}
	if ( ! empty( $ids['privacy'] ) ) {
		update_option( 'wp_page_for_privacy_policy', $ids['privacy'] );
	}
	return $ids;
}

function az_seed_menus( $pages ) {
	$locations = (array) get_theme_mod( 'nav_menu_locations', array() );
	$menus     = array(
		'primary' => array( 'Audazzio: header', array( 'Live QR' => 'live-qr', 'Solutions' => 'solutions', 'Newsroom' => 'newsroom', 'About' => 'about' ) ),
		'footer'  => array( 'Audazzio: footer', array( 'Live QR' => 'live-qr', 'Solutions' => 'solutions', 'Newsroom' => 'newsroom', 'About' => 'about', 'Try Audazzio' => 'try', 'Join the Wave' => 'join' ) ),
	);
	foreach ( $menus as $location => $def ) {
		if ( ! empty( $locations[ $location ] ) || wp_get_nav_menu_object( $def[0] ) ) {
			continue;
		}
		$menu_id = wp_create_nav_menu( $def[0] );
		if ( is_wp_error( $menu_id ) ) {
			continue;
		}
		$i = 0;
		foreach ( $def[1] as $label => $slug ) {
			if ( empty( $pages[ $slug ] ) ) {
				continue;
			}
			wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => $label, 'menu-item-object' => 'page', 'menu-item-object-id' => $pages[ $slug ], 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish', 'menu-item-position' => ++$i ) );
		}
		$locations[ $location ] = $menu_id;
	}
	set_theme_mod( 'nav_menu_locations', $locations );
}

/** Elementor's Site Settings: the brand colours and faces, so anything built by hand matches. */
function az_seed_elementor_kit() {
	$kit = (int) get_option( 'elementor_active_kit' );
	if ( ! $kit ) {
		return;
	}
	$settings = (array) get_post_meta( $kit, '_elementor_page_settings', true );
	$settings['system_colors'] = array(
		array( '_id' => 'primary', 'title' => 'Ink', 'color' => '#0C1222' ),
		array( '_id' => 'secondary', 'title' => 'Slate', 'color' => '#4A5061' ),
		array( '_id' => 'text', 'title' => 'Text', 'color' => '#0C1222' ),
		array( '_id' => 'accent', 'title' => 'Wave (Join the Wave only)', 'color' => '#F26A2E' ),
	);
	$settings['custom_colors'] = array(
		array( '_id' => 'az_white', 'title' => 'White', 'color' => '#FFFFFF' ),
		array( '_id' => 'az_mist', 'title' => 'Mist', 'color' => '#F5F5F7' ),
		array( '_id' => 'az_line', 'title' => 'Hairline', 'color' => '#E3E4E8' ),
		array( '_id' => 'az_grey', 'title' => 'Grey', 'color' => '#6E7280' ),
		array( '_id' => 'az_peach', 'title' => 'Audazzio peach (logo)', 'color' => '#F89C68' ),
	);
	$settings['system_typography'] = array(
		array( '_id' => 'primary', 'title' => 'Headline', 'typography_typography' => 'custom', 'typography_font_family' => 'Inter Tight', 'typography_font_weight' => '600' ),
		array( '_id' => 'secondary', 'title' => 'Subhead', 'typography_typography' => 'custom', 'typography_font_family' => 'Inter Tight', 'typography_font_weight' => '500' ),
		array( '_id' => 'text', 'title' => 'Text', 'typography_typography' => 'custom', 'typography_font_family' => 'Inter', 'typography_font_weight' => '400' ),
		array( '_id' => 'accent', 'title' => 'Label', 'typography_typography' => 'custom', 'typography_font_family' => 'Geist Mono', 'typography_font_weight' => '500' ),
	);
	$settings['container_width'] = array( 'unit' => 'px', 'size' => 1200, 'sizes' => array() );
	update_post_meta( $kit, '_elementor_page_settings', $settings );
}
