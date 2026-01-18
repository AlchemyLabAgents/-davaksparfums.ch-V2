<?php
/**
 * View for rendering the block.
 * 
 * @since 2.0.0-beta.1
 *
 * @package Spectra\Blocks\LoopBuilderChildPaginationPageNumbersButton
 */

use Spectra\Helpers\BlockAttributes;

// Get block attributes.
$text                       = $attributes['text'] ?? '';
$link_url                   = $attributes['linkURL'] ?? '';
$text_color                 = $attributes['textColor'] ?? $block->context['spectra-pro/loop-builder-pagination/textColor'] ?? '';
$text_color_active          = $attributes['textColorActive'] ?? $block->context['spectra-pro/loop-builder-pagination/textColorActive'] ?? '';
$text_color_hover           = $attributes['textColorHover'] ?? $block->context['spectra-pro/loop-builder-pagination/textColorHover'] ?? '';
$background_color           = $attributes['backgroundColor'] ?? $block->context['spectra-pro/loop-builder-pagination/backgroundColor'] ?? '';
$background_color_active    = $attributes['backgroundColorActive'] ?? $block->context['spectra-pro/loop-builder-pagination/backgroundColorActive'] ?? '';
$background_color_hover     = $attributes['backgroundColorHover'] ?? $block->context['spectra-pro/loop-builder-pagination/backgroundColorHover'] ?? '';
$background_gradient        = $attributes['backgroundGradient'] ?? $block->context['spectra-pro/loop-builder-pagination/backgroundGradient'] ?? '';
$background_gradient_active = $attributes['backgroundGradientActive'] ?? $block->context['spectra-pro/loop-builder-pagination/backgroundGradientActive'] ?? '';
$background_gradient_hover  = $attributes['backgroundGradientHover'] ?? $block->context['spectra-pro/loop-builder-pagination/backgroundColorHover'] ?? '';

// Style and class configurations.
$config = array(
	array(
		'key'   => 'textColor',
		'value' => $text_color,
	),
	array(
		'key'   => 'textColorHover',
		'value' => $text_color_hover,
	),
	array(
		'key'   => 'textColorActive',
		'value' => $text_color_active,
	),
	array(
		'key'   => 'backgroundColor',
		'value' => $background_color,
	),
	array(
		'key'   => 'backgroundColorHover',
		'value' => $background_color_hover,
	),
	array(
		'key'   => 'backgroundColorActive',
		'value' => $background_color_active,
	),
	array(
		'key'   => 'backgroundGradient',
		'value' => $background_gradient,
	),
	array(
		'key'   => 'backgroundGradientHover',
		'value' => $background_gradient_hover,
	),
	array(
		'key'   => 'backgroundGradientActive',
		'value' => $background_gradient_active,
	),
);

// Extract text alignment and add appropriate class
$text_align = $attributes['responsiveControls']['lg']['style']['typography']['textAlign'] ?? '';

// Base classes.
$additional_classes = array( 'wp-block-button', 'wp-block-button__link wp-element-button' );

// Add text alignment class if text alignment is set
if ( ! empty( $text_align ) ) {
	$additional_classes[] = "has-text-align-{$text_align}";
}

// Additional attributes.
$additional_attributes = array(
	'href'   => esc_url( $link_url ),
	'target' => '_self',
);

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, $additional_attributes, $additional_classes );

$btn_content = '<div class="spectra-button__link"> ' . wp_kses_post( $text ) . '</div>';

// return the view.
return 'file:./view.php';
