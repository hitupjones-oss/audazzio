<?php
/**
 * Elementor integration: an "Audazzio" widget group with one widget per section, the brand faces in the
 * font picker, the brand colours as Elementor's global colours, and no Google Fonts requests.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'elementor/elements/categories_registered', function ( $elements_manager ) {
	$elements_manager->add_category( 'audazzio', array( 'title' => 'Audazzio', 'icon' => 'eicon-apps' ) );
} );

add_action( 'elementor/widgets/register', function ( $widgets_manager ) {
	require_once AZ_DIR . 'elementor/widgets.php';
	foreach ( array_keys( az_component_schemas() ) as $name ) {
		$class = 'AZ_Widget_' . str_replace( ' ', '_', ucwords( str_replace( '-', ' ', $name ) ) );
		if ( class_exists( $class ) ) {
			$widgets_manager->register( new $class() );
		}
	}
} );

add_filter( 'elementor/fonts/additional_fonts', function ( $fonts ) {
	$fonts['Inter']       = 'system';
	$fonts['Inter Tight'] = 'system';
	$fonts['Geist Mono']  = 'system';
	return $fonts;
} );
add_filter( 'elementor/frontend/print_google_fonts', '__return_false' );

/**
 * Elementor's "lazy load background images" blanks backgrounds in lower sections until its own script has
 * seen them. These sections draw their own backgrounds, so the switch is turned off.
 */
add_filter( 'pre_option_elementor_lazy_load_background_images', function () {
	return '0';
} );
add_filter( 'pre_option_elementor_experiment-e_lazyload', function () {
	return 'inactive';
} );

/** Editor: the Audazzio widgets sit in their own group, marked in the accent. */
add_action( 'elementor/editor/after_enqueue_styles', function () {
	wp_add_inline_style( 'elementor-editor', '#elementor-panel-category-audazzio .elementor-element .icon{color:#0C1222;background:#FFE7D8;border-radius:10px;padding:8px}#elementor-panel-category-audazzio .elementor-panel-category-title{font-weight:600}' );
} );

add_action( 'admin_notices', function () {
	if ( did_action( 'elementor/loaded' ) || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( $screen && in_array( $screen->id, array( 'plugins', 'dashboard', 'toplevel_page_audazzio' ), true ) ) {
		echo '<div class="notice notice-warning"><p>Audazzio Core needs Elementor (free) so page layouts can be edited visually. Pages still render without it.</p></div>';
	}
} );
