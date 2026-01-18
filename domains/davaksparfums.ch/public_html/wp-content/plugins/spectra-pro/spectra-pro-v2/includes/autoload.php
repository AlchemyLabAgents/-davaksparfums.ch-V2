<?php
/**
 * Custom Autoloader for SpectraPro Namespace.
 *
 * @since 2.0.0-beta.1
 *
 * @package SpectraPro
 */

defined( 'ABSPATH' ) || exit;

spl_autoload_register(
	function ( $class ) {
		// Define the base namespace.
		$namespace = 'SpectraPro\\';

		// Ensure the class belongs to the SpectraPro namespace.
		if ( strpos( $class, $namespace ) !== 0 ) {
			return; // Not part of SpectraPro, ignore.
		}

		// Define the base directory for class files.
		$base_dir = __DIR__ . DIRECTORY_SEPARATOR;

		// Get the relative class name.
		$relative_class = substr( $class, strlen( $namespace ) );

		// Convert namespace separators to directory separators.
		$file = $base_dir . str_replace( array( '\\', '/' ), DIRECTORY_SEPARATOR, $relative_class ) . '.php';

		// Normalize path to prevent directory traversal attacks.
		$real_path = realpath( $file );

		// Check and load the class file.
		if ( $real_path && file_exists( $real_path ) && strpos( $real_path, realpath( $base_dir ) ) === 0 ) {
			require_once $real_path;
		}
	}
);

// Load register helper functions.
require_once __DIR__ . '/Helpers/register.php';
\Spectra\Helpers\Register::init();

// Load login helper functions.
require_once __DIR__ . '/Helpers/login.php';
