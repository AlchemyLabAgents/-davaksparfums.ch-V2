<?php
/**
 * View for rendering the block.
 * 
 * @since 2.0.0-beta.1
 *
 * @package SpectraPro\Blocks\LoopBuilderChildPagination
 */

use Spectra\Helpers\HtmlSanitizer;

?>
<nav <?php echo wp_kses_data( $data['wrapper_attributes'] ); ?>>
	<?php HtmlSanitizer::render( $data['content'] ); ?>
</nav>
