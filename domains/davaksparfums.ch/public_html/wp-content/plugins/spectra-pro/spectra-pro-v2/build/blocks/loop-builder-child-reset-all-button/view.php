<?php
/**
 * View for rendering the block.
 * 
 * @since 2.0.0-beta.1
 *
 * @package Spectra\Blocks\LoopBuilderChildResetAllButton
 */

use Spectra\Helpers\Renderer;

?>

<a <?php echo wp_kses_data( $wrapper_attributes ); ?> data-wp-on--click="spectra-pro/loop-builder::actions.clearFilters">
	<?php if ( $icon && 'before' === $icon_position ) : ?>
		<?php Renderer::svg_html( $icon, $flip_for_rtl, $icon_props ); ?>
	<?php endif; ?>

	<?php echo wp_kses_post( $btn_content ); ?>

	<?php if ( $icon && 'after' === $icon_position ) : ?>
		<?php Renderer::svg_html( $icon, $flip_for_rtl, $icon_props ); ?>
	<?php endif; ?>
</a>
