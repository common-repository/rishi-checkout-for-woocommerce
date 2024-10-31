<?php
namespace WC_Checkout_PRO\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

class WC {
  function __construct() {

  }

  public static function init() {
    // enforce ajax response
    wc_maybe_define_constant( 'DOING_AJAX', true );
    add_filter( 'wp_doing_ajax', '__return_true' );
    add_filter( 'wp_die_ajax_handler', function() {
      return array( __CLASS__, 'wp_die_ajax_handler' );
    }, 9999 );

    // lets simulate a Frontend request
    add_filter( 'woocommerce_is_rest_api_request', '__return_false' );
    WC()->frontend_includes();
    WC()->initialize_session();
    // force cart init
    WC()->initialize_cart();
    WC()->cart->get_cart(); // we need to load the cart (why?)!
  }


  public static function wp_die_ajax_handler() {
    echo '----BREAK----'; // dont die, mas adicionar marcação de onde termina uma resposta :)
  }
}
