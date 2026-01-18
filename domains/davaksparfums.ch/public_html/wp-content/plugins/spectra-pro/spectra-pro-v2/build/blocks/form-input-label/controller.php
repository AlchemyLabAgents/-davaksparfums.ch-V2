<?php
/**
 * Controller for rendering the Form Input Label block.
 * 
 * @since 2.0.0
 *
 * @package SpectraPro\Blocks\FormInputLabel
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Spectra\Helpers\BlockAttributes;

// Get attributes with defaults.
$label_text         = $attributes['labelText'] ?? '';
$is_required        = $attributes['isRequired'] ?? false;
$required_indicator = $attributes['requiredIndicator'] ?? '*';
$hide_label         = $attributes['hideLabel'] ?? false;
$html_for           = $attributes['htmlFor'] ?? '';
$text_color_hover   = $attributes['textColorHover'] ?? '';

// Get context values for inheritance.
$context             = $block->context ?? array();
$context_label_text  = $context['spectra-pro/form-input/fieldLabel'] ?? '';
$context_is_required = $context['spectra-pro/form-input/isRequired'] ?? false;
$context_field_type  = $context['spectra-pro/form-input/fieldType'] ?? '';
$show_labels         = $context['spectra-pro/form/showLabels'] ?? true;

// Don't render if labels are disabled globally or locally.
if ( $hide_label || false === $show_labels ) {
	return '';
}

// Only use labelText (user's custom text), no fallback - matches old login block behavior.
$final_label_text  = $label_text;
$final_is_required = $context_is_required || $is_required;

// Don't render if no custom label text (matches old login block: ! empty( $attributes['usernameLabel'] )).
if ( empty( $final_label_text ) ) {
	return '';
}

// Generate field ID for htmlFor.
$field_id = $html_for ? $html_for : ( $context_field_type ? "field-{$context_field_type}" : 'field-input' );

// Merge context values into attributes (like render.js pattern).
$attributes['textColor']      = $attributes['textColor'] ?? $block->context['spectra-pro/form/labelColor'] ?? '';
$attributes['textColorHover'] = $attributes['textColorHover'] ?? $block->context['spectra-pro/form/labelColorHover'] ?? '';

// Style and class configurations - Spectra-specific CSS variables to match style.scss.
$config = array(
	array(
		'key'        => 'textColor',
		'css_var'    => '--spectra-label-color',
		'class_name' => 'spectra-label-color',
	),
	array(
		'key'        => 'textColorHover',
		'css_var'    => '--spectra-label-color-hover',
		'class_name' => 'spectra-label-color-hover',
	),
);

// Additional classes.
$additional_classes = array(
	'spectra-form-input-label--' . esc_attr( $context_field_type ? $context_field_type : 'text' ),
	$attributes['textColor'] ? 'has-text-color' : '',
);

// Get the block wrapper attributes - Spectra-v3 pattern.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, array( 'for' => $field_id ), $additional_classes );

// Prepare data for view.
$data = array(
	'wrapper_attributes' => $wrapper_attributes,
	'label_text'         => $final_label_text,
	'is_required'        => $final_is_required,
	'required_indicator' => $required_indicator,
	'tag_name'           => 'label',
);

// Render the label block.
return 'file:./view.php';
