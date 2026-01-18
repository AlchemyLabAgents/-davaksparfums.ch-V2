<?php
/**
 * Posts data source for dynamic content.
 *
 * Provides methods to retrieve various post-related dynamic content values.
 *
 * @package SpectraPro\Extensions\DynamicContent\Source
 * 
 * @since 2.0.0-beta.1
 */
namespace SpectraPro\Extensions\DynamicContent\Source;

use SpectraPro\Extensions\DynamicContent\Helper;

/**
 * Posts class.
 * 
 * Handles retrieval of dynamic content values from posts including:
 * - Standard post fields (title, excerpt, etc.)
 * - Custom fields
 * - Featured images
 * - Dates and times
 * - Terms and taxonomies
 * - Comments counts
 * 
 * @since 2.0.0-beta.1
 */
class Posts {

	/**
	 * Gets featured image data for a post.
	 * 
	 * Returns different properties of the featured image based on settings.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param \WP_Post $post     The post object.
	 * @param array    $settings The dynamic content settings.
	 * @return string|array The image data or empty string if no featured image.
	 */
	private static function get_featured_image_field( $post, $settings ) {
		$attachment_id = get_post_thumbnail_id( $post->ID );
		if ( ! $attachment_id ) {
			return '';
		}

		$content_type = $settings['type'] ?? 'text';

		// Return image array for image-type fields.
		if ( 'image' === $content_type ) {
			return array(
				'url' => wp_get_attachment_url( $attachment_id ),
				'id'  => $attachment_id,
			);
		}

		// Get specific image property based on settings.
		$field = $settings['source']['featuredImageField'] ?? 'title';
		
		switch ( $field ) {
			case 'title':
				return get_the_title( $attachment_id );
			case 'alt_text':
				return get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
			case 'caption':
				return wp_get_attachment_caption( $attachment_id );
			case 'description':
				return get_post_field( 'post_content', $attachment_id );
			case 'link':
				return wp_get_attachment_url( $attachment_id );
			case 'source_url':
				return wp_get_attachment_url( $attachment_id );
			default:
				return wp_get_attachment_url( $attachment_id );
		}
	}

	/**
	 * Gets a custom field value for a post.
	 * 
	 * Supports both standard meta and custom field plugins (ACF, Meta Box, Pods).
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param \WP_Post $post     The post object.
	 * @param array    $settings The dynamic content settings.
	 * @return string The custom field value or empty string.
	 */
	private static function get_custom_field( $post, $settings ) {
		$custom_field = $settings['source']['postCustomField'] ?? '';
		if ( empty( $custom_field ) ) {
			return '';
		}

		$post_meta_key = $settings['source']['postMetaKey'] ?? '';

		// Handle custom meta key if specified.
		if ( 'custom' === $custom_field && ! empty( $post_meta_key ) ) {
			$custom_field = $post_meta_key;
		}

		return CustomFields::get_value( $post->ID, $custom_field );
	}

	/**
	 * Gets formatted post date based on settings.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param \WP_Post $post     The post object.
	 * @param array    $settings The dynamic content settings.
	 * @return string Formatted date string.
	 */
	private static function get_post_date( $post, $settings ) {
		$date_type     = $settings['source']['dateType'] ?? 'published';
		$date_format   = $settings['source']['dateFormat'] ?? 'default';
		$custom_format = $settings['source']['customDateFormat'] ?? '';

		// Get appropriate date based on type.
		$date = ( 'modified' == $date_type ) ? $post->post_modified_gmt : $post->post_date_gmt;

		// Human readable format.
		if ( 'human_readable' === $date_format ) {
			return human_time_diff( strtotime( $date ) );
		}

		// Map format settings to actual date formats.
		$format_map = array(
			'default' => 'F j, Y',
			'F j, Y'  => 'F j, Y',
			'Y-m-d'   => 'Y-m-d',
			'm/d/Y'   => 'm/d/Y',
			'd/m/Y'   => 'd/m/Y',
			'custom'  => $custom_format ? $custom_format : 'F j, Y',
		);

		$format = $format_map[ $date_format ] ?? $format_map['default'];

		return date_i18n( $format, strtotime( $date ) );
	}

	/**
	 * Gets formatted post time based on settings.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param \WP_Post $post     The post object.
	 * @param array    $settings The dynamic content settings.
	 * @return string Formatted time string.
	 */
	private static function get_post_time( $post, $settings ) {
		$time_type     = $settings['source']['timeType'] ?? 'published';
		$time_format   = $settings['source']['timeFormat'] ?? 'default';
		$custom_format = $settings['source']['customTimeFormat'] ?? '';

		// Get appropriate time based on type.
		$date = ( 'modified' == $time_type ) ? $post->post_modified_gmt : $post->post_date_gmt;

		// Map format settings to actual time formats.
		$format_map = array(
			'default' => 'g:i a',
			'g:i a'   => 'g:i a',
			'g:i A'   => 'g:i A',
			'H:i'     => 'H:i',
			'custom'  => $custom_format ? $custom_format : 'g:i a',
		);

		$format = $format_map[ $time_format ] ?? $format_map['default'];

		return date_i18n( $format, strtotime( $date ) );
	}

	/**
	 * Gets post terms as a formatted string.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param \WP_Post $post     The post object.
	 * @param array    $settings The dynamic content settings.
	 * @return string Terms list or empty string if no terms.
	 */
	private static function get_post_terms( $post, $settings ) {
		$taxonomy_type = $settings['source']['taxonomyType'] ?? 'category';
		$separator     = $settings['source']['taxonomyValueSeparator'] ?? ',';

		$terms = wp_get_post_terms( $post->ID, $taxonomy_type, [ 'fields' => 'names' ] );
	
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '';
		}

		return implode( $separator, $terms );
	}

	/**
	 * Gets formatted comments count string.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param \WP_Post $post     The post object.
	 * @param array    $settings The dynamic content settings.
	 * @return string Formatted comments count.
	 */
	private static function get_comments_number( $post, $settings ) {
		$count = get_comments_number( $post->ID );
		
		// Get strings from settings with defaults.
		$no_comments   = $settings['source']['noComments'] ?? __( 'No Responses', 'spectra-pro' );
		$one_comment   = $settings['source']['oneComment'] ?? __( 'One Response', 'spectra-pro' );
		$many_comments = $settings['source']['manyComments'] ?? __( '{number} Responses', 'spectra-pro' );

		if ( 0 === $count ) {
			return $no_comments;
		}

		if ( 1 === $count ) {
			return $one_comment;
		}

		return str_replace( '{number}', $count, $many_comments );
	}

	/**
	 * Gets a dynamic content value from a post.
	 * 
	 * Main entry point for retrieving post-related dynamic content.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $type    The content source type ('post_type' or 'current_post').
	 * @param string $field   The field to retrieve (e.g. 'post_title', 'featured_image').
	 * @param array  $settings The dynamic content settings.
	 * @return string The retrieved value or empty string if not found.
	 */
	public static function get_value( $type, $field, $settings ) {
		// Get the target post based on type.
		if ( 'post_type' === $type ) {
			$post_id     = $settings['source']['postId'] ?? 0;
			$target_post = get_post( $post_id );
			
			// Verify the post type matches if specified.
			if ( $target_post && ! empty( $settings['source']['postType'] ) ) {
				if ( $target_post->post_type !== $settings['source']['postType'] ) {
					// Post type mismatch - return null to prevent showing wrong content.
					$target_post = null;
				}
			}
		} else {
			$post_id = $settings['source']['postId'] ?? 0; // Only for editor side.
			if ( $post_id ) {
				$target_post = get_post( $post_id );
			} else {
				global $post;
				$target_post = $post;
			}
		}
	
		// Return empty if invalid post.
		if ( ! $target_post instanceof \WP_Post ) {
			return '';
		}
		
		// Handle author fields separately.
		$author_fields = array(
			'name',
			'first_name',
			'last_name',
			'display_name',
			'login',
			'description',
			'nicename',
			'description',
			'email',
			'id',
			'url',
			'avatar',
			'author_info',
		);
		
		if ( in_array( $field, $author_fields, true ) ) {
			return Helper::get_user_field_value( $type, $field, $settings );
		}
		
		// Route to appropriate field handler.
		switch ( $field ) {
			case 'post_title':
				return $target_post->post_title ?? '';
			case 'post_excerpt':
				return $target_post->post_excerpt ?? '';
			case 'featured_image':
				return self::get_featured_image_field( $target_post, $settings );
			case 'custom_field':
				return self::get_custom_field( $target_post, $settings );
			case 'post_date':
				return self::get_post_date( $target_post, $settings );
			case 'post_time':
				return self::get_post_time( $target_post, $settings );
			case 'post_terms':
				return self::get_post_terms( $target_post, $settings );
			case 'post_ID':
				return $target_post->ID ?? '';
			case 'comments_number':
				return self::get_comments_number( $target_post, $settings );
			default:
				return '';
		}//end switch
	}

	/**
	 * Gets a dynamic link URL based on settings.
	 * 
	 * Supports various link sources including custom fields, author info, and standard post URLs.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param array $settings The dynamic content settings.
	 * @return string The link URL or empty string if invalid.
	 */
	public static function get_link_field_value( $settings ) {
		$type = $settings['source']['type'] ?? 'current_post';

		// Get the target post based on type.
		if ( 'post_type' === $type ) {
			$post_id     = $settings['source']['postId'] ?? 0;
			$target_post = get_post( $post_id );
			
			// Verify the post type matches if specified.
			if ( $target_post && ! empty( $settings['source']['postType'] ) ) {
				if ( $target_post->post_type !== $settings['source']['postType'] ) {
					// Post type mismatch - return null to prevent showing wrong content.
					$target_post = null;
				}
			}
		} else {
			global $post;
			$target_post = $post;
		}
	
		if ( ! $target_post instanceof \WP_Post ) {
			return '';
		}
		

		$field = $settings['linkSource'] ?? '';

		if ( empty( $field ) ) {
			return '';
		}

		// Handle custom field links.
		if ( 'custom_field' === $field ) {
			$field = $settings['linkSourcePostCustomField'] ?? '';

			if ( empty( $field ) ) {
				return '';
			}

			if ( 'custom' === $field ) {
				$field = $settings['linkSourcePostCustomMetaKey'] ?? '';
			}

			if ( empty( $field ) ) {
				return '';
			}

			return CustomFields::get_value( $target_post->ID, $field, 'url' );
		}

		// Handle author info links.
		if ( 'author_info' === $field ) {
			$field = $settings['linkSourceAuthorCustomField'] ?? '';

			if ( empty( $field ) ) {
				return '';
			}

			if ( 'custom' === $field ) {
				$field = $settings['linkSourceAuthorMetaKey'] ?? '';
			}

			if ( empty( $field ) ) {
				return '';
			}

			return CustomFields::get_user_field_value( $field, $target_post->post_author, 'url' );
		}//end if

		
		// Map standard link types to their URLs.
		$fields = array(
			'post_permalink' => get_permalink( $target_post ),
			'comments_area'  => get_permalink( $target_post ) . '#comments',
			'featured_image' => wp_get_attachment_url( get_post_thumbnail_id( $target_post->ID ) ),
			'author_archive' => get_author_posts_url( $target_post->post_author ),
			'author_page'    => get_the_author_meta( 'url', $target_post->post_author ),
			'avatar'         => get_avatar_url( $target_post->post_author ),
		);

		return $fields[ $field ] ?? '';
	}
}
