<?php
/**
 * Dynamic Content REST API Controller.
 *
 * Handles REST API endpoints for retrieving dynamic content data such as custom fields.
 *
 * @since 2.0.0-beta.1
 * 
 * @package SpectraPro\RestApi\Controllers\Version2
 */

namespace SpectraPro\RestApi\Controllers\Version2;

use SpectraPro\Extensions\DynamicContent\Helper;
use SpectraPro\Extensions\DynamicContent\Source\CustomFields;
use Spectra\Traits\Singleton;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Manages REST API endpoints for dynamic content operations such as retrieving dynamic data.
 *
 * @since 2.0.0-beta.1
 */
class DynamicContentController extends BaseController {

	use Singleton;

	/**
	 * Route base.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @var string
	 */
	protected $rest_base = 'dynamic-content';

	/**
	 * Determines the user ID based on the post ID or current user.
	 *
	 * @since 2.0.0-beta.1
	 * 
	 * @param int $post_id The post ID from the request.
	 * @return int The determined user ID.
	 */
	private function determine_user_id( $post_id ) {
		// Try to get user ID from post author if post exists.
		$post = get_post( $post_id );

		if ( $post instanceof \WP_Post ) {
			return (int) $post->post_author;
		}

		// Fallback to current user ID if no post found.
		return get_current_user_id();
	}

	/**
	 * Checks if the given dynamic content settings are valid.
	 *
	 * Checks that the required fields are present in the settings, and that they are valid.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $settings The dynamic content settings.
	 *
	 * @return array|WP_Error Settings array if the settings are valid, or an error object if not.
	 */
	private function is_valid_settings( $settings ) {
		// Decode JSON settings string to array.
		$settings = json_decode( $settings, true );
		if ( ! is_array( $settings ) ) {
			return $this->error( 'rest_invalid_settings', __( 'Invalid settings provided.', 'spectra-pro' ) );
		}

		// Get source configuration from settings.
		$source = $settings['source'] ?? [];
	
		// Check if source is empty.
		if ( empty( $source ) ) {
			return $this->error(
				'rest_invalid_source',
				__( 'Invalid source provided.', 'spectra-pro' ),
			);
		}
	
		// Extract type and field from source.
		$type  = $source['type'] ?? '';
		$field = $source['field'] ?? '';
	
		// Validate required fields.
		if ( empty( $type ) || empty( $field ) ) {
			return new WP_Error(
				'rest_invalid_type_or_field',
				__( 'Both type and field are required.', 'spectra-pro' ),
				[ 'status' => 400 ]
			);
		}
	
		// Switch based on source type and validate accordingly.
		switch ( $type ) {
			case 'post_type':
				// Validate post ID for post_type source.
				if ( empty( $source['postId'] ) ) {
					return new WP_Error(
						'rest_missing_post_id',
						__( 'Post ID is required for post_type source.', 'spectra-pro' ),
						[ 'status' => 400 ]
					);
				}
				break;
	
			case 'current_user':
				// Validate user meta fields for current_user source.
				if ( 'custom_field' === $field ) {
					if ( empty( $source['userMetaKey'] ) ) {
						return new WP_Error(
							'rest_missing_userMetaKey',
							__( 'User meta key is required for current_user with custom_field.', 'spectra-pro' ),
							[ 'status' => 400 ]
						);
					}
					if ( 'custom' === $source['userMetaKey'] && empty( $source['user_custom_meta_key'] ) ) {
						return new WP_Error(
							'rest_missing_custom_userMetaKey',
							__( 'Custom user meta key is required when using custom meta.', 'spectra-pro' ),
							[ 'status' => 400 ]
						);
					}
				}
				break;
	
			case 'archive':
				// Validate archive meta key for archive source.
				if ( 'archive_meta' === $field && empty( $source['archiveMetaKey'] ) ) {
					return new WP_Error(
						'rest_missing_archiveMetaKey',
						__( 'Archive meta key is required for archive_meta field.', 'spectra-pro' ),
						[ 'status' => 400 ]
					);
				}
				break;
	
			case 'request_parameter':
				// Validate request parameter key.
				if ( empty( $source['requestParamKey'] ) ) {
					return new WP_Error(
						'rest_missing_requestParamKey',
						__( 'Request parameter key is required for request_parameter source.', 'spectra-pro' ),
						[ 'status' => 400 ]
					);
				}
				break;
	
			default:
				break;
		}//end switch
	
		// If all checks pass, return true.
		return $settings;
	}   

	/**
	 * Register routes.
	 * 
	 * Sets up the endpoints for dynamic content operations.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return void
	 */
	public function register_routes() {

		/**
		 * Endpoint to get dynamic content value.
		 */
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_dynamic_content_value' ),
				'permission_callback' => array( $this, 'check_content_permissions' ),
			)
		);     
		
		/**
		 * Endpoint to get user custom fields by post ID or current user.
		 */
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/user-custom-fields',
			array(
				'args' => array(
					'post_id' => array(
						'description'       => __( 'Unique identifier for the post.', 'spectra-pro' ),
						'type'              => 'integer',
						'validate_callback' => array( $this, 'validate_post_id' ),
					),
					'type'    => array(
						'description'       => __( 'Type of custom field to retrieve.', 'spectra-pro' ),
						'type'              => 'string',
						'default'           => 'text',
						'enum'              => array( 'text', 'image', 'url' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_user_custom_fields' ),
					'permission_callback' => array( $this, 'check_content_permissions' ),
				),
			)
		);

		/**
		 * Endpoint to get post custom fields by post ID.
		 */
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/post-custom-fields',
			array(
				'args' => array(
					'post_id' => array(
						'description'       => __( 'Unique identifier for the post.', 'spectra-pro' ),
						'type'              => 'integer',
						'required'          => true,
						'validate_callback' => array( $this, 'validate_post_id' ),
					),
					'type'    => array(
						'description'       => __( 'Type of custom field to retrieve.', 'spectra-pro' ),
						'type'              => 'string',
						'default'           => 'text',
						'enum'              => array( 'text', 'image', 'url' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_post_custom_fields' ),
					'permission_callback' => array( $this, 'check_content_permissions' ),
				),
			)
		);

		/**
		 * Endpoint to get terms by post ID.
		 */
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/terms',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_terms' ),
					'permission_callback' => array( $this, 'check_content_permissions' ),
				),
			)
		);

		/**
		 * Endpoint to get term meta by queried object.
		 */
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/terms-meta',
			array(
				'args' => array(
					'type' => array(
						'description'       => __( 'Type of custom field to retrieve.', 'spectra-pro' ),
						'type'              => 'string',
						'default'           => 'text',
						'enum'              => array( 'text', 'image', 'url' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_term_meta' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			)
		);

		/**
		 * Endpoint to get comments count for a post.
		 */
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/comments-count',
			array(
				'args' => array(
					'post_id' => array(
						'description'       => __( 'Unique identifier for the post.', 'spectra-pro' ),
						'type'              => 'integer',
						'required'          => true,
						'validate_callback' => array( $this, 'validate_post_id' ),
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_comments_count' ),
					'permission_callback' => array( $this, 'check_content_permissions' ),
				),
			)
		);
	}

	/**
	 * Checks if the current user has administrative permissions.
	 *
	 * This function verifies if the current user has the capability to manage options,
	 * which is typically associated with administrative privileges.
	 *
	 * @since 2.0.0-beta.1
	 * 
	 * @return bool True if the user has admin permissions, false otherwise.
	 */
	public function check_admin_permissions() {
		// Check if user can manage options (admin capability).
		return current_user_can( 'manage_options' );
	}

	/**
	 * Checks permissions for content-related REST API requests.
	 *
	 * Verifies if the current user has the necessary capabilities to access content endpoints.
	 *
	 * @since 2.0.0-beta.1
	 * 
	 * @param WP_REST_Request $request The current request object.
	 * @return bool|WP_Error True if permitted, WP_Error if not.
	 */
	public function check_content_permissions( $request ) {
		// Check if user can edit posts (basic content editing capability).
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'rest_forbidden_content_access',
				__( 'Sorry, you are not allowed to access content data.', 'spectra-pro' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return true;
	}

	/**
	 * Validates the post ID parameter.
	 *
	 * Ensures the provided post ID is valid and exists.
	 *
	 * @since 2.0.0-beta.1
	 * 
	 * @param mixed           $value   The parameter value to validate.
	 * @param WP_REST_Request $request The current request object.
	 * @param string          $param   The parameter name.
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	public function validate_post_id( $value, $request, $param ) {
		// Convert to integer and validate.
		$post_id = absint( $value );
		if ( $post_id <= 0 || ! get_post( $post_id ) ) {
			return new WP_Error(
				'rest_invalid_post_id',
				__( 'Invalid post ID provided.', 'spectra-pro' ),
				[ 'status' => 400 ]
			);
		}
		return true;
	}

	/**
	 * Processes and validates dynamic content settings from a REST API request.
	 *
	 * Validates the settings provided in the request parameters and returns
	 * a response indicating success or any validation errors.
	 * 
	 * @since 2.0.0-beta.1
	 *
	 * @param WP_REST_Request $request The current request object containing parameters.
	 * @return WP_REST_Response|WP_Error A response indicating validation success or error details.
	 */
	public function get_dynamic_content_value( $request ) {
		// Get settings from request parameters.
		$params   = $request->get_params();
		$settings = $params['settings'] ?? '';

		// Validate settings exist.
		if ( empty( $settings ) ) {
			return $this->error(
				'rest_invalid_dynamic_content_settings',
				__( 'Invalid dynamic content settings provided.', 'spectra-pro' ),
			);
		}
	
		// Validate settings structure.
		$settings = $this->is_valid_settings( $settings );
	
		// Return error if validation fails.
		if ( is_wp_error( $settings ) ) {
			return $settings;
		}
	
		// Get and return dynamic content value.
		return $this->success( Helper::get_dynamic_content_value( $settings, false ) );
	}

	/**
	 * Retrieves user fields for a given post or current user.
	 *
	 * Fetches custom fields (ACF and meta) for a user associated with a post or the current user.
	 *
	 * @since 2.0.0-beta.1
	 * 
	 * @param WP_REST_Request $request The current request object.
	 * @return WP_REST_Response|WP_Error The response containing user fields or an error.
	 */
	public function get_user_custom_fields( $request ) {
		// Get parameters from request.
		$params  = $request->get_params();
		$post_id = absint( $params['post_id'] ?? 0 );
		$type    = sanitize_text_field( $params['type'] ?? 'text' );

		// Determine user ID based on post or current user.
		$user_id = $this->determine_user_id( $post_id );

		// Validate user ID.
		if ( ! $user_id ) {
			return $this->error(
				'rest_invalid_user_id',
				__( 'Invalid user ID provided.', 'spectra-pro' )
			);
		}

		// Get user meta fields and return them.
		$options = CustomFields::get_user_meta_fields( $user_id, $type );

		return $this->success( $options );
	}

	/**
	 * Retrieves custom fields for a given post or post type.
	 *
	 * Fetches custom fields (ACF, Meta Box, Pods, and native meta) for a given post or post type.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param WP_REST_Request $request The current request object.
	 * @return WP_REST_Response|WP_Error The response containing custom fields or an error.
	 */
	public function get_post_custom_fields( $request ) {
		// Get parameters from request.
		$params  = $request->get_params();
		$post_id = absint( $params['post_id'] ?? 0 );
		$type    = sanitize_text_field( $params['type'] ?? 'text' );
		$options = [];

		// Validate post ID.
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return $this->error(
				'rest_invalid_post_id',
				__( 'Invalid post ID provided.', 'spectra-pro' )
			);
		}

		// 1. Get ACF Fields.
		$acf_fields = CustomFields::get_acf_fields( $post_id, $type );
		foreach ( $acf_fields as $field ) {
			$options[] = [
				'value' => $field['value'],
				'label' => $field['label'],
				'group' => 'ACF',
			];
		}

		// 2. Get Meta Box Fields.
		$metabox_fields = CustomFields::get_metabox_fields( $post_id, $type );
		foreach ( $metabox_fields as $field ) {
			if ( ! in_array( $field['value'], array_column( $options, 'value' ) ) ) {
				$options[] = [
					'value' => $field['value'],
					'label' => $field['label'],
					'group' => 'Meta Box',
				];
			}
		}

		// 3. Get Pods Fields.
		$pods_fields = CustomFields::get_pods_fields( $post_id, $type );
		foreach ( $pods_fields as $field ) {
			if ( ! in_array( $field['value'], array_column( $options, 'value' ) ) ) {
				$options[] = [
					'value' => $field['value'],
					'label' => $field['label'],
					'group' => 'Pods',
				];
			}
		}

		// 4. Get Native WordPress Custom Fields (only for text type).
		if ( 'text' === $type ) {
			$exclude_list = CustomFields::get_excluded_meta_keys();
			$custom_keys  = get_post_custom_keys( $post_id );
		
			if ( is_array( $custom_keys ) ) {
				foreach ( $custom_keys as $key ) {
					// Skip if already exists in any of the previous fields.
					if ( in_array( $key, array_column( $options, 'value' ) ) ) {
						continue;
					}

					// Skip excluded keys and private fields.
					if ( ! CustomFields::is_valid_meta_key( $key, $exclude_list ) ) {
						continue;
					}

					$options[] = array(
						'value' => $key,
						'label' => ucwords( str_replace( [ '_', '-' ], ' ', $key ) ),
						'group' => __( 'Custom Fields', 'spectra-pro' ),
					);
				}
			}
		}//end if

		// Remove duplicates while preserving array keys.
		$unique_options = [];
		foreach ( $options as $option ) {
			if ( ! isset( $unique_options[ $option['value'] ] ) ) {
				$unique_options[ $option['value'] ] = $option;
			}
		}

		// Return unique options.
		return $this->success( array_values( $unique_options ) );
	}

	/**
	 * Retrieves taxonomies that are available to be shown in navigation menus.
	 * 
	 * @since 2.0.0-beta.1
	 *
	 * @param WP_REST_Request $request The current request object.
	 * @return WP_REST_Response The response containing the list of taxonomies.
	 */
	public function get_terms( $request ) {
		// Get and return taxonomies using Helper class.
		return $this->success( Helper::get_taxonomies() );
	}

	/**
	 * Get Term Meta for the queried object
	 * 
	 * @since 2.0.0-beta.1
	 *
	 * @param object $request Rest API request param.
	 * @return array
	 */
	public function get_term_meta( $request ) {
		// Get parameters from request.
		$params  = $request->get_params();
		$type    = sanitize_text_field( $params['type'] ?? 'text' );
		$options = [];

		// Get taxonomies associated with the post type.
		$taxonomy_args   = array(
			'show_in_nav_menus' => true,
		);
		$taxonomies      = Helper::get_taxonomies( $taxonomy_args );
		$taxonomy_values = wp_list_pluck( $taxonomies, 'value' );
	
		// Return empty if no taxonomies found.
		if ( empty( $taxonomy_values ) ) {
			return $this->success( $options );
		}

		// Get allowed field types based on requested type.
		$allowed_types = CustomFields::get_acf_field_type_by_group( $type );

		// 1. ACF Support - Get ACF fields for taxonomies.
		if ( class_exists( 'ACF' ) ) {
			$field_groups = \acf_get_field_groups(
				array(
					'taxonomy' => $taxonomy_values,
				)
			);

			if ( ! empty( $field_groups ) ) {
				foreach ( $field_groups as $field_group ) {

					$fields = \acf_get_fields( $field_group['key'] );
			
					if ( empty( $fields ) ) {
						continue;
					}

					// Add valid fields to options.
					foreach ( $fields as $field ) {
						if ( in_array( $field['type'], $allowed_types, true ) ) {
							$options[] = [
								'label' => $field['label'],
								'value' => $field['name'],
								'group' => 'ACF',
							];
						}
					}
				}//end foreach
			}//end if
		}//end if

		return $this->success( $options );
	}

	/**
	 * Retrieves the comments count for a given post ID.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param WP_REST_Request $request The current request object.
	 * @return WP_REST_Response The response containing the comments count.
	 */
	public function get_comments_count( $request ) {
		// Get post ID from request.
		$post_id = $request->get_param( 'post_id' );

		// Get and return comments count.
		return $this->success( get_comments_number( $post_id ) );
	}
}
