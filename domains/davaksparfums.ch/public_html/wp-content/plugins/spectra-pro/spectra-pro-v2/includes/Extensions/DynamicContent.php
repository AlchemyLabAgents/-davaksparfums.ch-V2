<?php
/**
 * Dynamic Content extension for Spectra Pro.
 *
 * Handles dynamic content rendering for blocks including links, images, and text content.
 *
 * @package SpectraPro\Extensions
 * 
 * @since 2.0.0-beta.1
 */

namespace SpectraPro\Extensions;

use SpectraPro\Extensions\DynamicContent\Helper;
use Spectra\Traits\Singleton;

/**
 * DynamicContent class.
 * 
 * This class handles the dynamic content rendering for supported blocks.
 * It processes blocks during rendering to replace dynamic content placeholders with actual values from various sources (posts, users, custom fields, etc.).
 * 
 * @since 2.0.0-beta.1
 */
class DynamicContent {

	use Singleton;

	/**
	 * Initialize the class.
	 * 
	 * Sets up filters for block rendering and enqueues necessary assets.
	 *
	 * @since 2.0.0-beta.1
	 * 
	 * @return void
	 */
	public function init() {
		add_filter( 'register_block_type_args', array( $this, 'modify_block_uses_context' ), 10, 2 );
		add_filter( 'render_block_data', array( $this, 'inject_dynamic_background_image_url' ), 5 );
		add_filter( 'render_block_data', array( $this, 'inject_dynamic_link_url' ) );
		add_filter( 'render_block', array( $this, 'render_dynamic_link_for_core_image' ), 10, 2 );
		add_filter( 'render_block', array( $this, 'render_dynamic_image_for_core_image' ), 10, 2 );
		add_filter( 'render_block', array( $this, 'render_dynamic_content' ), 10, 2 );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Modify the usesContext property of a block to include postId and postType if not already present.
	 * 
	 * This is necessary because the block editor does not automatically add these context values when
	 * registering a block. This is a workaround to ensure that the context values are present in order
	 * to properly render dynamic content.
	 *
	 * @since 2.0.0-beta.1
	 * 
	 * @param array  $args       Block arguments including the usesContext property.
	 * @param string $block_name The name/identifier of the block being registered.
	 * 
	 * @return array Modified block arguments with updated usesContext property.
	 */
	public function modify_block_uses_context( $args, $block_name ) {
		// Define blocks that should receive the additional context values.
		$valid_blocks = array( 'spectra/container', 'spectra/slider', 'spectra/content', 'spectra/button', 'spectra/icon', 'spectra/modal-popup-content', 'core/image' );

		// Return unmodified args if block is not in our valid blocks list.
		if ( ! in_array( $block_name, $valid_blocks, true ) ) {
			return $args;
		} 

		// Initialize uses_context as array if not set.
		$args['uses_context'] = $args['uses_context'] ?? array();

		// Define new context values to be added.
		$new_contexts = array( 'postId', 'postType' );
	
		// Add each new context if not already present.
		foreach ( $new_contexts as $context ) {
			if ( ! in_array( $context, $args['uses_context'] ) ) {
				$args['uses_context'][] = $context;
			}
		}

		return $args;
	}

	/**
	 * Injects a dynamic background image URL into the block's attributes
	 * for blocks that support dynamic background images.
	 * 
	 * @since 2.0.0-beta.1
	 *
	 * @param array $parsed_block The parsed block data.
	 * @return array The modified block data with dynamic image URL injected (if applicable).
	 */
	public function inject_dynamic_background_image_url( $parsed_block ) {
		// Bail early if the block name is missing or not eligible for dynamic images.
		if ( empty( $parsed_block['blockName'] ) || ! Helper::is_image_allowed_block( $parsed_block['blockName'] ) ) {
			return $parsed_block;
		}

		// Initialize attributes with references.
		$parsed_block['attrs'] = $parsed_block['attrs'] ?? [];
		$attributes            = &$parsed_block['attrs'];
		
		// Retrieve dynamic image settings.
		$dynamic_image_enabled = $attributes['spectraProEnableDynamicImage'] ?? false;
		$settings              = $attributes['spectraProDynamicImage'] ?? array();

		// Early return if dynamic image is not enabled or settings are empty.
		if ( ! $dynamic_image_enabled || empty( $settings ) ) {
			return $parsed_block;
		}

		// Retrieve the dynamic image data.
		$value = Helper::get_dynamic_content_value( $settings, false );
		$url   = esc_url_raw( $value['url'] ?? '' );

		// If URL is empty or invalid, don't inject anything.
		if ( empty( $url ) ) {
			return $parsed_block;
		}

		// Apply dynamic URL to all breakpoints.
		$responsive_controls = &$attributes['responsiveControls'];
		$breakpoints         = array( 'lg', 'md', 'sm' );

		foreach ( $breakpoints as $breakpoint ) {
			if ( isset( $responsive_controls[ $breakpoint ]['background'] ) ) {
				$background = &$responsive_controls[ $breakpoint ]['background'];
				
				// Only set media URL if background type is image.
				if ( ( $background['type'] ?? '' ) === 'image' ) {
					// Ensure media is a proper array.
					$background['media'] = isset( $background['media'] ) && is_array( $background['media'] ) ? $background['media'] : array();
					
					// Set the URL in the media array.
					$background['media']['url'] = $url;
				}
			}
		}

		return $parsed_block;
	}

	/**
	 * Injects a dynamic link URL into a block's attributes before rendering.
	 *
	 * This method allows supported blocks (e.g. buttons, images, etc.) to have their
	 * `linkURL` attribute dynamically populated based on user-defined dynamic content settings.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param array $parsed_block The parsed block data before rendering.
	 * @return array The modified block data with the dynamic link URL injected, if applicable.
	 */
	public function inject_dynamic_link_url( $parsed_block ) {
		$block_name = $parsed_block['blockName'] ?? '';
		$link_attr  = Helper::is_link_allowed_block( $block_name );

		// Exit early if block name is missing or the block is not allowed for dynamic links.
		if ( ! $block_name || ! $link_attr ) {
			return $parsed_block;
		}

		// Retrieve the block's attributes and dynamic link configuration.
		$attributes           = $parsed_block['attrs'] ?? array();
		$dynamic_link_enabled = $attributes['spectraProEnableDynamicLink'] ?? false;
		$settings             = $attributes['spectraProDynamicLink'] ?? null;
		
		// Exit if dynamic link feature is disabled or no settings are provided.
		if ( ! $dynamic_link_enabled || ! $settings ) {
			return $parsed_block;
		}

		// Get the dynamic URL value from the helper.
		$dynamic_url = Helper::get_dynamic_url_value( $settings );

		// If the dynamic URL is empty or invalid, do not modify the block.
		if ( empty( $dynamic_url ) || ! is_string( $dynamic_url ) ) {
			return $parsed_block;
		}

		// Inject the dynamic link URL into the block's attributes.
		$attributes[ $link_attr ] = $dynamic_url;

		// Update the block with modified attributes.
		$parsed_block['attrs'] = $attributes;

		return $parsed_block;
	}

	/**
	 * Replaces dynamic content links with actual dynamic URLs.
	 *
	 * Processes blocks to replace href attributes with dynamic URLs when enabled.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $content The block content.
	 * @param array  $block   The block data.
	 * @return string The updated block content with dynamic links.
	 */
	public function render_dynamic_link_for_core_image( $content, $block ) {
		$block_name           = $block['blockName'] ?? '';
		$dynamic_link_enabled = $block['attrs']['spectraProEnableDynamicLink'] ?? false;
		
		// Return early if conditions aren't met.
		if ( empty( $block_name ) || 'core/image' !== $block_name || ! $dynamic_link_enabled ) {
			return $content;
		}

		// Get dynamic link settings.
		$link_settings = $block['attrs']['spectraProDynamicLink'] ?? null;

		// Return early if no settings are provided.
		if ( empty( $link_settings ) || ! is_array( $link_settings ) ) {
			return $content;
		}

		$dynamic_image_enabled = $block['attrs']['spectraProEnableDynamicImage'] ?? false;
		$image_settings        = $block['attrs']['spectraProDynamicImage'] ?? null;

		// Return early if dynamic image feature is disabled or no settings are provided.
		if ( $dynamic_image_enabled && is_array( $image_settings ) && ! empty( $image_settings ) ) {
			$link_settings = array_merge( $link_settings, $image_settings );
		}

		$dynamic_url = Helper::get_dynamic_url_value( $link_settings );

		// Return original content if URL is invalid.
		if ( empty( $dynamic_url ) || ! is_string( $dynamic_url ) ) {
			return $content;
		}

		// Process anchor tags in content.
		$processor = new \WP_HTML_Tag_Processor( $content );

		while ( $processor->next_tag( 'a' ) ) {
			$processor->set_attribute( 'href', $dynamic_url );
		}

		return $processor->get_updated_html();
	}

	/**
	 * Replaces image URLs with dynamic content for supported blocks.
	 * 
	 * Handles both background images (for container/slider blocks) and regular image src attributes.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $content The block content.
	 * @param array  $block   The block attributes.
	 * @return string The updated block content with dynamic images.
	 */
	public function render_dynamic_image_for_core_image( $content, $block ) {
		$block_name            = $block['blockName'] ?? '';
		$dynamic_image_enabled = $block['attrs']['spectraProEnableDynamicImage'] ?? false;
		
		// Return early if conditions aren't met.
		if ( ! $block_name || 'core/image' !== $block_name || ! $dynamic_image_enabled ) {
			return $content;
		}

		$settings = $block['attrs']['spectraProDynamicImage'] ?? null;

		if ( ! $settings ) {
			return $content;
		}

		// Get the dynamic image value.
		$value = Helper::get_dynamic_content_value( $settings, false );
		$url   = esc_url( $value['url'] ?? '' );

		// Return original content if URL is invalid.
		if ( empty( $url ) ) {
			return $content;
		}

		// Handle regular image blocks.
		$processor = new \WP_HTML_Tag_Processor( $content );

		while ( $processor->next_tag( 'img' ) ) {
			$processor->set_attribute( 'src', $url );
		}

		$content = $processor->get_updated_html();

		return $content;
	}

	/**
	 * Replaces dynamic content tags with their actual values.
	 *
	 * Processes span tags with data-spectra-dc attributes to replace with dynamic content.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $content The block content.
	 * @param array  $block   The block data.
	 * @return string The updated content with dynamic values.
	 */
	public function render_dynamic_content( $content, $block ) {
		$block_name = $block['blockName'] ?? '';

		// Return original content early if block is not allowed.
		if ( empty( $block_name ) || ! Helper::is_allowed_block( $block_name ) ) {
			return $content;
		}

		// Pattern to identify dynamic content <span> tags.
		static $pattern = '/<span\s+([^>]*)data-spectra-dc=["\'](.*?)["\']([^>]*)>(.*?)<\/span>/is';

		// Skip processing if no dynamic content tags found.
		if ( ! preg_match( $pattern, $content ) ) {
			return $content;
		}
		
		// Initialize href for button block.
		$button_href = '';

		// Replace matched dynamic content spans with actual dynamic values.
		$content = preg_replace_callback(
			$pattern,
			function ( $matches ) use ( &$button_href, $block_name ) {
				$data_attr = $matches[2];
	
				// Decode dynamic content settings from the data attribute.
				$settings = json_decode( html_entity_decode( $data_attr ), true );
				if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $settings ) || empty( $settings ) ) {
					return $matches[0]; // Return original if invalid JSON.
				}

				// Fetch the dynamic content value.
				$value = Helper::get_dynamic_content_value( $settings );

				if ( ! ( is_string( $value ) || is_numeric( $value ) ) ) {
					return $matches[0];
				}

				$value_decoded = html_entity_decode( $value );
				$enable_link   = $settings['enableLink'] ?? false;

				// Handle special case for button block with link enabled.
				if ( ! empty( $value_decoded ) && $enable_link && 'spectra/button' === $block_name ) {
					// Extract href and inner content from the anchor tag in the value.
					if ( preg_match( '/<a\s[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $value_decoded, $href_match ) ) {
						$button_href  = esc_url( $href_match[1] ?? '' );
						$inner_markup = $href_match[2] ?? '';

						return $inner_markup;
					}
				}

				return $value_decoded; // Return decoded value as replacement.
			},
			$content
		);

		// If a valid href was extracted, update the button anchor tag with it.
		if ( ! empty( $button_href ) ) {
			$processor = new \WP_HTML_Tag_Processor( $content );
			while ( $processor->next_tag( 'a' ) ) {
				if ( $processor->has_class( 'wp-block-spectra-button' ) ) {
					$processor->set_attribute( 'href', $button_href );
					break;
				}
			}
			$content = $processor->get_updated_html();
		}
	
		return $content;
	}

	/**
	 * Enqueues frontend assets for dynamic content functionality.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		wp_enqueue_style( 'spectra-extensions-dynamic-content' );
	}
}
