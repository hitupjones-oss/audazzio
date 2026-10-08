<?php
/**
 * Who it is for: the four doors on the home page, and one detailed section per audience on Solutions
 * (broadcasters, teams and leagues, sponsors and brands, and everything beyond sport).
 */

defined( 'ABSPATH' ) || exit;

function az_schema_solutions() {
	return array(
		'title'  => 'Who it is for (cards)',
		'icon'   => 'eicon-call-to-action',
		'fields' => az_head_fields( 'Built for', "One signal.\n*Every kind of audience.*", '', 'white' ) + array(
			'items' => az_f( 'repeater', 'Cards', array(
				array( 'title' => 'Broadcasters', 'text' => 'Facts, replays and exclusive content on fans’ phones, plus new inventory to sell.', 'url' => array( 'url' => '/solutions/#broadcasters' ), 'icon' => 'tv', 'image' => az_ship( 'img/sol-broadcasters.jpg' ) ),
				array( 'title' => 'Teams and leagues', 'text' => 'Reach fans at home and in the stadium, and grow your official app audience.', 'url' => array( 'url' => '/solutions/#teams' ), 'icon' => 'stadium', 'image' => az_ship( 'img/sol-teams.jpg' ) ),
				array( 'title' => 'Sponsors and brands', 'text' => 'More exposure, deeper engagement and direct response in under a second.', 'url' => array( 'url' => '/solutions/#sponsors' ), 'icon' => 'spark', 'image' => az_ship( 'img/sol-sponsors.jpg' ) ),
				array( 'title' => 'Beyond sport', 'text' => 'Concerts, studio shows, corporate meetings, casinos and theme parks.', 'url' => array( 'url' => '/solutions/#beyond' ), 'icon' => 'users', 'image' => az_ship( 'img/sol-beyond.jpg' ) ),
			), array( 'fields' => array(
				'title' => az_f( 'text', 'Title', '' ),
				'text'  => az_f( 'textarea', 'Text', '' ),
				'url'   => az_f( 'url', 'Link', '' ),
				'icon'  => az_f( 'select', 'Icon', 'tv', array( 'options' => az_icon_options() ) ),
				'image' => az_f( 'media', 'Picture', '' ),
			), 'title' => '{{{ title }}}' ) ),
		),
	);
}

function az_render_solutions( $a ) {
	if ( empty( $a['anchor'] ) ) {
		$a['anchor'] = 'for';
	}
	az_open( $a, 'az-sols' );
	?>
	<div class="az-wrap">
		<?php echo az_head( $a ); // phpcs:ignore ?>
		<ul class="az-sols__grid">
			<?php foreach ( (array) $a['items'] as $it ) : $img = az_media( $it['image'] ?? '' ); ?>
				<li class="az-sol" data-az-rise>
					<a class="az-sol__a" href="<?php echo esc_url( az_url( $it['url'] ?? '' ) ); ?>">
						<span class="az-sol__pic"><?php if ( $img ) : ?><img src="<?php echo esc_url( $img ); ?>" alt="" loading="lazy" decoding="async"><?php endif; ?></span>
						<span class="az-sol__body">
							<span class="az-sol__ico"><?php echo az_icon( sanitize_key( $it['icon'] ?? 'tv' ) ); // phpcs:ignore ?></span>
							<span class="az-sol__t"><?php echo esc_html( $it['title'] ?? '' ); ?></span>
							<span class="az-sol__p"><?php echo esc_html( $it['text'] ?? '' ); ?></span>
							<span class="az-sol__go"><?php echo az_icon( 'arrow' ); // phpcs:ignore ?></span>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	</section>
	<?php
}

function az_schema_solution() {
	return array(
		'title'  => 'Audience detail',
		'icon'   => 'eicon-info-box',
		'fields' => az_head_fields( '', '', '', 'white' ) + array(
			'features' => az_f( 'repeater', 'What it does (grouped)', array(), array( 'fields' => array(
				'group' => az_f( 'text', 'Group', '' ),
				'title' => az_f( 'text', 'Title', '' ),
				'text'  => az_f( 'textarea', 'Text', '' ),
				'icon'  => az_f( 'select', 'Icon', 'spark', array( 'options' => az_icon_options() ) ),
			), 'title' => '{{{ group }}}: {{{ title }}}' ) ),
			'uses'     => az_f( 'textarea', 'Use cases (one per line: Title | idea; idea; idea)', '', array( 'rows' => 8 ) ),
			'gallery'  => az_f( 'repeater', 'Screens', array(), array( 'fields' => array( 'image' => az_f( 'media', 'Picture', '' ), 'caption' => az_f( 'text', 'Caption', '' ) ), 'title' => '{{{ caption }}}' ) ),
			'shape'    => az_f( 'select', 'Screens are', 'phone', array( 'options' => array( 'phone' => 'Phone screens (tall)', 'wide' => 'Wide pictures' ) ) ),
			'cta'      => az_f( 'text', 'Button', '' ),
			'cta_url'  => az_f( 'url', 'Button link', '/join/' ),
		),
	);
}

function az_render_solution( $a ) {
	$groups = array();
	foreach ( (array) $a['features'] as $f ) {
		$g              = trim( (string) ( $f['group'] ?? '' ) );
		$groups[ $g ][] = $f;
	}
	$gallery = array_values( array_filter( (array) $a['gallery'], function ( $g ) {
		return '' !== az_media( $g['image'] ?? '' );
	} ) );
	$uses    = az_pairs( $a['uses'] );
	az_open( $a, 'az-solution' );
	?>
	<div class="az-wrap">
		<?php echo az_head( $a ); // phpcs:ignore ?>
		<?php if ( $gallery ) : ?>
			<div class="az-shots az-shots--<?php echo esc_attr( sanitize_key( $a['shape'] ) ); ?>" data-az-rail>
				<ul class="az-rail__track">
					<?php foreach ( $gallery as $g ) : ?>
						<li class="az-shot" data-az-rise><img src="<?php echo esc_url( az_media( $g['image'] ) ); ?>" alt="<?php echo esc_attr( $g['caption'] ?? '' ); ?>" loading="lazy" decoding="async"><?php if ( ! empty( $g['caption'] ) ) : ?><span class="az-shot__cap az-label"><?php echo esc_html( $g['caption'] ); ?></span><?php endif; ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
		<?php if ( $groups ) : ?>
			<div class="az-feats">
				<?php foreach ( $groups as $g => $items ) : ?>
					<div class="az-feats__g" data-az-rise>
						<?php if ( '' !== $g ) : ?><h3 class="az-feats__h az-label"><?php echo esc_html( $g ); ?></h3><?php endif; ?>
						<ul class="az-feats__list">
							<?php foreach ( $items as $f ) : ?>
								<li class="az-feat"><span class="az-feat__ico"><?php echo az_icon( sanitize_key( $f['icon'] ?? 'spark' ) ); // phpcs:ignore ?></span><span><b class="az-feat__t"><?php echo esc_html( $f['title'] ?? '' ); ?></b><span class="az-feat__p"><?php echo esc_html( $f['text'] ?? '' ); ?></span></span></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php if ( $uses ) : ?>
			<ul class="az-uses">
				<?php foreach ( $uses as $u ) : ?>
					<li class="az-use" data-az-rise><h3 class="az-use__t"><?php echo esc_html( $u[0] ); ?></h3><ul class="az-chips"><?php foreach ( array_filter( array_map( 'trim', explode( ';', $u[1] ) ) ) as $chip ) : ?><li><?php echo esc_html( $chip ); ?></li><?php endforeach; ?></ul></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php if ( $a['cta'] ) : ?><p class="az-solution__cta" data-az-rise><?php echo az_btn( $a['cta'], $a['cta_url'], 'wave' ); // phpcs:ignore ?></p><?php endif; ?>
	</div>
	</section>
	<?php
}
