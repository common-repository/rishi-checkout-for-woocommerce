<?php
namespace WC_Checkout_PRO\WC_Api;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

class Settings {
  private $namespace = 'wc/v3';

  public function __construct() {
    add_action( 'rest_api_init', array( $this, 'add_endpoints' ) );
  }


  /**
   * add_endpoints
   *
   * @param mixed
   * @return void
   */
  public function add_endpoints() {
    register_rest_route(
      $this->namespace,
      '/wc_checkout_pro_settings',
      array(
        'methods'  => 'POST',
        'permission_callback' => array( $this, 'is_admin_auth' ),
        'callback' => array( $this, 'update_checkout_settings' ),
      )
    );
  }


  /**
   * update_checkout_settings
   *
   * @param mixed $request
   * @return void
   */
  public function update_checkout_settings( $request ) {
    $data = $request->get_json_params();

    foreach ( $data as $key => $values ) {
      if ( $values ) {
        update_option( 'wc_checkout_pro_' . $key, $values );
      } else {
        delete_option( 'wc_checkout_pro_' . $key );
      }
    }

    // checkout cache
    delete_transient( 'wc_checkout_pro_cache_settings' );
    delete_transient( 'wc_checkout_pro_cache_scripts' );

    return [
      'success' => true
    ];
  }


  /**
   * is_admin_auth
   *
   * @return void
   */
  public function is_admin_auth() {
    return current_user_can( 'manage_woocommerce' );
  }
}

