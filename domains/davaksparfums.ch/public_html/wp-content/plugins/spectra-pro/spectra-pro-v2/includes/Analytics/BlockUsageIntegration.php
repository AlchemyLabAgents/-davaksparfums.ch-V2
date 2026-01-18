<?php
/**
 * Block Usage Analytics Integration for Spectra Pro v2.
 *
 * This file provides integration with Spectra 3's analytics system,
 * allowing Spectra Pro blocks to be tracked in the main analytics.
 *
 * @package Spectra Pro
 * @since 3.0.0-beta.1
 */

namespace SpectraPro\Analytics;

defined( 'ABSPATH' ) || exit;

/**
 * Block Usage Analytics Integration class.
 *
 * Integrates Spectra Pro v2 blocks with the main Spectra 3 analytics system.
 *
 * @since 3.0.0-beta.1
 */
class BlockUsageIntegration {

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
		// Only initialize if Spectra 3 analytics is available.
		if ( ! $this->is_spectra_3_analytics_available() ) {
			return;
		}

		// Hook into Spectra 3's block tracking filters.
		add_filter( 'spectra_analytics_allow_block_tracking', array( $this, 'allow_pro_block_tracking' ), 10, 4 );
		add_filter( 'spectra_analytics_allow_root_block', array( $this, 'allow_pro_root_block' ), 10, 4 );
		add_filter( 'spectra_pro_blocks_directory', array( $this, 'provide_pro_blocks_directory' ) );
		add_filter( 'spectra_analytics_include_pro_blocks', array( $this, 'should_include_pro_blocks' ) );

		// Add Pro-specific analytics data.
		add_filter( 'bsf_core_stats', array( $this, 'add_pro_specific_stats' ), 25 );

		// Clear analytics cache when Pro plugin is activated/deactivated.
		add_action( 'activated_plugin', array( $this, 'clear_analytics_cache_on_plugin_change' ) );
		add_action( 'deactivated_plugin', array( $this, 'clear_analytics_cache_on_plugin_change' ) );
	}

	/**
	 * Check if Spectra 3 analytics system is available.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @return bool True if Spectra 3 analytics is available.
	 */
	private function is_spectra_3_analytics_available() {
		return class_exists( '\Spectra\Analytics\BlockUsageTracker' );
	}

	/**
	 * Allow Spectra Pro blocks to be tracked in analytics.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param bool   $allowed     Whether the block is allowed to be tracked.
	 * @param string $block_name  Block name without prefix.
	 * @param string $block_prefix Block prefix ('spectra-pro').
	 * @param string $full_block_name Full block name with prefix.
	 * @return bool Whether to allow tracking.
	 */
	public function allow_pro_block_tracking( $allowed, $block_name, $block_prefix, $full_block_name ) {
		// Only handle Spectra Pro blocks.
		if ( 'spectra-pro' !== $block_prefix ) {
			return $allowed;
		}

		// Security check: Only allow known safe Pro blocks.
		$safe_pro_blocks = $this->get_safe_pro_blocks();
		
		return in_array( $block_name, $safe_pro_blocks, true );
	}

	/**
	 * Allow Spectra Pro root-level blocks to be included in available blocks list.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param bool   $allowed     Whether the block is allowed as root block.
	 * @param string $block_name  Block name without prefix.
	 * @param string $block_prefix Block prefix ('spectra-pro').
	 * @param array  $block_data  Block metadata from block.json.
	 * @return bool Whether to allow as root block.
	 */
	public function allow_pro_root_block( $allowed, $block_name, $block_prefix, $block_data ) {
		// Only handle Spectra Pro blocks.
		if ( 'spectra-pro' !== $block_prefix ) {
			return $allowed;
		}

		// Additional security: Ensure block has proper metadata.
		if ( empty( $block_data['name'] ) || empty( $block_data['title'] ) ) {
			return false;
		}

		// Check if block is in our safe list.
		$safe_pro_blocks = $this->get_safe_pro_blocks();
		
		return in_array( $block_name, $safe_pro_blocks, true );
	}

	/**
	 * Provide the directory path for Spectra Pro blocks.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param string $current_dir Current directory path.
	 * @return string Directory path for Pro blocks.
	 */
	public function provide_pro_blocks_directory( $current_dir ) {
		$pro_blocks_dir = plugin_dir_path( __FILE__ ) . '../build/blocks/';
		
		// Fallback to src directory if build doesn't exist.
		if ( ! is_dir( $pro_blocks_dir ) ) {
			$pro_blocks_dir = plugin_dir_path( __FILE__ ) . '../src/blocks/';
		}

		return is_dir( $pro_blocks_dir ) ? $pro_blocks_dir : $current_dir;
	}

	/**
	 * Determine if Pro blocks should be included in analytics.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param bool $include_pro Current setting.
	 * @return bool Whether to include Pro blocks.
	 */
	public function should_include_pro_blocks( $include_pro ) {
		// Only include if user has opted into analytics and Pro plugin is active.
		return $include_pro && $this->is_pro_plugin_active();
	}

	/**
	 * Add Spectra Pro specific analytics data.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param array $stats Existing BSF Analytics stats.
	 * @return array Enhanced stats with Pro-specific data.
	 */
	public function add_pro_specific_stats( $stats ) {
		// Only add Pro stats if analytics are enabled and Pro is active.
		if ( ! $this->should_add_pro_stats() ) {
			return $stats;
		}

		// Ensure the spectra plugin data container exists.
		if ( empty( $stats['plugin_data']['spectra'] ) || ! is_array( $stats['plugin_data']['spectra'] ) ) {
			$stats['plugin_data']['spectra'] = array();
		}

		// Add Pro plugin version and status.
		$stats['plugin_data']['spectra']['spectra_pro_v2'] = array(
			'version'                     => $this->get_pro_version(),
			'active'                      => $this->is_pro_plugin_active(),
			'blocks_directory_accessible' => is_readable( $this->provide_pro_blocks_directory( '' ) ),
			'integration_active'          => true,
		);

		return $stats;
	}

	/**
	 * Clear analytics cache when plugin activation status changes.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param string $plugin Plugin path.
	 */
	public function clear_analytics_cache_on_plugin_change( $plugin ) {
		// Only clear cache for Spectra-related plugins.
		if ( strpos( $plugin, 'spectra' ) === false ) {
			return;
		}

		// Clear relevant caches.
		wp_cache_delete( 'spectra_3_comprehensive_analytics', 'spectra' );
		wp_cache_delete( 'spectra_all_registered_blocks', 'spectra' );
	}

	/**
	 * Get list of safe Spectra Pro blocks that can be tracked.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @return array Array of safe Pro block names.
	 */
	private function get_safe_pro_blocks() {
		$safe_blocks = array(
			'loop-builder',
			'loop-builder-child-filter',
			'loop-builder-child-filter-button',
			'loop-builder-child-filter-checkbox',
			'loop-builder-child-filter-select',
			'loop-builder-child-no-results',
			'loop-builder-child-pagination',
			'loop-builder-child-pagination-next-button',
			'loop-builder-child-pagination-page-numbers-button',
			'loop-builder-child-pagination-previous-button',
			'loop-builder-child-reset-all-button',
			'loop-builder-child-search',
			'loop-builder-child-sort',
			'loop-builder-child-template',
		);

		// Apply filter to allow customization.
		return apply_filters( 'spectra_pro_safe_analytics_blocks', $safe_blocks );
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
	 * Get Spectra Pro version.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @return string Pro plugin version.
	 */
	private function get_pro_version() {
		if ( defined( 'SPECTRA_PRO_VER' ) ) {
			return SPECTRA_PRO_VER;
		}

		// Fallback: Try to get version from plugin file.
		$plugin_data = get_file_data( 
			plugin_dir_path( __FILE__ ) . '../../spectra-pro.php', 
			array( 'Version' => 'Version' ), 
			'plugin' 
		);

		return $plugin_data['Version'] ?? '2.0.0';
	}

	/**
	 * Determine if Pro-specific stats should be added.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @return bool True if Pro stats should be added.
	 */
	private function should_add_pro_stats() {
		// Check if analytics are enabled via parent Spectra settings.
		if ( ! class_exists( '\UAGB_Admin_Helper' ) ) {
			return false;
		}

		$optin_status = \UAGB_Admin_Helper::get_admin_settings_option( 'spectra_analytics_optin', 'no' );

		return 'yes' === $optin_status && $this->is_pro_plugin_active();
	}
}

// Initialize the integration.
BlockUsageIntegration::instance();
