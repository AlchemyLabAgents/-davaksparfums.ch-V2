<?php
/**
 * View for rendering the block.
 * 
 * @since 2.0.0
 *
 * @package SpectraPro\Blocks\FormCheckbox
 */

?>
<<?php echo esc_attr( $tag_name ); ?>
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
>
	<div class="spectra-pro-form-checkbox__wrapper">
		<label 
			for="<?php echo esc_attr( $checkbox_id ); ?>"
			class="spectra-pro-form-checkbox__label"
		>
			<input
				type="checkbox"
				id="<?php echo esc_attr( $checkbox_id ); ?>"
				name="<?php echo esc_attr( $checkbox_name ); ?>"
				class="spectra-pro-form-checkbox__input"
				value="1"
				<?php if ( $is_required ) : ?>
					required="required"
				<?php endif; ?>
				data-wp-on--change="actions.onCheckboxChange"
			/>
			<span class="spectra-pro-form-checkbox__checkmark"></span>
			<span class="spectra-pro-form-checkbox__text">
				<?php echo wp_kses_post( $checkbox_label ); ?>
				<?php if ( $is_required ) : ?>
					<span class="spectra-pro-form-checkbox__required" aria-label="<?php esc_attr_e( 'required', 'spectra-pro' ); ?>">*</span>
				<?php endif; ?>
			</span>
		</label>
	</div>
</<?php echo esc_attr( $tag_name ); ?>>
