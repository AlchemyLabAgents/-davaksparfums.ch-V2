<?php
/**
 * View for rendering the block.
 * 
 * @since 3.0.0-beta.1
 *
 * @package Spectra\Blocks\Button
 */

use Spectra\Helpers\BlockAttributes;
use Spectra\Helpers\Core;

// Get text from context (matching render.js priority: context first, then attribute).
$context_text   = $block->context['spectra-pro/form/submitButtonText'] ?? '';
$attribute_text = $attributes['text'] ?? '';

// Match editor behavior: context has priority, fallback to attribute.
$text = ! empty( $context_text ) ? $context_text : $attribute_text;
$icon = $attributes['icon'] ?? '';

// Bail out if both text and icon are empty.
if ( empty( $text ) && empty( $icon ) ) {
	return;
}

// Ensure attributes exist.
$show_text     = $attributes['showText'] ?? true;
$show_text     = $attributes['showText'] ?? true;
$icon_position = $attributes['iconPosition'] ?? 'after';
$flip_for_rtl  = $attributes['flipForRTL'] ?? false;

// Icon colors.
$icon_color       = $attributes['iconColor'] ?? '';
$icon_color_hover = $attributes['iconColorHover'] ?? '';
$text_color_hover = $attributes['textColorHover'] ?? '';

// Hover icon attributes.
$show_icon_on_hover      = $attributes['showIconOnHover'] ?? false;
$hover_icon              = $attributes['hoverIcon'] ?? '';
$hover_icon_position     = $attributes['hoverIconPosition'] ?? 'right';
$hover_icon_rotation     = $attributes['hoverIconRotation'] ?? 0;
$hover_icon_flip_for_rtl = $attributes['hoverIconFlipForRTL'] ?? false;
$hover_icon_aria_label   = $attributes['hoverIconAriaLabel'] ?? '';


// Convert shadow hover object to CSS string.
$shadow_hover = '';
if ( ! empty( $attributes['shadowHover'] ) ) {
	$shadow = $attributes['shadowHover'];
	// If it's already a string, use it directly (but only if it contains a color).
	if ( is_string( $shadow ) ) {
		$shadow_hover = $shadow;
	} elseif ( is_array( $shadow ) ) {
		$color = $shadow['color'] ?? '';
		
		// Only set shadow if color is actually provided.
		if ( ! empty( $color ) && trim( $color ) !== '' ) {
			$shadow_hover = sprintf(
				'%dpx %dpx %dpx %dpx %s',
				isset( $shadow['x'] ) ? intval( $shadow['x'] ) : 0,
				isset( $shadow['y'] ) ? intval( $shadow['y'] ) : 4,
				isset( $shadow['blur'] ) ? intval( $shadow['blur'] ) : 8,
				isset( $shadow['spread'] ) ? intval( $shadow['spread'] ) : 0,
				$color
			);
		}
	}
}//end if
$attributes['shadowHover'] = $shadow_hover;

// Convert border hover object to CSS strings - only set the hover color.
$border_hover_config = array();
if ( ! empty( $attributes['borderHover']['color'] ) ) {
	$border_hover = $attributes['borderHover'];
	$hover_color  = $border_hover['color'];
	
	// Only set the hover color as a CSS variable.
	// Let WordPress core border settings handle the responsive width/style.
	$border_hover_config[] = array(
		'key'        => 'borderHoverColor',
		'css_var'    => '--spectra-border-hover-color',
		'class_name' => 'spectra-border-hover',
		'value'      => $hover_color,
	);
}

// Define base classes.
$icon_classes = array(
	'spectra-button__icon',
	"spectra-button__icon-position-$icon_position",
	$icon_color ? 'spectra-icon-color' : '',
	( $icon_color_hover || $text_color_hover ) ? 'spectra-icon-color-hover' : '',
);

// Add the default specific icon props.
$icon_props = array(
	'class'     => Core::concatenate_array( $icon_classes ),
	'focusable' => 'false',
	'style'     => array(
		'transform' => ! empty( $attributes['rotation'] ) ? 'rotate(' . $attributes['rotation'] . 'deg)' : '',
	),
);

// Hover icon classes and props.
$hover_icon_classes = array(
	'spectra-button__hover-icon',
	"spectra-button__hover-icon-position-$hover_icon_position",
	$icon_color ? 'spectra-icon-color' : '',
	( $icon_color_hover || $text_color_hover ) ? 'spectra-icon-color-hover' : '',
);

$hover_icon_props = array(
	'class'     => Core::concatenate_array( $hover_icon_classes ),
	'focusable' => 'false',
	'style'     => array(
		'transform' => ! empty( $hover_icon_rotation ) ? 'rotate(' . $hover_icon_rotation . 'deg)' : '',
	),
);

// Style and class configurations - Spectra-specific CSS variables to match style.scss.
$config = array(
	array(
		'key'        => 'textColor',
		'css_var'    => '--spectra-button-text-color',
		'class_name' => 'spectra-button-text-color',
	),
	array(
		'key'        => 'textColorHover',
		'css_var'    => '--spectra-button-text-color-hover',
		'class_name' => 'spectra-button-text-color-hover',
	),
	array(
		'key'        => 'backgroundColor',
		'css_var'    => '--spectra-button-bg-color',
		'class_name' => 'spectra-button-bg-color',
	),
	array(
		'key'        => 'backgroundColorHover',
		'css_var'    => '--spectra-button-bg-color-hover',
		'class_name' => 'spectra-button-bg-color-hover',
	),
	array(
		'key'        => 'backgroundGradient',
		'css_var'    => '--spectra-background-gradient',
		'class_name' => 'spectra-background-gradient',
	),
	array(
		'key'        => 'backgroundGradientHover',
		'css_var'    => '--spectra-background-gradient-hover',
		'class_name' => 'spectra-background-gradient-hover',
	),
	array(
		'key'        => 'iconColor',
		'css_var'    => '--spectra-icon-color',
		'class_name' => 'spectra-icon-color',
	),
	array(
		'key'        => 'iconColorHover',
		'css_var'    => '--spectra-icon-color-hover',
		'class_name' => 'spectra-icon-color-hover',
	),
	array(
		'key'        => 'shadowHover',
		'css_var'    => '--spectra-shadow-hover',
		'class_name' => 'spectra-shadow-hover',
	),
	array(
		'key'        => 'gap',
		'css_var'    => '--spectra-icon-gap',
		'class_name' => 'spectra-icon-gap',
	),
);

// Add border hover configurations to main config.
$config = array_merge( $config, $border_hover_config );


// Get alignment - prioritize button's own alignment, fallback to parent context, then default to left.
$button_alignment  = $attributes['buttonAlignment'] ?? '';
$overall_alignment = $block->context['spectra-pro/form/overallAlignment'] ?? 'left';
$final_alignment   = ! empty( $button_alignment ) ? $button_alignment : $overall_alignment;

// Base classes.
$custom_classes = array(
	'wp-block-button__link wp-element-button',
	"spectra-button--align-{$final_alignment}",
);

// Add hover icon class if enabled.
if ( $show_icon_on_hover && ! empty( $hover_icon ) ) {
	$custom_classes[] = 'has-hover-icon';
}

// Add border hover classes if enabled.
if ( ! empty( $attributes['borderHover']['color'] ) ) {
	$custom_classes[] = 'has-border-hover';
	$custom_classes[] = 'spectra-border-hover-override';
}

// Add shadow hover classes if enabled.
if ( ! empty( $attributes['shadowHover'] ) ) {
	$custom_classes[] = 'spectra-shadow-hover-override';
}

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, array(), $custom_classes );

// return the view.
return 'file:./view.php';
