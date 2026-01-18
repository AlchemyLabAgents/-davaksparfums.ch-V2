<?php
/**
 * Controller for rendering the block.
 * 
 * @since 2.0.0-beta.1
 *
 * @package SpectraPro\Blocks\LoopBuilderChildNoResults
 */

use Spectra\Helpers\BlockAttributes;
use SpectraPro\Helpers\BlockStyleManager;
use SpectraPro\Queries\LoopBuilderQuery;

// Get query ID.
$query_id = LoopBuilderQuery::get_query_id( $block );

// Exit if queryId is null.
if ( is_null( $query_id ) ) {
	return '';
}

// Get search key and value.
$search_key   = LoopBuilderQuery::get_query_key( $query_id, 'search' ); 
$search_value = LoopBuilderQuery::get_query_value( $query_id, 'search' );

// Get the placeholder.
$placeholder = $attributes['placeholder'] ?? '';

// Get style attributes.
$style_attributes = BlockStyleManager::get_attributes( $attributes, array( 'border', 'spacing', 'shadow', 'typography' ) );

// Style and class configurations.
$config = array(
	array( 'key' => 'textColor' ),
	array( 'key' => 'textColorFocus' ),
	array( 'key' => 'textColorSecondary' ),
	array( 'key' => 'textColorFocusSecondary' ),
	array( 'key' => 'backgroundColor' ),
	array( 'key' => 'backgroundColorFocus' ),
	array( 'key' => 'backgroundGradient' ),
	array( 'key' => 'backgroundGradientFocus' ),
);

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config );

// Prepare the data for the view.
$data = array(
	'wrapper_attributes' => $wrapper_attributes,
	'search_placeholder' => $placeholder ? $placeholder : __( 'Search', 'spectra-pro' ),
	'query_id'           => $query_id,
	'style'              => $style_attributes['style'],
	'class'              => $style_attributes['class'],
);

// Return the view.
return 'file:./view.php';
