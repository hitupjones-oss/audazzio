<?php
/**
 * Deleting Audazzio Core from Plugins removes what it stored: every Join the Wave inquiry (with its answers,
 * contact details and notes), the settings and the form's questions, and the rate-limit counters. The pages,
 * menus and media it imported stay: they belong to the site.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

$az_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s", 'az_lead' ) ); // phpcs:ignore
foreach ( $az_ids as $az_id ) {
	wp_delete_post( (int) $az_id, true );
}

// Every az_* option (az_settings, az_join_form, az_seeded ...) and transient (the az_join_* rate-limit counters).
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( 'az_' ) . '%', $wpdb->esc_like( '_transient_az_' ) . '%', $wpdb->esc_like( '_transient_timeout_az_' ) . '%' ) ); // phpcs:ignore
wp_cache_flush();
