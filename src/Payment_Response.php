<?php

namespace WC_Checkout_PRO;

class Payment_Response {
  function __construct() {
    add_filter( 'woocommerce_payment_successful_result', array( $this, 'add_order_to_response' ), 10, 2 );
  }


  /**
   * add_order_to_response
   *
   * @param mixed $result
   * @param mixed $order_id
   * @return void
   */
  public function add_order_to_response( $result, $order_id ) {
    $order = wc_get_order( $order_id );

    if ( $order )  {
      $result['order'] = [
        'id'    => $order->get_id(),
        'total' => $order->get_total()
      ];
    }

    return $result;
  }
}
