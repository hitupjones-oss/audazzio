<?php
/**
 * The company: the SportsTech story, leadership and board (portraits, bios, LinkedIn), what the team
 * believes, questions and answers, and a plain text section (privacy).
 */

defined( 'ABSPATH' ) || exit;

function az_schema_story() {
	return array(
		'title'  => 'Story (SportsTech)',
		'icon'   => 'eicon-time-line',
		'fields' => az_head_fields( 'Comcast NBCUniversal SportsTech', "Built in the room\n*where sport meets tech.*", 'Audazzio is a product of the 2021 inaugural Comcast NBCUniversal SportsTech Accelerator, one of ten companies selected from over 1,000 applicants.', 'white' ) + array(
			'points' => az_f( 'textarea', 'Since then (one per line)', "A defined, deliberate company focus\nNew investors, including Comcast and Boomtown\nExtensive scientific research in acoustics and ultrasonic signaling, leading to the first of multiple patent applications\nA retooled marketing strategy and the rebrand to Audazzio\nA successful Audazzio trial during the Tour de France", array( 'rows' => 6 ) ),
			'stats'  => az_f( 'textarea', 'Numbers (one per line: number | what it counts)', "1 of 10 | companies selected for the inaugural accelerator\n8,000+ | live events delivered by the team\n160+ | years of technical experience in house" ),
			'badge'  => az_f( 'media', 'Badge picture', '' ),
		),
	);
}

function az_render_story( $a ) {
	if ( empty( $a['anchor'] ) ) {
		$a['anchor'] = 'story';
	}
	$badge = az_media( $a['badge'] );
	az_open( $a, 'az-story' );
	?>
	<div class="az-wrap az-story__grid">
		<div class="az-story__text">
			<?php echo az_head( $a ); // phpcs:ignore ?>
			<?php $pts = az_lines( $a['points'] ); if ( $pts ) : ?>
				<p class="az-label az-story__since">Since SportsTech, the company has</p>
				<ul class="az-checks"><?php foreach ( $pts as $p ) : ?><li data-az-rise><?php echo az_icon( 'check' ) . esc_html( $p ); // phpcs:ignore ?></li><?php endforeach; ?></ul>
			<?php endif; ?>
		</div>
		<aside class="az-story__card" data-az-rise>
			<?php if ( $badge ) : ?><img class="az-story__badge" src="<?php echo esc_url( $badge ); ?>" alt="Comcast NBCUniversal SportsTech" loading="lazy" decoding="async"><?php endif; ?>
			<dl class="az-story__stats"><?php foreach ( az_pairs( $a['stats'] ) as $s ) : ?><div><dt><?php echo esc_html( $s[0] ); ?></dt><dd><?php echo esc_html( $s[1] ); ?></dd></div><?php endforeach; ?></dl>
			<span class="az-story__waves" aria-hidden="true"><canvas data-az-waves="card"></canvas></span>
		</aside>
	</div>
	</section>
	<?php
}

function az_people_default( $group ) {
	$out = array();
	foreach ( (array) az_data( 'people' ) as $p ) {
		if ( ( $p['group'] ?? '' ) !== $group ) {
			continue;
		}
		$out[] = array(
			'name'       => $p['name'] ?? '',
			'role'       => $p['role'] ?? '',
			'photo'      => ! empty( $p['photo'] ) ? az_ship( 'people/' . $p['photo'] ) : array( 'url' => '' ),
			'short'      => $p['short'] ?? '',
			'bio'        => $p['bio'] ?? '',
			'highlights' => implode( "\n", (array) ( $p['highlights'] ?? array() ) ),
			'linkedin'   => array( 'url' => $p['linkedin'] ?? '' ),
		);
	}
	return $out;
}

function az_schema_people() {
	return array(
		'title'  => 'People (leadership or board)',
		'icon'   => 'eicon-person',
		'note'   => 'Portraits look best as 4:5 pictures (for example 1200 × 1500) with the person centred. Each card opens the full biography.',
		'fields' => az_head_fields( 'Leadership', "The people\n*behind the signal.*", '', 'white' ) + array(
			'group'  => az_f( 'select', 'Layout', 'leadership', array( 'options' => array( 'leadership' => 'Large portraits (leadership)', 'board' => 'Smaller portraits (board)' ) ) ),
			'items'  => az_f( 'repeater', 'People', az_people_default( 'leadership' ), array( 'fields' => array(
				'name'       => az_f( 'text', 'Name', '' ),
				'role'       => az_f( 'text', 'Title', '' ),
				'photo'      => az_f( 'media', 'Portrait', '' ),
				'short'      => az_f( 'textarea', 'One-line summary (on the card)', '' ),
				'bio'        => az_f( 'textarea', 'Full biography', '', array( 'rows' => 10 ) ),
				'highlights' => az_f( 'textarea', 'Highlights (one per line)', '' ),
				'linkedin'   => az_f( 'url', 'LinkedIn profile', '' ),
			), 'title' => '{{{ name }}}' ) ),
		),
	);
}

function az_render_people( $a ) {
	$group = sanitize_key( $a['group'] ?: 'leadership' );
	if ( empty( $a['anchor'] ) ) {
		$a['anchor'] = $group;
	}
	az_open( $a, 'az-people az-people--' . $group );
	?>
	<div class="az-wrap">
		<?php echo az_head( $a ); // phpcs:ignore ?>
		<ul class="az-people__grid">
			<?php foreach ( (array) $a['items'] as $i => $p ) : $img = az_media( $p['photo'] ?? '' ); $li = az_url( $p['linkedin'] ?? '' ); $id = 'az-bio-' . sanitize_title( $p['name'] ?? (string) $i ); ?>
				<li class="az-person" data-az-rise>
					<button class="az-person__open" type="button" data-az-bio="<?php echo esc_attr( $id ); ?>" aria-label="<?php echo esc_attr( 'Read the biography of ' . ( $p['name'] ?? '' ) ); ?>">
						<span class="az-person__pic"><?php if ( $img ) : ?><img src="<?php echo esc_url( $img ); ?>" alt="" loading="lazy" decoding="async"><?php else : ?><span class="az-person__initials"><?php echo esc_html( implode( '', array_map( function ( $w ) { return mb_substr( $w, 0, 1 ); }, array_slice( preg_split( '/\s+/', trim( (string) ( $p['name'] ?? '' ) ) ), 0, 2 ) ) ) ); ?></span><?php endif; ?></span>
					</button>
					<div class="az-person__body">
						<h3 class="az-person__name"><?php echo esc_html( $p['name'] ?? '' ); ?></h3>
						<p class="az-person__role"><?php echo esc_html( $p['role'] ?? '' ); ?></p>
						<?php if ( ! empty( $p['short'] ) && 'leadership' === $group ) : ?><p class="az-person__short"><?php echo esc_html( $p['short'] ); ?></p><?php endif; ?>
						<div class="az-person__acts">
							<button class="az-person__more" type="button" data-az-bio="<?php echo esc_attr( $id ); ?>"><span>Read bio</span><?php echo az_icon( 'plus' ); // phpcs:ignore ?></button>
							<?php if ( $li ) : ?><a class="az-person__li" href="<?php echo esc_url( $li ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( ( $p['name'] ?? '' ) . ' on LinkedIn' ); ?>"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.13 1.45-2.13 2.94v5.67H9.35V9h3.41v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.46v6.28ZM5.34 7.43a2.06 2.06 0 1 1 0-4.13 2.06 2.06 0 0 1 0 4.13ZM7.12 20.45H3.56V9h3.56v11.45ZM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.73C24 .77 23.2 0 22.22 0Z"/></svg></a><?php endif; ?>
						</div>
					</div>
					<template id="<?php echo esc_attr( $id ); ?>">
						<div class="az-bio">
							<div class="az-bio__pic"><?php if ( $img ) : ?><img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $p['name'] ?? '' ); ?>"><?php endif; ?></div>
							<div class="az-bio__text">
								<p class="az-eyebrow"><?php echo esc_html( $p['role'] ?? '' ); ?></p>
								<h2 class="az-bio__name"><?php echo esc_html( $p['name'] ?? '' ); ?></h2>
								<?php $hl = az_lines( $p['highlights'] ?? '' ); if ( $hl ) : ?><ul class="az-chips az-chips--soft"><?php foreach ( $hl as $h ) : ?><li><?php echo esc_html( $h ); ?></li><?php endforeach; ?></ul><?php endif; ?>
								<div class="az-bio__body"><?php echo az_paras( $p['bio'] ?? '' ); // phpcs:ignore ?></div>
								<?php if ( $li ) : ?><a class="az-btn az-btn--ghost" href="<?php echo esc_url( $li ); ?>" target="_blank" rel="noopener"><span>View on LinkedIn</span><?php echo az_icon( 'arrow-ur' ); // phpcs:ignore ?></a><?php endif; ?>
							</div>
						</div>
					</template>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	</section>
	<?php
}

function az_schema_values() {
	return array(
		'title'  => 'What we believe',
		'icon'   => 'eicon-favorite',
		'fields' => az_head_fields( 'How we work', "No rewind button.\n*No excuses.*", '', 'mist' ) + array(
			'items' => az_f( 'repeater', 'Beliefs', array(
				array( 'title' => 'Integrity matters', 'text' => 'We come from the entertainment world, where there is no rewind button and you can’t come back tomorrow if you got it wrong. So we keep our promises, because that is the only way to earn your trust.' ),
				array( 'title' => 'We don’t care for drama', 'text' => 'We love the dramatic arts and frown on drama in our company. We respond to your needs quickly, accurately and with no excuses.' ),
				array( 'title' => 'Never satisfied with the status quo', 'text' => 'We openly question the science, the technology and every step of a process to keep pushing the boundaries. Why not?' ),
				array( 'title' => 'Hard work and fun, hand in hand', 'text' => 'We love our work: creating winning solutions for our clients and a better life for our employees and partners.' ),
			), array( 'fields' => array( 'title' => az_f( 'text', 'Title', '' ), 'text' => az_f( 'textarea', 'Text', '' ) ), 'title' => '{{{ title }}}' ) ),
		),
	);
}

function az_render_values( $a ) {
	az_open( $a, 'az-values' );
	?>
	<div class="az-wrap">
		<?php echo az_head( $a ); // phpcs:ignore ?>
		<ul class="az-values__grid">
			<?php foreach ( (array) $a['items'] as $i => $v ) : ?>
				<li class="az-value" data-az-rise><span class="az-value__n az-label"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span><h3 class="az-value__t"><?php echo esc_html( $v['title'] ?? '' ); ?></h3><p class="az-value__p"><?php echo esc_html( $v['text'] ?? '' ); ?></p></li>
			<?php endforeach; ?>
		</ul>
	</div>
	</section>
	<?php
}

function az_schema_faq() {
	return array(
		'title'  => 'Questions',
		'icon'   => 'eicon-accordion',
		'fields' => az_head_fields( 'Questions', "Good questions.\n*Straight answers.*", '', 'white' ) + array(
			'items' => az_f( 'repeater', 'Questions', array(), array( 'fields' => array( 'q' => az_f( 'text', 'Question', '' ), 'a' => az_f( 'textarea', 'Answer', '' ) ), 'title' => '{{{ q }}}' ) ),
		),
	);
}

function az_render_faq( $a ) {
	$items = array_filter( (array) $a['items'], function ( $q ) {
		return ! empty( $q['q'] );
	} );
	if ( ! $items ) {
		return;
	}
	az_open( $a, 'az-faq' );
	?>
	<div class="az-wrap az-faq__grid">
		<?php echo az_head( $a ); // phpcs:ignore ?>
		<div class="az-faq__list">
			<?php foreach ( $items as $q ) : ?>
				<details class="az-qa" data-az-rise><summary class="az-qa__q"><span><?php echo esc_html( $q['q'] ); ?></span><?php echo az_icon( 'plus' ); // phpcs:ignore ?></summary><div class="az-qa__a"><?php echo az_paras( $q['a'] ?? '' ); // phpcs:ignore ?></div></details>
			<?php endforeach; ?>
		</div>
	</div>
	</section>
	<?php
}

function az_schema_prose() {
	return array(
		'title'  => 'Text',
		'icon'   => 'eicon-text',
		'fields' => az_head_fields( '', '', '', 'white' ) + array(
			'body' => az_f( 'textarea', 'Text (HTML allowed: headings, paragraphs, lists, links)', '', array( 'rows' => 16 ) ),
		),
	);
}

function az_render_prose( $a ) {
	az_open( $a, 'az-prosesec' );
	?>
	<div class="az-wrap az-wrap--narrow">
		<?php echo az_head( $a ); // phpcs:ignore ?>
		<div class="az-prose"><?php echo wp_kses_post( wpautop( (string) $a['body'] ) ); ?></div>
	</div>
	</section>
	<?php
}
