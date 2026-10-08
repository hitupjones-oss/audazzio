<?php
/**
 * Join the Wave inquiries in WordPress's privacy tools: Tools > Export Personal Data and Tools > Erase
 * Personal Data find a person's inquiries by the email address they gave. uninstall.php removes them all.
 */

defined( 'ABSPATH' ) || exit;

/** The inquiries sent with this email address (any letter case), 50 at a time. */
function az_leads_by_email( $email, $page = 1 ) {
	$email = trim( (string) $email );
	if ( '' === $email ) {
		return array();
	}
	$ids = get_posts( array(
		'post_type'   => 'az_lead',
		'post_status' => 'any',
		'numberposts' => 50,
		'paged'       => max( 1, (int) $page ),
		'orderby'     => 'ID',
		'order'       => 'ASC',
		'fields'      => 'ids',
		'meta_query'  => array( array( 'key' => '_az_email', 'value' => $email, 'compare' => 'LIKE' ) ), // phpcs:ignore
	) );
	return array_values( array_filter( $ids, function ( $id ) use ( $email ) {
		return 0 === strcasecmp( (string) get_post_meta( $id, '_az_email', true ), $email );
	} ) );
}

add_filter( 'wp_privacy_personal_data_exporters', function ( $exporters ) {
	$exporters['audazzio-join'] = array(
		'exporter_friendly_name' => 'Join the Wave inquiries',
		'callback'               => 'az_privacy_export',
	);
	return $exporters;
} );

add_filter( 'wp_privacy_personal_data_erasers', function ( $erasers ) {
	$erasers['audazzio-join'] = array(
		'eraser_friendly_name' => 'Join the Wave inquiries',
		'callback'             => 'az_privacy_erase',
	);
	return $erasers;
} );

function az_privacy_export( $email, $page = 1 ) {
	$items = array();
	$ids   = az_leads_by_email( $email, $page );
	foreach ( $ids as $id ) {
		$data = array( array( 'name' => 'Received', 'value' => get_the_date( 'Y-m-d H:i', $id ) ) );
		foreach ( array( 'name' => 'Name', 'position' => 'Position', 'company' => 'Company', 'email' => 'Email', 'phone' => 'Phone' ) as $k => $label ) {
			$data[] = array( 'name' => $label, 'value' => (string) az_lead_meta( $id, $k ) );
		}
		foreach ( az_join_questions() as $k => $q ) {
			$data[] = array( 'name' => $q['label'], 'value' => az_answer_label( $k, az_lead_meta( $id, $k ) ) );
		}
		$data[]  = array( 'name' => 'In their words', 'value' => (string) az_lead_meta( $id, 'details' ) );
		$data[]  = array( 'name' => 'Sent from', 'value' => (string) az_lead_meta( $id, 'source' ) );
		$items[] = array(
			'group_id'    => 'audazzio-join',
			'group_label' => 'Join the Wave inquiries',
			'item_id'     => 'az-lead-' . $id,
			'data'        => $data,
		);
	}
	return array( 'data' => $items, 'done' => count( $ids ) < 50 );
}

function az_privacy_erase( $email, $page = 1 ) {
	// Each pass deletes what it finds, so the next pass starts again from the first page.
	$ids = az_leads_by_email( $email, 1 );
	foreach ( $ids as $id ) {
		wp_delete_post( $id, true );
	}
	return array(
		'items_removed'  => (bool) $ids,
		'items_retained' => false,
		'messages'       => array(),
		'done'           => count( $ids ) < 50,
	);
}
