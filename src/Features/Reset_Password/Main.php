<?php
namespace WC_Checkout_PRO\Features\Reset_Password;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

class Main {
  function __construct() {
    $this->helpers = new Helpers();
    // $this->mail    = new Email();
  }

  public function send_secret_link( $email ) {
    $user = $this->get_user( $email );

    if ( $user ) {
      $link = $this->helpers->save_login_link( $user );
      WC()->mailer()->emails['wccp_password']->trigger( $user, $link );

      return true;
    }

    return false;
  }



  public function get_user( $email ) {
    $user = get_user_by( 'email', sanitize_email( $email ) );

    return $user;
  }






  public function trigger_email( $email, $link ) {
    return true;
  }



  public function validate_instant_login() {
    if ( ! isset( $_REQUEST['wccp_login'], $_REQUEST['uid'] ) || empty( $_REQUEST['wccp_login'] ) || empty( $_REQUEST['uid'] ) ) {
      return;
    }

    $user = get_user_by( 'id', wc_clean( wp_unslash( $_REQUEST['uid'] ) ) );

    if ( ! $user ) {
      return;
    }

    $user_id = $user->ID;
    $data    = get_user_meta( $user_id, '_wc_checkout_pro_login', true );

    if ( '' === $data ) {
      return;
    }

    if ( ! isset( $data['expires_at'], $data['key'] ) ) {
      return false;
    }

    if ( $data['key'] !== wc_clean( wp_unslash( $_REQUEST['wccp_login'] ) ) ) {
      return;
    }

    if ( $data['expires_at'] < time() ) {
      delete_user_meta( $user_id, '_wc_checkout_pro_login' );
      $this->send_secret_link( $user->user_email );
      wp_die( 'Este link expirou. Mas já enviamos um novo. <br />Confira seu e-mail :)', 'Link expirado', array( 'back_link' => false ) );
    }

    delete_user_meta( $user_id, '_wc_checkout_pro_login' );


    // maybe init session before login
    if ( is_null( WC()->session ) ) {
      WC()->initialize_session();
    }
    // log in customer
    wc_set_customer_auth_cookie( $user->ID );

    $redirect = wc_get_page_permalink( 'checkout' );

    wp_safe_redirect( $redirect );
    exit;
  }
}
