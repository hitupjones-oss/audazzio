<?php
/**
 * Join the Wave inquiries: received through the REST API (POST /wp-json/audazzio/v1/join), checked,
 * graded A to D from the answers (az_join_questions()), stored as private "az_lead" posts, emailed to
 * the team, optionally sent on to a HubSpot form, and listed under Audazzio > Inbox with a CSV export.
 */

defined( 'ABSPATH' ) || exit;

const AZ_LEAD_STATUSES = array( 'new' => 'New', 'contacted' => 'Contacted', 'meeting' => 'Meeting booked', 'proposal' => 'Proposal sent', 'won' => 'Won', 'closed' => 'Closed' );

function az_register_leads() {
	register_post_type( 'az_lead', array(
		'label'           => 'Join the Wave',
		'public'          => false,
		'show_ui'         => false,
		'show_in_rest'    => false,
		'supports'        => array( 'title' ),
		'capability_type' => 'post',
		'rewrite'         => false,
	) );
}
add_action( 'init', 'az_register_leads' );

/**
 * Grade an inquiry. Points come from the answer table; the sum is scaled to 100. A work email adds a little,
 * a detailed description adds a little. Returns score, grade, label, nature and the breakdown.
 */
function az_grade_lead( $d ) {
	$qs    = az_join_questions();
	$sum   = 0;
	$max   = 0;
	$parts = array();
	foreach ( $qs as $key => $q ) {
		$best = max( array_map( function ( $o ) {
			return (int) $o[1];
		}, $q['options'] ) );
		if ( 'checkbox' === $q['type'] ) {
			$got = 0;
			foreach ( (array) ( $d[ $key ] ?? array() ) as $v ) {
				$got = max( $got, (int) ( $q['options'][ $v ][1] ?? 0 ) );
			}
		} else {
			$got = (int) ( $q['options'][ $d[ $key ] ?? '' ][1] ?? 0 );
		}
		$sum            += $got;
		$max            += $best;
		$parts[ $key ]   = $got;
	}
	$len               = mb_strlen( trim( (string) ( $d['details'] ?? '' ) ) );
	$parts['details']  = $len >= 160 ? 5 : ( $len >= 60 ? 3 : 1 );
	$domain            = strtolower( (string) substr( strrchr( (string) ( $d['email'] ?? '' ), '@' ), 1 ) );
	$parts['email']    = ( $domain && ! in_array( $domain, az_join_free_domains(), true ) ) ? 5 : 0;
	$sum              += $parts['details'] + $parts['email'];
	$max              += 10;
	$score             = $max ? (int) round( $sum / $max * 100 ) : 0;
	$grade             = 'D';
	foreach ( az_join_grades() as $g => $def ) {
		if ( $score >= $def[0] ) {
			$grade = $g;
			break;
		}
	}
	$natures = az_join_natures();
	$nature  = $natures[ $d['org'] ?? 'other' ] ?? $natures['other'];
	$scale   = '';
	if ( in_array( $d['audience'] ?? '', array( 'xl' ), true ) || in_array( $d['budget'] ?? '', array( 'xl' ), true ) ) {
		$scale = 'Enterprise scale';
	} elseif ( 'one' === ( $d['frequency'] ?? '' ) ) {
		$scale = 'Single event';
	} elseif ( in_array( $d['frequency'] ?? '', array( 'season', 'many' ), true ) ) {
		$scale = 'Season-long';
	}
	return array(
		'score'  => $score,
		'grade'  => $grade,
		'label'  => az_join_grades()[ $grade ][1],
		'advice' => az_join_grades()[ $grade ][2],
		'nature' => trim( $nature . ( $scale ? ' · ' . $scale : '' ) ),
		'parts'  => $parts,
	);
}

/** An answer's label, for the email, the inbox and HubSpot. */
function az_answer_label( $key, $value ) {
	$q = az_join_questions()[ $key ] ?? null;
	if ( ! $q ) {
		return is_array( $value ) ? implode( ', ', $value ) : (string) $value;
	}
	$labels = array();
	foreach ( (array) $value as $v ) {
		$labels[] = $q['options'][ $v ][0] ?? $v;
	}
	return implode( ', ', $labels );
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'audazzio/v1', '/join', array(
		'methods'             => 'POST',
		'permission_callback' => '__return_true',
		'callback'            => 'az_join_receive',
	) );
} );

function az_join_receive( WP_REST_Request $req ) {
	$p    = $req->get_json_params() ? $req->get_json_params() : $req->get_body_params();
	$fail = function ( $msg, $code = 400, $field = '' ) {
		return new WP_REST_Response( array( 'ok' => false, 'message' => $msg, 'field' => $field ), $code );
	};
	// Bots fill the hidden box and send faster than a person can read the first question.
	if ( ! empty( $p['website'] ) || ( isset( $p['t'] ) && (int) $p['t'] < 4000 ) ) {
		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}
	$ip   = hash( 'sha256', wp_salt( 'nonce' ) . ( $_SERVER['REMOTE_ADDR'] ?? '' ) ); // phpcs:ignore
	$key  = 'az_join_' . substr( $ip, 0, 20 );
	$seen = (int) get_transient( $key );
	if ( $seen >= 5 ) {
		return $fail( 'Too many inquiries from this connection. Please try again in an hour, or email us.', 429 );
	}

	$d = array();
	foreach ( array( 'name' => 120, 'position' => 120, 'company' => 160, 'phone' => 40 ) as $f => $max ) {
		$d[ $f ] = mb_substr( sanitize_text_field( (string) ( $p[ $f ] ?? '' ) ), 0, $max );
		if ( '' === $d[ $f ] ) {
			return $fail( 'Please fill in every field about you.', 400, $f );
		}
	}
	$d['email'] = sanitize_email( (string) ( $p['email'] ?? '' ) );
	if ( ! is_email( $d['email'] ) ) {
		return $fail( 'Please check the email address.', 400, 'email' );
	}
	if ( strlen( preg_replace( '/\D/', '', $d['phone'] ) ) < 7 ) {
		return $fail( 'Please check the phone number.', 400, 'phone' );
	}
	$d['details'] = mb_substr( sanitize_textarea_field( (string) ( $p['details'] ?? '' ) ), 0, 4000 );
	if ( mb_strlen( trim( $d['details'] ) ) < 20 ) {
		return $fail( 'Please tell us a little more about what you have in mind.', 400, 'details' );
	}
	foreach ( az_join_questions() as $k => $q ) {
		$v = $p[ $k ] ?? ( 'checkbox' === $q['type'] ? array() : '' );
		if ( 'checkbox' === $q['type'] ) {
			$v = array_values( array_intersect( array_map( 'sanitize_key', (array) $v ), array_keys( $q['options'] ) ) );
			if ( ! $v ) {
				return $fail( 'Please pick at least one way you would use Audazzio.', 400, $k );
			}
		} else {
			$v = sanitize_key( (string) $v );
			if ( ! isset( $q['options'][ $v ] ) ) {
				return $fail( 'Please answer every question.', 400, $k );
			}
		}
		$d[ $k ] = $v;
	}
	if ( empty( $p['consent'] ) ) {
		return $fail( 'Please tick the box so we can reply.', 400, 'consent' );
	}
	$d['source'] = esc_url_raw( (string) ( $p['source'] ?? '' ) );

	$g  = az_grade_lead( $d );
	$id = wp_insert_post( array(
		'post_type'   => 'az_lead',
		'post_status' => 'private',
		'post_title'  => $d['name'] . ', ' . $d['company'],
	), true );
	if ( is_wp_error( $id ) ) {
		return $fail( 'Something went wrong on our side. Please email us instead.', 500 );
	}
	foreach ( $d as $k => $v ) {
		update_post_meta( $id, '_az_' . $k, $v );
	}
	foreach ( array( 'score', 'grade', 'nature' ) as $k ) {
		update_post_meta( $id, '_az_' . $k, $g[ $k ] );
	}
	update_post_meta( $id, '_az_parts', $g['parts'] );
	update_post_meta( $id, '_az_status', 'new' );
	set_transient( $key, $seen + 1, HOUR_IN_SECONDS );

	az_join_email( $id, $d, $g );
	az_join_hubspot( $id, $d, $g );

	return new WP_REST_Response( array( 'ok' => true, 'message' => (string) az_opt( 'thanks_line' ) ), 200 );
}

/** The inquiry as plain text lines (email, HubSpot message). */
function az_join_summary( $d, $g ) {
	$rows = array(
		'Grade'     => $g['grade'] . ' (' . $g['label'] . ', ' . $g['score'] . '/100)',
		'Nature'    => $g['nature'],
		'Next step' => $g['advice'],
		''          => '',
		'Name'      => $d['name'],
		'Position'  => $d['position'],
		'Company'   => $d['company'],
		'Email'     => $d['email'],
		'Phone'     => $d['phone'],
	);
	$out = array();
	foreach ( $rows as $k => $v ) {
		$out[] = '' === $k ? '' : $k . ': ' . $v;
	}
	foreach ( az_join_questions() as $k => $q ) {
		$out[] = $q['label'] . ' ' . az_answer_label( $k, $d[ $k ] ?? '' );
	}
	$out[] = '';
	$out[] = 'In their words:';
	$out[] = $d['details'];
	if ( ! empty( $d['source'] ) ) {
		$out[] = '';
		$out[] = 'Sent from: ' . $d['source'];
	}
	return implode( "\n", $out );
}

function az_join_email( $id, $d, $g ) {
	$to = array_filter( array_map( 'trim', explode( ',', (string) az_opt( 'leads_email' ) ) ) );
	if ( ! $to ) {
		$to = array( get_option( 'admin_email' ) );
	}
	if ( 'A' === $g['grade'] ) {
		$to = array_merge( $to, array_filter( array_map( 'trim', explode( ',', (string) az_opt( 'leads_a_email' ) ) ) ) );
	}
	$subject = sprintf( '[Join the Wave] %s · %s · %s', $g['grade'], $g['nature'], $d['company'] );
	$body    = az_join_summary( $d, $g ) . "\n\nOpen in the inbox: " . admin_url( 'admin.php?page=audazzio&lead=' . (int) $id );
	$headers = array( 'Reply-To: ' . $d['name'] . ' <' . $d['email'] . '>' );
	$sent    = wp_mail( array_unique( $to ), $subject, $body, $headers );
	update_post_meta( $id, '_az_emailed', $sent ? 'yes' : 'no' );
}

function az_join_hubspot( $id, $d, $g ) {
	$portal = preg_replace( '/[^0-9]/', '', (string) az_opt( 'hubspot_portal' ) );
	$form   = preg_replace( '/[^a-zA-Z0-9-]/', '', (string) az_opt( 'hubspot_form' ) );
	if ( ! $portal || ! $form ) {
		return;
	}
	$names = preg_split( '/\s+/', trim( $d['name'] ), 2 );
	$body  = array(
		'fields'  => array(
			array( 'name' => 'firstname', 'value' => $names[0] ?? '' ),
			array( 'name' => 'lastname', 'value' => $names[1] ?? '' ),
			array( 'name' => 'email', 'value' => $d['email'] ),
			array( 'name' => 'phone', 'value' => $d['phone'] ),
			array( 'name' => 'company', 'value' => $d['company'] ),
			array( 'name' => 'jobtitle', 'value' => $d['position'] ),
			array( 'name' => 'message', 'value' => az_join_summary( $d, $g ) ),
		),
		'context' => array( 'pageUri' => $d['source'] ?: home_url( '/join/' ), 'pageName' => 'Join the Wave' ),
	);
	$r = wp_remote_post( "https://api.hsforms.com/submissions/v3/integration/submit/{$portal}/{$form}", array(
		'timeout' => 8,
		'headers' => array( 'Content-Type' => 'application/json' ),
		'body'    => wp_json_encode( $body ),
	) );
	update_post_meta( $id, '_az_hubspot', is_wp_error( $r ) ? 'error: ' . $r->get_error_message() : (string) wp_remote_retrieve_response_code( $r ) );
}

/* ------------------------------------------------------------- the inbox */

function az_lead_meta( $id, $k ) {
	return get_post_meta( $id, '_az_' . $k, true );
}

function az_grade_badge( $g ) {
	$c = array( 'A' => '#0C1222', 'B' => '#3C4456', 'C' => '#8A8F9C', 'D' => '#C5C8D0' );
	return sprintf( '<span style="display:inline-block;min-width:26px;padding:3px 8px;border-radius:999px;background:%s;color:%s;font-weight:700;text-align:center">%s</span>', esc_attr( $c[ $g ] ?? '#C5C8D0' ), in_array( $g, array( 'C', 'D' ), true ) ? '#0C1222' : '#fff', esc_html( $g ?: '?' ) );
}

add_action( 'admin_post_az_lead_save', function () {
	$id = (int) ( $_POST['lead'] ?? 0 ); // phpcs:ignore
	check_admin_referer( 'az_lead_' . $id );
	if ( ! current_user_can( 'edit_pages' ) || 'az_lead' !== get_post_type( $id ) ) {
		wp_die( 'Not allowed.' );
	}
	$status = sanitize_key( wp_unslash( $_POST['status'] ?? 'new' ) ); // phpcs:ignore
	update_post_meta( $id, '_az_status', isset( AZ_LEAD_STATUSES[ $status ] ) ? $status : 'new' );
	update_post_meta( $id, '_az_notes', sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ) ); // phpcs:ignore
	wp_safe_redirect( admin_url( 'admin.php?page=audazzio&lead=' . $id . '&saved=1' ) );
	exit;
} );

add_action( 'admin_post_az_leads_csv', function () {
	check_admin_referer( 'az_leads_csv' );
	if ( ! current_user_can( 'edit_pages' ) ) {
		wp_die( 'Not allowed.' );
	}
	$ids = get_posts( array( 'post_type' => 'az_lead', 'post_status' => 'private', 'numberposts' => -1, 'fields' => 'ids' ) );
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=join-the-wave-' . gmdate( 'Y-m-d' ) . '.csv' );
	$out  = fopen( 'php://output', 'w' );
	$cols = array_merge( array( 'date', 'grade', 'score', 'nature', 'status', 'name', 'position', 'company', 'email', 'phone' ), array_keys( az_join_questions() ), array( 'details', 'notes', 'source' ) );
	fputcsv( $out, $cols );
	foreach ( $ids as $id ) {
		$row = array();
		foreach ( $cols as $c ) {
			if ( 'date' === $c ) {
				$row[] = get_the_date( 'Y-m-d H:i', $id );
			} elseif ( isset( az_join_questions()[ $c ] ) ) {
				$row[] = az_answer_label( $c, az_lead_meta( $id, $c ) );
			} else {
				$row[] = (string) az_lead_meta( $id, $c );
			}
		}
		fputcsv( $out, $row );
	}
	fclose( $out ); // phpcs:ignore
	exit;
} );

function az_leads_page() {
	if ( ! current_user_can( 'edit_pages' ) ) {
		return;
	}
	$lead = (int) ( $_GET['lead'] ?? 0 ); // phpcs:ignore
	echo '<div class="wrap az-admin">';
	if ( $lead && 'az_lead' === get_post_type( $lead ) ) {
		az_lead_view( $lead );
	} else {
		az_leads_list();
	}
	echo '</div>';
}

function az_leads_list() {
	$grade = sanitize_key( wp_unslash( $_GET['grade'] ?? '' ) ); // phpcs:ignore
	$args  = array( 'post_type' => 'az_lead', 'post_status' => 'private', 'numberposts' => 200 );
	if ( $grade ) {
		$args['meta_query'] = array( array( 'key' => '_az_grade', 'value' => strtoupper( $grade ) ) ); // phpcs:ignore
	}
	$leads  = get_posts( $args );
	$counts = array();
	foreach ( array( 'A', 'B', 'C', 'D' ) as $g ) {
		$counts[ $g ] = count( get_posts( array( 'post_type' => 'az_lead', 'post_status' => 'private', 'numberposts' => -1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => '_az_grade', 'value' => $g ) ) ) ) ); // phpcs:ignore
	}
	$csv = wp_nonce_url( admin_url( 'admin-post.php?action=az_leads_csv' ), 'az_leads_csv' );
	echo '<h1 class="wp-heading-inline">Join the Wave inbox</h1> <a class="page-title-action" href="' . esc_url( $csv ) . '">Export CSV</a>';
	echo '<p>Every inquiry is graded from its answers: <b>A</b> priority, <b>B</b> qualified, <b>C</b> nurture, <b>D</b> early. The grade is for your team only; the person who wrote never sees it.</p>';
	echo '<ul class="subsubsub"><li><a href="' . esc_url( admin_url( 'admin.php?page=audazzio' ) ) . '"' . ( $grade ? '' : ' class="current"' ) . '>All</a> | </li>';
	foreach ( $counts as $g => $n ) {
		echo '<li><a href="' . esc_url( admin_url( 'admin.php?page=audazzio&grade=' . strtolower( $g ) ) ) . '"' . ( strtoupper( $grade ) === $g ? ' class="current"' : '' ) . '>' . esc_html( $g ) . ' <span class="count">(' . (int) $n . ')</span></a>' . ( 'D' !== $g ? ' | ' : '' ) . '</li>';
	}
	echo '</ul><table class="widefat striped" style="margin-top:12px"><thead><tr><th>Grade</th><th>Received</th><th>Who</th><th>Company</th><th>Nature</th><th>Audience</th><th>Budget</th><th>Timeline</th><th>Status</th></tr></thead><tbody>';
	if ( ! $leads ) {
		echo '<tr><td colspan="9">No inquiries yet. They arrive here from every Join the Wave form on the site.</td></tr>';
	}
	foreach ( $leads as $l ) {
		$id = $l->ID;
		printf(
			'<tr><td>%s <small>%d</small></td><td>%s</td><td><a href="%s"><b>%s</b></a><br><small>%s</small></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
			az_grade_badge( (string) az_lead_meta( $id, 'grade' ) ), // phpcs:ignore
			(int) az_lead_meta( $id, 'score' ),
			esc_html( get_the_date( 'M j, Y g:i a', $id ) ),
			esc_url( admin_url( 'admin.php?page=audazzio&lead=' . $id ) ),
			esc_html( (string) az_lead_meta( $id, 'name' ) ),
			esc_html( (string) az_lead_meta( $id, 'position' ) ),
			esc_html( (string) az_lead_meta( $id, 'company' ) ),
			esc_html( (string) az_lead_meta( $id, 'nature' ) ),
			esc_html( az_answer_label( 'audience', az_lead_meta( $id, 'audience' ) ) ),
			esc_html( az_answer_label( 'budget', az_lead_meta( $id, 'budget' ) ) ),
			esc_html( az_answer_label( 'timeline', az_lead_meta( $id, 'timeline' ) ) ),
			esc_html( AZ_LEAD_STATUSES[ az_lead_meta( $id, 'status' ) ] ?? 'New' )
		);
	}
	echo '</tbody></table>';
}

function az_lead_view( $id ) {
	$g     = (string) az_lead_meta( $id, 'grade' );
	$grade = az_join_grades()[ $g ] ?? null;
	echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=audazzio' ) ) . '">&larr; All inquiries</a></p>';
	if ( isset( $_GET['saved'] ) ) { // phpcs:ignore
		echo '<div class="notice notice-success is-dismissible"><p>Saved.</p></div>';
	}
	printf( '<h1>%s %s</h1>', az_grade_badge( $g ), esc_html( get_post_field( 'post_title', $id ) ) ); // phpcs:ignore
	printf( '<p><b>%s</b> · %d/100 · %s<br>%s</p>', esc_html( $grade[1] ?? '' ), (int) az_lead_meta( $id, 'score' ), esc_html( (string) az_lead_meta( $id, 'nature' ) ), esc_html( $grade[2] ?? '' ) );
	$email = (string) az_lead_meta( $id, 'email' );
	echo '<table class="form-table" role="presentation">';
	foreach ( array( 'name' => 'Name', 'position' => 'Position', 'company' => 'Company', 'email' => 'Email', 'phone' => 'Phone' ) as $k => $label ) {
		$v = (string) az_lead_meta( $id, $k );
		if ( 'email' === $k ) {
			$v = '<a href="mailto:' . esc_attr( $v ) . '">' . esc_html( $v ) . '</a>';
		} elseif ( 'phone' === $k ) {
			$v = '<a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $v ) ) . '">' . esc_html( $v ) . '</a>';
		} else {
			$v = esc_html( $v );
		}
		echo '<tr><th>' . esc_html( $label ) . '</th><td>' . $v . '</td></tr>'; // phpcs:ignore
	}
	$parts = (array) az_lead_meta( $id, 'parts' );
	foreach ( az_join_questions() as $k => $q ) {
		echo '<tr><th>' . esc_html( $q['label'] ) . '</th><td>' . esc_html( az_answer_label( $k, az_lead_meta( $id, $k ) ) ) . ' <small style="color:#787c82">(' . (int) ( $parts[ $k ] ?? 0 ) . ' pts)</small></td></tr>';
	}
	echo '<tr><th>In their words</th><td>' . nl2br( esc_html( (string) az_lead_meta( $id, 'details' ) ) ) . '</td></tr>';
	echo '<tr><th>Sent from</th><td>' . esc_html( (string) az_lead_meta( $id, 'source' ) ) . '</td></tr>';
	echo '<tr><th>Emailed / HubSpot</th><td>' . esc_html( (string) az_lead_meta( $id, 'emailed' ) ) . ' / ' . esc_html( (string) ( az_lead_meta( $id, 'hubspot' ) ?: 'not connected' ) ) . '</td></tr>';
	echo '</table>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="az_lead_save"><input type="hidden" name="lead" value="' . (int) $id . '">';
	wp_nonce_field( 'az_lead_' . $id );
	echo '<h2>Follow-up</h2><p><label>Status <select name="status">';
	foreach ( AZ_LEAD_STATUSES as $k => $label ) {
		printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), selected( az_lead_meta( $id, 'status' ), $k, false ), esc_html( $label ) );
	}
	echo '</select></label></p><p><label>Notes (only your team sees these)<br><textarea name="notes" rows="5" class="large-text">' . esc_textarea( (string) az_lead_meta( $id, 'notes' ) ) . '</textarea></label></p>';
	submit_button( 'Save', 'primary', 'submit', false );
	echo ' <a class="button" href="mailto:' . esc_attr( $email ) . '?subject=' . rawurlencode( 'Audazzio: your Join the Wave inquiry' ) . '">Reply by email</a></form>';
}
