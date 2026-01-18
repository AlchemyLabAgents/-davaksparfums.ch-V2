<?php
/**
 * Controller for rendering the block.
 * 
 * @since 2.0.0
 *
 * @package SpectraPro\Blocks\FormCheckbox
 */

use Spectra\Helpers\BlockAttributes;

// Get context values.
$context       = $block->context ?? array();
$form_type     = $context['spectra-pro/form/formType'] ?? '';
$block_id      = $context['spectra-pro/form/block_id'] ?? 'default';
$show_remember = $context['spectra-pro/form/showRememberMe'] ?? true;

// Get checkbox type from attributes.
$checkbox_type = $attributes['checkboxType'] ?? 'remember';

// Don't render if showRememberMe is false and this is a remember me checkbox.
if ( 'remember' === $checkbox_type && ! $show_remember ) {
	return '';
}

// Get label text from parent context based on checkbox type.
$checkbox_label = '';

switch ( $checkbox_type ) {
	case 'remember':
		$checkbox_label = $context['spectra-pro/form/rememberLabel'] ?? __( 'Remember Me', 'spectra-pro' );
		$checkbox_name  = 'rememberme';
		break;
	case 'terms':
		$checkbox_label = $context['spectra-pro/form/termsLabel'] ?? __( 'I agree to the terms and conditions', 'spectra-pro' );
		$checkbox_name  = 'terms_agreed';
		break;
	case 'newsletter':
		$checkbox_label = $context['spectra-pro/form/newsletterLabel'] ?? __( 'Subscribe to newsletter', 'spectra-pro' );
		$checkbox_name  = 'newsletter';
		break;
	default:
		$checkbox_label = __( 'Checkbox', 'spectra-pro' );
		$checkbox_name  = 'checkbox';
		break;
}

// Set checkbox ID.
$checkbox_id = 'checkbox-' . $checkbox_type . '-' . $block_id;

// Merge context values into attributes to match render.js pattern.
$attributes['textColorHover']          = $attributes['textColorHover'] ?? $context['spectra-pro/form/textColorHover'] ?? '';
$attributes['checkboxBackgroundColor'] = $attributes['checkboxBackgroundColor'] ?? $context['spectra-pro/form/checkboxBackgroundColor'] ?? '';

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
	'spectra-pro-form-checkbox--' . esc_attr( $checkbox_type ),
);

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, array(), $additional_classes );

// Render the block.
return 'file:./view.php';
