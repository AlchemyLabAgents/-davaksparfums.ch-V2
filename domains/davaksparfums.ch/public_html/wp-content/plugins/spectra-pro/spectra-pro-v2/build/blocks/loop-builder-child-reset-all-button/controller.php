<?php
/**
 * View for rendering the block.
 * 
 * @since 2.0.0-beta.1
 *
 * @package Spectra\Blocks\LoopBuilderChildResetAllButton
 */

use Spectra\Helpers\BlockAttributes;
use Spectra\Helpers\Core;
use SpectraPro\Queries\LoopBuilderQuery;

// If queryId is null, return an empty string.
if ( is_null( LoopBuilderQuery::get_query_id( $block ) ) ) {
	return '';
}

// Get attributes.
$text             = $attributes['text'] ?? '';
$show_text        = $attributes['showText'] ?? true;
$icon             = $attributes['icon'] ?? '';
$icon_position    = $attributes['iconPosition'] ?? 'after';
$icon_color       = $attributes['iconColor'] ?? '';
$icon_color_hover = $attributes['iconColorHover'] ?? '';
$flip_for_rtl     = ! empty( $attributes['flipForRTL'] );
$aria_label       = ( ! $show_text && ! empty( $text ) ) ? $text : '';

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
	array( 'key' => 'textColor' ),
	array( 'key' => 'textColorHover' ),
	array( 'key' => 'backgroundColor' ),
	array( 'key' => 'backgroundColorHover' ),
	array( 'key' => 'backgroundGradient' ),
	array( 'key' => 'backgroundGradientHover' ),
	array(
		'key'        => 'iconColor',
		'class_name' => null,
	),
	array(
		'key'        => 'iconColorHover',
		'class_name' => null,
	),
);

// Extract text alignment from responsive controls
$text_align = $attributes['responsiveControls']['lg']['style']['typography']['textAlign'] ?? '';

// Class to handle astra theme button style compatibility.
$additional_classes = array( 'wp-block-button', 'wp-block-button__link wp-element-button' );

// Add text alignment class if text alignment is set
if ( ! empty( $text_align ) ) {
	$additional_classes[] = "has-text-align-{$text_align}";
}

// Additional attributes.
$additional_attributes = array(
	'aria-label' => $aria_label,
);

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, $additional_attributes, $additional_classes );

$btn_content = $show_text ? wp_kses_post( $text ) : '';

// return the view.
return 'file:./view.php';
