<?php
/**
 * Plugin Name:       Audazzio Core
 * Description:       The Audazzio design system and every section of the site as an Elementor widget: the wave motion, Live QR explainer, Try Audazzio player, press carousel, leadership, newsroom, and the Join the Wave form with its lead inbox, grading and settings. Works with the Audazzio theme and Elementor (free).
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            RSPKT
 * Author URI:        https://rspkt.co
 * Text Domain:       audazzio
 */

defined( 'ABSPATH' ) || exit;

define( 'AZ_VERSION', '1.0.0' );
define( 'AZ_DIR', plugin_dir_path( __FILE__ ) );
define( 'AZ_URL', plugin_dir_url( __FILE__ ) );

require_once AZ_DIR . 'includes/helpers.php';
require_once AZ_DIR . 'includes/settings.php';
require_once AZ_DIR . 'includes/assets.php';
require_once AZ_DIR . 'includes/components.php';
require_once AZ_DIR . 'includes/lists.php';
require_once AZ_DIR . 'includes/shortcodes.php';
require_once AZ_DIR . 'includes/shell.php';
require_once AZ_DIR . 'includes/leads.php';
require_once AZ_DIR . 'includes/join-settings.php';
require_once AZ_DIR . 'includes/privacy.php';
require_once AZ_DIR . 'includes/meta.php';
require_once AZ_DIR . 'includes/redirects.php';
require_once AZ_DIR . 'includes/seed.php';

/** Elementor is optional at runtime (pages fall back to shortcodes) but it is how layouts are edited. */
add_action( 'plugins_loaded', function () {
	if ( did_action( 'elementor/loaded' ) ) {
		require_once AZ_DIR . 'elementor/loader.php';
	}
} );

register_activation_hook( __FILE__, function () {
	az_register_leads();
	az_settings_seed();
	flush_rewrite_rules();
	if ( ! get_option( 'az_seeded' ) ) {
		update_option( 'az_seed_pending', 1 );
	}
} );

/** First admin visit after activation: import the starter pages (the dev script runs it directly). */
add_action( 'admin_init', function () {
	if ( get_option( 'az_seed_pending' ) && current_user_can( 'activate_plugins' ) && did_action( 'elementor/loaded' ) ) {
		az_run_seed();
	}
} );

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
