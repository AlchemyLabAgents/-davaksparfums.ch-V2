<?php
/**
 * View for rendering the block.
 * 
 * @since 2.0.0-beta.1
 *
 * @package SpectraPro\Blocks\LoopBuilderChildNoResults
 */
?>
<div <?php echo wp_kses_data( $data['wrapper_attributes'] ); ?>>
	<input 
		type="search" 
		<?php echo ! empty( $data['class'] ) ? 'class="' . esc_attr( $data['class'] ) . '"' : ''; ?>
		placeholder="<?php echo esc_attr( $data['search_placeholder'] ); ?>"
		data-wp-key="loop-builder-child-query-search-<?php echo esc_attr( $data['query_id'] ); ?>"
		data-wp-bind--value="spectra-pro/loop-builder::context.search"
		data-wp-on--input="spectra-pro/loop-builder::actions.onSearchInput"
		aria-label="<?php esc_attr_e( 'Search input', 'spectra-pro' ); ?>"
		role="search" 
		<?php echo ! empty( $data['style'] ) ? 'style="' . esc_attr( $data['style'] ) . '"' : ''; ?>
	/>
</div>
