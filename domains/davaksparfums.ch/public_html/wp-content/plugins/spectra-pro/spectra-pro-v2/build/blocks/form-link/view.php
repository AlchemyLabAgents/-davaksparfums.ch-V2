<?php
/**
 * View for rendering the block.
 * 
 * @since 2.0.0
 *
 * @package SpectraPro\Blocks\FormLink
 */

?>
<<?php echo esc_attr( $tag_name ); ?>
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
>
	<div class="spectra-pro-form-link__wrapper">
		<a
			href="<?php echo esc_url( $link_url ); ?>"
			class="spectra-pro-form-link__link"
			target="<?php echo esc_attr( $link_target ); ?>"
			<?php if ( '_blank' === $link_target ) : ?>
				rel="noopener noreferrer"
			<?php endif; ?>
			<?php if ( 'forgot_password' === $link_type ) : ?>
				data-wp-on--click="spectra-pro/login.actions.onForgotPassword"
			<?php endif; ?>
		>
			<span class="spectra-pro-form-link__text">
				<?php echo esc_html( $link_text ); ?>
			</span>
		</a>
	</div>
</<?php echo esc_attr( $tag_name ); ?>>
