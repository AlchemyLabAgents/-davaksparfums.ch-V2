<?php
/**
 * Controller for rendering the block.
 * 
 * @since 2.0.0-beta.1
 *
 * @package SpectraPro\Blocks\LoopBuilderChildFilter
 */

use SpectraPro\Queries\LoopBuilderQuery;

// Get query ID.
$query_id = LoopBuilderQuery::get_query_id( $block );

// Exit if queryId is null or innerBlocks is empty.
if ( is_null( $query_id ) || empty( $block->parsed_block['innerBlocks'] ) ) {
	return '';
}

// Get filter type attribute.
$filter_type = $attributes['filterType'] ?? 'select';

// Allowed filter types.
$allowed_filter_types = array( 'select', 'checkbox', 'button' );

// Exit if filter type is not allowed.
if ( ! in_array( $filter_type, $allowed_filter_types, true ) ) {
	return '';
}

// Get block attributes.
$show_post_count      = $attributes['showPostCount'] ?? true;
$show_children        = $attributes['showChildren'] ?? false;
$show_empty_taxonomy  = $attributes['showEmptyTaxonomy'] ?? false;
$taxonomy_type        = $attributes['taxonomyType'] ?? 'category';
$cat_taxonomy_include = $attributes['catTaxonomyInclude'] ?? array();
$tag_taxonomy_include = $attributes['tagTaxonomyInclude'] ?? array();
$cat_taxonomy_exclude = $attributes['catTaxonomyExclude'] ?? array();
$tag_taxonomy_exclude = $attributes['tagTaxonomyExclude'] ?? array();
$filter_mode          = $attributes['filterMode'] ?? 'include';

// Set include and exclude arrays based on taxonomy type.
$include = 'post_tag' === $taxonomy_type ? $tag_taxonomy_include : ( 'category' === $taxonomy_type ? $cat_taxonomy_include : array() );
$exclude = 'post_tag' === $taxonomy_type ? $tag_taxonomy_exclude : ( 'category' === $taxonomy_type ? $cat_taxonomy_exclude : array() );

// Get cached data for terms.
$terms = LoopBuilderQuery::get_terms_with_cache( $taxonomy_type, $filter_mode, $include, $exclude, $show_empty_taxonomy, $show_children );

if ( empty( $terms ) ) {
	return '';
}

// Generate dynamic legend text based on taxonomy type.
$taxonomy_obj = get_taxonomy( $taxonomy_type );
$aria_label   = $taxonomy_obj && ! empty( $taxonomy_obj->labels->singular_name ) ?
	sprintf(
		/* translators: %s is the singular name of the taxonomy */
		__( 'Filter by %s', 'spectra-pro' ),
		$taxonomy_obj->labels->singular_name
	) :
	esc_html__( 'Filter by Terms', 'spectra-pro' );

// Class to handle astra theme button style compatibility.
$custom_classes = array( 'wp-block-button' );

// Prepare wrapper attributes.
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'aria-label' => $aria_label,
		'class'      => implode( ' ', $custom_classes ),
	),
);

// Get the selected taxonomy terms.
$filter_key   = LoopBuilderQuery::get_query_key( $query_id, 'filter' );
$filter_value = LoopBuilderQuery::get_query_value( $query_id, 'filter' );
$tax_ids      = $filter_value ?? array();

// Categorize inner blocks (prev, page numbers, next).
$inner_blocks = array(
	'select'   => null,
	'checkbox' => null,
	'button'   => null,
);

foreach ( $block->parsed_block['innerBlocks'] as $inner_block ) {
	$button_type = $inner_block['blockName'] ?? '';
	switch ( $button_type ) {
		case 'spectra-pro/loop-builder-child-filter-button':
			$inner_blocks['button'] = $inner_block;
			break;
		case 'spectra-pro/loop-builder-child-filter-select':
			$inner_blocks['select'] = $inner_block;
			break;
		case 'spectra-pro/loop-builder-child-filter-checkbox':
			$inner_blocks['checkbox'] = $inner_block;
			break;
	}
}

// Prepare the data for the view.
$data = array(
	'wrapper_attributes' => $wrapper_attributes,
	'content'            => $content,
);

// Return the view.
return 'file:./view.php';
