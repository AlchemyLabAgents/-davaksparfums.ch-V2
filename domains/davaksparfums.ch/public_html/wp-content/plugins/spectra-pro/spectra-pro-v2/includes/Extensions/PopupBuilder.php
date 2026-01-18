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
 * Class PopupBuilder
 *
 * Handles the Popup Builder block functionality and navigation features.
 *
 * @since 2.0.0-beta.1
 */
class PopupBuilder {

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
	const BLOCK_NAME = 'spectra/popup-builder';

	/**
	 * Initialize the extension.
	 *
	 * @since 2.0.0-beta.1
	 * @return void
	 */
	public function init() {
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_assets' ) );
		add_filter( 'spectra_pro_popup_frontend_js_v3', array( $this, 'upgrade_frontend_js' ), 15, 5 ); 
		add_filter( 'spectra_pro_popup_display_filters_v3', array( $this, 'render_shortcode_conditionally' ), 10, 2 );
		
		// AJAX handlers for admin
		add_action( 'wp_ajax_spectra_popup_builder_get_posts_by_query', array( $this, 'handle_ajax_search' ) );
		add_action( 'wp_ajax_nopriv_spectra_popup_builder_get_posts_by_query', array( $this, 'handle_ajax_search' ) );
	}
	
	/**
	 * Enqueues frontend assets for dynamic content functionality.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		wp_enqueue_style( 'spectra-extensions-popup-builder' );
	}

	/**
	 * Handle AJAX search for posts/pages
	 *
	 * @since 2.0.0-beta.1
	 * @return void
	 */
	public function handle_ajax_search() {
		check_ajax_referer( 'spectra_pro_ajax_nonce', 'nonce' );

		$search_string = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
		
		if ( empty( $search_string ) || strlen( $search_string ) < 2 ) {
			wp_send_json_success( array() );
			return;
		}

		$result = array();

		// Get public post types
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		
		// Remove attachment post type
		unset( $post_types['attachment'] );

		foreach ( $post_types as $post_type ) {
			$query = new \WP_Query( array(
				's' => $search_string,
				'post_type' => $post_type->name,
				'posts_per_page' => 20, // Limit results
				'post_status' => 'publish'
			) );

			$posts_data = array();
			
			while ( $query->have_posts() ) {
				$query->the_post();
				$posts_data[] = array(
					'id' => 'post-' . get_the_ID(),
					'title' => get_the_title() . ' (ID: ' . get_the_ID() . ')'
				);
			}

			if ( ! empty( $posts_data ) ) {
				$result[] = array(
					'title' => $post_type->labels->name,
					'children' => $posts_data
				);
			}
		}

		wp_reset_postdata();
		wp_send_json_success( $result );
	}

	/**
	 * Enhance frontend JavaScript for V3 compatibility
	 *
	 * @since 2.0.0-beta.1
	 * @param string $js              The current block JS script.
	 * @param int    $id              The current block ID.
	 * @param array  $attr            The current block attributes.
	 * @param bool   $is_push_banner  A boolean stating if this is a push banner or not.
	 * @param int    $popup_timer     The timer of the current popup based on if it's a push banner.
	 * @return string                 The enhanced JS script.
	 */
	public function enhance_frontend_js( $js, $id, $attr, $is_push_banner, $popup_timer ) {
		// Get popup meta
		$popup_id = get_the_ID();
		if ( ! $popup_id ) {
			return $js;
		}

		$trigger = get_post_meta( $popup_id, 'spectra-popup-trigger', true );
		$trigger_delay = (int) get_post_meta( $popup_id, 'spectra-popup-trigger-delay', true );
		$display_inclusions = get_post_meta( $popup_id, 'spectra-popup-display-inclusions', true );
		$display_exclusions = get_post_meta( $popup_id, 'spectra-popup-display-exclusions', true );

		// Build enhanced configuration
		$config = array(
			'id' => $popup_id,
			'trigger' => $trigger ?: 'load',
			'triggerDelay' => $trigger_delay,
			'displayConditions' => array(
				'inclusions' => $display_inclusions ?: array(),
				'exclusions' => $display_exclusions ?: array(),
			),
			'blockSelector' => '.spectra-popup-builder',
			'isPushBanner' => $is_push_banner,
			'popupTimer' => $popup_timer,
			'haltBackgroundInteraction' => ! empty( $attr['haltBackgroundInteraction'] ),
			'closeOverlayClick' => ! empty( $attr['closeOverlayClick'] ),
			'hasOverlay' => ! empty( $attr['hasOverlay'] ),
			'closeEscapePress' => ! empty( $attr['closeEscapePress'] ),
			'closeIcon' => ! empty( $attr['closeIcon'] ),
			'variantType' => $attr['variantType'] ?? 'popup'
		);

		$config_json = wp_json_encode( $config );

		// Return enhanced JavaScript that uses the new trigger system
		return "
		document.addEventListener('DOMContentLoaded', function() {
			if (window.SpectraPopupTriggerManager) {
				window.SpectraPopupTriggerManager.registerPopup({$config_json});
			} else {
				// Fallback to original system
				{$js}
			}
		});
		";
	}

		/**
	 * Check whether to render this popup or not based on Pro Display Conditions.
	 *
	 * Note:
	 * Popups can be included in a general post type AND be excluded specifically.
	 *
	 * @param bool $render_status  The current render status of this popup based on whether it is enabled.
	 * @param int  $post_id        The current post ID to render this popup on.
	 * @return bool                Whether to render this popup on this post or not.
	 *
	 * @since 3.0.0-beta.1
	 */
	public function render_shortcode_conditionally( $render_status, $post_id ) {
		// If this popup is not enabled, exit directly.
		if ( ! $render_status ) {
			return $render_status;
		}

		// Return early if unable to get the popup ID.
		$popup_id = get_the_ID();
		if ( false === $popup_id ) {
			return $render_status;
		}

		// Get the display inclusion meta, return if it's not defined.
		$include_on = get_post_meta( $popup_id, 'spectra-popup-display-inclusions', true );
		if ( ! is_array( $include_on ) ) {
			return $render_status;
		}

		// Get the display exclusion meta, return if it's not defined.
		$exclude_on = get_post_meta( $popup_id, 'spectra-popup-display-exclusions', true );
		if ( ! is_array( $exclude_on ) ) {
			return $render_status;
		}

		// Parse the exclusion rules to check if this popup is excluded from the current post (generally or specifically).
		$is_excluded = $this->parse_popup_display_condition( $post_id, $exclude_on );

		// Exit Early - Don't render this popup if it's excluded.
		if ( $is_excluded ) {
			return false;
		}

		// Parse the inclusion rules to check if this popup is included on the current post (generally or specifically).
		$is_included = $this->parse_popup_display_condition( $post_id, $include_on );
		if ( $is_included ) {
			return true;
		}

		// If this popup had implicit include rules, don't render it - else render it.
		return empty( $include_on['rule'] );
	}

	/**
	 * Parse the generic / specific rules for exclusion / inclusion of this popup.
	 *
	 * @param int   $post_id  The current post ID to render this popup on.
	 * @param array $rules    Array of rules for the inclusion / exclusion meta.
	 * @return boolean        Whether or not the current meta rules match for the current page and popup.
	 *
	 * @since 3.0.0-beta.1
	 */
	public function parse_popup_display_condition( $post_id, $rules ) {

		if ( empty( $rules['rule'] ) ) {
			return false;
		}

		$is_in_rule        = false;
		$current_post_type = get_post_type( $post_id );

		foreach ( $rules['rule'] as $key => $rule ) {
			if ( empty( $rule ) ) {
				continue;
			}

			if ( is_array( $rule ) && isset( $rule['value'] ) ) {
				$rule = $rule['value'];
			}

			$rule_case = ( strrpos( $rule, 'all' ) !== false ) ? 'all' : $rule;

			switch ( $rule_case ) {

				// If 'Basic --> All Singulars' is selected.
				case 'basic-singulars':
					if ( is_singular() ) {
						$is_in_rule = true;
					}
					break;

				// If 'Basic --> All Archives' is selected.
				case 'basic-archives':
					if ( is_archive() ) {
						$is_in_rule = true;
					}
					break;

				// If 'Special Pages --> 404 Page' is selected.
				case 'special-404':
					if ( is_404() ) {
						$is_in_rule = true;
					}
					break;

				// If 'Special Pages --> Search Page' is selected.
				case 'special-search':
					if ( is_search() ) {
						$is_in_rule = true;
					}
					break;

				// If 'Special Pages --> Blog / Post Page' is selected.
				case 'special-blog':
					if ( is_home() ) {
						$is_in_rule = true;
					}
					break;

				// If 'Special Pages --> Front Page' is selected.
				case 'special-front':
					if ( is_front_page() ) {
						$is_in_rule = true;
					}
					break;

				// If 'Special Pages --> Date Archive' is selected.
				case 'special-date':
					if ( is_date() ) {
						$is_in_rule = true;
					}
					break;

				// If 'Special Pages --> Author Archive' is selected.
				case 'special-author':
					if ( is_author() ) {
						$is_in_rule = true;
					}
					break;

				// If 'Special Pages --> WooCommerce Shop Page' is selected.
				case 'special-woo-shop':
					if ( function_exists( 'is_shop' ) && is_shop() ) {
						$is_in_rule = true;
					}
					break;

				// If '[postTypes] --> All [postTypes|taxonomy|archive|etc]' is selected.
				case 'all':
					
					// First split apart the rule to determine the depth of this rule.
					$rule_data = explode( '|', $rule );

					// Then set the depth to check if this page needs this popup.
					// The depth is as follows: postType | 'all' | archiveType | taxonomy.
					$rule_post_type    = isset( $rule_data[0] ) ? $rule_data[0] : false;
					$rule_archive_type = isset( $rule_data[2] ) ? $rule_data[2] : false;
					$rule_taxonomy     = isset( $rule_data[3] ) ? $rule_data[3] : false;

					// Check if this rule was not for an archive type.
					if ( false === $rule_archive_type ) {

						// Since this is not an archive type, check if the post ID is valid and the rule matches the current post type.
						if ( $post_id && $current_post_type === $rule_post_type ) {
							$is_in_rule = true;
						}
						
						break;
					}

					// Check if the current page is not an archive.
					if ( is_archive() ) {
						break;
					}

					// Since this is an archive, get the post type without an ID.
					$current_post_type = get_post_type();

					// Check if the current post type is not the post type in the rule.
					if ( $current_post_type !== $rule_post_type ) {
						break;
					}

					// Check what kind of archive this is.
					switch ( $rule_archive_type ) {

						case 'archive':
							$is_in_rule = true;
							break;

						case 'taxarchive':
							$current_query_obj = get_queried_object();
							
							if ( null === $current_query_obj || ! isset( $current_query_obj->taxonomy ) ) {
								break;
							}

							$current_taxonomy = $current_query_obj->taxonomy;

							if ( $current_taxonomy === $rule_taxonomy ) {
								$is_in_rule = true;
							}
							break;
					}
					break;

				// If 'Specific Target --> Specific Pages / Posts / Taxonomies' is selected.
				case 'specifics':
					// Continue only if this rule has a list of speficic targets.
					if ( ! isset( $rules['specific'] ) || ! is_array( $rules['specific'] ) ) {
						break;
					}

					foreach ( $rules['specific'] as $specific_page ) {

						$specific_data = explode( '-', $specific_page );

						$specific_post_type = isset( $specific_data[0] ) ? $specific_data[0] : false;
						$specific_post_id   = isset( $specific_data[1] ) ? (int) $specific_data[1] : 0;
						$specific_single    = isset( $specific_data[2] ) ? $specific_data[2] : false;

						// Check what kind of post this is.
						switch ( $specific_post_type ) {

							case 'post':
								if ( $specific_post_id === $post_id ) {
									$is_in_rule = true;
								}
								break;

							case 'tax':
								if ( 'single' === $specific_single && is_singular() ) {
									$term_details = get_term( $specific_post_id );
									if ( isset( $term_details->taxonomy ) ) {
										$has_term = has_term( $specific_post_id, $term_details->taxonomy, $post_id );
										if ( $has_term ) {
											$is_in_rule = true;
										}
									}
								} else {
									$tax_id = get_queried_object_id();
									if ( $specific_post_id === $tax_id ) {
										$is_in_rule = true;
									}
								}
								break;
						}//end switch
					}//end foreach
					break;
			}//end switch

			if ( $is_in_rule ) {
				break;
			}
		}//end foreach

		return $is_in_rule;
	}

	/**
	 * Add Pro Meta Based Conditions to JS Popup Builder Block.
	 *
	 * @param string $js              The current block JS script.
	 * @param int    $id              The current block ID.
	 * @param array  $attr            The current block attributes.
	 * @param bool   $is_push_banner  A boolean stating if this is a push banner or not.
	 * @param int    $popup_timer     The timer of the current popup based on if it's a push banner.
	 * @return string                 The upgraded JS script or the Default JS Script.
	 *
	 * @since 1.0.0
	 */
	public function upgrade_frontend_js( $js, $id, $attr, $is_push_banner, $popup_timer ) {
		$popup_id = $id;
		if ( ! $popup_id ) {
			return $js;
		}
		$trigger = get_post_meta( $popup_id, 'spectra-popup-trigger', true );
		
		if ( ! is_string( $trigger ) ) {
			return $js;
		}

		$trigger_delay = get_post_meta( $popup_id, 'spectra-popup-trigger-delay', true );
		if ( ! $trigger_delay ) {
			$trigger_delay = 0;
		}

		// Convert the Seconds to Milliseconds.
		$trigger_delay *= 1000;

		ob_start();

		switch ( $trigger ) {
			case 'load':
				?>
					window.addEventListener( 'DOMContentLoaded', () => {
						
						const blockScope = document.getElementById('spectra-popup-builder-<?php echo esc_attr( strval( $popup_id ) ); ?>');
						if ( ! blockScope ) {
							return;
						}
						<?php
							// The front-end JS common responsive code snippet cannot be escaped.
							echo $this->frontend_js_responsive_snippet(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The output buffer is escaped.
						?>

						<?php
							// The front-end JS common repetition code snippet cannot be escaped.
							echo $this->frontend_js_repetition_snippet( $popup_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The args passed to the function is already escaped.
						?>

						const theBody = document.querySelector( 'body' );

						setTimeout( () => {
							blockScope.style.display = 'flex';
						}, <?php echo intval( $trigger_delay ); ?> );
						setTimeout( () => {
							<?php
								// The front-end JS common load code snippet cannot be escaped.
								echo $this->frontend_js_load_snippet( $attr, $is_push_banner ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The args passed to the function is already escaped.
							?>
						}, <?php echo intval( $trigger_delay ) + 100; ?> );

						<?php
							// The front-end JS common close code snippet cannot be escaped.
							echo $this->frontend_js_close_snippet( $attr, $popup_id, true, $is_push_banner, $popup_timer, $trigger_delay ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The args passed to the function is already escaped.
						?>
					} );
				<?php
				break;
			case 'exit':
				?>
					window.addEventListener( 'DOMContentLoaded', () => {
						const exitIntent = ( event ) => {
							if ( ! event.toElement && ! event.relatedTarget ) {
								document.removeEventListener( 'mouseout', exitIntent );
								
								const blockScope = document.getElementById('spectra-popup-builder-<?php echo esc_attr( strval( $popup_id ) ); ?>');
								
								if ( ! blockScope ) {
									return;
								}
								<?php
									// The front-end JS common responsive code snippet cannot be escaped.
									echo $this->frontend_js_responsive_snippet(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								?>

								<?php
									// The front-end JS common repetition code snippet cannot be escaped.
									echo $this->frontend_js_repetition_snippet( $popup_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The args passed to the function is already escaped.
								?>

								const theBody = document.querySelector( 'body' );

								blockScope.style.display = 'flex';
								setTimeout( () => {
									<?php
										// The front-end JS common load code snippet cannot be escaped.
										echo $this->frontend_js_load_snippet( $attr, $is_push_banner ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The args passed to the function is already escaped.
									?>
								}, 100 );

								<?php
									// The front-end JS common close code snippet cannot be escaped.
									echo $this->frontend_js_close_snippet( $attr, $popup_id, true, $is_push_banner, $popup_timer, 0 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The args passed to the function is already escaped.
								?>
							}
						}
						document.addEventListener( 'mouseout', exitIntent );
					} );
				<?php
				break;
			case 'element':
				?>
					window.addEventListener( 'DOMContentLoaded', () => {
						const popupTriggers = document.querySelectorAll( '.spectra-popup-trigger-<?php echo esc_attr( strval( $popup_id ) ); ?>' );
						for ( let i = 0; i < popupTriggers.length; i++ ) {
							popupTriggers[ i ].style.cursor = 'pointer';
							popupTriggers[ i ].addEventListener( 'click', () => {
								const blockScope = document.getElementById('spectra-popup-builder-<?php echo esc_attr( strval( $popup_id ) ); ?>');
								if ( ! blockScope ) {
									return;
								}
								<?php
									// The front-end JS common responsive code snippet cannot be escaped.
									echo $this->frontend_js_responsive_snippet(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								?>

								const theBody = document.querySelector( 'body' );

								blockScope.style.display = 'flex';
								setTimeout( () => {
									<?php
										// The front-end JS common load code snippet cannot be escaped.
										echo $this->frontend_js_load_snippet( $attr, $is_push_banner ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The args passed to the function is already escaped.
									?>
								}, 100 );

								<?php
									// The front-end JS common close code snippet cannot be escaped.
									echo $this->frontend_js_close_snippet( $attr, $popup_id, false, $is_push_banner, $popup_timer, 0 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The args passed to the function is already escaped.
								?>
							} );
						}
					} );
				<?php
				break;
			default:
				// The block of JS code sent to this acction cannot be escaped.
				echo $js; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				break;
		}//end switch

		$updated_js = ob_get_clean();

		return is_string( $updated_js ) ?  $updated_js : $js;
	}

	/**
	 * Snippet of the responsive handling for all pro JS renders.
	 *
	 * @return string  The output buffer.
	 *
	 * @since 1.0.0
	 */
	private function frontend_js_responsive_snippet() {
		ob_start();
		?>
			const deviceWidth = ( window.innerWidth > 0 ) ? window.innerWidth : screen.width;
			if ( blockScope.classList.contains( 'uag-hide-desktop' ) && deviceWidth > 1024 ) {
				blockScope.remove();
				return;
			} else if ( blockScope.classList.contains( 'uag-hide-tab' ) && ( deviceWidth <= 1024 && deviceWidth > 768 ) ) {
				blockScope.remove();
				return;
			} else if ( blockScope.classList.contains( 'uag-hide-mob' ) && deviceWidth <= 768 ) {
				blockScope.remove();
				return;
			}
		<?php
		$output = ob_get_clean();
		
		return is_string( $output ) ? $output : '';
	}

	/**
	 * Snippet of common repetition JS code.
	 *
	 * @param int $popup_id  The popup ID.
	 * @return string        The output buffer.
	 *
	 * @since 1.0.0
	 */
	private function frontend_js_repetition_snippet( $popup_id ) {
		// Either check if the localStorage has been set before - If not, create it.
		// Or if this popup has an updated repetition number, reset the localStorage.
		$repetition = get_post_meta( $popup_id, 'spectra-popup-repetition', true );
		if ( ! is_numeric( $repetition ) ) {
			return '';
		}
		ob_start();
		?>
		let popupSesh = JSON.parse( localStorage.getItem( 'spectraPopup<?php echo esc_attr( strval( $popup_id ) ); ?>' ) );
		const repetition = <?php echo intval( $repetition ); ?>;
		if ( null === popupSesh || repetition !== popupSesh[1] ) {
			<?php // [0] is the updating repetition number, [1] is the original repetition number. ?>
			const repetitionArray = [
				repetition,
				repetition,
			];
			localStorage.setItem( 'spectraPopup<?php echo esc_attr( strval( $popup_id ) ); ?>', JSON.stringify( repetitionArray ) );
			popupSesh = JSON.parse( localStorage.getItem( 'spectraPopup<?php echo esc_attr( strval( $popup_id ) ); ?>' ) );
		}

		if ( 0 === popupSesh[0] ) {
			blockScope.remove();
			return;
		}
		<?php
		$output = ob_get_clean();
		return is_string( $output ) ? $output : '';
	}

	/**
	 * Snippet of common close JS function and calls required for all popups.
	 *
	 * @param array $attr            The array of block attributes.
	 * @param int   $popup_id        The popup ID.
	 * @param bool  $delete          Determines whether or not the popup should be deleted when closed.
	 * @param bool  $is_push_banner  A boolean stating if this is a push banner or not.
	 * @param int   $popup_timer     The timer of the current popup based on if it's a push banner.
	 * @param int   $trigger_delay   The delay for on load popups, or zero for other popups.
	 * @return string                The output buffer.
	 *
	 * @since 1.0.0
	 */
	private function frontend_js_close_snippet( $attr, $popup_id, $delete, $is_push_banner, $popup_timer, $trigger_delay ) {
		ob_start();
		// If this is a banner with push, Add the unset bezier curve after animating.
		if ( $is_push_banner ) :
			?>
			setTimeout( () => {
				blockScope.style.transition = 'max-height 0.5s cubic-bezier(0, 1, 0, 1)';
			}, <?php echo intval( $trigger_delay ) + 600; ?> );
		<?php endif; ?>
			const closePopup = ( event = null ) => {
				if ( event && blockScope !== event.target ) {
					return;
				}
				<?php
					// If this is a banner with push, render the required animation instead of opacity.
				if ( $is_push_banner ) :
					?>
					blockScope.style.maxHeight = '';
				<?php else : ?>
					blockScope.style.opacity = 0;
				<?php endif; ?>
				setTimeout( () => {
					<?php
						// If this is a banner with push, remove the unset bezier curve.
					if ( $is_push_banner ) :
						?>
						blockScope.style.transition = '';
					<?php endif; ?>
					<?php if ( $delete ) : ?>
						if ( popupSesh[0] > 0 ) {
							popupSesh[0] -= 1;
							localStorage.setItem( 'spectraPopup<?php echo esc_attr( strval( $popup_id ) ); ?>', JSON.stringify( popupSesh ) );
						}
						blockScope.remove();
					<?php else : ?>
						blockScope.style.display = 'none';
						blockScope.classList.remove( 'spectra-popup--open' );
					<?php endif; ?>
					const allActivePopups = document.querySelectorAll( 'spectra-popup-builder.spectra-popup--open' );
					if ( 0 === allActivePopups.length ) {
						theBody.classList.remove( 'spectra-popup-builder__body--overflow-hidden' );
					}
				}, <?php echo intval( $popup_timer ); ?> );
			};

			<?php
			if ( ! empty( $attr['isDismissable'] ) ) :
				if ( ! empty( $attr['hasOverlay'] ) && ! empty( $attr['closeOverlayClick'] ) ) :
					?>
					blockScope.addEventListener( 'click', ( event ) => closePopup( event ) );
					<?php
					endif;
				if ( ! empty( $attr['closeIcon'] ) ) :
					?>
					const closeButton = blockScope.querySelector( '.spectra-popup-builder__close' );
					closeButton.style.cursor = 'pointer';
					closeButton.addEventListener( 'click', () => closePopup() );
					closeButton.addEventListener( 'keydown', ( event ) => {
						if ( 13 === event.keyCode || 32 === event.keyCode ) {
							event.preventDefault();
							closePopup();
						}
					} );
					<?php
					endif;
				if ( ! empty( $attr['closeEscapePress'] ) && ! empty( $attr['haltBackgroundInteraction'] ) && ! empty( $attr['variantType'] ) && 'popup' === $attr['variantType'] ) :
					?>
					document.addEventListener( 'keyup', ( event ) => {
						if ( 27 === event.keyCode && blockScope.classList.contains( 'spectra-popup--open' ) ) {
							return closePopup();
						}
					} );
					<?php
					endif;
				endif;
			?>

			const closingElements = blockScope.querySelectorAll( '.spectra-popup-close-<?php echo esc_attr( strval( $popup_id ) ); ?>' );
			for ( let i = 0; i < closingElements.length; i++ ) {
				closingElements[ i ].style.cursor = 'pointer';
				closingElements[ i ].addEventListener( 'click', () => closePopup() );
			}
		<?php
		$output = ob_get_clean();
		return is_string( $output ) ? $output : '';
	}

	/**
	 * Snippet of common scrollbar hide and push banner JS code on load.
	 *
	 * @param array $attr            The array of block attributes.
	 * @param bool  $is_push_banner  A boolean stating if this is a push banner or not.
	 * @since 1.0.1
	 * @return string                The output buffer.
	 */
	private function frontend_js_load_snippet( $attr, $is_push_banner ) {
		ob_start();
		// If this is a banner with push, render the max height instead of opacity on timeout.
		if ( $is_push_banner ) {
			?>
				blockScope.style.maxHeight = '100vh';
			<?php
		} else {
			// If this is a popup which prevent background interaction, hide the scrollbar.
			if ( 'popup' === $attr['variantType'] && $attr['haltBackgroundInteraction'] ) :
				?>
				theBody.classList.add( 'spectra-popup-builder__body--overflow-hidden' );
				blockScope.classList.add( 'spectra-popup--open' );
				<?php // Focus management for accessibility ?>
				const closeButton = blockScope.querySelector( '.spectra-popup-builder__close' );
				if ( closeButton ) {
					closeButton.focus();
				} else {
					<?php // Fallback: create a focusable element to add focus onto the popup and then remove it ?>
					const focusElement = document.createElement( 'button' );
					focusElement.style.position = 'absolute';
					focusElement.style.opacity = '0';
					const popupFocus = blockScope.insertBefore( focusElement, blockScope.firstChild );
					popupFocus.focus();
					popupFocus.remove();
				}
			<?php endif; ?>
			blockScope.style.opacity = 1;
			<?php
		}//end if
		$output = ob_get_clean();

		return is_string( $output ) ? $output : '';
	}
}
