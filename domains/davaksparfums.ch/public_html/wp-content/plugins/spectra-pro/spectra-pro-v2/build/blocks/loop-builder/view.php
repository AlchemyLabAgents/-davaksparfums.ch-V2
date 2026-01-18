<?php
/**
 * View for rendering the block.
 * 
 * @since 2.0.0-beta.1
 *
 * @package SpectraPro\Blocks\LoopBuilderChildNoResults
 */

use Spectra\Helpers\HtmlSanitizer;

?>
<<?php echo esc_attr( $data['tag_name'] ); ?>
	<?php echo wp_kses_data( $data['wrapper_attributes'] ); ?>
	<?php echo wp_kses_data( wp_interactivity_data_wp_context( $data['loop_builder_contexts'], 'spectra-pro/loop-builder' ) ); ?>
	data-wp-interactive="spectra-pro/loop-builder"
	data-wp-router-region="query-<?php echo esc_attr( $data['query_id'] ); ?>"
	data-wp-key="<?php echo esc_attr( $data['query_id'] ); ?>"
>
	<?php HtmlSanitizer::render( $data['content'] ); ?>

</<?php echo esc_attr( $data['tag_name'] ); ?>>
