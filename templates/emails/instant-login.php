<?php
/**
 * Instant Login email notification.
 *
 * @author  Rishi
 * @package WC_Checkout_PRO/Templates
 * @since   1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_email_header', $email_heading, $email );


echo wptexturize( wpautop( $content ) );


/**
 * Email footer.
 *
 * @hooked WC_Emails::email_footer() Output the email footer.
 */
do_action( 'woocommerce_email_footer', $email );
