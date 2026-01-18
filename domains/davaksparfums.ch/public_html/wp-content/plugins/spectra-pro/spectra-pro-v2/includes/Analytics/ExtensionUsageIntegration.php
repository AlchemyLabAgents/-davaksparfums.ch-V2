<?php
/**
 * Extension Usage Analytics Integration for Spectra Pro v2.
 *
 * This file provides integration with Spectra 3's extension analytics system,
 * allowing Spectra Pro extensions to be tracked in the main analytics.
 *
 * @package Spectra Pro
 * @since 3.0.0-beta.1
 */

namespace SpectraPro\Analytics;

defined( 'ABSPATH' ) || exit;

/**
 * Extension Usage Analytics Integration class.
 *
 * Integrates Spectra Pro v2 extensions with the main Spectra 3 analytics system.
 *
 * @since 3.0.0-beta.1
 */
class ExtensionUsageIntegration {

	/**
	 * Instance of this class.
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks and filters for analytics integration.
	 *
	 * @since 3.0.0-beta.1
	 */
	private function init_hooks() {
		// Only initialize if Spectra 3 extension analytics is available..
		if ( ! $this->is_spectra_3_extension_analytics_available() ) {
			return;
		}

		// Hook into Spectra 3's extension discovery and analytics..
		add_filter( 'spectra_analytics_available_extensions', array( $this, 'add_pro_extensions' ) );

		// Add Pro-specific extension analytics data via filters..
		add_filter( 'spectra_analytics_extension_specific_animations', array( $this, 'add_pro_animations_analytics' ), 10, 2 );
		add_filter( 'spectra_analytics_extension_specific_responsive-controls', array( $this, 'add_pro_responsive_controls_analytics' ), 10, 2 );
		
		// Add Pro-only extensions that need specific tracking..
		add_filter( 'spectra_analytics_extension_specific_dynamic-content', array( $this, 'add_dynamic_content_analytics' ), 10, 2 );
		add_filter( 'spectra_analytics_extension_specific_global-styles', array( $this, 'add_global_styles_analytics' ), 10, 2 );

		// Clear extension analytics cache when Pro plugin is activated/deactivated..
		add_action( 'activated_plugin', array( $this, 'clear_extension_cache_on_plugin_change' ) );
		add_action( 'deactivated_plugin', array( $this, 'clear_extension_cache_on_plugin_change' ) );
	}

	/**
	 * Check if Spectra 3 extension analytics system is available.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @return bool True if Spectra 3 extension analytics is available.
	 */
	private function is_spectra_3_extension_analytics_available() {
		return class_exists( '\Spectra\Analytics\ExtensionUsageTracker' );
	}

	/**
	 * Add Spectra Pro extensions to the available extensions list.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param array $extensions Current list of available extensions.
	 * @return array Enhanced list with Pro extensions.
	 */
	public function add_pro_extensions( $extensions ) {
		if ( ! $this->should_include_pro_extensions() ) {
			return $extensions;
		}

		$pro_extensions = $this->get_pro_extensions();
		
		return array_merge( $extensions, $pro_extensions );
	}

	/**
	 * Get list of Spectra Pro extensions.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @return array Array of Pro extension names.
	 */
	private function get_pro_extensions() {
		$pro_extensions_dir    = plugin_dir_path( __FILE__ ) . '../src/extensions/';
		$discovered_extensions = array();

		if ( is_dir( $pro_extensions_dir ) && is_readable( $pro_extensions_dir ) ) {
			$extension_dirs = array_filter( glob( $pro_extensions_dir . '*' ), 'is_dir' );
			
			foreach ( $extension_dirs as $extension_path ) {
				$extension_name = basename( $extension_path );
				
				// Skip hidden directories and common non-extension files..
				if ( 0 === strpos( $extension_name, '.' ) ) {
					continue;
				}

				// Verify it's a valid extension by checking for index.js..
				if ( file_exists( $extension_path . '/index.js' ) ) {
					$discovered_extensions[] = $extension_name;
				}
			}
		}

		// Apply filter for additional customization..
		return apply_filters( 'spectra_pro_analytics_extensions', $discovered_extensions );
	}

	/**
	 * Add Pro-specific animations analytics.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param array $data Current animations analytics data.
	 * @param array $usage_data Extension usage data for animations.
	 * @return array Enhanced animations analytics with Pro data.
	 */
	public function add_pro_animations_analytics( $data, $usage_data ) {
		if ( ! $this->should_add_pro_extension_stats() ) {
			return $data;
		}

		// Pro animations specific tracking..
		$pro_animations       = array();
		$total_pro_animations = 0;

		// In a real implementation, you'd parse the usage data to extract
		// Pro-specific animation types from block attributes.
		// This is a placeholder structure..
		$pro_animation_types = array( 'bounce', 'rotate', 'skew', 'scale' );

		foreach ( $usage_data as $post_data ) {
			// Placeholder: In real implementation, parse post content for Pro animations..
			$total_pro_animations++;
			$pro_animations['bounce'] = ( $pro_animations['bounce'] ?? 0 ) + 1;
		}

		if ( 0 < $total_pro_animations ) {
			$data['pro_animations'] = array(
				'total_pro_animation_instances' => $total_pro_animations,
				'pro_animation_types_used'      => $pro_animations,
				'most_popular_pro_animation'    => ! empty( $pro_animations ) ? array_key_first( $pro_animations ) : '',
				'unique_pro_animation_types'    => count( $pro_animations ),
			);
		}

		return $data;
	}

	/**
	 * Add Pro-specific responsive controls analytics.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param array $data Current responsive controls analytics data.
	 * @param array $usage_data Extension usage data for responsive controls.
	 * @return array Enhanced responsive controls analytics with Pro data.
	 */
	public function add_pro_responsive_controls_analytics( $data, $usage_data ) {
		if ( ! $this->should_add_pro_extension_stats() ) {
			return $data;
		}

		// Pro responsive controls specific tracking..
		$breakpoint_usage               = array();
		$total_pro_responsive_instances = 0;

		foreach ( $usage_data as $post_data ) {
			// Placeholder: In real implementation, track Pro-specific responsive features..
			$total_pro_responsive_instances++;
			$breakpoint_usage['custom_breakpoints'] = ( $breakpoint_usage['custom_breakpoints'] ?? 0 ) + 1;
		}

		if ( 0 < $total_pro_responsive_instances ) {
			$data['pro_responsive_controls'] = array(
				'total_pro_responsive_instances' => $total_pro_responsive_instances,
				'breakpoint_usage'               => $breakpoint_usage,
				'custom_breakpoints_used'        => count( $breakpoint_usage ),
			);
		}

		return $data;
	}


	/**
	 * Add dynamic content extension analytics (Pro-only extension).
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param array $data Current dynamic content analytics data.
	 * @param array $usage_data Extension usage data for dynamic content.
	 * @return array Dynamic content analytics data.
	 */
	public function add_dynamic_content_analytics( $data, $usage_data ) {
		if ( ! $this->should_add_pro_extension_stats() ) {
			return $data;
		}

		$content_sources                 = array();
		$total_dynamic_content_instances = count( $usage_data );

		foreach ( $usage_data as $post_data ) {
			// Placeholder: Track dynamic content sources (ACF, meta, taxonomy, etc.).
			$content_sources['acf'] = ( $content_sources['acf'] ?? 0 ) + 1;
		}

		if ( 0 < $total_dynamic_content_instances ) {
			arsort( $content_sources );
			
			return array(
				'total_dynamic_content_instances' => $total_dynamic_content_instances,
				'content_sources_used'            => $content_sources,
				'most_popular_content_source'     => ! empty( $content_sources ) ? array_key_first( $content_sources ) : '',
				'unique_content_sources'          => count( $content_sources ),
			);
		}

		return $data;
	}

	/**
	 * Add global styles extension analytics (Pro-only extension).
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param array $data Current global styles analytics data.
	 * @param array $usage_data Extension usage data for global styles.
	 * @return array Global styles analytics data.
	 */
	public function add_global_styles_analytics( $data, $usage_data ) {
		if ( ! $this->should_add_pro_extension_stats() ) {
			return $data;
		}

		$total_global_styles_instances = count( $usage_data );
		
		if ( 0 === $total_global_styles_instances ) {
			return $data;
		}

		// Get comprehensive Global Styles analytics.
		$gs_analytics = $this->get_comprehensive_global_styles_analytics( $usage_data );

		return array_merge( $data, $gs_analytics );
	}

	/**
	 * Get comprehensive Global Styles analytics.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param array $usage_data Extension usage data for global styles.
	 * @return array Comprehensive Global Styles analytics data.
	 */
	private function get_comprehensive_global_styles_analytics( $usage_data ) {
		$analytics = array(
			'total_global_styles_instances' => count( $usage_data ),
		);

		// System Variables Customization Analytics.
		$system_variables_analytics = $this->get_system_variables_analytics();
		if ( ! empty( $system_variables_analytics ) ) {
			$analytics['system_variables'] = $system_variables_analytics;
		}

		// User Variables Analytics.
		$user_variables_analytics = $this->get_user_variables_analytics();
		if ( ! empty( $user_variables_analytics ) ) {
			$analytics['user_variables'] = $user_variables_analytics;
		}

		// User Classes Analytics.
		$user_classes_analytics = $this->get_user_classes_analytics();
		if ( ! empty( $user_classes_analytics ) ) {
			$analytics['user_classes'] = $user_classes_analytics;
		}

		// Block Defaults Analytics.
		$block_defaults_analytics = $this->get_block_defaults_analytics();
		if ( ! empty( $block_defaults_analytics ) ) {
			$analytics['block_defaults'] = $block_defaults_analytics;
		}

		// GS Classes Usage Analytics from post content.
		$gs_classes_usage = $this->get_gs_classes_usage_analytics( $usage_data );
		if ( ! empty( $gs_classes_usage ) ) {
			$analytics['gs_classes_usage'] = $gs_classes_usage;
		}

		// Overall customization level.
		$analytics['customization_level'] = $this->calculate_customization_level( $analytics );

		return $analytics;
	}

	/**
	 * Get system variables customization analytics.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @return array System variables analytics.
	 */
	private function get_system_variables_analytics() {
		$system_variables = get_option( 'spectra_pro_gs_system_variables', array() );
		
		if ( empty( $system_variables ) ) {
			return array();
		}

		$analytics             = array();
		$total_customizations  = 0;
		$categories_customized = array();

		// Analyze colors customization.
		if ( ! empty( $system_variables['colors'] ) && is_array( $system_variables['colors'] ) ) {
			$color_customizations = 0;
			$color_types          = array( 'primary', 'secondary', 'base' );
			
			foreach ( $color_types as $color_type ) {
				if ( ! empty( $system_variables['colors'][ $color_type ] ) ) {
					$color_customizations++;
					
					// Check if using custom color vs theme color.
					if ( ! empty( $system_variables['colors'][ $color_type ]['useThemeColor'] ) && 
						false === $system_variables['colors'][ $color_type ]['useThemeColor'] ) {
						$categories_customized['colors_custom'] = ( $categories_customized['colors_custom'] ?? 0 ) + 1;
					} else {
						$categories_customized['colors_theme'] = ( $categories_customized['colors_theme'] ?? 0 ) + 1;
					}
				}
			}
			
			$analytics['colors_customized'] = $color_customizations;
			$total_customizations          += $color_customizations;
		}//end if

		// Analyze spacing customization.
		if ( ! empty( $system_variables['spacing'] ) && is_array( $system_variables['spacing'] ) ) {
			$spacing_customizations = 0;
			$spacing_sizes          = array( 'xs', 'sm', 'md', 'lg', 'xl', 'xxl' );
			
			foreach ( $spacing_sizes as $size ) {
				if ( ! empty( $system_variables['spacing'][ $size ] ) ) {
					$spacing_customizations++;
				}
			}
			
			if ( 0 < $spacing_customizations ) {
				$analytics['spacing_customized']  = $spacing_customizations;
				$categories_customized['spacing'] = $spacing_customizations;
				$total_customizations            += $spacing_customizations;
			}
		}

		// Analyze font size customization.  
		if ( ! empty( $system_variables['fontsize'] ) && is_array( $system_variables['fontsize'] ) ) {
			$fontsize_customizations = 0;
			$fontsize_types          = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'xs', 'sm', 'md', 'lg', 'xl', 'xxl' );
			
			foreach ( $fontsize_types as $type ) {
				if ( ! empty( $system_variables['fontsize'][ $type ] ) ) {
					$fontsize_customizations++;
				}
			}
			
			if ( 0 < $fontsize_customizations ) {
				$analytics['fontsize_customized']    = $fontsize_customizations;
				$categories_customized['typography'] = $fontsize_customizations;
				$total_customizations               += $fontsize_customizations;
			}
		}

		if ( 0 < $total_customizations ) {
			$analytics['total_system_customizations'] = $total_customizations;
			$analytics['categories_customized']       = $categories_customized;
			$analytics['most_customized_category']    = ! empty( $categories_customized ) ? array_key_first( array_slice( arsort( $categories_customized ) ? $categories_customized : array(), 0, 1, true ) ) : '';
		}

		return $analytics;
	}

	/**
	 * Get user variables analytics.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @return array User variables analytics.
	 */
	private function get_user_variables_analytics() {
		$user_css = get_option( 'spectra_pro_gs_user_css', array() );
		
		if ( empty( $user_css['variables'] ) ) {
			return array();
		}

		$variables      = $user_css['variables'];
		$variable_count = count( $variables );
		
		if ( 0 === $variable_count ) {
			return array();
		}

		$analytics = array(
			'total_user_variables' => $variable_count,
		);

		// Analyze variable types by CSS property patterns.
		$variable_types = array();
		
		foreach ( $variables as $var_name => $var_value ) {
			// Categorize variables by common patterns.
			if ( false !== strpos( $var_name, 'color' ) || $this->is_color_value( $var_value ) ) {
				$variable_types['color'] = ( $variable_types['color'] ?? 0 ) + 1;
			} elseif ( false !== strpos( $var_name, 'font' ) || false !== strpos( $var_name, 'text' ) ) {
				$variable_types['typography'] = ( $variable_types['typography'] ?? 0 ) + 1;
			} elseif ( false !== strpos( $var_name, 'spacing' ) || false !== strpos( $var_name, 'margin' ) || false !== strpos( $var_name, 'padding' ) ) {
				$variable_types['spacing'] = ( $variable_types['spacing'] ?? 0 ) + 1;
			} elseif ( false !== strpos( $var_name, 'border' ) ) {
				$variable_types['border'] = ( $variable_types['border'] ?? 0 ) + 1;
			} else {
				$variable_types['other'] = ( $variable_types['other'] ?? 0 ) + 1;
			}
		}

		if ( ! empty( $variable_types ) ) {
			arsort( $variable_types );
			$analytics['variable_types']            = $variable_types;
			$analytics['most_common_variable_type'] = array_key_first( $variable_types );
		}

		return $analytics;
	}

	/**
	 * Get user classes analytics.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @return array User classes analytics.
	 */
	private function get_user_classes_analytics() {
		$user_css = get_option( 'spectra_pro_gs_user_css', array() );
		
		if ( empty( $user_css['classes'] ) ) {
			return array();
		}

		$classes     = $user_css['classes'];
		$class_count = count( $classes );
		
		if ( 0 === $class_count ) {
			return array();
		}

		$analytics = array(
			'total_user_classes' => $class_count,
		);

		// Analyze class complexity and pseudo-selector usage.
		$pseudo_selector_usage  = array();
		$css_property_usage     = array();
		$total_pseudo_selectors = 0;
		
		foreach ( $classes as $class_name => $class_data ) {
			if ( ! is_array( $class_data ) ) {
				continue;
			}
			
			foreach ( $class_data as $pseudo_selector => $css_properties ) {
				if ( 'default' !== $pseudo_selector ) {
					$pseudo_selector_usage[ $pseudo_selector ] = ( $pseudo_selector_usage[ $pseudo_selector ] ?? 0 ) + 1;
					$total_pseudo_selectors++;
				}
				
				// Analyze CSS properties used.
				if ( is_array( $css_properties ) ) {
					foreach ( $css_properties as $property => $value ) {
						$category                        = $this->categorize_css_property( $property );
						$css_property_usage[ $category ] = ( $css_property_usage[ $category ] ?? 0 ) + 1;
					}
				}
			}
		}

		if ( 0 < $total_pseudo_selectors ) {
			arsort( $pseudo_selector_usage );
			$analytics['pseudo_selector_usage']         = $pseudo_selector_usage;
			$analytics['most_used_pseudo_selector']     = array_key_first( $pseudo_selector_usage );
			$analytics['classes_with_pseudo_selectors'] = $total_pseudo_selectors;
		}

		if ( ! empty( $css_property_usage ) ) {
			arsort( $css_property_usage );
			$analytics['css_property_categories'] = $css_property_usage;
			$analytics['most_used_css_category']  = array_key_first( $css_property_usage );
		}

		return $analytics;
	}

	/**
	 * Get block defaults analytics.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @return array Block defaults analytics.
	 */
	private function get_block_defaults_analytics() {
		$block_defaults = get_option( 'spectra_pro_gs_block_defaults', array() );
		
		if ( empty( $block_defaults ) ) {
			return array();
		}

		$analytics                  = array();
		$total_blocks_with_defaults = 0;
		$total_default_classes      = 0;
		$pseudo_selector_usage      = array();
		$block_categories           = array();
		
		foreach ( $block_defaults as $block_name => $block_data ) {
			$total_blocks_with_defaults++;
			
			// Handle both old format (array of classes) and new format (pseudo-selector structure).
			if ( is_array( $block_data ) ) {
				// Check if it's the new pseudo-selector structure.
				if ( isset( $block_data['default'] ) || isset( $block_data['hover'] ) || isset( $block_data['active'] ) || isset( $block_data['focus-visible'] ) ) {
					// New pseudo-selector structure.
					foreach ( $block_data as $pseudo_selector => $classes ) {
						if ( is_array( $classes ) && ! empty( $classes ) ) {
							$pseudo_selector_usage[ $pseudo_selector ] = ( $pseudo_selector_usage[ $pseudo_selector ] ?? 0 ) + count( $classes );
							$total_default_classes                    += count( $classes );
						}
					}
				} else {
					// Old format - direct array of classes.
					$total_default_classes           += count( $block_data );
					$pseudo_selector_usage['default'] = ( $pseudo_selector_usage['default'] ?? 0 ) + count( $block_data );
				}
			}
			
			// Categorize blocks.
			$category                      = $this->categorize_block_name( $block_name );
			$block_categories[ $category ] = ( $block_categories[ $category ] ?? 0 ) + 1;
		}//end foreach

		$analytics['total_blocks_with_defaults']     = $total_blocks_with_defaults;
		$analytics['total_default_classes_assigned'] = $total_default_classes;
		
		if ( ! empty( $pseudo_selector_usage ) ) {
			arsort( $pseudo_selector_usage );
			$analytics['pseudo_selector_usage']     = $pseudo_selector_usage;
			$analytics['most_used_pseudo_selector'] = array_key_first( $pseudo_selector_usage );
		}
		
		if ( ! empty( $block_categories ) ) {
			arsort( $block_categories );
			$analytics['block_categories']               = $block_categories;
			$analytics['most_configured_block_category'] = array_key_first( $block_categories );
		}

		return $analytics;
	}

	/**
	 * Get GS classes usage analytics from post content.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param array $usage_data Extension usage data.
	 * @return array GS classes usage analytics.
	 */
	private function get_gs_classes_usage_analytics( $usage_data ) {
		$class_usage           = array();
		$class_categories      = array();
		$total_gs_classes_used = 0;
		
		foreach ( $usage_data as $post_data ) {
			// Parse post content to extract GS classes.
			$post_id = $post_data['post_id'] ?? 0;
			if ( 0 < $post_id ) {
				$post_content = get_post_field( 'post_content', $post_id );
				if ( ! empty( $post_content ) ) {
					$gs_classes = $this->extract_gs_classes_from_content( $post_content );
					
					foreach ( $gs_classes as $class_name ) {
						$class_usage[ $class_name ] = ( $class_usage[ $class_name ] ?? 0 ) + 1;
						$total_gs_classes_used++;
						
						// Categorize the class.
						$category                      = $this->categorize_gs_class( $class_name );
						$class_categories[ $category ] = ( $class_categories[ $category ] ?? 0 ) + 1;
					}
				}
			}
		}

		if ( 0 === $total_gs_classes_used ) {
			return array();
		}

		arsort( $class_usage );
		arsort( $class_categories );

		return array(
			'total_gs_classes_used'       => $total_gs_classes_used,
			'unique_gs_classes'           => count( $class_usage ),
			'most_used_gs_classes'        => array_slice( $class_usage, 0, 10, true ),
			'class_categories'            => $class_categories,
			'most_popular_class_category' => array_key_first( $class_categories ),
		);
	}

	/**
	 * Calculate overall customization level.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param array $analytics Analytics data.
	 * @return string Customization level (low, medium, high, expert).
	 */
	private function calculate_customization_level( $analytics ) {
		$score = 0;

		// System variables customization (max 30 points).
		if ( ! empty( $analytics['system_variables']['total_system_customizations'] ) ) {
			$score += min( $analytics['system_variables']['total_system_customizations'] * 2, 30 );
		}

		// User variables (max 25 points).
		if ( ! empty( $analytics['user_variables']['total_user_variables'] ) ) {
			$score += min( $analytics['user_variables']['total_user_variables'] * 5, 25 );
		}

		// User classes (max 25 points).
		if ( ! empty( $analytics['user_classes']['total_user_classes'] ) ) {
			$score += min( $analytics['user_classes']['total_user_classes'] * 3, 25 );
		}

		// Block defaults (max 15 points).
		if ( ! empty( $analytics['block_defaults']['total_blocks_with_defaults'] ) ) {
			$score += min( $analytics['block_defaults']['total_blocks_with_defaults'] * 2, 15 );
		}

		// GS classes usage (max 5 points).
		if ( ! empty( $analytics['gs_classes_usage']['unique_gs_classes'] ) ) {
			$score += min( $analytics['gs_classes_usage']['unique_gs_classes'], 5 );
		}

		// Determine level based on score.
		if ( 80 <= $score ) {
			return 'expert';
		} elseif ( 50 <= $score ) {
			return 'high';
		} elseif ( 20 <= $score ) {
			return 'medium';
		} else {
			return 'low';
		}
	}

	/**
	 * Check if a value is a color value.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param string $value CSS value to check.
	 * @return bool True if value appears to be a color.
	 */
	private function is_color_value( $value ) {
		// Simple color detection.
		return preg_match( '/^(#[0-9a-f]{3,8}|rgb\(|rgba\(|hsl\(|hsla\(|[a-z]+)/', strtolower( $value ) );
	}

	/**
	 * Categorize a CSS property.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param string $property CSS property name.
	 * @return string Category name.
	 */
	private function categorize_css_property( $property ) {
		$property = strtolower( $property );
		
		if ( in_array( $property, array( 'color', 'background-color', 'border-color', 'background', 'border' ), true ) ) {
			return 'colors';
		} elseif ( in_array( $property, array( 'font-family', 'font-size', 'font-weight', 'line-height', 'text-align', 'text-decoration' ), true ) ) {
			return 'typography';
		} elseif ( in_array( $property, array( 'margin', 'padding', 'gap', 'margin-top', 'margin-bottom', 'margin-left', 'margin-right', 'padding-top', 'padding-bottom', 'padding-left', 'padding-right' ), true ) ) {
			return 'spacing';
		} elseif ( in_array( $property, array( 'border-width', 'border-style', 'border-radius' ), true ) ) {
			return 'border';
		} elseif ( in_array( $property, array( 'width', 'height', 'max-width', 'min-width', 'max-height', 'min-height' ), true ) ) {
			return 'sizing';
		} elseif ( in_array( $property, array( 'display', 'position', 'flex', 'grid', 'align-items', 'justify-content' ), true ) ) {
			return 'layout';
		} else {
			return 'other';
		}
	}

	/**
	 * Categorize a block name.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param string $block_name Block name.
	 * @return string Category name.
	 */
	private function categorize_block_name( $block_name ) {
		if ( false !== strpos( $block_name, 'button' ) ) {
			return 'buttons';
		} elseif ( false !== strpos( $block_name, 'list' ) ) {
			return 'lists';
		} elseif ( false !== strpos( $block_name, 'icon' ) ) {
			return 'icons';
		} elseif ( false !== strpos( $block_name, 'tab' ) ) {
			return 'tabs';
		} elseif ( false !== strpos( $block_name, 'container' ) || false !== strpos( $block_name, 'content' ) ) {
			return 'layout';
		} else {
			return 'other';
		}
	}

	/**
	 * Extract GS classes from post content.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param string $content Post content.
	 * @return array Array of GS class names found.
	 */
	private function extract_gs_classes_from_content( $content ) {
		$gs_classes = array();
		
		// Extract spectraGSClasses attribute values from block comments.
		if ( preg_match_all( '/"spectraGSClasses":\[([^\]]*)\]/', $content, $matches ) ) {
			foreach ( $matches[1] as $classes_json ) {
				// Parse the JSON array of class names.
				$classes = json_decode( '[' . $classes_json . ']', true );
				if ( is_array( $classes ) ) {
					foreach ( $classes as $class_name ) {
						$class_name = trim( $class_name, '"' );
						if ( ! empty( $class_name ) && $this->is_gs_class( $class_name ) ) {
							$gs_classes[] = $class_name;
						}
					}
				}
			}
		}
		
		return array_unique( $gs_classes );
	}

	/**
	 * Check if a class name is a GS class.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param string $class_name Class name to check.
	 * @return bool True if it's a GS class.
	 */
	private function is_gs_class( $class_name ) {
		// GS classes typically start with specific prefixes or patterns.
		$gs_prefixes = array(
			'background--',
			'text--',
			'border--',
			'spacing--',
			'padding--',
			'margin--',
			'gap--',
			'font--',
			'display--',
			'width--',
			'height--',
			'radius--',
		);
		
		foreach ( $gs_prefixes as $prefix ) {
			if ( 0 === strpos( $class_name, $prefix ) ) {
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Categorize a GS class.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param string $class_name GS class name.
	 * @return string Category name.
	 */
	private function categorize_gs_class( $class_name ) {
		if ( 0 === strpos( $class_name, 'background--' ) || 0 === strpos( $class_name, 'text--' ) ) {
			return 'colors';
		} elseif ( 0 === strpos( $class_name, 'font--' ) ) {
			return 'typography';
		} elseif ( 0 === strpos( $class_name, 'spacing--' ) || 0 === strpos( $class_name, 'padding--' ) || 0 === strpos( $class_name, 'margin--' ) || 0 === strpos( $class_name, 'gap--' ) ) {
			return 'spacing';
		} elseif ( 0 === strpos( $class_name, 'border--' ) || 0 === strpos( $class_name, 'radius--' ) ) {
			return 'border';
		} elseif ( 0 === strpos( $class_name, 'width--' ) || 0 === strpos( $class_name, 'height--' ) ) {
			return 'sizing';
		} elseif ( 0 === strpos( $class_name, 'display--' ) ) {
			return 'display';
		} else {
			return 'other';
		}
	}



	/**
	 * Clear extension analytics cache when plugin activation status changes.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param string $plugin Plugin path.
	 */
	public function clear_extension_cache_on_plugin_change( $plugin ) {
		// Only clear cache for Spectra-related plugins..
		if ( false === strpos( $plugin, 'spectra' ) ) {
			return;
		}

		// Clear relevant extension caches..
		wp_cache_delete( 'spectra_3_extension_analytics', 'spectra' );
		wp_cache_delete( 'spectra_available_extensions', 'spectra' );
	}

	/**
	 * Check if Spectra Pro plugin is active.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @return bool True if Pro plugin is active.
	 */
	private function is_pro_plugin_active() {
		return defined( 'SPECTRA_PRO_VER' ) || function_exists( 'spectra_pro_loader' );
	}

	/**
	 * Determine if Pro extensions should be included in analytics.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @return bool True if Pro extensions should be included.
	 */
	private function should_include_pro_extensions() {
		return $this->should_add_pro_extension_stats();
	}

	/**
	 * Determine if Pro-specific extension stats should be added.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @return bool True if Pro extension stats should be added.
	 */
	private function should_add_pro_extension_stats() {
		// Check if analytics are enabled via parent Spectra settings..
		if ( ! class_exists( '\UAGB_Admin_Helper' ) ) {
			return false;
		}

		$optin_status = \UAGB_Admin_Helper::get_admin_settings_option( 'spectra_analytics_optin', 'no' );

		return 'yes' === $optin_status && $this->is_pro_plugin_active();
	}
}

// Initialize the integration.
ExtensionUsageIntegration::instance();
