<?php
namespace WC_Checkout_PRO\Api\Checkout;

use WC_Checkout_PRO\Helpers;
use WC_Checkout_PRO\Integrations\WC;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

class Cart {
  public function get_cart_data() {
    WC()->cart->calculate_fees();

    return array(
      'hash'           => WC()->cart->get_cart_hash(),
      'items'          => $this->get_cart_items(),
      'subtotal'       => WC()->cart->get_subtotal(),
      'total'          => WC()->cart->get_cart_contents_total(),
      'coupons'        => $this->get_coupons(),
      'needs_shipping' => WC()->cart->needs_shipping(),
      'needs_shipping' => WC()->cart->needs_shipping(),
      'fees'           => array_values( WC()->cart->get_fees() ), // reset keys
    );
  }


  private function get_coupons() {
    $coupons = array();
    foreach( WC()->cart->get_coupons() as $code => $coupon ) {
      $coupons[] = array(
        'coupon_code' => $code,
        'amount'      =>  WC()->cart->get_coupon_discount_amount( $coupon->get_code(), WC()->cart->display_cart_ex_tax ),
        'label'       => wc_cart_totals_coupon_label( $coupon, false ),
      );
    }

    return $coupons;
  }


  protected function get_cart_items() {
    WC::init();

    $items = array();

    foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
      $_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

      if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) {

        $items[] = array(
          'key'   => $cart_item_key,
          'id'    => $_product->get_id(),
          'sku'   => $_product->get_sku() ? $_product->get_sku() : $_product->get_id(),
          'price' => $_product->get_price(), // precisa ser o custo do item no carrinho, não o custo real, já que pode ser personalizado
          'name'  => $_product->get_name(),
          'image' => $_product->get_image_id() ? wp_get_attachment_image_url( $_product->get_image_id(), 'thumbnail' ) : wc_placeholder_img_src( 'thumbnail' ),
          'quantity'  => $cart_item['quantity'],
          'meta_data' => wc_get_formatted_cart_item_data( $cart_item, true ),
          'subtotal'  => wc_get_price_excluding_tax( $_product, array( 'qty' => $cart_item['quantity'] ) )
        );
      }
    }

    return $items;
  }
}
