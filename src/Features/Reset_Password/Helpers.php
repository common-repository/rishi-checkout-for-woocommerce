<?php
namespace WC_Checkout_PRO\Features\Reset_Password;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

class Helpers {
  public function save_login_link( $user ) {
    $user_id = $user->ID;
    $key     = substr( wp_hash( wp_generate_password( 60, true, true ) ), 0, 20 );
    update_user_meta( $user_id, '_wc_checkout_pro_login', array(
      'expires_at' => time() + 120 * MINUTE_IN_SECONDS,
      'key'        => $key,
    ) );

    return add_query_arg( array( 'uid' => $user_id, 'wccp_login' => $key ), home_url() );
  }
}
