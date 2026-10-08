<?php
/**
 * Watch Audazzio in action: a rail of films. Each card plays in a lightbox, from YouTube or from an MP4 in
 * the Media Library. Today's films are placeholders for the new ones.
 */

defined( 'ABSPATH' ) || exit;

function az_videos_default() {
	$out = array();
	foreach ( (array) az_data( 'videos' ) as $v ) {
		$out[] = array(
			'title'  => $v['title'] ?? '',
			'label'  => $v['label'] ?? '',
			'video'  => array( 'url' => $v['url'] ?? '' ),
			'poster' => isset( $v['poster'] ) ? az_ship( 'img/' . $v['poster'] ) : array( 'url' => '' ),
			'length' => $v['length'] ?? '',
		);
	}
	return $out;
}

function az_schema_videos() {
	return array(
		'title'  => 'Films',
		'icon'   => 'eicon-video-playlist',
		'note'   => 'Each film can be a YouTube link or the address of an MP4 uploaded to the Media Library. The poster is the picture shown before it plays.',
		'fields' => az_head_fields( 'Watch Audazzio in action', "See it work\n*in the moment.*", '', 'white' ) + array(
			'items' => az_f( 'repeater', 'Films', az_videos_default(), array( 'fields' => array(
				'title'  => az_f( 'text', 'Title', '' ),
				'label'  => az_f( 'text', 'Small label', '' ),
				'video'  => az_f( 'url', 'Film (YouTube link or MP4 address)', '' ),
				'poster' => az_f( 'media', 'Poster', '' ),
				'length' => az_f( 'text', 'Length (1:30)', '' ),
			), 'title' => '{{{ title }}}' ) ),
			'layout' => az_f( 'select', 'Layout', 'feature', array( 'options' => array( 'feature' => 'First film large, the rest beside it', 'rail' => 'One row that scrolls' ) ) ),
		),
	);
}

/** The data a card hands to the lightbox. */
function az_video_attrs( $video ) {
	$url = is_array( $video ) ? ( $video['url'] ?? '' ) : (string) $video;
	$yt  = az_youtube_id( $url );
	if ( $yt ) {
		return 'data-az-video="youtube" data-az-id="' . esc_attr( $yt ) . '"';
	}
	$src = az_media( $url );
	return $src ? 'data-az-video="file" data-az-src="' . esc_url( $src ) . '"' : '';
}

function az_render_videos( $a ) {
	if ( empty( $a['anchor'] ) ) {
		$a['anchor'] = 'watch';
	}
	$items  = (array) $a['items'];
	$layout = sanitize_key( $a['layout'] ?: 'feature' );
	az_open( $a, 'az-videos az-videos--' . $layout );
	?>
	<div class="az-wrap">
		<?php echo az_head( $a ); // phpcs:ignore ?>
		<ul class="az-films">
			<?php foreach ( $items as $i => $v ) : $poster = az_media( $v['poster'] ?? '' ); $attrs = az_video_attrs( $v['video'] ?? '' ); ?>
				<li class="az-film<?php echo 0 === $i ? ' az-film--lead' : ''; ?>" data-az-rise>
					<button class="az-film__b" type="button" <?php echo $attrs; // phpcs:ignore ?> data-az-title="<?php echo esc_attr( $v['title'] ?? '' ); ?>">
						<span class="az-film__pic"><?php if ( $poster ) : ?><img src="<?php echo esc_url( $poster ); ?>" alt="" loading="lazy" decoding="async"><?php endif; ?><span class="az-film__play" aria-hidden="true"><?php echo az_icon( 'play' ); // phpcs:ignore ?></span><?php if ( ! empty( $v['length'] ) ) : ?><span class="az-film__len az-label"><?php echo esc_html( $v['length'] ); ?></span><?php endif; ?></span>
						<span class="az-film__meta"><span class="az-label"><?php echo esc_html( $v['label'] ?? '' ); ?></span><span class="az-film__t"><?php echo esc_html( $v['title'] ?? '' ); ?></span></span>
					</button>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	</section>
	<?php
}
