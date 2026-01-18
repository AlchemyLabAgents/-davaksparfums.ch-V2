<?php
/**
 * The Spectra Loop Builder Helper.
 *
 * @package SpectraPro\Helpers
 */

namespace SpectraPro\Helpers;

use WP_Block;
use WP_HTML_Tag_Processor;

defined( 'ABSPATH' ) || exit;

/**
 * Class LoopBuilder.
 * 
 * @since 2.0.0-beta.1
 */
class LoopBuilder { 
	/**
	 * Render a link block with Interactivity API attributes.
	 * 
	 * @since 2.0.0-beta.1
	 *
	 * @param array $block The block array containing attributes and inner content.
	 * @param array $args  Associative array of arguments to customize the link.
	 * @return string The processed HTML with updated attributes.
	 */
	public static function render_link_with_interactivity( array $block, array $args ): string {
		$defaults = array(
			'url'      => '',
			'wp_key'   => '',
			'classes'  => '',
			'prefetch' => true,
		);

		$args = wp_parse_args( $args, $defaults );

		// Set text if provided.
		if ( isset( $args['text'] ) ) {
			$block['attrs']['text'] = $args['text'];
		}

		// Set the URL in the block's link attributes.
		$block['attrs']['linkURL'] = esc_url( $args['url'] );

		// Render the block into HTML.
		$block_instance          = new WP_Block( $block );
		$block_instance->context = $block['context'] ?? array();
		$html                    = $block_instance->render();

		// Process the HTML to add Interactivity API attributes.
		$processor = new WP_HTML_Tag_Processor( $html );
		if ( $processor->next_tag( [ 'tag_name' => 'a' ] ) ) {
			// Add wp_key if provided.
			if ( ! empty( $args['wp_key'] ) ) {
				$processor->set_attribute( 'data-wp-key', $args['wp_key'] );
			}

			// Add navigation action.
			$processor->set_attribute( 'data-wp-on--click', 'spectra-pro/loop-builder::actions.navigate' );

			// Add prefetch action if enabled.
			if ( $args['prefetch'] ) {
				$processor->set_attribute( 'data-wp-on-async--mouseenter', 'spectra-pro/loop-builder::actions.prefetch' );
				$processor->set_attribute( 'data-wp-watch', 'spectra-pro/loop-builder::callbacks.prefetch' );
			}

			// Append additional classes if provided.
			if ( ! empty( $args['classes'] ) ) {
				$existing_classes = $processor->get_attribute( 'class' ) ?? '';
				$new_classes      = trim( $existing_classes . ' ' . $args['classes'] );
				$processor->set_attribute( 'class', $new_classes );
			}
		}//end if

		return $processor->get_updated_html();
	}

	/**
	 * Generate a URL for a given term filter.
	 * 
	 * @since 2.0.0-beta.1
	 *
	 * @param string   $filter_key  The query arg key used to filter posts.
	 * @param string   $taxonomy_type  The taxonomy type (e.g. category, post_tag).
	 * @param int|null $term_id  The term ID to filter by. Omitting this will return the "All" button URL.
	 * @return string The generated URL.
	 */
	public static function generate_term_url( $filter_key, $taxonomy_type, $term_id = null ) {
		$current_url = trailingslashit( self::get_current_url() );
		$query_args  = array();
	
		// Sanitize and parse raw query string safely.
		$raw_query_string = isset( $_SERVER['QUERY_STRING'] ) ? sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) : '';
		$raw_query        = wp_parse_args( $raw_query_string );

		// Merge with raw query parameters, sanitized appropriately, only if there are query args.
		if ( ! empty( $raw_query ) ) {
			foreach ( $raw_query as $key => $value ) {
				$sanitized_key                = sanitize_key( $key );
				$query_args[ $sanitized_key ] = is_array( $value )
					? array_map( 'sanitize_text_field', $value )
					: sanitize_text_field( $value );
			}
		}
	
		// Update the filter key based on the term_id.
		if ( is_null( $term_id ) ) {
			unset( $query_args[ $filter_key ] );
		} else {
			$query_args[ sanitize_key( $filter_key ) ] = sprintf(
				'%s|%d',
				sanitize_key( $taxonomy_type ),
				absint( $term_id )
			);
		}
	
		return add_query_arg( $query_args, $current_url );
	}   

	/**
	 * Get the current URL.
	 *
	 * Returns the home URL combined with the current request URI.
	 * 
	 * @since 2.0.0-beta.1
	 *
	 * @return string The current full URL.
	 */
	public static function get_current_url() {
		global $wp;
		
		return home_url( add_query_arg( [], $wp->request ) );
	}
}
