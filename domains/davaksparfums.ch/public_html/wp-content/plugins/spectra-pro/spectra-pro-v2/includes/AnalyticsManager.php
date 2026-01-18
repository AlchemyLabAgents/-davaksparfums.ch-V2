<?php
/**
 * Analytics Manager for Spectra Pro v2.
 *
 * @package SpectraPro
 * @since 3.0.0-beta.1
 */

namespace SpectraPro;

defined( 'ABSPATH' ) || exit;

/**
 * Analytics Manager class.
 *
 * Handles the initialization of analytics integration for Spectra Pro.
 *
 * @since 3.0.0-beta.1
 */
class AnalyticsManager {

	/**
	 * Initialize analytics integration.
	 *
	 * @since 3.0.0-beta.1
	 */
	public static function init() {
		// Initialize the block usage integration.
		self::init_block_usage_integration();

		// Initialize the extension usage integration.
		self::init_extension_usage_integration();

		// Add any additional analytics components here in the future.
	}

	/**
	 * Initialize block usage analytics integration.
	 *
	 * @since 3.0.0-beta.1
	 */
	private static function init_block_usage_integration() {
		// Only initialize if the integration class exists.
		if ( class_exists( '\SpectraPro\Analytics\BlockUsageIntegration' ) ) {
			\SpectraPro\Analytics\BlockUsageIntegration::instance();
		}
	}

	/**
	 * Initialize extension usage analytics integration.
	 *
	 * @since 3.0.0-beta.1
	 */
	private static function init_extension_usage_integration() {
		// Only initialize if the integration class exists.
		if ( class_exists( '\SpectraPro\Analytics\ExtensionUsageIntegration' ) ) {
			\SpectraPro\Analytics\ExtensionUsageIntegration::instance();
		}
	}
}
