<?php
/**
 * View for rendering the block.
 *
 * @since 2.0.0
 *
 * @package SpectraPro\Blocks\FormInputField
 */

use Spectra\Helpers\Renderer;

// Render input element with block attributes (no wrapper!).
// This allows padding and other styles to apply directly to the input.
?>
<input <?php echo wp_kses_data( $wrapper_attributes ); ?> />

<?php if ( 'password' === $input_type ) : ?>
	<button
		type="button"
		id="password-visibility-<?php echo esc_attr( $block_id ); ?>"
		class="spectra-pro-form-input-field__password-toggle"
		aria-label="<?php esc_attr_e( 'Show Password', 'spectra-pro' ); ?>"
	>
		<?php
		// Render SVG icon for password visibility toggle.

		// Eye icon SVG (visibility state).
		$eye_icon_svg = 'eye';
		$icon_props   = array(
			'focusable'      => 'false',
			'style'          => array(
				'fill'   => 'currentColor',
				'width'  => '16px',
				'height' => '16px',
			),
			'class'          => 'spectra-pro-password-toggle__icon spectra-pro-password-toggle__icon--visible',
			'data-icon-type' => 'visible',
		);

		Renderer::svg_html( esc_attr( $eye_icon_svg ), false, $icon_props );

		// Eye-off icon SVG (hidden state).
		$eye_off_icon_svg  = 'eye-slash';
		$icon_props_hidden = array(
			'focusable'      => 'false',
			'style'          => array(
				'fill'   => 'currentColor',
				'width'  => '16px',
				'height' => '16px',
			),
			'class'          => 'spectra-pro-password-toggle__icon spectra-pro-password-toggle__icon--hidden',
			'data-icon-type' => 'hidden',
		);

		Renderer::svg_html( esc_attr( $eye_off_icon_svg ), false, $icon_props_hidden );
		?>
	</button>
<?php endif; ?>
