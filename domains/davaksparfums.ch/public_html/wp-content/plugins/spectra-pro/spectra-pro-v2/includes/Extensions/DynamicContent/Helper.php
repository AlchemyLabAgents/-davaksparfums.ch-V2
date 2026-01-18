<?php
/**
 * Helper class for dynamic content operations in Spectra Pro.
 *
 * This class provides utility methods for handling dynamic content in WordPress,
 * including processing field values, applying formatting, and retrieving various
 * types of dynamic data (posts, users, archives, etc.).
 *
 * @package SpectraPro\Extensions\DynamicContent
 * 
 * @since 2.0.0-beta.1
 */

namespace SpectraPro\Extensions\DynamicContent;

use SpectraPro\Extensions\DynamicContent\Source\CustomFields;
use SpectraPro\Extensions\DynamicContent\Source\Posts;

/**
 * Helper class for dynamic content operations.
 * 
 * @since 2.0.0-beta.1
 */
class Helper {

	/**
	 * Apply advanced settings to a value.
	 *
	 * Processes a value with various advanced settings including:
	 * - Fallback value if empty
	 * - Character length limitation
	 * - Before/after text
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $value    The original value to process.
	 * @param array  $settings The settings array containing advanced options.
	 * @return string The processed value with advanced settings applied.
	 */
	private static function apply_advanced_settings( $value, $settings ) {
		// Apply fallback if value is empty.
		$fallback = $settings['advanced']['fallback'] ?? '';
		if ( empty( $value ) && ! empty( $fallback ) ) {
			$value = $fallback;
		}

		// Return early if value is still empty.
		if ( empty( $value ) ) {
			return $value;
		}

		// Apply character length limit if specified.
		$char_length = absint( $settings['advanced']['charLength'] ?? 0 );
		if ( $char_length > 0 && mb_strlen( $value ) > $char_length ) {
			$value = mb_substr( $value, 0, $char_length ) . '&hellip;';
		}

		// Prepend before text if specified.
		$before_text = $settings['advanced']['before'] ?? '';
		if ( ! empty( $before_text ) ) {
			$value = $before_text . $value;
		}

		// Append after text if specified.
		$after_text = $settings['advanced']['after'] ?? '';
		if ( ! empty( $after_text ) ) {
			$value .= $after_text;
		}

		return $value;
	}

	/**
	 * Apply link settings to a value.
	 *
	 * Wraps the value in an anchor tag if link settings are enabled and valid.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $value    The text value to potentially link.
	 * @param array  $settings The settings array containing link options.
	 * @return string The original value or linked version if conditions met.
	 */
	private static function apply_link_settings( $value, $settings ) {
		// Return early if value is empty.
		if ( empty( $value ) ) {
			return $value;
		}

		$enable_link = $settings['enableLink'] ?? false;
		$link_source = $settings['linkSource'] ?? '';

		// Return original value if linking is disabled or source is empty.
		if ( ! $enable_link || empty( $link_source ) ) {
			return $value;
		}

		// Get the URL from post link fields.
		$url = Posts::get_link_field_value( $settings );

		// Return linked version if URL is valid.
		if ( ! empty( $url ) ) {
			return sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( $value ) );
		}

		return $value;
	}

	/**
	 * Format a value according to the provided settings.
	 *
	 * Applies both advanced formatting and optionally link settings to a value.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $value       The value to format.
	 * @param array  $settings    The settings array for formatting options.
	 * @param bool   $apply_link  Whether to apply link settings. Default true.
	 * @return string The formatted value.
	 */
	private static function format_value( $value, $settings, $apply_link = true ) {
		if ( empty( $value ) ) {
			return __( 'N/A', 'spectra-pro' );
		}

		$value = self::apply_advanced_settings( $value, $settings );

		// Apply link settings if enabled.
		if ( $apply_link ) {
			$value = self::apply_link_settings( $value, $settings );
		}

		return $value;
	}

	/**
	 * Retrieves the value of an archive field.
	 *
	 * Supports archive titles, descriptions, URLs, and custom meta values.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $field    The archive field to retrieve.
	 * @param array  $settings The settings array containing source info.
	 * @return string The retrieved value or empty string if not found.
	 */
	private static function get_archive_field_value( $field, $settings ) {
		if ( empty( $field ) ) {
			return '';
		}

		// Handle archive meta fields.
		if ( 'archive_meta' === $field ) {
			$field = $settings['source']['archiveMetaKey'] ?? '';

			// Return early if field is empty.
			if ( empty( $field ) ) {
				return '';
			}

			// Handle custom archive meta fields.
			if ( 'custom' === $field ) {
				$field = $settings['source']['customArchiveMetaKey'] ?? '';
			}

			// Return early if field is empty.
			if ( empty( $field ) ) {
				return '';
			}

			return CustomFields::get_archive_meta_value( $field );
		}

		// Map common archive fields to their WordPress functions.
		$fields = array(
			'archive_title'       => get_the_archive_title(),
			'archive_description' => get_the_archive_description(),
			'archive_url'         => self::get_current_archive_url(),

		);

		return $fields[ $field ] ?? '';
	}

	/**
	 * Retrieves the value of a request parameter.
	 *
	 * Supports GET, POST, and query var sources with proper sanitization.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $field    The request type (GET, POST, QUERY_VAR).
	 * @param array  $settings The settings array containing the param key.
	 * @return string The sanitized request value or empty string.
	 */
	private static function get_request_param_value( $field, $settings ) {
		$key = $settings['source']['requestParamKey'] ?? '';

		// Return early if key is empty.
		if ( ! $key ) {
			return '';
		}

		switch ( $field ) {
			case 'POST':
				// Phpcs ignore comment is required as nonce verification is not needed for read-only form data access for dynamic content display.
				return isset( $_POST[ $key ] ) ? sanitize_text_field( $_POST[ $key ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			case 'GET':
				// Phpcs ignore comment is required as nonce verification is not needed for read-only URL parameter access for dynamic content display.
				return isset( $_GET[ $key ] ) ? sanitize_text_field( $_GET[ $key ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			case 'QUERY_VAR':
				return get_query_var( $key );
		}
	}

	/**
	 * Gets the dynamic content value based on settings.
	 *
	 * Main entry point for retrieving dynamic content of various types.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param array $settings The configuration settings for the dynamic content.
	 * @param bool  $format   Whether to apply formatting. Default true.
	 * @return string The dynamic content value or 'N/A' if invalid.
	 */
	public static function get_dynamic_content_value( $settings, $format = true ) {
		$type  = $settings['source']['type'] ?? '';
		$field = $settings['source']['field'] ?? 'post_title';

		// If the type or field is empty, return 'N/A'.
		if ( empty( $field ) || empty( $type ) ) {
			return __( 'N/A', 'spectra-pro' );
		}

		// Route to appropriate value getter based on type.
		switch ( $type ) {
			case 'current_post':
			case 'post_type':
				$value = Posts::get_value( $type, $field, $settings );
				break;
			case 'site':
				$value = self::get_site_field_value( $field );
				break;
			case 'current_user':
				$value = self::get_user_field_value( $type, $field, $settings );
				break;
			case 'archive':
				$value = self::get_archive_field_value( $field, $settings );
				break;
			case 'request_parameter':
				$value = self::get_request_param_value( $field, $settings );
				break;
			case 'shortcode':
				$value = do_shortcode( $field );
				break;
			default:
				$value = __( 'N/A', 'spectra-pro' );
		}//end switch

		if ( empty( $value ) ) {
			$value = $settings['advanced']['fallback'] ?? '';
		}

		// Return unformatted value if requested.
		if ( ! $format ) {
			return empty( $value ) ? __( 'N/A', 'spectra-pro' ) : $value;
		}
	
		return self::format_value( $value, $settings );
	}

	/**
	 * Gets a dynamic URL value based on settings.
	 *
	 * Supports both legacy link source format and new full dynamic source format.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param array $settings The configuration settings for the URL.
	 * @return string The dynamic URL or empty string if invalid.
	 */
	public static function get_dynamic_url_value( $settings ) {
		// Early validation - ensure settings is an array.
		if ( ! is_array( $settings ) ) {
			return '';
		}

		// Check if this is the new full dynamic source format.
		if ( isset( $settings['source'] ) && isset( $settings['type'] ) && 'link' === $settings['type'] ) {
			return self::get_url_from_dynamic_source( $settings );
		}

		// Legacy format - use the original link source processing.
		$link_source = $settings['linkSource'] ?? '';

		// Sanitize link source for legacy format.
		$link_source = sanitize_key( $link_source );

		// Return early if link source is empty.
		if ( empty( $link_source ) ) {
			return '';
		}

		// Sanitize legacy settings before passing to Posts class.
		$sanitized_settings = array();
		foreach ( $settings as $key => $value ) {
			if ( is_string( $value ) ) {
				$sanitized_settings[ sanitize_key( $key ) ] = sanitize_text_field( $value );
			} elseif ( is_array( $value ) ) {
				$sanitized_settings[ sanitize_key( $key ) ] = $value; // Posts class should handle array sanitization.
			} else {
				$sanitized_settings[ sanitize_key( $key ) ] = $value;
			}
		}

		return Posts::get_link_field_value( $sanitized_settings );
	}

	/**
	 * Validates and sanitizes a URL with fallback support.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $url The URL to validate.
	 * @param string $fallback_url The fallback URL if validation fails.
	 * @return string The validated URL or fallback.
	 */
	private static function validate_url_with_fallback( $url, $fallback_url = '' ) {
		// Ensure we have strings.
		if ( ! is_string( $url ) || empty( $url ) ) {
			return $fallback_url;
		}

		// Sanitize the URL.
		$url = sanitize_text_field( $url );

		// Validate URL format.
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return $fallback_url;
		}

		// Check for safe protocols.
		$parsed_url      = wp_parse_url( $url );
		$allowed_schemes = array( 'http', 'https' );
		
		if ( ! isset( $parsed_url['scheme'] ) || ! in_array( $parsed_url['scheme'], $allowed_schemes, true ) ) {
			return $fallback_url;
		}

		return esc_url_raw( $url );
	}

	/**
	 * Gets a custom field URL value with ACF support.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $field_key The custom field key.
	 * @param int    $object_id The object ID (post ID, user ID, etc.).
	 * @param string $context The context ('post', 'user', etc.).
	 * @param string $fallback_url The fallback URL if field is empty or invalid.
	 * @return string The custom field URL or fallback.
	 */
	private static function get_custom_field_url( $field_key, $object_id, $context = 'post', $fallback_url = '' ) {
		if ( empty( $field_key ) || ! $object_id ) {
			return $fallback_url;
		}

		$field_value = '';

		// Get field value based on context.
		switch ( $context ) {
			case 'user':
				// Try ACF first for users.
				if ( function_exists( 'get_field' ) ) {
					$field_value = get_field( $field_key, 'user_' . $object_id );
				} else {
					$field_value = get_user_meta( $object_id, $field_key, true );
				}
				break;

			case 'post':
			default:
				// Try ACF first for posts.
				if ( function_exists( 'get_field' ) ) {
					$field_value = get_field( $field_key, $object_id );
				} else {
					$field_value = get_post_meta( $object_id, $field_key, true );
				}
				break;
		}

		// Handle ACF URL field object format.
		if ( is_array( $field_value ) && isset( $field_value['url'] ) ) {
			$field_value = $field_value['url'];
		}

		// Validate and return the URL.
		return self::validate_url_with_fallback( $field_value, $fallback_url );
	}

	/**
	 * Gets author-related URLs for a given post.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param int    $post_id The post ID.
	 * @param string $type The type of author URL ('archive', 'website', 'custom_field').
	 * @param string $custom_field Optional custom field key for custom field type.
	 * @return string The author URL.
	 */
	private static function get_author_url( $post_id, $type, $custom_field = '' ) {
		$author_id = get_post_field( 'post_author', $post_id );
		if ( ! $author_id ) {
			return get_permalink( $post_id );
		}

		$author_archive_url = get_author_posts_url( $author_id );

		switch ( $type ) {
			case 'website':
				$author_url = get_the_author_meta( 'user_url', $author_id );
				return self::validate_url_with_fallback( $author_url, $author_archive_url );

			case 'custom_field':
				if ( empty( $custom_field ) ) {
					return $author_archive_url;
				}
				return self::get_custom_field_url( $custom_field, $author_id, 'user', $author_archive_url );

			case 'archive':
			default:
				return $author_archive_url;
		}
	}

	/**
	 * Gets post-related URLs for common link fields.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param int    $post_id The post ID.
	 * @param string $link_field The link field type.
	 * @param array  $settings Additional settings for complex fields.
	 * @return string The post URL.
	 */
	private static function get_post_url( $post_id, $link_field, $settings = array() ) {
		if ( ! $post_id ) {
			return '';
		}

		$permalink = get_permalink( $post_id );

		switch ( $link_field ) {
			case 'post_permalink':
				return $permalink;

			case 'comments_area':
				return $permalink . '#comments';

			case 'featured_image':
				$image_url = get_the_post_thumbnail_url( $post_id, 'full' );
				return $image_url ? $image_url : $permalink;

			case 'custom_field':
				$custom_field = $settings['source']['postCustomField'] ?? '';
				if ( 'custom' === $custom_field ) {
					$custom_field = $settings['source']['postMetaKey'] ?? '';
				}
				return self::get_custom_field_url( $custom_field, $post_id, 'post', $permalink );

			case 'author_archive':
				return self::get_author_url( $post_id, 'archive' );

			case 'author_website':
				return self::get_author_url( $post_id, 'website' );

			case 'author_custom_field':
				$custom_field = $settings['source']['authorMetaKey'] ?? '';
				return self::get_author_url( $post_id, 'custom_field', $custom_field );

			case 'post_terms':
				$taxonomy = $settings['source']['taxonomyType'] ?? 'category';
				$terms    = get_the_terms( $post_id, $taxonomy );
				if ( $terms && ! is_wp_error( $terms ) ) {
					return get_term_link( $terms[0] );
				}
				return $permalink;

			default:
				return $permalink;
		}//end switch
	}

	/**
	 * Validates link field against allowed values for a given context.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $link_field The link field to validate.
	 * @param string $context The context ('post', 'user', 'site', 'archive').
	 * @return string The validated link field or default for context.
	 */
	private static function validate_link_field( $link_field, $context ) {
		$allowed_fields_by_context = array(
			'post'    => array( 'post_permalink', 'comments_area', 'featured_image', 'custom_field', 'author_archive', 'author_website', 'author_custom_field', 'post_terms' ),
			'site'    => array( 'site_url', 'home_url' ),
			'archive' => array( 'archive_url', 'archive_meta' ),
			'user'    => array( 'user_profile', 'user_website', 'custom_field' ),
		);

		$allowed_fields = $allowed_fields_by_context[ $context ] ?? array();
		$default_fields = array(
			'post'    => 'post_permalink',
			'site'    => 'home_url',
			'archive' => 'archive_url',
			'user'    => 'user_profile',
		);

		if ( ! in_array( $link_field, $allowed_fields, true ) ) {
			return $default_fields[ $context ] ?? '';
		}

		return $link_field;
	}

	/**
	 * Sanitizes dynamic content settings to prevent injection attacks.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param array $settings The dynamic content settings.
	 * @return array The sanitized settings.
	 */
	private static function sanitize_settings( $settings ) {
		$sanitized = array();

		// Sanitize main settings.
		if ( isset( $settings['linkField'] ) ) {
			$sanitized['linkField'] = sanitize_key( $settings['linkField'] );
		}

		// Sanitize source settings.
		if ( isset( $settings['source'] ) && is_array( $settings['source'] ) ) {
			$source              = $settings['source'];
			$sanitized['source'] = array();

			// Sanitize source type.
			if ( isset( $source['type'] ) ) {
				$sanitized['source']['type'] = sanitize_key( $source['type'] );
			}

			// Sanitize post ID.
			if ( isset( $source['postId'] ) ) {
				$sanitized['source']['postId'] = absint( $source['postId'] );
			}

			// Sanitize meta keys and field names.
			$meta_keys = array( 'postMetaKey', 'userMetaKey', 'authorMetaKey', 'archiveMetaKey' );
			foreach ( $meta_keys as $key ) {
				if ( isset( $source[ $key ] ) ) {
					$sanitized['source'][ $key ] = sanitize_key( $source[ $key ] );
				}
			}

			// Sanitize custom field names.
			$field_keys = array( 'postCustomField', 'authorField' );
			foreach ( $field_keys as $key ) {
				if ( isset( $source[ $key ] ) ) {
					$sanitized['source'][ $key ] = sanitize_key( $source[ $key ] );
				}
			}

			// Sanitize request parameter key.
			if ( isset( $source['requestParamKey'] ) ) {
				$sanitized['source']['requestParamKey'] = sanitize_key( $source['requestParamKey'] );
			}

			// Sanitize shortcode content (allow shortcode syntax but escape potentially dangerous content).
			if ( isset( $source['shortcodeContent'] ) ) {
				$shortcode = $source['shortcodeContent'];
				// Allow basic shortcode syntax but sanitize the content.
				$sanitized['source']['shortcodeContent'] = preg_replace( '/[^a-zA-Z0-9\[\]_\-="\s]/', '', $shortcode );
			}

			// Sanitize taxonomy type.
			if ( isset( $source['taxonomyType'] ) ) {
				$sanitized['source']['taxonomyType'] = sanitize_key( $source['taxonomyType'] );
			}
		}//end if

		return $sanitized;
	}

	/**
	 * Gets URL from dynamic source settings using the new format.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param array $settings The dynamic content settings.
	 * @return string The generated URL or empty string.
	 */
	private static function get_url_from_dynamic_source( $settings ) {
		// Sanitize all user input before processing.
		$settings = self::sanitize_settings( $settings );

		$source      = $settings['source'] ?? array();
		$source_type = $source['type'] ?? '';
		$link_field  = $settings['linkField'] ?? '';

		// Validate source type against allowed values.
		$allowed_source_types = array( 'current_post', 'post_type', 'site', 'archive', 'current_user', 'request_parameter', 'shortcode' );
		if ( ! in_array( $source_type, $allowed_source_types, true ) ) {
			return '';
		}

		// Validate link field is not empty.
		if ( empty( $link_field ) ) {
			return '';
		}

		// Generate URL based on source type and link field.
		switch ( $source_type ) {
			case 'current_post':
			case 'post_type':
				return self::get_post_url_by_link_field( $link_field, $settings );

			case 'site':
				return self::get_site_url_by_link_field( $link_field );

			case 'archive':
				return self::get_archive_url_by_link_field( $link_field, $settings );

			case 'current_user':
				return self::get_user_url_by_link_field( $link_field, $settings );

			case 'request_parameter':
				return self::get_request_parameter_url( $link_field, $settings );

			case 'shortcode':
				return self::get_shortcode_url( $link_field, $settings );

			default:
				return '';
		}//end switch
	}

	/**
	 * Gets URL for post-related link fields.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $link_field The link field type.
	 * @param array  $settings The dynamic content settings.
	 * @return string The post URL or empty string.
	 */
	private static function get_post_url_by_link_field( $link_field, $settings ) {
		// Sanitize settings and validate link field.
		$settings   = self::sanitize_settings( $settings );
		$link_field = self::validate_link_field( $link_field, 'post' );

		$source      = $settings['source'] ?? array();
		$source_type = $source['type'] ?? '';

		// Get post ID.
		if ( 'post_type' === $source_type ) {
			$post_id = absint( $source['postId'] ?? 0 );
		} else {
			$post_id = get_the_ID();
		}

		// Use the new utility function to get the post URL.
		return self::get_post_url( $post_id, $link_field, $settings );
	}

	/**
	 * Gets URL for site-related link fields.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $link_field The link field type.
	 * @return string The site URL.
	 */
	private static function get_site_url_by_link_field( $link_field ) {
		// Validate link field using utility function.
		$link_field = self::validate_link_field( $link_field, 'site' );

		switch ( $link_field ) {
			case 'site_url':
				return get_bloginfo( 'url' );

			case 'home_url':
			default:
				return home_url();
		}
	}

	/**
	 * Gets URL for archive-related link fields.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $link_field The link field type.
	 * @param array  $settings The settings array.
	 * @return string The archive URL.
	 */
	private static function get_archive_url_by_link_field( $link_field, $settings ) {
		// Sanitize settings and validate link field.
		$settings   = self::sanitize_settings( $settings );
		$link_field = self::validate_link_field( $link_field, 'archive' );

		switch ( $link_field ) {
			case 'archive_url':
				return self::get_current_archive_url();

			case 'archive_meta':
				// For archive meta, return archive URL as default.
				return self::get_current_archive_url();

			default:
				return self::get_current_archive_url();
		}
	}

	/**
	 * Gets URL for user-related link fields.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $link_field The link field type.
	 * @param array  $settings The settings array.
	 * @return string The user URL.
	 */
	private static function get_user_url_by_link_field( $link_field, $settings ) {
		// Sanitize settings and validate link field.
		$settings   = self::sanitize_settings( $settings );
		$link_field = self::validate_link_field( $link_field, 'user' );

		$user = wp_get_current_user();

		if ( ! $user || ! $user->exists() ) {
			return home_url();
		}

		$author_archive_url = get_author_posts_url( $user->ID );

		switch ( $link_field ) {
			case 'user_profile':
				return $author_archive_url;

			case 'user_website':
				return self::validate_url_with_fallback( $user->user_url, $author_archive_url );

			case 'custom_field':
				$meta_key = $settings['source']['userMetaKey'] ?? '';
				return self::get_custom_field_url( $meta_key, $user->ID, 'user', $author_archive_url );

			default:
				return $author_archive_url;
		}
	}

	/**
	 * Gets URL for request parameter link fields.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $link_field The link field type.
	 * @param array  $settings The settings array.
	 * @return string The request parameter value as URL or current page URL as fallback.
	 */
	private static function get_request_parameter_url( $link_field, $settings ) {
		// Sanitize settings.
		$settings  = self::sanitize_settings( $settings );
		$source    = $settings['source'] ?? array();
		$param_key = $source['requestParamKey'] ?? '';

		// Additional validation for parameter key.
		if ( empty( $param_key ) || ! is_string( $param_key ) ) {
			// Fallback to current page URL if no parameter key is set.
			return self::get_current_page_url();
		}

		// Get parameter value based on the parameter key.
		$param_value = '';
		if ( isset( $_GET[ $param_key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			// Phpcs ignore comment is required as nonce verification is not needed for read-only URL parameter access for dynamic content display.
			$param_value = sanitize_text_field( wp_unslash( $_GET[ $param_key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} elseif ( isset( $_POST[ $param_key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			// Phpcs ignore comment is required as nonce verification is not needed for read-only form data access for dynamic content display.
			$param_value = sanitize_text_field( wp_unslash( $_POST[ $param_key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		} else {
			$param_value = get_query_var( $param_key );
			if ( is_string( $param_value ) ) {
				$param_value = sanitize_text_field( $param_value );
			}
		}

		// Use utility function for URL validation with fallback.
		return self::validate_url_with_fallback( $param_value, self::get_current_page_url() );
	}

	/**
	 * Gets URL for shortcode link fields.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $link_field The link field type.
	 * @param array  $settings The settings array.
	 * @return string The shortcode output as URL or current page URL as fallback.
	 */
	private static function get_shortcode_url( $link_field, $settings ) {
		// Sanitize settings first.
		$settings          = self::sanitize_settings( $settings );
		$source            = $settings['source'] ?? array();
		$shortcode_content = $source['shortcodeContent'] ?? '';

		if ( empty( $shortcode_content ) || ! is_string( $shortcode_content ) ) {
			// Fallback to current page URL if no shortcode content is set.
			return self::get_current_page_url();
		}

		// Additional security: validate shortcode format and prevent dangerous shortcodes.
		if ( ! preg_match( '/^\[[\w\-_]+[^\]]*\]$/', trim( $shortcode_content ) ) ) {
			return self::get_current_page_url();
		}

		$shortcode_result = do_shortcode( $shortcode_content );
		
		// Use utility function for URL validation with fallback.
		return self::validate_url_with_fallback( $shortcode_result, self::get_current_page_url() );
	}

	/**
	 * Gets the current page URL.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return string The current page URL.
	 */
	private static function get_current_page_url() {
		global $wp;

		// For posts and pages, use get_permalink.
		if ( is_singular() ) {
			return get_permalink();
		}

		// For archive pages, use get_current_archive_url.
		if ( is_archive() || is_category() || is_tag() || is_tax() || is_author() ) {
			return self::get_current_archive_url();
		}

		// For home page.
		if ( is_home() || is_front_page() ) {
			return home_url();
		}

		// Fallback: construct URL from current request.
		return home_url( add_query_arg( array(), $wp->request ) );
	}

	/**
	 * Retrieves site field value.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $field The site field to retrieve.
	 * @return string|array The field value or empty string if invalid.
	 */
	public static function get_site_field_value( string $field ) {
		// Special handling for site logo.
		if ( 'site_logo' === $field ) {
			return self::get_site_logo();
		}

		// Map standard site fields to their WordPress functions.
		$fields = array(
			'site_title'   => get_bloginfo( 'name' ),
			'site_tagline' => get_bloginfo( 'description' ),
			'site_url'     => get_bloginfo( 'url' ),
			'admin_email'  => get_bloginfo( 'admin_email' ),
		);

		return $fields[ $field ] ?? '';
	}

	/**
	 * Retrieves the site logo data.
	 *
	 * @since 2.0.0-beta.1
	 * 
	 * @return array|string Array with logo URL and ID, or empty string if not set.
	 */
	public static function get_site_logo() {
		$logo_id = get_theme_mod( 'custom_logo' );
	
		// Return early if logo ID is empty.
		if ( empty( $logo_id ) ) {
			return '';
		}

		// Get logo URL.
		$logo_url = wp_get_attachment_image_url( $logo_id, 'full' );
	
		// Return early if logo URL is empty.
		if ( empty( $logo_url ) ) {
			return '';
		}

		// Return logo data.
		return array(
			'url' => esc_url( $logo_url ),
			'id'  => $logo_id,
		);
	}

	/**
	 * Retrieves a user field value.
	 *
	 * Supports both standard user fields and custom meta fields.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $type     The context type ('current_user', 'post_type', etc.).
	 * @param string $field    The field to retrieve.
	 * @param array  $settings The configuration settings.
	 * @return string|array The field value or empty string if invalid.
	 */
	public static function get_user_field_value( string $type, string $field, array $settings ) {
		$user         = null;
		$content_type = $settings['type'] ?? 'text';

		// Handle post-related user contexts.
		if ( in_array( $type, [ 'post_type', 'current_post' ], true ) ) {
			$post_id = absint( $settings['source']['postId'] ?? 0 );

			// Fallback to current post ID if in current_post context.
			if ( ! $post_id && 'current_post' === $type ) {
				$post_id = get_the_ID();
			}

			// Return empty if no post ID found.
			if ( ! $post_id ) {
				return '';
			}

			// Get the author ID from the post.
			$author_id = absint( get_post_field( 'post_author', $post_id ) );
			if ( ! $author_id ) {
				return '';
			}

			$user = get_user_by( 'ID', $author_id );
		} else {
			$user = wp_get_current_user();
		}//end if

		// Return early if no user found.
		if ( ! $user || ! $user->exists() ) {
			return '';
		}

		// Handle custom field mappings.
		if ( 'current_user' === $type && 'custom_input' === $field ) {
			$field = $settings['source']['userMetaKey'] ?? '';
		} elseif ( in_array( $type, [ 'current_post', 'post_type' ], true ) && 'author_info' === $field ) {
			$field = $settings['source']['authorField'] ?? '';
			if ( 'custom' === $field ) {
				$field = $settings['source']['authorMetaKey'] ?? '';
			}
		}

		// Return early if field is empty.
		if ( ! $field ) {
			return '';  
		}

		// if the field is 'avatar' and the content type is 'image', return the avatar URL.
		if ( 'image' === $content_type && 'avatar' === $field ) {
			return array(
				'url' => get_avatar_url( $user->ID, 96 ),
			);
		}

		// Map standard user fields.
		$basic_fields = array(
			'id'           => $user->ID,
			'name'         => trim( $user->first_name . ' ' . $user->last_name ),
			'display_name' => $user->display_name,
			'nicename'     => $user->user_nicename,
			'login'        => $user->user_login,
			'description'  => $user->description,
			'first_name'   => $user->first_name,
			'last_name'    => $user->last_name,
			'email'        => $user->user_email,
			'url'          => $user->user_url,
			'avatar'       => get_avatar_url( $user->ID ),
		);

		// Return basic field if exists.
		if ( array_key_exists( $field, $basic_fields ) ) {
			return $basic_fields[ $field ];
		}

		// Fallback to custom field.
		return CustomFields::get_user_field_value( $field, $user->ID );
	}

	/**
	 * Retrieves the URL of the current archive page.
	 *
	 * Supports various archive types including category, tag, taxonomy,
	 * author, post type, and date archives.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return string The archive URL or current post permalink if not an archive.
	 */
	public static function get_current_archive_url() {
		switch ( true ) {
			case is_category():
			case is_tag():
			case is_tax():
				$term = get_queried_object();
				return get_term_link( $term );
			case is_author():
				return get_author_posts_url( get_queried_object_id() );
			case is_post_type_archive():
				return get_post_type_archive_link( get_post_type() );
			case is_day():
				return get_day_link(
					get_query_var( 'year' ),
					get_query_var( 'monthnum' ),
					get_query_var( 'day' )
				);
			case is_month():
				return get_month_link(
					get_query_var( 'year' ),
					get_query_var( 'monthnum' )
				);
			case is_year():
				return get_year_link( get_query_var( 'year' ) );
			default:
				return get_permalink();
		}//end switch
	}

	/**
	 * Retrieves a filtered list of taxonomies.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param array $args Optional. Arguments to filter taxonomies.
	 *                    Default shows taxonomies registered for nav menus.
	 * @return array Array of taxonomy objects with label/value pairs.
	 */ 
	public static function get_taxonomies( $args = array( 'show_in_nav_menus' => true ) ) {
		global $wp_taxonomies;

		// Filter taxonomies based on the passed arguments.
		$taxonomies = wp_filter_object_list(
			$wp_taxonomies,
			$args 
		);

		// Map taxonomies to label/value pairs.
		return array_map(
			fn( $taxonomy ) => array(
				'label' => $taxonomy->label,
				'value' => $taxonomy->name,
			),
			$taxonomies
		);
	}

	/**
	 * Checks if a block is allowed for dynamic content.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $block_name The block name to check.
	 * @return bool True if allowed, false otherwise.
	 */
	public static function is_allowed_block( $block_name ) {
		// List of allowed blocks.
		$allowed_blocks = array(
			'spectra/content' => true,
			'spectra/button'  => true,
		);

		return isset( $allowed_blocks[ $block_name ] );
	}

	/**
	 * Checks if a block is allowed for dynamic content background image field.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $block_name The block name to check.
	 * @return bool True if allowed, false otherwise.
	 */
	public static function is_image_allowed_block( $block_name ) {
		// List of allowed blocks.
		$allowed_blocks = array(
			'spectra/slider'              => true,
			'spectra/container'           => true,
			'spectra/modal-popup-content' => true,
		);

		return isset( $allowed_blocks[ $block_name ] );
	}

	/**
	 * Checks if a block is allowed for dynamic content link field.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $block_name The block name to check.
	 * @return bool|string The attribute name if allowed, false otherwise.
	 */
	public static function is_link_allowed_block( $block_name ) {
		// List of allowed blocks key-value pairs( block name => attribute name).
		$allowed_blocks = array(
			'spectra/icon'    => 'linkURL',
			'spectra/button'  => 'linkURL',
			'spectra/content' => 'linkURL',
		);

		return isset( $allowed_blocks[ $block_name ] ) ? $allowed_blocks[ $block_name ] : false;
	}

}
