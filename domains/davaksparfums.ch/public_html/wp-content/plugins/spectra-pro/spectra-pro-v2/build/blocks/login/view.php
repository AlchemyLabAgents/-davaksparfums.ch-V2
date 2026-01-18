<?php
/**
 * Login block view.
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

// Check if user is logged in and handle accordingly.
if ( is_user_logged_in() && ! $attributes['enableLoggedInMessage'] ) {
	return; // Don't render anything if user is logged in and logged-in message is disabled.
}

// Inject configuration for legacy JavaScript compatibility.
if ( ! isset( $GLOBALS['spectra_login_config'] ) ) {
	$GLOBALS['spectra_login_config'] = [];
}
$GLOBALS['spectra_login_config'][ $block_id ] = $login_contexts;

?>
<<?php echo esc_attr( $tag_name ); ?>
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
	<?php echo wp_kses_data( wp_interactivity_data_wp_context( $login_contexts, 'spectra-pro/login' ) ); ?>
	data-wp-interactive="spectra-pro/login"
	data-wp-key="<?php echo esc_attr( $block_id ); ?>"
	data-form-id="<?php echo esc_attr( $form_id ); ?>"
	data-js-config="<?php echo esc_attr( wp_json_encode( $login_contexts ) ); ?>"
>
	<?php if ( is_user_logged_in() && $attributes['enableLoggedInMessage'] ) : ?>
		<?php
		$logged_in_user             = wp_get_current_user();
		$user_name                  = $logged_in_user->display_name;
		$logout_url                 = ! empty( $login_contexts['logoutRedirectUrl'] ) ? $login_contexts['logoutRedirectUrl'] : home_url( '/' );
		$has_custom_logout_redirect = ! empty( $login_contexts['logoutRedirectUrl'] );
		?>
		<div class="spectra-pro-login-logged-in-message">
			<?php
			if ( $has_custom_logout_redirect ) {
				// Use custom logout redirect - create logout URL without redirect parameter.
				$logout_base_url = wp_logout_url();
				printf(
					// translators: 1: User display name, 2: Opening anchor tag for logout link, 3: Closing anchor tag.
					esc_html__( 'You are logged in as %1$s (%2$sLogout%3$s)', 'spectra-pro' ),
					esc_html( $user_name ),
					'<a href="' . esc_url( $logout_base_url ) . '" class="spectra-pro-logout-link" data-redirect-url="' . esc_url( $logout_url ) . '" style="cursor: pointer !important; pointer-events: auto; position: relative; z-index: 999;">',
					'</a>'
				);
			} else {
				// Use default WordPress logout behavior.
				printf(
					// translators: 1: User display name, 2: Opening anchor tag for logout link, 3: Closing anchor tag.
					esc_html__( 'You are logged in as %1$s (%2$sLogout%3$s)', 'spectra-pro' ),
					esc_html( $user_name ),
					'<a href="' . esc_url( wp_logout_url( $logout_url ) ) . '" style="cursor: pointer !important; pointer-events: auto; position: relative; z-index: 999;">',
					'</a>'
				);
			}
			?>
		</div>
	<?php else : ?>
		<?php
			// Render the background video element if needed - outside form for proper layering.
			Renderer::background_video( $background );
		?>
		<form 
			id="<?php echo esc_attr( $form_id ); ?>"
			class="spectra-pro-login-form" 
			method="post"
			novalidate
		>
			<?php
				// Inject reCAPTCHA before submit button.
			if ( $login_contexts['enableRecaptcha'] && ! empty( $login_contexts['recaptchaSiteKey'] ) ) {
				$recaptcha_html = '<div class="spectra-pro-form-recaptcha__wrapper">';
				if ( 'v2' === $login_contexts['recaptchaVersion'] || 'v2' === $login_contexts['reCaptchaType'] ) {
					$recaptcha_html .= '<div class="g-recaptcha" data-sitekey="' . esc_attr( $login_contexts['recaptchaSiteKey'] ) . '"></div>';
					// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- External CDN script, version managed by Google.
					wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js', array(), null, true );
				} else {
					$recaptcha_html .= '<input type="hidden" class="g-recaptcha-response" name="g-recaptcha-response" data-sitekey="' . esc_attr( $login_contexts['recaptchaSiteKey'] ) . '" />';
					// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- External CDN script, version managed by Google.
					wp_enqueue_script( 'google-recaptcha-v3', 'https://www.google.com/recaptcha/api.js?render=' . $login_contexts['recaptchaSiteKey'], array(), null, true );
				}
				$recaptcha_html .= '</div>';

				// Insert reCAPTCHA before submit button.
				$content = preg_replace(
					'/(<[^>]*wp-block-spectra-pro-form-button[^>]*>)/i',
					$recaptcha_html . '$1',
					$content
				);
			}

				// Add required hidden fields for login form.
				$hidden_fields  = '<input type="hidden" name="_nonce" value="' . esc_attr( $login_contexts['nonce'] ) . '" />';
				$hidden_fields .= '<input type="hidden" name="action" value="spectra_pro_v2_block_login" />';

				// Insert hidden fields before the submit button.
				$content = preg_replace(
					'/(<[^>]*wp-block-spectra-pro-form-button[^>]*>)/i',
					$hidden_fields . '$1',
					$content
				);

				// Process block comments to render actual HTML.
				// Note: Message blocks are now always included in the template (see render.js).
				$content = do_blocks( $content );

				HtmlSanitizer::render( $content );
			?>
		</form>
	<?php endif; ?>
</<?php echo esc_attr( $tag_name ); ?>>

<?php
// Schedule JavaScript configuration output.
add_action(
	'wp_footer',
	function() {
		if ( ! empty( $GLOBALS['spectra_login_config'] ) ) {
			echo '<script type="text/javascript">';
			echo 'window.spectra_login_config = ' . wp_json_encode( $GLOBALS['spectra_login_config'] ) . ';';
			echo '</script>';
		}
	},
	20 
);
?>
