<?php
/**
 * Countdown Extension
 *
 * @package SpectraPro\Extensions
 */

namespace SpectraPro\Extensions;

use Spectra\Traits\Singleton;
use WP_HTML_Tag_Processor;

/**
 * Countdown class for handling countdown block functionality.
 *
 * @since 2.0.0-beta.1
 */
class Countdown {

	use Singleton;

	/**
	 * Block name constant.
	 * 
	 * @since 2.0.0-beta.1
	 *
	 * @var string
	 */
	const BLOCK_NAME = 'spectra/countdown';

	/**
	 * Cookie name prefix for evergreen timers.
	 * 
	 * @since 2.0.0-beta.1
	 *
	 * @var string
	 */
	const COOKIE_PREFIX = 'spectra_countdown_evergreen_end_';

	/**
	 * Initialize the countdown extension.
	 *
	 * Hooks into WordPress filters to extend the countdown block functionality.
	 *
	 * @since 2.0.0-beta.1
	 * 
	 * @return void
	 */
	public function init() {
		add_filter( 'spectra_countdown_context', array( $this, 'extend_context_with_pro_attributes' ), 10, 2 );
		add_filter( 'render_block_data', array( $this, 'handle_timer_expiry_action' ), 11 );
		add_filter( 'render_block', array( $this, 'enqueue_countdown_assets' ), 10, 2 );
	}

	/**
	 * Handle timer expiry actions for the countdown block.
	 *
	 * Modifies block attributes based on timer expiration and end action.
	 *
	 * @since 2.0.0-beta.1
	 * 
	 * @param array $parsed_block The parsed block data.
	 * @return array The modified parsed block data.
	 */
	public function handle_timer_expiry_action( $parsed_block ) {
		if ( self::BLOCK_NAME !== $parsed_block['blockName'] ) {
			return $parsed_block;
		}

		// Get the block attributes to check timer settings.
		$attributes        = $parsed_block['attrs'];
		$timer_type        = $attributes['timerType'] ?? 'date';
		$timer_end_action  = $attributes['timerEndAction'] ?? 'zero';
		$evergreen_days    = $attributes['evergreenDays'] ?? 0;
		$evergreen_hours   = $attributes['evergreenHours'] ?? 0;
		$evergreen_minutes = $attributes['evergreenMinutes'] ?? 0;
		$end_date_time     = $attributes['endDateTime'] ?? '';
		$unique_id         = $attributes['uniqueId'] ?? '';
		$reset_after_days  = $attributes['resetAfterDays'] ?? 1;
		$reload_required   = $attributes['reloadRequired'] ?? true;

		// Check if the timer is expired based on the timer type and other parameters.
		$is_expired   = $this->is_timer_expired( $timer_type, $end_date_time, $unique_id, $evergreen_days, $evergreen_hours, $evergreen_minutes, $reset_after_days );
		$inner_blocks = $parsed_block['innerBlocks'] ?? [];

		// Handle actions if timer has expired and the 'replace' action is chosen for expired timers.
		if ( $is_expired && 'replace' === $timer_end_action ) {
			foreach ( $inner_blocks as $index => $block ) {
				$child_name = $block['blockName'] ?? '';
		
				// Disable the child blocks (like day, hour, minute, second) if the timer expired.
				if ( in_array(
					$child_name,
					array(
						'spectra/countdown-child-day',
						'spectra/countdown-child-hour',
						'spectra/countdown-child-minute',
						'spectra/countdown-child-second',
						'spectra/countdown-child-separator',
					),
					true 
				) ) {
					$parsed_block['innerBlocks'][ $index ]['attrs']['show'] = false;
				}
			}
		}

		// Hide child expiry wrapper if timerEndAction is not 'replace' or if timer is not expired and reload is required.
		if ( 'replace' !== $timer_end_action || ( 'replace' === $timer_end_action && ! $is_expired && $reload_required ) ) {
			foreach ( $inner_blocks as $index => $block ) {
				// Hide the expiry wrapper block if the conditions are met.
				if ( 'spectra/countdown-child-expiry-wrapper' === $block['blockName'] ) {
					$parsed_block['innerBlocks'][ $index ]['attrs']['show'] = false;
					break;
				}
			}
		}

		// Update the block attributes.
		$parsed_block['attrs'] = $attributes;

		return $parsed_block;
	}

	/**
	 * Extend the context with pro attributes for the countdown block.
	 *
	 * Merges additional attributes into the block context for frontend rendering.
	 *
	 * @since 2.0.0-beta.1
	 * 
	 * @param array $context The context.
	 * @param array $attributes The block attributes.
	 * @return array
	 */
	public function extend_context_with_pro_attributes( $context, $attributes ) {
		return array_merge(
			$context,
			array(
				'timerType'        => $attributes['timerType'] ?? 'date',
				'timerEndAction'   => $attributes['timerEndAction'] ?? 'zero',
				'redirectURL'      => $attributes['redirectURL'] ?? '',
				'reloadRequired'   => $attributes['reloadRequired'] ?? true,
				'autoReload'       => $attributes['autoReload'] ?? false,
				'evergreenDays'    => $attributes['evergreenDays'] ?? 0,
				'evergreenHours'   => $attributes['evergreenHours'] ?? 0,
				'evergreenMinutes' => $attributes['evergreenMinutes'] ?? 0,
				'uniqueId'         => $attributes['uniqueId'] ?? '',
				'resetAfterDays'   => $attributes['resetAfterDays'] ?? 1,
			)
		);
	}

	/**
	 * Enqueue assets for the countdown block.
	 *
	 * @since 2.0.0-beta.1
	 * 
	 * @param string $block_content The block content.
	 * @param array  $block The block data.
	 * @return string
	 */
	public function enqueue_countdown_assets( $block_content, $block ) {
		if ( self::BLOCK_NAME !== $block['blockName'] ) {
			return $block_content;
		}

		// Get the block attributes to check timer settings.
		$attributes        = $block['attrs'];
		$timer_type        = $attributes['timerType'] ?? 'date';
		$timer_end_action  = $attributes['timerEndAction'] ?? 'zero';
		$evergreen_days    = $attributes['evergreenDays'] ?? 0;
		$evergreen_hours   = $attributes['evergreenHours'] ?? 0;
		$evergreen_minutes = $attributes['evergreenMinutes'] ?? 0;
		$end_date_time     = $attributes['endDateTime'] ?? '';
		$unique_id         = $attributes['uniqueId'] ?? '';
		$reset_after_days  = $attributes['resetAfterDays'] ?? 1;

		// Check if the timer is expired.
		$is_expired = $this->is_timer_expired( $timer_type, $end_date_time, $unique_id, $evergreen_days, $evergreen_hours, $evergreen_minutes, $reset_after_days );

		// If expired and end action is 'hide' or 'redirect', return empty content.
		if ( $is_expired && in_array( $timer_end_action, array( 'hide', 'redirect' ), true ) ) {
			return '';
		}

		// If not expired and 'replace' is the action, update block class.
		if ( ! $is_expired && 'replace' === $timer_end_action ) {
			$processor = new WP_HTML_Tag_Processor( $block_content );

			if ( $processor->next_tag( array( 'class' => 'wp-block-spectra-countdown' ) ) ) {
				$current_class = $processor->get_attribute( 'class' );
				$updated_class = $current_class . ' replace';
				$processor->set_attribute( 'class', $updated_class );
	
				$block_content = $processor->get_updated_html();
			}
		}

		// Enqueue the countdown block's necessary JavaScript and CSS files.
		wp_enqueue_script_module(
			'spectra-pro-countdown',
			SPECTRA_PRO_2_URL . 'build/extensions/countdown/view.js',
			array(),
			SPECTRA_PRO_VER,
			true
		);

		wp_enqueue_style( 'spectra-extensions-countdown' );

		return $block_content;
	}

	/**
	 * Check if a timer is expired.
	 *
	 * Supports both date-based and evergreen timers.
	 *
	 * @since 2.0.0-beta.1
	 * 
	 * @param string $timer_type The timer type ('date' or 'evergreen').
	 * @param string $end_date_time The end date and time for date-based timers.
	 * @param string $unique_id Unique identifier for evergreen timers.
	 * @param int    $days Duration in days for evergreen timers.
	 * @param int    $hours Duration in hours for evergreen timers.
	 * @param int    $minutes Duration in minutes for evergreen timers.
	 * @param int    $reset_after_days Reset period in days for evergreen timers.
	 * @return bool True if the timer is expired, false otherwise.
	 */
	private function is_timer_expired( $timer_type, $end_date_time, $unique_id, $days, $hours, $minutes, $reset_after_days ) {
		$current_time = time(); // UTC.

		// Check expiration for date-based timer.
		if ( 'date' === $timer_type ) {
			$end_time = strtotime( $end_date_time ?? '' );

			if ( ! $end_time ) {
				return false; // Invalid or missing end date.
			}

			// Expired if end time is before or equal to current time.
			return $end_time <= $current_time;
		}

		// Check expiration for evergreen timer.
		if ( 'evergreen' === $timer_type && $unique_id ) {
			$cookie_name  = self::COOKIE_PREFIX . $unique_id;
			$cookie_value = sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ?? '' ) );

			if ( ! $cookie_value ) {
				return false; // Cookie not set yet.
			}

			$end_date = strtotime( $cookie_value );

			if ( ! $end_date || $end_date > $current_time ) {
				return false; // Not yet expired.
			}

			// Check if reset period has elapsed.
			if ( $reset_after_days > 0 ) {
				// Calculate reset period and check if still within it.
				$duration_seconds = ( $days * 86400 ) + ( $hours * 3600 ) + ( $minutes * 60 );
				$cookie_set_time  = $end_date - $duration_seconds;
				$reset_time       = $cookie_set_time + ( $reset_after_days * 86400 );

				if ( $current_time < $reset_time ) {
					return true; // Expired but still within reset window.
				}
			}
		}//end if

		// Default case: not expired.
		return false;
	}
}
