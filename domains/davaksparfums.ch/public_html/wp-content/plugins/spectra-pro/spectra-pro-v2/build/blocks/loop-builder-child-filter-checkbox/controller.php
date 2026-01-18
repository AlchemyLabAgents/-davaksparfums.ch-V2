<?php
/**
 * Controller for rendering the block.
 * 
 * @since 2.0.0-beta.1
 *
 * @package SpectraPro\Blocks\LoopBuilderChildFilterCheckbox
 */

use Spectra\Helpers\BlockAttributes;
use SpectraPro\Queries\LoopBuilderQuery;

// Exit if queryId is null or taxonomyType and filterType are not set or filterType is not equal to checkbox.
if ( is_null( LoopBuilderQuery::get_query_id( $block ) ) || ! isset(
	$block->context['spectra-pro/loop-builder-child-filter/filterType'], 
	$block->context['spectra-pro/loop-builder-child-filter/taxonomyType'] 
) || 'checkbox' !== $block->context['spectra-pro/loop-builder-child-filter/filterType'] ) {
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
	array( 'key' => 'checkboxSize' ),
	array( 'key' => 'itemsGap' ),
	array( 'key' => 'labelCheckboxGap' ),
	array( 'key' => 'textColor' ),
	array( 'key' => 'textColorHover' ),
	array( 'key' => 'backgroundColor' ),
	array( 'key' => 'backgroundColorHover' ),
	array( 'key' => 'backgroundGradient' ),
	array( 'key' => 'backgroundGradientHover' ),
);

// Get layout type and validate it.
$layout_type = $attributes['layoutType'] ?? 'stack';
// Ensure layout type is only 'stack' or 'inline'.
$validated_layout_type = in_array( $layout_type, array( 'stack', 'inline' ), true ) ? $layout_type : 'stack';

// Add layout class to additional classes.
$additional_classes = array( 'is-layout-' . $validated_layout_type );

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, array(), $additional_classes );

// Prepare the data for the view.
$data = array(
	'wrapper_attributes' => $wrapper_attributes,
	'terms'              => $terms,
	'show_post_count'    => $show_post_count,
);

// Return the view.
return 'file:./view.php';
