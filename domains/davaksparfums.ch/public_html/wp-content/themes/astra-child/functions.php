<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue parent + child styles.
 */
add_action( 'wp_enqueue_scripts', function () {
	$parent_style = 'astra-theme-css';

	wp_enqueue_style(
		$parent_style,
		get_template_directory_uri() . '/style.css',
		[],
		wp_get_theme( 'astra' )->get( 'Version' )
	);

	wp_enqueue_style(
		'astra-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[ $parent_style ],
		filemtime( get_stylesheet_directory() . '/style.css' )
	);
}, 15 );

/**
 * Mirror sections helpers.
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_script(
		'astra-child-mirror-sections',
		get_stylesheet_directory_uri() . '/mirror-sections.js',
		[],
		filemtime( get_stylesheet_directory() . '/mirror-sections.js' ),
		true
	);
}, 20 );

/**
 * Davaks SEO/AEO Schema.org support.
 * Homepage uses native Spectra design with FAQ Schema markup.
 */

add_filter( 'wp_nav_menu_objects', function ( $items, $args ) {
	if ( empty( $args->theme_location ) ) {
		return $items;
	}

	$locations = [
		'primary',
		'mobile_menu',
		'ast-hf-menu-1',
		'ast-hf-menu-2',
		'ast-hf-mobile-menu',
	];
	if ( ! in_array( $args->theme_location, $locations, true ) ) {
		return $items;
	}

	$allowed = [ 'shop', 'exklusiv', 'damen', 'herren' ];
	$allowed_paths = [ '/shop/', '/exklusiv/', '/damen/', '/herren/' ];
	$filtered = [];

	foreach ( $items as $item ) {
		$title = wp_strip_all_tags( (string) $item->title );
		$title = function_exists( 'mb_strtolower' ) ? mb_strtolower( $title ) : strtolower( $title );
		$title = trim( $title );
		$path = '';
		if ( ! empty( $item->url ) ) {
			$path = (string) wp_parse_url( $item->url, PHP_URL_PATH );
			$path = trailingslashit( strtolower( $path ) );
		}

		if ( in_array( $title, $allowed, true ) || ( $path && in_array( $path, $allowed_paths, true ) ) ) {
			$filtered[] = $item;
		}
	}

	return $filtered;
}, 20, 2 );

/**
 * Force header menus to use the main menu ID and disable fallback page lists.
 */
add_filter( 'wp_nav_menu_args', function ( $args ) {
	if ( is_admin() || empty( $args['theme_location'] ) ) {
		return $args;
	}

	$header_locations = [
		'primary',
		'secondary_menu',
		'mobile_menu',
		'menu_1',
		'menu_2',
		'ast-hf-menu-1',
		'ast-hf-menu-2',
		'ast-hf-mobile-menu',
	];

	if ( in_array( $args['theme_location'], $header_locations, true ) ) {
		$args['menu'] = 19;
		$args['fallback_cb'] = false;
	}

	return $args;
}, 30 );

/**
 * Replace any page-menu fallback with the main menu.
 */
add_filter( 'wp_page_menu', function ( $menu, $args ) {
	$replacement = wp_nav_menu(
		[
			'menu'            => 19,
			'echo'            => false,
			'fallback_cb'     => false,
			'menu_id'         => 'primary-menu',
			'menu_class'      => 'main-header-menu ast-nav-menu',
			'container'       => 'nav',
			'container_class' => 'main-header-bar-navigation',
			'items_wrap'      => '<ul id="%1$s" class="%2$s">%3$s</ul>',
		]
	);

	return $replacement ?: $menu;
}, 20, 2 );
