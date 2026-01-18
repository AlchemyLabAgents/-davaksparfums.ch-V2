<?php
/**
 * The Spectra Pro Block Style Manager.
 *
 * @package SpectraPro\Helpers
 */

namespace SpectraPro\Helpers;

/**
 * Helper class for generating CSS-related class names and styles.
 *
 * This class provides functions to extract and format styles such as
 * border, spacing, margin, and shadow from block attributes.
 *
 * @since 2.0.0-beta.1
 */
class BlockStyleManager {
	/**
	 * Generates class names and styles for border support in blocks.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param array $attributes The block attributes.
	 * @return array The border-related classnames and styles for the block.
	 */
	public static function get_border_attributes( array $attributes ): array {
		$border_styles = array();
		$sides         = array( 'top', 'right', 'bottom', 'left' );
	
		// Border radius.
		if ( isset( $attributes['style']['border']['radius'] ) ) {
			$border_styles['radius'] = $attributes['style']['border']['radius'];
		}
	
		// Border style.
		if ( isset( $attributes['style']['border']['style'] ) ) {
			$border_styles['style'] = $attributes['style']['border']['style'];
		}
	
		// Border width.
		if ( isset( $attributes['style']['border']['width'] ) ) {
			$border_styles['width'] = $attributes['style']['border']['width'];
		}
	
		// Border color.
		$preset_color           = array_key_exists( 'borderColor', $attributes ) ? "var:preset|color|{$attributes['borderColor']}" : null;
		$custom_color           = $attributes['style']['border']['color'] ?? null;
		$border_styles['color'] = $preset_color ? $preset_color : $custom_color;
	
		// Individual border styles e.g. top, left etc.
		foreach ( $sides as $side ) {
			$border                 = $attributes['style']['border'][ $side ] ?? null;
			$border_styles[ $side ] = array(
				'color' => isset( $border['color'] ) ? $border['color'] : null,
				'style' => isset( $border['style'] ) ? $border['style'] : null,
				'width' => isset( $border['width'] ) ? $border['width'] : null,
			);
		}
	
		$styles = wp_style_engine_get_styles( array( 'border' => $border_styles ) );

		return array(
			'class' => $styles['classnames'] ?? '',
			'style' => $styles['css'] ?? '',
		);
	}

	/**
	 * Generates class names and styles for spacing attributes.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param array $attributes The block attributes.
	 * @return array The spacing-related classnames and styles.
	 */
	public static function get_spacing_attributes( array $attributes ): array {
		$spacing_styles = array();
		$spacing_types  = [ 'margin', 'padding' ];
		$sides          = [ 'top', 'right', 'bottom', 'left' ];

		foreach ( $spacing_types as $type ) {
			if ( isset( $attributes['style']['spacing'][ $type ] ) ) {
				foreach ( $sides as $side ) {
					if ( isset( $attributes['style']['spacing'][ $type ][ $side ] ) ) {
						$spacing_styles[ $type ][ $side ] = $attributes['style']['spacing'][ $type ][ $side ];
					}
				}
			}
		}

		$styles = wp_style_engine_get_styles( [ 'spacing' => $spacing_styles ] );
		
		return array(
			'class' => $styles['classnames'] ?? '',
			'style' => $styles['css'] ?? '',
		);
	}

	/**
	 * Generates class names and styles for shadow attributes.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param array $attributes The block attributes.
	 * @return array The shadow-related classnames and styles.
	 */
	public static function get_shadow_attributes( array $attributes ): array {
		if ( empty( $attributes['style']['shadow'] ) ) {
			return array();
		}

		$shadow_styles = $attributes['style']['shadow'];
		$styles        = wp_style_engine_get_styles( [ 'shadow' => $shadow_styles ] );

		return array(
			'class' => $styles['classnames'] ?? '',
			'style' => $styles['css'] ?? '',
		);
	}

	/**
	 * Consolidates only requested style attributes into a single array.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param array $attributes The block attributes.
	 * @param array $args List of attributes to include (e.g., 'border', 'spacing', 'shadow').
	 * @return array The filtered classnames and styles.
	 */
	public static function get_attributes( array $attributes, array $args = [] ): array {
		$result = array();

		if ( in_array( 'border', $args, true ) ) {
			$result = array_merge_recursive( $result, self::get_border_attributes( $attributes ) );
		}
		if ( in_array( 'spacing', $args, true ) ) {
			$result = array_merge_recursive( $result, self::get_spacing_attributes( $attributes ) );
		}
		if ( in_array( 'shadow', $args, true ) ) {
			$result = array_merge_recursive( $result, self::get_shadow_attributes( $attributes ) );
		}

		$result['class'] = is_array( $result['class'] ) ? implode( ' ', $result['class'] ) : $result['class'] ?? '';
		$result['style'] = is_array( $result['style'] ) ? implode( '', $result['style'] ) : $result['style'] ?? '';

		return $result;
	}
}
