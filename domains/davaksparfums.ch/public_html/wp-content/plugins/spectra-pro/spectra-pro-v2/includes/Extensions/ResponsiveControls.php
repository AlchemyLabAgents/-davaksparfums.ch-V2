<?php
/**
 * ResponsiveControls Extension.
 * 
 * Extends the responsive controls functionality from Spectra free
 * to support Pro blocks with their specific responsive attributes.
 * 
 * @package SpectraPro\Extensions
 * @since 2.0.0-beta.1
 */

namespace SpectraPro\Extensions;

use Spectra\Traits\Singleton;

/**
 * Class to manage responsive controls for Spectra Pro blocks.
 * 
 * This class:
 * - Provides default layouts for Pro blocks
 * - Registers Pro block responsive attributes
 * - Generates CSS for Pro block responsive attributes
 *
 * @since 2.0.0-beta.1
 */
class ResponsiveControls {
	
	use Singleton;

	/**
	 * Pro block responsive attributes definitions.
	 *
	 * @var array<string, array<string, array>> Block name => attribute definitions.
	 * @since 2.0.0-beta.1
	 */
	private $pro_block_attributes = array(
		'spectra-pro/loop-builder-child-pagination-next-button' => array(
			'size' => array(
				'default'   => '16px',
				'selector'  => ' svg',
				'formatter' => 'format_svg_size',
			),
			'gap'  => array( 
				'default'  => '10px',
				'property' => 'gap',
			),
		),
		'spectra-pro/loop-builder-child-pagination-previous-button' => array(
			'size' => array(
				'default'   => '16px',
				'selector'  => ' svg',
				'formatter' => 'format_svg_size',
			),
			'gap'  => array( 
				'default'  => '10px',
				'property' => 'gap',
			),
		),
		'spectra-pro/loop-builder-child-filter-checkbox'  => array(
			'checkboxSize'     => array(
				'default'   => '16px',
				'selector'  => ' input[type="checkbox"]',
				'formatter' => 'format_svg_size',
			),
			'itemsGap'         => array(
				'default'  => '5px',
				'property' => 'gap',
			),
			'labelCheckboxGap' => array(
				'default'  => '5px',
				'selector' => ' label',
				'property' => 'gap',
			),
		),
		'spectra-pro/loop-builder-child-reset-all-button' => array(
			'size' => array(
				'default'   => '16px',
				'selector'  => ' svg',
				'formatter' => 'format_svg_size',
			),
			'gap'  => array( 
				'default'  => '10px',
				'property' => 'gap',
			),
		),
		'spectra-pro/form-button'                         => array(
			'size'  => array(
				'default'   => '16px',
				'selector'  => ' svg',
				'formatter' => 'format_svg_size',
			),
			'gap'   => array(
				'default'  => '10px',
				'property' => 'gap',
			),
			'width' => array(
				'default'  => '100%',
				'property' => 'width',
			),
		),
		'spectra-pro/form-icon'                           => array(
			'size' => array( 
				'default'   => '16px',
				'selector'  => ' svg',
				'formatter' => 'format_svg_size',
			),
		),
		'spectra-pro/login'                               => array(
			'width' => array(
				'default'  => '100%',
				'property' => 'width',
			),
		),
		'spectra-pro/register'                            => array(
			'width' => array(
				'default'  => '100%',
				'property' => 'width',
			),
		),
	);

	/**
	 * Default layout configurations for specific Spectra blocks.
	 * 
	 * This array defines the default layout settings that should be applied
	 * to specific blocks when no custom layout has been defined by the user.
	 * Each block type can have its own predefined layout structure to ensure
	 * consistent appearance and behavior.
	 *
	 * @var array<string, array> Block name => Default layout configuration.
	 * @since 2.0.0-beta.1
	 */
	private $blocks_default_layout = array(
		'spectra-pro/loop-builder'                  => array(
			'layout' => array(
				'type'           => 'flex',
				'orientation'    => 'vertical',
				'justifyContent' => 'stretch',
			),
		),
		'spectra-pro/loop-builder-child-pagination' => array(
			'layout' => array(
				'type'           => 'flex',
				'justifyContent' => 'center',
			),
		),
		'spectra-pro/loop-builder-child-filter'     => array(
			'layout' => array(
				'type'           => 'flex',
				'justifyContent' => 'left',
				'flexWrap'       => 'nowrap',
			),
		),
		'spectra-pro/loop-builder-child-template'   => array(
			'layout' => array(
				'type' => 'grid',
			),
		),
		'spectra/countdown-child-expiry-wrapper'    => array(
			'layout' => array(
				'type'              => 'flex',
				'orientation'       => 'vertical',
				'flexWrap'          => 'nowrap',
				'justifyContent'    => 'center',
				'verticalAlignment' => 'center',
			),
		),
		'spectra-pro/login'                         => array(
			'layout' => array(
				'type'              => 'flex',
				'orientation'       => 'vertical',
				'flexWrap'          => 'nowrap',
				'justifyContent'    => 'stretch',
				'verticalAlignment' => 'center',
				'gap'               => '20px',
			),
		),
		'spectra-pro/register'                      => array(
			'layout' => array(
				'type'              => 'flex',
				'orientation'       => 'vertical',
				'flexWrap'          => 'nowrap',
				'justifyContent'    => 'stretch',
				'verticalAlignment' => 'center',
				'gap'               => '20px',
			),
		),
		'spectra-pro/form-input-wrapper'            => array(
			'layout' => array(
				'type'           => 'flex',
				'orientation'    => 'vertical',
				'justifyContent' => 'stretch',
			),
		),
		'spectra-pro/form-field-wrapper'            => array(
			'layout' => array(
				'type'              => 'flex',
				'flexWrap'          => 'nowrap',
				'justifyContent'    => 'left',
				'verticalAlignment' => 'center',
			),
		),
	);

	/**
	 * Initializes the extension by adding necessary filters.
	 * 
	 * @since 2.0.0-beta.1
	 * @return void
	 */
	public function init() {
		add_filter( 'spectra_blocks_responsive_default_layout', array( $this, 'set_default_layout' ), 10, 1 );
		add_filter( 'spectra_responsive_attr_definitions', array( $this, 'add_pro_attr_definitions' ), 10, 1 );
		add_filter( 'spectra_flex_text_align_blocks', array( $this, 'add_pro_flex_text_align_blocks' ), 10, 1 );
		add_filter( 'spectra_responsive_css_selector', array( $this, 'modify_filter_button_selector' ), 10, 3 );
		// Register and enqueue responsive videos script for frontend.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_responsive_videos_script' ), 10, 1 );
	}

	/**
	 * Sets default layout for Spectra Pro blocks.
	 * 
	 * This method merges the default layouts defined in this class
	 * with any existing layouts from the free version of Spectra.
	 * 
	 * @since 2.0.0-beta.1
	 * @param array $layout Existing default layouts from Spectra free.
	 * @return array Modified layout array with Pro blocks added.
	 */
	public function set_default_layout( $layout ) {
		return array_merge( $layout, $this->blocks_default_layout );
	}

	/**
	 * Add Pro block attribute definitions to the responsive system.
	 * 
	 * This filter merges Pro block attribute definitions with the existing
	 * definitions from the free plugin.
	 * 
	 * @since 2.0.0-beta.1
	 * @param array $definitions Existing attribute definitions from Spectra free.
	 * @return array Modified definitions array with Pro blocks added.
	 */
	public function add_pro_attr_definitions( $definitions ) {
		return array_merge( $definitions, $this->pro_block_attributes );
	}

	/**
	 * Add Pro button blocks to the flex text alignment system.
	 * 
	 * This method adds Pro button blocks that use flexbox and need
	 * justifyContent instead of textAlign for proper responsive alignment.
	 * 
	 * @since 3.0.0-beta.1
	 * @param array $flex_blocks Existing flex text alignment blocks from Spectra free.
	 * @return array Modified flex blocks array with Pro button blocks added.
	 */
	public function add_pro_flex_text_align_blocks( $flex_blocks ) {
		$pro_button_blocks = array(
			'spectra-pro/loop-builder-child-filter-button',             // Filter button.
			'spectra-pro/loop-builder-child-pagination-next-button',    // Next page button.
			'spectra-pro/loop-builder-child-pagination-previous-button', // Previous page button.
			'spectra-pro/loop-builder-child-pagination-page-numbers-button', // Page number button.
			'spectra-pro/loop-builder-child-reset-all-button',          // Reset all button.
		);
		
		return array_merge( $flex_blocks, $pro_button_blocks );
	}

	/**
	 * Modify CSS selector for filter button block to target individual buttons.
	 *
	 * The filter button block renders multiple <a> elements, so we need to modify
	 * the CSS selector to target the individual buttons instead of the container.
	 *
	 * @since 3.0.0-beta.1
	 * @param string $selector The original CSS selector.
	 * @param string $block_name The block name.
	 * @param string $spectra_id The block's unique ID.
	 * @return string Modified CSS selector.
	 */
	public function modify_filter_button_selector( $selector, $block_name, $spectra_id ) {
		if ( 'spectra-pro/loop-builder-child-filter-button' === $block_name ) {
			return ".wp-block-spectra-pro-loop-builder-child-filter-button.wp-block-spectra-pro-loop-builder-child-filter-button[data-spectra-id='{$spectra_id}'],
				.wp-block-spectra-pro-loop-builder-child-filter-button[data-spectra-id='{$spectra_id}'] ~ .wp-block-spectra-pro-loop-builder-child-filter-button";
		}

		return $selector;
	}

	/**
	 * Enqueue responsive videos script for frontend.
	 * 
	 * Conditionally enqueues the responsive videos JavaScript file only when
	 * container or slider blocks with responsive video backgrounds are present.
	 * This handles dynamic video source switching based on viewport size.
	 * 
	 * @since 3.0.0-beta.1
	 * @return void
	 */
	public function enqueue_responsive_videos_script() {
		// Only enqueue on frontend requests (excluding AJAX and admin).
		if ( wp_doing_ajax() || is_admin() ) {
			return;
		}

		// Only enqueue if container, slider, or modal blocks are present.
		if ( ! has_block( 'spectra-pro/login' ) && ! has_block( 'spectra-pro/register' ) ) {
			return;
		}

		wp_enqueue_script(
			'spectra-responsive-videos',
			SPECTRA_PRO_2_URL . 'assets/js/responsive-videos.js',
			array(),
			filemtime( SPECTRA_PRO_2_DIR . 'assets/js/responsive-videos.js' ),
			true
		);
	}

	/**
	 * Generate responsive CSS for Pro blocks.
	 *
	 * This method delegates to the free version's ResponsiveControls class
	 * to generate CSS, as the Pro version extends the free version's functionality.
	 *
	 * @since 3.0.0-beta.1
	 * @param string $spectra_id The block's unique ID.
	 * @param array  $responsive_controls Responsive control data.
	 * @param string $block_name The block name.
	 * @param array  $attrs Block attributes.
	 * @return string Generated responsive CSS.
	 */
	public function generate_responsive_css( $spectra_id, $responsive_controls, $block_name, $attrs ) {
		// Delegate to the free version's ResponsiveControls class.
		if ( class_exists( '\Spectra\Extensions\ResponsiveControls' ) ) {
			$free_responsive = \Spectra\Extensions\ResponsiveControls::instance();
			if ( method_exists( $free_responsive, 'generate_responsive_css' ) ) {
				return $free_responsive->generate_responsive_css( $spectra_id, $responsive_controls, $block_name, $attrs );
			}
		}

		return '';
	}

	/**
	 * Get the default layout for a specific block.
	 *
	 * This method returns the default layout configuration for Pro blocks.
	 *
	 * @since 3.0.0-beta.1
	 * @param string $block_name The block name.
	 * @return array Default layout configuration.
	 */
	public function get_block_default_layout( $block_name ) {
		return $this->blocks_default_layout[ $block_name ] ?? array();
	}
}
