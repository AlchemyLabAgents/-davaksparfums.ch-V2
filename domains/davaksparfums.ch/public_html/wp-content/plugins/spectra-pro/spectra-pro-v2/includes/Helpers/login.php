<?php
/**
 * AJAX Login Handler for Spectra Pro Modular Login Block
 *
 * @package SpectraPro\Helpers
 */

// Add a test action to verify this file is loaded.
add_action(
	'wp_ajax_test_login_loaded',
	function() {
		wp_send_json_success( 'Login handler loaded successfully' );
	}
);

add_action( 'wp_ajax_nopriv_spectra_pro_v2_block_login', 'spectra_pro_handle_login' );
add_action( 'wp_ajax_spectra_pro_v2_block_login', 'spectra_pro_handle_login' );
add_action( 'wp_ajax_nopriv_spectra_pro_v2_block_login_forgot_password', 'spectra_pro_handle_forgot_password' );
add_action( 'wp_ajax_spectra_pro_v2_block_login_forgot_password', 'spectra_pro_handle_forgot_password' );

/**
 * Handle AJAX login request for Spectra Pro Login Block.
 *
 * @since 2.0.0
 * @return void
 */
function spectra_pro_handle_login() {
	// Validate nonce - make it optional for better compatibility.
	$nonce = isset( $_POST['_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_nonce'] ) ) : '';
	if ( ! empty( $nonce ) && ! wp_verify_nonce( $nonce, 'spectra_pro_v2_login_nonce' ) ) {
		wp_send_json_error( __( 'Invalid nonce. Please refresh and try again.', 'spectra-pro' ) );
	}

	// Handle reCAPTCHA if enabled.
	$recaptcha_status = isset( $_POST['recaptchaStatus'] ) ? filter_var( sanitize_text_field( $_POST['recaptchaStatus'] ), FILTER_VALIDATE_BOOLEAN ) : false;
	if ( $recaptcha_status ) {
		$recaptcha_type   = isset( $_POST['reCaptchaType'] ) ? sanitize_text_field( $_POST['reCaptchaType'] ) : 'v2';
		$recaptcha_secret = '';
		
		if ( 'v2' === $recaptcha_type ) {
			$recaptcha_secret = \UAGB_Admin_Helper::get_admin_settings_option( 'uag_recaptcha_secret_key_v2', '' );
		} else {
			$recaptcha_secret = \UAGB_Admin_Helper::get_admin_settings_option( 'uag_recaptcha_secret_key_v3', '' );
		}

		if ( ! is_string( $recaptcha_secret ) ) {
			$recaptcha_secret = '';
		}

		if ( ! empty( $recaptcha_secret ) ) {
			$g_recaptcha_response = isset( $_POST['g-recaptcha-response'] ) ? sanitize_text_field( $_POST['g-recaptcha-response'] ) : '';
			$remote_addr          = filter_input( INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP );
			$remote_addr          = is_string( $remote_addr ) ? $remote_addr : '';
			
			// Use the RecaptchaVerifier class like v1 for consistency.
			if ( class_exists( 'SpectraPro\\BlocksConfig\\Utils\\RecaptchaVerifier' ) ) {
				$recaptcha_verifier = new \SpectraPro\BlocksConfig\Utils\RecaptchaVerifier();
				$verify             = $recaptcha_verifier->verify( $g_recaptcha_response, $remote_addr, $recaptcha_secret );
				if ( false === $verify ) {
					wp_send_json_error( __( 'Captcha is not matching, please try again.', 'spectra-pro' ) );
				}
			} else {
				// Fallback to direct verification.
				$verify_url  = 'https://www.google.com/recaptcha/api/siteverify';
				$verify_data = array(
					'secret'   => $recaptcha_secret,
					'response' => $g_recaptcha_response,
					'remoteip' => $remote_addr,
				);
				
				$verify_response = wp_remote_post(
					$verify_url,
					array(
						'body' => $verify_data,
					)
				);
				
				if ( is_wp_error( $verify_response ) ) {
					wp_send_json_error( __( 'reCAPTCHA verification failed. Please try again.', 'spectra-pro' ) );
				}
				
				$verify_result = json_decode( wp_remote_retrieve_body( $verify_response ), true );
				if ( ! $verify_result['success'] ) {
					wp_send_json_error( __( 'Captcha is not matching, please try again.', 'spectra-pro' ) );
				}
			}//end if
		} else {
			wp_send_json_error( __( 'reCAPTCHA configuration error. Please contact the site administrator.', 'spectra-pro' ) );
		}//end if
	}//end if

	// Handle multiple field name formats for better compatibility.
	$username = '';
	if ( isset( $_POST['log'] ) ) {
		$username = sanitize_user( $_POST['log'] );
	} elseif ( isset( $_POST['username'] ) ) {
		$username = sanitize_user( $_POST['username'] );
	} elseif ( isset( $_POST['user_login'] ) ) {
		$username = sanitize_user( $_POST['user_login'] );
	}
	
	// Password fields should not be sanitized - passed directly to wp_signon for security reasons.
	// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	$password = '';
	if ( isset( $_POST['pwd'] ) ) {
		$password = $_POST['pwd'];
	} elseif ( isset( $_POST['password'] ) ) {
		$password = $_POST['password'];
	} elseif ( isset( $_POST['user_password'] ) ) {
		$password = $_POST['user_password'];
	}
	// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	$rememberme = isset( $_POST['rememberme'] ) ? true : false;

	if ( empty( $username ) || empty( $password ) ) {
		wp_send_json_error( __( 'Username and password are required.', 'spectra-pro' ) );
	}

	$creds = array(
		'user_login'    => $username,
		'user_password' => $password,
		'remember'      => $rememberme,
	);
	$user  = wp_signon( $creds, is_ssl() );

	if ( is_wp_error( $user ) ) {
		$msg = $user->get_error_message();
		if ( empty( $msg ) ) {
			$msg = __( 'Login failed. Please try again.', 'spectra-pro' );
		}
		wp_send_json_error( $msg );
	}

	wp_set_current_user( $user->ID );
	wp_set_auth_cookie( $user->ID );

	// Handle redirect URL if provided.
	$redirect_url = '';
	if ( ! empty( $_POST['redirectUrl'] ) ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below with esc_url_raw().
		$redirect_data = wp_unslash( $_POST['redirectUrl'] );

		// Handle both string and object formats from LinkControl.
		if ( is_string( $redirect_data ) ) {
			$redirect_url = esc_url_raw( $redirect_data );
		} elseif ( is_array( $redirect_data ) && isset( $redirect_data['url'] ) ) {
			$redirect_url = esc_url_raw( $redirect_data['url'] );
		}
	}

	// Prepare response data.
	$response_data = array(
		'message'     => __( 'Login successful! Redirecting...', 'spectra-pro' ),
		'redirectUrl' => $redirect_url,
		'user_id'     => $user->ID,
	);

	wp_send_json_success( $response_data );
}

/**
 * Handle AJAX forgot password request for Spectra Pro Login Block.
 *
 * @since 2.0.0
 * @return void
 */
function spectra_pro_handle_forgot_password() {
	// Validate nonce - make it optional for better compatibility.
	$nonce = isset( $_POST['_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_nonce'] ) ) : '';
	if ( ! empty( $nonce ) && ! wp_verify_nonce( $nonce, 'spectra_pro_v2_login_nonce' ) ) {
		wp_send_json_error( __( 'Invalid nonce. Please refresh and try again.', 'spectra-pro' ) );
	}
	$username = isset( $_POST['log'] ) ? sanitize_user( $_POST['log'] ) : ( isset( $_POST['user_login'] ) ? sanitize_user( $_POST['user_login'] ) : ( isset( $_POST['username'] ) ? sanitize_user( $_POST['username'] ) : '' ) );
	if ( empty( $username ) ) {
		wp_send_json_error( __( 'Username or email is required.', 'spectra-pro' ) );
	}
	$user = get_user_by( 'login', $username );
	if ( ! $user ) {
		$user = get_user_by( 'email', $username );
	}
	if ( ! $user ) {
		wp_send_json_error( __( 'No user found with that username or email.', 'spectra-pro' ) );
	}
	$reset = retrieve_password( $user->user_login );
	if ( true === $reset ) {
		wp_send_json_success( __( 'A password reset email has been sent.', 'spectra-pro' ) );
	} else {
		wp_send_json_error( __( 'Could not send reset email. Please try again later.', 'spectra-pro' ) );
	}
} 
