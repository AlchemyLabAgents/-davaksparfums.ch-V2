<?php
/**
 * Form Input Wrapper controller.
 *
 * @since 2.0.0
 * @package Spectra Pro
 */

use Spectra\Helpers\HtmlSanitizer;
use Spectra\Helpers\Renderer;
use Spectra\Helpers\BlockAttributes;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get context values first to determine form type and field type.
$context   = $block->context ?? array();
$form_type = $context['spectra-pro/form/formType'] ?? '';

// Get attributes (fieldType comes from attributes set by template).
$wrapper_tag       = $attributes['wrapperTag'] ?? 'div';
$field_type        = $attributes['fieldType'] ?? 'text'; // From template attributes.
$field_label       = $attributes['fieldLabel'] ?? '';
$field_placeholder = $attributes['fieldPlaceholder'] ?? '';
$field_icon        = $attributes['fieldIcon'] ?? '';
$is_required       = $attributes['isRequired'] ?? false;
$field_id          = $attributes['fieldId'] ?? '';
$show_label        = $attributes['showLabel'] ?? true;


// Auto-detect field type for register forms if not explicitly set.
if ( 'text' === $field_type && 'register' === $form_type && ! empty( $field_placeholder ) ) {
	$placeholder_lower = strtolower( $field_placeholder );
	
	// Check for email patterns.
	if ( strpos( $placeholder_lower, 'email' ) !== false ) {
		$field_type = 'email';
	} elseif ( strpos( $placeholder_lower, 'password' ) !== false || strpos( $placeholder_lower, 'confirm' ) !== false ) {
		// Check for password patterns (including confirm password).
		if ( strpos( $placeholder_lower, 'confirm' ) !== false ) {
			$field_type = 'confirm_password';
		} else {
			$field_type = 'password';
		}
	} elseif ( strpos( $placeholder_lower, 'first' ) !== false ) {
		// Check for name patterns.
		$field_type = 'first_name';
	} elseif ( strpos( $placeholder_lower, 'last' ) !== false ) {
		$field_type = 'last_name';
	} elseif ( strpos( $placeholder_lower, 'username' ) !== false || 'enter username' === $placeholder_lower ) {
		// Check for username patterns (including just "username" text).
		$field_type = 'username';
	} elseif ( 'enter password' === $placeholder_lower ) {
		// Fallback: if it's "enter password" without "confirm".
		$field_type = 'password';
	}//end if
}//end if

// Get remaining context values for inheritance (following loop-builder pattern).
$parent_show_labels   = $context['spectra-pro/form/showLabels'] ?? true;
$parent_show_icons    = $context['spectra-pro/form/showIcons'] ?? true;
$show_password_toggle = $context['spectra-pro/form/showPasswordToggle'] ?? true;
$block_id             = $context['spectra-pro/form/block_id'] ?? 'preview';

// Generate unique field ID if not set.
if ( empty( $field_id ) && $field_type ) {
	$field_id = $form_type . '_' . $field_type . '_' . $block_id;
}

// Use block's own attributes directly - no context dependency.
$effective_label       = $field_label;
$effective_placeholder = $field_placeholder;

// Set defaults only if empty.
if ( empty( $effective_label ) ) {
	$default_labels  = array(
		'username'         => __( 'Username or Email', 'spectra-pro' ),
		'email'            => __( 'Email Address', 'spectra-pro' ),
		'password'         => __( 'Password', 'spectra-pro' ),
		'first_name'       => __( 'First Name', 'spectra-pro' ),
		'last_name'        => __( 'Last Name', 'spectra-pro' ),
		'confirm_password' => __( 'Confirm Password', 'spectra-pro' ),
	);
	$effective_label = $default_labels[ $field_type ] ?? __( 'Field Label', 'spectra-pro' );
}

if ( empty( $effective_placeholder ) ) {
	$default_placeholders  = array(
		'username'         => __( 'Enter your username or email', 'spectra-pro' ),
		'email'            => __( 'Enter your email address', 'spectra-pro' ),
		'password'         => __( 'Enter your password', 'spectra-pro' ),
		'first_name'       => __( 'Enter your first name', 'spectra-pro' ),
		'last_name'        => __( 'Enter your last name', 'spectra-pro' ),
		'confirm_password' => __( 'Confirm your password', 'spectra-pro' ),
	);
	$effective_placeholder = $default_placeholders[ $field_type ] ?? __( 'Enter value...', 'spectra-pro' );
}

// Set default field icon if not provided.
if ( empty( $field_icon ) && $field_type ) {
	$default_icons = array(
		'username'         => 'user',
		'email'            => 'envelope',
		'password'         => 'lock',
		'first_name'       => 'user',
		'last_name'        => 'user',
		'confirm_password' => 'lock',
	);
	$field_icon    = $default_icons[ $field_type ] ?? 'user';
}

// Determine if we should show labels and icons.
$should_show_labels = ( false !== $show_label ) ? $parent_show_labels : false;
$should_show_icons  = $parent_show_icons;

// Merge context values into attributes (like form-icon pattern).
$attributes['textColor']            = $attributes['textColor'] ?? $block->context['spectra-pro/form/inputTextColor'] ?? '';
$attributes['textColorHover']       = $attributes['textColorHover'] ?? $block->context['spectra-pro/form/inputTextColorHover'] ?? '';
$attributes['backgroundColor']      = $attributes['backgroundColor'] ?? $block->context['spectra-pro/form/inputBgColor'] ?? '';
$attributes['backgroundColorHover'] = $attributes['backgroundColorHover'] ?? $block->context['spectra-pro/form/inputBgColorHover'] ?? '';

// Style and class configurations - Spectra-v3 pattern (let BlockAttributes get values from attributes).
$config = array(
	array( 'key' => 'textColor' ),
	array( 'key' => 'textColorHover' ),
	array( 'key' => 'backgroundColor' ),
	array( 'key' => 'backgroundColorHover' ),
);

$additional_classes = array(
	'spectra-pro-form-input-wrapper',
	'spectra-pro-form-input-wrapper--' . esc_attr( $field_type ),
);

$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, array( 'id' => 'input-wrapper-' . esc_attr( $field_type ) . '-' . esc_attr( $block_id ) ), $additional_classes );

// Provide context to child blocks using the correct context keys.
$block->context['spectra-pro/form-input/fieldType']        = $field_type;
$block->context['spectra-pro/form-input/fieldLabel']       = $effective_label;
$block->context['spectra-pro/form-input/fieldPlaceholder'] = $effective_placeholder;
$block->context['spectra-pro/form-input/fieldIcon']        = $field_icon;
$block->context['spectra-pro/form-input/isRequired']       = $is_required;
$block->context['spectra-pro/form-input/fieldId']          = $field_id;

// Prepare data for view.
$data = array(
	'wrapper_attributes' => $wrapper_attributes,
	'content'            => $content, // Use child blocks content.
	'tag_name'           => $wrapper_tag,
);

// Return the view file.
return 'file:./view.php';
