<?php
/**
 * View for rendering the block.
 * 
 * @since 2.0.0-beta.1
 * 
 * @package SpectraPro\Blocks\CountdownChildExpiryWrapper
 */

use Spectra\Helpers\HtmlSanitizer;
?>
<div
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
>
	<?php HtmlSanitizer::render( $content ); ?>
</div>
