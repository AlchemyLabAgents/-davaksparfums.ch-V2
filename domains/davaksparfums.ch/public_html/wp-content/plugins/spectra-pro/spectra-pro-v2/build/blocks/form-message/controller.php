<?php
/**
 * Controller for rendering the block.
 * 
 * @since 2.0.0
 *
 * @package SpectraPro\Blocks\FormMessage
 */

use Spectra\Helpers\BlockAttributes;

// Get attributes with defaults.
$message_type    = $attributes['messageType'] ?? 'success';
$message_text    = $attributes['messageText'] ?? 'Operation completed successfully.';
$is_visible      = $attributes['isVisible'] ?? false;
$dismissible     = $attributes['dismissible'] ?? true;
$auto_hide       = $attributes['autoHide'] ?? false;
$auto_hide_delay = $attributes['autoHideDelay'] ?? 5000;


// Get context values (matching render.js behavior).
$context   = $block->context ?? array();
$form_type = $context['spectra-pro/form/formType'] ?? '';
$block_id  = $context['spectra-pro/form/block_id'] ?? 'default';

// Get demo message toggle states.
$demo_success_message = $context['spectra-pro/form/demoSuccessMessage'] ?? false;
$demo_error_message   = $context['spectra-pro/form/demoErrorMessage'] ?? false;

// Override message type with context if available.
$message_type = $context['spectra-pro/form/messageType'] ?? $message_type;

// Get message text based on type - matching render.js logic exactly.
// Context has priority, fallback to hardcoded defaults (NOT attribute).
if ( 'success' === $message_type ) {
	// Try context values in order, use first non-empty value.
	$message_text = ! empty( $context['spectra-pro/form/successMessageText'] )
		? $context['spectra-pro/form/successMessageText']
		: ( ! empty( $context['spectra-pro/form/messageSuccessRegistration'] )
			? $context['spectra-pro/form/messageSuccessRegistration']
			: 'Registration successful!' );
} elseif ( 'error' === $message_type ) {
	// Try context values in order, use first non-empty value.
	$message_text = ! empty( $context['spectra-pro/form/errorMessageText'] )
		? $context['spectra-pro/form/errorMessageText']
		: ( ! empty( $context['spectra-pro/form/messageOtherError'] )
			? $context['spectra-pro/form/messageOtherError']
			: 'An error occurred.' );
}

$is_visible = isset( $context['spectra-pro/form/isVisible'] )
	? $context['spectra-pro/form/isVisible']
	: $is_visible;

// Check if we're in editor context (block editor or site editor).
$is_editor = ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ||
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only used to detect editor context, not processing form data.
			( isset( $_GET['context'] ) && 'edit' === $_GET['context'] ) ||
			is_admin();

// IMPORTANT: Always render messages in template (both editor and frontend).
// Demo toggles only control CSS visibility for preview in editor.
// Frontend: JavaScript will show/hide based on actual form submission result.
if ( $is_editor ) {
	// Editor: show/hide based on demo toggles for preview purposes only.
	$demo_visible = false;
	if ( 'success' === $message_type && $demo_success_message ) {
		$demo_visible = true;
	}
	if ( 'error' === $message_type && $demo_error_message ) {
		$demo_visible = true;
	}
	$is_visible = $demo_visible;
} else {
	// Frontend: always render in template but hidden by default.
	// JavaScript will show the appropriate message on form submission success/error.
	$is_visible = false;
}

// Generate message ID for accessibility.
$message_id = 'message-' . $message_type . '-' . $block_id;

// Get message icon based on type - using dashicons like v1.
$message_icon_classes = array(
	'success' => 'dashicons-yes-alt',
	'error'   => 'dashicons-warning',
	'warning' => 'dashicons-warning',
	'info'    => 'dashicons-info',
);
$message_icon_class   = $message_icon_classes[ $message_type ] ?? $message_icon_classes['info'];

// Data attributes for accessibility.
$element_attributes = array(
	'id'          => $message_id,
	'role'        => 'alert',
	'aria-live'   => 'polite',
	'aria-atomic' => 'true',
);

if ( $auto_hide && $auto_hide_delay > 0 ) {
	$element_attributes['data-auto-hide'] = $auto_hide_delay;
}

// Merge context values into attributes (like render.js pattern).
$attributes['successMessageBackground']  = $attributes['successMessageBackground'] ?? $block->context['spectra-pro/form/successMessageBackground'] ?? '';
$attributes['successMessageColor']       = $attributes['successMessageColor'] ?? $block->context['spectra-pro/form/successMessageColor'] ?? '';
$attributes['successMessageBorderColor'] = $attributes['successMessageBorderColor'] ?? $block->context['spectra-pro/form/successMessageBorderColor'] ?? '';
$attributes['errorMessageBackground']    = $attributes['errorMessageBackground'] ?? $block->context['spectra-pro/form/errorMessageBackground'] ?? '';
$attributes['errorMessageColor']         = $attributes['errorMessageColor'] ?? $block->context['spectra-pro/form/errorMessageColor'] ?? '';
$attributes['errorMessageBorderColor']   = $attributes['errorMessageBorderColor'] ?? $block->context['spectra-pro/form/errorMessageBorderColor'] ?? '';
$attributes['textColor']                 = $attributes['textColor'] ?? $block->context['spectra-pro/form/textColor'] ?? '';
$attributes['backgroundColor']           = $attributes['backgroundColor'] ?? $block->context['spectra-pro/form/backgroundColor'] ?? '';

// Style and class configurations - Spectra-specific CSS variables to match style.scss.
$config = array(
	array(
		'key'        => 'textColor',
		'css_var'    => '--spectra-text-color',
		'class_name' => 'spectra-text-color',
	),
	array(
		'key'        => 'backgroundColor',
		'css_var'    => '--spectra-background-color',
		'class_name' => 'spectra-background-color',
	),
	array(
		'key'        => 'successMessageBackground',
		'css_var'    => '--spectra-success-message-background',
		'class_name' => 'spectra-success-message-background',
	),
	array(
		'key'        => 'successMessageColor',
		'css_var'    => '--spectra-success-message-color',
		'class_name' => 'spectra-success-message-color',
	),
	array(
		'key'        => 'successMessageBorderColor',
		'css_var'    => '--spectra-success-message-border-color',
		'class_name' => 'spectra-success-message-border-color',
	),
	array(
		'key'        => 'errorMessageBackground',
		'css_var'    => '--spectra-error-message-background',
		'class_name' => 'spectra-error-message-background',
	),
	array(
		'key'        => 'errorMessageColor',
		'css_var'    => '--spectra-error-message-color',
		'class_name' => 'spectra-error-message-color',
	),
	array(
		'key'        => 'errorMessageBorderColor',
		'css_var'    => '--spectra-error-message-border-color',
		'class_name' => 'spectra-error-message-border-color',
	),
);

// Additional classes.
$additional_classes = array(
	'spectra-pro-form-message--' . esc_attr( $message_type ),
	'spectra-pro-form-message--' . esc_attr( $form_type ),
	$is_visible ? 'spectra-pro-form-message--visible' : 'spectra-pro-form-message--hidden',
);

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, $element_attributes, $additional_classes );

// Form messages are always div tags.
$tag_name = 'div';

// Render the block.
return 'file:./view.php';
