<?php
/**
 * Controller for rendering the block.
 * 
 * @since 2.0.0
 *
 * @package SpectraPro\Blocks\FormWrapper
 */

use Spectra\Helpers\BlockAttributes;

// Get attributes with defaults.
$wrapper_type = $attributes['wrapperType'] ?? 'form';
$is_preview   = $attributes['isPreview'] ?? false;

// Get context values.
$context           = $block->context ?? array();
$form_id           = $context['spectra-pro/form/formId'] ?? 'login-form';
$form_type         = $context['spectra-pro/form/formType'] ?? 'login';
$overall_alignment = $context['spectra-pro/form/overallAlignment'] ?? 'left';

// Style and class configurations.
$config = array(
	array( 'key' => 'textColor' ),
	array( 'key' => 'backgroundColor' ),
	array( 'key' => 'borderColor' ),
);

// Additional classes.
$additional_classes = array(
	'spectra-pro-form-wrapper',
	'spectra-pro-form-wrapper--' . esc_attr( $wrapper_type ),
);

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, array(), $additional_classes );

// Add data attributes for form identification.
$data_attributes = array(
	'data-form-id="' . esc_attr( $form_id ) . '"',
	'data-form-type="' . esc_attr( $form_type ) . '"',
	'style="text-align: ' . esc_attr( $overall_alignment ) . ';"',
);

// Append data attributes to wrapper.
$wrapper_attributes .= ' ' . implode( ' ', $data_attributes );

// Determine the appropriate store namespace for submit handler.
$store_namespace = 'spectra-pro/' . $form_type;

// Prepare data for view.
$data = array(
	'wrapper_attributes' => $wrapper_attributes,
	'content'            => $content,
	'store_namespace'    => $store_namespace,
	'form_type'          => $form_type,
);

// Render the block.
return 'file:./view.php';
