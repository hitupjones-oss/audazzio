<?php
/**
 * Press: the grey logo carousel of the companies Audazzio has worked with, and the list of news, case studies
 * and coverage (latest few on the home page, all of it with filters on the Newsroom).
 */

defined( 'ABSPATH' ) || exit;

function az_logos_default() {
	$out = array();
	foreach ( (array) az_data( 'logos' ) as $l ) {
		$out[] = array( 'name' => $l['name'] ?? '', 'logo' => az_ship( 'logos/' . $l['file'] ), 'url' => array( 'url' => $l['url'] ?? '' ) );
	}
	return $out;
}

/** Width / height of a logo, so a long wordmark and a square badge get the same visual weight. */
function az_logo_ratio( $logo ) {
	$id = is_array( $logo ) ? (int) ( $logo['id'] ?? 0 ) : 0;
	if ( $id ) {
		$m = wp_get_attachment_metadata( $id );
		if ( ! empty( $m['width'] ) && ! empty( $m['height'] ) ) {
			return $m['width'] / $m['height'];
		}
	}
	$url    = az_media( $logo );
	$ratios = az_seed_json( 'logo-ratios.json' );
	$base   = basename( (string) wp_parse_url( $url, PHP_URL_PATH ) );
	return isset( $ratios[ $base ] ) ? (float) $ratios[ $base ] : 3.0;
}

function az_schema_logos() {
	return array(
		'title'  => 'Logo carousel',
		'icon'   => 'eicon-carousel',
		'note'   => 'Logos are shown in grey on white. Upload transparent PNG or SVG files; a logo with a white box behind it is blended into the page.',
		'fields' => array(
			'tone'    => az_f( 'select', 'Background', 'white', array( 'options' => az_tones() ) ),
			'anchor'  => az_f( 'text', 'Anchor', 'press' ),
			'eyebrow' => az_f( 'text', 'Line above the logos', 'Selected by, on air with and featured in' ),
			'items'   => az_f( 'repeater', 'Logos', az_logos_default(), array( 'fields' => array( 'name' => az_f( 'text', 'Company', '' ), 'logo' => az_f( 'media', 'Logo', '' ), 'url' => az_f( 'url', 'Link (optional)', '' ) ), 'title' => '{{{ name }}}' ) ),
			'speed'   => az_f( 'number', 'Seconds for one full loop', 48 ),
		),
	);
}

function az_render_logos( $a ) {
	$items = array_values( array_filter( (array) $a['items'], function ( $l ) {
		return '' !== az_media( $l['logo'] ?? '' );
	} ) );
	if ( ! $items ) {
		return;
	}
	$cell = function ( $l, $hidden ) {
		$r   = max( 0.6, min( 7.0, az_logo_ratio( $l['logo'] ) ) );
		$h   = round( min( 50, 58 / pow( $r, 0.4 ) ), 1 );
		$img = sprintf( '<img src="%s" alt="%s" style="height:%spx" loading="lazy" decoding="async">', esc_url( az_media( $l['logo'] ) ), $hidden ? '' : esc_attr( $l['name'] ?? '' ), esc_attr( $h ) );
		$url = az_url( $l['url'] ?? '' );
		return '<li class="az-logo">' . ( $url && ! $hidden ? '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . $img . '</a>' : $img ) . '</li>';
	};
	az_open( $a, 'az-logos' );
	?>
	<div class="az-logos__in">
		<?php if ( $a['eyebrow'] ) : ?>
			<div class="az-logos__head">
				<svg class="az-bracket" viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path d="M0 14H440C500 14 500 106 560 106H880C940 106 940 14 1000 14H1440"/></svg>
				<p class="az-logos__eb az-label"><?php echo esc_html( $a['eyebrow'] ); ?></p>
			</div>
		<?php endif; ?>
		<div class="az-marquee" data-az-marquee style="--az-loop:<?php echo esc_attr( max( 12, (int) $a['speed'] ) ); ?>s">
			<ul class="az-marquee__track">
				<?php foreach ( $items as $l ) { echo $cell( $l, false ); } // phpcs:ignore ?>
				<?php foreach ( $items as $l ) { echo str_replace( '<li class="az-logo">', '<li class="az-logo" aria-hidden="true">', $cell( $l, true ) ); } // phpcs:ignore ?>
			</ul>
		</div>
	</div>
	</section>
	<?php
}

function az_press_default() {
	$out = array();
	foreach ( (array) az_data( 'press' ) as $p ) {
		$url = $p['url'] ?? '';
		if ( ! empty( $p['pdf'] ) ) {
			$url = az_asset( 'docs/' . $p['pdf'] );
		}
		$out[] = array(
			'date'    => $p['date'] ?? '',
			'kind'    => $p['kind'] ?? 'News',
			'outlet'  => $p['outlet'] ?? '',
			'title'   => $p['title'] ?? '',
			'summary' => $p['summary'] ?? '',
			'url'     => array( 'url' => $url ),
			'image'   => ! empty( $p['image'] ) ? az_ship( 'img/' . $p['image'] ) : array( 'url' => '' ),
		);
	}
	return $out;
}

function az_schema_press() {
	return array(
		'title'  => 'News and press',
		'icon'   => 'eicon-post-list',
		'fields' => az_head_fields( 'Newsroom', "In the news.", '', 'white' ) + array(
			'items'   => az_f( 'repeater', 'Stories', az_press_default(), array( 'fields' => array(
				'date'    => az_f( 'text', 'Date (YYYY-MM-DD, or YYYY-MM for a month)', '' ),
				'kind'    => az_f( 'select', 'Kind', 'Press release', array( 'options' => array( 'Case study' => 'Case study', 'Press release' => 'Press release', 'Coverage' => 'Coverage', 'Award' => 'Award' ) ) ),
				'outlet'  => az_f( 'text', 'Outlet or source', '' ),
				'title'   => az_f( 'textarea', 'Headline', '' ),
				'summary' => az_f( 'textarea', 'One line', '' ),
				'url'     => az_f( 'url', 'Link or PDF', '' ),
				'image'   => az_f( 'media', 'Picture', '' ),
			), 'title' => '{{{ date }}} {{{ title }}}' ) ),
			'limit'   => az_f( 'number', 'How many to show (0 = all)', 0 ),
			'filters' => az_f( 'switch', 'Filter buttons', '' ),
			'layout'  => az_f( 'select', 'Layout', 'list', array( 'options' => array( 'list' => 'List', 'cards' => 'Cards with pictures' ) ) ),
			'cta'     => az_f( 'text', 'Link', '' ),
			'cta_url' => az_f( 'url', 'Link address', '/newsroom/' ),
		),
	);
}

function az_render_press( $a ) {
	$items = (array) $a['items'];
	usort( $items, function ( $x, $y ) {
		return strcmp( (string) ( $y['date'] ?? '' ), (string) ( $x['date'] ?? '' ) );
	} );
	if ( (int) $a['limit'] > 0 ) {
		$items = array_slice( $items, 0, (int) $a['limit'] );
	}
	$kinds  = array_values( array_unique( array_filter( array_map( function ( $p ) {
		return $p['kind'] ?? '';
	}, $items ) ) ) );
	$layout = sanitize_key( $a['layout'] ?: 'list' );
	az_open( $a, 'az-press az-press--' . $layout, 'data-az-press' );
	?>
	<div class="az-wrap">
		<div class="az-head-row"><?php echo az_head( $a ); // phpcs:ignore ?><?php if ( $a['cta'] ) { echo '<p class="az-head-row__cta">' . az_btn( $a['cta'], $a['cta_url'], 'link' ) . '</p>'; } // phpcs:ignore ?></div>
		<?php if ( 'yes' === $a['filters'] && count( $kinds ) > 1 ) : ?>
			<div class="az-filters" role="group" aria-label="Show">
				<button type="button" class="az-filter" aria-pressed="true" data-az-filter="">All</button>
				<?php foreach ( $kinds as $k ) : ?><button type="button" class="az-filter" aria-pressed="false" data-az-filter="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $k ); ?></button><?php endforeach; ?>
			</div>
		<?php endif; ?>
		<ul class="az-news">
			<?php foreach ( $items as $p ) : $url = az_url( $p['url'] ?? '' ); $img = az_media( $p['image'] ?? '' ); $raw = (string) ( $p['date'] ?? '' ); $month = (bool) preg_match( '/^\d{4}-\d{2}$/', $raw ); $ts = strtotime( $month ? $raw . '-01' : $raw ); $pdf = (bool) preg_match( '/\.pdf($|\?)/i', $url ); ?>
				<li class="az-new" data-kind="<?php echo esc_attr( $p['kind'] ?? '' ); ?>" data-az-rise>
					<a class="az-new__a" href="<?php echo esc_url( $url ?: '#' ); ?>"<?php echo $url && ( $pdf || 0 !== strpos( $url, home_url() ) ) ? ' target="_blank" rel="noopener"' : ''; ?>>
						<?php if ( 'cards' === $layout ) : ?><span class="az-new__pic"><?php if ( $img ) : ?><img src="<?php echo esc_url( $img ); ?>" alt="" loading="lazy" decoding="async"><?php endif; ?></span><?php endif; ?>
						<span class="az-new__meta az-label"><span><?php echo esc_html( $p['kind'] ?? '' ); ?></span><?php if ( $ts ) : ?><time datetime="<?php echo esc_attr( gmdate( $month ? 'Y-m' : 'Y-m-d', $ts ) ); ?>"><?php echo esc_html( gmdate( $month ? 'M Y' : 'M j, Y', $ts ) ); ?></time><?php endif; ?><?php if ( ! empty( $p['outlet'] ) ) : ?><span><?php echo esc_html( $p['outlet'] ); ?></span><?php endif; ?></span>
						<span class="az-new__t"><?php echo esc_html( $p['title'] ?? '' ); ?></span>
						<?php if ( ! empty( $p['summary'] ) ) : ?><span class="az-new__p"><?php echo esc_html( $p['summary'] ); ?></span><?php endif; ?>
						<span class="az-new__go" aria-hidden="true"><?php echo az_icon( $pdf ? 'download' : 'arrow-ur' ); // phpcs:ignore ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	</section>
	<?php
}
