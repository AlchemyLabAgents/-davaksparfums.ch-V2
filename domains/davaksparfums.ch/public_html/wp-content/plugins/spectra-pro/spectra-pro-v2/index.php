<?php
/**
 * Load the Spectra Pro 2 Requirements.
 * 
 * @package SpectraPro
 */

use SpectraPro\AssetLoader;
use SpectraPro\BlockManager;
use SpectraPro\ExtensionManager;
use SpectraPro\RestApi\RestApi;
use SpectraPro\AnalyticsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Define constants.
 */
define( 'SPECTRA_PRO_2_FILE', __FILE__ );
define( 'SPECTRA_PRO_2_DIR', plugin_dir_path( SPECTRA_PRO_2_FILE ) );
define( 'SPECTRA_PRO_2_URL', plugins_url( '/', SPECTRA_PRO_2_FILE ) );

/**
 * Include the autoloader safely.
 */
$autoload_file = dirname( __FILE__ ) . '/includes/autoload.php';

if ( file_exists( $autoload_file ) ) {
	require_once $autoload_file;
} else {
	wp_die( esc_html__( 'Required file missing. Plugin cannot be initialized.', 'spectra-pro' ) ); // Stop execution with a message.
}

/**
 * Include helper files
 */
require_once dirname( __FILE__ ) . '/includes/Helpers/login.php';

/**
 * Initialize the plugin.
 * 
 * @since 2.0.0-beta.1
 */
function spectra_pro_init() {
	( BlockManager::instance() )->init();
	( ExtensionManager::instance() )->init();
	( AssetLoader::instance() )->init();
	RestApi::init();
	
	// Initialize analytics integration.
	AnalyticsManager::init();
}
add_action( 'spectra_pro_after_loaded', 'spectra_pro_init' );
