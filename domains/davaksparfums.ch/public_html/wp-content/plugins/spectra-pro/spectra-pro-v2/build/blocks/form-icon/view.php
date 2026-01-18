<?php
/**
 * View for rendering the block.
 * 
 * @since 3.0.0-beta.1
 *
 * @package Spectra\Blocks\Icon
 */

use Spectra\Helpers\Renderer;

?>
<div
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
>
	<?php Renderer::svg_html( $icon, $attributes['flipForRTL'], $icon_props ); ?>
</div>
