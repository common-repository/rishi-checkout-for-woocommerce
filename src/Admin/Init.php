<?php

namespace WC_Checkout_PRO\Admin;

use WC_Checkout_PRO;

defined( 'ABSPATH' ) or exit;

/**
 * @since 1.1.0
 */
class Init {
  function __construct() {
    add_filter( 'woocommerce_integrations', [ $this, 'register_integration' ] );

		add_filter(
			'plugin_action_links_' . \plugin_basename( WC_Checkout_PRO::get_main_file() ),
			[ $this, 'add_settings_link' ]
		);
  }

  public function register_integration( $integrations ) {
    $integrations[] = Settings::class;

    return $integrations;
  }

	public function add_settings_link( $links ) {
		$url = admin_url( "/admin.php?page=wc-settings&tab=integration&section=rishi" );
		$label = esc_html__( 'Configurações', 'wc-checkout-pro' );
		$link = "<a href='$url'>$label</a>";

    array_unshift( $links, $link );

    return $links;
	}
}
