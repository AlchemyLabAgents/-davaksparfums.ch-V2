<?php
/**
 * Countdown Extension
 *
 * @package SpectraPro\Extensions
 */

namespace SpectraPro\Extensions;

use Spectra\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class Modal
 *
 * Handles the modal block functionality and navigation features.
 *
 * @since 2.0.0-beta.1
 */
class Modal {

	/**
	 * Singleton trait.
	 */
	use Singleton;

	/**
	 * Block name constant.
	 * 
	 * @since 2.0.0-beta.1
	 *
	 * @var string
	 */
	const BLOCK_NAME = 'spectra/modal';

	/**
	 * Initialize the extension.
	 *
	 * @since 2.0.0-beta.1
	 * @return void
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register custom navigation required scripts.
	 *
	 * @since 2.0.0-beta.1
	 * @return void
	 */
	public function register_scripts() {
		wp_register_script(
			'spectra-pro-modal-settings',
			SPECTRA_PRO_2_URL . 'assets/js/modal-script.js',
			array(),
			SPECTRA_PRO_VER,
			true
		);
		wp_enqueue_script( 'spectra-pro-modal-settings' );
	}
	
	/**
	 * Enqueues frontend assets for dynamic content functionality.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		wp_enqueue_style( 'spectra-extensions-modal' );
	}
}
