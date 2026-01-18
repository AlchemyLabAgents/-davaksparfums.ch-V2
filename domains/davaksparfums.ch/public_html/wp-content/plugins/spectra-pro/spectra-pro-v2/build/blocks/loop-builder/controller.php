<?php
/**
 * Controller for rendering the block.
 * 
 * @since 2.0.0-beta.1
 *
 * @package SpectraPro\Blocks\LoopBuilder
 */

use SpectraPro\Queries\LoopBuilderQuery;

$query_id = $attributes['queryId'] ?? null;

// If queryId is null, return an empty string.
if ( is_null( $query_id ) ) {
	return '';
}

$tag_name = $attributes['tagName'] ?? 'div';
$anchor   = $attributes['anchor'] ?? '';

// Get the filter value.
$tax_ids = LoopBuilderQuery::get_query_value( $query_id, 'filter' );

// Initialize cleaned variables.
$cleaned_filter  = '';
$cleaned_filters = array();

if ( is_array( $tax_ids ) && ! empty( $tax_ids ) ) {
	// Get the taxonomy type from the first key.
	$taxonomy_type = key( $tax_ids );
	$ids           = $tax_ids[ $taxonomy_type ];
	
	// Ensure $ids is an array.
	if ( ! is_array( $ids ) ) {
		$ids = [ $ids ];
	}
		
	// Map each ID to a cleaned filter string.
	$cleaned_filters = array_map(
		function ( $id ) use ( $taxonomy_type ) {
			return sprintf( '%s|%s', $taxonomy_type, $id );
		},
		$ids
	);

	$cleaned_filter = reset( $cleaned_filters );
}//end if

// Get the context values.
$loop_builder_contexts = array(
	'urlPrefix' => 'query-' . $attributes['queryId'],
	'loading'   => false,
	'filtering' => false,
	'searching' => false,
	'sorting'   => false,
	'sort'      => LoopBuilderQuery::get_query_value( $query_id, 'sort' ),
	'search'    => LoopBuilderQuery::get_query_value( $query_id, 'search' ),
	'filter'    => $cleaned_filter,
	'filters'   => $cleaned_filters,
);

// Get the wrapper attributes.
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'id' => $anchor,
	) 
);

// Prepare the data for the view.
$data = array(
	'wrapper_attributes'    => $wrapper_attributes,
	'loop_builder_contexts' => $loop_builder_contexts,
	'content'               => $content,
	'tag_name'              => $tag_name ?? 'div',
	'query_id'              => $query_id,
);

// Return the view.
return 'file:./view.php';
