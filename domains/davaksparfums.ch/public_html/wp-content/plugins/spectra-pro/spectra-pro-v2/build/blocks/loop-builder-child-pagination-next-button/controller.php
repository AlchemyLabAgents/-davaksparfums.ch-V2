<?php
/**
 * View for rendering the block.
 * 
 * @since 2.0.0-beta.1
 *
 * @package Spectra\Blocks\LoopBuilderChildPaginationNextButton
 */

use Spectra\Helpers\BlockAttributes;
use Spectra\Helpers\Core;

// Get block attributes.
$text                      = $attributes['text'] ?? '';
$link_url                  = $attributes['linkURL'] ?? '';
$show_text                 = $attributes['showText'] ?? true;
$icon                      = $attributes['icon'] ?? '';
$icon_position             = $attributes['iconPosition'] ?? 'after';
$flip_for_rtl              = $attributes['flipForRTL'] ?? false;
$aria_label                = ( ! $show_text && ! empty( $text ) ) ? $text : '';
$text_color                = $attributes['textColor'] ?? $block->context['spectra-pro/loop-builder-pagination/textColor'] ?? '';
$text_color_hover          = $attributes['textColorHover'] ?? $block->context['spectra-pro/loop-builder-pagination/textColorHover'] ?? '';
$icon_color                = $attributes['iconColor'] ?? $block->context['spectra-pro/loop-builder-pagination/textColor'] ?? '';
$icon_color_hover          = $attributes['iconColorHover'] ?? $block->context['spectra-pro/loop-builder-pagination/textColorHover'] ?? '';
$background_color          = $attributes['backgroundColor'] ?? $block->context['spectra-pro/loop-builder-pagination/backgroundColor'] ?? '';
$background_color_hover    = $attributes['backgroundColorHover'] ?? $block->context['spectra-pro/loop-builder-pagination/backgroundColorHover'] ?? '';
$background_gradient       = $attributes['backgroundGradient'] ?? $block->context['spectra-pro/loop-builder-pagination/backgroundGradient'] ?? '';
$background_gradient_hover = $attributes['backgroundGradientHover'] ?? $block->context['spectra-pro/loop-builder-pagination/backgroundGradientHover'] ?? '';

// Define base classes.
$icon_classes = array(
	'spectra-button__icon',
	"spectra-button__icon-position-$icon_position",
	$icon_color ? 'spectra-icon-color' : '',
	$icon_color_hover ? 'spectra-icon-color-hover' : '',
);

// Add the default specific icon props.
$icon_props = array(
	'class'     => Core::concatenate_array( $icon_classes ),
	'focusable' => 'false',
	'style'     => array(
		'transform' => ! empty( $attributes['rotation'] ) ? 'rotate(' . $attributes['rotation'] . 'deg)' : '',
	),
);

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
		'key'   => 'backgroundColor',
		'value' => $background_color,
	),
	array(
		'key'   => 'backgroundColorHover',
		'value' => $background_color_hover,
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
		'key'        => 'iconColor',
		'value'      => $icon_color,
		'class_name' => null,
	),
	array(
		'key'        => 'iconColorHover',
		'value'      => $icon_color_hover,
		'class_name' => null,
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
	'aria-label' => $aria_label,
	'href'       => esc_url( $link_url ),
	'target'     => '_self',
);

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, $additional_attributes, $additional_classes );

$btn_content = $show_text ? wp_kses_post( $text ) : '';

// return the view.
return 'file:./view.php';
