<?php
/**
 * Proof: the numbers band, the case studies and what people in the industry say.
 */

defined( 'ABSPATH' ) || exit;

function az_schema_numbers() {
	return array(
		'title'  => 'Numbers',
		'icon'   => 'eicon-counter',
		'fields' => az_head_fields( 'Why second screens', "Fans are already\n*on their phones.*", '', 'white' ) + array(
			'items'  => az_f( 'repeater', 'Numbers', array(
				array( 'value' => '49', 'unit' => '%', 'label' => 'use two or more screens while watching the NFL' ),
				array( 'value' => '78', 'unit' => '%', 'label' => 'of Gen Z and Millennial sports fans seek out non-game second-screen content during live sports' ),
				array( 'value' => '69', 'unit' => '%', 'label' => 'use a second screen after seeing an ad to find out more about a product or service' ),
			), array( 'fields' => array( 'value' => az_f( 'text', 'Number', '' ), 'unit' => az_f( 'text', 'After the number (%, +)', '' ), 'pre' => az_f( 'text', 'Before the number', '' ), 'label' => az_f( 'textarea', 'What it counts', '' ) ), 'title' => '{{{ value }}}{{{ unit }}}' ) ),
			'note'   => az_f( 'text', 'Small print', '' ),
			'size'   => az_f( 'select', 'Size', 'big', array( 'options' => array( 'big' => 'Big', 'compact' => 'Compact' ) ) ),
		),
	);
}

function az_render_numbers( $a ) {
	az_open( $a, 'az-numbers az-numbers--' . sanitize_key( $a['size'] ) );
	?>
	<div class="az-wrap">
		<?php echo az_head( $a ); // phpcs:ignore ?>
		<dl class="az-nums">
			<?php foreach ( (array) $a['items'] as $it ) : ?>
				<div class="az-num" data-az-rise>
					<dt class="az-num__v"><?php echo esc_html( $it['pre'] ?? '' ); ?><span data-az-count="<?php echo esc_attr( preg_replace( '/[^0-9.]/', '', (string) ( $it['value'] ?? '' ) ) ); ?>"><?php echo esc_html( $it['value'] ?? '' ); ?></span><small><?php echo esc_html( $it['unit'] ?? '' ); ?></small></dt>
					<dd class="az-num__l"><?php echo esc_html( $it['label'] ?? '' ); ?></dd>
				</div>
			<?php endforeach; ?>
		</dl>
		<?php if ( $a['note'] ) : ?><p class="az-small"><?php echo esc_html( $a['note'] ); ?></p><?php endif; ?>
	</div>
	</section>
	<?php
}

function az_cases_default() {
	$out = array();
	foreach ( (array) az_data( 'cases' ) as $c ) {
		$out[] = array(
			'org'     => $c['org'] ?? '',
			'title'   => $c['title'] ?? '',
			'year'    => $c['year'] ?? '',
			'image'   => isset( $c['image'] ) ? az_ship( 'img/' . $c['image'] ) : array( 'url' => '' ),
			'metrics' => implode( "\n", (array) ( $c['metrics'] ?? array() ) ),
			'summary' => $c['summary'] ?? '',
			'pdf'     => isset( $c['pdf'] ) ? az_ship( 'docs/' . $c['pdf'] ) : array( 'url' => '' ),
			'where'   => $c['where'] ?? '',
		);
	}
	return $out;
}

function az_schema_cases() {
	return array(
		'title'  => 'Case studies',
		'icon'   => 'eicon-posts-grid',
		'fields' => az_head_fields( 'Proven live', "On air with NBC Sports\n*and USA Swimming.*", 'Real broadcasts, real venues, real fans.', 'mist' ) + array(
			'items'   => az_f( 'repeater', 'Case studies', az_cases_default(), array( 'fields' => array(
				'org'     => az_f( 'text', 'Partner', '' ),
				'title'   => az_f( 'textarea', 'Headline', '' ),
				'year'    => az_f( 'text', 'Year', '' ),
				'where'   => az_f( 'text', 'Where it played (Broadcast, Venue, Both)', '' ),
				'image'   => az_f( 'media', 'Picture', '' ),
				'metrics' => az_f( 'textarea', 'Results (one per line: number | what it counts)', '' ),
				'summary' => az_f( 'textarea', 'Summary', '' ),
				'pdf'     => az_f( 'media', 'Case study PDF', '', array( 'media_types' => array( 'application/pdf' ) ) ),
			), 'title' => '{{{ org }}} {{{ year }}}' ) ),
			'cta'     => az_f( 'text', 'Link', 'All case studies and news' ),
			'cta_url' => az_f( 'url', 'Link address', '/newsroom/' ),
		),
	);
}

function az_render_cases( $a ) {
	if ( empty( $a['anchor'] ) ) {
		$a['anchor'] = 'cases';
	}
	az_open( $a, 'az-cases' );
	?>
	<div class="az-wrap">
		<div class="az-head-row"><?php echo az_head( $a ); // phpcs:ignore ?><?php if ( $a['cta'] ) { echo '<p class="az-head-row__cta">' . az_btn( $a['cta'], $a['cta_url'], 'link' ) . '</p>'; } // phpcs:ignore ?></div>
		<div class="az-rail" data-az-rail>
			<ul class="az-rail__track">
				<?php foreach ( (array) $a['items'] as $c ) : $img = az_media( $c['image'] ?? '' ); $pdf = az_media( $c['pdf'] ?? '' ); ?>
					<li class="az-case" data-az-rise>
						<div class="az-case__pic"><?php if ( $img ) : ?><img src="<?php echo esc_url( $img ); ?>" alt="" loading="lazy" decoding="async"><?php endif; ?><span class="az-case__tag az-label"><?php echo esc_html( trim( ( $c['where'] ?? '' ) . ( ! empty( $c['year'] ) ? ' · ' . $c['year'] : '' ), ' ·' ) ); ?></span></div>
						<div class="az-case__body">
							<p class="az-case__org az-label"><?php echo esc_html( $c['org'] ?? '' ); ?></p>
							<h3 class="az-case__t"><?php echo esc_html( $c['title'] ?? '' ); ?></h3>
							<?php $m = az_pairs( $c['metrics'] ?? '' ); ?>
							<?php if ( count( $m ) < 2 && ! empty( $c['summary'] ) ) : ?>
								<p class="az-case__p"><?php echo esc_html( $c['summary'] ); ?></p>
							<?php endif; ?>
							<?php if ( $m ) : ?>
								<dl class="az-case__m"><?php foreach ( array_slice( $m, 0, 2 ) as $row ) : ?><div><dt><?php echo esc_html( $row[0] ); ?></dt><dd><?php echo esc_html( $row[1] ); ?></dd></div><?php endforeach; ?></dl>
							<?php endif; ?>
							<?php if ( $pdf ) : ?><a class="az-case__dl" href="<?php echo esc_url( $pdf ); ?>" target="_blank" rel="noopener"><?php echo az_icon( 'download' ); // phpcs:ignore ?><span>Read the case study</span></a><?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
			<div class="az-rail__nav" aria-hidden="true"><button type="button" class="az-rail__b" data-az-rail-prev tabindex="-1"><?php echo az_icon( 'arrow' ); // phpcs:ignore ?></button><button type="button" class="az-rail__b" data-az-rail-next tabindex="-1"><?php echo az_icon( 'arrow' ); // phpcs:ignore ?></button></div>
		</div>
	</div>
	</section>
	<?php
}

function az_quotes_default() {
	$out = array();
	foreach ( (array) az_data( 'quotes' ) as $q ) {
		$out[] = array( 'quote' => $q['quote'] ?? '', 'name' => $q['name'] ?? '', 'role' => $q['role'] ?? '' );
	}
	return $out;
}

function az_schema_quotes() {
	return array(
		'title'  => 'Quotes',
		'icon'   => 'eicon-testimonial',
		'fields' => az_head_fields( 'What the industry says', '', '', 'white' ) + array(
			'items' => az_f( 'repeater', 'Quotes', az_quotes_default(), array( 'fields' => array( 'quote' => az_f( 'textarea', 'Quote', '' ), 'name' => az_f( 'text', 'Name', '' ), 'role' => az_f( 'text', 'Title, company', '' ) ), 'title' => '{{{ name }}}' ) ),
		),
	);
}

function az_render_quotes( $a ) {
	$items = array_values( array_filter( (array) $a['items'], function ( $q ) {
		return ! empty( $q['quote'] );
	} ) );
	if ( ! $items ) {
		return;
	}
	az_open( $a, 'az-quotes', 'data-az-quotes' );
	?>
	<div class="az-wrap az-wrap--narrow">
		<?php echo az_head( $a, 'az-head--center' ); // phpcs:ignore ?>
		<div class="az-quotes__stage">
			<?php foreach ( $items as $i => $q ) : ?>
				<figure class="az-quote<?php echo 0 === $i ? ' is-on' : ''; ?>" data-az-quote="<?php echo (int) $i; ?>"<?php echo 0 === $i ? '' : ' aria-hidden="true"'; ?>>
					<blockquote class="az-quote__q"><p><?php echo esc_html( $q['quote'] ); ?></p></blockquote>
					<figcaption class="az-quote__by"><b><?php echo esc_html( $q['name'] ); ?></b><span><?php echo esc_html( $q['role'] ); ?></span></figcaption>
				</figure>
			<?php endforeach; ?>
		</div>
		<?php if ( count( $items ) > 1 ) : ?>
			<div class="az-quotes__dots" role="tablist" aria-label="Quotes">
				<?php foreach ( $items as $i => $q ) : ?><button type="button" role="tab" class="az-quotes__dot" aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr( $q['name'] ); ?>" data-az-quote-go="<?php echo (int) $i; ?>"><i></i></button><?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
	</section>
	<?php
}
