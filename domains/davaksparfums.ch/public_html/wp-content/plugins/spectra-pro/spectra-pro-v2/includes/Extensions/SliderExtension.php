<?php
/**
 * Class to handle the slider functionality and navigation features.
 * 
 * @package SpectraPro\Extensions
 */

namespace SpectraPro\Extensions;

use Spectra\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class SliderExtension
 *
 * @since 2.0.0-beta.1
 */
class SliderExtension {

	/**
	 * Singleton trait.
	 */
	use Singleton;

	/**
	 * Current slider being rendered
	 *
	 * @since 2.0.0-beta.1
	 * @var array|null
	 */
	private $current_slider = null;

	/**
	 * Slide counter for current slider
	 *
	 * @since 2.0.0-beta.1
	 * @var array
	 */
	private static $slide_counter = [];

	/**
	 * Initialize the extension.
	 *
	 * @since 2.0.0-beta.1
	 * @return void
	 */
	public function init() {
		// Register scripts early.
		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );
		
		// Block filters.
		add_filter( 'render_block', array( $this, 'process_slider_blocks' ), 5, 2 );
		add_filter( 'spectra_slider_params', array( $this, 'extend_slider_params' ), 10, 2 );
		add_filter( 'spectra_slider_modules', array( $this, 'add_modules' ), 10, 2 );
		add_filter( 'spectra_slider_child_attributes', array( $this, 'extend_slide_attributes' ), 10, 2 );
	}

	/**
	 * Register custom navigation required scripts.
	 *
	 * @since 2.0.0-beta.1
	 * @return void
	 */
	public function register_scripts() {
		
		// Register transition effects script.
		wp_register_script(
			'spectra-pro-slider-effects',
			SPECTRA_PRO_2_URL . 'build/extensions/slider/effects.js',
			array( 'wp-hooks', 'swiper-script' ),
			SPECTRA_PRO_VER,
			true
		);

		// Register navigation script.
		wp_register_script(
			'spectra-pro-slider-navigation',
			SPECTRA_PRO_2_URL . 'assets/js/spectra-slider-navigation.js',
			array( 'swiper-script' ),
			SPECTRA_PRO_VER,
			true
		);

		if ( has_block( 'spectra/slider' ) ) {
			wp_enqueue_script( 'spectra-pro-slider-effects' );
		}

	}

	/**
	 * Find parent slider block in the block tree.
	 *
	 * @since 2.0.0-beta.1
	 * @param array $block The block.
	 * @return array|null The parent slider block or null if not found.
	 */
	private function find_parent_slider( $block ) {
		return ( ! empty( $block['context']['spectra/slider'] ) ) ? [
			'blockName' => 'spectra/slider',
			'attrs'     => $block['attrs'],
		] : null;
	}

	/**
	 * Process slider and slider child blocks.
	 * This method consolidates the functionality of track_slider_block and filter_slider_child_block.
	 *
	 * @since 2.0.0-beta.1
	 * @param string $block_content The block content.
	 * @param array  $block The block.
	 * @return string The filtered block content.
	 */
	public function process_slider_blocks( $block_content, $block ) {
		static $custom_navigation = false;
		static $slider_id         = '';
		static $slide_count       = 0;

		// Handle main slider block.
		switch ( $block['blockName'] ) {
			// Handle the main slider block.
			case 'spectra/slider':
				// Set current slider context and get attributes.
				$this->current_slider = $block;
				$custom_navigation    = $block['attrs']['customNavigation'] ?? false;
				$slider_id            = $block['attrs']['sliderId'] ?? '';
				// If custom navigation is enabled, add the data attribute to the slider wrapper.
				if ( $custom_navigation && $slider_id ) {
					$processor = new \WP_HTML_Tag_Processor( $block_content );
					if ( $processor->next_tag( 'div' ) ) {
						$processor->set_attribute( 'data-spectra-custom-navigation', 'true' );
						$processor->set_attribute( 'data-slider-id', $slider_id );
						$block_content = $processor->get_updated_html();
					}
					// Enqueue navigation script for the custom navigation when custom navigation is enabled.
					wp_enqueue_script( 'spectra-pro-slider-navigation' );
				}//end if
				$transition_effect = $block['attrs']['transitionEffect'] ?? '';
				
				if ( $transition_effect ) {
					// Add Swiper transition effect module name to data attribute to the slider wrapper.
					$processor = new \WP_HTML_Tag_Processor( $block_content );
					if ( $processor->next_tag( 'div' ) ) {
						$existing_modules = $processor->get_attribute( 'data-modules' );
						$modules          = json_decode( $existing_modules ? $existing_modules : '[]' );
						$modules[]        = 'Effect' . ucfirst( $transition_effect );
						$processor->set_attribute( 'data-modules', wp_json_encode( $modules ) );
						$block_content = $processor->get_updated_html();
					}
				}
				return $block_content;
			// Handle slider child blocks.
			case 'spectra/slider-child':
				// If no current slider is set, try to find the parent.
				if ( ! $this->current_slider ) {
					$this->current_slider = $this->find_parent_slider( $block );
				}
				// Get client ID from block attributes or from the block itself.
				$client_id = $block['attrs']['clientId'] ?? '';
				// If clientId is still empty, try to get it from block data.
				if ( empty( $client_id ) && isset( $block['clientId'] ) ) {
					$client_id = $block['clientId'];
				}
				// If we have a valid client ID, use it to generate a hash.
				if ( ! empty( $client_id ) ) {
					$hash = 'slide-' . substr( $client_id, 0, 8 );
				} else {
					// Otherwise use a combination of slider ID and slide number for a unique but deterministic hash.
					$slide_count++;
					$hash = 'slide-' . $slider_id . '-' . $slide_count;
				}
				// Add data-hash attribute to the slide.
				$processor = new \WP_HTML_Tag_Processor( $block_content );
				if ( $processor->next_tag() ) {
					$processor->set_attribute( 'data-hash', esc_attr( $hash ) );
					return $processor->get_updated_html();
				}
				return $block_content;
		}//end switch
		
		return $block_content;
	}

	/**
	 * Extend Swiper parameters for hash navigation and transition effect.
	 *
	 * @since 2.0.0-beta.1
	 * @param array $params The Swiper parameters.
	 * @param array $attributes The block attributes.
	 * @return array The modified parameters.
	 */
	public function extend_slider_params( $params, $attributes ) {
		// Add hash navigation parameters.
		if ( ! empty( $attributes['hashNavigation'] ) ) {
			$params['hashNavigation'] = [
				'enabled'      => true,
				'watchState'   => true,
				'replaceState' => true,
			];
		}
		// Add transition effect parameters.
		if ( ! empty( $attributes['transitionEffect'] ) ) {
			// Set the transition effect.
			$params['effect'] = $attributes['transitionEffect'];
			// Add specific transition effect parameters.
			switch ( $attributes['transitionEffect'] ) {
				// Cube transition effect.
				case 'cube':
					$params['cubeEffect'] = array(
						'slideShadows' => true,
					);
					break;
				// Coverflow transition effect.
				case 'coverflow':
					$params['coverflowEffect'] = array(
						'rotate'   => 50,
						'stretch'  => 0,
						'depth'    => 100,
						'modifier' => 1,
					);
					$params['centeredSlides']  = true;
					break;
				// Flip transition effect.
				case 'flip':
					$params['flipEffect'] = array(
						'limitRotation' => true,
					);
					break;
				// Fade transition effect.
				case 'fade':
					$params['fadeEffect'] = array(
						'crossFade' => true,
					);
					break;
				// Default to slide transition effect.
				default:
					$params['effect'] = 'slide';
					break;
			}//end switch
		}//end if
		return $params;
	}

	/**
	 * Add modules to Swiper based on transition effect.
	 *
	 * @since 2.0.0-beta.1
	 * @param array $params The Swiper parameters.
	 * @param array $attributes The block attributes.
	 * @return array The modified parameters.
	 */
	public function add_modules( $params, $attributes ) {
		if ( ! empty( $attributes['transitionEffect'] ) ) {
			switch ( $attributes['transitionEffect'] ) {
				case 'cube':
					$params[] = 'EffectCube';
					break;
				case 'coverflow':
					$params[] = 'EffectCoverflow';
					break;
				case 'flip':
					$params[] = 'EffectFlip';
					break;
				case 'fade':
					$params[] = 'EffectFade';
					break;
			}
		}
		return $params;
	}
	
	/**
	 * Add custom navigation parameters to Swiper config.
	 *
	 * @since 2.0.0-beta.1
	 * @param array $params Swiper parameters.
	 * @param array $attributes Block attributes.
	 * @return array Modified parameters.
	 */
	public function add_custom_navigation( $params, $attributes ) {
		if ( ! empty( $attributes['hashNavigation'] ) && ! empty( $attributes['customNavigation'] ) ) {
			$slider_id = $attributes['sliderId'] ?? '';
			if ( $slider_id ) {
				$params['navigation'] = array(
					'prevEl' => '.slider-' . $slider_id . '-prev',
					'nextEl' => '.slider-' . $slider_id . '-next',
				);
			}
		}
		return $params;
	}
}
