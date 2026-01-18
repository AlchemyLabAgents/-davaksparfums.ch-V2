<?php
/**
 * Class to manage SpectraPro Blocks.
 *
 * @package SpectraPro
 */

namespace SpectraPro;

use RuntimeException;
use Spectra\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class to manage Spectra Blocks.
 *
 * @since 2.0.0-beta.1
 */
class BlockManager {

	use Singleton;

	/**
	 * Initialize the block manager by registering all block types and
	 * adding a block category.
	 *
	 * @since 2.0.0-beta.1
	 * 
	 * @return void
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_filter( 'block_categories_all', array( $this, 'add_block_category' ), 99999999 );
	}
	
	/**
	 * Registers all block types defined in block.json files located within the build/blocks directory.
	 *
	 * Utilizes the WordPress function `register_block_type_from_metadata` to register each block
	 * by its metadata specified in the block.json file.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @throws RuntimeException If the blocks directory is invalid or inaccessible.
	 * @return void
	 */
	public function register_blocks() {
		$blocks_dir = SPECTRA_PRO_2_DIR . 'build/blocks/';

		if ( ! is_dir( $blocks_dir ) || ! is_readable( $blocks_dir ) ) {                
			throw new RuntimeException( sprintf( 'Invalid or inaccessible blocks directory: %s', $blocks_dir ) );
		}

		$block_files = glob( $blocks_dir . '**/block.json' );

		if ( false === $block_files ) {
			return;
		}


		if ( ! empty( $block_files ) ) {
			foreach ( $block_files as $block_file ) {
				register_block_type_from_metadata( $block_file );
			}
		}
	}
	
	/**
	 * Adds a custom block category named "Spectra 3" and appends it to the list of existing categories.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @param array $categories The list of registered block categories.
	 * @return array The updated list of block categories.
	 */
	public function add_block_category( $categories ) {
		$slugs = wp_list_pluck( $categories, 'slug' );

		if ( ! in_array( 'spectra-pro-v2', $slugs, true ) ) {
			array_unshift( $categories, $this->get_spectra_block_category() );
		}

		return $categories;
	}

	/**
	 * Private methods are here.
	 *
	 * These methods are all used internally by the class and should not be
	 * accessed directly.
	 */

	/**
	 * Retrieves the block category for Spectra 3 blocks.
	 *
	 * @since 2.0.0-beta.1
	 *
	 * @return array The block category configuration.
	 */
	private function get_spectra_block_category() {
		return array(
			'slug'  => 'spectra-pro-v2',
			'title' => __( 'Spectra Pro', 'spectra-pro' ),
			'icon'  => 'superhero',
		);
	}
}
