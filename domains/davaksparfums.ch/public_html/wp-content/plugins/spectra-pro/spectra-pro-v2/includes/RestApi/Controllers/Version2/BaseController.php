<?php
/**
 * Base class for REST API controllers.
 *
 * @since 2.0.0-beta.1
 * 
 * @package SpectraPro\RestApi\Controllers\Version2
 */

namespace SpectraPro\RestApi\Controllers\Version2;  

defined( 'ABSPATH' ) || exit;

/**
 * Base class for REST API controllers.
 */
abstract class BaseController {
	/**
	 * REST API namespace.
	 * 
	 * @since 2.0.0-beta.1
	 *
	 * @var string
	 */
	protected $namespace = 'spectra/pro/v2';

	/**
	 * Registers REST API routes.
	 * 
	 * @since 2.0.0-beta.1
	 *
	 * @return void
	 */
	abstract public function register_routes();

	/**
	 * Creates a standardized success response.
	 * 
	 * @since 2.0.0-beta.1
	 *
	 * @param mixed $data    The data to return in the response.
	 * @param int   $status  HTTP status code (default: 200).
	 * @return \WP_REST_Response
	 */
	protected static function success(
		$data,
		$status = 200
	) {
		$response = array(
			'success' => true,
			'data'    => $data,
		);

		return new \WP_REST_Response( $response, $status );
	}

	/**
	 * Creates a standardized error response.
	 * 
	 * @since 2.0.0-beta.1
	 *
	 * @param string $code    Error code.
	 * @param string $message Error message.
	 * @param int    $status  HTTP status code (default: 400).
	 * @param array  $data   Additional error data (optional).
	 * @return \WP_Error
	 */
	protected static function error(
		$code,
		$message,
		$status = 400,
		$data = array()
	) {
		return new \WP_Error(
			$code,
			$message,
			array_merge( array( 'status' => $status ), $data )
		);
	}
}
