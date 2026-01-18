<?php
/**
 * Global Styles Extension
 *
 * @package SpectraPro\Extensions\GlobalStyles
 */

namespace SpectraPro\Extensions;

use Spectra\Traits\Singleton;
use Spectra\Helpers\Core;
use SpectraPro\Helpers\ColorConverter;

/**
 * GlobalStyles class.
 * 
 * @since 2.0.0-beta.1
 */
class GlobalStyles {

	use Singleton;

	/**
	 * Array of all the default CSS variables required.
	 * 
	 * @since 2.0.0-beta.1
	 *
	 * @var array
	 */
	private $default_gs_system_variables = array(
		'spacing'  => array(
			'xs'  => array(
				'value' => 0.5,
				'unit'  => 'rem',
			),
			'sm'  => array(
				'value' => 1,
				'unit'  => 'rem',
			),
			'md'  => array(
				'value' => 1.5,
				'unit'  => 'rem',
			),
			'lg'  => array(
				'value' => 2,
				'unit'  => 'rem',
			),
			'xl'  => array(
				'value' => 3,
				'unit'  => 'rem',
			),
			'xxl' => array(
				'value' => 5,
				'unit'  => 'rem',
			),
		),
		'border'   => array(
			'xs'  => array(
				'value' => 0.125,
				'unit'  => 'rem',
			),
			'sm'  => array(
				'value' => 0.25,
				'unit'  => 'rem',
			),
			'md'  => array(
				'value' => 0.5,
				'unit'  => 'rem',
			),
			'lg'  => array(
				'value' => 0.75,
				'unit'  => 'rem',
			),
			'xl'  => array(
				'value' => 1,
				'unit'  => 'rem',
			),
			'xxl' => array(
				'value' => 1.5,
				'unit'  => 'rem',
			),
		),
		'fontsize' => array(
			'h1'  => array(
				'value' => 2.25,
				'unit'  => 'rem',
			),
			'h2'  => array(
				'value' => 1.875,
				'unit'  => 'rem',
			),
			'h3'  => array(
				'value' => 1.5,
				'unit'  => 'rem',
			),
			'h4'  => array(
				'value' => 1.25,
				'unit'  => 'rem',
			),
			'h5'  => array(
				'value' => 1.125,
				'unit'  => 'rem',
			),
			'h6'  => array(
				'value' => 1,
				'unit'  => 'rem',
			),
			'xs'  => array(
				'value' => 0.75,
				'unit'  => 'rem',
			),
			'sm'  => array(
				'value' => 0.875,
				'unit'  => 'rem',
			),
			'md'  => array(
				'value' => 1,
				'unit'  => 'rem',
			),
			'lg'  => array(
				'value' => 1.25,
				'unit'  => 'rem',
			),
			'xl'  => array(
				'value' => 1.5,
				'unit'  => 'rem',
			),
			'xxl' => array(
				'value' => 2,
				'unit'  => 'rem',
			),
		),
	);

	/**
	 * Array of all the Global Styles related option names.
	 * 
	 * @since 2.0.0-beta.1
	 *
	 * @var array
	 */
	private $gs_options = array(
		'system_variables' => 'spectra_pro_gs_system_variables',
		'user_css'         => 'spectra_pro_gs_user_css',
		'block_defaults'   => 'spectra_pro_gs_block_defaults',
	);

	/**
	 * Common string used to identify the cached CSS variables.
	 * 
	 * @since 2.0.0-beta.1
	 *
	 * @var string
	 */
	private $cache_key_css_variables = 'spectra_pro_gs_variables';


	/**
	 * Cached CSS variables for use.
	 * 
	 * @since 2.0.0-beta.1
	 *
	 * @var array
	 */
	private $cached_css_variables = array();

	/**
	 * Initialize the class.
	 *
	 * Hooks into render_block, asset registration, and conditional asset enqueue.
	 *
	 * @since 2.0.0-beta.1
	 * 
	 * @return void
	 */
	public function init() {
		// First manage the cached variables, and apply defaults if needed.
		$this->manage_cached_css_variable_with_defaults();

		// Register the post meta for the current editor's Global Styles classes used.
		add_action( 'init', array( $this, 'register_gs_post_meta' ) );

		// Update the class list in the post meta when the current editor is saved.
		add_action( 'save_post', array( $this, 'accumulate_gs_classes' ) );

		// Localize the editor assets for Global Styles.
		add_action( 'spectra_pro_2_extensions_editor_assets', array( $this, 'localize_editor_assets' ), 10, 3 );

		// Register the required scripts while the Spectra admin scripts are being loaded.
		add_action( 'spectra_admin_prerequisite_scripts', array( $this, 'enqueue_gs_admin_scripts' ) );

		// Add the Global Styles menu item to the Spectra menu.
		add_action( 'spectra_after_menu_register', array( $this, 'add_global_styles_submenu' ) );

		// Add a stylesheet to both front-end and editor with appropriate classes and variables.
		add_action( 'enqueue_block_assets', array( $this, 'generate_gs_stylesheet' ) );

		// Also enqueue on Global Styles admin page.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_gs_stylesheet_on_admin' ) );

		// Filter through the blocks being rendered on the front-end, and add the required classes.
		add_filter( 'render_block', array( $this, 'apply_gs_classes_to_frontend_blocks' ), 10, 2 );

		// Enqueue preview builder assets for iframe preview mode.
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_preview_builder_assets' ) );

		// Disable post saving for preview builder.
		add_action( 'init', array( $this, 'disable_preview_post_saving' ) );

		// Register the required Ajax Actions on the backend.
		add_action( 'wp_ajax_spectra_pro_gs_system_color_variables', array( $this, 'update_system_color_variables' ) );
		add_action( 'wp_ajax_spectra_pro_gs_system_spacing_variables', array( $this, 'update_system_spacing_variables' ) );
		add_action( 'wp_ajax_spectra_pro_gs_system_fontsize_variables', array( $this, 'update_system_fontsize_variables' ) );
		add_action( 'wp_ajax_spectra_pro_gs_user_variables', array( $this, 'update_user_variables' ) );
		add_action( 'wp_ajax_spectra_pro_gs_user_classes', array( $this, 'update_user_classes' ) );
		add_action( 'wp_ajax_spectra_pro_gs_block_defaults', array( $this, 'update_block_defaults' ) );
		add_action( 'wp_ajax_spectra_pro_gs_replace_dynamic_stylesheet', array( $this, 'replace_dynamic_stylesheet' ) );
		add_action( 'wp_ajax_spectra_pro_gs_update_class_in_stylesheet', array( $this, 'update_class_in_stylesheet' ) );
	}

	/**
	 * Function to get the required color or CSS varaible, with fallback.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $variable The CSS variable of the current color.
	 * @param string $hexcode  The hexcode of the current color.
	 * @return string          The CSS variable, with the fallback hex.
	 */
	public function get_color_variable_with_fallback( $variable, $hexcode ) {
		// Clean the CSS variable.
		$cleaned_css_variable = trim( $variable );
		$final_css_variable;

		// Remove the 'var()' wrapper if present.
		if ( 0 === strpos( $cleaned_css_variable, 'var(' ) && ')' === substr( $cleaned_css_variable, -1 ) ) {
			$cleaned_css_variable = substr( $cleaned_css_variable, 4, -1 ); // Strip 'var(' and ')' from the variable string.
		}

		// Validate the newly obtained CSS variable format.
		if ( preg_match( '/^--[\w-]+$/', $cleaned_css_variable ) ) {
			// If it's valid, combine the valid variable and hexcode into a final CSS variable string with fallback.
			$final_css_variable = 'var(' . $cleaned_css_variable . ', ' . $hexcode . ')';
		}

		// Return the variable if all was successful, else just return the hexcode.
		return $final_css_variable ?? $hexcode;
	}

	/**
	 * Merge custom values from WordPress options into base data.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return void
	 */
	private function update_variable_values() {
		// Retrieve the updated values from the user updated option.
		$system_variables = get_option( $this->gs_options['system_variables'] );
		
		// Ensure that the system variables are still an array.
		if ( ! is_array( $system_variables ) ) {
			$system_variables = array();
		}

		$all_defined_variables = array();

		// Run through each color, and update the required CSS variables.
		if ( isset( $system_variables['colors'] ) && is_array( $system_variables['colors'] ) ) {
			foreach ( $system_variables['colors'] as $type => $color ) {
				// Get the color hexcode value and possible variable of the current color.
				$hexcode  = sanitize_hex_color( $color['hexcode'] );
				$variable = ! empty( $color['variable'] ) ? trim( sanitize_text_field( $color['variable'] ) ) : '';

				// Set the color to use as the hexcode or the variable instead with the hexcode as a fallback.
				$final_color = empty( $variable ) ? $hexcode : $this->get_color_variable_with_fallback( $variable, $hexcode );

				$shades = ColorConverter::generate_shades( $hexcode );

				// If the shades are available, loop through them and add them.
				if ( ! empty( $shades ) ) {

					foreach ( $shades as $shade ) {
						// Create the CSS variable name for this shade.
						$variable_name = 'color--' . esc_attr( $type ) . ( 'main' === $shade['label'] ? '' : '-' . esc_attr( $shade['label'] ) );

						// Then create the CSS color for this shade.
						$variable_value = ( 'main' === $shade['label'] ? esc_attr( $final_color ) : esc_attr( sanitize_hex_color( $shade['color'] ) ) );

						// Add this color to the defined variables.
						$all_defined_variables[ $variable_name ] = $variable_value;

						// Get the translucent colors for this color.
						$translucent_colors = ColorConverter::get_translucent_colors( $hexcode );

						// If the translucent colors are available, loop through them and add them.
						if ( ! empty( $translucent_colors ) ) {

							foreach ( $translucent_colors as $opacity => $translucent_color ) {
								// Create the CSS variable name for this color.
								$translucent_color_name = $variable_name . '--' . $opacity;

								// Then create the CSS color with the alpha channel.
								// Since WP does not have an alpha sanitization, we will remove the last 2 alpha characters, sanitize the hex, and add them to it.
								$translucent_color_value = esc_attr( ColorConverter::sanitize_hex_color_alpha( $translucent_color ) );
		
								// Add this color to the defined variables.
								$all_defined_variables[ $translucent_color_name ] = $translucent_color_value;
							}
						}                       
					}//end foreach
				}//end if
			}//end foreach
		}//end if

		// Run through each font-size, and update the required CSS variables.
		if ( isset( $system_variables['fontsize'] ) && is_array( $system_variables['fontsize'] ) ) {
			foreach ( $system_variables['fontsize'] as $type => $data ) {
				// Create the CSS variable name for this font-size.
				$variable_name = in_array( $type, array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ) )
					? 'heading--' . esc_attr( substr( $type, 1 ) )
					: 'text--' . esc_attr( $type );

				// Then create the CSS value for this font-size.
				if ( isset( $data['value'] ) && ! empty( $data['unit'] ) ) {
					$variable_value = $data['value'] . $data['unit'];

					$all_defined_variables[ $variable_name ] = $variable_value;
				}
			}
		}

		// Run through each spacing, and update the required CSS variables.
		if ( isset( $system_variables['spacing'] ) && is_array( $system_variables['spacing'] ) ) {
			foreach ( $system_variables['spacing'] as $type => $data ) {
				// Create the CSS variable name for this spacing.
				$variable_name = 'space--' . esc_attr( $type );

				// Then create the CSS value for this spacing.
				if ( isset( $data['value'] ) && ! empty( $data['unit'] ) ) {
					$variable_value = $data['value'] . $data['unit'];

					$all_defined_variables[ $variable_name ] = $variable_value;
				}
			}
		}

		// Get any custom variables that the user has created.
		$user_css = get_option( $this->gs_options['user_css'] );

		if ( isset( $user_css['variables'] ) && is_array( $user_css['variables'] ) ) {
			// Run through each spacing, and update the required CSS variables.
			foreach ( $user_css['variables'] as $name => $value ) {
				// Create the CSS variable name for this user variable.
				$variable_name = esc_attr( $name );
	
				// Then add the CSS value for this user variable.
				$all_defined_variables[ $variable_name ] = esc_attr( $value );
			}
		}

		// Update the existing cached Global Styles variables with the values from the database.
		if ( ! empty( $all_defined_variables ) ) {
			foreach ( $all_defined_variables as $key => $value ) {
				$this->cached_css_variables[ $key ] = $value;
			}
		}
	}

	/**
	 * Recursively flattens the nested JSON array into a single-level array.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param array $nested_array The multi-dimensional array to flatten.
	 * @param array $flat_array   Reference to the array that will store the flattened result.
	 * @return void
	 */
	private function flatten_json_array_recursively( $nested_array, &$flat_array ) {
		foreach ( $nested_array as $key => $value ) {
			if ( is_array( $value ) ) {
				// If the array contains a CSS key, treat it as a leaf node.
				if ( isset( $value['css'] ) ) {
					$flat_array[ $key ] = $value['css'];
				} else {
					$this->flatten_json_array_recursively( $value, $flat_array );
				}
			} else {
				$flat_array[ $key ] = $value;
			}
		}
	}

	/**
	 * Manage the cached CSS variables, and apply defaults if needed from the updated user options.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return void
	 */
	private function manage_cached_css_variable_with_defaults() {
		$cache_key = $this->cache_key_css_variables;

		// Attempt to retrieve cached CSS variables first.
		$this->cached_css_variables = wp_cache_get( $cache_key );

		// If there isn't a cache, load and process the fresh CSS variables.
		if ( empty( $this->cached_css_variables ) ) {
			$this->cached_css_variables = array();
			// Get path to JSON file for the Global Styles variables.
			$gs_variables_file = SPECTRA_PRO_2_DIR . 'data/gs-variables.json';

			// Read the raw JSON data from the file.
			$raw_data = file_get_contents( $gs_variables_file );

			// If somehow the file does not exist, or the content was not fetched, abandon ship.
			if ( empty( $raw_data ) ) {
				return;
			}

			// Validate and sanitize the JSON data.
			$multi_dimensional_data = json_decode( $raw_data, true );

			// Flatten the array recursively.
			$single_dimensional_data = [];
			$this->flatten_json_array_recursively( $multi_dimensional_data, $single_dimensional_data );

			// Save the cached CSS variables.
			$this->cached_css_variables = $single_dimensional_data;

			// Merge with custom values from WordPress options.
			$this->update_variable_values();

			// Cache processed data.
			wp_cache_set( $cache_key, $this->cached_css_variables );
		}//end if
	}

	/**
	 * Get the theme colors of the current theme.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param \WP_Theme|null $current_theme The current theme object, or null if it was not passed.
	 * @return array                        The current theme's colors.
	 */
	private function get_theme_colors( $current_theme = null ) {

		// If the current theme was previously used, then use it directly, else fetch it.
		if ( ! ( $current_theme instanceof WP_Theme ) ) {
			$current_theme = wp_get_theme();
		}

		// Create a resolver, and use it to get the theme data.
		$resolver   = new \WP_Theme_JSON_Resolver();
		$theme_data = $resolver->get_theme_data( [], [ 'with_supports' => true ] );

		// Get the settings from the theme data, and identify the color palette.
		$settings      = $theme_data->get_settings();
		$theme_palette = $settings['color']['palette']['theme'] ?? array();

		// Create a variable for the current theme colors.
		$current_theme_colors = array();

		// For astra, we handle the defaults separately.
		if (
			'astra' === $current_theme->get_template()
			&& class_exists( '\Astra_Global_Palette' )
			&& method_exists( '\Astra_Global_Palette', 'get_palette_labels' )
			&& method_exists( '\Astra_Global_Palette', 'get_palette_slugs' )
			&& method_exists( '\Astra_Global_Palette', 'get_color_by_palette_variable' )
		) {
			// Create an instance of the Astra Global Palette.
			$astra_global_palette_instance = new \Astra_Global_Palette();
			
			// Get the palette labels and slugs.
			$theme_color_labels = $astra_global_palette_instance->get_palette_labels();
			$theme_color_slugs  = $astra_global_palette_instance->get_palette_slugs();

			// Wrap the theme slugs in the CSS variable format.
			$theme_color_slugs = array_map(
				function( $theme_color_slug ) {
					return 'var(--' . $theme_color_slug . ')';
				},
				$theme_color_slugs 
			);

			// Set the current theme colors for Astra.
			$current_theme_colors = array(
				array(
					'label'    => $theme_color_labels[0],
					'variable' => $theme_color_slugs[0],
					'value'    => $astra_global_palette_instance->get_color_by_palette_variable( $theme_color_slugs[0] ),
					'default'  => 'primary',
				),
				array(
					'label'    => $theme_color_labels[1],
					'variable' => $theme_color_slugs[1],
					'value'    => $astra_global_palette_instance->get_color_by_palette_variable( $theme_color_slugs[1] ),
				),
				array(
					'label'    => $theme_color_labels[2],
					'variable' => $theme_color_slugs[2],
					'value'    => $astra_global_palette_instance->get_color_by_palette_variable( $theme_color_slugs[2] ),
					'default'  => 'base',
				),
				array(
					'label'    => $theme_color_labels[3],
					'variable' => $theme_color_slugs[3],
					'value'    => $astra_global_palette_instance->get_color_by_palette_variable( $theme_color_slugs[3] ),
				),
				array(
					'label'    => $theme_color_labels[4],
					'variable' => $theme_color_slugs[4],
					'value'    => $astra_global_palette_instance->get_color_by_palette_variable( $theme_color_slugs[4] ),
				),
				array(
					'label'    => $theme_color_labels[5],
					'variable' => $theme_color_slugs[5],
					'value'    => $astra_global_palette_instance->get_color_by_palette_variable( $theme_color_slugs[5] ),
				),
				array(
					'label'    => $theme_color_labels[6],
					'variable' => $theme_color_slugs[6],
					'value'    => $astra_global_palette_instance->get_color_by_palette_variable( $theme_color_slugs[6] ),
					'default'  => 'secondary',
				),
				array(
					'label'    => $theme_color_labels[7],
					'variable' => $theme_color_slugs[7],
					'value'    => $astra_global_palette_instance->get_color_by_palette_variable( $theme_color_slugs[7] ),
				),
				array(
					'label'    => $theme_color_labels[8],
					'variable' => $theme_color_slugs[8],
					'value'    => $astra_global_palette_instance->get_color_by_palette_variable( $theme_color_slugs[8] ),
				),
			);
		} else {
			// Create an array to store the formatted theme colors.
			$formatted_theme_colors = array();
	
			// Loop through each color in the palette.
			foreach ( $theme_palette as $index => $palette_color ) {
				// Get the color's label and value.
				$fetched_color = array(
					'label' => $palette_color['name'] ?? '',
					'value' => $palette_color['color'] ?? '',
				);
				// Based on the general color index in the palette, use it as one of the required colors by default.
				switch ( $index ) {
					case 0:
						$fetched_color = array_merge( $fetched_color, array( 'default' => 'base' ) );
						break;
					case 2:
						$fetched_color = array_merge( $fetched_color, array( 'default' => 'primary' ) );
						break;
					case 3:
						$fetched_color = array_merge( $fetched_color, array( 'default' => 'secondary' ) );
						break;
				}
				// Push this color to the formatted theme colors.
				array_push( $formatted_theme_colors, $fetched_color );          
			}//end foreach

			// For most themes, we will use the previously formatted colors. The filter allows developers to change the defaults of the formatted theme colors.
			$current_theme_colors = apply_filters( 'spectra_pro_gs_theme_colors', $formatted_theme_colors );
			if ( ! is_array( $current_theme_colors ) ) {
				$current_theme_colors = array();
			}
		}//end if

		// Return the current theme colors.
		return $current_theme_colors;
	}

	/**
	 * AJAX helper function to update the numeric value and unit of a system variable.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $variable_type  The type of system variable being updated.
	 * @param array  $messages       An array of messages to be used when sending the AJAX responses.
	 * @return void
	 */
	private function update_system_numeric_unit_variable( $variable_type, $messages ) {
		// Run a security check.
		check_ajax_referer( 'spectra_gs_ajax_nonce', 'security' );

		// Check if this is a destructive request.
		$is_destructive = ! empty( $_POST['is_destructive'] ) && 'yes' === $_POST['is_destructive'];

		// Send an error if any of the required ddata is not posted.
		if ( empty( $_POST['type'] || empty( $_POST['value'] ) || empty( $_POST['unit'] ) ) ) {
			wp_send_json_error(
				array(
					'title'       => $is_destructive ? __( 'Could not reset.', 'spectra-pro' ) : $messages['error'],
					'description' => __( 'Could not read the value or unit.', 'spectra-pro' ),
				) 
			);
		}

		// Sanitize the required post data.
		$type  = sanitize_key( $_POST['type'] );
		$value = sanitize_text_field( $_POST['value'] );
		$unit  = sanitize_text_field( $_POST['unit'] );

		// Get the system variables.
		$system_variables = get_option( $this->gs_options['system_variables'], $this->default_gs_system_variables );

		// If the numeric unit variables of this type do not exist, create them.
		if ( empty( $system_variables[ $variable_type ] ) ) {
			$system_variables[ $variable_type ] = array();
		}

		// If this is destructive, unset the value from the array and proceed with the response.
		if ( $is_destructive && ! empty( $system_variables[ $variable_type ][ $type ] ) ) {
			unset( $system_variables[ $variable_type ][ $type ] );
		} else {
			// Update the required numeric value and unit.
			$system_variables[ $variable_type ][ $type ]['value'] = $value;
			$system_variables[ $variable_type ][ $type ]['unit']  = $unit;
		}

		// Update the system variables.
		$success = update_option( $this->gs_options['system_variables'], $system_variables );

		// If successful, first update the cache with the latest option, then send the success. Else send the error.
		if ( $success ) {
			$this->update_cached_variables();
			wp_send_json_success(
				array(
					'title'          => $is_destructive ? __( 'Reset To Default!', 'spectra-pro' ) : $messages['success'],
					'description'    => __( 'Values updated in the database.', 'spectra-pro' ),
					'saved_type'     => $type,
					'saved_variable' => $system_variables[ $variable_type ][ $type ] ?? array(),
					'destroyed'      => $is_destructive,
				) 
			);
		} else {
			wp_send_json_error(
				array(
					'title'       => $is_destructive ? __( 'Reset Not Successful.', 'spectra-pro' ) : $messages['error'],
					'description' => __( 'Could not update in the database.', 'spectra-pro' ),
				) 
			);
		}
	}

	/**
	 * Process the custom user classes.
	 *
	 * This function will return an array of classes.
	 * The keys will be the class name, with the psuedo-selector if any.
	 * The values will be all the CSS of this class in the string format.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param boolean $using_shortcode Determines whether the pseudo-selectors should be saved as shortcodes.
	 * @return array                   An array of all the user classes, or an empty array.
	 */
	private function process_user_custom_classes( $using_shortcode = false ) {
		// Get the user CSS.
		$user_css = get_option( $this->gs_options['user_css'] );
		// If the user hasn't created any classes, abandon ship.
		if ( empty( $user_css['classes'] ) || ! is_array( $user_css['classes'] ) ) {
			return array();
		}

		// Create an array to store all processed user classes.
		$processed_classes = array();
		// Loop through each custom class.
		foreach ( $user_css['classes'] as $user_class => $types ) {
			// Loop through each type of this user class.
			if ( ! empty( $types ) && is_array( $types ) ) {
				foreach ( $types as $type => $type_styles ) {
					// Convert the style array of this class into a single style string.
					$style_string = Core::concatenate_array( $type_styles, 'style' );
					switch ( $type ) {
						case 'hover':
						case 'active':
						case 'focus-visible':
						case 'focus-within':
						case 'disabled':
						case 'checked':
						case 'visited':
						case 'first-child':
						case 'last-child':
						case 'only-child':
							// If this is a pseudo-selector, format the selector.
							$class_with_pseudo_selector = $user_class . ( $using_shortcode ? '[' . $type . ']' : ':' . $type );
							// Add the CSS for this custom class.
							$processed_classes[ $class_with_pseudo_selector ] = $style_string;
							break;
						default:
							// Add the CSS for this custom class.
							$processed_classes[ $user_class ] = $style_string;
					}
				}//end foreach
			}//end if
		}//end foreach

		return $processed_classes;
	}

	/**
	 * Get all JSON files in the gs-classes directory and its subdirectories.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $pattern    The pattern to match JSON files.
	 * @return Generator<string> A generator that yields the paths of JSON files.
	 */
	private function recursively_get_json_files( $pattern ) {
		yield from glob( $pattern );
		foreach ( glob( dirname( $pattern ) . '/*', GLOB_ONLYDIR ) as $dir ) {
			yield from $this->recursively_get_json_files( "$dir/" . basename( $pattern ) );
		}
	}

	/**
	 * Load JSON data from the gs-classes directory and its subdirectories.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return array An array of JSON data.
	 */
	private function load_json_data() {
		$data = [];
		// Get the base directory for Global Styles classes.
		$gs_classes_dir = SPECTRA_PRO_2_DIR . 'data/gs-classes/';

		// Read and merge class definitions from all JSON files.
		foreach ( $this->recursively_get_json_files( $gs_classes_dir . '**/*.json' ) as $file ) {
			$default_category = basename( dirname( $file ) );
			$content          = json_decode( file_get_contents( $file ), true );

			foreach ( $content as $classname => $data_item ) {
				if ( is_array( $data ) ) {
					$data[] = [
						'name'        => $this->get_cleaned_class( $classname ),
						'css'         => $data_item['css'] ?? '',
						'title'       => $data_item['title'] ?? '',
						'description' => $data_item['description'] ?? '',
						'category'    => $data_item['category'] ?? $default_category,
						'tags'        => isset( $data_item['tags'] ) && is_array( $data_item['tags'] ) ? $data_item['tags'] : array(),
					];
				}
			}
		}//end foreach

		// Also parse gs-variables.json and add its entries (supports nested objects).
		$gs_variables_file = SPECTRA_PRO_2_DIR . 'data/gs-variables.json';
		if ( file_exists( $gs_variables_file ) ) {
			$vars_json = json_decode( file_get_contents( $gs_variables_file ), true );

			$add_vars = function( $arr ) use ( &$add_vars, &$data ) {
				foreach ( $arr as $variable_name => $data_item ) {
					if ( is_array( $data_item ) && isset( $data_item['category'] ) && 'variables' === $data_item['category'] ) {
						$data[] = [
							'name'        => $variable_name,
							'css'         => $data_item['css'] ?? '',
							'title'       => $data_item['title'] ?? '',
							'description' => $data_item['description'] ?? '',
							'category'    => $data_item['category'],
							'tags'        => ( isset( $data_item['tags'] ) && is_array( $data_item['tags'] ) ) ? $data_item['tags'] : array(),
						];
					} elseif ( is_array( $data_item ) ) {
						$add_vars( $data_item );
					}
				}
			};
			$add_vars( $vars_json );
		}//end if
		return $data;
	}

	/**
	 * Helper function to check if an array is multidimensional.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param array $array The array to check.
	 * @return boolean     True if the array is multidimensional, false otherwise.
	 */
	private function is_multidimensional_array( $array ) {
		if ( ! is_array( $array ) ) {
			return false;
		}
		foreach ( $array as $element ) {
			if ( is_array( $element ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Function to update the cached variables when required.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return void
	 */
	public function update_cached_variables() {
		$cache_key = $this->cache_key_css_variables;

		// Attempt to retrieve cached CSS variables first.
		$this->cached_css_variables = wp_cache_get( $cache_key );
		
		// In edge cases, if there isn't a cache, update it with the JSON of default CSS Variable KV Pairs.
		if ( empty( $this->cached_css_variables ) ) {
			$this->cached_css_variables = array();
		}

		// Merge with custom values from WordPress options.
		$this->update_variable_values();
		
		// Cache the processed data.
		wp_cache_set( $cache_key, $this->cached_css_variables );
	}

	/**
	 * Function to get verified theme colors, by cross-checking them with the latest theme colors.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param \WP_Theme|null $current_theme The current theme object, or null if it was not passed.
	 * @param boolean        $get_option    Determines whether to return the option, or just the latest colors.
	 * @return array                        The verified theme colors, or the current theme's latest colors.
	 */
	public function get_verified_theme_colors( $current_theme = null, $get_option = true ) {
		// Get the option from the database, with the defaults for those that are not set.
		$system_variables = get_option( $this->gs_options['system_variables'], $this->default_gs_system_variables );

		// If there are no colors, just return the theme colors.
		if ( empty( $system_variables['colors'] ) || ! is_array( $system_variables['colors'] ) ) {
			return $this->get_theme_colors( $current_theme );
		}

		// By default, we will assume that the colors are indeed up-to-date.
		$is_verified = true;

		// Get the latest theme colors.
		$latest_theme_colors = $this->get_theme_colors( $current_theme );

		// Run through each color, and verify the required values.
		foreach ( $system_variables['colors'] as $type => $color ) {
			// Get the color hexcode value and possible variable of the current color.
			$saved_hexcode = sanitize_hex_color( $color['hexcode'] );
			$theme_label   = ! empty( $color['theme_label'] ) ? trim( sanitize_text_field( $color['theme_label'] ) ) : '';

			// Get the currently set value for this theme, and find the color that matches.
			$current_latest_color = array_values(
				array_filter(
					$latest_theme_colors,
					function( $theme_color ) use ( $theme_label ) {
						return array_key_exists( 'label', $theme_color ) && trim( sanitize_text_field( $theme_color['label'] ) === $theme_label );
					} 
				)
			);

			// If there's a match for this color, and the default hex color is outdated, then update the default hex color.
			// Else if there's no match, update the system colors to match the current theme.
			if (
				! empty( $current_latest_color[0] )
				&& is_array( $current_latest_color[0] )
				&& array_key_exists( 'value', $current_latest_color[0] )
			) {
				$latest_value = $current_latest_color[0]['value'];
				
				// Check if the latest hexcode is up-to-date for this theme color.
				if ( strtolower( $latest_value ) !== strtolower( $saved_hexcode ) ) {
					// In this case, the colors of at least 1 color here is not up-to-date.
					$is_verified = false;
					// Update the mismatching hexcode.
					$system_variables['colors'][ $type ]['hexcode'] = sanitize_hex_color( $latest_value );
				}
			} elseif ( ! empty( $theme_label ) ) {
				// In this case, the colors of at least 1 color here is not up-to-date.
				$is_verified = false;
				
				$required_theme_color;
				// If there's no match, but the theme label does exist - that means that the theme has changed.
				foreach ( $latest_theme_colors as $theme_color ) {
					if ( isset( $theme_color['default'] ) && $type === $theme_color['default'] ) {
						$required_theme_color = $theme_color;
					}
				}

				// Update the hexcode of this color.
				if ( ! empty( $required_theme_color['value'] ) && is_string( $required_theme_color['value'] ) ) {
					$system_variables['colors'][ $type ]['hexcode'] = sanitize_hex_color( $required_theme_color['value'] );
				}
				// Update the label of this color.
				if ( ! empty( $required_theme_color['label'] ) && is_string( $required_theme_color['label'] ) ) {
					$system_variables['colors'][ $type ]['theme_label'] = sanitize_text_field( $required_theme_color['label'] );
				}
				// Add or remove the variable of this color, based on whether it exists.
				if ( ! empty( $required_theme_color['variable'] ) && is_string( $required_theme_color['variable'] ) ) {
					$system_variables['colors'][ $type ]['variable'] = sanitize_text_field( $required_theme_color['variable'] );
				} elseif ( ! empty( $system_variables['colors'][ $type ]['variable'] ) ) {
					unset( $system_variables['colors'][ $type ]['variable'] );
				}
			}//end if
		}//end foreach

		// If at least 1 color was not up-to-date with the theme's latest color configuration, update the Global Styles colors.
		if ( ! $is_verified ) {
			update_option( $this->gs_options['system_variables'], $system_variables );
			$this->update_variable_values();
		}

		// Return the verified colors.
		return $get_option ? $system_variables['colors'] : $latest_theme_colors;
	}

	/**
	 * Function to recursively sanitize the JSON data.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param array $json_data The JSON data part.
	 * @return array           The sanitized data.
	 */
	public function recursively_sanitize_json( $json_data ) {
		// Set an array for the sanitized decoded JSON.
		$sanitized = [];

		// Loop through each item, sanitizing the values.
		foreach ( $json_data as $key => $value ) {
			// Sanitize keys.
			$clean_key = sanitize_text_field( $key );
			
			// Handle different value types.
			if ( is_array( $value ) ) {
				// Recursively sanitize nested arrays.
				$sanitized[ $clean_key ] = $this->recursively_sanitize_json( $value );
			} elseif ( is_object( $value ) ) {
				// Convert objects to arrays first, then recursively sanitize it.
				$sanitized[ $clean_key ] = $this->recursively_sanitize_json( (array) $value );
			} else {
				// Sanitize any scalar value.
				$sanitized[ $clean_key ] = sanitize_text_field( $value );
			}
		}

		// Return the sanitized array.
		return $sanitized;
	}

	/**
	 * Sanitize a given post JSON as per WordPress standards.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $input The JSON string.
	 * @return array        The decoded JSON array, with all items sanitized - or an empty array if an error was encountered.
	 */
	public function sanitize_json( $input ) {
		// Strip the slashes from the input, and decode it.
		$cleaned = wp_unslash( $input );
		$decoded = json_decode( $cleaned, true );
		
		// If it was not properly decoded, abandon ship.
		if ( null === $decoded || ! is_array( $decoded ) ) {
			return array();
		}

		// Return the recursively sanitized JSON array.
		return $this->recursively_sanitize_json( $decoded );
	}

	/**
	 * Register the post meta for Global Styles.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return void
	 */
	public function register_gs_post_meta() {

		$post_types = get_post_types( [ 'show_in_rest' => true ] );
		
		foreach ( $post_types as $post_type ) {
			register_post_meta(
				$post_type,
				'spectra_gs_classes',
				[
					'show_in_rest'      => true,
					'single'            => true,
					'type'              => 'string',
					'sanitize_callback' => function( $value ) {
						return wp_json_encode( array_unique( explode( ' ', $value ) ) );
					},
				]
			);
		}
	}

	/**
	 * Gets all the used global style classes, and collects them into a single post meta.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param int $post_id The current post ID.
	 * @return void
	 */
	public function accumulate_gs_classes( $post_id ) {
		// Get all blocks in the post.
		$blocks = parse_blocks( get_post( $post_id )->post_content );

		$used_classes = array();
		// Helper function to recursively process blocks.
		$process_block_recursive = function( $block ) use ( &$used_classes, &$process_block_recursive ) {
			// Process current block's classes.
			if ( isset( $block['attrs']['spectraGSClasses'] ) ) {
				$used_classes = array_merge(
					$used_classes,
					$block['attrs']['spectraGSClasses'],
				);
			}
			
			// Process inner blocks.
			if ( isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				foreach ( $block['innerBlocks'] as $inner_block ) {
					$process_block_recursive( $inner_block );
				}
			}
		};
		
		// Process all top-level blocks.
		foreach ( $blocks as $block ) {
			$process_block_recursive( $block );
		}
		
		// Remove duplicates and sanitize.
		$unique_classes = array_unique( array_filter( $used_classes ) );
		
		// Update post meta.
		update_post_meta(
			$post_id,
			'spectra_gs_classes',
			implode( ' ', $unique_classes )
		);
	}

	/**
	 * Generate the stylesheet required in the editor.
	 *
	 * @since 2.0.0-beta.1
	 * 
	 * @param string $handle      The asset handle.
	 * @param string $folder_name The folder of this current asset.
	 * @param array  $asset_file  The array of the asset details.
	 * @return void
	 */
	public function localize_editor_assets( $handle, $folder_name, $asset_file ) {
		// If the handle does not contain the word 'global-styles', abandon ship.
		if ( false === strpos( $handle, 'global-styles' ) ) {
			return;
		}
		
		// Create an empty array to localize.
		$localize = array();

		// Get the user CSS option.
		$user_css = get_option( $this->gs_options['user_css'] );

		// Add the user classes if needed.
		if ( ! empty( $user_css['classes'] ) ) {
			$localize['user_classes'] = $user_css['classes'];
		}

		// Add the block defaults for editor use.
		$localize['block_defaults'] = get_option( $this->gs_options['block_defaults'], array() );

		// Localize the scripts.
		wp_localize_script( $handle, 'spectra_editor_gs', $localize );
	}

	/**
	 * Enqueue the Global Styles's Admin Scripts.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return void
	 */
	public function enqueue_gs_admin_scripts() {
		// Enqueue the admin script for the Global Styles.
		$handle            = 'spectra-pro-admin-gs';
		$build_path        = SPECTRA_PRO_2_DIR . 'build/admin/global-styles/';
		$build_url         = SPECTRA_PRO_2_URL . 'build/admin/global-styles/';
		$script_asset_path = $build_path . 'index.asset.php';
		$script_info       = file_exists( $script_asset_path )
			? include $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => SPECTRA_PRO_VER,
			);
		$script_dep        = array_merge( $script_info['dependencies'], array( 'updates' ) );

		// Register the Global Styles scripts.
		wp_register_script(
			$handle,
			$build_url . 'index.js',
			$script_dep,
			$script_info['version'],
			true
		);

		// Register the Global Styles styles.
		wp_register_style(
			$handle,
			$build_url . 'index.css',
			array(),
			SPECTRA_PRO_VER
		);

		// Enqueue the script.
		wp_enqueue_script( $handle );

		// If the styles need to be loaded on this admin page, enqueue them.
		if ( ! empty( $_GET['page'] ) && ( array_key_exists( 'page', $_GET ) && 'spectra' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page check, no user input processed
			wp_enqueue_style( $handle );
			
			// Enqueue block editor and block library dependencies for block preview functionality.
			wp_enqueue_style( 'wp-block-editor' );
			wp_enqueue_style( 'wp-components' );
			wp_enqueue_style( 'wp-block-library' );
			
			// Enqueue block editor scripts.
			wp_enqueue_script( 'wp-format-library' );
			wp_enqueue_script( 'wp-editor' );
		}

		// Add the RTL styles.
		wp_style_add_data( $handle, 'rtl', 'replace' );

		// Enqueue the Code Mirror editor.
		wp_enqueue_code_editor(
			array(
				'type'       => 'text/css',
				'codemirror' => array( 'placeholder' => "--color-wordpress: #007cba;\n--radius-custom: 1rem;" ),
			) 
		);

		// Enqueue the Code Mirror styles.
		wp_enqueue_style( 'wp-codemirror' );

		// Get the current theme.
		$current_theme = wp_get_theme();

		// Get the verified theme colors.
		$verified_theme_colors = $this->get_verified_theme_colors( $current_theme, false );

		// Create a variable to localize data for React.
		$localize = array(
			'ajax_url'         => admin_url( 'admin-ajax.php' ),
			'ajax_nonce'       => wp_create_nonce( 'spectra_gs_ajax_nonce' ),
			'system_variables' => get_option( $this->gs_options['system_variables'], $this->default_gs_system_variables ),
			'system_defaults'  => $this->default_gs_system_variables,
			'user_css'         => get_option( $this->gs_options['user_css'] ),
			'block_defaults'   => get_option( $this->gs_options['block_defaults'], array() ),
			'current_theme'    => array(
				'name'   => esc_html( $current_theme->get( 'Name' ) ),
				'colors' => $verified_theme_colors,
			),
			'all_variables'    => wp_json_encode( $this->cached_css_variables ),
			'json_data'        => $this->load_json_data(),
			'preview_url'      => admin_url( 'post-new.php?spectra-gs-preview-builder=true' ),
		);

		// If the Pro URL is defined, push it for localization.
		if ( defined( 'SPECTRA_PRO_URL' ) ) {
			$localize['pro_plugin_url'] = SPECTRA_PRO_URL;
		}

		// Localize the scripts.
		wp_localize_script( $handle, 'spectra_admin_gs', $localize );
		
		// Add block editor settings for admin context.
		if ( ! empty( $_GET['page'] ) && 'spectra' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page check, no user input processed
			// Initialize block editor settings similar to post editor.
			wp_add_inline_script(
				'wp-blocks',
				'window.wp = window.wp || {}; window.wp.blocks = window.wp.blocks || {};',
				'before'
			);
			
			// Ensure block settings are available.
			$block_editor_context = new \WP_Block_Editor_Context( array( 'name' => 'core/edit-site' ) );
			$settings             = get_block_editor_settings( array(), $block_editor_context );
			
			wp_add_inline_script(
				$handle,
				sprintf(
					'window._wpBlockEditorSettings = %s;',
					wp_json_encode( $settings )
				),
				'before'
			);
		}//end if
	}

	/**
	 * Strips out all shortcodes from the given class.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $class_name The class name to clean.
	 * @return string            The class name without any shortcodes.
	 */
	public function get_cleaned_class( $class_name ) {
		return preg_replace( '/\[.*?\]/', '', $class_name );
	}

	/**
	 * Parse any shortcodes in the given classname.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $class_name        The classname to parse.
	 * @param string $style_declaration The stylesheet for this class.
	 * @param bool   $is_editor         Whether we're in the editor context.
	 * @return array<string,string>     An array containing the selectors, and the styles associated with them.
	 */
	public function process_class_shortcodes( $class_name, $style_declaration, $is_editor = false ) {
		// If there's no shortcode to parse, return the current classname as it is.
		if ( false === strpos( $class_name, '[' ) ) {
			return array(
				$class_name => $style_declaration,
			);
		}
		// Get the cleaned class name.
		$cleaned_class = $this->get_cleaned_class( $class_name );

		// Set the array to be returned.
		$processed_classes = array();

		// If the [innerblocks] shortcode is found, process it.
		// This shortcode is used to apply styles to inner blocks in the editor.
		if ( false !== strpos( $class_name, '[innerblocks]' ) ) {
			if ( $is_editor ) {
				// In the editor, apply styles to the block (if it's not a wrapper) or the inner blocks container.
				$non_wrapper_selector                        = $cleaned_class . ':not(:has(.block-editor-inner-blocks))';
				$inner_blocks_selector                       = $cleaned_class . ' > .block-editor-inner-blocks > .block-editor-block-list__layout';
				$processed_classes[ $non_wrapper_selector ]  = $style_declaration;
				$processed_classes[ $inner_blocks_selector ] = $style_declaration;
			} else {
				// In the frontend, apply styles to the element itself.
				$processed_classes[ $cleaned_class ] = $style_declaration;
			}
		}

		// If the [before] shortcode is found, process it as a pseudo-element (two colons).
		// Note that for a before shortcode, the styles associated with it shoud go to the before pseudo-element.
		// This shortcode is used to apply the styles to the before pseudo-element instead of the actual element.
		if ( false !== strpos( $class_name, '[before]' ) ) {
			// Add the selectors for the before pseudo-element and the direct descendants other than the video block.
			$before_selector   = $cleaned_class . '::before';
			$children_selector = $cleaned_class . ' > *:not(.spectra-background-video__wrapper)';
			$video_background  = $cleaned_class . ' > .spectra-background-video__wrapper';

			// Add the styles for this class to the before pseudo-element.
			$processed_classes[ $before_selector ] = $style_declaration;

			// Make all direct descendants relative to avoid appearing under the overlay.
			$processed_classes[ $children_selector ] = 'position: relative;';

			// Make the video background as -1, since the block that has an overlay is set to 0 when the class is added.
			$processed_classes[ $video_background ] = 'z-index: -1;';
		}

		// Parse the [element~*] shortcode.
		preg_match( '/\[element~(.*?)\]/', $class_name, $matches );

		// If the [element~*] shortcode is found, process it.
		// Note that the element shortcode will have 'K:V' pairs separated by hyphens that should be applied to the element.
		// This shortcode is used along with a pseudo-element or pseudo-selector shortcode when you want to apply certain styles to the element as well.
		if ( ! empty( $matches ) && isset( $matches[1] ) ) {
			// Split the element CSS KV pairs.
			$pairs = explode( '~', $matches[1] );

			// Format each pair into the CSS format of "key: value;".
			$element_styles = '';
			foreach ( $pairs as $pair ) {
				if ( ! empty( $pair ) ) {
					list( $key, $value ) = explode( ':', $pair );
					$element_styles     .= $key . ': ' . $value . '; ';
				}
			}

			// Add the styles for this element.
			$processed_classes[ $cleaned_class ] = trim( $element_styles );
		}

		// If any of the pseudo-selector shortcodes is found (one colon), process them.
		$valid_pseudo_elements = array(
			'hover',
			'active',
			'focus-visible',
			'focus-within',
			'disabled',
			'checked',
			'visited',
			'first-child',
			'last-child',
			'only-child',
		);

		// Create a pattern to match the above pseudo-elements.
		$pseudo_element_pattern = '/\[(' . implode( '|', array_map( 'preg_quote', $valid_pseudo_elements ) ) . ')\]/';

		// Get all matches of the current pattern.
		preg_match( $pseudo_element_pattern, $class_name, $pseudo_element_matches );
		if ( ! empty( $pseudo_element_matches ) && isset( $pseudo_element_matches[1] ) ) {
			// Create the selector with this pseudo-element.
			$pseudo_selector_class = $cleaned_class . ':' . $pseudo_element_matches[1];
			// Add the styles for this element.
			$processed_classes[ $pseudo_selector_class ] = $style_declaration;
		}

		// Return the processed classes.
		return $processed_classes;
	}

	/**
	 * Add the Global Styles Submenu to the Spectra Menu.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $menu_slug The menu slug.
	 * @return void
	 */
	public function add_global_styles_submenu( $menu_slug ) {
		add_submenu_page(
			$menu_slug,
			__( 'Global Styles', 'spectra-pro' ),
			__( 'Global Styles', 'spectra-pro' ),
			'manage_options',
			'admin.php?page=' . $menu_slug . '&path=global-styles',
		);
	}

	/**
	 * Generate the dynamic stylesheet for this post with only the required variables and classes.
	 *
	 * This function checks the post meta for the Global Styles data.
	 * If it exists, the required classes are fetched for generation.
	 * Then the required root variables from the cache are fetched.
	 * Finally the required stylesheet is enqueued and loaded.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return void
	 */
	public function generate_gs_stylesheet() {
		// Check if we're in the editor/admin context.
		$is_editor = is_admin();

		// Ensure theme colors are up-to-date before generating the stylesheet.
		// This will verify and update the color variables if the theme has changed.
		$this->get_verified_theme_colors();

		// For frontend, we need to get the used classes.
		if ( ! $is_editor ) {
			global $post;

			// If this ain't a post, abandon ship.
			if ( ! $post || ! isset( $post->ID ) ) {
				return;
			}
			
			// Get stored classes for this post.
			$used_classes = get_post_meta( $post->ID, 'spectra_gs_classes', true );

			// If the post meta we need for the Global styles isn't available, abandon ship.
			if ( empty( $used_classes ) || ! is_string( $used_classes ) ) {
				return;
			}

			// Decode the post meta.
			$used_classes = json_decode( $used_classes, false );
		} else {
			// In editor, we want to load all classes.
			$used_classes = array(); // Empty array means all classes will be loaded.
		}//end if

		// Initialize array to store all class definitions.
		$single_dimensional_data = array();

		// Get the base directory for Global Styles classes.
		$gs_classes_dir = SPECTRA_PRO_2_DIR . 'data/gs-classes/';

		// Read and merge class definitions from all JSON files.
		foreach ( $this->recursively_get_json_files( $gs_classes_dir . '**/*.json' ) as $json_file ) {
			// Read the raw JSON data from the file.
			$raw_data = file_get_contents( $json_file );

			// Skip if file read failed.
			if ( false === $raw_data ) {
				continue;
			}

			// Decode JSON data.
			$file_data = json_decode( $raw_data, true );

			// Skip if JSON decode failed.
			if ( null === $file_data ) {
				continue;
			}

			// If the data is already flat (like in fontsizes.json), merge directly.
			if ( ! $this->is_multidimensional_array( $file_data ) ) {
				$single_dimensional_data = array_merge( $single_dimensional_data, $file_data );
			} else {
				// For nested data, flatten it first.
				$this->flatten_json_array_recursively( $file_data, $single_dimensional_data );
			}
		}//end foreach

		// In editor mode, use all classes. In frontend, filter unused ones.
		$active_system_classes = $is_editor ? $single_dimensional_data : array_filter(
			$single_dimensional_data,
			function( $key ) use ( $used_classes ) {
				$cleaned_class = $this->get_cleaned_class( $key );
				return in_array( $cleaned_class, $used_classes );
			},
			ARRAY_FILTER_USE_KEY 
		);

		// Set an array for the active user classes.
		$active_user_classes = array();

		// Get the processed user classes, with shortcodes for pseudo-elements.
		$user_classes = $this->process_user_custom_classes( true );

		// In editor mode, use all user classes. In frontend, filter for used ones.
		if ( ! empty( $user_classes ) ) {
			$active_user_classes = $is_editor ? $user_classes : array_filter(
				$user_classes,
				function( $key ) use ( $used_classes ) {
					$current_class = $this->get_cleaned_class( $key );
					return in_array( $current_class, $used_classes );
				},
				ARRAY_FILTER_USE_KEY 
			);
		}

		// Check if block defaults exist to determine if stylesheet should be generated.
		$block_defaults     = get_option( $this->gs_options['block_defaults'], array() );
		$has_block_defaults = ! empty( $block_defaults );
		
		// If there aren't any active classes, no user CSS, and no block defaults in frontend mode, abandon ship.
		if ( ! $is_editor && empty( $active_system_classes ) && empty( $active_user_classes ) && ! $has_block_defaults ) {
			return;
		}

		// Add all user classes and system classes into a single array for style generation.
		$active_classes = array_merge( $active_user_classes, $active_system_classes );

		// On front-end, ensure block default classes are included even if not explicitly used.
		if ( ! $is_editor && $has_block_defaults ) {
			foreach ( $block_defaults as $block_config ) {
				// Handle pseudo-selector format.
				if ( is_array( $block_config ) ) {
					foreach ( $block_config as $pseudo_classes ) {
						if ( is_array( $pseudo_classes ) ) {
							foreach ( $pseudo_classes as $class_name ) {
								// Add block default classes from system classes if they exist.
								if ( isset( $single_dimensional_data[ $class_name ] ) && ! isset( $active_classes[ $class_name ] ) ) {
									$active_classes[ $class_name ] = $single_dimensional_data[ $class_name ];
								}
							}
						}
					}
				}
			}
		}

		// Generate CSS rules and the root rule for the variables.
		$css_rules      = '';
		$root_variables = array();

		// Create an array to use based on the cached variables.
		$saved_variables = array();

		// If the cached variables is available, use them.
		// This is just a precautionary fallback check. The variables at this point should always exist.
		if ( ! empty( $this->cached_css_variables ) && is_array( $this->cached_css_variables ) ) {
			$saved_variables = $this->cached_css_variables;
		}

		// Loop through each avtive class, and generate the required CSS for it.
		foreach ( $active_classes as $class_name => $style_declaration ) {
			// Create an array of classes related to this class name.
			$current_classes = array();

			// Parse and remove any shortcodes that might exist in the class name from the JSON.
			$current_classes = $this->process_class_shortcodes( $class_name, $style_declaration, $is_editor );

			foreach ( $current_classes as $current_selector => $current_styles ) {
				// Add the required class name to the stylesheet.
				$selector = '.' . $current_selector;

				// Minify the styles string by removing any whitespace after colons or semicolons.
				$current_styles = preg_replace( '/([\:\;])\s*/', '$1', $current_styles );

				// Add the required rules for this class.
				$css_rules .= ' [class*="wp-block"]' . $selector . '{' . $current_styles . '}';

				// If saved variables exist, add the required variables from this style to the root.
				if ( ! empty( $saved_variables ) ) {
					// Use regex to match CSS variables.
					$variable_pattern = '/(?:var\(--){1}([a-zA-Z][a-zA-Z0-9-]+)\b/';

					// Find all matches.
					preg_match_all( $variable_pattern, $current_styles, $matches );

					// Add these matches to the root variables.
					$root_variables = array_merge( $root_variables, $matches[1] );
				}
			}//end foreach
		}//end foreach

		// Create the content for the dynamic stylesheet.
		$handle      = 'spectra-gs-dynamic-styles';
		$css_content = "/** Generated Styles from the Spectra Global Styles. */\n";

		// Remove any duplicates from the root variables and add the vars to the sheet, if it exists of course.
		if ( ! empty( $root_variables ) ) {
			// First cleanup the array to just keep the unique variables.
			$root_variables = array_unique( $root_variables );

			// Start generating the root variable CSS.
			$root_css = ':root{';

			// Get the values for each variable, and add them to the root variable if they're available.
			foreach ( $root_variables as $root_variable ) {
				// If this root variable was in the cached variables, get the value and add the definition to the CSS root.
				if ( isset( $saved_variables[ $root_variable ] ) ) {
					$root_css .= '--' . $root_variable . ':' . $saved_variables[ $root_variable ] . ';';
				}
			}

			// Close the CSS root declaration.
			$root_css .= '} ';

			// Add the root CSS to the generated content.
			$css_content .= $root_css;
		}//end if

		// Add all the CSS rules to the generated content.
		$css_content .= trim( $css_rules );

		// Generate block defaults CSS (skip for preview builder).
		if ( ! isset( $_GET['spectra-gs-preview-builder'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe feature flag check, no user input processed
			$block_defaults_css = $this->generate_block_defaults_css( $active_classes );
			if ( ! empty( $block_defaults_css ) ) {
				$css_content .= ' ' . $block_defaults_css;
			}
		}

		// Register and enqueue the dynamic stylesheet.
		wp_register_style( $handle, false, array(), SPECTRA_PRO_VER );
		wp_enqueue_style( $handle );

		// Add the CSS content to the front-end.
		wp_add_inline_style( $handle, $css_content );
	}

	/**
	 * Filter the front-end content of blocks to check for and apply the spectraGSClasses.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $block_content The block content about to be rendered.
	 * @param array  $block         The block data including attributes.
	 * @return string               The filtered block content with classes applied if needed.
	 */
	public function apply_gs_classes_to_frontend_blocks( $block_content, $block ) {
		$block_name = $block['blockName'];
		
		// Map the block names to match our dropdown values.
		$block_mapping = array(
			'spectra/button'                               => 'button',
			'spectra/buttons'                              => 'buttons',
			'spectra/container'                            => 'container',
			'spectra/content'                              => 'content',
			'spectra/google-map'                           => 'google-map',
			'spectra/icon'                                 => 'icon',
			'spectra/icons'                                => 'icons',
			'spectra/separator'                            => 'separator',
			'spectra/list'                                 => 'list',
			'spectra/list-child-item'                      => 'list-child-item',
			'spectra/list-child-icon'                      => 'list-child-icon',
			'spectra/tabs'                                 => 'tabs',
			'spectra/tabs-child-tab-wrapper'               => 'tabs-child-tab-wrapper',
			'spectra/tabs-child-tab-button'                => 'tabs-child-tab-button',
			'spectra/tabs-child-tabpanel'                  => 'tabs-child-tabpanel',
			'spectra/accordion'                            => 'accordion',
			'spectra/accordion-child-item'                 => 'accordion-child-item',
			'spectra/accordion-child-header'               => 'accordion-child-header',
			'spectra/accordion-child-details'              => 'accordion-child-details',
			'spectra/accordion-child-header-content'       => 'accordion-child-header-content',
			'spectra/accordion-child-header-icon'          => 'accordion-child-header-icon',
			'spectra/slider'                               => 'slider',
			'spectra/slider-child'                         => 'slider-child',
			'spectra/countdown'                            => 'countdown',
			'spectra/countdown-child-day'                  => 'countdown-child-day',
			'spectra/countdown-child-hour'                 => 'countdown-child-hour',
			'spectra/countdown-child-minute'               => 'countdown-child-minute',
			'spectra/countdown-child-second'               => 'countdown-child-second',
			'spectra/countdown-child-number'               => 'countdown-child-number',
			'spectra/countdown-child-label'                => 'countdown-child-label',
			'spectra/countdown-child-separator'            => 'countdown-child-separator',
			'spectra/countdown-child-expiry-wrapper'       => 'countdown-child-expiry-wrapper',
			'spectra/modal'                                => 'modal',
			'spectra/modal-child-trigger'                  => 'modal-child-trigger',
			'spectra/modal-child-button'                   => 'modal-child-button',
			'spectra/modal-child-content'                  => 'modal-child-content',
			'spectra/modal-child-icon'                     => 'modal-child-icon',
			'spectra/modal-popup'                          => 'modal-popup',
			'spectra/modal-child-popup-close-icon'         => 'modal-child-popup-close-icon',
			'spectra/modal-popup-content'                  => 'modal-popup-content',
			'spectra-pro/loop-builder'                     => 'loop-builder',
			'spectra-pro/loop-builder-child-search'        => 'loop-builder-child-search',
			'spectra-pro/loop-builder-child-filter'        => 'loop-builder-child-filter',
			'spectra-pro/loop-builder-child-filter-select' => 'loop-builder-child-filter-select',
			'spectra-pro/loop-builder-child-filter-checkbox' => 'loop-builder-child-filter-checkbox',
			'spectra-pro/loop-builder-child-filter-button' => 'loop-builder-child-filter-button',
			'spectra-pro/loop-builder-child-reset-all-button' => 'loop-builder-child-reset-all-button',
			'spectra-pro/loop-builder-child-sort'          => 'loop-builder-child-sort',
			'spectra-pro/loop-builder-child-template'      => 'loop-builder-child-template',
			'spectra-pro/loop-builder-child-pagination'    => 'loop-builder-child-pagination',
			'spectra-pro/loop-builder-child-pagination-previous-button' => 'loop-builder-child-pagination-previous-button',
			'spectra-pro/loop-builder-child-pagination-page-numbers-button' => 'loop-builder-child-pagination-page-numbers-button',
			'spectra-pro/loop-builder-child-pagination-next-button' => 'loop-builder-child-pagination-next-button',
			'spectra-pro/loop-builder-child-no-results'    => 'loop-builder-child-no-results',
		);
		
		// Exit early if block is not supported.
		if ( ! isset( $block_mapping[ $block_name ] ) ) {
			return $block_content;
		}
		
		$mapped_block_name   = $block_mapping[ $block_name ];
		$gs_classes          = array();
		$apply_default_class = false;
		
		// Rule 1: If spectraGSClasses attribute does not exist, add the default class if block defaults are configured.
		if ( ! isset( $block['attrs']['spectraGSClasses'] ) ) {
			$block_defaults = get_option( $this->gs_options['block_defaults'], array() );
			if ( isset( $block_defaults[ $mapped_block_name ] ) ) {
				$block_config = $block_defaults[ $mapped_block_name ];
				
				// Check if any pseudo-selector has classes configured (not just 'default').
				$has_any_defaults = false;
				if ( is_array( $block_config ) ) {
					foreach ( $block_config as $pseudo_selector => $classes ) {
						if ( ! empty( $classes ) && is_array( $classes ) ) {
							$has_any_defaults = true;
							break;
						}
					}
				}
				
				if ( $has_any_defaults ) {
					$apply_default_class = true;
				}
			}
		} else {
			// Rule 2: If spectraGSClasses is an empty array, user explicitly removed defaults - do not add default class.
			// Rule 3: If spectraGSClasses has values, use them as-is (default class already included if needed).
			$gs_classes = is_array( $block['attrs']['spectraGSClasses'] ) ? $block['attrs']['spectraGSClasses'] : array();
		}//end if
		
		// If no classes to apply and no default class needed, return original content.
		if ( empty( $gs_classes ) && ! $apply_default_class ) {
			return $block_content;
		}

		// Get the current block wrapper element.
		$processor = new \WP_HTML_Tag_Processor( $block_content );
		
		// Find the main block wrapper element (first element with the wp-block-* class).
		while ( $processor->next_tag() ) {
			// Get the classes to compare with.
			$current_class = $processor->get_attribute( 'class' );

			// If this is not a block wrapper element, move to the next one.
			if ( ! $current_class || false === strpos( $current_class, 'wp-block-' ) ) {
				continue;
			}

			// Get existing classes to merge with new ones.
			$existing_classes = explode( ' ', $current_class );

			// Prepare new classes to add.
			$new_classes = array_filter( $gs_classes );
			
			// Add the default class only if explicitly needed (Rule 1).
			if ( $apply_default_class ) {
				$new_classes[] = 'default-' . $mapped_block_name;
			}

			// Combine all the classes, and remove duplicates.
			$all_classes = array_unique( array_merge( $existing_classes, $new_classes ) );

			// Apply the combined classes with spaces.
			$processor->set_attribute( 'class', implode( ' ', $all_classes ) );

			// Break out of the loop.
			break;
		}//end while
		
		// Return the updated HTML.
		return $processor->get_updated_html();
	}

	/**
	 * Enqueue Global Styles stylesheet on the Global Styles admin page.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_gs_stylesheet_on_admin( $hook ) {

		// Check if we're on the Spectra admin page.
		if ( 'toplevel_page_spectra' !== $hook ) {
			return;
		}

		// Check if we're on the Global Styles path.
		if ( ! isset( $_GET['path'] ) || 'global-styles' !== sanitize_text_field( wp_unslash( $_GET['path'] ) ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin path check, no user input processed
			return;
		}

		// Generate the stylesheet for the admin context.
		$this->generate_gs_stylesheet();
	}

	/**
	 * AJAX request handler to update the system color CSS variables.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return void
	 */
	public function update_system_color_variables() {

		// Run a security check.
		check_ajax_referer( 'spectra_gs_ajax_nonce', 'security' );

		// Send an error if any of the required ddata is not posted.
		if ( empty( $_POST['type'] || empty( $_POST['color'] ) || empty( $_POST['theme_variable'] ) ) ) {
			wp_send_json_error(
				array(
					'title'       => __( 'Color not saved.', 'spectra-pro' ),
					'description' => __( 'Could not read the color or variable.', 'spectra-pro' ),
				) 
			);
		}

		// Sanitize the required post data.
		$type              = sanitize_key( $_POST['type'] );
		$color             = sanitize_hex_color( $_POST['color'] );
		$theme_color_label = ( ! empty( $_POST['theme_color_label'] ) && is_string( $_POST['theme_color_label'] ) ) ? sanitize_text_field( $_POST['theme_color_label'] ) : '';
		$theme_variable    = sanitize_text_field( $_POST['theme_variable'] );

		// Get the system variables.
		$system_variables = get_option( $this->gs_options['system_variables'], $this->default_gs_system_variables );

		// If the color variables do not exist, create them.
		if ( empty( $system_variables['colors'] ) ) {
			$system_variables['colors'] = array();
		}

		// Update the required color.
		$system_variables['colors'][ $type ]['hexcode']     = $color;
		$system_variables['colors'][ $type ]['variable']    = 'none' === $theme_variable ? false : $theme_variable;
		$system_variables['colors'][ $type ]['theme_label'] = $theme_color_label;

		// Update the system variables.
		$success = update_option( $this->gs_options['system_variables'], $system_variables );

		// An array of messages for success and errors.
		$toast_titles = array(
			'success' => array(
				'primary'   => __( 'Primary Color Saved!', 'spectra-pro' ),
				'secondary' => __( 'Secondary Color Saved!', 'spectra-pro' ),
				'base'      => __( 'Base Color Saved!', 'spectra-pro' ),
				'default'   => __( 'Color Saved!', 'spectra-pro' ),
			),
			'error'   => array(
				'primary'   => __( 'Primary Color Not Saved.', 'spectra-pro' ),
				'secondary' => __( 'Secondary Color Not Saved.', 'spectra-pro' ),
				'base'      => __( 'Base Color Not Saved.', 'spectra-pro' ),
				'default'   => __( 'Color Not Saved.', 'spectra-pro' ),
			),
		);

		// If successful, first update the cache with the latest option, then send the success. Else send the error.
		if ( $success ) {
			$this->update_cached_variables();
			wp_send_json_success(
				array(
					'title'       => $toast_titles['success'][ $type ] ?? $toast_titles['success']['default'],
					'description' => __( 'Color updated in the database.', 'spectra-pro' ),
					'saved_color' => $system_variables['colors'][ $type ],
					'color_type'  => $type,
				) 
			);
		} else {
			wp_send_json_error(
				array(
					'title'       => $toast_titles['error'][ $type ] ?? $toast_titles['error']['default'],
					'description' => __( 'Color not updated in the database.', 'spectra-pro' ),
				) 
			);
		}
	}

	/**
	 * AJAX request handler to update the system spacing CSS variables.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return void
	 */
	public function update_system_spacing_variables() {

		// Created the messages for the value or unit update for FontSize.
		$messages = array(
			'success' => __( 'Spacing Saved!', 'spectra-pro' ),
			'error'   => __( 'Spacing not saved.', 'spectra-pro' ),
		);

		// Call the common numeric value & unit AJAX function.
		$this->update_system_numeric_unit_variable( 'spacing', $messages );
	}

	/**
	 * AJAX request handler to update the system font-size CSS variables.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return void
	 */
	public function update_system_fontsize_variables() {

		// Created the messages for the value or unit update for FontSize.
		$messages = array(
			'success' => __( 'Font Size Saved!', 'spectra-pro' ),
			'error'   => __( 'Font Size not saved.', 'spectra-pro' ),
		);

		// Call the common numeric value & unit AJAX function.
		$this->update_system_numeric_unit_variable( 'fontsize', $messages );
	}

	/**
	 * AJAX request handler to update the user created custom CSS variables.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return void
	 */
	public function update_user_variables() {

		// Run a security check.
		check_ajax_referer( 'spectra_gs_ajax_nonce', 'security' );

		// Send an error if any of the required ddata is not posted.
		if ( empty( $_POST['variables'] ) ) {
			wp_send_json_error(
				array(
					'title'       => __( 'Variables Not Saved.', 'spectra-pro' ),
					'description' => __( 'Could not read the variable sheet.', 'spectra-pro' ),
				) 
			);
		}

		// This function sanitizes the POST JSON string - the warning has been handled.
		$variables = $this->sanitize_json( $_POST['variables'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Input is sanitized via custom sanitize_json method

		// Get the user CSS.
		$user_variables = get_option( $this->gs_options['user_css'], array() );

		// Update the variables array.
		$user_variables['variables'] = $variables;

		// Update the user CSS.
		$success = update_option( $this->gs_options['user_css'], $user_variables );

		// If successful, first update the cache with the latest option, then send the success. Else send the error.
		if ( $success ) {
			$this->update_cached_variables();
			wp_send_json_success(
				array(
					'title'           => __( 'Variables Saved!', 'spectra-pro' ),
					'description'     => __( 'Values updated in the database.', 'spectra-pro' ),
					'saved_variables' => $user_variables['variables'],
				) 
			);
		} else {
			wp_send_json_error(
				array(
					'title'       => __( 'Variables Not Saved.', 'spectra-pro' ),
					'description' => __( 'Could not update in the database.', 'spectra-pro' ),
				) 
			);
		}
	}

	/**
	 * AJAX request handler to update the user created custom classes.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return void
	 */
	public function update_user_classes() {

		// Run a security check.
		check_ajax_referer( 'spectra_gs_ajax_nonce', 'security' );

		// Check if this is a destructive request.
		$is_destructive = ! empty( $_POST['is_destructive'] ) && 'yes' === $_POST['is_destructive'];

		// Send an error if any of the required ddata is not posted.
		if ( empty( $_POST['user_class'] ) || ( ! $is_destructive && empty( $_POST['user_styles'] ) ) ) {
			wp_send_json_error(
				array(
					'title'       => $is_destructive ? __( 'Could not delete.', 'spectra-pro' ) : __( 'Styles Not Saved.', 'spectra-pro' ),
					'description' => __( 'Could not read the class or stylesheet.', 'spectra-pro' ),
				) 
			);
		}
		
		// Sanitize the required post data.
		$user_class = sanitize_key( $_POST['user_class'] );

		// This function sanitizes the POST JSON string - the warning has been handled.
		$user_styles = array();
		if ( ! $is_destructive && ! empty( $_POST['user_styles'] ) ) {
			$user_styles = $this->sanitize_json( $_POST['user_styles'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Input is sanitized via custom sanitize_json method
		}
		// Get the user CSS option.
		$user_css = get_option( $this->gs_options['user_css'], array() );

		// Update the use classes array.
		if ( empty( $user_css['classes'] ) || ! is_array( $user_css['classes'] ) ) {
			$user_css['classes'] = array();
		}

		// If this is destructive, unset the value from the array and proceed with the response.
		if ( $is_destructive && isset( $user_css['classes'][ $user_class ] ) ) {
			unset( $user_css['classes'][ $user_class ] );
		} else {
			// Update the user classes with the sent class.
			$user_css['classes'][ $user_class ] = $user_styles;
		}

		// Update the user CSS option.
		$success = update_option( $this->gs_options['user_css'], $user_css );

		// If successful, send the success. Else send the error.
		if ( $success ) {
			wp_send_json_success(
				array(
					'title'       => $is_destructive ? __( 'Class Deleted!', 'spectra-pro' ) : __( 'Class Saved!', 'spectra-pro' ),
					'description' => sprintf(
						/* translators: %s: The class name. */
						__( 'Styles for %s updated in the database.', 'spectra-pro' ),
						esc_attr( $user_class )
					),
					'saved_class' => array(
						'name'   => $user_class,
						'styles' => $user_styles,
					),
					'destroyed'   => $is_destructive,
				) 
			);
		} else {
			wp_send_json_error(
				array(
					'title'       => $is_destructive ? __( 'Class Not Deleted.', 'spectra-pro' ) : __( 'Class Not Saved.', 'spectra-pro' ),
					'description' => sprintf(
						/* translators: %s: The class name. */
						__( 'Could not save styles for %s to the database.', 'spectra-pro' ),
						esc_attr( $user_class )
					),
				) 
			);
		}//end if
	}

	/**
	 * Enqueue block editor assets for a specific block.
	 *
	 * @since 2.0.0-beta.1
	 * 
	 * @param string $block_name The block name.
	 * @param string $blocks_path The path to blocks directory.
	 * @return void
	 */
	private function enqueue_block_editor_assets_for_block( $block_name, $blocks_path ) {
		$block_dir = $blocks_path . $block_name . '/';
		$block_url = UAGB_URL . '/spectra-v3/build/blocks/' . $block_name . '/';

		// Check for and enqueue editor script.
		$editor_script_path = $block_dir . 'index.js';
		if ( file_exists( $editor_script_path ) ) {
			$script_handle = "spectra-{$block_name}-editor";
			
			// Skip if already enqueued.
			if ( wp_script_is( $script_handle, 'enqueued' ) ) {
				return;
			}
			
			// Get asset file for dependencies if it exists.
			$asset_file_path = $block_dir . 'index.asset.php';
			$asset_file      = file_exists( $asset_file_path ) ? include $asset_file_path : [
				'dependencies' => [],
				'version'      => '1.0.0',
			];
			
			// Ensure WordPress block dependencies are included.
			$dependencies = array_merge( 
				$asset_file['dependencies'], 
				[ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components' ]
			);
			$dependencies = array_unique( $dependencies );
			
			wp_enqueue_script(
				$script_handle,
				$block_url . 'index.js',
				$dependencies,
				$asset_file['version'],
				true
			);
		}//end if

		// Check for and enqueue editor styles.
		$editor_style_path = $block_dir . 'index.css';
		if ( file_exists( $editor_style_path ) ) {
			$style_handle = "spectra-{$block_name}-editor";
			if ( ! wp_style_is( $style_handle, 'enqueued' ) ) {
				wp_enqueue_style(
					$style_handle,
					$block_url . 'index.css',
					[],
					'1.0.0'
				);
			}
		}

		// Check for and enqueue frontend styles.
		$style_path = $block_dir . 'style-index.css';
		if ( file_exists( $style_path ) ) {
			$style_handle = "spectra-{$block_name}";
			if ( ! wp_style_is( $style_handle, 'enqueued' ) ) {
				wp_enqueue_style(
					$style_handle,
					$block_url . 'style-index.css',
					[],
					'1.0.0'
				);
			}
		}       
	}

	/**
	 * Updates the block default classes.
	 *
	 * @since 2.0.0-beta.1
	 * 
	 * @return void
	 */
	public function update_block_defaults() {
		
		// Security Check.
		check_ajax_referer( 'spectra_gs_ajax_nonce', 'security' );
		
		$new_block_defaults = isset( $_POST['block_defaults'] ) ? $this->sanitize_json( wp_unslash( $_POST['block_defaults'] ) ) : array(); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Input is sanitized via custom sanitize_json method
		
		// Get existing block defaults from database to preserve unchanged states.
		$existing_block_defaults = get_option( $this->gs_options['block_defaults'], array() );
		
		// Merge new block defaults with existing ones, preserving unchanged pseudo-selectors.
		$merged_block_defaults = $existing_block_defaults;
		
		foreach ( $new_block_defaults as $block_name => $new_pseudo_selectors ) {
			if ( ! is_array( $new_pseudo_selectors ) ) {
				// Handle legacy format or invalid data.
				$merged_block_defaults[ $block_name ] = $new_pseudo_selectors;
				continue;
			}
			
			// Initialize block entry if it doesn't exist.
			if ( ! isset( $merged_block_defaults[ $block_name ] ) ) {
				$merged_block_defaults[ $block_name ] = array();
			}
			
			// Merge each pseudo-selector, preserving existing ones that aren't being updated.
			foreach ( $new_pseudo_selectors as $pseudo_selector => $class_names ) {
				$merged_block_defaults[ $block_name ][ $pseudo_selector ] = $class_names;
			}
		}
		
		// Update the option with merged data.
		$success = update_option( $this->gs_options['block_defaults'], $merged_block_defaults );
		
		if ( $success || ( get_option( $this->gs_options['block_defaults'] ) === $merged_block_defaults ) ) {
			wp_send_json_success(
				array(
					'title'          => __( 'Block Defaults Saved!', 'spectra-pro' ),
					'description'    => __( 'Your block default classes have been saved successfully.', 'spectra-pro' ),
					'block_defaults' => $merged_block_defaults,
				)
			);
		} else {
			wp_send_json_error(
				array(
					'title'       => __( 'Block Defaults Not Saved.', 'spectra-pro' ),
					'description' => __( 'There was an error saving your block defaults.', 'spectra-pro' ),
				)
			);
		}
	}

	/**
	 * Generate CSS for block defaults.
	 *
	 * This method creates default-[blockname] classes that combine all CSS properties
	 * from the Global Styles classes assigned to each block.
	 *
	 * @since 2.0.0
	 *
	 * @param array $active_classes Array of active CSS classes with their styles.
	 * @return string The generated CSS for block defaults.
	 */
	private function generate_block_defaults_css( $active_classes ) {
		// Get the block defaults option.
		$block_defaults = get_option( $this->gs_options['block_defaults'], array() );
		
		// If no block defaults are set, return empty.
		if ( empty( $block_defaults ) ) {
			return '';
		}
		
		$block_defaults_css = '';
		
		// Process each block that has defaults set.
		foreach ( $block_defaults as $block_name => $block_config ) {
			// Skip if no configuration is available.
			if ( empty( $block_config ) || ! is_array( $block_config ) ) {
				continue;
			}
			
			// Handle pseudo-selector format.
			$all_pseudo_selectors = $block_config;
			
			// Process each pseudo-selector for this block.
			foreach ( $all_pseudo_selectors as $pseudo_selector => $assigned_classes ) {
				// Skip if no classes are assigned for this pseudo-selector.
				if ( empty( $assigned_classes ) || ! is_array( $assigned_classes ) ) {
					continue;
				}
				
				// Collect all CSS properties for this block's assigned classes.
				$combined_styles = '';
				
				foreach ( $assigned_classes as $class_name ) {
					// Look for this class in the active classes array.
					if ( isset( $active_classes[ $class_name ] ) ) {
						// Get the styles for this class.
						$style_declaration = $active_classes[ $class_name ];
						
						// Process any shortcodes in the class name.
						$processed_classes = $this->process_class_shortcodes( $class_name, $style_declaration, false );
						
						// Add styles from each processed class.
						foreach ( $processed_classes as $current_selector => $current_styles ) {
							// Extract just the CSS properties (remove any formatting).
							$current_styles = preg_replace( '/([\:\;])\s*/', '$1', $current_styles );
							
							// Add to combined styles, ensuring no duplicate properties.
							if ( ! empty( $current_styles ) ) {
								$combined_styles .= $current_styles;
							}
						}
					}
				}//end foreach
				
				// If we have combined styles, create the default class.
				if ( ! empty( $combined_styles ) ) {
					// Create the default class name.
					$default_class = 'default-' . $block_name;
					
					// Add pseudo-selector if not default.
					$css_selector = '[class*="wp-block"].' . $default_class;
					if ( 'default' !== $pseudo_selector ) {
						$css_selector .= ':' . $pseudo_selector;
					}
					
					// Add the CSS rule for this block's default class.
					$block_defaults_css .= ' ' . $css_selector . '{' . $combined_styles . '}';
				}
			}//end foreach
		}//end foreach
		
		return $block_defaults_css;
	}

	/**
	 * Enqueue preview builder assets when in iframe preview mode.
	 *
	 * This method checks for the spectra-gs-preview-builder parameter and enqueues
	 * the necessary JavaScript and CSS files for the block preview functionality.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return void
	 */
	public function enqueue_preview_builder_assets() {
		// Check if we're in preview builder mode.
		if ( ! isset( $_GET['spectra-gs-preview-builder'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe feature flag check, no user input processed
			return;
		}

		// Define asset paths and URLs.
		$assets_url = SPECTRA_PRO_2_URL . 'assets/';

		// Enqueue the preview builder JavaScript.
		wp_enqueue_script(
			'spectra-gs-preview-builder',
			$assets_url . 'js/global-styles-preview-builder.js',
			array( 'wp-blocks', 'wp-data', 'wp-element' ),
			SPECTRA_PRO_VER,
			true
		);

		// Enqueue the preview builder CSS.
		wp_enqueue_style(
			'spectra-gs-preview-builder',
			$assets_url . 'css/global-styles-preview-builder.css',
			array(),
			SPECTRA_PRO_VER
		);

		// Add body class for preview mode styling.
		add_filter(
			'admin_body_class',
			function( $classes ) {
				return $classes . ' spectra-gs-preview-mode';
			} 
		);
	}

	/**
	 * Disable post saving functionality for preview builder.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return void
	 */
	public function disable_preview_post_saving() {
		// Check if we're in preview builder mode.
		if ( ! isset( $_GET['spectra-gs-preview-builder'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe feature flag check, no user input processed
			return;
		}

		// Disable autosave functionality.
		add_filter( 'wp_autosave_interval', '__return_false' );
		
		// Disable heartbeat API for this page.
		add_action( 'init', array( $this, 'disable_heartbeat_for_preview' ), 1 );
		
		// Disable post revision saving.
		remove_action( 'pre_post_update', 'wp_save_post_revision' );
		
		// Disable post saving via AJAX after initial creation.
		add_action( 'wp_ajax_heartbeat', array( $this, 'disable_heartbeat_save' ), 1 );
		
		// Disable draft saving on shutdown.
		remove_action( 'shutdown', 'wp_ob_end_flush_all', 1 );
		
		// Add selective action to prevent post updates (but allow initial creation).
		add_action( 'wp_insert_post', array( $this, 'prevent_preview_post_updates' ), 10, 3 );
	}

	/**
	 * Disable heartbeat for preview builder.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return void
	 */
	public function disable_heartbeat_for_preview() {
		// Check if we're in preview builder mode.
		if ( ! isset( $_GET['spectra-gs-preview-builder'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe feature flag check, no user input processed
			return;
		}

		// Completely disable heartbeat on preview builder pages.
		wp_deregister_script( 'heartbeat' );
		add_action(
			'admin_enqueue_scripts',
			function() {
				wp_dequeue_script( 'heartbeat' );
			},
			99 
		);
	}

	/**
	 * Prevent preview post updates after initial creation.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param int     $post_id    Post ID.
	 * @param WP_Post $post       Post object.
	 * @param bool    $update     Whether this is an existing post being updated.
	 * @return void
	 */
	public function prevent_preview_post_updates( $post_id, $post, $update ) {
		// Check if we're in preview builder mode.
		if ( ! isset( $_GET['spectra-gs-preview-builder'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe feature flag check, no user input processed
			return;
		}

		// Allow initial post creation, but prevent subsequent updates.
		if ( $update ) {
			// This is an update to an existing post - prevent it.
			wp_die(
				'',
				'',
				array(
					'response' => 200,
					'exit'     => false,
				) 
			);
		}
	}

	/**
	 * Disable heartbeat save functionality.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return void
	 */
	public function disable_heartbeat_save() {
		// Check if we're in preview builder mode.
		if ( ! isset( $_GET['spectra-gs-preview-builder'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe feature flag check, no user input processed
			return;
		}

		// Prevent heartbeat from processing save requests.
		if ( isset( $_POST['wp_autosave'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Missing -- Safe preview builder flag, no user input processed
			unset( $_POST['wp_autosave'] ); //phpcs:ignore WordPress.Security.NonceVerification.Missing -- Safe preview builder flag, no user input processed
		}
		
		// Exit early to prevent any save processing.
		wp_die(
			'',
			'',
			array(
				'response' => 200,
				'exit'     => false,
			) 
		);
	}

	/**
	 * AJAX request handler to generate CSS for block preview.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return void
	 */
	public function generate_preview_css() {
		// Security check.
		check_ajax_referer( 'spectra_gs_ajax_nonce', 'security' );

		// Get request data.
		$block_name      = isset( $_POST['block_name'] ) ? sanitize_text_field( $_POST['block_name'] ) : '';
		$pseudo_selector = isset( $_POST['pseudo_selector'] ) ? sanitize_text_field( $_POST['pseudo_selector'] ) : 'default';
		$class_names     = isset( $_POST['class_names'] ) ? array_map( 'sanitize_text_field', $_POST['class_names'] ) : array();
		$block_group     = isset( $_POST['block_group'] ) ? array_map( 'sanitize_text_field', $_POST['block_group'] ) : array();

		if ( empty( $block_name ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Block name is required.', 'spectra-pro' ),
				) 
			);
		}

		// Generate CSS for the provided classes and block group.
		$css = $this->generate_comprehensive_block_preview_css( $block_name, $pseudo_selector, $class_names, $block_group );

		wp_send_json_success(
			array(
				'css'             => $css,
				'block_name'      => $block_name,
				'pseudo_selector' => $pseudo_selector,
			) 
		);
	}

	/**
	 * AJAX request handler to generate CSS for all block defaults.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return void
	 */
	public function generate_all_preview_css() {
		// Security check.
		check_ajax_referer( 'spectra_gs_ajax_nonce', 'security' );

		// Generate comprehensive CSS for all saved block defaults.
		$css = $this->generate_all_block_defaults_preview_css();

		wp_send_json_success(
			array(
				'css' => $css,
			) 
		);
	}

	/**
	 * Generate comprehensive CSS for block preview including all pseudo-selectors and group blocks.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $current_block_name      The currently selected block name.
	 * @param string $current_pseudo_selector The currently selected pseudo-selector.
	 * @param array  $current_class_names     Array of class names for current selection.
	 * @param array  $block_group_data        Array of all block data in the group.
	 * @return string                         Generated CSS string.
	 */
	private function generate_comprehensive_block_preview_css( $current_block_name, $current_pseudo_selector, $current_class_names, $block_group_data ) {
		// Load all Global Styles classes.
		$single_dimensional_data = array();
		$gs_classes_dir          = SPECTRA_PRO_2_DIR . 'data/gs-classes/';

		// Read and merge class definitions from all JSON files.
		foreach ( $this->recursively_get_json_files( $gs_classes_dir . '**/*.json' ) as $json_file ) {
			$raw_data = file_get_contents( $json_file );
			if ( false === $raw_data ) {
				continue;
			}

			$file_data = json_decode( $raw_data, true );
			if ( null === $file_data ) {
				continue;
			}

			if ( ! $this->is_multidimensional_array( $file_data ) ) {
				$single_dimensional_data = array_merge( $single_dimensional_data, $file_data );
			} else {
				$this->flatten_json_array_recursively( $file_data, $single_dimensional_data );
			}
		}

		// Get user classes and all available classes.
		$user_classes = $this->process_user_custom_classes( true );
		$all_classes  = array_merge( $user_classes, $single_dimensional_data );

		// Get all block defaults for comprehensive preview.
		$block_defaults = get_option( $this->gs_options['block_defaults'], array() );

		// Initialize CSS generation.
		$root_variables = array();
		$css_rules      = '';

		// Merge current selection with existing block defaults.
		$comprehensive_block_data = array();
		
		// Add current block selection.
		$comprehensive_block_data[ $current_block_name ] = array(
			$current_pseudo_selector => $current_class_names,
		);

		// Add all pseudo-selectors for the current block from saved defaults.
		if ( isset( $block_defaults[ $current_block_name ] ) && is_array( $block_defaults[ $current_block_name ] ) ) {
			foreach ( $block_defaults[ $current_block_name ] as $pseudo_sel => $saved_classes ) {
				if ( $pseudo_sel !== $current_pseudo_selector && ! empty( $saved_classes ) ) {
					$comprehensive_block_data[ $current_block_name ][ $pseudo_sel ] = $saved_classes;
				}
			}
		}

		// Add all blocks from the same group with their defaults.
		foreach ( $block_group_data as $group_block_name ) {
			if ( $group_block_name !== $current_block_name && isset( $block_defaults[ $group_block_name ] ) ) {
				$comprehensive_block_data[ $group_block_name ] = $block_defaults[ $group_block_name ];
			}
		}

		// Generate CSS for all blocks and their pseudo-selectors.
		foreach ( $comprehensive_block_data as $block_name => $pseudo_selectors ) {
			foreach ( $pseudo_selectors as $pseudo_selector => $class_names ) {
				if ( empty( $class_names ) || ! is_array( $class_names ) ) {
					continue;
				}

				$combined_styles = '';

				// Process each class and collect styles.
				foreach ( $class_names as $class_name ) {
					if ( isset( $all_classes[ $class_name ] ) ) {
						$style_declaration = $all_classes[ $class_name ];
						$processed_classes = $this->process_class_shortcodes( $class_name, $style_declaration, true );

						foreach ( $processed_classes as $current_selector => $current_styles ) {
							$current_styles = preg_replace( '/([\:\;])\s*/', '$1', $current_styles );
							if ( ! empty( $current_styles ) ) {
								$combined_styles .= $current_styles;

								// Extract CSS variables used.
								preg_match_all( '/(?:var\(--){1}([a-zA-Z][a-zA-Z0-9-]+)\b/', $current_styles, $matches );
								$root_variables = array_merge( $root_variables, $matches[1] );
							}
						}
					}
				}

				// Add CSS rule for this block and pseudo-selector combination.
				if ( ! empty( $combined_styles ) ) {
					$default_class = 'default-' . $block_name;
					$css_selector  = '[class*="wp-block"].' . $default_class;

					if ( 'default' !== $pseudo_selector ) {
						$css_selector .= ':' . $pseudo_selector;
					}

					$css_rules .= ' ' . $css_selector . '{' . $combined_styles . '}';
				}
			}//end foreach
		}//end foreach

		// Create the final CSS.
		$css = '';

		// Add root variables if needed.
		if ( ! empty( $root_variables ) && ! empty( $this->cached_css_variables ) ) {
			$root_variables = array_unique( $root_variables );
			$root_css       = ':root{';

			foreach ( $root_variables as $root_variable ) {
				if ( isset( $this->cached_css_variables[ $root_variable ] ) ) {
					$root_css .= '--' . $root_variable . ':' . $this->cached_css_variables[ $root_variable ] . ';';
				}
			}

			$root_css .= '}';
			$css      .= $root_css;
		}

		// Add all CSS rules.
		$css .= $css_rules;

		return $css;
	}

	/**
	 * Generate CSS for all saved block defaults for comprehensive preview.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return string Generated CSS string for all block defaults.
	 */
	private function generate_all_block_defaults_preview_css() {
		// Get all block defaults.
		$block_defaults = get_option( $this->gs_options['block_defaults'], array() );
		
		if ( empty( $block_defaults ) ) {
			return '';
		}

		// Load all Global Styles classes.
		$single_dimensional_data = array();
		$gs_classes_dir          = SPECTRA_PRO_2_DIR . 'data/gs-classes/';

		// Read and merge class definitions from all JSON files.
		foreach ( $this->recursively_get_json_files( $gs_classes_dir . '**/*.json' ) as $json_file ) {
			$raw_data = file_get_contents( $json_file );
			if ( false === $raw_data ) {
				continue;
			}

			$file_data = json_decode( $raw_data, true );
			if ( null === $file_data ) {
				continue;
			}

			if ( ! $this->is_multidimensional_array( $file_data ) ) {
				$single_dimensional_data = array_merge( $single_dimensional_data, $file_data );
			} else {
				$this->flatten_json_array_recursively( $file_data, $single_dimensional_data );
			}
		}

		// Get user classes and all available classes.
		$user_classes = $this->process_user_custom_classes( true );
		$all_classes  = array_merge( $user_classes, $single_dimensional_data );

		// Initialize CSS generation.
		$root_variables = array();
		$css_rules      = '';

		// Generate CSS for all blocks and their pseudo-selectors.
		foreach ( $block_defaults as $block_name => $pseudo_selectors ) {
			foreach ( $pseudo_selectors as $pseudo_selector => $class_names ) {
				if ( empty( $class_names ) || ! is_array( $class_names ) ) {
					continue;
				}

				$combined_styles = '';

				// Process each class and collect styles.
				foreach ( $class_names as $class_name ) {
					if ( isset( $all_classes[ $class_name ] ) ) {
						$style_declaration = $all_classes[ $class_name ];
						$processed_classes = $this->process_class_shortcodes( $class_name, $style_declaration, true );

						foreach ( $processed_classes as $current_selector => $current_styles ) {
							$current_styles = preg_replace( '/([\:\;])\s*/', '$1', $current_styles );
							if ( ! empty( $current_styles ) ) {
								$combined_styles .= $current_styles;

								// Extract CSS variables used.
								preg_match_all( '/(?:var\(--){1}([a-zA-Z][a-zA-Z0-9-]+)\b/', $current_styles, $matches );
								$root_variables = array_merge( $root_variables, $matches[1] );
							}
						}
					}
				}

				// Add CSS rule for this block and pseudo-selector combination.
				if ( ! empty( $combined_styles ) ) {
					$default_class = 'default-' . $block_name;
					$css_selector  = '[class*="wp-block"].' . $default_class;

					if ( 'default' !== $pseudo_selector ) {
						$css_selector .= ':' . $pseudo_selector;
					}

					$css_rules .= ' ' . $css_selector . '{' . $combined_styles . '}';
				}
			}//end foreach
		}//end foreach

		// Create the final CSS.
		$css = '';

		// Add root variables if needed.
		if ( ! empty( $root_variables ) && ! empty( $this->cached_css_variables ) ) {
			$root_variables = array_unique( $root_variables );
			$root_css       = ':root{';

			foreach ( $root_variables as $root_variable ) {
				if ( isset( $this->cached_css_variables[ $root_variable ] ) ) {
					$root_css .= '--' . $root_variable . ':' . $this->cached_css_variables[ $root_variable ] . ';';
				}
			}

			$root_css .= '}';
			$css      .= $root_css;
		}

		// Add all CSS rules.
		$css .= $css_rules;

		return $css;
	}

	/**
	 * AJAX request handler to replace the spectra-gs-dynamic-styles stylesheet with generated CSS.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return void
	 */
	public function replace_dynamic_stylesheet() {
		// Security check.
		check_ajax_referer( 'spectra_gs_ajax_nonce', 'security' );

		// Generate comprehensive CSS with commenting system.
		$css = $this->generate_commented_stylesheet_css();

		wp_send_json_success(
			array(
				'css' => $css,
			) 
		);
	}

	/**
	 * AJAX request handler to update specific class in the stylesheet.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return void
	 */
	public function update_class_in_stylesheet() {
		// Security check.
		check_ajax_referer( 'spectra_gs_ajax_nonce', 'security' );

		// Get request data.
		$action          = isset( $_POST['action_type'] ) ? sanitize_text_field( $_POST['action_type'] ) : '';
		$block_name      = isset( $_POST['block_name'] ) ? sanitize_text_field( $_POST['block_name'] ) : '';
		$pseudo_selector = isset( $_POST['pseudo_selector'] ) ? sanitize_text_field( $_POST['pseudo_selector'] ) : 'default';
		$class_name      = isset( $_POST['class_name'] ) ? sanitize_text_field( $_POST['class_name'] ) : '';

		if ( empty( $action ) || empty( $block_name ) || empty( $class_name ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Required parameters missing.', 'spectra-pro' ),
				) 
			);
		}

		if ( 'add' === $action ) {
			// Generate CSS for the specific class.
			$css = $this->generate_single_class_css( $block_name, $pseudo_selector, $class_name );
			
			wp_send_json_success(
				array(
					'action'          => 'add',
					'css'             => $css,
					'block_name'      => $block_name,
					'pseudo_selector' => $pseudo_selector,
					'class_name'      => $class_name,
				) 
			);
		} elseif ( 'remove' === $action ) {
			wp_send_json_success(
				array(
					'action'          => 'remove',
					'block_name'      => $block_name,
					'pseudo_selector' => $pseudo_selector,
					'class_name'      => $class_name,
				) 
			);
		}//end if
	}

	/**
	 * Generate complete stylesheet CSS with commenting system for identification.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return string Generated CSS string with comments for all block defaults.
	 */
	private function generate_commented_stylesheet_css() {
		// Get all saved block defaults.
		$block_defaults = get_option( $this->gs_options['block_defaults'], array() );
		
		if ( empty( $block_defaults ) ) {
			return '';
		}

		// Load all Global Styles classes.
		$all_classes = $this->load_all_global_styles_classes();

		// Initialize CSS generation.
		$root_variables = array();
		$css_rules      = '';

		// Generate CSS for each block and pseudo-selector combination.
		foreach ( $block_defaults as $block_name => $pseudo_selectors ) {
			// Handle legacy format (simple array) vs new format (pseudo-selector array).
			if ( is_array( $pseudo_selectors ) && ! $this->is_assoc_array( $pseudo_selectors ) ) {
				$pseudo_selectors = array( 'default' => $pseudo_selectors );
			}

			foreach ( $pseudo_selectors as $pseudo_selector => $class_names ) {
				if ( empty( $class_names ) || ! is_array( $class_names ) ) {
					continue;
				}

				// Add commented section for this block/pseudo-selector combination.
				$css_rules .= "\n/* START: {$block_name}-{$pseudo_selector} */\n";

				foreach ( $class_names as $class_name ) {
					$class_css = $this->get_single_class_css( $class_name, $all_classes, $root_variables );
					if ( ! empty( $class_css ) ) {
						$css_rules .= "/* CLASS: {$class_name} */\n";
						$css_rules .= $this->format_class_css_for_block( $block_name, $pseudo_selector, $class_css );
						$css_rules .= "/* END CLASS: {$class_name} */\n";
					}
				}

				$css_rules .= "/* END: {$block_name}-{$pseudo_selector} */\n";
			}
		}//end foreach

		// Create the final CSS with root variables.
		$css = '';

		// Add root variables if needed.
		if ( ! empty( $root_variables ) && ! empty( $this->cached_css_variables ) ) {
			$css .= $this->generate_root_variables_css( $root_variables );
		}

		// Add all CSS rules.
		$css .= $css_rules;

		return $css;
	}

	/**
	 * Generate CSS for a single class to be added to the stylesheet.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $block_name      Block name.
	 * @param string $pseudo_selector Pseudo-selector.
	 * @param string $class_name      Class name to generate CSS for.
	 * @return string                 Generated CSS string.
	 */
	private function generate_single_class_css( $block_name, $pseudo_selector, $class_name ) {
		// Load all Global Styles classes.
		$all_classes    = $this->load_all_global_styles_classes();
		$root_variables = array();

		$class_css = $this->get_single_class_css( $class_name, $all_classes, $root_variables );
		
		if ( empty( $class_css ) ) {
			return '';
		}

		$css = '';

		// Add root variables if needed.
		if ( ! empty( $root_variables ) && ! empty( $this->cached_css_variables ) ) {
			$css .= $this->generate_root_variables_css( $root_variables );
		}

		// Add commented CSS for the class.
		$css .= "\n/* CLASS: {$class_name} */\n";
		$css .= $this->format_class_css_for_block( $block_name, $pseudo_selector, $class_css );
		$css .= "/* END CLASS: {$class_name} */\n";

		return $css;
	}

	/**
	 * Load all Global Styles classes from JSON files and user custom classes.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return array All available Global Styles classes.
	 */
	private function load_all_global_styles_classes() {
		// Load from JSON files.
		$single_dimensional_data = array();
		$gs_classes_dir          = SPECTRA_PRO_2_DIR . 'data/gs-classes/';

		foreach ( $this->recursively_get_json_files( $gs_classes_dir . '**/*.json' ) as $json_file ) {
			$raw_data = file_get_contents( $json_file );
			if ( false === $raw_data ) {
				continue;
			}

			$file_data = json_decode( $raw_data, true );
			if ( null === $file_data ) {
				continue;
			}

			if ( ! $this->is_multidimensional_array( $file_data ) ) {
				$single_dimensional_data = array_merge( $single_dimensional_data, $file_data );
			} else {
				$this->flatten_json_array_recursively( $file_data, $single_dimensional_data );
			}
		}

		// Get user custom classes.
		$user_classes = $this->process_user_custom_classes( true );

		return array_merge( $user_classes, $single_dimensional_data );
	}

	/**
	 * Get CSS for a single class and collect root variables.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $class_name      Class name.
	 * @param array  $all_classes     All available classes.
	 * @param array  $root_variables  Reference to root variables array.
	 * @return string                 CSS for the class.
	 */
	private function get_single_class_css( $class_name, $all_classes, &$root_variables ) {
		if ( ! isset( $all_classes[ $class_name ] ) ) {
			return '';
		}

		$style_declaration = $all_classes[ $class_name ];
		$processed_classes = $this->process_class_shortcodes( $class_name, $style_declaration, true );
		$combined_styles   = '';

		foreach ( $processed_classes as $current_selector => $current_styles ) {
			$current_styles = preg_replace( '/([\:\;])\s*/', '$1', $current_styles );
			if ( ! empty( $current_styles ) ) {
				$combined_styles .= $current_styles;

				// Extract CSS variables used.
				preg_match_all( '/(?:var\(--){1}([a-zA-Z][a-zA-Z0-9-]+)\b/', $current_styles, $matches );
				$root_variables = array_merge( $root_variables, $matches[1] );
			}
		}

		return $combined_styles;
	}

	/**
	 * Format class CSS for a specific block and pseudo-selector.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param string $block_name      Block name.
	 * @param string $pseudo_selector Pseudo-selector.
	 * @param string $class_css       CSS styles for the class.
	 * @return string                 Formatted CSS with proper selector.
	 */
	private function format_class_css_for_block( $block_name, $pseudo_selector, $class_css ) {
		$default_class = 'default-' . $block_name;
		$css_selector  = '[class*="wp-block"].' . $default_class;

		if ( 'default' !== $pseudo_selector ) {
			$css_selector .= ':' . $pseudo_selector;
		}

		return $css_selector . '{' . $class_css . "}\n";
	}

	/**
	 * Generate root variables CSS.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param array $root_variables Array of root variable names.
	 * @return string               Root variables CSS.
	 */
	private function generate_root_variables_css( $root_variables ) {
		if ( empty( $root_variables ) || empty( $this->cached_css_variables ) ) {
			return '';
		}

		$root_variables = array_unique( $root_variables );
		$root_css       = ":root{\n";

		foreach ( $root_variables as $root_variable ) {
			if ( isset( $this->cached_css_variables[ $root_variable ] ) ) {
				$root_css .= '--' . $root_variable . ':' . $this->cached_css_variables[ $root_variable ] . ";\n";
			}
		}

		$root_css .= "}\n";
		return $root_css;
	}
} 
