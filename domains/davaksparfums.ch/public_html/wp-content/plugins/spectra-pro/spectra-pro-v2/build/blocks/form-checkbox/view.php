<?php
/**
 * View for rendering the block.
 *
 * @since 2.0.0
 *
 * @package SpectraPro\Blocks\FormCheckbox
 */

?>
<div
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
>
	<div class="spectra-pro-form-checkbox__wrapper">
		<label
			for="<?php echo esc_attr( $checkbox_id ); ?>"
			id="label-<?php echo esc_attr( $checkbox_type ); ?>-<?php echo esc_attr( $block_id ); ?>"
			class="spectra-pro-form-checkbox__label"
		>
			<input
				type="checkbox"
				id="<?php echo esc_attr( $checkbox_id ); ?>"
				name="<?php echo esc_attr( $checkbox_name ); ?>"
				class="spectra-pro-form-checkbox__input"
				value="1"
				aria-describedby="label-<?php echo esc_attr( $checkbox_type ); ?>-<?php echo esc_attr( $block_id ); ?>"
				data-wp-on--change="actions.onCheckboxChange"
			/>
			<span class="spectra-pro-form-checkbox__checkmark"></span>
			<span class="spectra-pro-form-checkbox__text">
				<?php echo wp_kses_post( $checkbox_label ); ?>
			</span>
		</label>
	</div>
</div>
