<?php
/**
 * Class to manage Spectra Pro Block assets.
 *
 * @package SpectraPro
 */

namespace SpectraPro;

use Spectra\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class to manage Spectra Pro Block assets.
 *
 * @since 2.0.0-beta.1
 */
class AssetLoader {

	use Singleton;

	/**
	 * Initializes the asset loader by setting up necessary components.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_common_style_assets' ) );
		add_action( 'enqueue_block_assets', array( __CLASS__, 'localize_block_editor_data' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'handle_frontend_assets' ) );
	}

	/**
	 * Enqueue all the required scripts in the admin side.
	 *
	 * @since 2.0.0-beta.1
	 * @return void
	 */
	public function enqueue_admin_assets() {

		// Load the Global Styles stylesheet.
		wp_enqueue_style( 'spectra-pro-extensions-global-styles' );
	}

	/**
	 * Register all the styles from the '/src/styles' directory.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return void
	 */
	public function enqueue_common_style_assets() {
		$css_path  = SPECTRA_PRO_2_DIR . 'build/styles/';
		$css_files = glob( $css_path . '**/*.css' ) ?? array();

		foreach ( $css_files as $css_file ) {
			// Get the parent directory name relative to built styles directory. For example, 'components'.
			$relative_path = str_replace( $css_path, '', $css_file );
			$style_type    = dirname( $relative_path );

			// Extract the file name without the extension and prepend with 'spectra-' and the directory name.
			$handle = 'spectra-' . trim( $style_type, '/' ) . '-' . basename( $css_file, '.css' );

			// Register the style.
			wp_register_style(
				$handle,
				plugins_url( 'build/styles/' . trim( $style_type, '/' ) . '/' . basename( $css_file ), SPECTRA_PRO_2_FILE ),
				array(),
				UAGB_VER
			);
		}
	}
	
	/**
	 * Localize block editor data for JavaScript.
	 *
	 * @since 2.0.0-beta.1
	 * 
	 * @return void
	 */
	public static function localize_block_editor_data() {
		$data = array(
			'rootApiUrl' => esc_url_raw( untrailingslashit( rest_url() ) ),
			'nonce'      => wp_create_nonce( 'wp_rest' ),
		);

		wp_localize_script( 'wp-block-editor', '_SPECTRA_PRO_BLOCK_EDITOR_DATA_', $data );
	}

	/**
	 * Register all block-specific assets that can be conditionally enqueued.
	 *
	 * @since 2.0.0
	 * 
	 * @return void
	 */
	public function register_block_assets() {
		// Register login block JavaScript.
		wp_register_script(
			'spectra-pro-login-v2',
			SPECTRA_PRO_2_URL . 'assets/js/login.js',
			array(), // No dependencies since it's self-contained with Interactivity API fallback.
			UAGB_VER,
			true
		);

		// Register register block JavaScript.
		wp_register_script(
			'spectra-pro-register-v2',
			SPECTRA_PRO_2_URL . 'assets/js/register.js',
			array(), // No dependencies since it's self-contained with Interactivity API fallback.
			UAGB_VER,
			true
		);
	}

	/**
	 * Conditionally enqueue frontend assets based on block presence.
	 * Following Spectra V3 pattern with has_block() checks.
	 *
	 * @since 2.0.0
	 * 
	 * @return void
	 */
	public function enqueue_frontend_assets() {
		// Only enqueue login script if login block is present.
		if ( has_block( 'spectra-pro/login' ) ) {
			wp_enqueue_script( 'spectra-pro-login-v2' );
		}

		// Only enqueue register script if register block is present.
		if ( has_block( 'spectra-pro/register' ) ) {
			wp_enqueue_script( 'spectra-pro-register-v2' );
		}
	}

	/**
	 * Handle all frontend asset registration and enqueuing.
	 * Following Spectra V3 AssetLoader pattern.
	 *
	 * @since 2.0.0
	 * 
	 * @return void
	 */
	public function handle_frontend_assets() {
		$this->register_block_assets();
		$this->enqueue_frontend_assets();
	}

}
