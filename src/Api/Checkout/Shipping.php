<?php
namespace WC_Checkout_PRO\Api\Checkout;

use WC_Checkout_PRO\Helpers;
use WC_Checkout_PRO\Integrations\WC;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

class Shipping {
  public function get_shipping_methods( $request ) {
    $postcode = wc_clean( wp_unslash( $request->get_param( 'shipping_postcode' ) ) );

    WC::init();

    if ( ! WC()->cart->needs_shipping() )  {
      return array(
        'success'        => true,
        'message'        => 'Custos de envio atualizados',
        'postcode'       => $postcode,
        'items_count'    => count( WC()->cart->get_cart() ),
        'methods'        => array(),
        'needs_shipping' => false
      );
    }

    $is_valid = \WC_Validation::is_postcode( $postcode, 'BR' );

    if ( ! $is_valid ) {
      return array(
        'success'  => false,
        'message'  => 'CEP inválido. Verifique seu CEP e tente novamente.',
        'postcode' => $postcode,
        'needs_shipping' => true
      );
    }

    // set the customer
    WC()->customer->set_props(
      array(
        'shipping_country'   => 'BR',
        'shipping_state'     => Helpers::get_state( $postcode ),
        'shipping_postcode'  => $postcode,
        'shipping_city'      => null,
        'shipping_address_1' => null,
        'shipping_address_2' => null,
      )
    );
    WC()->customer->save();

    return array(
      'success'        => true,
      'message'        => 'Custos de envio atualizados',
      'postcode'       => $postcode,
      'items_count'    => count( WC()->cart->get_cart() ),
      'methods'        => $this->get_shipping_methods_list(),
      'needs_shipping' => true
    );
  }





  public function get_shipping_methods_list() {
    WC::init();

    WC()->cart->calculate_shipping();
    $packages = WC()->shipping()->get_packages();
    $methods  = array();

    foreach ( $packages as $k => $package ) {
      // TODO: implement multiple packages
      // $methods[ $k ] = array();

      foreach ( $package['rates'] as $rate ) {
        $methods[] = array(
        // $methods[ $k ][ $rate->get_id() ] = array(
          'key' => $rate->get_id(),
          'method_id' => $rate->get_method_id(),
          'instance_id' => $rate->get_instance_id(),
          'label' => $rate->get_label(),
          'cost' => $rate->get_cost(),
          'delivery_time' => $this->get_delivery_time( $rate )
        );
      }
    }

    return $methods;
  }



  private function get_delivery_time( $rate ) {
    switch ( $rate->get_method_id() ) {
      case 'correios-pac':
      case 'correios-sedex':
        $data = $rate->get_meta_data();
        return isset( $data['_delivery_forecast'] ) ? $data['_delivery_forecast'] : null;
        break;

      default:
        return null;
      break;
    }
  }



  public function get_total() {
    return WC()->cart->get_shipping_total();
  }
}
