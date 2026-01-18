<?php
/**
 * Initialize REST API.
 *
 * This file is responsible for bootstrapping the Spectra Pro REST API system.
 * It manages the registration of all REST API controllers and provides a central
 * location for managing API endpoints. The REST API is used for dynamic features
 * like block previews and dynamic content loading.
 *
 * @since 2.0.0-beta.1
 *
 * @package  SpectraPro\RestApi
 */

namespace SpectraPro\RestApi;

// Import the base controller that all API controllers must extend.
// This ensures consistent structure and functionality across endpoints.
use SpectraPro\RestApi\Controllers\Version2\BaseController;

// Import specific controller implementations.
// Each controller handles a specific set of related endpoints.
use SpectraPro\RestApi\Controllers\Version2\DynamicContentController;

// WordPress security check - prevent direct file access.
// If WordPress isn't loaded (ABSPATH not defined), exit immediately.
defined( 'ABSPATH' ) || exit;

/**
 * Class to manage REST API.
 *
 * This static class serves as the central manager for all Spectra Pro REST API
 * functionality. It handles the initialization and registration of API controllers,
 * providing a clean and extensible architecture for adding new endpoints.
 *
 * @since 2.0.0-beta.1
 */
class RestApi {
	/**
	 * Hook into WordPress ready to init the REST API as needed.
	 *
	 * This method is called during plugin initialization to set up REST API hooks.
	 * It registers our callback with WordPress's 'rest_api_init' action, ensuring
	 * our custom endpoints are available when the REST API is initialized.
	 *
	 * @since 2.0.0-beta.1
	 * 
	 * @return void
	 */
	public static function init() {
		// Register our REST route registration method with WordPress
		// This ensures our endpoints are available at the right time
		// Using array notation for static method callback.
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * This method is called by WordPress during 'rest_api_init' action.
	 * It iterates through all registered controllers and calls their
	 * register_routes() method to set up individual endpoints.
	 *
	 * @since 2.0.0-beta.1
	 * 
	 * @return void
	 */
	public static function register_rest_routes() {
		// Get all controller classes that should be registered.
		foreach ( self::get_rest_controllers() as $controller_class ) {
			// Validate that the controller extends BaseController.
			// This ensures it has the required interface and methods.
			if ( ! is_subclass_of( $controller_class, BaseController::class ) ) {
				// Skip any controllers that don't extend BaseController.
				// This prevents errors and ensures consistency.
				continue; // Skip invalid classes.
			}

			// Get singleton instance and register its routes.
			// The instance() method is provided by the Singleton trait.
			// Each controller's register_routes() sets up its specific endpoints.
			( $controller_class::instance() )->register_routes();
		}
	}

	/**
	 * Get registered REST API controllers.
	 *
	 * Returns an array of controller class names that should be registered
	 * with the REST API. This method provides a filterable list, allowing
	 * third-party developers to add or modify controllers.
	 *
	 * @since 2.0.0-beta.1
	 * 
	 * @return array Array of fully-qualified controller class names.
	 */
	protected static function get_rest_controllers() {
		// Define the core controllers that ship with Spectra Pro.
		// Each controller handles a specific domain of functionality.
		$controllers = array(
			// Handles dynamic content features like post queries, custom fields.
			DynamicContentController::class,
		);

		/**
		 * Filter to modify REST API controllers.
		 *
		 * This filter allows third-party developers to:
		 * - Add new REST API controllers
		 * - Remove existing controllers
		 * - Replace controllers with custom implementations
		 *
		 * Example usage:
		 * add_filter( 'spectra_pro_rest_api_get_controllers', function( $controllers ) {
		 *     $controllers[] = 'MyPlugin\RestApi\CustomController';
		 *     return $controllers;
		 * } );
		 *
		 * @since 2.0.0-beta.1
		 * 
		 * @param array $controllers Array of controller class names (fully qualified)
		 * @return array Modified array of controller class names
		 */
		return apply_filters( 'spectra_pro_rest_api_get_controllers', $controllers );
	}
}
