<?php
/**
 * Text Module template
 *
 * $this is an instace of the Text object.
 *
 * Available properties:
 * $this->theme (string).
 * $this->number_of_sites (int)
 *
 * @package Hogan
 */

declare( strict_types = 1 );
namespace DSS\Hogan;

if ( ! defined( 'ABSPATH' ) || ! ( $this instanceof Sites ) ) {
	return; // Exit if accessed directly.
}

$theme =  ( isset( $this->theme ) && 'all' != $this->theme ) ? $this->theme : '';
$number_of_sites =  ( isset( $this->number_of_sites ) ) ? $this->number_of_sites : 0;

echo $this->portfolio(
	[
		'theme'  => $theme,
		'num'    => $number_of_sites,
		'width'  => '432',
		'height' => '288',
	]
);
