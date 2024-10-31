<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

function rishi() {
  return new WC_Checkout_PRO\Rishi\Rishi();
}


function rishi_api() {
  return new WC_Checkout_PRO\Rishi\Api();
}


/**
 * Clean variables using sanitize_text_field. Arrays are cleaned recursively.
 * Non-scalar values are ignored.
 *
 * @param string|array $var Data to sanitize.
 * @return string|array
 */
function rishi_clean( $var ) {
	if ( is_array( $var ) ) {
		return array_map( 'rishi_clean', $var );
	} else {
		return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
	}
}
