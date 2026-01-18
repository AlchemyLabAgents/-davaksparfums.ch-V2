<?php
/**
 * Register form helper functions for Spectra Pro v2
 * 
 * @since 2.0.0
 * @package Spectra\Helpers
 */

namespace Spectra\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SpectraPro\BlocksConfig\Utils\RecaptchaVerifier;

/**
 * Class Register
 */
class Register {

	/**
	 * Block Name
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private static $block_name = 'spectra-pro/register';

	/**
	 * Hold Block Attributes Data
	 *
	 * @since 2.0.0
	 * @var array
	 */
	private static $saved_attributes = [];

	/**
	 * Hold Email Settings
	 *
	 * @since 2.0.0
	 * @var array
	 */
	private static $email_settings = [];

	/**
	 * Initialize the register functionality
	 *
	 * @since 2.0.0
	 */
	public static function init() {
		// Remove ALL handlers for v1 AJAX actions (v2 takes priority)
		// We need to remove all handlers because v1 uses instance methods.
		remove_all_actions( 'wp_ajax_spectra_pro_block_register' );
		remove_all_actions( 'wp_ajax_nopriv_spectra_pro_block_register' );

		// Register v2 AJAX handlers with higher priority to ensure they run.
		add_action( 'wp_ajax_spectra_pro_block_register', [ __CLASS__, 'register_new_user' ], 5 );
		add_action( 'wp_ajax_nopriv_spectra_pro_block_register', [ __CLASS__, 'register_new_user' ], 5 );
		add_action( 'wp_ajax_nopriv_spectra_pro_block_register_unique_username_and_email', [ __CLASS__, 'unique_username_and_email' ], 5 );
		add_action( 'wp_ajax_spectra_pro_block_register_get_roles', [ __CLASS__, 'get_roles' ], 5 );

		// Custom email notification filter - use priority 5 to run before v1's priority 10.
		add_filter( 'wp_new_user_notification_email', [ __CLASS__, 'custom_wp_new_user_notification_email' ], 5, 3 );
	}

	/**
	 * Register new user
	 *
	 * @return void
	 * @since 2.0.0
	 */
	public static function register_new_user() {
		
		check_ajax_referer( 'spectra-pro-register-nonce', '_nonce' );

		$allow_register = get_option( 'users_can_register' );
		if ( ! $allow_register ) {
			wp_send_json_error( esc_html__( 'Sorry, the site admin has disabled new user registration', 'spectra-pro' ) );
		}

		$error      = [];
		$post_id    = ( isset( $_POST['post_id'] ) ? sanitize_text_field( $_POST['post_id'] ) : '' );
		$block_id   = ( isset( $_POST['block_id'] ) ? sanitize_text_field( $_POST['block_id'] ) : '' );
		$first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( $_POST['first_name'] ) : '';
		$last_name  = isset( $_POST['last_name'] ) ? sanitize_text_field( $_POST['last_name'] ) : '';
		// Accept both WordPress standard field names and legacy names for compatibility.
		$username = isset( $_POST['user_login'] ) ? sanitize_user( $_POST['user_login'], true ) : ( isset( $_POST['username'] ) ? sanitize_user( $_POST['username'], true ) : '' );
		$email    = isset( $_POST['user_email'] ) ? sanitize_text_field( $_POST['user_email'] ) : ( isset( $_POST['email'] ) ? sanitize_text_field( $_POST['email'] ) : '' );
		$password = isset( $_POST['user_pass'] ) ? sanitize_text_field( $_POST['user_pass'] ) : ( isset( $_POST['password'] ) ? sanitize_text_field( $_POST['password'] ) : '' );
		

		$content_post = get_post( intval( $post_id ) );
		if ( ! $content_post instanceof \WP_Post ) {
			wp_send_json_error( __( 'Not a valid post.', 'spectra-pro' ) );
			die();
		}

		self::$saved_attributes = self::get_block_attributes( $content_post->post_content, self::$block_name, $block_id );
		$default_attributes     = self::get_default_attributes();

		// Verify reCaptcha.
		$recaptcha_enable = isset( self::$saved_attributes[ self::$block_name ]['reCaptchaEnable'] ) ? self::$saved_attributes[ self::$block_name ]['reCaptchaEnable'] : $default_attributes['reCaptchaEnable']['default'];
		if ( $recaptcha_enable ) {
			$recaptcha_type   = isset( self::$saved_attributes[ self::$block_name ]['reCaptchaType'] ) ? self::$saved_attributes[ self::$block_name ]['reCaptchaType'] : $default_attributes['reCaptchaType']['default'];
			$recaptcha_secret = '';
			if ( 'v2' === $recaptcha_type ) {
				$recaptcha_secret = \UAGB_Admin_Helper::get_admin_settings_option( 'uag_recaptcha_secret_key_v2', '' );
			} else {
				$recaptcha_secret = \UAGB_Admin_Helper::get_admin_settings_option( 'uag_recaptcha_secret_key_v3', '' );
			}

			if ( ! is_string( $recaptcha_secret ) ) {
				$recaptcha_secret = '';
			}

			$recaptcha_verifier   = new RecaptchaVerifier();
			$g_recaptcha_response = ( isset( $_POST['g-recaptcha-response'] ) ? sanitize_text_field( $_POST['g-recaptcha-response'] ) : '' );
			$remote_addr          = filter_input( INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP );
			$remote_addr          = is_string( $remote_addr ) ? $remote_addr : '';
			$verify               = $recaptcha_verifier->verify( $g_recaptcha_response, $remote_addr, $recaptcha_secret );
			if ( false === $verify ) {
				wp_send_json_error( array( 'g-recaptcha-response' => __( 'Captcha is not matching, please try again.', 'spectra-pro' ) ) );
			}
		}//end if

		// Password validation.
		if ( empty( $password ) ) {
			$password = wp_generate_password();
		} elseif ( ( isset( $_POST['user_pass_confirm'] ) && $password !== $_POST['user_pass_confirm'] ) || ( isset( $_POST['confirm_password'] ) && $password !== $_POST['confirm_password'] ) ) {
			$error['password'] = isset( self::$saved_attributes[ self::$block_name ]['messagePasswordConfirmError'] ) ? self::$saved_attributes[ self::$block_name ]['messagePasswordConfirmError'] : $default_attributes['messagePasswordConfirmError']['default'];
		} elseif ( false !== strpos( wp_unslash( $password ), '\\' ) ) {
			$error['password'] = __( 'Password may not contain the character "\\"', 'spectra-pro' );
		}

		// Check required fields.
		if ( empty( $first_name ) && isset( self::$saved_attributes['first_name']['required'] ) && self::$saved_attributes['first_name']['required'] ) {
			$error['first_name'] = esc_html__( 'This field is required.', 'spectra-pro' );
		}
		if ( empty( $last_name ) && isset( self::$saved_attributes['last_name']['required'] ) && self::$saved_attributes['last_name']['required'] ) {
			$error['last_name'] = esc_html__( 'This field is required.', 'spectra-pro' );
		}

		// Username validation.
		if ( isset( self::$saved_attributes['username']['required'] ) && self::$saved_attributes['username']['required'] ) {
			if ( empty( $username ) ) {
				$error['username'] = isset( self::$saved_attributes[ self::$block_name ]['messageInvalidUsernameError'] ) ? self::$saved_attributes[ self::$block_name ]['messageInvalidUsernameError'] : $default_attributes['messageInvalidUsernameError']['default'];
			} elseif ( username_exists( $username ) ) {
				$error['username'] = isset( self::$saved_attributes[ self::$block_name ]['messageUsernameAlreadyUsedError'] ) ? self::$saved_attributes[ self::$block_name ]['messageUsernameAlreadyUsedError'] : $default_attributes['messageUsernameAlreadyUsedError']['default'];
			}
		}

		// Email validation.
		if ( empty( $email ) ) {
			$error['email'] = isset( self::$saved_attributes[ self::$block_name ]['messageEmailMissingError'] ) ? self::$saved_attributes[ self::$block_name ]['messageEmailMissingError'] : $default_attributes['messageEmailMissingError']['default'];
		} elseif ( $email && ! is_email( $email ) ) {
			$error['email'] = isset( self::$saved_attributes[ self::$block_name ]['messageInvalidEmailError'] ) ? self::$saved_attributes[ self::$block_name ]['messageInvalidEmailError'] : $default_attributes['messageInvalidEmailError']['default'];
		} elseif ( email_exists( $email ) ) {
			$error['email'] = isset( self::$saved_attributes[ self::$block_name ]['messageEmailAlreadyUsedError'] ) ? self::$saved_attributes[ self::$block_name ]['messageEmailAlreadyUsedError'] : $default_attributes['messageEmailAlreadyUsedError']['default'];
		}

		// Terms validation.
		if ( isset( self::$saved_attributes['terms']['required'] ) && self::$saved_attributes['terms']['required'] ) {
			$terms = (bool) isset( $_POST['terms'] ) ? sanitize_text_field( $_POST['terms'] ) : false;
			if ( ! $terms ) {
				$error['terms'] = isset( self::$saved_attributes[ self::$block_name ]['messageTermsError'] ) ? self::$saved_attributes[ self::$block_name ]['messageTermsError'] : $default_attributes['messageTermsError']['default'];
			}
		}

		// Get all roles.
		$get_all_roles = array_keys( self::get_all_roles() );
		
		// Role assignment.
		$default_role = get_option( 'default_role' );
		$role         = $default_role;
		if ( isset( self::$saved_attributes[ self::$block_name ]['newUserRole'] ) && ! empty( self::$saved_attributes[ self::$block_name ]['newUserRole'] ) ) {
			$role = self::$saved_attributes[ self::$block_name ]['newUserRole'];
			// Check if role is valid.
			$role = in_array( $role, $get_all_roles ) ? $role : $default_role;
		}
		// Apply filter.
		$role = apply_filters( 'spectra_pro_registration_form_change_new_user_role', $role );

		// Email settings.
		$email_template_type = isset( self::$saved_attributes[ self::$block_name ]['emailTemplateType'] ) ? self::$saved_attributes[ self::$block_name ]['emailTemplateType'] : $default_attributes['emailTemplateType']['default'];

		if (
			isset( self::$saved_attributes[ self::$block_name ]['afterRegisterActions'] ) &&
			in_array( 'sendMail', self::$saved_attributes[ self::$block_name ]['afterRegisterActions'], true ) &&
			'custom' === $email_template_type
		) {
			// Form data.
			self::$email_settings['user_login'] = $username;
			self::$email_settings['user_pass']  = $password;
			self::$email_settings['user_email'] = $email;
			self::$email_settings['first_name'] = $first_name;
			self::$email_settings['last_name']  = $last_name;

			// Email settings.
			self::$email_settings['subject'] = isset( self::$saved_attributes[ self::$block_name ]['emailTemplateSubject'] ) ? self::$saved_attributes[ self::$block_name ]['emailTemplateSubject'] : $default_attributes['emailTemplateSubject']['default'];
			self::$email_settings['message'] = isset( self::$saved_attributes[ self::$block_name ]['emailTemplateMessage'] ) ? self::$saved_attributes[ self::$block_name ]['emailTemplateMessage'] : $default_attributes['emailTemplateMessage']['default'];
			$headers                         = isset( self::$saved_attributes[ self::$block_name ]['emailTemplateMessageType'] ) ? self::$saved_attributes[ self::$block_name ]['emailTemplateMessageType'] : $default_attributes['emailTemplateMessageType']['default'];

			self::$email_settings['headers'] = 'Content-Type: text/' . ( 'plain' === $headers ? $headers : 'html; charset=UTF-8\r\n' );
		}

		// Create username from email if empty.
		if ( empty( $username ) ) {
			$username = self::create_username( $email, '' );
			$username = sanitize_user( $username );
		}

		// Return errors if any.
		if ( count( $error ) ) {
			wp_send_json_error( $error );
		}

		$user_args = apply_filters(
			'spectra_pro_block_register_insert_user_args',
			array(
				'user_login'      => $username,
				'user_pass'       => $password,
				'user_email'      => $email,
				'first_name'      => $first_name,
				'last_name'       => $last_name,
				'user_registered' => gmdate( 'Y-m-d H:i:s' ),
				'role'            => $role,
			)
		);

		$result = wp_insert_user( $user_args );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result );
		}

		// Set auth cookie for seamless experience.
		wp_set_auth_cookie( $result );

		/**
		 * Fires after a new user has been created.
		 *
		 * @since 2.0.0
		 *
		 * @param int    $user_id ID of the newly created user.
		 * @param string $notify  Type of notification that should happen.
		 */
		do_action( 'edit_user_created_user', $result, 'both' );

		$message = isset( self::$saved_attributes[ self::$block_name ]['messageSuccessRegistration'] ) ? self::$saved_attributes[ self::$block_name ]['messageSuccessRegistration'] : $default_attributes['messageSuccessRegistration']['default'];
		
		// Handle redirect URL if provided (same pattern as login block).
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
			
			// Add protocol if missing.
			if ( ! empty( $redirect_url ) && ! preg_match( '/^https?:\/\//i', $redirect_url ) ) {
				$redirect_url = 'https://' . $redirect_url;
				$redirect_url = esc_url( $redirect_url );
			}
		}
		

		// Login user after registration if enabled.
		$afterRegisterActions = isset( self::$saved_attributes[ self::$block_name ]['afterRegisterActions'] ) ? self::$saved_attributes[ self::$block_name ]['afterRegisterActions'] : $default_attributes['afterRegisterActions']['default'];
		if ( in_array( 'autoLogin', $afterRegisterActions, true ) ) {
			$creds                  = array();
			$creds['user_login']    = $username;
			$creds['user_password'] = $password;
			$creds['remember']      = true;
			$login_user             = wp_signon( $creds, false );
			if ( ! is_wp_error( $login_user ) ) {
				wp_send_json_success(
					[
						'message'      => $message,
						'redirect_url' => $redirect_url,
					]
				);
			}

			$error['other'] = isset( self::$saved_attributes[ self::$block_name ]['messageOtherError'] ) ? self::$saved_attributes[ self::$block_name ]['messageOtherError'] : $default_attributes['messageOtherError']['default'];
			wp_send_json_error( $error );
		}

		wp_send_json_success(
			[
				'message'      => $message,
				'redirect_url' => $redirect_url,
			]
		);
	}

	/**
	 * Check unique username and email
	 *
	 * @return void
	 * @since 2.0.0
	 */
	public static function unique_username_and_email() {
		check_ajax_referer( 'spectra-pro-register-nonce', 'security' );
		$field_name  = ( isset( $_POST['field_name'] ) ? sanitize_key( $_POST['field_name'] ) : '' );
		$field_value = ( isset( $_POST['field_value'] ) ? sanitize_text_field( $_POST['field_value'] ) : '' );
		
		if ( 'username' === $field_name ) {
			if ( username_exists( $field_value ) ) {
				wp_send_json_success(
					[
						'has_error' => true,
						'attribute' => 'messageUsernameAlreadyUsedError',
					]
				);
			}
		} elseif ( 'email' === $field_name ) {
			if ( ! is_email( $field_value ) ) {
				wp_send_json_success(
					[
						'has_error' => true,
						'attribute' => 'messageInvalidEmailError',
					]
				);
			} elseif ( email_exists( $field_value ) ) {
				wp_send_json_success(
					[
						'has_error' => true,
						'attribute' => 'messageEmailAlreadyUsedError',
					]
				);
			}
		}//end if
		
		wp_send_json_success(
			[
				'has_error' => false,
				'attribute' => '',
			]
		);
	}

	/**
	 * Get all available user roles
	 *
	 * @return array
	 * @since 2.0.0
	 */
	public static function get_all_roles() {
		$all_roles = new \WP_Roles();
		$all_roles = $all_roles->get_names();

		// Roles to remove for security.
		$roles_to_remove = array( 'administrator', 'editor' );

		// Remove the specified roles from the array.
		foreach ( $roles_to_remove as $role ) {
			if ( isset( $all_roles[ $role ] ) ) {
				unset( $all_roles[ $role ] );
			}
		}

		return $all_roles;
	}

	/**
	 * Get roles for AJAX response
	 *
	 * @return void
	 * @since 2.0.0
	 */
	public static function get_roles() {
		check_ajax_referer( 'spectra_pro_ajax_nonce', 'security' );
		$all_roles = self::get_all_roles();
		$response  = [
			array(
				'value' => 'default',
				'label' => esc_html__( '– Select –', 'spectra-pro' ),
			),
		];
		foreach ( $all_roles as $value => $label ) {
			$response[] = array(
				'value' => $value,
				'label' => $label,
			);
		}
		wp_send_json_success( $response );
	}

	/**
	 * Custom email notification template
	 *
	 * @param array  $wp_new_user_notification_email Email data.
	 * @param object $user User object.
	 * @param string $blogname Website name.
	 * @return array
	 * @since 2.0.0
	 */
	public static function custom_wp_new_user_notification_email( $wp_new_user_notification_email, $user, $blogname ) {
		if (
			! empty( self::$email_settings ) &&
			isset( self::$saved_attributes[ self::$block_name ]['afterRegisterActions'] ) &&
			in_array( 'sendMail', self::$saved_attributes[ self::$block_name ]['afterRegisterActions'], true ) &&
			isset( self::$saved_attributes[ self::$block_name ]['emailTemplateType'] ) &&
			'custom' === self::$saved_attributes[ self::$block_name ]['emailTemplateType']
		) {
			$wp_new_user_notification_email['subject'] = preg_replace( '/\{{site_title}}/', $blogname, self::$email_settings['subject'] );

			$message = self::$email_settings['message'];

			$find = array( '/\{{login_url}}/', '/\[field=password\]/', '/\[field=username\]/', '/\[field=email\]/', '/\[field=first_name\]/', '/\[field=last_name\]/', '/\{{site_title}}/' );

			$replacement = array( esc_url( wp_login_url( get_permalink() ) ), self::$email_settings['user_pass'], self::$email_settings['user_login'], self::$email_settings['user_email'], self::$email_settings['first_name'], self::$email_settings['last_name'], $blogname );

			if ( isset( self::$email_settings['user_pass'] ) ) {
				$message = preg_replace( $find, $replacement, $message );
			}

			$wp_new_user_notification_email['message'] = $message;
			$wp_new_user_notification_email['headers'] = self::$email_settings['headers'];

		}
		return $wp_new_user_notification_email;
	}

	/**
	 * Generate username from email
	 *
	 * @param string $email Email address.
	 * @param string $suffix Email suffix.
	 * @return string
	 * @since 2.0.0
	 */
	public static function create_username( $email, $suffix ) {
		$username_parts = array();

		// If there are no parts, fallback to email.
		if ( empty( $username_parts ) ) {
			$email_parts    = explode( '@', $email );
			$email_username = $email_parts[0];

			// Exclude common prefixes.
			if ( in_array(
				$email_username,
				array(
					'sales',
					'hello',
					'mail',
					'contact',
					'info',
				),
				true
			) ) {
				// Get the domain part.
				$email_username = $email_parts[1];
			}

			$username_parts[] = sanitize_user( $email_username, true );
		}//end if
		
		$username = strtolower( implode( '', $username_parts ) );

		if ( $suffix ) {
			$username .= $suffix;
		}

		if ( username_exists( $username ) ) {
			// Generate something unique to append to the username.
			$suffix = '-' . zeroise( wp_rand( 0, 9999 ), 4 );
			return self::create_username( $email, $suffix );
		}

		return $username;
	}

	/**
	 * Get block attributes recursively
	 *
	 * @param array  $blocks     Blocks array.
	 * @param string $block_name Block name.
	 * @param string $block_id   Block ID.
	 * @return array
	 * @since 2.0.0
	 */
	public static function get_block_attributes_recursive( $blocks, $block_name, $block_id ) {
		$attributes = [];
		foreach ( $blocks as $block ) {
			if ( $block['blockName'] === $block_name && $block['attrs']['block_id'] === $block_id ) {
				$attributes[ $block_name ] = $block['attrs'];
				if ( is_array( $block['innerBlocks'] ) && count( $block['innerBlocks'] ) ) {
					foreach ( $block['innerBlocks'] as $inner_block ) {
						if ( isset( $inner_block['attrs']['name'] ) ) {
							$attributes[ $inner_block['attrs']['name'] ] = $inner_block['attrs'];
						}
					}
				}
				return $attributes;
			} elseif ( is_array( $block['innerBlocks'] ) && count( $block['innerBlocks'] ) ) {
				$inner_attributes = self::get_block_attributes_recursive( $block['innerBlocks'], $block_name, $block_id );
				if ( ! empty( $inner_attributes ) ) {
					return $inner_attributes;
				}
			}
		}
		return $attributes;
	}

	/**
	 * Get block attributes from post content
	 *
	 * @param string $content    Post content.
	 * @param string $block_name Block name.
	 * @param string $block_id   Block ID.
	 * @return array
	 * @since 2.0.0
	 */
	public static function get_block_attributes( $content, $block_name, $block_id ) {
		$blocks = parse_blocks( $content );
		if ( empty( $blocks ) ) {
			return array();
		}
		return self::get_block_attributes_recursive( $blocks, $block_name, $block_id );
	}

	/**
	 * Get default block attributes
	 *
	 * @return array
	 * @since 2.0.0
	 */
	public static function get_default_attributes() {
		return array(
			'newUserRole'                  => array(
				'type'    => 'string',
				'default' => '',
			),
			'afterRegisterActions'         => array(
				'type'    => 'array',
				'default' => [ 'autoLogin' ],
			),
			'emailTemplateType'            => array(
				'type'    => 'string',
				'default' => 'default',
			),
			'emailTemplateSubject'         => array(
				'type'    => 'string',
				'default' => 'Thank you for registering with "{{site_title}}"!',
			),
			'emailTemplateMessage'         => array(
				'type' => 'string',
			),
			'emailTemplateMessageType'     => array(
				'type'    => 'string',
				'default' => 'html',
			),
			'messageInvalidEmailError'     => array(
				'type'    => 'string',
				'default' => __( 'Please enter a valid email address.', 'spectra-pro' ),
			),
			'messageEmailMissingError'     => array(
				'type'    => 'string',
				'default' => __( 'Please enter a valid email address.', 'spectra-pro' ),
			),
			'messageEmailAlreadyUsedError' => array(
				'type'    => 'string',
				'default' => __( 'Email already in use. Please try to sign in.', 'spectra-pro' ),
			),
			'messageInvalidUsernameError'  => array(
				'type'    => 'string',
				'default' => __( 'Invalid user name. Please try again.', 'spectra-pro' ),
			),
			'messageUsernameAlreadyUsed'   => array(
				'type'    => 'string',
				'default' => __( 'Username is already taken.', 'spectra-pro' ),
			),
			'messageInvalidPasswordError'  => array(
				'type'    => 'string',
				'default' => __( 'Password cannot be accepted. Please try something else.', 'spectra-pro' ),
			),
			'messagePasswordConfirmError'  => array(
				'type'    => 'string',
				'default' => __( 'Passwords do not match.', 'spectra-pro' ),
			),
			'messageTermsError'            => array(
				'type'    => 'string',
				'default' => __( 'Please try again after accepting terms & conditions.', 'spectra-pro' ),
			),
			'messageOtherError'            => array(
				'type'    => 'string',
				'default' => __( 'Something went wrong! Please try again.', 'spectra-pro' ),
			),
			'messageSuccessRegistration'   => array(
				'type'    => 'string',
				'default' => __( 'Registration successful. Please check your email inbox.', 'spectra-pro' ),
			),
			'reCaptchaEnable'              => array(
				'type'    => 'boolean',
				'default' => false,
			),
			'reCaptchaType'                => array(
				'type'    => 'string',
				'default' => 'v2',
			),
		);
	}
}
