<?php
/**
 * The header: the Audazzio mark, four links, Try it and Join the Wave. On a phone the links fold into a
 * full-screen menu. The bar turns frosted once the page scrolls.
 */

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#ffffff">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="az-skip" href="#az-main">Skip to content</a>
<header class="az-nav" data-az-nav>
	<div class="az-nav__bar">
		<a class="az-nav__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Audazzio, home">
			<span class="az-nav__sym" aria-hidden="true"><?php echo az_theme_mark( 'symbol' ); // phpcs:ignore -- inline SVG ?></span>
			<span class="az-nav__type" aria-hidden="true"><?php echo az_theme_mark( 'logotype' ); // phpcs:ignore -- inline SVG ?></span>
		</a>
		<nav class="az-nav__links" aria-label="Main">
			<?php az_theme_links( 'primary', 'az-nav__link' ); ?>
		</nav>
		<div class="az-nav__end">
			<a class="az-nav__try" href="<?php echo esc_url( home_url( '/try/' ) ); ?>" data-az-try><span class="az-pulse" aria-hidden="true"></span><span>Try it</span></a>
			<a class="az-btn az-btn--wave az-btn--sm az-nav__join" href="<?php echo esc_url( home_url( '/join/' ) ); ?>" data-az-join>Join the Wave</a>
			<button class="az-nav__burger" type="button" aria-expanded="false" aria-controls="az-menu" aria-label="Open the menu"><i></i><i></i></button>
		</div>
	</div>
	<div class="az-menu" id="az-menu" hidden>
		<nav class="az-menu__links" aria-label="Menu">
			<?php az_theme_links( 'primary', 'az-menu__link' ); ?>
			<a class="az-menu__link" href="<?php echo esc_url( home_url( '/try/' ) ); ?>" data-az-try>Try Audazzio</a>
		</nav>
		<a class="az-btn az-btn--wave az-btn--lg az-menu__join" href="<?php echo esc_url( home_url( '/join/' ) ); ?>" data-az-join>Join the Wave</a>
		<p class="az-menu__line az-label">It comes in waves.</p>
	</div>
</header>
<main id="az-main" class="az-main">
