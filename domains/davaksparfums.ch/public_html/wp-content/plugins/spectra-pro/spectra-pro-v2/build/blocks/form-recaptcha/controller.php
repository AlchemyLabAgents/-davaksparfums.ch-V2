<?php
/**
 * Controller for rendering the block.
 * 
 * @since 2.0.0
 *
 * @package SpectraPro\Blocks\FormRecaptcha
 */

use Spectra\Helpers\BlockAttributes;

// Get attributes with defaults.
$recaptcha_version = $attributes['recaptchaVersion'] ?? 'v2';
$recaptcha_size    = $attributes['recaptchaSize'] ?? 'normal';
$recaptcha_theme   = $attributes['recaptchaTheme'] ?? 'light';
$is_enabled        = $attributes['isEnabled'] ?? true;

// Get context values (these can override attributes).
$context   = $block->context ?? array();
$form_type = $context['spectra-pro/form/formType'] ?? '';
$block_id  = $context['spectra-pro/form/block_id'] ?? 'default';

// Override with context if available.
$is_enabled        = isset( $context['spectra-pro/form/reCaptchaEnable'] ) 
	? $context['spectra-pro/form/reCaptchaEnable'] 
	: $is_enabled;
$recaptcha_version = $context['spectra-pro/form/reCaptchaType'] ?? $context['spectra-pro/form/recaptchaVersion'] ?? $recaptcha_version;
$recaptcha_size    = $context['spectra-pro/form/recaptchaSize'] ?? $recaptcha_size;
$recaptcha_theme   = $context['spectra-pro/form/recaptchaTheme'] ?? $recaptcha_theme;
$hide_batch        = $context['spectra-pro/form/hidereCaptchaBatch'] ?? false;

// Don't render if reCAPTCHA is not enabled.
if ( ! $is_enabled ) {
	return '';
}

// Get reCAPTCHA site key from settings.
if ( 'v2' === $recaptcha_version ) {
	$recaptcha_site_key = \UAGB_Admin_Helper::get_admin_settings_option( 'uag_recaptcha_site_key_v2', '' );
} else {
	$recaptcha_site_key = \UAGB_Admin_Helper::get_admin_settings_option( 'uag_recaptcha_site_key_v3', '' );
}

if ( ! is_string( $recaptcha_site_key ) ) {
	$recaptcha_site_key = '';
}

// Don't render if no site key is configured.
if ( empty( $recaptcha_site_key ) ) {
	// Return placeholder for admins.
	if ( current_user_can( 'manage_options' ) ) {
		return '<div class="spectra-pro-form-recaptcha__error">' . 
		esc_html__( 'reCAPTCHA site key not configured. Please set it in Spectra Pro settings.', 'spectra-pro' ) . 
		'</div>';
	}
	return '';
}

// Generate reCAPTCHA container ID.
$recaptcha_id = 'recaptcha-' . $recaptcha_version . '-' . $block_id;

// Style and class configurations.
$config = array(
	array( 'key' => 'backgroundColor' ),
	array( 'key' => 'borderColor' ),
);

// Additional classes.
$additional_classes = array(
	'spectra-pro-form-recaptcha--' . esc_attr( $recaptcha_version ),
	'spectra-pro-form-recaptcha--' . esc_attr( $form_type ),
);

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, array(), $additional_classes );

// Form reCAPTCHA are always div tags.
$tag_name = 'div';

// Add required scripts.
if ( 'v2' === $recaptcha_version ) {
	// Add the reCAPTCHA script if not already added.
	if ( ! wp_script_is( 'google-recaptcha', 'enqueued' ) ) {
		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- External CDN script, version managed by Google.
		wp_enqueue_script(
			'google-recaptcha',
			'https://www.google.com/recaptcha/api.js',
			array(),
			null,
			true
		);
	}
} else {
	// Add the reCAPTCHA v3 script if not already added.
	if ( ! wp_script_is( 'google-recaptcha-v3', 'enqueued' ) ) {
		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- External CDN script, version managed by Google.
		wp_enqueue_script(
			'google-recaptcha-v3',
			'https://www.google.com/recaptcha/api.js?render=' . $recaptcha_site_key,
			array(),
			null,
			true
		);
	}
}//end if

// Add inline script for reCAPTCHA callbacks.
static $script_added = false;
if ( ! $script_added ) {
	$script = '';
	
	if ( 'v2' === $recaptcha_version ) {
		$script .= '
			window.spectraProRecaptchaCallback = function(token) {
				// reCAPTCHA v2 callback - token is automatically set in the response field
			};
		';
	} else {
		$script .= '
			window.spectraProRecaptchaV3Execute = function(siteKey, action) {
				return new Promise(function(resolve, reject) {
					if (typeof grecaptcha !== "undefined") {
						grecaptcha.ready(function() {
							grecaptcha.execute(siteKey, {action: action}).then(function(token) {
								resolve(token);
							}).catch(function(error) {
								reject(error);
							});
						});
					} else {
						reject(new Error("reCAPTCHA not loaded"));
					}
				});
			};
		';
	}//end if
	
	wp_add_inline_script( 'google-recaptcha' . ( 'v3' === $recaptcha_version ? '-v3' : '' ), $script );
	$script_added = true;
}//end if

// Render the block.
return 'file:./view.php';
