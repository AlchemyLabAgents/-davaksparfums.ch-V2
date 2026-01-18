<?php
/**
 * Helper to convert colors.
 * 
 * @package SpectraPro\Helpers
 */

namespace SpectraPro\Helpers;

use InvalidArgumentException;
use ValueError;

/**
 * Class ColorConverter.
 * 
 * @since 2.0.0-beta.1
 */
class ColorConverter {
	/**
	 * Pattern to match for a hexadecimal color value, without an alpha channel.
	 * 
	 * @since 2.0.0-beta.1
	 * @var string 
	 */
	private static $hex_pattern = '/^#?([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/';

	/**
	 * Pattern to match for a RGB color value.
	 * 
	 * @since 2.0.0-beta.1
	 * @var string 
	 */
	private static $rgb_pattern = '/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/';
	
	/**
	 * An improved version of sanitize_hex_color for #RRGGBBAA that also verifies the alpha channel.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $color The color to sanitize.
	 * @return string       The sanitized color, or an empty string. 
	 */
	public static function sanitize_hex_color_alpha( $color ) {
		// If the color is empty, abandon ship.
		if ( empty( $color ) ) {
			return '';
		}
	
		// Remove whitespace and convert to uppercase for consistency.
		$color = trim( strtolower( $color ) );
	
		// Check for valid hex color format (#RRGGBBAA). If there are no mathces, abandon ship.
		if ( ! preg_match( '/^#([a-f0-9]{6})([a-f0-9]{2})?$/', $color, $matches ) ) {
			return '';
		}
	
		// Extract RGB and alpha parts.
		$rgb   = substr( $matches[1], 0, 6 );
		$alpha = isset( $matches[2] ) ? $matches[2] : 'ff';
	
		// Validate each component.
		foreach ( str_split( $rgb . $alpha ) as $char ) {
			// If any of the characters in the formatted color are not valid hexadecimal characters, abandon ship.
			if ( ! ctype_xdigit( $char ) ) {
				return '';
			}
		}
	
		// Return sanitized color with alpha channel.
		return '#' . $rgb . $alpha;
	}

	/**
	 * Converts hex color to RGB array.
	 *
	 * @since 2.0.0-beta.1
	 * @param string $hex Te hexadecimal color.
	 * @return array
	 */
	public static function hex_to_rgb( $hex ) {
		// If the passed value is not in the hexadecimal format, abandon ship.
		if ( ! preg_match( self::$hex_pattern, $hex, $matches ) ) {
			return array();
		}

		// Remove # symbol if present and expand 3-digit hex to 6-digit.
		$hex = str_replace( '#', '', $matches[1] );
		if ( 3 === strlen( $hex ) ) {
			// Convert 3-digit hex (e.g., F05) to 6-digit (e.g., FF0055).
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		// Convert hex string to RGB array using hexdec for base-16 conversion.
		return [
			hexdec( substr( $hex, 0, 2 ) ), // Red value.
			hexdec( substr( $hex, 2, 2 ) ), // Green value.
			hexdec( substr( $hex, 4, 2 ) ), // Blue value.
		];
	}

	/**
	 * Converts RGB values to HSL (Hue, Saturation, Lightness) format.
	 * Uses color space conversion algorithms for accurate results.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param array $rgb RGB values as [red, green, blue].
	 * @return array HSL values as [hue, saturation, lightness].
	 */
	public static function rgb_to_hsl( $rgb ) {
		// Normalize RGB values to range 0-1 for calculation.
		$r = $rgb[0] / 255;
		$g = $rgb[1] / 255;
		$b = $rgb[2] / 255;

		// Find maximum and minimum values for lightness calculation.
		$max   = max( $r, $g, $b );
		$min   = min( $r, $g, $b );
		$delta = $max - $min;

		// Calculate lightness as average of max and min.
		$l = ( $max + $min ) / 2;

		// If max equals min, color is grayscale.
		$h = 0;
		$s = 0;

		if ( $max !== $min ) {
			// Calculate saturation based on lightness.
			$s = $l > 0.5 ? $delta / ( 2 - $max - $min ) : $delta / ( $max + $min );

			// Determine hue based on which component is maximum.
			switch ( $max ) {
				case $r:
					$h = ( $g - $b ) / $delta + ( $g < $b ? 6 : 0 );
					break;
				case $g:
					$h = ( $b - $r ) / $delta + 2;
					break;
				case $b:
					$h = ( $r - $g ) / $delta + 4;
					break;
			}
			// Convert hue to degrees (0-360).
			$h *= 60;
		}//end if

		// Return HSL values with saturation and lightness as percentages.
		return [
			round( $h ), // Hue in degrees (0-360).
			round( $s * 100 ), // Saturation as percentage (0-100).
			round( $l * 100 ), // Lightness as percentage (0-100).
		];
	}

	/**
	 * Converts HSL values back to RGB format.
	 * Uses color space conversion algorithms for accurate results.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param array $hsl HSL values as [hue, saturation, lightness].
	 * @throws ValueError If HSL values are invalid.
	 * @return array RGB values as [red, green, blue].
	 */
	public static function hsl_to_rgb( $hsl ) {
		// Normalize hue to 0-1 range and convert saturation/lightness to 0-1.
		$h = $hsl[0] / 360;
		$s = $hsl[1] / 100;
		$l = $hsl[2] / 100;

		// If saturation is 0, color is grayscale.
		$r = $l;
		$g = $l;
		$b = $l;

		if ( 0 !== $s ) {
			// Calculate intermediate values for color calculation.
			$q = $l < 0.5 ? $l * ( 1 + $s ) : $l + $s - $l * $s;
			$p = 2 * $l - $q;

			// Calculate RGB values using helper function.
			$r = self::hue_to_rgb( $p, $q, $h + 1 / 3 );
			$g = self::hue_to_rgb( $p, $q, $h );
			$b = self::hue_to_rgb( $p, $q, $h - 1 / 3 );
		}

		// Return RGB values as integers (0-255).
		return [
			round( $r * 255 ),
			round( $g * 255 ),
			round( $b * 255 ),
		];
	}

	/**
	 * Helper function for HSL to RGB conversion.
	 * Calculates RGB values for a given hue using intermediate values.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param float $p Intermediate value p.
	 * @param float $q Intermediate value q.
	 * @param float $t Hue value in range 0-1.
	 * @return float RGB value in range 0-1.
	 */
	public static function hue_to_rgb( $p, $q, $t ) {
		// Normalize t to range 0-1.
		if ( $t < 0 ) {
			++$t;
		}
		if ( $t > 1 ) {
			--$t;
		}

		// Calculate RGB value based on t position in color wheel.
		if ( $t < 1 / 6 ) {
			return $p + ( $q - $p ) * 6 * $t;
		}
		if ( $t < 1 / 2 ) {
			return $q;
		}
		if ( $t < 2 / 3 ) {
			return $p + ( $q - $p ) * ( 2 / 3 - $t ) * 6;
		}
		return $p;
	}

	/**
	 * Converts RGB values to hex color code.
	 * Validates RGB values and formats as hex string.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param array $rgb RGB values as [red, green, blue].
	 * @throws ValueError If RGB values are invalid.
	 * @return string Hex color code (e.g., #FF5733).
	 */
	public static function rgb_to_hex( $rgb ) {
		$is_valid_for_conversion = true;
		// Validate RGB values are within valid range (0-255).
		foreach ( $rgb as $value ) {
			if ( $value < 0 || $value > 255 ) {
				throw new ValueError( 'RGB values must be between 0 and 255' );
			}
		}

		// Format RGB values as hex string with leading zeros.
		return sprintf( '#%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2] );
	}

	/**
	 * Processes a color input and returns all color formats.
	 * Validates input and converts to all supported formats.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $color Color in hex or RGB format.
	 * @return array Array containing hex, rgb, and hsl formats - or an empty array if an error was encountered.
	 */
	public static function process_color( $color ) {
		// Check if input is hex format, else check if input is RGB format.
		if ( preg_match( self::$hex_pattern, $color, $matches ) ) {
			$rgb = self::hex_to_rgb( $color );
		} elseif ( preg_match( self::$rgb_pattern, $color, $matches ) ) {
			// Typecasting from string of matches to integers.
			$rgb = [
				(int) $matches[1],
				(int) $matches[2],
				(int) $matches[3],
			];
		} else {
			return array();
		}

		// Convert to HSL and hex formats.
		$hsl = self::rgb_to_hsl( $rgb );
		$hex = self::rgb_to_hex( $rgb );

		// Return all color formats.
		return [
			'hex' => $hex,
			'rgb' => sprintf( 'rgb(%d,%d,%d)', $rgb[0], $rgb[1], $rgb[2] ),
			'hsl' => sprintf( 'hsl(%d,%d%%,%d%%)', $hsl[0], $hsl[1], $hsl[2] ),
		];
	}

	/**
	 * Generates shades and complementary color for a given base color.
	 * Creates lighter/darker shades and complementary color.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $base_color Base color in hex or RGB format.
	 * @return array Array of shade objects with labels and colors.
	 */
	public static function generate_shades( $base_color ) {
		// Process base color to get RGB values.
		$processed = self::process_color( $base_color );

		// If the color could not be processed, return an empty array.
		if ( empty( $processed ) ) {
			return array();
		}

		$rgb = self::hex_to_rgb( $processed['hex'] );
		$hsl = self::rgb_to_hsl( $rgb );

		// Create an array of the color labels.
		$labels = array(
			'light'      => array(
				'light',
				'lighter',
				'lightest',
				'near-white',
			),
			'dark'       => array(
				'dark',
				'darker',
				'darkest',
				'near-black',
			),
			'complement' => 'complement',
			'inverted'   => 'inverted',
			'main'       => 'main',
		);

		// Initialize shades array.
		$shades = array(
			array(
				'label' => $labels['main'],
				'color' => $base_color,
			),
		);

		// Generate lighter shades including near-white.
		for ( $i = 1; $i <= 4; $i++ ) {
			// Calculate new lightness (98% for near-white).
			$lightness = 4 === $i
				? 98 
				: $hsl[2] + ( ( 100 === $hsl[2] ? 0 : ( 100 - $hsl[2] ) ) * ( $i / 4 ) );
			
			// Create new HSL color with adjusted lightness.
			$newHsl = [ $hsl[0], $hsl[1], min( 100, $lightness ) ];
			$newRgb = self::hsl_to_rgb( $newHsl );
			$newHex = self::rgb_to_hex( $newRgb );

			// Add shade to collection.
			$shades[] = [
				'label' => $labels['light'][ $i - 1 ],
				'color' => $newHex,
			];
		}

		// Generate darker shades including near-black.
		for ( $i = 1; $i <= 4; $i++ ) {
			// Calculate new lightness (2% for near-black).
			$lightness = 4 === $i
				? 2 
				: max( 0, $hsl[2] - ( $hsl[2] * ( $i / 4 ) ) );
			
			// Create new HSL color with adjusted lightness.
			$newHsl = [ $hsl[0], $hsl[1], $lightness ];
			$newRgb = self::hsl_to_rgb( $newHsl );
			$newHex = self::rgb_to_hex( $newRgb );
			
			// Add shade to collection.
			$shades[] = [
				'label' => $labels['dark'][ $i - 1 ],
				'color' => $newHex,
			];
		}

		// Generate complementary color (180 degrees opposite on color wheel).
		$complementaryHsl = [
			( $hsl[0] + 180 ) % 360,
			$hsl[1],
			$hsl[2],
		];
		$complementaryRgb = self::hsl_to_rgb( $complementaryHsl );
		$complementaryHex = self::rgb_to_hex( $complementaryRgb );

		// Add complementary color to collection.
		$shades[] = array(
			'label' => $labels['complement'],
			'color' => $complementaryHex,
		);

		// Generate the inverted color.
		$invertedRgb = [
			255 - $rgb[0],
			255 - $rgb[1],
			255 - $rgb[2],
		];
		$invertedHex = self::rgb_to_hex( $invertedRgb );

		// Add complementary color to collection.
		$shades[] = array(
			'label' => $labels['inverted'],
			'color' => $invertedHex,
		);

		return $shades;
	}

	/**
	 * Creates the translucent variants of the given hexadecimal color.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $hexcode The 6 digit hexadecimal color.
	 * @return array          All the translucent variants, ignoring 0% and 100%.
	 */
	public static function get_translucent_colors( $hexcode ) {
		// If the passed value is not in the hexadecimal format, abandon ship.
		if ( ! preg_match( self::$hex_pattern, $hexcode ) ) {
			return array();
		}

		// Return an array with the hexadecimal value of the transparency from 0 to 255.
		return array(
			'10' => strtolower( $hexcode ) . '1a', // Decimal 25.5 (26).
			'20' => strtolower( $hexcode ) . '33', // Decimal 51.
			'30' => strtolower( $hexcode ) . '4d', // Decimal 76.5 (77).
			'40' => strtolower( $hexcode ) . '66', // Decimal 102.
			'50' => strtolower( $hexcode ) . '80', // Decimal 127.5 (128).
			'60' => strtolower( $hexcode ) . '99', // Decimal 153.
			'70' => strtolower( $hexcode ) . 'b3', // Decimal 178.5 (179).
			'80' => strtolower( $hexcode ) . 'cc', // Decimal 204.
			'90' => strtolower( $hexcode ) . 'e6', // Decimal 229.5 (230).
		);
	}
}
