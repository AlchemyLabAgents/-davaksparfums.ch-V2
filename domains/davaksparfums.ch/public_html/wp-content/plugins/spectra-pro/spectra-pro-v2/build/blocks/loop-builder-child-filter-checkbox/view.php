<?php
/**
 * View for rendering the block.
 * 
 * @since 2.0.0-beta.1
 *
 * @package SpectraPro\Blocks\LoopBuilderChildFilterCheckbox
 */

?>

<div <?php echo wp_kses_data( $data['wrapper_attributes'] ); ?>>
	<?php 
	foreach ( $data['terms'] as $t ) : 
		$display_text   = esc_html( $t->name ) . ( $show_post_count ? " ({$t->count})" : '' );
		$combined_value = esc_attr( $taxonomy_type . '|' . $t->term_id );
		?>

		<label>
			<input 
				type="checkbox"
				name="spectra-term-filter[]"
				value="<?php echo esc_attr( $combined_value ); ?>"
				data-wp-init="spectra-pro/loop-builder::callbacks.initFiltersInput"
				data-wp-on--change="spectra-pro/loop-builder::actions.onFiltersInput"
			/>
			<?php echo esc_html( $display_text ); ?>
		</label>
	<?php endforeach; ?>
</div>
