<?php
/**
 * Controller for rendering the block.
 * 
 * @since 3.0.0-beta.1
 *
 * @package Spectra\Blocks\Icon
 */

use Spectra\Helpers\BlockAttributes;
use Spectra\Helpers\Core;

// Set the attributes with fallback if required.
$anchor = $attributes['anchor'] ?? '';

// Get fieldType from attribute or context.
$field_type = $attributes['fieldType'] ?? $block->context['spectra-pro/form-input/fieldType'] ?? '';

// Default icon mapping based on field type.
$icon_map = array(
	'username'         => 'user',
	'password'         => 'lock',
	'email'            => 'envelope',
	'first_name'       => 'user',
	'last_name'        => 'user',
	'confirm_password' => 'lock',
);

// Use custom icon if provided, otherwise use default based on field type.
$default_icon = ! empty( $field_type ) && isset( $icon_map[ $field_type ] ) ? $icon_map[ $field_type ] : 'star';
$icon         = $attributes['icon'] ?? $default_icon;

// Set the default props required for the icon.
$icon_props = array(
	'focusable' => 'false',
	'style'     => array(
		'fill'      => 'currentColor',
		'transform' => ! empty( $attributes['rotation'] ) ? 'rotate(' . $attributes['rotation'] . 'deg)' : '',
	),
);

// Check if icons should be shown from parent context.
$show_icons = $block->context['spectra-pro/form/showIcons'] ?? true;

// If icons are hidden, return empty string (don't render).
if ( ! $show_icons ) {
	return '';
}

// Merge context values into attributes (like render.js pattern).
$attributes['textColor'] = $attributes['textColor'] ?? $block->context['spectra-pro/form/iconColor'] ?? '';

// Style and class configurations - Spectra-specific CSS variables to match style.scss.
$config = array(
	array(
		'key'        => 'textColor',
		'css_var'    => '--spectra-icon-color',
		'class_name' => 'spectra-icon-color',
	),
);

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, array() );

// Render the icon block.
return 'file:./view.php';
