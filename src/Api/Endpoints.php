<?php
namespace WC_Checkout_PRO\Api;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

Class Endpoints {
  private $namespace = 'wc_checkout/v1';

  public function __construct() {
    add_action( 'rest_api_init', array( $this, 'refresh_tokens' ) );
    add_action( 'rest_api_init', array( $this, 'add_endpoints' ) );
  }

  /**
   * refresh_tokens
   *
   * @return void
   */
  public function refresh_tokens() {
    if ( is_user_logged_in() && $_SERVER['REQUEST_URI'] && ! empty( $_SERVER['HTTP_X_WP_NONCE'] ) ) {
      $_SERVER['HTTP_X_WP_NONCE'] = wp_create_nonce( 'wp_rest' );
    }
  }

  /**
   * add_endpoints
   *
   * @return void
   */
  public function add_endpoints() {
    $guest    = new Guest();
    $customer = new Customer();

    register_rest_route(
      $this->namespace,
      '/validate_email',
      array(
        'methods'  => 'POST',
        'callback' => array( $guest, 'check_user_email' ),
        'permission_callback' => '__return_true',
        'args' => array(
          'user_email' => array(
            'required'          => true,
            'validate_callback' => function( $param, $request, $key ) {
              return is_email( $param );
            },
          ),
        )
      )
    );

    register_rest_route(
      $this->namespace,
      '/authenticate',
      array(
        'methods'  => 'POST',
        'callback' => array( $guest, 'authenticate' ),
        'permission_callback' => '__return_true',
        'args' => array(
          'user_email' => array(
            'required'          => true,
            'validate_callback' => function( $param, $request, $key ) {
              return is_email( $param );
            },
          ),
          'user_password' => array(
            'required' => true,
          ),
        )
      )
    );

    register_rest_route(
      $this->namespace,
      '/reset_password',
      array(
        'methods'  => 'POST',
        'callback' => array( $guest, 'reset_password' ),
        'permission_callback' => '__return_true',
        'args' => array(
          'user_email' => array(
            'required'          => true,
            'validate_callback' => function( $param, $request, $key ) {
              return is_email( $param );
            },
          ),
        )
      )
    );


    register_rest_route(
      $this->namespace,
      '/get_user_nonce',
      array(
        'methods'  => 'POST',
        'callback' => array( $customer, 'get_user_nonce' ),
        'permission_callback' => array( $this, 'is_logged_in' ),
      )
    );


    register_rest_route(
      $this->namespace,
      '/add_coupon_code',
      array(
        'methods'  => 'POST',
        'callback' => array( $customer, 'add_coupon_code' ),
        'args'     => array(
          'coupon_code' => array(
            'required' => true,
          ),
        ),
        'permission_callback' => array( $this, 'is_logged_in' ),
      )
    );


    register_rest_route(
      $this->namespace,
      '/remove_coupon_code',
      array(
        'methods'  => 'POST',
        'callback' => array( $customer, 'remove_coupon_code' ),
        'args'     => array(
          'coupon_code' => array(
            'required' => true,
          ),
        ),
        'permission_callback' => array( $this, 'is_logged_in' ),
      )
    );


    register_rest_route(
      $this->namespace,
      '/get_shipping_methods',
      array(
        'methods'  => 'POST',
        'callback' => array( $customer, 'get_shipping_methods' ),
        'permission_callback' => array( $this, 'is_logged_in' ),
      )
    );


    register_rest_route(
      $this->namespace,
      '/payment',
      array(
        'methods'  => 'POST',
        'callback' => array( $customer, 'payment' ),
        'permission_callback' => array( $this, 'is_logged_in' ),
      )
    );


    register_rest_route(
      $this->namespace,
      '/get_customer_data',
      array(
        'methods'  => 'POST',
        'callback' => array( $customer, 'get_customer_data' ),
        'permission_callback' => array( $this, 'is_logged_in' ),
      )
    );


    register_rest_route(
      $this->namespace,
      '/cart_hash',
      array(
        'methods'  => 'POST',
        'callback' => function() {
          return WC()->cart->get_cart_hash();
        },
        'permission_callback' => array( $this, 'is_logged_in' ),
      )
    );


    register_rest_route(
      $this->namespace,
      '/plugin_installed',
      array(
        'methods'  => 'GET',
        'permission_callback' => '__return_true',
        'callback' => [ $this, 'plugin_installed_response' ]
      )
    );
  }


  public function plugin_installed_response( $request ) {
    $data = get_option( 'wc_checkout_pro_settings', [] );

    return [
      'result' => true,
      'data'   => [
        'account_id' => isset( $data['account_id'] ) ? $data['account_id'] : null,
        'version'    => \WC_Checkout_PRO::VERSION
      ]
    ];
  }


  /**
   * is_logged_in
   *
   * @param mixed $request
   * @return void
   */
  public function is_logged_in( $request = null ) {
    return is_user_logged_in();
  }
}
