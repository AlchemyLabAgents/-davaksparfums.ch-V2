<?php
/**
 * Controller for rendering the block.
 * 
 * @since 2.0.0-beta.1
 *
 * @package SpectraPro\Blocks\LoopBuilderChildPagination
 */

use Spectra\Helpers\BlockAttributes;
use SpectraPro\Helpers\LoopBuilder;
use SpectraPro\Queries\LoopBuilderQuery;

// Get query ID.
$query_id = LoopBuilderQuery::get_query_id( $block );

// Ensure that a valid context exists before proceeding.
if ( is_null( $query_id ) ) {
	return '';
}

// Initialize query.
$page_key     = LoopBuilderQuery::get_page_key( $query_id );
$current_page = LoopBuilderQuery::get_current_page( $query_id );
$query        = LoopBuilderQuery::get_query( $block );

// Exit if no posts are found.
if ( ! $query->have_posts() ) {
	return '';
}

// Get attributes.
$text_color                 = $attributes['textColor'] ?? '';
$text_color_hover           = $attributes['textColorHover'] ?? '';
$text_color_active          = $attributes['textColorActive'] ?? '';
$background_color           = $attributes['backgroundColor'] ?? '';
$background_color_hover     = $attributes['backgroundColorHover'] ?? '';
$background_color_active    = $attributes['backgroundColorActive'] ?? '';
$background_gradient        = $attributes['backgroundGradient'] ?? '';
$background_gradient_hover  = $attributes['backgroundGradientHover'] ?? '';
$background_gradient_active = $attributes['backgroundGradientActive'] ?? '';

$context = array(
	'spectra-pro/loop-builder-pagination/textColor'       => $text_color,
	'spectra-pro/loop-builder-pagination/textColorHover'  => $text_color_hover,
	'spectra-pro/loop-builder-pagination/textColorActive' => $text_color_active,
	'spectra-pro/loop-builder-pagination/backgroundColor' => $background_color,
	'spectra-pro/loop-builder-pagination/backgroundColorHover' => $background_color_hover,
	'spectra-pro/loop-builder-pagination/backgroundColorActive' => $background_color_active,
	'spectra-pro/loop-builder-pagination/backgroundGradient' => $background_gradient,
	'spectra-pro/loop-builder-pagination/backgroundGradientHover' => $background_gradient_hover,
	'spectra-pro/loop-builder-pagination/backgroundGradientActive' => $background_gradient_active,
);

// Get max number of pages.
$max_page = $query->max_num_pages;

// Categorize inner blocks (prev, page numbers, next).
$inner_blocks = [
	'prev' => null,
	'page' => null,
	'next' => null,
];

// Categorize inner blocks using switch-case.
foreach ( $block->parsed_block['innerBlocks'] as $inner_block ) {
	$inner_block['context'] = $context;

	switch ( $inner_block['blockName'] ?? '' ) {
		case 'spectra-pro/loop-builder-child-pagination-previous-button':
			$inner_blocks['prev'] = $inner_block;
			break;
		case 'spectra-pro/loop-builder-child-pagination-page-numbers-button':
			$inner_blocks['page'] = $inner_block;
			break;
		case 'spectra-pro/loop-builder-child-pagination-next-button':
			$inner_blocks['next'] = $inner_block;
			break;
	}
}

// Generate pagination links array.
// Get current URL with all query parameters to preserve filters, search, etc.
// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
$host        = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotValidated

$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $host . $request_uri;
$current_url = remove_query_arg( $page_key, $current_url ); // Remove existing page parameter to avoid conflicts.

$pagination_links = paginate_links(
	[
		'base'      => add_query_arg( $page_key, '%#%', $current_url ),
		'format'    => '',
		'current'   => $current_page,
		'total'     => $max_page,
		'prev_next' => true,
		'type'      => 'array',
	]
);

if ( empty( $pagination_links ) ) {
	return '';
}

// Prepare content.
$content = '';

foreach ( $pagination_links as $pagination_link ) {
	$processor = new WP_HTML_Tag_Processor( $pagination_link );
	if ( ! $processor->next_tag() ) {
		continue;
	}

	// Get the tag name of the processed element.
	$tag_name = $processor->get_tag();

	$args     = [];
	$page_num = 0;

	switch ( $tag_name ) {
		case 'A':
			$url = esc_url( $processor->get_attribute( 'href' ) );

			switch ( true ) {
				case $processor->has_class( 'prev' ) && $inner_blocks['prev']:
					$args     = [
						'url'     => $url,
						'wp_key'  => "query-pagination-prev-$query_id",
						'classes' => 'prev',
					];
					$content .= LoopBuilder::render_link_with_interactivity( $inner_blocks['prev'], $args );
					break;

				case $processor->has_class( 'next' ) && $inner_blocks['next']:
					$args     = [
						'url'     => $url,
						'wp_key'  => "query-pagination-next-$query_id",
						'classes' => 'next',
					];
					$content .= LoopBuilder::render_link_with_interactivity( $inner_blocks['next'], $args );
					break;

				default:
					preg_match( '/>(\d+)<\/a>/', $pagination_link, $matches );
					$page_num = $matches[1] ?? '';

					if ( $page_num && $inner_blocks['page'] ) {
						$args     = [
							'url'    => $url,
							'wp_key' => "$query_id-index-$page_num",
							'text'   => $page_num,
						];
						$content .= LoopBuilder::render_link_with_interactivity( $inner_blocks['page'], $args );
					}
					break;
			}//end switch
			break;

		case 'SPAN':
			switch ( true ) {
				case $processor->has_class( 'current' ) && $inner_blocks['page']:
					preg_match( '/<span[^>]*>(.*?)<\/span>/', $pagination_link, $matches );
					$page_num = $matches[1] ?? '';

					if ( $page_num ) {
						$args     = [
							'url'     => esc_url( add_query_arg( $page_key, $page_num, $current_url ) ),
							'wp_key'  => "$query_id-index-$page_num",
							'text'    => $page_num,
							'classes' => 'current',
						];
						$content .= LoopBuilder::render_link_with_interactivity( $inner_blocks['page'], $args );
					}
					break;

				case $processor->has_class( 'dots' ):
					$content .= $pagination_link;
					break;
			}
			break;
	}//end switch
}//end foreach

// Style and class configurations.
$config = array(
	array( 'key' => 'backgroundColorSecondary' ),
	array( 'key' => 'backgroundColorSecondaryHover' ),
	array( 'key' => 'backgroundGradientSecondary' ),
	array( 'key' => 'backgroundGradientSecondaryHover' ),
);

// Additional attributes.
$additional_attributes = array(
	'aria-label' => __( 'Pagination', 'spectra-pro' ),
);

// Class to handle astra theme button style compatibility.
$custom_classes = array( 'wp-block-button' );

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, $additional_attributes, $custom_classes );

// Prepare data for the view.
$data = array(
	'wrapper_attributes' => $wrapper_attributes,
	'content'            => $content,
);

// Return the view.
return 'file:./view.php';
