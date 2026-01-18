<?php
/**
 * Controller for rendering the block.
 * 
 * @since 2.0.0-beta.1
 *
 * @package SpectraPro\Blocks\LoopBuilderChildFilterSelect
 */

use Spectra\Helpers\BlockAttributes;
use SpectraPro\Helpers\BlockStyleManager;
use SpectraPro\Queries\LoopBuilderQuery;

// Exit if queryId is null, taxonomyType and filterType are not set or filterType is not equal to select.
if ( is_null( LoopBuilderQuery::get_query_id( $block ) ) || ! isset(
	$block->context['spectra-pro/loop-builder-child-filter/filterType'], 
	$block->context['spectra-pro/loop-builder-child-filter/taxonomyType'] 
) || 'select' !== $block->context['spectra-pro/loop-builder-child-filter/filterType'] ) {
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

// Exit if terms are empty.
if ( empty( $terms ) ) {
	return '';
}

// Get the placeholder.
$placeholder = $attributes['placeholder'] ?? '';

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

// Get style attributes.
$style_attributes = BlockStyleManager::get_attributes( $attributes, array( 'border', 'spacing', 'shadow', 'typography' ) );

// Prepare the data for the view.
$data = array(
	'wrapper_attributes' => $wrapper_attributes,
	'terms'              => $terms,
	'show_post_count'    => $show_post_count,
	'placeholder'        => $placeholder,
	'taxonomy_type'      => $taxonomy_type,
	'style'              => $style_attributes['style'],
	'class'              => $style_attributes['class'],
);

// Return the view.
return 'file:./view.php';
