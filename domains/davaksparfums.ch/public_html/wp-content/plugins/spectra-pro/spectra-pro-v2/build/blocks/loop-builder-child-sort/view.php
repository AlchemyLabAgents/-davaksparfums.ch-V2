<?php
/**
 * View for rendering the block.
 * 
 * @since 2.0.0-beta.1
 *
 * @package SpectraPro\Blocks\LoopBuilderChildSort
 */

use Spectra\Helpers\HtmlSanitizer;

?>
<div <?php echo wp_kses_data( $data['wrapper_attributes'] ); ?>>
	<select name="<?php echo esc_attr( $data['sort_key'] ); ?>"
		<?php echo ! empty( $data['class'] ) ? 'class="' . esc_attr( $data['class'] ) . '"' : ''; ?>
		data-wp-bind--value="spectra-pro/loop-builder::context.sort"
		data-wp-on--change="spectra-pro/loop-builder::actions.onSortInput"
		<?php echo ! empty( $data['style'] ) ? 'style="' . esc_attr( $data['style'] ) . '"' : ''; ?>
		aria-label="<?php echo esc_attr__( 'Sort by', 'spectra-pro' ); ?>"
	>

		<?php HtmlSanitizer::render( $data['sort_options_html'] ); ?>

	</select>
</div>
