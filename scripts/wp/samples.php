<?php
/**
 * Sample Join the Wave inquiries for the local dev site (screenshots for the guide and the deck).
 *   php .wp/wp-cli.phar --allow-root --path=.wp/wordpress eval-file scripts/wp/samples.php
 * Every sample is marked "(sample)" in its company name; run it again to replace them.
 */
defined( 'ABSPATH' ) || exit;
foreach ( get_posts( array( 'post_type' => 'az_lead', 'post_status' => 'private', 'numberposts' => -1, 'fields' => 'ids' ) ) as $id ) {
	wp_delete_post( $id, true );
}
$samples = array(
	array( 'Dana Whitfield', 'VP, Digital Products', 'Regional Sports Network (sample)', 'dana@rsn-example.com', '210 555 0142', 'broadcaster', array( 'broadcast', 'sponsor' ), 'xl', 'many', 'xl', 'soon', 'decide', 'Every home game next season: player profiles when a substitute comes on, and a presenting sponsor’s competition at half time.' ),
	array( 'Marcus Lee', 'Director of Fan Experience', 'Pro Soccer Club (sample)', 'mlee@club-example.com', '512 555 0199', 'team', array( 'venue', 'sponsor' ), 'm', 'season', 'l', 'later', 'recommend', 'In-stadium replays and food and drink offers during matches.' ),
	array( 'Priya Shah', 'Brand Partnerships Lead', 'Beverage Brand (sample)', 'priya@brand-example.com', '646 555 0123', 'brand', array( 'sponsor' ), 'm', 'few', 's', 'later', 'research', 'A sponsor activation during two televised tournaments.' ),
	array( 'Sam Ortiz', 'Event Producer', 'Ortiz Live Events (sample)', 'sam.ortiz@gmail.com', '305 555 0177', 'venue', array( 'concert', 'polling' ), 's', 'one', 's', 'explore', 'research', 'Encore voting at a festival.' ),
	array( 'Taylor Brooks', 'Head of Partnerships', 'Streaming Platform (sample)', 'taylor@stream-example.com', '415 555 0110', 'tech', array( 'broadcast', 'polling' ), 'l', 'many', 'tbd', 'later', 'decide', 'Interested in an SDK integration for live shows on our platform.' ),
);
$statuses = array( 'meeting', 'contacted', 'new', 'new', 'new' );
foreach ( $samples as $i => $s ) {
	$d = array_combine( array( 'name', 'position', 'company', 'email', 'phone', 'org', 'uses', 'audience', 'frequency', 'budget', 'timeline', 'role', 'details' ), $s );
	$d['source'] = home_url( '/' );
	$g  = az_grade_lead( $d );
	$id = wp_insert_post( array( 'post_type' => 'az_lead', 'post_status' => 'private', 'post_title' => $d['name'] . ', ' . $d['company'], 'post_date' => gmdate( 'Y-m-d H:i:s', time() - ( 5 - $i ) * 7200 ) ) );
	foreach ( $d as $k => $v ) {
		update_post_meta( $id, '_az_' . $k, $v );
	}
	foreach ( array( 'score', 'grade', 'nature' ) as $k ) {
		update_post_meta( $id, '_az_' . $k, $g[ $k ] );
	}
	update_post_meta( $id, '_az_parts', $g['parts'] );
	update_post_meta( $id, '_az_status', $statuses[ $i ] );
	update_post_meta( $id, '_az_emailed', 'yes' );
	echo $d['name'], ': ', $g['grade'], ' ', $g['score'], ' ', $g['nature'], "\n";
}
