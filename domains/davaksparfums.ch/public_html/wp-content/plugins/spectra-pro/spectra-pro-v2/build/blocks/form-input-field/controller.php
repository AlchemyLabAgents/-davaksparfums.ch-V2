<?php
/**
 * Controller for rendering the block.
 * 
 * @since 2.0.0
 *
 * @package SpectraPro\Blocks\FormInputField
 */

use Spectra\Helpers\BlockAttributes;
use Spectra\Helpers\Core;

// Get context values from parent form-input-wrapper.
$context           = $block->context ?? array();
$form_type         = $context['spectra-pro/form/formType'] ?? '';
$field_placeholder = $context['spectra-pro/form-input/fieldPlaceholder'] ?? '';

// Get field type from context (provided by parent register-* or form-input-wrapper blocks).
$field_type = $context['spectra-pro/form-input/fieldType'] ?? 'text';

// Fallback: If no fieldType in context, try to detect from placeholder (backward compatibility).
if ( 'text' === $field_type && ! empty( $field_placeholder ) ) {
	$placeholder_lower = strtolower( $field_placeholder );
	if ( strpos( $placeholder_lower, 'username' ) !== false || strpos( $placeholder_lower, 'enter username' ) !== false ) {
		$field_type = 'username';
	} elseif ( strpos( $placeholder_lower, 'email' ) !== false ) {
		$field_type = 'email';
	} elseif ( strpos( $placeholder_lower, 'confirm' ) !== false && strpos( $placeholder_lower, 'password' ) !== false ) {
		$field_type = 'confirm_password';
	} elseif ( strpos( $placeholder_lower, 'password' ) !== false ) {
		$field_type = 'password';
	} elseif ( strpos( $placeholder_lower, 'first' ) !== false && strpos( $placeholder_lower, 'name' ) !== false ) {
		$field_type = 'first_name';
	} elseif ( strpos( $placeholder_lower, 'last' ) !== false && strpos( $placeholder_lower, 'name' ) !== false ) {
		$field_type = 'last_name';
	}
}


$is_required          = $context['spectra-pro/form-input/isRequired'] ?? false;
$field_value          = $attributes['fieldValue'] ?? '';
$show_password_toggle = $context['spectra-pro/form/showPasswordToggle'] ?? false;
$block_id             = $context['spectra-pro/form/block_id'] ?? 'default';

// Set field names based on form type - login.js expects 'log'/'pwd', register.js expects 'username'/'password'.
switch ( $field_type ) {
	case 'username':
		$input_type = 'text';
		// Login forms expect 'log' field name (WordPress standard).
		// Register forms expect 'username' field name.
		$input_name = ( 'login' === $form_type ) ? 'log' : 'username';
		break;
	case 'email':
		$input_type = 'email';
		$input_name = 'email';
		break;
	case 'password':
		$input_type = 'password';
		// Login forms expect 'pwd' field name (WordPress standard).
		// Register forms expect 'password' field name.
		$input_name = ( 'login' === $form_type ) ? 'pwd' : 'password';
		break;
	case 'confirm_password':
		$input_type = 'password';
		$input_name = 'confirm_password';
		break;
	case 'first_name':
		$input_type = 'text';
		$input_name = 'first_name';
		break;
	case 'last_name':
		$input_type = 'text';
		$input_name = 'last_name';
		break;
	default:
		$input_type = 'text';
		$input_name = 'field';
}//end switch


$input_id = 'input-' . $field_type . '-' . $block_id;

// Set autocomplete attributes.
$autocomplete_mapping = array(
	'username'         => 'username',
	'email'            => 'email',
	'password'         => 'current-password',
	'confirm_password' => 'new-password',
	'first_name'       => 'given-name',
	'last_name'        => 'family-name',
);
$autocomplete         = $autocomplete_mapping[ $field_type ] ?? '';

// Set the attributes with fallback from parent context - Spectra-v3 pattern.
$text_color              = $attributes['textColor'] ?? $block->context['spectra-pro/form/inputTextColor'] ?? '';
$text_color_hover        = $attributes['textColorHover'] ?? $block->context['spectra-pro/form/inputTextColorHover'] ?? '';
$background_color        = $attributes['backgroundColor'] ?? $block->context['spectra-pro/form/inputBgColor'] ?? '';
$background_color_hover  = $attributes['backgroundColorHover'] ?? $block->context['spectra-pro/form/inputBgColorHover'] ?? '';
$placeholder_color       = $attributes['placeholderColor'] ?? $block->context['spectra-pro/form/inputPlaceholderColor'] ?? '';
$placeholder_color_hover = $attributes['placeholderColorHover'] ?? $block->context['spectra-pro/form/inputPlaceholderColorHover'] ?? '';

// Override attributes with context values for BlockAttributes helper.
$attributes['textColor']             = $text_color;
$attributes['textColorHover']        = $text_color_hover;
$attributes['backgroundColor']       = $background_color;
$attributes['backgroundColorHover']  = $background_color_hover;
$attributes['placeholderColor']      = $placeholder_color;
$attributes['placeholderColorHover'] = $placeholder_color_hover;

// Style and class configurations - Spectra-specific CSS variables to match style.scss.
$config = array(
	array(
		'key'        => 'textColor',
		'css_var'    => '--spectra-input-text-color',
		'class_name' => 'spectra-input-text-color',
	),
	array(
		'key'        => 'textColorHover',
		'css_var'    => '--spectra-input-text-color-hover',
		'class_name' => 'spectra-input-text-color-hover',
	),
	array(
		'key'        => 'backgroundColor',
		'css_var'    => '--spectra-input-bg-color',
		'class_name' => 'spectra-input-bg-color',
	),
	array(
		'key'        => 'backgroundColorHover',
		'css_var'    => '--spectra-input-bg-color-hover',
		'class_name' => 'spectra-input-bg-color-hover',
	),
	array(
		'key'        => 'placeholderColor',
		'css_var'    => '--spectra-input-placeholder-color',
		'class_name' => 'spectra-input-placeholder-color',
	),
	array(
		'key'        => 'placeholderColorHover',
		'css_var'    => '--spectra-input-placeholder-color-hover',
		'class_name' => 'spectra-input-placeholder-color-hover',
	),
);

// Additional classes for the input element.
$additional_classes = array(
	'spectra-form-input-field__input',
	'spectra-pro-form-input-field--' . esc_attr( $field_type ),
);

// Build input element attributes directly (no wrapper!).
// This allows padding to apply directly to the input.
$input_attributes = array(
	'type'              => $input_type,
	'id'                => $input_id,
	'name'              => $input_name,
	'data-wp-on--input' => 'actions.onInputChange',
	'data-wp-on--focus' => 'actions.onInputFocus',
);

// Add optional attributes.
if ( ! empty( $field_placeholder ) ) {
	$input_attributes['placeholder'] = $field_placeholder;
}
if ( ! empty( $field_value ) ) {
	$input_attributes['value'] = $field_value;
}
if ( $is_required ) {
	$input_attributes['required'] = 'required';
}
if ( $autocomplete ) {
	$input_attributes['autocomplete'] = $autocomplete;
}

// Get wrapper attributes for the input (includes styles, spacing, etc.).
// Classes from $additional_classes will be merged with the generated classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, $input_attributes, $additional_classes );

// Render the block.
return 'file:./view.php';
