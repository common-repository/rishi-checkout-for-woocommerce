<?php
namespace WC_Checkout_PRO\Api;

use WC_Checkout_PRO\Api\Checkout\Cart;
use WC_Checkout_PRO\Api\Checkout\Shipping;

use WC_Checkout_PRO\Helpers;
use WC_Checkout_PRO\Integrations\WC;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

Class Customer {
  private $cart = null;
  private $shipping = null;

  function __construct() {
    $this->cart = new Cart();
    $this->shipping = new Shipping();
  }

  // logged_in = false if it's is a new account
  public function get_customer_data( $logged_in = true ) {
    WC::init();

    $checkout = WC()->checkout;
    $fields   = $checkout->get_checkout_fields();

    $billing_values = array();
    foreach ( $fields['billing'] as $k => $value ) {
      $billing_values[ $k ] = esc_attr( $checkout->get_value( $k ) );
    }

    $shipping_values = array();
    foreach ( $fields['shipping'] as $k => $value ) {
      $shipping_values[ $k ] = esc_attr( $checkout->get_value( $k ) );
    }

    $data = array(
      'success' => true,
      'message' => 'Dados validados com sucesso',
      'billing_values'   => $billing_values,
      'shipping_values'  => $shipping_values,
      'user_id'          => get_current_user_id(),
      'cart'             => $this->cart->get_cart_data(),
    );

    if ( isset( $fields['shipping']['shipping_postcode'] ) ) {
      $data['shipping'] = $this->shipping->get_total();
    }

    return $data;
  }

  public function get_user_nonce( $request ) {
    $logged_in = is_user_logged_in();

    return array(
      'success'  => true,
      'message'  => 'Nonce gerada com sucesso',
      'loggedIn' => $logged_in,
      'nonce'    => $logged_in ? wp_create_nonce( 'wp_rest' ) : ''
    );
  }



  public function add_coupon_code( $request ) {
    WC::init();

    // make sure to clean up the old notices
    wc_clear_notices();

    WC()->cart->add_discount( wc_format_coupon_code( wp_unslash( $request->get_param( 'coupon_code' ) ) ) );

    $all_notices = WC()->session->get( 'wc_notices', array() );

    if ( ! empty( $all_notices['error'] ) ) {
      return array(
        'success' => false,
        'cart'    => $this->cart->get_cart_data(),
        'message' => isset( $all_notices['error'][0]['notice'] ) ? $all_notices['error'][0]['notice'] : 'Ocorreu um erro. Tente novamente (3j44h)'
      );
    }

    // calculate only if there is no errors!
    WC()->cart->calculate_shipping();
    WC()->cart->calculate_totals();

    return array(
      'success' => true,
      'cart'    => $this->cart->get_cart_data(),
      'message' => $all_notices
    );
  }



  public function remove_coupon_code( $request ) {
    WC::init();

    // make sure to clean up the old notices
    wc_clear_notices();

    WC()->cart->remove_coupon( wc_format_coupon_code( wp_unslash( $request->get_param( 'coupon_code' ) ) ) );

    $all_notices = WC()->session->get( 'wc_notices', array() );

    WC()->cart->calculate_shipping();
    WC()->cart->calculate_totals();

    if ( ! empty( $all_notices['error'] ) ) {
      return array(
        'success' => false,
        'cart'    => $this->cart->get_cart_data(),
        'message' => $all_notices
      );
    }

    return array(
      'success' => true,
      'cart'    => $this->cart->get_cart_data(),
      'message' => $all_notices
    );
  }



  public function get_shipping_methods( $request ) {
    return $this->shipping->get_shipping_methods( $request );
  }


  public function payment( $request ) {
    // disable shipping calculation
    add_filter( 'woocommerce_cart_ready_to_calc_shipping', '__return_false', 200 );

    WC::init();

    add_filter( 'woocommerce_cart_calculate_fees', function( $cart ) use( $request ) {
      $settings = get_option( 'wc_checkout_pro_settings' );

      if ( ! isset( $settings['gateways_fees'] ) || ! is_array( $settings['gateways_fees'] ) ) {
        return;
      }

      $gateway_fees    = $settings['gateways_fees'];
      $checkout_method = $request->get_param( 'checkout_payment_method' );

      if ( $checkout_method && ! empty( $gateway_fees[ $checkout_method ] ) ) {
        $total = $cart->get_cart_contents_total();
        $fee   = $this->get_fee( $gateway_fees[ $checkout_method ], $total );

        if ( $fee ) {
          $cart->add_fee( $fee > 0 ? 'Taxa forma de pagamento' : 'Desconto forma de pagamento', $fee );
        }
      }
    });

    // let's clear any pending notice
    // to avoid issues
    wc_clear_notices();

    $nonce = wp_create_nonce( 'woocommerce-process_checkout' );

    // using $_POST as required by WC plugins
    $params = $request->get_json_params();

    $_POST = $this->process_woocommerce_fields( $params );

    // "Itaú Shopline para WooCommerce" integration
    if ( isset( $_POST['billing_cnpj'] ) ) {
      $_REQUEST['billing_cnpj'] = wc_clean( wp_unslash( $_POST['billing_cnpj'] ) );
    }

    if ( isset( $_POST['billing_cpf'] ) ) {
      $_REQUEST['billing_cpf'] = wc_clean( wp_unslash( $_POST['billing_cpf'] ) );
    }

    // set WooCommerce Nonce
    $_REQUEST['woocommerce-process-checkout-nonce'] = $nonce;

    // Aqui o WooCommerce irá retornar automaticamente json.
    ob_start();
    WC()->checkout()->process_checkout();

    $result = ob_get_clean();

    // se houver mais respostas, pega apenas o primeiro json
    $result = explode( '----BREAK----', $result );
    $result = json_decode( trim( $result[0] ), true );

    $q = new \WC_Logger(); $q->add('wc-checkout-pro-payment', print_r( $result, true ) );

    if ( ! empty( $_POST['secondary_method'] ) ) {
      $q = new \WC_Logger(); $q->add('rishi-second-attempt', print_r( $_POST['secondary_method'], true ) );

      if ( $order_id = WC()->session->get( 'order_awaiting_payment' ) ) {
        $order = wc_get_order( $order_id );

        $q = new \WC_Logger();
        $q->add('rishi-second-attempt', 'Tentantiva de processar pedido #' . $order_id );

        if ( $order && $order->has_status( 'failed' ) ) {

          $_POST = array_merge( $_POST, $_POST['secondary_method'] );
          unset( $_POST['secondary_method'] );

          ob_start();
          WC()->checkout()->process_checkout();

          $result = ob_get_clean();

          // se houver mais respostas, pega apenas o primeiro json
          $result = explode( '----BREAK----', $result );
          $result = json_decode( trim( $result[0] ), true );

          $q = new \WC_Logger(); $q->add('wc-checkout-pro-payment', '========= SEGUNDA TENTATIVA =========' );
          $q = new \WC_Logger(); $q->add('wc-checkout-pro-payment', print_r( $result, true ) );

        } else {
          $q = new \WC_Logger();
          $q->add('rishi-second-attempt', 'Impossível de processar pedido #' . $order_id . '. Status: ' . $order->get_status() );
        }
      }
    }

    $q = new \WC_Logger(); $q->add('wc-checkout-pro-payment', print_r( $result, true ) );

    $messages = '';
    if ( isset( $result['messages'] ) ) {
      $messages = $this->format_messages( $result['messages'] );
    }

    $result = array(
      'content'  => $result,
      'success'  => 'success' === $result['result'],
      'message'  => $messages,
      'order'    => isset( $result['order'] ) ? $result['order'] : [],
      'redirect' => isset( $result['redirect'] ) ? $result['redirect'] : ''
    );

    // it's not a temp user anymore
    if ( $result['success'] ) {
      delete_user_meta( get_current_user_id(), '_wc_checkout_pro_is_temp' );
      update_user_meta( get_current_user_id(), '_wc_checkout_pro_customer', true );
    }

    $q = new \WC_Logger(); $q->add( 'wc-checkout-pro', print_r( $result, true ) );

    return $result;
  }



  private function format_messages( $messages ) {
    $messages = strip_tags( $messages );
    $messages = explode( "\n", $messages );
    $messages = array_map( 'trim', $messages );
    $messages = array_filter( $messages );
    $messages = array_values( $messages );

    return $messages;
  }


  public function process_woocommerce_fields( $params ) {
		$data    = array(
			'terms'                              => 1, // WPCS: input var ok, CSRF ok.
			'payment_method'                     => isset( $params['payment_method'] ) ? wc_clean( wp_unslash( $params['payment_method'] ) ) : '', // WPCS: input var ok, CSRF ok.
			'shipping_method'                    => isset( $params['shipping_method'] ) ? wc_clean( wp_unslash( $params['shipping_method'] ) ) : '', // WPCS: input var ok, CSRF ok.
			'ship_to_different_address'          => ! empty( $params['ship_to_different_address'] ) && ! wc_ship_to_billing_address_only(), // WPCS: input var ok, CSRF ok.
			'woocommerce_checkout_update_totals' => isset( $params['woocommerce_checkout_update_totals'] ), // WPCS: input var ok, CSRF ok.
    );

		foreach ( WC()->checkout->get_checkout_fields() as $fieldset_key => $fieldset ) {
			foreach ( $fieldset as $key => $field ) {
				$type = sanitize_title( isset( $field['type'] ) ? $field['type'] : 'text' );

				switch ( $type ) {
					case 'checkbox':
						$value = isset( $params[ $key ] ) ? 1 : ''; // WPCS: input var ok, CSRF ok.
						break;
					case 'multiselect':
						$value = isset( $params[ $key ] ) ? implode( ', ', wc_clean( wp_unslash( $params[ $key ] ) ) ) : ''; // WPCS: input var ok, CSRF ok.
						break;
					case 'textarea':
						$value = isset( $params[ $key ] ) ? wc_sanitize_textarea( wp_unslash( $params[ $key ] ) ) : ''; // WPCS: input var ok, CSRF ok.
						break;
					case 'password':
						$value = isset( $params[ $key ] ) ? wp_unslash( $params[ $key ] ) : ''; // WPCS: input var ok, CSRF ok, sanitization ok.
						break;
					default:
						$value = isset( $params[ $key ] ) ? wc_clean( wp_unslash( $params[ $key ] ) ) : ''; // WPCS: input var ok, CSRF ok.
						break;
				}

				$data[ $key ] = apply_filters( 'woocommerce_process_checkout_' . $type . '_field', apply_filters( 'woocommerce_process_checkout_field_' . $key, $value ) );
			}
		}

    // always use billing address as shipping address
    foreach ( WC()->checkout->get_checkout_fields( 'shipping' ) as $key => $field ) {
      if ( ! empty( $data[$key] ) ) {
        $data[ 'billing_' . substr( $key, 9 ) ] = $data[$key];
      }
    }

    // CPF by default :)
    if ( ! isset( $data['billing_persontype'] ) ) {
      $data['billing_persontype'] = 1;
    }

    $data['billing_persontype'] = intval( $data['billing_persontype'] );

    // always Brazil
    $data['billing_country']  = 'BR';
    $data['shipping_country'] = 'BR';

    foreach ( $params as $key => $value ) {
      // default field
      if ( false !== array_key_exists( $key, $data ) ) {
        continue;
      }

      if ( is_scalar( $value ) ) {
        $data[ $key ] = esc_attr( $value );
      } else {
        $data[ $key ] = $this->esc_fields( $value );
      }
    }

    // clear empty fields (sometimes not required)
    $data = array_filter( $data );

		return $data;
  }



  private function esc_fields( $fields ) {
    $data = [];

    foreach ( $fields as $key => $value ) {
      if ( is_scalar( $value ) ) {
        $data[ $key ] = esc_attr( $value );
      } else {
        $data[ $key ] = $this->esc_fields( $value );
      }
    }

    return $data;
  }




	/**
	 * Get fee to add to shipping cost.
	 *
	 * @param string|float $fee Fee.
	 * @param float        $total Total.
	 * @return float
	 */
	protected function get_fee( $fee, $total ) {
		if ( strstr( $fee, '%' ) ) {
			$fee = ( $total / 100 ) * str_replace( '%', '', $fee );
		}

		return $fee;
	}
}
