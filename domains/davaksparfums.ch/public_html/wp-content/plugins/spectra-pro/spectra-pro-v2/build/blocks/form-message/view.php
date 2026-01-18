<?php
/**
 * View for rendering the block.
 *
 * @since 2.0.0
 *
 * @package SpectraPro\Blocks\FormMessage
 */

?>
<?php 
// Prepare wrapper attributes with proper hidden state handling.
if ( ! $is_visible ) {
	// For hidden messages, add stronger hiding styles.
	$hidden_style = 'display: none !important; visibility: hidden !important; opacity: 0 !important;';
	
	// Check if wrapper_attributes already has a style attribute.
	if ( strpos( $wrapper_attributes, 'style=' ) !== false ) {
		// Append to existing style.
		$wrapper_attributes = str_replace( 'style="', 'style="' . $hidden_style . ' ', $wrapper_attributes );
	} else {
		// Add new style attribute.
		$wrapper_attributes .= ' style="' . $hidden_style . '"';
	}
}
?>
<!-- Form message - visible in editor based on demo toggles, hidden on frontend until shown via JavaScript -->
<<?php echo esc_attr( $tag_name ); ?>
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
	data-message-type="<?php echo esc_attr( $message_type ); ?>"
>
	<div class="spectra-pro-form-message__wrapper">
		<div class="spectra-pro-form-message__content">
			<?php if ( ! empty( $message_icon_class ) ) : ?>
				<span class="dashicons <?php echo esc_attr( $message_icon_class ); ?>"></span>
			<?php endif; ?>

			<div class="spectra-pro-form-message__text-wrapper">
				<span class="spectra-pro-form-message__text">
					<?php echo wp_kses_post( $message_text ); ?>
				</span>
			</div>
		</div>
	</div>
</<?php echo esc_attr( $tag_name ); ?>>
