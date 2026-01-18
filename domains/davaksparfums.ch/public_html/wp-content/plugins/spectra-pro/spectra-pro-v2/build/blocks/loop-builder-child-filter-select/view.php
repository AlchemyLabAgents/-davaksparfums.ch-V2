<?php
/**
 * View for rendering the block.
 * 
 * @since 2.0.0-beta.1
 *
 * @package SpectraPro\Blocks\LoopBuilderChildFilterSelect
 */

?>

<div <?php echo wp_kses_data( $data['wrapper_attributes'] ); ?>>
	<select 
		data-wp-bind--value="spectra-pro/loop-builder::context.filter"
		data-wp-on--change="spectra-pro/loop-builder::actions.onFilterInput"
		<?php echo ! empty( $data['class'] ) ? 'class="' . esc_attr( $data['class'] ) . '" ' : ''; ?>
		<?php echo ! empty( $data['style'] ) ? 'style="' . esc_attr( $data['style'] ) . '" ' : ''; ?>
	>
		<?php if ( ! empty( $data['placeholder'] ) ) : ?>
			<option value="" disabled selected><?php echo esc_html( $data['placeholder'] ); ?></option>
		<?php endif; ?>
		<option value=""><?php echo esc_html__( 'All', 'spectra-pro' ); ?></option>
		<?php foreach ( $data['terms'] as $t ) : ?>
			<option value="<?php echo esc_attr( $data['taxonomy_type'] . '|' . $t->term_id ); ?>">
				<?php echo esc_html( $t->name . ( $data['show_post_count'] ? ' (' . $t->count . ')' : '' ) ); ?>
			</option>
		<?php endforeach; ?>
	</select>
</div>
