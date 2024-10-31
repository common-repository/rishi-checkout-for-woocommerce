<?php
namespace WC_Checkout_PRO\Api;

use WC_Checkout_PRO\Api\Checkout\Cart;
use WC_Checkout_PRO\Features\Reset_Password;
use WC_Checkout_PRO\Integrations\WC;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

Class Guest {
  public function check_user_email( $request ) {
    WC::init();

    $email        = sanitize_email( $request->get_param( 'user_email' ) );
    $has_account = false;
    $first_name   = '';
    $token        = false;

    if ( $user = get_user_by( 'email', $email ) ) {
      $customer_id = $user->ID;

      // se for um usuário temporário não pede senha,
      // e faz login. não possui dados sensíveis
      if ( get_user_meta( $customer_id, '_wc_checkout_pro_is_temp', true ) ) {
        WC()->initialize_session();
        wc_set_customer_auth_cookie( $customer_id );

        $token = $this->get_rest_nonce( $customer_id );

      } else {
        $first_name   = get_user_meta( 1, 'billing_first_name', true );
        $has_account = true;
      }

    } else {

      $customer_id = $this->register_user( $email );

      update_user_meta( $customer_id, '_wc_checkout_pro_is_temp', true );

      // login customer
      WC()->initialize_session();
      wc_set_customer_auth_cookie( $customer_id );

      $token = $this->get_rest_nonce( $customer_id );
    }

    $this->cart = new Cart();

    return array(
      'success'     => true,
      'message'     => 'E-mail verificado com sucesso.',
      'has_account' => $has_account,
      'first_name'  => $first_name,
      'token'       => $token,
      'user_id'     => $customer_id,
      'cart'        => $this->cart->get_cart_data(),
    );
  }



  public function authenticate( $request ) {
    $email    = sanitize_email( $request->get_param( 'user_email' ) );
    $password = $request->get_param( 'user_password' );
    $token    = '';

    // results
    $result   = wp_authenticate( $email, $password );
    $success  = false;
    $message  = 'Login realizado.';

    if ( is_wp_error( $result ) ) {
      if ( 'incorrect_password' === $result->get_error_code() ) {
        $message = 'Senha incorreta. Esqueceu sua senha?';
      } else {
        $message = $result->get_error_code() . ': ' . $result->get_error_message();
      }
    } else {
      WC()->initialize_session();
      wc_set_customer_auth_cookie( $result->ID );

      $success  = true;
      $token    = $this->get_rest_nonce( $result->ID );
    }

    return array(
      'success' => $success,
      'message' => $message,
      'token'   => $token
    );
  }


  public function reset_password( $request ) {
    $email   = sanitize_email( $request->get_param( 'user_email' ) );
    $handler = new Reset_Password\Main();
    $success = $handler->send_secret_link( $email );

    return array(
      'success'   => $success,
      'message'   => 'E-mail enviado com sucesso.'
    );
  }



  public function get_rest_nonce( $customer_id ) {
    return wp_create_nonce( 'wp_rest' );
    return $customer_id;
  }


  public function register_user( $email ) {
		$new_customer_data = apply_filters(
			'woocommerce_new_customer_data',
      array(
        'user_login' => $email,
        'user_email' => $email,
        'user_pass'  => wp_generate_password(),
        'role'       => 'customer',
      )
		);

    $customer_id = wp_insert_user( $new_customer_data );

    return $customer_id;
  }
}
