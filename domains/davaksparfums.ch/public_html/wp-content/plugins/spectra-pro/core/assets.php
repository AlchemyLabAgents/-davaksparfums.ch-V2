<?php
namespace SpectraPro\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SpectraPro\Includes\Extensions\PopupBuilder\Spectra_Pro_Popup_Builder;

/**
 * Assets
 *
 * @package spectra-pro
 * @since 1.0.0
 */
class Assets {

	/**
	 * Micro Constructor
	 */
	public static function init() {
		$self = new self();
		add_action( 'enqueue_block_editor_assets', array( $self, 'block_editor_assets' ) );
		add_action( 'enqueue_block_assets', array( $self, 'block_assets' ) );
		add_action( 'spectra_localize_pro_block_ajax', array( $self, 'localize_pro_block_ajax' ) );

		if ( version_compare( get_bloginfo( 'version' ), '5.8', '>=' ) ) {
			add_filter( 'block_categories_all', array( $self, 'register_block_category' ), 999999, 2 );
		} else {
			add_filter( 'block_categories', array( $self, 'register_block_category' ), 999999, 2 );
		}
	}

	/**
	 * Enqueue the Pro localize Scripts.
	 *
	 * @since 1.1.4
	 * @return void
	 */
	public function enqueue_pro_localize_scripts() {
		$localize = array(
			'cannot_be_blank' => esc_html__( 'cannot be blank.', 'spectra-pro' ),
			'first_name'      => esc_html__( 'First Name', 'spectra-pro' ),
			'last_name'       => esc_html__( 'Last Name', 'spectra-pro' ),
			'this_field'      => esc_html__( 'This field', 'spectra-pro' ),
		);
		wp_localize_script( 'uagb-register-js', 'uagb_register_js', $localize );
	}

	/**
	 * Gutenberg block category for Spectra Pro.
	 *
	 * @param array  $categories Block categories.
	 * @param object $post Post object.
	 * @since 1.0.0
	 */
	public function register_block_category( $categories, $post ) {
		return array_merge(
			array(
				array(
					'slug'  => 'spectra-pro',
					'title' => __( 'Spectra Pro', 'spectra-pro' ),
				),
			),
			$categories
		);
	}

	/**
	 * Check if current user is an administrator.
	 *
	 * @since x.x.x
	 *
	 * @return bool True if user is admin, false otherwise.
	 */
	private function is_current_user_admin() {
		$current_user = wp_get_current_user();
		
		return in_array( 'administrator', $current_user->roles, true );
	}

	/**
	 * Get the localized variables for pro blocks.
	 *
	 * Returns the complete array of variables needed by Spectra Pro blocks.
	 * This includes essential variables for v3 blocks and additional ones for v2.
	 *
	 * @since x.x.x
	 *
	 * @param bool $include_v2_only Whether to include v2-only variables. Default true.
	 * @return array Localized variables array.
	 */
	private function get_pro_blocks_localized_vars( $include_v2_only = true ) {
		$user_is_admin = $this->is_current_user_admin();

		// Build array of variables needed by pro blocks.
		$localized_vars = array(
			'spectra_pro_url'       => SPECTRA_PRO_URL,
			'ajax_url'              => admin_url( 'admin-ajax.php' ),
			'ajax_nonce'            => wp_create_nonce( 'spectra_pro_ajax_nonce' ),
			'current_post_id'       => get_the_ID(),
			'is_allow_registration' => (bool) get_option( 'users_can_register' ),
			'login_url'             => esc_url( wp_login_url( home_url() ) ),
			'admin_block_settings'  => admin_url( 'admin.php?page=spectra&path=settings&settings=block-settings' ),
			'anyone_can_register'   => admin_url( 'options-general.php#users_can_register' ),
			'enableDynamicContent'  => apply_filters( 'enable_dynamic_content', \UAGB_Admin_Helper::get_admin_settings_option( 'uag_enable_dynamic_content', 'enabled' ) ),
			'dynamic_content_mode'  => \UAGB_Admin_Helper::get_admin_settings_option( 'uag_dynamic_content_mode', 'popup' ),
			'display_rules'         => Spectra_Pro_Popup_Builder::get_location_selections(),
			'user_can_adjust_role'  => apply_filters( 'spectra_pro_registration_form_role_manager', $user_is_admin ),
			'post_excerpt_length'   => apply_filters( 'uagb_loop_excerpt_length', 20 ),
		);

		// Add v2-only variables if needed (for backward compatibility).
		if ( $include_v2_only ) {
			$localized_vars = array_merge(
				array( 'category' => 'spectra-pro' ),
				$localized_vars,
				array( 'uag_enable_gbs_extension' => \UAGB_Admin_Helper::get_admin_settings_option( 'uag_enable_gbs_extension', 'enabled' ) )
			);
		}

		return $localized_vars;
	}

	/**
	 * Enqueue essential variables for v3 pro blocks when full v2 assets are not loaded.
	 *
	 * This method provides only the essential variables without loading heavy v2 assets.
	 * It creates a lightweight script and localizes all needed configuration variables.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	private function enqueue_minimal_v3_pro_block_assets() {
		// Enqueue minimal script for localization.
		wp_enqueue_script(
			'spectra-pro-essential-vars',
			'data:text/javascript;base64,' . base64_encode( '/* Spectra Pro Essential Variables */' ),
			array( 'wp-blocks' ),
			SPECTRA_PRO_VER,
			true
		);

		// Get and localize variables (without v2-only variables).
		$localized_vars = $this->get_pro_blocks_localized_vars( false );
		wp_localize_script( 'spectra-pro-essential-vars', 'spectra_pro_blocks_info', $localized_vars );
	}

	/**
	 * Enqueue Gutenberg block assets for backend editor.
	 *
	 * @Hooked - enqueue_block_editor_assets
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function block_editor_assets() {

		// Check if assets should be excluded for the current post type.
		if ( \UAGB_Admin_Helper::should_exclude_assets_for_cpt() ) {
			return; // Early return to prevent loading assets.
		}

		$should_load_full_assets = \UAGB_Helper::is_old_user_less_than_v3() || \UAGB_Helper::is_v2_blocks_enabled();

		// If not loading full assets, provide minimal assets for v3 pro blocks.
		if ( ! $should_load_full_assets ) {
			$this->enqueue_minimal_v3_pro_block_assets();
			return;
		}

		// Load full v2 assets (styles and scripts).
		wp_enqueue_style(
			'spectra-pro-block-css', // Handle.
			SPECTRA_PRO_URL . 'dist/style-blocks.css', // Block style CSS.
			array(),
			SPECTRA_PRO_VER
		);

		$script_dep_path = SPECTRA_PRO_DIR . 'dist/blocks.asset.php';
		$script_info     = file_exists( $script_dep_path )
			? include $script_dep_path
			: array(
				'dependencies' => array(),
				'version'      => SPECTRA_PRO_VER,
			);
		$script_dep      = array_merge( $script_info['dependencies'], array( 'wp-blocks', 'wp-i18n', 'uagb-block-editor-js' ) );

		// Scripts.
		wp_enqueue_script(
			'spectra-pro-block-editor-js', // Handle.
			SPECTRA_PRO_URL . 'dist/blocks.js',
			$script_dep, // Dependencies, defined above.
			$script_info['version'], // UAGB_VER.
			true // Enqueue the script in the footer.
		);
		wp_set_script_translations( 'spectra-pro-block-editor-js', 'spectra-pro', SPECTRA_PRO_DIR . 'languages' );

		// Get and localize variables (with v2-only variables for backward compatibility).
		$localized_vars = $this->get_pro_blocks_localized_vars( true );
		wp_localize_script( 'spectra-pro-block-editor-js', 'spectra_pro_blocks_info', $localized_vars );
	}

	/**
	 * Enqueue Gutenberg block assets for frontend.
	 *
	 * @Hooked - enqueue_block_assets
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function block_assets() {

		// Check if assets should be excluded for the current post type.
		if ( \UAGB_Admin_Helper::should_exclude_assets_for_cpt() ) {
			return; // Early return to prevent loading assets.
		}
		wp_enqueue_style(
			'spectra-pro-block-css', // Handle.
			SPECTRA_PRO_URL . 'dist/style-blocks.css', // Block style CSS.
			array(),
			SPECTRA_PRO_VER
		);

	}

	/**
	 * Extend Core Front-end Dynamic Block Asset Localization.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function localize_pro_block_ajax() {

		// AJAX for Instagram Feed Block.
		$spectra_pro_instagram_masonry_ajax_nonce         = wp_create_nonce( 'spectra_pro_instagram_masonry_ajax_nonce' );
		$spectra_pro_instagram_grid_pagination_ajax_nonce = wp_create_nonce( 'spectra_pro_instagram_grid_pagination_ajax_nonce' );
		wp_localize_script(
			'uagb-instagram-feed-js',
			'spectra_pro_instagram_media',
			array(
				'ajax_url'                                 => admin_url( 'admin-ajax.php' ),
				'spectra_pro_instagram_masonry_ajax_nonce' => $spectra_pro_instagram_masonry_ajax_nonce,
				'spectra_pro_instagram_grid_pagination_ajax_nonce' => $spectra_pro_instagram_grid_pagination_ajax_nonce,
			)
		);
		$this->enqueue_pro_localize_scripts();
	}
}
