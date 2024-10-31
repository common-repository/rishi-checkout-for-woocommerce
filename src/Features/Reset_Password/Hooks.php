<?php
namespace WC_Checkout_PRO\Features\Reset_Password;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

class Hooks {
  function __construct() {
    $main   = new Main();
    $styles = new Email_Styles();

    add_action( 'init', array( $main, 'validate_instant_login' ) );
    add_filter( 'woocommerce_email_styles', array( $styles, 'add_css' ), 10 );

    add_filter( 'woocommerce_email_classes', array( __CLASS__, 'include_emails' ) );
  }

  /**
   * Include emails.
   *
   * @param  array $emails Default emails.
   *
   * @return array
   */
  public static function include_emails( $emails ) {
    if ( ! isset( $emails['wccp_password'] ) ) {
      $emails['wccp_password'] = include dirname( __FILE__ ) . '/Email.php';
    }

    return $emails;
  }
}
