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
 * Davaks subtle effects.
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_script(
		'davaks-effects',
		get_stylesheet_directory_uri() . '/davaks-effects.js',
		[],
		filemtime( get_stylesheet_directory() . '/davaks-effects.js' ),
		true
	);
}, 25 );

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

	$right_locations = [
		'secondary_menu',
		'menu_2',
		'ast-hf-menu-2',
	];

	if ( in_array( $args->theme_location, $right_locations, true ) ) {
		return [];
	}

	$locations = [
		'primary',
		'mobile_menu',
		'menu_1',
		'ast-hf-menu-1',
		'ast-hf-mobile-menu',
	];
	if ( ! in_array( $args->theme_location, $locations, true ) ) {
		return $items;
	}

	$allowed = [ 'shop', 'laden', 'exklusiv', 'damen', 'herren', 'unisex' ];
	$allowed_paths = [ '/shop/', '/laden/', '/exklusiv/', '/damen/', '/herren/', '/unisex/', '/product-category/unisex/' ];
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

		if ( in_array( $path, [ '/shop/', '/laden/' ], true ) ) {
			$item->title = 'Laden';
			$item->url = home_url( '/laden/' );
			$title = 'laden';
			$path = '/laden/';
		}

		if ( in_array( $title, $allowed, true ) || ( $path && in_array( $path, $allowed_paths, true ) ) ) {
			$filtered[] = $item;
		}
	}

	return $filtered;
}, 20, 2 );

/**
 * Force menu label and URL for shop items, even without theme_location.
 */
add_filter( 'nav_menu_item_title', function ( $title, $item ) {
	$path = '';
	if ( ! empty( $item->url ) ) {
		$path = (string) wp_parse_url( $item->url, PHP_URL_PATH );
		$path = trailingslashit( strtolower( $path ) );
	}

	if ( in_array( $path, [ '/shop/', '/laden/' ], true ) ) {
		return 'Laden';
	}

	return $title;
}, 20, 2 );

add_filter( 'wp_nav_menu_items', function ( $items ) {
	if ( false !== stripos( $items, '>Shop<' ) ) {
		$items = str_ireplace( '>Shop<', '>Laden<', $items );
	}
	return $items;
}, 30 );

/**
 * Force header menus to use the main menu ID and disable fallback page lists.
 */
add_filter( 'wp_nav_menu_args', function ( $args ) {
	if ( is_admin() || empty( $args['theme_location'] ) ) {
		return $args;
	}

	$right_locations = [
		'secondary_menu',
		'menu_2',
		'ast-hf-menu-2',
	];

	if ( in_array( $args['theme_location'], $right_locations, true ) ) {
		$args['menu'] = 0;
		$args['fallback_cb'] = false;
		$args['container'] = false;
		$args['items_wrap'] = '';
		$args['echo'] = false;
		return $args;
	}

	$header_locations = [
		'primary',
		'mobile_menu',
		'menu_1',
		'ast-hf-menu-1',
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

/**
 * Disable Astra header menu-2 output globally.
 */
add_action( 'after_setup_theme', function () {
	if ( class_exists( 'Astra_Builder_Header' ) ) {
		remove_action( 'astra_header_menu_2', [ Astra_Builder_Header::get_instance(), 'menu_2' ] );
	}
}, 20 );

/**
 * Replace Astra footer with custom footer.
 */
add_action( 'after_setup_theme', function () {
	remove_action( 'astra_footer', 'astra_footer_markup' );
	if ( class_exists( 'Astra_Builder_Footer' ) ) {
		remove_action( 'astra_footer', [ Astra_Builder_Footer::get_instance(), 'footer_markup' ], 10 );
	}
}, 30 );

add_action( 'astra_footer', function () {
	if ( is_admin() ) {
		return;
	}
	$footer_path = get_stylesheet_directory() . '/template-parts/footer-custom.php';
	if ( file_exists( $footer_path ) ) {
		include $footer_path;
	}
}, 5 );

/**
 * Remove legacy inline custom footer from page content.
 */
add_filter( 'the_content', function ( $content ) {
	if ( ! is_string( $content ) ) {
		return $content;
	}

	return preg_replace( '#<footer[^>]*class=["\"]davaks-footer["\"][\s\S]*?</footer>#i', '', $content );
}, 20 );

/**
 * Redirect legacy English slugs to German slugs.
 */
add_action( 'template_redirect', function () {
	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}

	$request = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path    = strtolower( strtok( $request, '?' ) );
	$path    = trailingslashit( $path );

	$map = [
		'/product-category/women/'     => '/product-category/damen/',
		'/product-category/men/'       => '/product-category/herren/',
		'/product-category/exclusive/' => '/product-category/exklusiv/',
		'/shop/'                       => '/laden/',
		'/cart/'                       => '/warenkorb/',
		'/checkout/'                   => '/kasse/',
		'/my-account/'                 => '/mein-konto/',
		'/privacy-policy/'             => '/datenschutz/',
		'/refund_returns/'             => '/rueckerstattung-rueckgabe/',
		'/refund-returns-policy/'      => '/rueckerstattung-rueckgabe/',
	];

	if ( isset( $map[ $path ] ) ) {
		wp_redirect( home_url( $map[ $path ] ), 301 );
		exit;
	}
}, 1 );

/**
 * Hide the default Shop page title section.
 */
add_filter( 'woocommerce_show_page_title', function ( $show ) {
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return false;
	}
	return $show;
}, 20 );

/**
 * Force German shop title.
 */
add_filter( 'woocommerce_page_title', function ( $title ) {
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return 'Laden';
	}
	return $title;
}, 20 );

/**
 * Disable Astra page header on the shop archive.
 */
add_filter( 'astra_page_header_enabled', function ( $enabled ) {
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return false;
	}
	return $enabled;
}, 20 );

/**
 * Replace footer copyright text.
 */
add_action( 'after_setup_theme', function () {
	if ( class_exists( 'Astra_Builder_Footer' ) ) {
		remove_action( 'astra_footer_copyright', [ Astra_Builder_Footer::get_instance(), 'footer_copyright' ], 10 );
	}
}, 30 );

add_action( 'astra_footer_copyright', function () {
	echo '<span class="davaks-footer-copyright">' . esc_html( '© 2026 Davaks Parfums · Luxus‑parfums in der Schweiz' ) . '</span>';
}, 40 );

/**
 * Force custom templates for specific product categories.
 */





/**
 * FORCE TEMPLATES via template_redirect
 * Corrects issue where Astra/WooCommerce ignores child theme taxonomy templates.
 */
add_action( 'template_redirect', function() {
    if ( is_product_category() ) {
        $term = get_queried_object();
        if ( $term && in_array( $term->slug, [ 'herren', 'damen', 'unisex', 'exklusiv' ] ) ) {
            $template_path = get_stylesheet_directory() . '/taxonomy-product_cat-' . $term->slug . '.php';
            if ( file_exists( $template_path ) ) {
                include $template_path;
                exit;
            }
        }
    }
} );





