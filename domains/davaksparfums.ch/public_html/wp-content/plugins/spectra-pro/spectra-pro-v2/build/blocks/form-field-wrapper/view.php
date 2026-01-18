<?php
/**
 * View for rendering the block.
 * 
 * @since 2.0.0
 * 
 * @package SpectraPro\Blocks\FormFieldWrapper
 */

use Spectra\Helpers\HtmlSanitizer;

?>
<<?php echo esc_attr( $tag_name ); ?>
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
>
	<?php HtmlSanitizer::render( $content ); ?>
</<?php echo esc_attr( $tag_name ); ?>>
