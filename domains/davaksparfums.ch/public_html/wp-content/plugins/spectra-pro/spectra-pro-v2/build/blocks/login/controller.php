<?php
/**
 * Login block controller.
 * Following loop-builder hybrid pattern.
 *
 * @since 2.0.0
 * @package Spectra Pro
 */

use Spectra\Helpers\HtmlSanitizer;
use Spectra\Helpers\Core;
use Spectra\Helpers\BlockAttributes;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get attributes with defaults.
$form_type = $attributes['formType'] ?? 'login';

$overall_alignment    = $attributes['overallAlignment'] ?? 'left';
$show_labels          = $attributes['showLabels'] ?? true;
$show_icons           = $attributes['showIcons'] ?? true;
$show_password_toggle = $attributes['showPasswordToggle'] ?? true;
$show_remember_me     = $attributes['showRememberMe'] ?? true;
$enable_recaptcha     = $attributes['enableRecaptcha'] ?? $attributes['reCaptchaEnable'] ?? false;
$recaptcha_site_key   = $attributes['recaptchaSiteKey'] ?? '';
$recaptcha_version    = $attributes['recaptchaVersion'] ?? $attributes['reCaptchaType'] ?? 'v2';

// Get reCAPTCHA site key from settings if not provided in attributes.
if ( empty( $recaptcha_site_key ) && $enable_recaptcha ) {
	if ( 'v2' === $recaptcha_version ) {
		$recaptcha_site_key = \UAGB_Admin_Helper::get_admin_settings_option( 'uag_recaptcha_site_key_v2', '' );
	} else {
		$recaptcha_site_key = \UAGB_Admin_Helper::get_admin_settings_option( 'uag_recaptcha_site_key_v3', '' );
	}
}
$block_id = $attributes['block_id'] ?? 'preview';
$tag_name = $attributes['tagName'] ?? 'div';
$anchor   = $attributes['anchor'] ?? '';

// Background-related attributes.
$background                = $attributes['background'] ?? array();
$background_gradient       = $attributes['backgroundGradient'] ?? '';
$background_gradient_hover = $attributes['backgroundGradientHover'] ?? '';
$dimRatio                  = ( isset( $attributes['dimRatio'] ) && is_numeric( $attributes['dimRatio'] ) ? ( $attributes['dimRatio'] / 100 ) : null );

// Form content attributes.
$show_welcome_message = $attributes['showWelcomeMessage'] ?? true;
$welcome_message      = $attributes['welcomeMessage'] ?? __( 'Welcome Back', 'spectra-pro' );
$username_label       = $attributes['usernameLabel'] ?? __( 'Username or Email', 'spectra-pro' );
$username_placeholder = $attributes['usernamePlaceholder'] ?? __( 'Enter your username or email', 'spectra-pro' );
$password_label       = $attributes['passwordLabel'] ?? __( 'Password', 'spectra-pro' );
$password_placeholder = $attributes['passwordPlaceholder'] ?? __( 'Enter your password', 'spectra-pro' );
$submit_button_text   = $attributes['submitButtonText'] ?? __( 'Log In', 'spectra-pro' );
$remember_me_text     = $attributes['rememberMeText'] ?? __( 'Remember Me', 'spectra-pro' );
$remember_label       = $attributes['rememberLabel'] ?? __( 'Remember Me', 'spectra-pro' );
$forgot_password_text = $attributes['forgotPasswordText'] ?? __( 'Forgot Password', 'spectra-pro' );
$register_link_text   = $attributes['registerLinkText'] ?? __( 'Don\'t have an account? Register', 'spectra-pro' );

// URLs and redirects.
$forgot_password_url = $attributes['forgotPasswordUrl'] ?? wp_lostpassword_url();
$register_url        = $attributes['registerLinkUrl'] ?? wp_registration_url();
$redirect_url        = '';

$register_url = $attributes['registerLinkUrl'] ?? '';

// Handle redirect URL from LinkControl (can be string or object).
if ( ! empty( $attributes['redirectAfterLoginURL'] ) ) {
	if ( is_array( $attributes['redirectAfterLoginURL'] ) && isset( $attributes['redirectAfterLoginURL']['url'] ) ) {
		$redirect_url = $attributes['redirectAfterLoginURL']['url'];
	} elseif ( is_string( $attributes['redirectAfterLoginURL'] ) ) {
		$redirect_url = $attributes['redirectAfterLoginURL'];
	}
}

// Handle logout redirect URL from LinkControl (can be string or object).
$logout_redirect_url = '';
if ( ! empty( $attributes['redirectAfterLogoutURL'] ) ) {
	if ( is_array( $attributes['redirectAfterLogoutURL'] ) && isset( $attributes['redirectAfterLogoutURL']['url'] ) ) {
		$logout_redirect_url = $attributes['redirectAfterLogoutURL']['url'];
	} elseif ( is_string( $attributes['redirectAfterLogoutURL'] ) ) {
		$logout_redirect_url = $attributes['redirectAfterLogoutURL'];
	}
}

$redirect_on_success = ! empty( $redirect_url );

// Messages.
$success_message_text = $attributes['successMessageText'] ?? __( 'Login successful! Redirecting...', 'spectra-pro' );
$error_message_text   = $attributes['errorMessageText'] ?? __( 'Login failed. Please check your credentials.', 'spectra-pro' );

// Generate unique form ID.
$form_id = 'spectra-pro-login-form-' . $block_id;

// Check if any breakpoint has video background, image background or overlay.
$has_video_background   = false;
$has_responsive_image   = false;
$responsive_controls    = $attributes['responsiveControls'] ?? array();
$video_background       = null;
$has_responsive_overlay = false;
foreach ( array( 'lg', 'md', 'sm' ) as $device ) {
	if ( isset( $responsive_controls[ $device ]['background']['type'] ) ) {
		if ( 'video' === $responsive_controls[ $device ]['background']['type'] ) {
			$has_video_background = true;
			// Store the first video found.
			if ( null === $video_background ) {
				$video_background = $responsive_controls[ $device ]['background'];
			}
		} elseif ( 'image' === $responsive_controls[ $device ]['background']['type'] ) {
			$has_responsive_image = true;
		}
	}
}

// If we found a video background in any responsive breakpoint, ensure we render the video element.
// Even if desktop has no background, we need the video element for responsive switching.
if ( $has_video_background && null !== $video_background ) {
	// If background is not set or is 'none', use the video background to ensure video element renders.
	if ( ! $background || ( isset( $background['type'] ) && 'none' === $background['type'] ) ) {
		$background = $video_background;
	}
}

// Background detection and styling.
$background_type      = $background['type'] ?? '';
$has_image_background = 'image' === $background_type;
$has_border_radius    = ! empty( $attributes['style']['border']['radius'] );

$background_styles = Core::get_background_image_styles( $background, $background_gradient, $background_gradient_hover );

// WordPress Interactivity API context (following loop-builder pattern).
$login_contexts = array(
	'formType'                   => $form_type,
	'blockId'                    => $block_id,
	'formId'                     => $form_id,
	'isLoggedIn'                 => is_user_logged_in(),
	'isSubmitting'               => false,
	'message'                    => '',
	'messageType'                => '',
	
	// Form settings.
	'showLabels'                 => $show_labels,
	'showIcons'                  => $show_icons,
	'showPasswordToggle'         => $show_password_toggle,
	'showRememberMe'             => $show_remember_me,
	'showWelcomeMessage'         => $show_welcome_message,
	
	// Content.
	'welcomeMessage'             => $welcome_message,
	'usernameLabel'              => $username_label,
	'usernamePlaceholder'        => $username_placeholder,
	'passwordLabel'              => $password_label,
	'passwordPlaceholder'        => $password_placeholder,
	'submitButtonText'           => $submit_button_text,
	'rememberMeText'             => $remember_me_text,
	'rememberLabel'              => $remember_label,
	'forgotPasswordText'         => $forgot_password_text,
	'registerLinkText'           => $register_link_text,
	
	// URLs.
	'forgotPasswordUrl'          => $forgot_password_url,
	'registerUrl'                => $register_url,
	'redirectUrl'                => $redirect_url,
	'logoutRedirectUrl'          => $logout_redirect_url,
	'redirectOnSuccess'          => $redirect_on_success,
	'loginRedirectURL'           => $redirect_url,
	
	// Messages.
	'successMessageText'         => $success_message_text,
	'errorMessageText'           => $error_message_text,
	
	// reCAPTCHA.
	'enableRecaptcha'            => $enable_recaptcha,
	'reCaptchaEnable'            => $enable_recaptcha,
	'recaptchaVersion'           => $recaptcha_version,
	'reCaptchaType'              => $recaptcha_version,
	'recaptchaSiteKey'           => $recaptcha_site_key,
	'hidereCaptchaBatch'         => $attributes['hidereCaptchaBatch'] ?? false,
	
	// AJAX.
	'ajaxUrl'                    => admin_url( 'admin-ajax.php' ),
	'nonce'                      => wp_create_nonce( 'spectra_pro_v2_login_nonce' ),
	
	// Error messages for field validation.
	'errorMessages'              => array(
		'username'  => __( 'Username or email is required.', 'spectra-pro' ),
		'password'  => __( 'Password is required.', 'spectra-pro' ),
		'recaptcha' => __( 'Please complete the reCAPTCHA verification.', 'spectra-pro' ),
	),
	
	// Color context for child blocks - following Spectra-v3 accordion pattern.
	'labelColor'                 => $attributes['labelColor'] ?? '',
	'labelColorHover'            => $attributes['labelColorHover'] ?? '',
	'inputTextColor'             => $attributes['inputTextColor'] ?? '',
	'inputTextColorHover'        => $attributes['inputTextColorHover'] ?? '',
	'inputBgColor'               => $attributes['inputBgColor'] ?? '',
	'inputBgColorHover'          => $attributes['inputBgColorHover'] ?? '',
	'inputBorderColor'           => $attributes['inputBorderColor'] ?? '',
	'inputBorderColorHover'      => $attributes['inputBorderColorHover'] ?? '',
	'inputBorderColorFocus'      => $attributes['inputBorderColorFocus'] ?? '',
	'inputPlaceholderColor'      => $attributes['inputPlaceholderColor'] ?? '',
	'inputPlaceholderColorHover' => $attributes['inputPlaceholderColorHover'] ?? '',
	'inputTextColorActive'       => $attributes['inputTextColorActive'] ?? '',
	'inputBgColorActive'         => $attributes['inputBgColorActive'] ?? '',
	'inputBorderColorActive'     => $attributes['inputBorderColorActive'] ?? '',
	'iconColor'                  => $attributes['iconColor'] ?? '',
	'iconColorHover'             => $attributes['iconColorHover'] ?? '',
	'fieldsIconSize'             => $attributes['fieldsIconSize'] ?? '',
	'fieldsIconColor'            => $attributes['fieldsIconColor'] ?? '',
	'eyeIconSize'                => $attributes['eyeIconSize'] ?? '16px',
	'eyeIconColor'               => $attributes['eyeIconColor'] ?? '',
	'inputFieldBgColor'          => $attributes['inputFieldBgColor'] ?? '',
	'inputFieldBgColorHover'     => $attributes['inputFieldBgColorHover'] ?? '',
	'inputFieldBgColorActive'    => $attributes['inputFieldBgColorActive'] ?? '',
);

// Style and class configurations - matching render.js exactly + child block colors with CSS variables.
$config = array(
	// Parent login block colors.
	array( 'key' => 'textColor' ),
	array( 'key' => 'backgroundColor' ),
	array( 'key' => 'backgroundColorHover' ),
	array( 'key' => 'backgroundGradient' ),
	array( 'key' => 'backgroundGradientHover' ),

	
	// Child block colors as CSS variables for frontend context inheritance.
	array(
		'key'        => 'labelColor',
		'css_var'    => '--spectra-label-color',
		'class_name' => 'spectra-label-color',
	),
	array(
		'key'        => 'labelColorHover',
		'css_var'    => '--spectra-label-color-hover',
		'class_name' => 'spectra-label-color-hover',
	),
	array(
		'key'        => 'inputTextColor',
		'css_var'    => '--spectra-input-text-color',
		'class_name' => 'spectra-input-text-color',
	),
	array(
		'key'        => 'inputBgColor',
		'css_var'    => '--spectra-input-bg-color',
		'class_name' => 'spectra-input-bg-color',
	),
	array(
		'key'        => 'inputBgColorHover',
		'css_var'    => '--spectra-input-bg-color-hover',
		'class_name' => 'spectra-input-bg-color-hover',
	),
	array(
		'key'        => 'inputBorderColor',
		'css_var'    => '--spectra-input-border-color',
		'class_name' => 'spectra-input-border-color',
	),
	array(
		'key'        => 'inputBorderColorHover',
		'css_var'    => '--spectra-input-border-color-hover',
		'class_name' => 'spectra-input-border-color-hover',
	),
	array(
		'key'        => 'inputBorderColorFocus',
		'css_var'    => '--spectra-input-border-color-focus',
		'class_name' => 'spectra-input-border-color-focus',
	),
	array(
		'key'        => 'inputPlaceholderColor',
		'css_var'    => '--spectra-input-placeholder-color',
		'class_name' => 'spectra-input-placeholder-color',
	),
	array(
		'key'        => 'inputPlaceholderColorHover',
		'css_var'    => '--spectra-input-placeholder-color-hover',
		'class_name' => 'spectra-input-placeholder-color-hover',
	),
	array(
		'key'        => 'inputTextColorActive',
		'css_var'    => '--spectra-input-text-color-active',
		'class_name' => 'spectra-input-text-color-active',
	),
	array(
		'key'        => 'inputBgColorActive',
		'css_var'    => '--spectra-input-bg-color-active',
		'class_name' => 'spectra-input-bg-color-active',
	),
	array(
		'key'        => 'inputBorderColorActive',
		'css_var'    => '--spectra-input-border-color-active',
		'class_name' => 'spectra-input-border-color-active',
	),
	array(
		'key'        => 'buttonTextColor',
		'css_var'    => '--spectra-button-text-color',
		'class_name' => 'spectra-button-text-color',
	),
	array(
		'key'        => 'buttonBgColor',
		'css_var'    => '--spectra-button-bg-color',
		'class_name' => 'spectra-button-bg-color',
	),
	array(
		'key'        => 'buttonBgColorHover',
		'css_var'    => '--spectra-button-bg-color-hover',
		'class_name' => 'spectra-button-bg-color-hover',
	),
	array(
		'key'        => 'buttonBorderColor',
		'css_var'    => '--spectra-button-border-color',
		'class_name' => 'spectra-button-border-color',
	),
	array(
		'key'        => 'buttonBorderColorHover',
		'css_var'    => '--spectra-button-border-color-hover',
		'class_name' => 'spectra-button-border-color-hover',
	),
	array(
		'key'        => 'linkColor',
		'css_var'    => '--spectra-link-color',
		'class_name' => 'spectra-link-color',
	),
	array(
		'key'        => 'linkColorHover',
		'css_var'    => '--spectra-link-color-hover',
		'class_name' => 'spectra-link-color-hover',
	),
	array(
		'key'        => 'iconColor',
		'css_var'    => '--spectra-icon-color',
		'class_name' => 'spectra-icon-color',
	),
	array(
		'key'        => 'iconColorHover',
		'css_var'    => '--spectra-icon-color-hover',
		'class_name' => 'spectra-icon-color-hover',
	),
	array(
		'key'        => 'checkboxBackgroundColor',
		'css_var'    => '--spectra-checkbox-background-color',
		'class_name' => 'spectra-checkbox-background-color',
	),
	array(
		'key'        => 'checkboxColor',
		'css_var'    => '--spectra-checkbox-color',
		'class_name' => 'spectra-checkbox-color',
	),
	array(
		'key'        => 'checkboxBorderColor',
		'css_var'    => '--spectra-checkbox-border-color',
		'class_name' => 'spectra-checkbox-border-color',
	),
	array(
		'key'        => 'checkboxGlowColor',
		'css_var'    => '--spectra-checkbox-glow-color',
		'class_name' => 'spectra-checkbox-glow-color',
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

// Only add dimRatio to config if it has a valid numeric value.
if ( null !== $dimRatio ) {
	$config[] = array(
		'key'        => 'dimRatio',
		'css_var'    => '--spectra-overlay-opacity',
		'class_name' => 'spectra-dim-ratio',
		'value'      => $dimRatio,
	);
}

// Custom classes for background support.
$custom_classes = array(
	// Video background class is required for proper positioning (from common.scss).
	( 'video' === $background_type || $has_video_background ) ? 'spectra-background-video' : '',
	// These classes are used for overflow handling with border-radius.
	$has_video_background ? 'has-video-background' : '',
	( $has_image_background || $has_responsive_image ) ? 'has-image-background' : '',
	// Add overlay class when overlay is used.
	$has_responsive_overlay ? 'spectra-background-overlay' : '',
	// Add background image class.
	( 'image' === $background_type || $has_responsive_image ) ? 'spectra-background-image' : '',
	'spectra-pro-login-form',
	'wp-block-button',
	'spectra-login-form--align-' . esc_attr( $overall_alignment ),
	'spectra-overlay-color', // For overlay support.
);

// Add responsive video data as data attribute for JavaScript.
$responsive_video_data = array();
if ( ! empty( $responsive_controls ) ) {
	foreach ( array( 'lg', 'md', 'sm' ) as $device ) {
		if ( isset( $responsive_controls[ $device ]['background'], $responsive_controls[ $device ]['background']['type'] ) && 
		'video' === $responsive_controls[ $device ]['background']['type'] && 
		! empty( $responsive_controls[ $device ]['background']['media']['url'] ) ) {
			$responsive_video_data[ $device ] = $responsive_controls[ $device ]['background']['media']['url'];
		}
	}
}

$additional_attributes = array( 'id' => $anchor );
if ( ! empty( $responsive_video_data ) ) {
	$additional_attributes['data-responsive-videos'] = wp_json_encode( $responsive_video_data );
}

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, $additional_attributes, $custom_classes, $background_styles );

// Use template-defined child blocks following Spectra-v3 pattern.
$rendered_content = $content;

// Prepare data for view (following loop-builder pattern).
$data = array(
	'wrapper_attributes' => $wrapper_attributes,
	'login_contexts'     => $login_contexts,
	'content'            => $rendered_content, // Dynamically rendered child blocks.
	'tag_name'           => $tag_name,
	'form_id'            => $form_id,
	'block_id'           => $block_id,
	'attributes'         => $attributes,
	'background'         => $background, // Add background for view.
);

// Return the view file.
return 'file:./view.php';
