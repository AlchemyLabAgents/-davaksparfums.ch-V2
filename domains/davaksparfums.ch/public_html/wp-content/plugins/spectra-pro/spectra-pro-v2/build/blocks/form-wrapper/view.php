<?php
/**
 * View for rendering the block.
 * 
 * @since 2.0.0
 * 
 * @package SpectraPro\Blocks\FormWrapper
 */

use Spectra\Helpers\HtmlSanitizer;

// Extract data from controller.
extract( $data );

?>
<div
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
>
	<?php HtmlSanitizer::render( $content ); ?>
</div>
