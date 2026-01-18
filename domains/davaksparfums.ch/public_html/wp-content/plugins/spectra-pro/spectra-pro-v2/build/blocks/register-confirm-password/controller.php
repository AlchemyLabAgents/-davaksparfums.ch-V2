<?php
/**
 * Register Confirm Password Field controller.
 *
 * @since 2.0.0
 * @package Spectra Pro
 */

use Spectra\Helpers\HtmlSanitizer;
use Spectra\Helpers\Renderer;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get attributes.
$wrapper_tag       = $attributes['wrapperTag'] ?? 'div';
$field_type        = $attributes['fieldType'] ?? 'confirm_password'; // Field type for register-confirm-password block.
$field_label       = $attributes['fieldLabel'] ?? '';
$field_placeholder = $attributes['fieldPlaceholder'] ?? '';
$field_icon        = $attributes['fieldIcon'] ?? '';
$is_required       = $attributes['isRequired'] ?? false;
$field_id          = $attributes['fieldId'] ?? '';
$show_label        = $attributes['showLabel'] ?? true;

$context              = $block->context ?? array();
$form_type            = $context['spectra-pro/form/formType'] ?? '';
$parent_show_labels   = $context['spectra-pro/form/showLabels'] ?? true;
$parent_show_icons    = $context['spectra-pro/form/showIcons'] ?? true;
$show_password_toggle = $context['spectra-pro/form/showPasswordToggle'] ?? true;
$block_id             = $context['spectra-pro/form/block_id'] ?? 'preview';

$context_username_label       = $context['spectra-pro/form/usernameLabel'] ?? '';
$context_username_placeholder = $context['spectra-pro/form/usernamePlaceholder'] ?? '';
$context_password_label       = $context['spectra-pro/form/passwordLabel'] ?? '';
$context_password_placeholder = $context['spectra-pro/form/passwordPlaceholder'] ?? '';

// Generate unique field ID if not set.
if ( empty( $field_id ) ) {
	$field_id = $form_type . '_' . $field_type . '_' . $block_id;
}

// Determine the effective label and placeholder.
$effective_label       = $field_label;
$effective_placeholder = $field_placeholder;

// Set field-specific values using switch case for reusable inner blocks.
switch ( $field_type ) {
	case 'confirm_password':
		$effective_label       = $field_label ? $field_label : __( 'Confirm Password', 'spectra-pro' );
		$effective_placeholder = $field_placeholder ? $field_placeholder : __( 'Confirm your password', 'spectra-pro' );
		$field_icon            = $field_icon ? $field_icon : 'lock';
		break;
	default:
		$effective_label       = $field_label ? $field_label : __( 'Field Label', 'spectra-pro' );
		$effective_placeholder = $field_placeholder ? $field_placeholder : __( 'Enter value...', 'spectra-pro' );
		$field_icon            = $field_icon ? $field_icon : 'user';
}

// Determine if we should show labels and icons.
$should_show_labels = ( false !== $show_label ) ? $parent_show_labels : false;
$should_show_icons  = $parent_show_icons;

use Spectra\Helpers\BlockAttributes;

// Merge context values into attributes (like form-icon pattern).
$attributes['textColor']            = $attributes['textColor'] ?? $block->context['spectra-pro/form/inputTextColor'] ?? '';
$attributes['textColorHover']       = $attributes['textColorHover'] ?? $block->context['spectra-pro/form/inputTextColorHover'] ?? '';
$attributes['backgroundColor']      = $attributes['backgroundColor'] ?? $block->context['spectra-pro/form/inputBgColor'] ?? '';
$attributes['backgroundColorHover'] = $attributes['backgroundColorHover'] ?? $block->context['spectra-pro/form/inputBgColorHover'] ?? '';

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
);

$additional_classes = array(
	'spectra-pro-register-confirm-password',
	'spectra-pro-form-input-wrapper--' . esc_attr( $field_type ),
);

$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, array( 'id' => 'register-confirm-password-' . esc_attr( $block_id ) ), $additional_classes );



// Provide context to child blocks using the correct context keys.
$block->context['spectra-pro/form-input/fieldType']        = $field_type;
$block->context['spectra-pro/form-input/fieldLabel']       = $effective_label;
$block->context['spectra-pro/form-input/fieldPlaceholder'] = $effective_placeholder;
$block->context['spectra-pro/form-input/fieldIcon']        = $field_icon;
$block->context['spectra-pro/form-input/isRequired']       = $is_required;
$block->context['spectra-pro/form-input/fieldId']          = $field_id;

// Use template-defined child blocks following Spectra-v3 pattern.
$final_content = $content;

// Prepare data for view.
$data = array(
	'wrapper_attributes' => $wrapper_attributes,
	'content'            => $final_content,
	'tag_name'           => $wrapper_tag,
);

// Return the view file.
return 'file:./view.php';
