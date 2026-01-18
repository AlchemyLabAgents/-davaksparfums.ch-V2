<?php
/**
 * Register block controller.
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
$form_type = $attributes['formType'] ?? 'register';

$overall_alignment    = $attributes['overallAlignment'] ?? 'left';
$show_labels          = $attributes['showLabels'] ?? true;
$show_icons           = $attributes['showIcons'] ?? true;
$show_password_toggle = $attributes['showPasswordToggle'] ?? true;
$enable_recaptcha     = $attributes['reCaptchaEnable'] ?? $attributes['enableRecaptcha'] ?? false;
$recaptcha_site_key   = $attributes['recaptchaSiteKey'] ?? '';
$recaptcha_version    = $attributes['reCaptchaType'] ?? $attributes['recaptchaVersion'] ?? 'v2';

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

// Form content attributes.
$show_welcome_message         = $attributes['showWelcomeMessage'] ?? true;
$welcome_message              = $attributes['welcomeMessage'] ?? __( 'Create Account', 'spectra-pro' );
$username_label               = $attributes['usernameLabel'] ?? __( 'Username', 'spectra-pro' );
$username_placeholder         = $attributes['usernamePlaceholder'] ?? __( 'Enter your username', 'spectra-pro' );
$email_label                  = $attributes['emailLabel'] ?? __( 'Email Address', 'spectra-pro' );
$email_placeholder            = $attributes['emailPlaceholder'] ?? __( 'Enter your email address', 'spectra-pro' );
$password_label               = $attributes['passwordLabel'] ?? __( 'Password', 'spectra-pro' );
$password_placeholder         = $attributes['passwordPlaceholder'] ?? __( 'Enter your password', 'spectra-pro' );
$confirm_password_label       = $attributes['confirmPasswordLabel'] ?? __( 'Confirm Password', 'spectra-pro' );
$confirm_password_placeholder = $attributes['confirmPasswordPlaceholder'] ?? __( 'Confirm your password', 'spectra-pro' );
$first_name_label             = $attributes['firstNameLabel'] ?? __( 'First Name', 'spectra-pro' );
$first_name_placeholder       = $attributes['firstNamePlaceholder'] ?? __( 'Enter your first name', 'spectra-pro' );
$last_name_label              = $attributes['lastNameLabel'] ?? __( 'Last Name', 'spectra-pro' );
$last_name_placeholder        = $attributes['lastNamePlaceholder'] ?? __( 'Enter your last name', 'spectra-pro' );
$submit_button_text           = $attributes['submitButtonText'] ?? __( 'Register', 'spectra-pro' );
$login_link_text              = $attributes['loginLinkText'] ?? __( 'Already have an account? Login', 'spectra-pro' );

// Field visibility.
$show_first_name       = $attributes['showFirstName'] ?? false;
$show_last_name        = $attributes['showLastName'] ?? false;
$show_username         = $attributes['showUsername'] ?? true;
$show_email            = $attributes['showEmail'] ?? true;
$show_password         = $attributes['showPassword'] ?? true;
$show_confirm_password = $attributes['showConfirmPassword'] ?? true;

// Background-related attributes.
$background                = $attributes['background'] ?? array();
$background_gradient       = $attributes['backgroundGradient'] ?? '';
$background_gradient_hover = $attributes['backgroundGradientHover'] ?? '';
$dimRatio                  = ( isset( $attributes['dimRatio'] ) && is_numeric( $attributes['dimRatio'] ) ? ( $attributes['dimRatio'] / 100 ) : null );

// URLs and redirects.
$login_url                         = $attributes['loginUrl'] ?? wp_login_url();
$redirect_url                      = '';
$auto_register_redirect_url_object = $attributes['autoRegisterRedirectURL'] ?? array( 'url' => '' );
$auto_register_redirect_url        = $auto_register_redirect_url_object['url'] ?? '';
$redirect_on_success               = ! empty( $auto_register_redirect_url );

// After registration actions.
$after_register_actions  = $attributes['afterRegisterActions'] ?? array( 'autoLogin' );
$auto_login              = in_array( 'autoLogin', $after_register_actions );
$send_email              = in_array( 'sendMail', $after_register_actions );
$redirect_after_register = in_array( 'redirect', $after_register_actions );

// Email template settings.
$email_template_type         = $attributes['emailTemplateType'] ?? 'default';
$email_template_subject      = $attributes['emailTemplateSubject'] ?? __( 'Welcome! Please verify your email', 'spectra-pro' );
$email_template_message      = $attributes['emailTemplateMessage'] ?? __( 'Thank you for registering. Please click the link below to verify your email address.', 'spectra-pro' );
$email_template_message_type = $attributes['emailTemplateMessageType'] ?? 'default';

// Registration settings.
$new_user_role   = $attributes['newUserRole'] ?? 'subscriber';
$show_login_info = $attributes['showLoginInfo'] ?? true;
$login_info      = $attributes['loginInfo'] ?? __( 'Already have an account?', 'spectra-pro' );
$btn_login_label = $attributes['btnLoginLabel'] ?? __( 'Login', 'spectra-pro' );
$btn_login_link  = $attributes['btnLoginLink'] ?? wp_login_url();

// Messages.
$success_message_text = $attributes['successMessageText'] ?? __( 'Registration successful!', 'spectra-pro' );
$error_message_text   = $attributes['errorMessageText'] ?? __( 'Registration failed. Please try again.', 'spectra-pro' );

// Generate unique form ID.
$form_id = 'spectra-pro-register-form-' . $block_id;

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

// Add layout styles to match editor (render.js line 232).
$layout_styles = array(
	'display'        => 'flex',
	'flex-direction' => 'column',
);

// Merge layout styles with background styles.
$background_styles = array_merge( $layout_styles, $background_styles );

$register_contexts = array(
	'formType'                        => $form_type,
	'blockId'                         => $block_id,
	'formId'                          => $form_id,
	'isLoggedIn'                      => is_user_logged_in(),
	'isSubmitting'                    => false,
	'message'                         => '',
	'messageType'                     => '',
	
	// Form settings.
	'showLabels'                      => $show_labels,
	'showIcons'                       => $show_icons,
	'showPasswordToggle'              => $show_password_toggle,
	'showWelcomeMessage'              => $show_welcome_message,
	
	// Field visibility.
	'showFirstName'                   => $show_first_name,
	'showLastName'                    => $show_last_name,
	'showUsername'                    => $show_username,
	'showEmail'                       => $show_email,
	'showPassword'                    => $show_password,
	'showConfirmPassword'             => $show_confirm_password,
	
	// Content.
	'welcomeMessage'                  => $welcome_message,
	'usernameLabel'                   => $username_label,
	'usernamePlaceholder'             => $username_placeholder,
	'emailLabel'                      => $email_label,
	'emailPlaceholder'                => $email_placeholder,
	'passwordLabel'                   => $password_label,
	'passwordPlaceholder'             => $password_placeholder,
	'confirmPasswordLabel'            => $confirm_password_label,
	'confirmPasswordPlaceholder'      => $confirm_password_placeholder,
	'firstNameLabel'                  => $first_name_label,
	'firstNamePlaceholder'            => $first_name_placeholder,
	'lastNameLabel'                   => $last_name_label,
	'lastNamePlaceholder'             => $last_name_placeholder,
	'submitButtonText'                => $submit_button_text,
	'loginLinkText'                   => $login_link_text,
	
	// URLs.
	'loginUrl'                        => $login_url,
	'autoRegisterRedirectURL'         => $auto_register_redirect_url_object,
	'redirectOnSuccess'               => $redirect_on_success,
	
	// Registration settings.
	'afterRegisterActions'            => $after_register_actions,
	'autoLogin'                       => $auto_login,
	'sendEmail'                       => $send_email,
	'redirectAfterRegister'           => $redirect_after_register,
	
	// Email template settings.
	'emailTemplateType'               => $email_template_type,
	'emailTemplateSubject'            => $email_template_subject,
	'emailTemplateMessage'            => $email_template_message,
	'emailTemplateMessageType'        => $email_template_message_type,
	
	// Registration form settings.
	'newUserRole'                     => $new_user_role,
	'showLoginInfo'                   => $show_login_info,
	'loginInfo'                       => $login_info,
	'btnLoginLabel'                   => $btn_login_label,
	'btnLoginLink'                    => $btn_login_link,
	
	// Messages.
	'successMessageText'              => $success_message_text,
	'errorMessageText'                => $error_message_text,
	'messageSuccessRegistration'      => $attributes['messageSuccessRegistration'] ?? __( 'Registration successful!', 'spectra-pro' ),
	'messageInvalidEmailError'        => $attributes['messageInvalidEmailError'] ?? __( 'Please enter a valid email address.', 'spectra-pro' ),
	'messageEmailMissingError'        => $attributes['messageEmailMissingError'] ?? __( 'Email address is required.', 'spectra-pro' ),
	'messageEmailAlreadyUsedError'    => $attributes['messageEmailAlreadyUsedError'] ?? __( 'Email address is already in use.', 'spectra-pro' ),
	'messageInvalidUsernameError'     => $attributes['messageInvalidUsernameError'] ?? __( 'Please enter a valid username.', 'spectra-pro' ),
	'messageUsernameMissingError'     => $attributes['messageUsernameMissingError'] ?? __( 'Username is required.', 'spectra-pro' ),
	'messageUsernameAlreadyUsedError' => $attributes['messageUsernameAlreadyUsedError'] ?? __( 'Username is already taken.', 'spectra-pro' ),
	'messageInvalidPasswordError'     => $attributes['messageInvalidPasswordError'] ?? __( 'Password cannot be accepted. Please try something else.', 'spectra-pro' ),
	'messagePasswordConfirmError'     => $attributes['messagePasswordConfirmError'] ?? __( 'Passwords do not match.', 'spectra-pro' ),
	'messageTermsError'               => $attributes['messageTermsError'] ?? __( 'Please accept the terms and conditions.', 'spectra-pro' ),
	'messageOtherError'               => $attributes['messageOtherError'] ?? __( 'Something went wrong! Please try again.', 'spectra-pro' ),
	
	// reCAPTCHA.
	'enableRecaptcha'                 => $enable_recaptcha,
	'reCaptchaEnable'                 => $enable_recaptcha, // Legacy camelCase for register.js compatibility.
	'recaptchaVersion'                => $recaptcha_version,
	'reCaptchaType'                   => $recaptcha_version, // Legacy compatibility.
	'recaptchaSiteKey'                => $recaptcha_site_key,
	'hidereCaptchaBatch'              => $attributes['hidereCaptchaBatch'] ?? false,
	
	// AJAX.
	'ajaxUrl'                         => admin_url( 'admin-ajax.php' ),
	'ajax_url'                        => admin_url( 'admin-ajax.php' ), // Legacy snake_case for register.js compatibility.
	'nonce'                           => wp_create_nonce( 'spectra-pro-register-nonce' ),
	'postId'                          => get_the_ID(),
	'post_id'                         => get_the_ID(), // Legacy snake_case for register.js compatibility.
	'block_id'                        => $block_id, // Legacy snake_case for register.js compatibility.
	
	// WordPress version check.
	'wp_version'                      => version_compare( get_bloginfo( 'version' ), '5.5', '>=' ),
	
	// Color context for child blocks - following Spectra-v3 accordion pattern.
	'labelColor'                      => $attributes['labelColor'] ?? '',
	'labelColorHover'                 => $attributes['labelColorHover'] ?? '',
	'inputTextColor'                  => $attributes['inputTextColor'] ?? '',
	'inputTextColorHover'             => $attributes['inputTextColorHover'] ?? '',
	'inputBgColor'                    => $attributes['inputBgColor'] ?? '',
	'inputBgColorHover'               => $attributes['inputBgColorHover'] ?? '',
	'inputBorderColor'                => $attributes['inputBorderColor'] ?? '',
	'inputBorderColorHover'           => $attributes['inputBorderColorHover'] ?? '',
	'inputBorderColorFocus'           => $attributes['inputBorderColorFocus'] ?? '',
	'inputPlaceholderColor'           => $attributes['inputPlaceholderColor'] ?? '',
	'inputPlaceholderColorHover'      => $attributes['inputPlaceholderColorHover'] ?? '',
	'inputTextColorActive'            => $attributes['inputTextColorActive'] ?? '',
	'inputBgColorActive'              => $attributes['inputBgColorActive'] ?? '',
	'inputBorderColorActive'          => $attributes['inputBorderColorActive'] ?? '',
	'iconColor'                       => $attributes['iconColor'] ?? '',
	'iconColorHover'                  => $attributes['iconColorHover'] ?? '',
	'fieldsIconSize'                  => $attributes['fieldsIconSize'] ?? '',
	'fieldsIconColor'                 => $attributes['fieldsIconColor'] ?? '',
	'eyeIconSize'                     => $attributes['eyeIconSize'] ?? '16px',
	'eyeIconColor'                    => $attributes['eyeIconColor'] ?? '',
	'inputFieldBgColor'               => $attributes['inputFieldBgColor'] ?? '',
	'inputFieldBgColorHover'          => $attributes['inputFieldBgColorHover'] ?? '',
	'inputFieldBgColorActive'         => $attributes['inputFieldBgColorActive'] ?? '',
	'textColor'                       => $attributes['textColor'] ?? '',
	'fieldsBackground'                => $attributes['fieldsBackground'] ?? '',
	'fieldsBackgroundHover'           => $attributes['fieldsBackgroundHover'] ?? '',
	'fieldsBackgroundActive'          => $attributes['fieldsBackgroundActive'] ?? '',
	'placeholderColor'                => $attributes['placeholderColor'] ?? '',
	'placeholderColorHover'           => $attributes['placeholderColorHover'] ?? '',
	'placeholderColorActive'          => $attributes['placeholderColorActive'] ?? '',
	'fieldsColor'                     => $attributes['fieldsColor'] ?? '',
	'errorColor'                      => $attributes['errorColor'] ?? '',
	'errorColorHover'                 => $attributes['errorColorHover'] ?? '',
	'formBackgroundColor'             => $attributes['formBackgroundColor'] ?? '',
	
	// Demo message settings for editor preview.
	'demoSuccessMessage'              => $attributes['demoSuccessMessage'] ?? false,
	'demoErrorMessage'                => $attributes['demoErrorMessage'] ?? false,
	'demoFieldErrorMessage'           => $attributes['demoFieldErrorMessage'] ?? false,
	
	// Message styling context.
	'successMessageBackground'        => $attributes['successMessageBackground'] ?? '',
	'successMessageColor'             => $attributes['successMessageColor'] ?? '',
	'successMessageBorderColor'       => $attributes['successMessageBorderColor'] ?? '',
	'errorMessageBackground'          => $attributes['errorMessageBackground'] ?? '',
	'errorMessageColor'               => $attributes['errorMessageColor'] ?? '',
	'errorMessageBorderColor'         => $attributes['errorMessageBorderColor'] ?? '',
);

// Style and class configurations - matching render.js exactly + child block colors with CSS variables.
$config = array(
	// Parent register block colors.
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

// Custom classes.
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
	'spectra-pro-register-form',
	'spectra-pro-form-container',
	'wp-block-button',
	'spectra-register-form--align-' . esc_attr( $overall_alignment ),
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
	'register_contexts'  => $register_contexts,
	'content'            => $rendered_content, // Dynamically rendered child blocks.
	'tag_name'           => $tag_name,
	'form_id'            => $form_id,
	'block_id'           => $block_id,
	'attributes'         => $attributes,
	'background'         => $background, // Add background for view.
);

// Return the view file.
return 'file:./view.php';
