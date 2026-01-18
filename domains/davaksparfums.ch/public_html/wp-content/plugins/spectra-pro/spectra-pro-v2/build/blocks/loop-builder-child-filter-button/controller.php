<?php
/**
 * View for rendering the block.
 * 
 * @since 2.0.0-beta.1
 *
 * @package SpectraPro\Blocks\LoopBuilderChildFilterButton
 */

use Spectra\Helpers\BlockAttributes;
use SpectraPro\Helpers\LoopBuilder;
use SpectraPro\Queries\LoopBuilderQuery;

// Get query ID.
$query_id = LoopBuilderQuery::get_query_id( $block );

// Exit if queryId is null, or taxonomyType and filterType are not set or filterType is not equal to button.
if ( is_null( $query_id ) || ! isset(
	$block->context['spectra-pro/loop-builder-child-filter/filterType'],
	$block->context['spectra-pro/loop-builder-child-filter/taxonomyType'] 
) || 'button' !== $block->context['spectra-pro/loop-builder-child-filter/filterType'] ) {
	return '';
}

// Get sorting options.
$show_post_count      = $block->context['spectra-pro/loop-builder-child-filter/showPostCount'] ?? true;
$show_children        = $block->context['spectra-pro/loop-builder-child-filter/showChildren'] ?? false;
$show_empty_taxonomy  = $block->context['spectra-pro/loop-builder-child-filter/showEmptyTaxonomy'] ?? false;
$taxonomy_type        = $block->context['spectra-pro/loop-builder-child-filter/taxonomyType'];
$cat_taxonomy_include = $block->context['spectra-pro/loop-builder-child-filter/catTaxonomyInclude'] ?? array();
$tag_taxonomy_include = $block->context['spectra-pro/loop-builder-child-filter/tagTaxonomyInclude'] ?? array();
$cat_taxonomy_exclude = $block->context['spectra-pro/loop-builder-child-filter/catTaxonomyExclude'] ?? array();
$tag_taxonomy_exclude = $block->context['spectra-pro/loop-builder-child-filter/tagTaxonomyExclude'] ?? array();
$filter_mode          = $block->context['spectra-pro/loop-builder-child-filter/filterMode'] ?? 'include';

// Set include and exclude arrays based on taxonomy type.
$include = 'post_tag' === $taxonomy_type ? $tag_taxonomy_include : ( 'category' === $taxonomy_type ? $cat_taxonomy_include : array() );
$exclude = 'post_tag' === $taxonomy_type ? $tag_taxonomy_exclude : ( 'category' === $taxonomy_type ? $cat_taxonomy_exclude : array() );

// Get cached data for terms.
$terms = LoopBuilderQuery::get_terms_with_cache( $taxonomy_type, $filter_mode, $include, $exclude, $show_empty_taxonomy, $show_children );

if ( empty( $terms ) ) {
	return '';
}

// Style and class configurations.
$config = array(
	array( 'key' => 'textColor' ),
	array( 'key' => 'textColorActive' ),
	array( 'key' => 'textColorHover' ),
	array( 'key' => 'backgroundColor' ),
	array( 'key' => 'backgroundColorActive' ),
	array( 'key' => 'backgroundColorHover' ),
	array( 'key' => 'backgroundGradient' ),
	array( 'key' => 'backgroundGradientActive' ),
	array( 'key' => 'backgroundGradientHover' ),
);

// Extract text alignment and add appropriate class
$text_align = $attributes['responsiveControls']['lg']['style']['typography']['textAlign'] ?? '';

// Remove editor-only classes from className attribute before processing.
if ( ! empty( $attributes['className'] ) ) {
	$editor_only_classes = array( 'editor-active' );
	$class_array = array_filter(
		explode( ' ', $attributes['className'] ),
		function( $class ) use ( $editor_only_classes ) {
			return ! in_array( trim( $class ), $editor_only_classes, true );
		}
	);
	$attributes['className'] = implode( ' ', $class_array );
}

// Additional classes.
$additional_classes = array( 'wp-block-button', 'wp-block-button__link wp-element-button' );

// Add text alignment class if text alignment is set
if ( ! empty( $text_align ) ) {
	$additional_classes[] = "has-text-align-{$text_align}";
}

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, array(), $additional_classes );

// Get selected taxonomy terms.
$filter_key   = LoopBuilderQuery::get_query_key( $query_id, 'filter' );
$filter_value = LoopBuilderQuery::get_query_value( $query_id, 'filter' );
$tax_data     = $filter_value ?? array();
$tax_ids      = isset( $tax_data[ $taxonomy_type ] ) ? $tax_data[ $taxonomy_type ] : array();

// Prepare term data with URLs and active states.
$prepared_terms = array();

// All terms are active.
$prepared_terms[] = array(
	'id'           => 'all',
	'display_text' => __( 'All', 'spectra-pro' ),
	'url'          => LoopBuilder::generate_term_url( $filter_key, $taxonomy_type ),
	'is_active'    => empty( $tax_ids ),
);

foreach ( $terms as $t ) {
	$is_active    = in_array( $t->term_id, $tax_ids, true );
	$display_text = esc_html( $t->name ) . ( $show_post_count ? " ({$t->count})" : '' );

	$prepared_terms[] = array(
		'id'           => $t->term_id,
		'display_text' => $display_text,
		'url'          => LoopBuilder::generate_term_url( $filter_key, $taxonomy_type, $t->term_id ),
		'is_active'    => $is_active,
	);
}//end foreach

// Prepare the final data array.
$data = array(
	'query_id'           => $query_id,
	'wrapper_attributes' => $wrapper_attributes,
	'terms'              => $prepared_terms,
);

// return the view.
return 'file:./view.php';
