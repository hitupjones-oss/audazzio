<?php
/**
 * Shared lists: News, Case studies and Logos, kept once under Audazzio and shown by every news list, case study
 * rail and logo carousel whose List is "Shared list". Each entry is a private post (it has no page of its own on
 * the site) with its fields in one box under the title. The starter entries are made by az_seed_lists().
 */

defined( 'ABSPATH' ) || exit;

/** The lists: post type => names, the title box, help, the admin columns and the fields (az_f shorthand, plus help). */
function az_lists() {
	static $lists = null;
	if ( null !== $lists ) {
		return $lists;
	}
	$kinds = array( 'Case study' => 'Case study', 'Press release' => 'Press release', 'Coverage' => 'Coverage', 'Award' => 'Award' );
	$lists = array(
		'az_news' => array(
			'name'    => 'News',
			'one'     => 'News item',
			'title'   => 'Headline',
			'key'     => 'title',
			'sorted'  => false,
			'help'    => 'Published items show in the Newsroom list, newest first, and the latest four on the home page. Save one as a draft to take it off the site.',
			'columns' => array( 'title' => 'Headline', 'kind' => 'Kind', 'date' => 'Date', 'outlet' => 'Outlet' ),
			'fields'  => array(
				'kind'    => az_f( 'select', 'Kind', 'Press release', array( 'options' => $kinds ) ),
				'date'    => az_f( 'date', 'Date', '', array( 'help' => 'Written as 2026-01-23, or as 2026-01 when only the month is known (the site then shows Jan 2026).' ) ),
				'outlet'  => az_f( 'text', 'Outlet or source', '', array( 'help' => 'Who published it. Audazzio for its own releases and case studies.' ) ),
				'url'     => az_f( 'file', 'Link or PDF', '', array( 'pick' => 'application/pdf', 'help' => 'Paste the address of the article, or choose a PDF from the Media Library.' ) ),
				'summary' => az_f( 'textarea', 'One line', '', array( 'rows' => 2 ) ),
				'image'   => az_f( 'image', 'Picture', '', array( 'help' => 'Shown when a news list uses the cards layout. Landscape, cropped to 4:3.' ) ),
			),
		),
		'az_case' => array(
			'name'    => 'Case studies',
			'one'     => 'Case study',
			'title'   => 'Headline',
			'key'     => 'title',
			'sorted'  => true,
			'help'    => 'Published case studies show in every case study rail (home, Solutions and Newsroom), by Order: lower numbers first. Save one as a draft to take it off the site.',
			'columns' => array( 'image' => 'Picture', 'title' => 'Headline', 'org' => 'Partner', 'year' => 'Year', 'order' => 'Order' ),
			'fields'  => array(
				'org'     => az_f( 'text', 'Partner', '', array( 'help' => 'For example NBC Sports · Tour de France.' ) ),
				'year'    => az_f( 'text', 'Year', '' ),
				'where'   => az_f( 'text', 'Where it played', '', array( 'help' => 'Broadcast, Venue, or both (for example Venue and home). Shown on the picture with the year.' ) ),
				'image'   => az_f( 'image', 'Picture', '', array( 'help' => 'Landscape, cropped to 16:10.' ) ),
				'metrics' => az_f( 'textarea', 'Results', '', array( 'rows' => 3, 'help' => 'One per line: number | what it counts. The first two show on the card.' ) ),
				'summary' => az_f( 'textarea', 'Summary', '', array( 'rows' => 3, 'help' => 'Shown on the card when there are fewer than two results.' ) ),
				'pdf'     => az_f( 'file', 'Case study PDF', '', array( 'pick' => 'application/pdf' ) ),
			),
		),
		'az_logo' => array(
			'name'    => 'Logos',
			'one'     => 'Logo',
			'title'   => 'Company',
			'key'     => 'name',
			'sorted'  => true,
			'help'    => 'Published logos run in every logo carousel, by Order: lower numbers first.',
			'columns' => array( 'logo' => 'Logo', 'title' => 'Company', 'url' => 'Link', 'order' => 'Order' ),
			'fields'  => array(
				'logo' => az_f( 'image', 'Logo', '', array( 'help' => 'A transparent SVG or PNG. It is shown in grey on white.' ) ),
				'url'  => az_f( 'url', 'Link (optional)', '' ),
			),
		),
	);
	return $lists;
}

add_action( 'init', function () {
	foreach ( az_lists() as $type => $l ) {
		$one = strtolower( $l['one'] );
		register_post_type( $type, array(
			'labels'              => array(
				'name'               => $l['name'],
				'singular_name'      => $l['one'],
				'menu_name'          => $l['name'],
				'all_items'          => $l['name'],
				'add_new'            => 'Add ' . $one,
				'add_new_item'       => 'Add ' . $one,
				'edit_item'          => 'Edit ' . $one,
				'new_item'           => 'New ' . $one,
				'search_items'       => 'Search ' . strtolower( $l['name'] ),
				'not_found'          => 'None yet.',
				'not_found_in_trash' => 'None in the Trash.',
				'attributes'         => 'Order',
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => 'audazzio',
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'show_in_rest'        => false,
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'hierarchical'        => false,
			'capability_type'     => 'page',
			'map_meta_cap'        => true,
			'delete_with_user'    => false,
			'supports'            => $l['sorted'] ? array( 'title', 'page-attributes' ) : array( 'title' ),
		) );
	}
} );

/** Starter entries on the first admin visit of a site that had the plugin before the lists existed. */
add_action( 'admin_init', function () {
	if ( function_exists( 'az_seed_lists' ) && current_user_can( 'edit_pages' ) ) {
		az_seed_lists();
	}
} );

/** A news date as the site shows it: array( datetime attribute, label ), or null. "2025-05" is a month. */
function az_news_date( $raw ) {
	$raw   = (string) $raw;
	$month = (bool) preg_match( '/^\d{4}-\d{2}$/', $raw );
	$ts    = '' === $raw ? false : strtotime( $month ? $raw . '-01' : $raw );
	return $ts ? array( gmdate( $month ? 'Y-m' : 'Y-m-d', $ts ), gmdate( $month ? 'M Y' : 'M j, Y', $ts ) ) : null;
}

/**
 * The published entries of a list, in the shape the widget's own list has (so the markup is the same either way).
 * Read once per request. Until the starter entries exist, the starter data itself.
 */
function az_list_items( $type ) {
	static $cache = array();
	$lists = az_lists();
	if ( ! isset( $lists[ $type ] ) ) {
		return array();
	}
	if ( isset( $cache[ $type ] ) ) {
		return $cache[ $type ];
	}
	if ( ! get_option( 'az_lists_seeded' ) ) {
		$starter        = array( 'az_news' => 'az_press_default', 'az_case' => 'az_cases_default', 'az_logo' => 'az_logos_default' );
		$cache[ $type ] = (array) call_user_func( $starter[ $type ] );
		return $cache[ $type ];
	}
	$out   = array();
	$posts = get_posts( array( 'post_type' => $type, 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'ID' => 'ASC' ), 'no_found_rows' => true ) );
	foreach ( $posts as $p ) {
		$item = array( $lists[ $type ]['key'] => $p->post_title );
		foreach ( $lists[ $type ]['fields'] as $k => $f ) {
			$v = (string) get_post_meta( $p->ID, '_az_' . $k, true );
			if ( 'url' === $f['type'] ) {
				$v = array( 'url' => $v );
			} elseif ( in_array( $f['type'], array( 'image', 'file' ), true ) ) {
				$v = array( 'url' => $v, 'id' => (int) get_post_meta( $p->ID, '_az_' . $k . '_id', true ) ?: '' );
			}
			$item[ $k ] = $v;
		}
		$out[] = $item;
	}
	$cache[ $type ] = $out;
	return $out;
}

/** The entries a widget shows: its own list, or the shared one. */
function az_list_for( $a, $type ) {
	return 'own' === ( $a['source'] ?? '' ) ? array_values( (array) ( $a['items'] ?? array() ) ) : az_list_items( $type );
}

/** A widget's List control and, for the shared list, a note that links to it. The own list's rows show only for "own". */
function az_list_fields( $type, $what ) {
	$l = az_lists()[ $type ];
	return array(
		'source'      => az_f( 'select', 'List', 'shared', array( 'options' => array( 'shared' => 'Shared list (Audazzio > ' . $l['name'] . ')', 'own' => 'This widget’s own list' ), 'label_block' => true ) ),
		'source_note' => az_f( 'note', '', sprintf( '%s Edit them under Audazzio > %s, and every page that uses the list follows. <a href="%s" target="_blank" rel="noopener">Open %s</a>', esc_html( $what ), esc_html( $l['name'] ), esc_url( admin_url( 'edit.php?post_type=' . $type ) ), esc_html( $l['name'] ) ), array( 'condition' => array( 'source' => 'shared' ) ) ),
	);
}

/* ------------------------------------------------------------------ admin */

/** In the Audazzio menu the lists come right after the Inbox. */
add_action( 'admin_menu', function () {
	global $submenu;
	if ( empty( $submenu['audazzio'] ) ) {
		return;
	}
	$slugs = array_map( function ( $t ) {
		return 'edit.php?post_type=' . $t;
	}, array_keys( az_lists() ) );
	$first = array();
	$lists = array();
	$rest  = array();
	foreach ( $submenu['audazzio'] as $item ) {
		$at = array_search( $item[2], $slugs, true );
		if ( 'audazzio' === $item[2] ) {
			$first[] = $item;
		} elseif ( false !== $at ) {
			$lists[ $at ] = $item;
		} else {
			$rest[] = $item;
		}
	}
	ksort( $lists );
	$submenu['audazzio'] = array_merge( $first, array_values( $lists ), $rest ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
}, 99 );

add_filter( 'enter_title_here', function ( $text, $post ) {
	$lists = az_lists();
	return isset( $lists[ $post->post_type ] ) ? $lists[ $post->post_type ]['title'] : $text;
}, 10, 2 );

add_filter( 'disable_months_dropdown', function ( $off, $type ) {
	return $off || isset( az_lists()[ $type ] );
}, 10, 2 );

add_filter( 'post_updated_messages', function ( $m ) {
	foreach ( az_lists() as $type => $l ) {
		$m[ $type ] = array( 1 => $l['one'] . ' updated.', 4 => $l['one'] . ' updated.', 6 => $l['one'] . ' published.', 7 => $l['one'] . ' saved.', 8 => $l['one'] . ' submitted.', 10 => 'Draft saved.' );
	}
	return $m;
} );

add_action( 'add_meta_boxes', function ( $type ) {
	if ( isset( az_lists()[ $type ] ) ) {
		add_meta_box( 'az-list', 'Details', 'az_list_box', $type, 'normal', 'high' );
	}
} );

function az_list_box( $post ) {
	$l = az_lists()[ $post->post_type ];
	wp_nonce_field( 'az_list_' . $post->ID, 'az_list_nonce' );
	echo '<p>' . esc_html( $l['help'] ) . '</p><table class="form-table az-list" role="presentation">';
	foreach ( $l['fields'] as $k => $f ) {
		$v = 'auto-draft' === $post->post_status ? (string) $f['default'] : (string) get_post_meta( $post->ID, '_az_' . $k, true );
		az_list_field( $k, $f, $v );
	}
	echo '</table>';
}

/** One labelled field of the Details box. */
function az_list_field( $k, $f, $v ) {
	$id   = 'az-' . $k;
	$name = 'az[' . $k . ']';
	$help = $f['help'] ?? '';
	switch ( $f['type'] ) {
		case 'select':
			$field = '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
			foreach ( $f['options'] as $ov => $ol ) {
				$field .= '<option value="' . esc_attr( $ov ) . '"' . selected( $v, $ov, false ) . '>' . esc_html( $ol ) . '</option>';
			}
			$field .= '</select>';
			break;
		case 'textarea':
			$field = sprintf( '<textarea class="large-text" rows="%d" id="%s" name="%s">%s</textarea>', (int) ( $f['rows'] ?? 3 ), esc_attr( $id ), esc_attr( $name ), esc_textarea( $v ) );
			break;
		case 'date':
			$field = sprintf( '<input type="text" class="regular-text" id="%s" name="%s" value="%s" placeholder="2026-01-23" inputmode="numeric" pattern="\d{4}-\d{1,2}(-\d{1,2})?" title="Year-month-day, or year-month">', esc_attr( $id ), esc_attr( $name ), esc_attr( $v ) );
			break;
		case 'image':
		case 'file':
			$img   = 'image' === $f['type'];
			$field = sprintf(
				'<span class="az-list__pick"><input type="text" class="large-text" id="%1$s" name="%2$s" value="%3$s"%4$s><button type="button" class="button" data-az-pick="%5$s" data-for="%1$s" data-title="%6$s">Choose file</button></span>',
				esc_attr( $id ),
				esc_attr( $name ),
				esc_attr( $v ),
				$img ? ' data-az-preview' : '',
				esc_attr( $img ? 'image' : ( $f['pick'] ?? '' ) ),
				esc_attr( $f['label'] )
			);
			if ( $img ) {
				$src    = az_media( $v );
				$field .= '<span class="az-list__pv" id="' . esc_attr( $id ) . '-pv"' . ( $src ? '' : ' hidden' ) . '><img src="' . esc_url( $src ) . '" alt=""></span>';
			}
			if ( 0 === strpos( $v, 'asset:' ) ) {
				$help = trim( $help . ' An address starting with asset: is a file that ships with the site.' );
			}
			break;
		default:
			$field = sprintf( '<input type="text" class="large-text" id="%s" name="%s" value="%s">', esc_attr( $id ), esc_attr( $name ), esc_attr( $v ) );
	}
	printf( '<tr><th scope="row"><label for="%s">%s</label></th><td>%s%s</td></tr>', esc_attr( $id ), esc_html( $f['label'] ), $field, $help ? '<p class="description">' . esc_html( $help ) . '</p>' : '' ); // phpcs:ignore
}

/** "2025-5-3" -> "2025-05-03", "2025-05" stays a month, '' stays empty; anything else is not a date (null). */
function az_list_clean_date( $v ) {
	$v = trim( (string) $v );
	if ( '' === $v ) {
		return '';
	}
	if ( ! preg_match( '/^(\d{4})-(\d{1,2})(?:-(\d{1,2}))?$/', $v, $m ) ) {
		return null;
	}
	$y  = (int) $m[1];
	$mo = (int) $m[2];
	$d  = isset( $m[3] ) ? (int) $m[3] : 0;
	if ( $mo < 1 || $mo > 12 || ( $d && ! checkdate( $mo, $d, $y ) ) ) {
		return null;
	}
	return sprintf( '%04d-%02d', $y, $mo ) . ( $d ? sprintf( '-%02d', $d ) : '' );
}

/** A link or file: an address, a path on this site, or asset:folder/file for a file that ships with the plugin. */
function az_list_clean_url( $v ) {
	$v = trim( (string) $v );
	if ( 0 === strpos( $v, 'asset:' ) ) {
		return 'asset:' . ltrim( preg_replace( '#[^A-Za-z0-9._/-]|\.\.#', '', substr( $v, 6 ) ), '/' );
	}
	return esc_url_raw( $v );
}

add_action( 'save_post', function ( $id, $post ) {
	$lists = az_lists();
	if ( ! isset( $lists[ $post->post_type ], $_POST['az_list_nonce'] ) || wp_is_post_revision( $id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['az_list_nonce'] ) ), 'az_list_' . $id ) || ! current_user_can( 'edit_post', $id ) ) {
		return;
	}
	$in = isset( $_POST['az'] ) && is_array( $_POST['az'] ) ? wp_unslash( $_POST['az'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- each field is sanitised below.
	foreach ( $lists[ $post->post_type ]['fields'] as $k => $f ) {
		$v = isset( $in[ $k ] ) && is_scalar( $in[ $k ] ) ? (string) $in[ $k ] : '';
		switch ( $f['type'] ) {
			case 'select':
				$v = isset( $f['options'][ $v ] ) ? $v : $f['default'];
				break;
			case 'textarea':
				$v = sanitize_textarea_field( str_replace( "\r\n", "\n", $v ) );
				break;
			case 'date':
				$v = az_list_clean_date( $v );
				if ( null === $v ) {
					// Not a date: the saved one stays, and the screen says why.
					add_filter( 'redirect_post_location', function ( $loc ) {
						return add_query_arg( 'az_bad_date', 1, $loc );
					} );
					continue 2;
				}
				break;
			case 'url':
			case 'image':
			case 'file':
				$v = az_list_clean_url( $v );
				break;
			default:
				$v = sanitize_text_field( $v );
		}
		update_post_meta( $id, '_az_' . $k, wp_slash( $v ) );
		if ( in_array( $f['type'], array( 'image', 'file' ), true ) ) {
			update_post_meta( $id, '_az_' . $k . '_id', $v && 0 !== strpos( $v, 'asset:' ) ? attachment_url_to_postid( $v ) : 0 );
		}
	}
}, 10, 2 );

add_action( 'admin_notices', function () {
	$screen = get_current_screen();
	if ( $screen && 'post' === $screen->base && isset( az_lists()[ $screen->post_type ] ) && ! empty( $_GET['az_bad_date'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-error"><p>The date was not saved: write it as 2026-01-23, or as 2026-01 for a month.</p></div>';
	}
} );

foreach ( array_keys( az_lists() ) as $az_type ) {
	add_filter( "manage_{$az_type}_posts_columns", function ( $cols ) use ( $az_type ) {
		$out = array( 'cb' => $cols['cb'] ?? '<input type="checkbox">' );
		foreach ( az_lists()[ $az_type ]['columns'] as $k => $label ) {
			$out[ 'title' === $k ? 'title' : 'az_' . $k ] = $label;
		}
		return $out;
	} );
	add_filter( "manage_edit-{$az_type}_sortable_columns", function ( $cols ) use ( $az_type ) {
		if ( az_lists()[ $az_type ]['sorted'] ) {
			$cols['az_order'] = 'menu_order';
		} else {
			$cols['az_date'] = array( 'az_date', true );
		}
		return $cols;
	} );
	add_action( "manage_{$az_type}_posts_custom_column", function ( $col, $id ) use ( $az_type ) {
		$k = substr( $col, 3 );
		if ( 'order' === $k ) {
			echo (int) get_post_field( 'menu_order', $id );
			return;
		}
		$f = az_lists()[ $az_type ]['fields'][ $k ] ?? null;
		if ( ! $f ) {
			return;
		}
		$v = (string) get_post_meta( $id, '_az_' . $k, true );
		if ( 'image' === $f['type'] ) {
			echo $v ? '<img class="az-list__thumb az-list__thumb--' . esc_attr( $k ) . '" src="' . esc_url( az_media( $v ) ) . '" alt="">' : '';
		} elseif ( 'date' === $f['type'] ) {
			$d = az_news_date( $v );
			echo $d ? '<time datetime="' . esc_attr( $d[0] ) . '">' . esc_html( $d[1] ) . '</time>' : esc_html( $v );
		} elseif ( 'url' === $f['type'] ) {
			echo $v ? '<a href="' . esc_url( az_url( $v ) ) . '" target="_blank" rel="noopener">' . esc_html( preg_replace( '#^www\.#', '', (string) wp_parse_url( az_url( $v ), PHP_URL_HOST ) ) ) . '</a>' : '';
		} else {
			echo esc_html( $v );
		}
	}, 10, 2 );
}
unset( $az_type );

/** The admin lists: news by date (newest first), case studies and logos by Order. */
add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() || ! $q->is_main_query() ) {
		return;
	}
	$type  = $q->get( 'post_type' );
	$lists = az_lists();
	if ( ! is_string( $type ) || ! isset( $lists[ $type ] ) ) {
		return;
	}
	$by = $q->get( 'orderby' );
	if ( 'az_news' === $type && ( ! $by || 'az_date' === $by ) ) {
		$order = 'ASC' === strtoupper( (string) $q->get( 'order' ) ) && $by ? 'ASC' : 'DESC';
		$q->set( 'meta_query', array( 'relation' => 'OR', 'az_date' => array( 'key' => '_az_date', 'compare' => 'EXISTS' ), array( 'key' => '_az_date', 'compare' => 'NOT EXISTS' ) ) );
		$q->set( 'orderby', array( 'az_date' => $order, 'ID' => $order ) );
	} elseif ( $lists[ $type ]['sorted'] && ! $by ) {
		$q->set( 'orderby', array( 'menu_order' => 'ASC', 'ID' => 'ASC' ) );
	}
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	$screen = get_current_screen();
	if ( ! $screen || ! isset( az_lists()[ $screen->post_type ] ) || ! in_array( $hook, array( 'post.php', 'post-new.php', 'edit.php' ), true ) ) {
		return;
	}
	wp_register_style( 'az-lists', false, array(), AZ_VERSION );
	wp_enqueue_style( 'az-lists' );
	wp_add_inline_style( 'az-lists', '.az-list__pick{display:flex;gap:8px;align-items:center;max-width:44em}.az-list__pick input{flex:1}.az-list__pv{display:block;margin-top:10px}.az-list__pv[hidden]{display:none}.az-list__pv img{display:block;max-width:240px;max-height:120px;padding:8px;background:#fff;border:1px solid #dcdcde;border-radius:4px}.az-list__thumb{display:block;max-width:96px;max-height:56px;border-radius:3px}.az-list__thumb--logo{max-height:28px;filter:grayscale(1)}.column-az_image,.column-az_logo{width:110px}.column-az_order,.column-az_year,.column-az_kind{width:110px}' );
	if ( 'edit.php' === $hook ) {
		return;
	}
	wp_enqueue_media();
	wp_register_script( 'az-lists', false, array( 'media-editor' ), AZ_VERSION, true );
	wp_enqueue_script( 'az-lists' );
	wp_add_inline_script( 'az-lists', 'window.AZ_ASSETS=' . wp_json_encode( az_asset( '' ) ) . ';(()=>{const src=(v)=>v.startsWith("asset:")?window.AZ_ASSETS+v.slice(6):v;const show=(i)=>{const p=document.getElementById(i.id+"-pv");if(!p)return;const v=i.value.trim();p.hidden=!v;if(v)p.querySelector("img").src=src(v)};document.addEventListener("click",(e)=>{const b=e.target.closest("[data-az-pick]");if(!b||!window.wp||!wp.media)return;e.preventDefault();const i=document.getElementById(b.dataset.for);const f=wp.media({title:b.dataset.title,button:{text:"Use this file"},library:b.dataset.azPick?{type:b.dataset.azPick}:{},multiple:false});f.on("select",()=>{i.value=f.state().get("selection").first().get("url");show(i)});f.open()});document.addEventListener("input",(e)=>{if(e.target.matches("[data-az-preview]"))show(e.target)})})();' );
} );
