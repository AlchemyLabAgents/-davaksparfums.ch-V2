<?php
/**
 * Controller for rendering the block.
 * 
 * @since 2.0.0-beta.1
 *
 * @package SpectraPro\Blocks\LoopBuilderChildNoResults
 */

use Spectra\Helpers\BlockAttributes;
use SpectraPro\Queries\LoopBuilderQuery;

// Initialize query.
$query = LoopBuilderQuery::get_query( $block );

// Exit if no results are found.
if ( ! $query->have_posts() ) {
	return '';
}

// Style and class configurations.
$config = array(
	array( 'key' => 'textColor' ),
	array( 'key' => 'textColorHover' ),
	array( 'key' => 'backgroundColor' ),
	array( 'key' => 'backgroundColorHover' ),
	array( 'key' => 'backgroundGradient' ),
	array( 'key' => 'backgroundGradientHover' ),
);

// Additional classes.
$additional_classes = array( ! empty( $attributes['style']['elements']['link']['color']['text'] ) ? 'has-link-color' : '' );

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, array(), $additional_classes );

// Preload featured images if the block uses them to optimize performance.
if ( block_core_post_template_uses_featured_image( $block->inner_blocks ) ) {
	update_post_thumbnail_cache( $query );
}

// Initialize an empty string to build the content of the post items.
$content = '';
while ( $query->have_posts() ) {
	$query->the_post();
	$current_post_id   = get_the_ID();
	$current_post_type = get_post_type();

	// Prepare the block instance for rendering inner blocks by setting its blockName to 'core/null' to prevent support rendering.
	$block_instance              = $block->parsed_block;
	$block_instance['blockName'] = 'core/null'; // Prevent block support rendering.

	// Define a filter to provide the current post's context to inner blocks.
	$filter_context = static function ( $context ) use ( $current_post_id, $current_post_type ) {
		$context['postType'] = $current_post_type;
		$context['postId']   = $current_post_id;
		return $context;
	};  
	add_filter( 'render_block_context', $filter_context, 1 );

	// Render the inner blocks with the provided context.
	$block_content = ( new WP_Block( $block_instance ) )->render( array( 'dynamic' => false ) );

	// Remove the filter to avoid affecting other blocks.
	remove_filter( 'render_block_context', $filter_context, 1 );

	// Get the post classes and create a unique key for each list item.
	$post_classes = esc_attr( implode( ' ', get_post_class( 'spectra-block-post' ) ) );
	$directives   = ' data-wp-key="loop-template-' . $current_post_id . '"';

	// Append the rendered block content wrapped in a list item to the content string.
	$content .= sprintf( '<li%s class="%s">%s</li>', $directives, $post_classes, $block_content );
}//end while

// Reset the post data to restore the main query context, ensuring no interference with other parts of the site.
wp_reset_postdata();

// Prepare the data for the view.
$data = array(
	'wrapper_attributes' => $wrapper_attributes,
	'content'            => $content,
);

// Return the view.
return 'file:./view.php';
