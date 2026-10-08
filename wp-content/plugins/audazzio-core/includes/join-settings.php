<?php
/**
 * Audazzio > Join the Wave form: the qualifying questions, their answers and the points each is worth, the
 * grade minimums, the step titles and the success text. Saved in the option az_join_form, which
 * az_join_questions(), az_join_grades(), az_join_natures() and az_join_text() read (includes/components/join.php).
 *
 * An answer keeps its key while its label stays the same, so inquiries already received still show their
 * answers. A new label gets a new key; an inquiry that has an answer no longer offered shows its key.
 */

defined( 'ABSPATH' ) || exit;

/** The answers of a question as "Label | points" lines (the organization adds "| what the team calls it"). */
function az_join_options_text( $key, $q ) {
	$natures = az_join_natures();
	$lines   = array();
	foreach ( $q['options'] as $ok => $o ) {
		$lines[] = $o[0] . ' | ' . (int) $o[1] . ( 'org' === $key ? ' | ' . ( $natures[ $ok ] ?? $natures['other'] ) : '' );
	}
	return implode( "\n", $lines );
}

/** "Label | points" lines back into key => [label, points(, nature)], keeping known keys for known labels. */
function az_join_parse_options( $key, $text ) {
	$known = array();
	foreach ( array( az_join_questions()[ $key ]['options'] ?? array(), az_join_default_questions()[ $key ]['options'] ?? array() ) as $set ) {
		foreach ( $set as $ok => $o ) {
			$norm = strtolower( trim( (string) $o[0] ) );
			if ( ! isset( $known[ $norm ] ) ) {
				$known[ $norm ] = (string) $ok;
			}
		}
	}
	$out = array();
	foreach ( az_lines( $text ) as $line ) {
		$p     = array_map( 'trim', explode( '|', $line ) );
		$label = sanitize_text_field( $p[0] );
		if ( '' === $label || count( $out ) >= 20 ) {
			continue;
		}
		$ok = $known[ strtolower( $label ) ] ?? '';
		if ( '' === $ok || isset( $out[ $ok ] ) ) {
			$base = substr( sanitize_key( sanitize_title( $label ) ), 0, 40 );
			$base = '' === $base ? 'answer' : $base;
			$ok   = $base;
			for ( $i = 2; isset( $out[ $ok ] ) || ( in_array( $ok, $known, true ) && strtolower( $label ) !== array_search( $ok, $known, true ) ); $i++ ) {
				$ok = $base . '-' . $i;
			}
		}
		$row = array( $label, max( 0, min( 100, (int) ( $p[1] ?? 0 ) ) ) );
		if ( 'org' === $key && ! empty( $p[2] ) ) {
			$row[] = sanitize_text_field( $p[2] );
		}
		$out[ $ok ] = $row;
	}
	return $out;
}

add_action( 'admin_post_az_join_form_save', function () {
	check_admin_referer( 'az_join_form' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.' );
	}
	$in   = wp_unslash( $_POST ); // phpcs:ignore
	$str  = function ( $v ) {
		return is_scalar( $v ) ? sanitize_text_field( (string) $v ) : '';
	};
	$save = array( 'questions' => array(), 'grades' => array(), 'texts' => array() );
	foreach ( az_join_default_questions() as $k => $q ) {
		$save['questions'][ $k ] = array(
			'label'   => $str( $in['q'][ $k ]['label'] ?? '' ),
			'options' => az_join_parse_options( $k, is_scalar( $in['q'][ $k ]['options'] ?? '' ) ? (string) ( $in['q'][ $k ]['options'] ?? '' ) : '' ),
		);
	}
	$prev    = 100;
	$changed = false;
	foreach ( array( 'A', 'B', 'C' ) as $g ) {
		$v = $in['grades'][ $g ] ?? '';
		$v = is_numeric( $v ) ? max( 0, min( 100, (int) $v ) ) : az_join_grades()[ $g ][0];
		if ( $v > $prev ) {
			$v       = $prev;
			$changed = true;
		}
		$save['grades'][ $g ] = $prev = $v;
	}
	foreach ( array_keys( az_join_default_texts() ) as $k ) {
		$save['texts'][ $k ] = $str( $in['texts'][ $k ] ?? '' );
	}
	update_option( 'az_join_form', $save, false );

	$settings = get_option( 'az_settings', array() );
	$settings = is_array( $settings ) ? $settings : array();
	$settings['thanks_line'] = $str( $in['thanks_line'] ?? '' );
	update_option( 'az_settings', $settings );

	wp_safe_redirect( admin_url( 'admin.php?page=audazzio-form&saved=' . ( $changed ? '2' : '1' ) ) );
	exit;
} );

function az_join_form_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$saved  = az_join_saved();
	$grades = az_join_grades();
	$texts  = az_join_default_texts();
	echo '<div class="wrap az-admin"><h1>Join the Wave form</h1>';
	az_settings_tabs( 'audazzio-form' );
	$done = sanitize_key( wp_unslash( $_GET['saved'] ?? '' ) ); // phpcs:ignore
	if ( $done ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . ( '2' === $done ? 'Saved. The grade minimums must go down from A to C, so the lower ones were set to match.' : 'Saved. Both Join the Wave forms on the site use these now.' ) . '</p></div>';
	}
	echo '<p>The questions on steps 2 and 3, the answers people pick from, and the points each answer is worth. Each inquiry gets a score out of 100 from its answers and a grade from the minimums below. Inquiries already received keep the grade they were given.</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="az_join_form_save">';
	wp_nonce_field( 'az_join_form' );

	echo '<h2>Steps</h2><table class="form-table" role="presentation">';
	for ( $n = 1; $n <= 4; $n++ ) {
		az_admin_row( 'az-jf-name' . $n, 'texts[name' . $n . ']', 'Step ' . $n . ': name in the progress bar', (string) ( $saved['texts'][ 'name' . $n ] ?? '' ) ?: $texts[ 'name' . $n ], $texts[ 'name' . $n ], 'Empty: the default.' );
		az_admin_row( 'az-jf-step' . $n, 'texts[step' . $n . ']', 'Step ' . $n . ': heading', (string) ( $saved['texts'][ 'step' . $n ] ?? '' ) ?: $texts[ 'step' . $n ], $texts[ 'step' . $n ], 'Empty: the default.' );
	}
	echo '</table>';

	echo '<h2>Questions</h2><p>One answer per line: <code>Answer | points</code>. For the organization, add what your team calls that kind of inquiry: <code>Answer | points | name</code>. An empty box goes back to the starter answers. Only the best answer of a question counts toward the score; "Pick any" questions count their best pick.</p><table class="form-table" role="presentation">';
	$defaults = az_join_default_questions();
	foreach ( az_join_questions() as $k => $q ) {
		$best = $q['options'] ? max( array_map( function ( $o ) {
			return (int) $o[1];
		}, $q['options'] ) ) : 0;
		$kind = 'checkbox' === $q['type'] ? 'Pick any' : 'Pick one';
		az_admin_row( 'az-jf-' . $k . '-label', 'q[' . $k . '][label]', 'Step ' . $q['step'] . ' question', $q['label'], $defaults[ $k ]['label'], $kind . '. Empty: the default.' );
		az_admin_row( 'az-jf-' . $k . '-options', 'q[' . $k . '][options]', 'Answers', az_join_options_text( $k, $q ), '', 'Best answer: ' . $best . ' points.', true, ' spellcheck="false"' );
	}
	echo '</table>';

	echo '<h2>Grades</h2><p>The minimum score (out of 100) for each grade. Below the C minimum an inquiry is D.</p><table class="form-table" role="presentation">';
	foreach ( array( 'A', 'B', 'C' ) as $g ) {
		printf(
			'<tr><th scope="row"><label for="az-jf-grade-%1$s">%1$s: %2$s</label></th><td><input class="small-text" type="number" min="0" max="100" step="1" id="az-jf-grade-%1$s" name="grades[%1$s]" value="%3$d"><p class="description">%4$s</p></td></tr>',
			esc_attr( $g ),
			esc_html( $grades[ $g ][1] ),
			(int) $grades[ $g ][0],
			esc_html( $grades[ $g ][2] )
		);
	}
	echo '</table>';

	echo '<h2>After sending</h2><table class="form-table" role="presentation">';
	az_admin_row( 'az-jf-done', 'texts[done]', 'Success headline', (string) ( $saved['texts']['done'] ?? '' ) ?: $texts['done'], $texts['done'], 'Empty: the default.' );
	$f = az_settings_fields()['thanks_line'];
	az_admin_row( 'az-jf-thanks', 'thanks_line', $f[0], (string) az_opt( 'thanks_line' ), $f[1], $f[2] );
	echo '</table>';
	submit_button();
	echo '</form></div>';
}
