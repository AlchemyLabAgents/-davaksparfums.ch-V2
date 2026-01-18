<?php
/**
 * Controller for rendering the block.
 * 
 * @since 2.0.0
 *
 * @package SpectraPro\Blocks\FormLink
 */

use Spectra\Helpers\BlockAttributes;
use Spectra\Helpers\Core;

// Get context values.
$context   = $block->context ?? array();
$form_type = $context['spectra-pro/form/formType'] ?? '';
$block_id  = $context['spectra-pro/form/block_id'] ?? 'default';

// Get link type from attributes.
$link_type = $attributes['linkType'] ?? 'forgot_password';

// Get color attributes.
$text_color       = $attributes['textColor'] ?? '';
$text_color_hover = $attributes['textColorHover'] ?? '';

// Check if this link should be shown based on parent context.
$show_forgot_password = $context['spectra-pro/form/showForgotPassword'] ?? true;
$show_register_link   = $context['spectra-pro/form/showRegisterLink'] ?? true;

// Early return if link should not be shown.
if ( 'forgot_password' === $link_type && ! $show_forgot_password ) {
	return '';
}

if ( 'register_info' === $link_type && ! $show_register_link ) {
	return '';
}

// Get link text and URL from parent context based on link type.
$link_text   = '';
$link_url    = '#';
$link_target = '_self';

switch ( $link_type ) {
	case 'forgot_password':
		$link_text           = $context['spectra-pro/form/forgotPasswordText'] ?? __( 'Forgot Password', 'spectra-pro' );
		$forgot_password_url = $context['spectra-pro/form/forgotPasswordUrl'] ?? '';

		// Fallback to WordPress default lost password page if URL is empty or just '#'.
		if ( empty( $forgot_password_url ) || '#' === $forgot_password_url ) {
			$link_url = wp_lostpassword_url();
		} else {
			$link_url = $forgot_password_url;
		}
		break;

	case 'register_info':
		$link_text         = $context['spectra-pro/form/registerLinkText'] ?? __( 'Register', 'spectra-pro' );
		$register_link_url = $context['spectra-pro/form/registerLinkUrl'] ?? '';

		// Fallback to WordPress default registration page if URL is empty or just '#'.
		if ( empty( $register_link_url ) || '#register' === $register_link_url || '#' === $register_link_url ) {
			$link_url = wp_registration_url();
		} else {
			$link_url = $register_link_url;
		}
		break;

	case 'login_info':
		$link_text = __( 'Already have an account? Login', 'spectra-pro' );
		// Set URL to WordPress login page.
		$link_url = wp_login_url();
		break;

	default:
		$link_text = __( 'Link', 'spectra-pro' );
		$link_url  = '#';
		break;
}//end switch

// Add interactivity attributes for forgot password.
$link_data_attributes = array();
if ( 'forgot_password' === $link_type ) {
	$link_data_attributes['data-wp-on--click'] = 'spectra-pro/login.actions.onForgotPassword';
}

// Set the attributes with fallback from parent context - Spectra-v3 pattern.
$text_color             = $attributes['textColor'] ?? $block->context['spectra-pro/form/linkColor'] ?? '';
$text_color_hover       = $attributes['textColorHover'] ?? $block->context['spectra-pro/form/linkColorHover'] ?? '';
$background_color       = $attributes['backgroundColor'] ?? '';
$background_color_hover = $attributes['backgroundColorHover'] ?? '';

// Override attributes with context values for BlockAttributes helper.
$attributes['textColor']            = $text_color;
$attributes['textColorHover']       = $text_color_hover;
$attributes['backgroundColor']      = $background_color;
$attributes['backgroundColorHover'] = $background_color_hover;

// Style and class configurations - Spectra-specific CSS variables to match style.scss.
$config = array(
	array(
		'key'        => 'textColor',
		'css_var'    => '--spectra-link-color',
		'class_name' => 'spectra-link-color',
	),
	array(
		'key'        => 'textColorHover',
		'css_var'    => '--spectra-link-color-hover',
		'class_name' => 'spectra-link-color-hover',
	),
	array(
		'key'        => 'backgroundColor',
		'css_var'    => '--spectra-background-color',
		'class_name' => 'spectra-background-color',
	),
	array(
		'key'        => 'backgroundColorHover',
		'css_var'    => '--spectra-background-color-hover',
		'class_name' => 'spectra-background-color-hover',
	),
);

// Additional classes.
$additional_classes = array(
	'spectra-pro-form-link--' . esc_attr( $link_type ),
	'spectra-pro-form-link--' . esc_attr( $form_type ),
);

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, $link_data_attributes, $additional_classes );

// Form links are always div tags containing an anchor.
$tag_name = 'div';

// Render the block.
return 'file:./view.php';
