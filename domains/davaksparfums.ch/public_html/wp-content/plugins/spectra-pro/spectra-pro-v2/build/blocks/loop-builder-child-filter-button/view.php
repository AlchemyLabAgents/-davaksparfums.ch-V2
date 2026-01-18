<?php
/**
 * View for rendering the block.
 *
 * @since 2.0.0-beta.1
 * 
 * @package SpectraPro\Blocks\LoopBuilderChildFilterButton
 */
?>
<?php foreach ( $data['terms'] as $t ) : ?>
	<a 
		<?php echo wp_kses_data( $data['wrapper_attributes'] ); ?>
		data-wp-key="<?php echo esc_attr( $data['query_id'] ); ?>-index-<?php echo esc_attr( $t['id'] ); ?>"
		data-wp-on--click="spectra-pro/loop-builder::actions.navigate"
		data-wp-on-async--mouseenter="spectra-pro/loop-builder::actions.prefetch"
		href="<?php echo esc_url( $t['url'] ); ?>"
		target="_self"
		<?php echo $t['is_active'] ? 'data-is-active="true"' : ''; ?>
	>
		<div class="spectra-button__link">
			<?php echo wp_kses_post( $t['display_text'] ); ?>
		</div>
	</a>
<?php endforeach; ?>
