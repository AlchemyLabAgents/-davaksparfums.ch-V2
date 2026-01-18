<?php
/**
 * Register block view.
 * Following loop-builder hybrid pattern.
 *
 * @since 2.0.0
 * @package Spectra Pro
 *
 * @var array $data Block data from controller.
 */

use Spectra\Helpers\Renderer;
use Spectra\Helpers\HtmlSanitizer;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Extract data from controller.
extract( $data );

// Inject configuration for legacy JavaScript compatibility.
if ( ! isset( $GLOBALS['spectra_register_config'] ) ) {
	$GLOBALS['spectra_register_config'] = [];
}
$GLOBALS['spectra_register_config'][ $block_id ] = $register_contexts;

// Check if registration is allowed - if not, don't render the block at all (matching old register block behavior).
if ( ! get_option( 'users_can_register' ) ) {
	return;
}

// Check if user is already logged in.
if ( is_user_logged_in() ) {
	$logged_in_user = wp_get_current_user();
	$user_name      = $logged_in_user->display_name;
	$logout_url     = isset( $attributes['redirectAfterLogoutURL']['url'] ) && $attributes['redirectAfterLogoutURL']['url'] ? $attributes['redirectAfterLogoutURL']['url'] : home_url( '/' );

	if ( $attributes['enableLoggedInMessage'] ?? true ) {
		?>
		<<?php echo esc_attr( $tag_name ); ?>
			<?php echo wp_kses_data( $wrapper_attributes ); ?>
		>
			<div class="spectra-pro-register-logged-in-message">
				<?php
				// Use the same format as V1 for consistency.
				printf(
					// translators: 1: User display name, 2: Opening anchor tag for logout link, 3: Closing anchor tag.
					esc_html__( 'You are logged in as %1$s (%2$sLogout%3$s)', 'spectra-pro' ),
					esc_html( $user_name ),
					'<a href="' . esc_url( wp_logout_url( $logout_url ) ) . '">',
					'</a>'
				);
				?>
			</div>
		</<?php echo esc_attr( $tag_name ); ?>>
		<?php
	}
	return;
}//end if

?>
<<?php echo esc_attr( $tag_name ); ?>
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
	<?php echo wp_kses_data( wp_interactivity_data_wp_context( $register_contexts, 'spectra-pro/register' ) ); ?>
	data-wp-interactive="spectra-pro/register"
	data-wp-key="<?php echo esc_attr( $block_id ); ?>"
	data-form-id="<?php echo esc_attr( $form_id ); ?>"
	data-js-config="<?php echo esc_attr( wp_json_encode( $register_contexts ) ); ?>"
>
	<?php 
		// Render the background video element if needed.
		Renderer::background_video( $background );
	?>
	<form 
		id="<?php echo esc_attr( $form_id ); ?>"
		class="spectra-pro-register-form" 
		method="post"
		data-wp-on--submit="actions.onSubmit"
		data-wp-bind--aria-busy="state.isSubmitting"
		novalidate
	>
		<?php
			// Inject reCAPTCHA before submit button.
		if ( $register_contexts['enableRecaptcha'] && ! empty( $register_contexts['recaptchaSiteKey'] ) ) {
			$recaptcha_html = '<div class="spectra-pro-form-recaptcha__wrapper">';
			if ( 'v2' === $register_contexts['recaptchaVersion'] || 'v2' === $register_contexts['reCaptchaType'] ) {
				$recaptcha_html .= '<div class="g-recaptcha" data-sitekey="' . esc_attr( $register_contexts['recaptchaSiteKey'] ) . '"></div>';
				// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- External CDN script, version managed by Google.
				wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js', array(), null, true );
			} else {
				$recaptcha_html .= '<input type="hidden" class="g-recaptcha-response" name="g-recaptcha-response" data-sitekey="' . esc_attr( $register_contexts['recaptchaSiteKey'] ) . '" />';
				// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- External CDN script, version managed by Google.
				wp_enqueue_script( 'google-recaptcha-v3', 'https://www.google.com/recaptcha/api.js?render=' . $register_contexts['recaptchaSiteKey'], array(), null, true );
			}
			$recaptcha_html .= '</div>';

			// Insert reCAPTCHA before submit button.
			$content = preg_replace(
				'/(<[^>]*wp-block-spectra-pro-form-button[^>]*>)/i',
				$recaptcha_html . '$1',
				$content
			);
		}

			// Process block comments to render actual HTML.
			// Note: Message blocks are now always included in the template (see render.js).
			$content = do_blocks( $content );

			HtmlSanitizer::render( $content );
		?>

		<!-- Hidden fields required by register.js -->
		<input type="hidden" name="_nonce" value="<?php echo esc_attr( $register_contexts['nonce'] ); ?>" />
		<input type="hidden" name="action" value="spectra_pro_block_register" />
	</form>

	<!-- Status container required by register.js -->
	<div class="spectra-pro-register-form-status"></div>
</<?php echo esc_attr( $tag_name ); ?>>

<?php
// Schedule JavaScript configuration output.
add_action(
	'wp_footer',
	function() {
		if ( ! empty( $GLOBALS['spectra_register_config'] ) ) {
			echo '<script type="text/javascript">';
			echo 'window.spectra_register_config = ' . wp_json_encode( $GLOBALS['spectra_register_config'] ) . ';';
			echo '</script>';
		}
	},
	20 
);
?>
