<?php
/**
 * CustomFields class.
 *
 * Handles retrieval and management of custom field data from various sources
 * including ACF, Meta Box, and Pods plugins.
 *
 * @package SpectraPro\Extensions\DynamicContent\Source
 * 
 * @since 2.0.0-beta.1
 */
namespace SpectraPro\Extensions\DynamicContent\Source;

/**
 * CustomFields class.
 * 
 * Provides comprehensive custom field handling for dynamic content, supporting:
 * - Advanced Custom Fields (ACF)
 * - Meta Box
 * - Pods
 * - Native WordPress meta
 * 
 * Includes methods for retrieving values and field definitions from all supported sources.
 * 
 * @since 2.0.0-beta.1
 */
class CustomFields {

	/**
	 * Retrieves Meta Box field value.
	 * 
	 * Handles various Meta Box field types with appropriate processing.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param int    $object_id Object (post, term, user) ID.
	 * @param string $field_id  The field ID.
	 * @param string $type      Field type ('text', 'image', 'url'). Default 'text'.
	 * @return string|array Field value or empty string if not found.
	 */
	private static function get_metabox_value( int $object_id, string $field_id, $type = 'text' ) {
		// Retrieve the raw Meta Box field value.
		$value = \rwmb_get_value( $field_id, array(), $object_id );    
		
		// Get field settings to determine field type.
		$field_data = \rwmb_get_field_settings( $field_id, array(), $object_id );
		$field_type = $field_data['type'] ?? '';

		// Handle file_input field type by returning attachment URL.
		if ( 'file_input' === $field_type ) {
			return wp_get_attachment_url( absint( $value ) );
		}

		// Process array values or convert to string based on field type and requested output.
		return is_array( $value ) ? self::process_metabox_array( $value, $field_type, $type ) : strval( $value );
	}
	
	/**
	 * Retrieves Pods field value.
	 * 
	 * Handles various Pods field types with appropriate processing.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key Meta key.
	 * @param string $pod_type Pod type. Default null (autodetect from post type).
	 * @param string $type     Field type ('text', 'image', 'url'). Default 'text'.
	 * @return string|array|null Field value or null if not found.
	 */
	private static function get_pods_value( int $post_id, string $meta_key, $pod_type = null, $type = 'text' ) {
		// Initialize Pods object for the given post or pod type.
		$pod = \pods( is_null( $pod_type ) ? get_post_type( $post_id ) : $pod_type, $post_id );

		// Validate Pods object and its existence.
		if ( ! is_object( $pod ) || ! $pod->exists() || ! method_exists( $pod, 'field' ) ) {
			return null;

		}//end if

		// Retrieve all fields for the pod.
		$fields = $pod->fields();

		// Return null if no fields exist.
		if ( empty( $fields ) ) {
			return null;
		}

		// Get specific field object by meta key.
		$field_object = $fields[ $meta_key ] ?? null;

		// Return null if field doesn't exist.
		if ( ! $field_object ) {
			return null;
		}
		
		// Prepare arguments for field retrieval.
		$args = array(
			'name'   => $meta_key,
			'single' => true,
		);

		// Retrieve field value.
		$value = $pod->field( $args );

		// Process field value based on field type.
		if ( $field_object && isset( $field_object['type'] ) ) {
			$field_type = $field_object['type'];

			// Handle file/image fields.
			if ( 'file' === $field_type ) {
				// Normalize value to ensure consistency.
				$value         = isset( $value['ID'] ) ? $value : ( is_array( $value ) ? current( $value ) : $value );
				$attachment_id = $value['ID'] ?? '';

				// Return empty string if no attachment ID.
				if ( ! $attachment_id ) {
					return ''; 
				}

				// Return URL for 'url' type.
				if ( 'url' === $type ) {
					return wp_get_attachment_url( $attachment_id );
				}

				// Return full image data array for other types.
				return array(
					'url' => wp_get_attachment_url( $attachment_id ),
					'id'  => $attachment_id,
				);
			}//end if

			// Return the raw value for other field types.
			return $value;
		}//end if

		return null;
	}

	/**
	 * Processes Meta Box array values.
	 * 
	 * Handles complex field types that return arrays of data.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param array  $value   Meta Box field value.
	 * @param string $field_type Field type.
	 * @param string $type       Desired output type ('text', 'image', 'url').
	 * @return array|string Processed value.
	 */
	private static function process_metabox_array( array $value, string $field_type, string $type ) {
		// Handle different field types with appropriate processing.
		switch ( $field_type ) {
			case 'select':
			case 'select_advanced':
			case 'checkbox_list':
			case 'autocomplete':
				// Convert array to comma-separated string for multi-value fields.
				return implode( ', ', $value );
			case 'file':
			case 'file_advanced':
			case 'file_upload':
				// Get first file's URL from array.
				$value = current( $value );
				return isset( $value['url'] ) ? $value['url'] : '';
			case 'image':
			case 'image_advanced':
			case 'image_upload':
			case 'single_image':
				// Normalize value to handle single or array formats.
				$value = isset( $value['ID'] ) ? $value : current( $value );
			
				$attachment_id = $value['ID'] ?? '';

				// Return empty string if no attachment ID.
				if ( ! $attachment_id ) {
					return ''; 
				}

				// Return URL for 'url' type.
				if ( 'url' === $type ) {
					return wp_get_attachment_url( $attachment_id );
				}
				
				// Return full image data array for other types.
				return array(
					'url' => wp_get_attachment_url( $attachment_id ),
					'id'  => $attachment_id,
				);
			default:
				// Extract value from array or return empty string.
				return isset( $value['value'] ) ? strval( $value['value'] ) : '';
		}//end switch
	}

	/**
	 * Processes an ACF array value into a string, array or null.
	 * 
	 * @since 2.0.0-beta.1
	 *
	 * @param array  $value       ACF array value.
	 * @param string $field_type ACF field type.
	 * @param string $type       Desired output type ('text', 'image', 'url').
	 * @return string|array|null Processed value.
	 */
	private static function process_acf_array( $value, $field_type, $type ) {
		// Process ACF array based on field type.
		switch ( $field_type ) {
			case 'image':
				$attachment_id = $value['ID'] ?? '';
				// Return empty string if no attachment ID.
				if ( ! $attachment_id ) {
					return ''; 
				}

				// Return URL for 'url' type.
				if ( 'url' === $type ) {
					return wp_get_attachment_url( $attachment_id );
				}

				// Return full image data array.
				return array(
					'url' => wp_get_attachment_url( $attachment_id ),
					'id'  => $attachment_id,
				);
			case 'file':
			case 'url':
				// Return file or URL field value.
				return $value['url'] ?? '';
			case 'checkbox':
			case 'select':
				$formatted_values = array();
				// Format checkbox/select options into array.
				foreach ( $value as $option ) {
					$formatted_values[] = is_string( $option ) ? $option : ( $option ['label'] ?? '' );
				}
				// Convert to comma-separated string.
				return implode( ', ', $formatted_values );
			case 'page_link':
				// Return first page link.
				return current( $value );
			case 'link':
				// Return link URL.
				return $value['url'] ?? '';
			default:
				// Extract value or return empty string.
				return isset( $value['value'] ) ? strval( $value['value'] ) : '';
		}//end switch
	}

	/**
	 * Generates the ACF identifier based on the queried object.
	 *
	 * @since 2.0.0-beta.1
	 * 
	 * @return string ACF identifier (e.g., "term_123", "user_456").
	 */
	private static function get_acf_identifier() {
		// Determine ACF identifier based on queried object type.
		if ( is_category() || is_tag() || is_tax() ) {
			return 'term_' . get_queried_object_id();
		} elseif ( is_author() ) {
			return 'user_' . get_queried_object_id();
		} else {
			return 'post_' . get_the_ID();
		}
	}

	/**
	 * Validates if a meta key should be included.
	 * 
	 * Filters out private and system meta keys.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $key         Meta key to check.
	 * @param array  $exclude_list List of excluded keys.
	 * @return bool True if key is valid for display.
	 */
	public static function is_valid_meta_key( string $key, array $exclude_list ) {
		// Check if meta key is public and not in exclude list.
		return ! str_starts_with( $key, '_' ) &&
		! str_starts_with( $key, 'wp_' ) &&
		! str_starts_with( $key, 'meta' ) &&
		( strlen( $key ) <= 10 || ! str_starts_with( $key, 'manageedit' ) ) &&
		! in_array( $key, $exclude_list, true );
	}

	/**
	 * Retrieves Pods fields for a user.
	 * 
	 * @since 2.0.0-beta.1
	 *
	 * @param int    $user_id User ID.
	 * @param string $type    Field type filter (text|image|url).
	 * @return array Array of Pods fields.
	 */
	private static function get_pods_user_fields( int $user_id, $type ) {
		// Check if Pods is available and user ID is valid.
		if ( ! function_exists( 'pods' ) || ! $user_id ) {
			return array();
		}

		// Initialize Pods for user.
		$pod = \pods( 'user', $user_id );
		// Return empty array if pod is invalid.
		if ( ! is_object( $pod ) || ! $pod->exists() ) {
			return array();
		}

		// Get all fields for the pod.
		$fields        = $pod->fields();
		$allowed_types = self::get_pods_field_type_by_group( $type );

		$result = array();

		// Filter fields by allowed types.
		foreach ( $fields as $field_name => $field_data ) {
			// Skip if field type doesn't match requested type.
			if ( ! in_array( $field_data['type'], $allowed_types, true ) ) {
				continue;
			}
	
			// Add field to result array.
			$result[ $field_name ] = array(
				'label' => $field_data['label'],
				'value' => $field_name,
			);
		}

		return $result;
	}

	/**
	 * Retrieves a custom field value for a post.
	 * 
	 * Supports multiple field sources with fallback to standard meta.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param int    $post_id  Post ID to retrieve meta from.
	 * @param string $meta_key Meta key to lookup.
	 * @param string $type     Type of field ('text', 'image', 'url'). Default 'text'.
	 * @return string|array Field value or empty string if not found.
	 */
	public static function get_value( int $post_id, string $meta_key, $type = 'text' ) {
		// Return empty for invalid requests.
		if ( ! $post_id || ! $meta_key ) {
			return '';
		}

		// Try ACF first if available.
		if ( class_exists( 'ACF' ) ) {
			$field_object = \get_field_object( $meta_key, $post_id );
			
			// Handle ACF field if found.
			if ( $field_object && isset( $field_object['value'] ) ) {
				$value = get_field( $meta_key, $post_id, true );

				return is_array( $value ) ? self::process_acf_array( $value, $field_object['type'], $type ) : $value;
			}//end if
		}//end if

		// Try Meta Box if available.
		if ( class_exists( 'RW_Meta_Box' ) && function_exists( 'rwmb_meta' ) && 
		self::is_metabox_meta_key( $meta_key ) ) {
			return self::get_metabox_value( $post_id, $meta_key, $type );
		}

		// Try Pods if available.
		if ( function_exists( 'pods' ) ) {
			$value = self::get_pods_value( $post_id, $meta_key, null, $type );

			if ( $value ) {
				return $value;
			}
		}

		// Fallback to standard WordPress meta.
		return get_post_meta( $post_id, $meta_key, true ) ?? '';
	}

	/**
	 * Retrieves available ACF fields for a post.
	 * 
	 * @since 2.0.0-beta.1
	 *
	 * @param int|string $post_id Post ID or user_UserID.
	 * @param string     $type    Field type filter ('text', 'image', 'url'). Default 'text'.
	 * @return array Associative array of field data.
	 */
	public static function get_acf_fields( $post_id, $type = 'text' ) {
		// Validate input and ACF availability.
		if ( ! $post_id || ! class_exists( 'ACF' ) ) {
			return array();
		}

		$allowed_types = self::get_acf_field_type_by_group( $type );
		$fields        = get_field_objects( $post_id );
		$result        = array();

		// Filter ACF fields by allowed types.
		if ( is_array( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( in_array( $field['type'], $allowed_types, true ) ) {
					$result[ $field['name'] ] = array(
						'label' => $field['label'],
						'value' => $field['name'],
					);
				}
			}
		}

		return $result;
	}

	/**
	 * Retrieves available Meta Box fields for a post.
	 * 
	 * @since 2.0.0-beta.1
	 *
	 * @param int    $post_id Post ID.
	 * @param string $type    Field type filter ('text', 'image', 'url'). Default 'text'.
	 * @return array Associative array of field data.
	 */
	public static function get_metabox_fields( int $post_id, string $type = 'text' ) {
		// Validate input and Meta Box availability.
		if ( ! $post_id || ! class_exists( 'RW_Meta_Box' ) ) {
			return array();
		}

		$allowed_types = self::get_metabox_field_type_by_group( $type );
		$fields        = \rwmb_get_object_fields( $post_id, get_post_type( $post_id ) ); 
		$result        = array();

		// Filter Meta Box fields by allowed types.
		if ( is_array( $fields ) && ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( in_array( $field['type'], $allowed_types, true ) ) {
					$result[] = array(
						'label' => $field['name'],
						'value' => $field['id'],
					);
				}
			}
		}

		return $result;
	}

	/**
	 * Retrieves available Pods fields for a post.
	 * 
	 * @since 2.0.0-beta.1
	 *
	 * @param int    $post_id Post ID.
	 * @param string $type    Field type filter ('text', 'image', 'url'). Default 'text'.
	 * @return array Associative array of field data.
	 */
	public static function get_pods_fields( int $post_id, string $type = 'text' ): array {
		// Validate input and Pods availability.
		if ( ! function_exists( 'pods' ) || ! $post_id ) {
			return array();
		}

		// Initialize Pods for the post.
		$post_type = get_post_type( $post_id );
		$pod       = \pods( $post_type, $post_id );

		// Return empty array if pod is invalid.
		if ( ! is_object( $pod ) || ! $pod->exists() ) {
			return array();
		}

		$allowed_types = self::get_pods_field_type_by_group( $type );
		$fields        = $pod->fields();
		$result        = array();

		// Filter Pods fields by allowed types.
		foreach ( $fields as $field_name => $field_data ) {
			if ( in_array( $field_data['type'], $allowed_types, true ) ) {
				$result[ $field_name ] = array(
					'label' => $field_data['label'],
					'value' => $field_name,
				);
			}
		}

		return $result;
	}

	/**
	 * Returns allowed ACF field types based on group.
	 * 
	 * @since 2.0.0-beta.1
	 *
	 * @param string $type Field type group ('text', 'image', 'url').
	 * @return array Array of allowed field types.
	 */
	public static function get_acf_field_type_by_group( string $type ) {
		// Define allowed ACF field types for each group.
		$types = array(
			'image' => array(
				'image',
			),
			'url'   => array(
				'text',
				'email',
				'image',
				'file',
				'page_link',
				'url',
				'link',
			),
			'text'  => array(
				'text',
				'textarea',
				'number',
				'range',
				'email',
				'url',
				'password',
				'wysiwyg',
				'select',
				'checkbox',
				'radio',
				'true_false',
				'date_picker',
				'time_picker',
				'date_time_picker',
				'color_picker',
			),
		);

		// Return field types for the specified group or default to text.
		return $types[ $type ] ?? $types['text'];
	}
	
	/**
	 * Returns allowed Meta Box field types based on group.
	 * 
	 * @since 2.0.0-beta.1
	 *
	 * @param string $type Field type group ('text', 'image', 'url').
	 * @return array Array of allowed field types.
	 */
	public static function get_metabox_field_type_by_group( string $type ) {
		// Define allowed Meta Box field types for each group.
		$types = array(
			'image' => array(
				'image',
				'image_advanced',
				'image_upload',
				'single_image',
			),
			'text'  => array(
				'text',
				'email',
				'number',
				'textaraa',
				'select',
				'radio',
				'checkbox',
				'checkbox_list',
				'autocomplete',
				'color',
				'date',
				'datetime',
				'time',
				'heading',
				'password',
				'radio',
				'select_advanced',
				'wysiwyg',
				'url',
				'range',
			),
			'url'   => array(
				'url',
				'file',
				'file_advanced',
				'file_input',
				'file_upload',
				'image',
				'image_advanced',
				'image_upload',
				'single_image',
			),
		);

		// Return field types for the specified group or default to text.
		return $types[ $type ] ?? $types['text'];
	}

	/**
	 * Returns allowed Pods field types based on group.
	 * 
	 * @since 2.0.0-beta.1
	 *
	 * @param string $type Field type group ('text', 'image', 'url').
	 * @return array Array of allowed field types.
	 */
	public static function get_pods_field_type_by_group( string $type ) {
		// Define allowed Pods field types for each group.
		$types = array(
			'image' => array(
				'file',
			),
			'text'  => array(
				'text',
				'paragraph',
				'wysiwyg',
				'code',
				'datetime',
				'date',
				'time',
				'number',
				'currency',
				'phone',
				'email',
				'password',
				'website',
				'color',
				'boolean',
				'comment',
			),
			'url'   => array(
				'file',
				'website',
			),
		);

		// Return field types for the specified group or default to text.
		return $types[ $type ] ?? $types['text'];
	}

	/**
	 * Checks if a meta key belongs to Meta Box.
	 * 
	 * @since 2.0.0-beta.1
	 *
	 * @param string $meta_key    Meta key to check.
	 * @param string $object_type Object type ('post', 'user', etc.). Default 'post'.
	 * @return bool True if meta key is from Meta Box.
	 */
	public static function is_metabox_meta_key( string $meta_key, string $object_type = 'post' ): bool {
		// Get Meta Box registry.
		$registry   = rwmb_get_registry( 'meta_box' );
		$meta_boxes = $registry->get_by( [ 'object_type' => $object_type ] );

		// Check if meta key exists in any Meta Box fields.
		foreach ( $meta_boxes as $mb_object ) {
			foreach ( $mb_object->meta_box['fields'] as $field ) {
				if ( $field['id'] === $meta_key ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Retrieves a user meta value.
	 * 
	 * Supports ACF, Meta Box, Pods, and native WordPress meta.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $meta_key The meta key to retrieve.
	 * @param int    $user_id  The user ID.
	 * @param string $type     The field type ('text', 'image', 'url'). Default 'text'.
	 * @return string|array The meta value, or empty string if not found.
	 */
	public static function get_user_field_value( string $meta_key, int $user_id, $type = 'text' ) {
		// Validate meta key.
		if ( ! $meta_key ) {
			return '';
		}

		// Try ACF fields.
		if ( class_exists( 'ACF' ) ) {
			$field_object = \get_field_object( $meta_key, "user_{$user_id}" );
	
			// Process ACF field value if found.
			if ( $field_object && isset( $field_object['value'] ) ) {
				$value = get_field( $meta_key, "user_{$user_id}", true );

				return is_array( $value ) ? self::process_acf_array( $value, $field_object['type'], $type ) : $value;
			}//end if
		}//end if

		// Try Meta Box fields.
		if ( class_exists( 'RW_Meta_Box' ) && function_exists( 'rwmb_meta' ) && 
		self::is_metabox_meta_key( $meta_key ) ) {
			return self::get_metabox_value( $user_id, $meta_key, $type );
		}

		// Try Pods fields.
		if ( function_exists( 'pods' ) ) {
			$value = self::get_pods_value( $user_id, $meta_key, 'user', $type );

			if ( $value ) {
				return $value;
			}
		}

		// Fallback to native WordPress user meta.
		$meta_value = get_user_meta( $user_id, $meta_key, true );
		return is_string( $meta_value ) ? $meta_value : '';
	}

	/**
	 * Retrieves a meta value for the current archive.
	 * 
	 * Supports term meta, author meta, and post type archive options.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $meta_key The meta key to fetch.
	 * @param string $type     The field type ('text', 'image', 'url'). Default 'text'.
	 * @return string The meta value, or empty string if not found.
	 */
	public static function get_archive_meta_value( string $meta_key, $type = 'text' ) {
		// Validate meta key.
		if ( empty( $meta_key ) ) {
			return '';
		}

		// Get queried object ID.
		$object_id = get_queried_object_id() ? get_queried_object_id() : get_the_ID();
		if ( ! $object_id ) {
			return '';
		}

		// Try ACF fields.
		if ( class_exists( 'ACF' ) ) {
			$field_object = \get_field_object( $meta_key, self::get_acf_identifier() );
			
			// Process ACF field value if found.
			if ( $field_object && isset( $field_object['value'] ) ) {
				$value = get_field( $meta_key, self::get_acf_identifier(), true );

				return is_array( $value ) ? self::process_acf_array( $value, $field_object['type'], $type ) : $value;
			}
		}//end if

		// Fallback to WordPress core meta based on archive type.
		if ( is_category() || is_tag() || is_tax() ) {
			return get_term_meta( $object_id, $meta_key, true );
		} elseif ( is_author() ) {
			return get_user_meta( $object_id, $meta_key, true );
		} elseif ( is_post_type_archive() ) {
			// Handle custom post type archive options.
			return get_option( $meta_key, '' );
		}

		return '';
	}

	/**
	 * Retrieves available user meta fields.
	 * 
	 * @since 2.0.0-beta.1
	 *
	 * @param int    $user_id User ID.
	 * @param string $type    Field type filter ('text', 'image', 'url'). Default 'text'.
	 * @return array Array of meta field options.
	 */
	public static function get_user_meta_fields( int $user_id, $type = 'text' ) {
		// Validate user ID.
		if ( ! $user_id ) {
			return array();
		}

		$options      = array();
		$exclude_list = self::get_excluded_meta_keys();

		// Process ACF fields.
		$acf_fields = self::get_acf_fields( "user_$user_id", $type );
		foreach ( $acf_fields as $field ) {
			$key = $field['value'];
			if ( self::is_valid_meta_key( $key, $exclude_list ) ) {
				$options[] = array(
					'value' => $key,
					'label' => $field['label'],
					'group' => 'ACF',
				);
			}
		}
		
		// Process Meta Box fields.
		if ( class_exists( 'RW_Meta_Box' ) ) {
			$fields        = \rwmb_get_object_fields( $user_id, 'user' );
			$allowed_types = self::get_metabox_field_type_by_group( $type );
			foreach ( $fields as $field ) {
				// Skip if field type doesn't match requested type.
				if ( ! in_array( $field['type'], $allowed_types, true ) ) {
					continue;
				}
				
				$key = $field['id'];
				// Avoid duplicates and validate key.
				if ( self::is_valid_meta_key( $key, $exclude_list ) && ! in_array( $key, array_column( $options, 'value' ) ) ) {
					$options[] = array(
						'value' => $key,
						'label' => $field['name'],
						'group' => 'Meta Box',
					);
				}
			}
		}

		// Process Pods fields.
		$pods_fields = self::get_pods_user_fields( $user_id, $type );
		foreach ( $pods_fields as $field ) {
			$key = $field['value'];
			// Avoid duplicates and validate key.
			if ( self::is_valid_meta_key( $key, $exclude_list ) && ! in_array( $key, array_column( $options, 'value' ) ) ) {
				$options[] = array(
					'value' => $key,
					'label' => $field['label'],
					'group' => 'Pods',
				);
			}
		}

		// Process native WordPress user meta for text type.
		if ( 'text' === $type ) {
			$custom_keys = get_user_meta( $user_id );

			foreach ( $custom_keys as $key => $data ) {
				// Skip already processed fields.
				if ( in_array( $key, array_column( $options, 'value' ) ) ) {
					continue;
				}

				// Validate and format meta key.
				if ( self::is_valid_meta_key( $key, $exclude_list ) ) {
					$label     = $key;
					$options[] = array(
						'value' => $key,
						'label' => ucwords( str_replace( [ '_', '-' ], ' ', $label ) ),
					);
				}
			}       
		}

		return $options;
	}

	/**
	 * Returns list of excluded meta keys.
	 * 
	 * These are typically WordPress core or plugin meta keys that shouldn't be exposed.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return array Array of meta keys to exclude.
	 */
	public static function get_excluded_meta_keys() {
		// Return array of meta keys to exclude from processing.
		return array(
			'nickname',
			'first_name',
			'last_name',
			'description',
			'rich_editing',
			'syntax_highlighting',
			'comment_shortcuts',
			'admin_color',
			'use_ssl',
			'show_admin_bar_front',
			'locale',
			'wp_capabilities',
			'wp_user_level',
			'dismissed_wp_pointers',
			'show_welcome_panel',
			'session_tokens',
			'wp_user-settings',
			'wp_user-settings-time',
			'wp_dashboard_quick_press_last_post_id',
			'community-events-location',
			'last_update',
			'wc_last_active',
			'woocommerce_admin_activity_panel_inbox_last_read',
			'wp_woocommerce_product_import_mapping',
			'wp_product_import_error_log',
			'elementor_introduction',
			'nav_menu_recently_edited',
			'managenav-menuscolumnshidden',
			'rtladminbar',
			'metaboxhidden_',
			'enable_custom_fields',
			'metaboxhidden_nav-menus',
		);
	}
}
