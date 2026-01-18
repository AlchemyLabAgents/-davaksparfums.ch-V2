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

// Exit if queryId is null or content are empty.
if ( is_null( LoopBuilderQuery::get_query_id( $block ) ) || empty( trim( $content ) ) ) {
	return '';
}

// Initialize query.
$query = LoopBuilderQuery::get_query( $block );

// Exit if no results are found.
if ( $query->post_count > 0 ) {
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

// Add the additional classes.
$additional_classes = array( ! empty( $attributes['style']['elements']['link']['color']['text'] ) ? 'has-link-color' : '' );

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, array(), $additional_classes );

// Prepare the data for the view.
$data = array(
	'wrapper_attributes' => $wrapper_attributes,
	'content'            => $content,
);

// Return the view.
return 'file:./view.php';
