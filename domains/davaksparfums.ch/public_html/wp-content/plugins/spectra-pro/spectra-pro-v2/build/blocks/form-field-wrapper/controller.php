<?php
/**
 * Controller for rendering the block.
 * 
 * @since 2.0.0
 *
 * @package SpectraPro\Blocks\FormFieldWrapper
 */

use Spectra\Helpers\BlockAttributes;

// Get attributes with defaults.
$is_preview = $attributes['isPreview'] ?? false;

// Get context values.
$context    = $block->context ?? array();
$form_id    = $context['spectra-pro/form/formId'] ?? 'login-form';
$form_type  = $context['spectra-pro/form/formType'] ?? 'login';
$field_type = $context['spectra-pro/form-input/fieldType'] ?? 'text';

// Style and class configurations.
$config = array(
	array( 'key' => 'textColor' ),
	array( 'key' => 'backgroundColor' ),
	array( 'key' => 'borderColor' ),
);

// Additional classes.
$additional_classes = array(
	'spectra-pro-form-field-wrapper',
	'spectra-pro-form-field-wrapper--' . esc_attr( $field_type ),
);

// Add legacy compatibility classes for icon positioning.
// Check if this field wrapper contains an icon (for old login styling compatibility).
$has_icon = $context['spectra-pro/form/showIcons'] ?? true;
if ( $has_icon ) {
	$additional_classes[] = 'has-icon'; // New class for CSS targeting.
	if ( 'username' === $field_type ) {
		$additional_classes[] = 'spectra-pro-login-form-username-wrap';
		$additional_classes[] = 'spectra-pro-login-form-username-wrap--have-icon';
	} elseif ( 'password' === $field_type ) {
		$additional_classes[] = 'spectra-pro-login-form-pass-wrap';
		$additional_classes[] = 'spectra-pro-login-form-pass-wrap--have-icon';
	}
} else {
	if ( 'username' === $field_type ) {
		$additional_classes[] = 'spectra-pro-login-form-username-wrap';
	} elseif ( 'password' === $field_type ) {
		$additional_classes[] = 'spectra-pro-login-form-pass-wrap';
	}
}

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, array(), $additional_classes );

// Add data attributes for field identification.
$data_attributes = array(
	'data-field-type="' . esc_attr( $field_type ) . '"',
);

// Append data attributes to wrapper.
$wrapper_attributes .= ' ' . implode( ' ', $data_attributes );

// Field wrapper is a div element.
$tag_name = 'div';

// Render the block.
return 'file:./view.php';
