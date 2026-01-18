<?php
/**
 * Controller for rendering the block.
 * 
 * @since 2.0.0-beta.1
 *
 * @package SpectraPro\Blocks\LoopBuilderChildSort
 */

use Spectra\Helpers\BlockAttributes;
use SpectraPro\Helpers\BlockStyleManager;
use SpectraPro\Queries\LoopBuilderQuery;

// Get query ID.
$query_id = LoopBuilderQuery::get_query_id( $block );

// If queryId is null, return an empty string.
if ( is_null( $query_id ) ) {
	return '';
}

// Get sorting options.
$sorting_options = $attributes['sortList'] ?? array();

// If sorting options are not set, return an empty string.
if ( empty( $sorting_options ) ) {
	return '';
}

// Get sort parameters from LoopBuilderQuery.
$sort_key   = LoopBuilderQuery::get_query_key( $query_id, 'sort' ); 
$sort_value = LoopBuilderQuery::get_query_value( $query_id, 'sort' );

// Get the placeholder.
$placeholder = $attributes['placeholder'] ?? '';

// Extract style attributes for border, spacing, shadow, and typography.
$style_attributes = BlockStyleManager::get_attributes( $attributes, array( 'border', 'spacing', 'shadow', 'typography' ) );

// Define default sorting options with their labels.
$default_sort_options = array(
	'post_title|desc'    => __( 'Sort by title (Z-A)', 'spectra-pro' ),
	'post_title|asc'     => __( 'Sort by title (A-Z)', 'spectra-pro' ),
	'post_date|desc'     => __( 'Sort by newest', 'spectra-pro' ),
	'post_date|asc'      => __( 'Sort by oldest', 'spectra-pro' ),
	'post_id|desc'       => __( 'Post ID descending', 'spectra-pro' ),
	'post_id|asc'        => __( 'Post ID ascending', 'spectra-pro' ),
	'post_modified|desc' => __( 'Modified last', 'spectra-pro' ),
	'post_modified|asc'  => __( 'Modified recently', 'spectra-pro' ),
	'post_author|desc'   => __( 'Sort by author (Z-A)', 'spectra-pro' ),
	'post_author|asc'    => __( 'Sort by author (A-Z)', 'spectra-pro' ),
);

// Build HTML for sort options dropdown.
$sort_options_html = '';

// Only add placeholder option if user has set a placeholder.
if ( ! empty( $placeholder ) ) {
	$sort_options_html .= '<option value="" disabled' . ( empty( $sort_value ) ? ' selected' : '' ) . '>' . esc_html( $placeholder ) . '</option>';
}

// Add Default option.
$sort_options_html .= '<option value=""' . ( empty( $sort_value ) ? ' selected' : '' ) . '>' . esc_html__( 'Default', 'spectra-pro' ) . '</option>';

foreach ( $default_sort_options as $value => $label ) {

	// Skip if value is not in sorting options.
	if ( ! in_array( $value, $sorting_options, true ) ) {
		continue;
	}
	
	// Check if value is selected.
	$selected = selected( $sort_value, $value, false );

	// Add option to HTML.
	$sort_options_html .= "<option value='" . esc_attr( $value ) . "' $selected>" . esc_html( $label ) . '</option>';
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

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config );

// Prepare data for the view.
$data = array(
	'wrapper_attributes' => $wrapper_attributes,
	'sort_key'           => $sort_key,
	'sort_options_html'  => $sort_options_html,
	'style'              => $style_attributes['style'],
	'class'              => $style_attributes['class'],
);

// Return the view.
return 'file:./view.php';
