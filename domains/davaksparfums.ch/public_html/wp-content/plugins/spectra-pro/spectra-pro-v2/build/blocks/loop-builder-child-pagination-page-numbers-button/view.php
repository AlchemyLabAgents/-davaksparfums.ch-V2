<?php
/**
 * View for rendering the block.
 * 
 * @since 2.0.0-beta.1
 *
 * @package SpectraPro\Blocks\LoopBuilderChildPaginationPageNumbersButton
 */
?>

<a <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php echo wp_kses_post( $btn_content ); ?>
</a>

