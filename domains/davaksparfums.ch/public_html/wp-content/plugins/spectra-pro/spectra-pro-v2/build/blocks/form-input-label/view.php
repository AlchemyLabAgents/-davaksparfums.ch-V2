<?php
/**
 * View for rendering the Form Input Label block.
 * 
 * @since 2.0.0
 *
 * @package SpectraPro\Blocks\FormInputLabel
 */

use Spectra\Helpers\HtmlSanitizer;

?>
<label
	<?php echo wp_kses_data( $data['wrapper_attributes'] ); ?>
>
	<?php echo esc_html( $data['label_text'] ); ?>
	<?php if ( $data['is_required'] ) : ?>
		<span class="spectra-pro-form-input-label__required">
			<?php echo esc_html( $data['required_indicator'] ); ?>
		</span>
	<?php endif; ?>
</label>
