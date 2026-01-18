<?php
/**
 * Controller for rendering the block.
 * 
 * @since 2.0.0
 *
 * @package SpectraPro\Blocks\FormCheckbox
 */

use Spectra\Helpers\BlockAttributes;

// Get context values first (these can override attributes).
$context   = $block->context ?? array();
$form_type = $context['spectra-pro/form/formType'] ?? '';
$block_id  = $context['spectra-pro/form/block_id'] ?? 'default';

$is_required    = $attributes['isRequired'] ?? false;
$checkbox_type  = $attributes['checkboxType'] ?? 'terms';
$checkbox_label = $attributes['checkboxLabel'] ?? 'I Accept the Terms and Conditions';
$checkbox_name  = $checkbox_type; // Use the type as the field name for validation.
$checkbox_id    = $attributes['checkboxId'] ?? '';

// Set checkbox ID.
if ( empty( $checkbox_id ) ) {
	$checkbox_id = $checkbox_type . '-' . $block_id;
}

// Merge context values into attributes (like form-icon pattern).
$attributes['textColorHover']          = $attributes['textColorHover'] ?? $block->context['spectra-pro/form/textColorHover'] ?? '';
$attributes['checkboxBackgroundColor'] = $attributes['checkboxBackgroundColor'] ?? $block->context['spectra-pro/form/checkboxBackgroundColor'] ?? '';

// Style and class configurations - Spectra-specific CSS variables to match style.scss.
$config = array(
	array(
		'key'        => 'textColorHover',
		'css_var'    => '--spectra-text-color-hover',
		'class_name' => 'spectra-text-color-hover',
	),
	array(
		'key'        => 'checkboxBackgroundColor',
		'css_var'    => '--spectra-checkbox-background-color',
		'class_name' => 'spectra-checkbox-background-color',
	),
	array(
		'key'        => 'checkboxCheckmarkColor',
		'css_var'    => '--spectra-checkbox-checkmark-color',
		'class_name' => 'spectra-checkbox-checkmark-color',
	),
);

// Additional classes.
$additional_classes = array(
	'spectra-pro-form-checkbox--' . esc_attr( $form_type ),
);

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, array(), $additional_classes );

// Form checkboxes are always label tags to wrap input and text.
$tag_name = 'label';

// Render the block.
return 'file:./view.php';
