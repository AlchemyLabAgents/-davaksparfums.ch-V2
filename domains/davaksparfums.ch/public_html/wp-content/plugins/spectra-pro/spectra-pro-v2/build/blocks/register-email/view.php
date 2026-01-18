<?php
/**
 * Form Input Wrapper view.
 *
 * @since 2.0.0
 * @package Spectra Pro
 */

use Spectra\Helpers\HtmlSanitizer;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<<?php echo esc_attr( $data['tag_name'] ); ?> <?php echo wp_kses_data( $data['wrapper_attributes'] ); ?>>
	<?php HtmlSanitizer::render( $data['content'] ); ?>
</<?php echo esc_attr( $data['tag_name'] ); ?>>
