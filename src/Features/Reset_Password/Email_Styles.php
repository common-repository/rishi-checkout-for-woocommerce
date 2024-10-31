<?php
namespace WC_Checkout_PRO\Features\Reset_Password;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

/**
*
*/
class Email_Styles {
  public function add_css( $css ) {
    $bg              = get_option( 'woocommerce_email_background_color' );
    $base            = get_option( 'woocommerce_email_base_color' );

    $css .= '.wc-checkout-pro-button-wrapper {
      margin-top: 30px;
      margin-bottom: 65px;
      text-align: center;
    }

    .wc-checkout-pro-button {
      padding-top: 10px;
      padding-bottom: 10px;
      padding-left: 15px;
      padding-right: 15px;
      text-decoration: none;
      font-size: 17px;
      width: 250px;
      max-width: 100%;
      background-color: ' . esc_attr( $base ) . ';
      color: ' . $bg . ';
    }';

    return apply_filters( 'wccp_instant_login_css', $css );
  }
}
