<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

use WC_Checkout_PRO\Features\Settings;

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.5">
  <link rel="profile" href="http://gmpg.org/xfn/11">

  <?php wp_head(); ?>

  <script type='text/javascript'>
    /* <![CDATA[ */
    <?php
      $keys = get_option( 'wc_checkout_pro_public_scripts', array() );
      $load_scripts = apply_filters( 'wc_checkout_pro_cache_scripts', get_transient( 'wc_checkout_pro_cache_scripts' ) );

      foreach ( $keys as $key => $object_name ) {
        $value  = get_option( 'wc_checkout_pro_' . $key, array() );

        if ( ! $value ) {
          continue;
        }

        if ( 'settings' === $key ) {
          $settings = get_transient( 'wc_checkout_pro_cache_settings' );

          if  ( false === $load_scripts || false === $settings ) {
			      $load_scripts = [];

            $value['paymentMethods'] = isset( $value['paymentMethods'] ) && is_array( $value['paymentMethods'] ) ? $value['paymentMethods'] : [];

            $available_gateways = WC()->payment_gateways()->get_available_payment_gateways();

            $payment_methods = [];
            $settings = new Settings();

            foreach ( $available_gateways as $k => $method ) {
              if ( 'yes' !== $method->enabled ) {
                continue;
              }

              $settings->{'get_' . str_replace( '-', '_', $method->id ) . '_args'}( $payment_methods, $method );
            }

            foreach ( $payment_methods as $k => $method ) {
              // prepare external scripts
              if ( isset( $method['script'] ) ) {
                $load_scripts[ $method['internal_id'] ] = $method['script'];
                unset( $payment_methods[ $k ]['script'] );
              }

              // handle custom parameters
              foreach ( $value['paymentMethods'] as $api_method ) {
                if ( $api_method['id'] === $method['id'] ) {
                  $payment_methods[ $k ]['extra_data'] = isset( $api_method['extra_data'] ) ? $api_method['extra_data'] : [];
                  $payment_methods[ $k ]['discount'] = isset( $api_method['discount'] ) ? $api_method['discount'] : 0;
                }
              }
            }

            $value['paymentMethods'] = $payment_methods;

            set_transient( 'wc_checkout_pro_cache_settings', $value, DAY_IN_SECONDS );
			      set_transient( 'wc_checkout_pro_cache_scripts', $load_scripts, DAY_IN_SECONDS );

          } else {
            $value = $settings;
          }

          // never cache this info!
          $value['loggedIn'] = is_user_logged_in();
          $value['token']    = is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '';
        }

        echo "var $object_name = " . wp_json_encode( $value ) . ';';
      }

      echo "\n";
      ?>
    /* ]]> */
  </script>
  <?php
  $extras = get_option( 'wc_checkout_pro_extra_content', array() );
    foreach ( $extras as $extra ) {
      echo $extra . "\n";
    }
    echo "\n";

    if ( $rollbar_key = get_option( 'wc_checkout_pro_rollbar', '' ) ) {
    include_once 'views/rollbar-settings.php';
    }
  ?>
</head>

<body>
  <div id="root">
    <style type="text/css">
      body,
      html {
        background: #fafafa;
        height: 100%;
      }

      body {
        display: flex;
        align-items: center;
        justify-content: center;
      }

      .spinner {
        width: 40px;
        height: 40px;
        vertical-align: text-bottom;
        border: 3px solid #8599b5;
        border-right-color: transparent;
        border-radius: 50%;
        animation: spinner .75s linear infinite;
      }

      @keyframes spinner {
        to { transform: rotate(360deg); }
      }
    </style>
    <div class="spinner"></div>
  </div>

  <?php  foreach ( $load_scripts as $script ) {
    echo $script . "\n";
  } ?>

  <?php wp_footer(); ?>
</body>
</html>
