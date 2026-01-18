<?php
/**
 * Controller for rendering the block.
 * 
 * @since 2.0.0-beta.1
 * 
 * @package SpectraPro\Blocks\CountdownChildExpiryWrapper
 */

use Spectra\Helpers\BlockAttributes;

$show = $attributes['show'] ?? '';

// Exit if show is false.
if ( ! $show ) {
	return '';
}

// Style and class configurations.
$config = array(
	array( 'key' => 'textColor' ),
	array( 'key' => 'textColorHover' ),
	array( 'key' => 'backgroundColor' ),
	array( 'key' => 'backgroundColorHover' ),
	array( 'key' => 'backgroundGradient' ),
	array( 'key' => 'backgroundGradientHover' ),
);

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config );


// Render the tabs block.
return 'file:./view.php';
