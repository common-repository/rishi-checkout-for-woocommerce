<?php
namespace WC_Checkout_PRO\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

Class Templates {
  public function __construct() {
    add_action( 'template_include', array( $this, 'custom_template' ), 9999 );
    add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ), 9999 );
    // add_action( 'template_redirect', array( $this, 'clear_checkout' ), 9999 );
  }
  public function custom_template( $template ) {
    if ( is_checkout() && ! is_checkout_pay_page() && ! is_order_received_page() && ! isset( $_GET['default'] ) ) {
      // check if has the plugin settings
      if ( get_option( 'wc_checkout_pro_script_url' ) ) {
        $template = \WC_Checkout_PRO::get_templates_path() . 'checkout.php';

        $this->clear_checkout();
      }
    }

    return $template;
  }

  public function register_scripts() {
    // check if has the plugin settings
    if ( $script_url = get_option( 'wc_checkout_pro_script_url' ) ) {
      wp_register_script(
        'wc-checkout-pro',
        apply_filters( 'wccp_script_url', $script_url ),
        array(),
        1.0,
        true
      );

      if ( $hotjar_id = get_option( 'wc_checkout_pro_hotjar_id' ) ) {
        wp_add_inline_script( 'wc-checkout-pro', "(function(h,o,t,j,a,r){
          h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
          h._hjSettings={hjid:" . $hotjar_id . ",hjsv:6};
          a=o.getElementsByTagName('head')[0];
          r=o.createElement('script');r.async=1;
          r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
          a.appendChild(r);
        })(window,document,'https://static.hotjar.com/c/hotjar-','.js?sv=');" );
      }
    }
  }

  public function clear_checkout() {
    if ( is_checkout() && ! is_checkout_pay_page() && ! is_order_received_page() && ! isset( $_GET['default'] ) ) {
      remove_all_actions( 'wp_head' );
      remove_all_actions( 'wp_print_styles' );
      remove_all_actions( 'wp_print_head_scripts' );
      remove_all_actions( 'wp_footer' );

      wp_enqueue_scripts();

      add_action( 'wp_head', '_wp_render_title_tag', 1 );
      add_action( 'wp_footer', array( $this, 'print_checkout_script' ), 9999 );
    }
  }

  public function print_checkout_script() {
    wp_print_scripts( array( 'wc-checkout-pro' ) );
  }
}
