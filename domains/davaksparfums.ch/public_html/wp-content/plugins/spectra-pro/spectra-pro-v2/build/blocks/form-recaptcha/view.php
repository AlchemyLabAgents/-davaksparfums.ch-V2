<?php
/**
 * View for rendering the block.
 * 
 * @since 2.0.0
 *
 * @package SpectraPro\Blocks\FormRecaptcha
 */

?>
<<?php echo esc_attr( $tag_name ); ?>
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
>
	<div class="spectra-pro-form-recaptcha__wrapper">
		<?php if ( 'v2' === $recaptcha_version ) : ?>
			<div
				id="<?php echo esc_attr( $recaptcha_id ); ?>"
				class="g-recaptcha"
				data-sitekey="<?php echo esc_attr( $recaptcha_site_key ); ?>"
				data-size="<?php echo esc_attr( $recaptcha_size ); ?>"
				data-theme="<?php echo esc_attr( $recaptcha_theme ); ?>"
				data-callback="spectraProRecaptchaCallback"
			></div>
		<?php else : ?>
			<input
				type="hidden"
				id="<?php echo esc_attr( $recaptcha_id ); ?>"
				class="g-recaptcha-response"
				name="g-recaptcha-response"
				data-sitekey="<?php echo esc_attr( $recaptcha_site_key ); ?>"
			/>
		<?php endif; ?>

		<?php if ( ! $hide_batch ) : ?>
			<div class="spectra-pro-form-recaptcha__badge">
				<span>
					<?php esc_html_e( 'This site is protected by reCAPTCHA and the Google', 'spectra-pro' ); ?>
					<a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Privacy Policy', 'spectra-pro' ); ?>
					</a>
					<?php esc_html_e( 'and', 'spectra-pro' ); ?>
					<a href="https://policies.google.com/terms" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Terms of Service', 'spectra-pro' ); ?>
					</a>
					<?php esc_html_e( 'apply.', 'spectra-pro' ); ?>
				</span>
			</div>
		<?php endif; ?>
	</div>
</<?php echo esc_attr( $tag_name ); ?>>
