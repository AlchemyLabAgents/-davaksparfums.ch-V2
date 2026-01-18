<?php
/**
 * Handles query building for block-based queries.
 *
 * This class is responsible for generating WP_Query instances based on 
 * block context, including pagination, search, sorting, and taxonomy filters.
 *
 * @package SpectraPro\Queries
 */

namespace SpectraPro\Queries;

use WP_Query;
use WP_Block;

/**
 * Class LoopBuilderQuery
 *
 * Provides utility methods to build WP_Query instances for dynamic block queries.
 *
 * @since 2.0.0-beta.1
 */
class LoopBuilderQuery {
	/**
	 * Safely retrieve and sanitize a value from $_GET.
	 * 
	 * Nonce verification is intentionally skipped here because this function is used 
	 * to retrieve query parameters for front-end filtering, search, and pagination 
	 * in public-facing contexts where nonces are not typically required. 
	 * Since these parameters do not trigger sensitive actions (such as modifying data 
	 * or performing authenticated requests), sanitization alone is sufficient to ensure security.
	 * 
	 * @since 2.0.0-beta.1
	 *
	 * @param string $key The query parameter key.
	 * @param mixed  $default The default value if key is not set.
	 * @return mixed The sanitized value.
	 */
	private static function get_query_param( $key, $default = '' ) {
		return isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : $default; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Get taxonomy filter values.
	 * 
	 * @since 2.0.0-beta.1
	 * 
	 * @param int $query_id The ID of the query.
	 * @return array|null An array containing the filter_mode and tax_ids values, or null if no filter is set.
	 */
	private static function get_filter_value( $query_id ) {
		$key          = self::get_query_key( $query_id, 'filter' );
		$filter_value = self::get_query_param( $key );

		// Return null if the filter value is empty.
		if ( empty( $filter_value ) ) {
			return null;
		}

		// Parse the filter value.
		$filters = $filter_value ? explode( '|', $filter_value ) : array();
		// Sanitize and parse filter_mode.
		$filter_mode = sanitize_key( $filters[0] ?? '' );
		// Sanitize and parse tax_ids.
		$tax_ids = array_filter( array_map( 'absint', explode( ',', $filters[1] ?? '' ) ) );
		
		// Return an array containing the filter_mode and tax_ids values, or null if no filter is set.
		return ( $filter_mode && ! empty( $tax_ids ) ) ? [ $filter_mode => $tax_ids ] : null;
	}

	/**
	 * Builds and returns a WP_Query instance for a given block.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param WP_Block $block The block instance containing query context.
	 * 
	 * @return WP_Query The generated WP_Query instance.
	 */
	public static function get_query( WP_Block $block ) {
		// Get query ID.
		$query_id = self::get_query_id( $block );

		// Ensure the block has a query context.
		if ( is_null( $query_id ) || empty( $block->context['spectra-pro/loop-builder/query'] ) ) {
			return new WP_Query();
		}

		// Pagination setup.
		$current_page = self::get_current_page( $query_id );

		// Handle search query.
		$search_value = self::get_query_value( $query_id, 'search' );
		if ( $search_value ) {
			$block->context['spectra-pro/loop-builder/query']['search'] = $search_value;
		}

		// Handle sorting.
		$sort_value = self::get_query_value( $query_id, 'sort' );
		$sort_parts = explode( '|', $sort_value );
		$order_by   = sanitize_text_field( $sort_parts[0] ?? '' ); 
		$order      = sanitize_text_field( $sort_parts[1] ?? '' );

		// Sanitize and set order by and order.
		if ( ! empty( $order_by ) ) {
			$block->context['spectra-pro/loop-builder/query']['orderBy'] = sanitize_text_field( $order_by );
		}

		// Sanitize and set order.
		if ( ! empty( $order ) ) {
			$block->context['spectra-pro/loop-builder/query']['order'] = sanitize_text_field( $order );
		}

		// Handle taxonomy filters.
		$tax_query = self::get_filter_value( $query_id );
		if ( $tax_query ) {
			$block->context['spectra-pro/loop-builder/query']['taxQuery'] = $tax_query;
		}

		// Reset page number if searching.
		$current_page = empty( $search_value ) ? $current_page : 1;

		// Set query context. Because build_query_vars_from_query_block() expects the query context to be set.
		$block->context['query'] = $block->context['spectra-pro/loop-builder/query'];

		// Build and return the query.
		return new WP_Query( build_query_vars_from_query_block( $block, $current_page ) );
	}

	/**
	 * Get the query ID from block context.
	 * 
	 * @since 2.0.0-beta.1
	 * 
	 * @param WP_Block $block The block instance.
	 * @return int|null The query ID.
	 */
	public static function get_query_id( WP_Block $block ) {
		return $block->context['spectra-pro/loop-builder/queryId'] ?? null;
	}

	/**
	 * Get the page key for pagination.
	 * 
	 * @since 2.0.0-beta.1
	 * 
	 * @param int $query_id The ID of the query.
	 * @return string The page key.
	 */
	public static function get_page_key( $query_id ) {
		return "query-{$query_id}-page";
	}

	/**
	 * Get the query key for a given type.
	 * 
	 * @since 2.0.0-beta.1
	 * 
	 * @param int    $query_id The ID of the query.
	 * @param string $type The type of query (e.g., 'page', 'search', 'sort', 'filter').
	 * @return string The query key.
	 */
	public static function get_query_key( $query_id, $type ) {
		return "query-{$query_id}-{$type}";
	}

	/**
	 * Get the value of a specific query parameter.
	 * 
	 * @since 2.0.0-beta.1
	 * 
	 * @param int    $query_id The ID of the query.
	 * @param string $type The type of query parameter (e.g., 'page', 'search', 'sort', 'filter').
	 * @param mixed  $default Default value if not set.
	 * @return mixed The sanitized query value.
	 */
	public static function get_query_value( $query_id, $type, $default = '' ) {
		$key = self::get_query_key( $query_id, $type );
		return 'filter' === $type ? self::get_filter_value( $query_id ) : self::get_query_param( $key, $default );
	}

	/**
	 * Get pagination page number.
	 * 
	 * @since 2.0.0-beta.1
	 * 
	 * @param int $query_id The ID of the query.
	 * @return int The current page number.
	 */
	public static function get_current_page( $query_id ) {
		return max( 1, absint( self::get_query_value( $query_id, 'page', 1 ) ) );
	}

	/**
	 * 
	 * Terms related.
	 */

	/**
	 * Retrieves terms for a specified taxonomy with caching and optimized query handling.
	 * 
	 * This method first checks the cache for existing terms and retrieves them if available. 
	 * If no cached terms are found, it queries the terms from the database with optional filters 
	 * like 'include', 'exclude', 'show_empty_taxonomy', and 'show_children'. The results are 
	 * cached for one hour to improve performance for subsequent requests.
	 * 
	 * @since 2.0.0-beta.1
	 * 
	 * @param string $taxonomy_type The taxonomy for which terms are queried (e.g., 'category', 'post_tag').
	 * @param string $filter_mode The filter type ('include' or 'exclude') for terms to be included or excluded.
	 * @param array  $include An array of term IDs to include in the query.
	 * @param array  $exclude An array of term IDs to exclude from the query.
	 * @param bool   $show_empty_taxonomy Whether to show terms that have no associated posts (default false).
	 * @param bool   $show_children Whether to include child terms (default false).
	 * @return array An array of term objects on success, or an empty array on failure.
	 */
	public static function get_terms_with_cache( $taxonomy_type, $filter_mode, $include = array(), $exclude = array(), $show_empty_taxonomy = false, $show_children = false ) {
		// Generate a unique cache key based on the parameters.
		$cache_key = "terms_{$taxonomy_type}_" . md5( maybe_serialize( [ $filter_mode, $show_empty_taxonomy, $exclude, $include, $show_children ] ) );
		$terms     = wp_cache_get( $cache_key, 'terms' );

		// If terms are not in cache, query the terms from the database.
		if ( false === $terms ) {
			$args = array(
				'taxonomy'   => $taxonomy_type,
				'hide_empty' => ! $show_empty_taxonomy,
				'parent'     => $show_children ? '' : 0,
			);

			// Handle 'include' directly in the query.
			if ( 'include' === $filter_mode && ! empty( $include ) && is_array( $include ) ) {
				$args['include'] = array_map( 'absint', $include );
			}
			
			// Fetch terms.
			$terms = get_terms( $args );

			// If terms retrieval fails, return an empty array.
			if ( is_wp_error( $terms ) ) {
				return array();
			}

			// Handle 'exclude' by filtering if needed.
			if ( 'exclude' === $filter_mode && ! empty( $exclude ) && is_array( $exclude ) ) {
				$exclude_ids = array_map( 'absint', $exclude );
				$terms       = array_filter(
					$terms,
					function ( $term ) use ( $exclude_ids ) {
						return ! in_array( $term->term_id, $exclude_ids, true );
					}
				);

				// Re-index the array after filtering.
				$terms = array_values( $terms );
			}

			// Cache the terms for later use.
			wp_cache_set( $cache_key, $terms, 'terms', HOUR_IN_SECONDS ); // Cache for 1 hour.
		}//end if

		return $terms;
	}
}
