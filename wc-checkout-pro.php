<?php
/**
 * Plugin Name:          Rishi Checkout for WooCommerce
 * Plugin URI:           https://app.rishi.com.br
 * Description:          Um checkout que converte.
 * Author:               Rishi
 * Author URI:           https://rishi.com.br
 * Version:              1.0.7
 * License:              GPLv2 or later
 * WC requires at least: 3.5.0
 * WC tested up to:      5.0.0
 *
 * This plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * This plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this plugin. If not, see
 * <https://www.gnu.org/licenses/gpl-2.0.txt>.
 *
 * @package WC_Checkout_PRO
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

class WC_Checkout_PRO {
  /**
   * Version.
   *
   * @var float
   */
  const VERSION = '1.0.5';

  /**
   * Instance of this class.
   *
   * @var object
   */
  protected static $instance = null;
  /**
   * Initialize the plugin public actions.
   */
  function __construct() {
    $this->includes();
  }

  public function includes() {
    require __DIR__ . '/vendor/autoload.php';

    new \WC_Checkout_PRO\Api\Endpoints();
    new \WC_Checkout_PRO\Frontend\Templates();

    new WC_Checkout_PRO\Features\Reset_Password\Hooks();

    new WC_Checkout_PRO\Payment_Response();

    new WC_Checkout_PRO\WC_Api\Settings();

    new WC_Checkout_PRO\Connection\Handler();

    new WC_Checkout_PRO\Admin\Init();
  }


  /**
   * Return an instance of this class.
   *
   * @return object A single instance of this class.
   */
  public static function get_instance() {
    // If the single instance hasn't been set, set it now.
    if ( null == self::$instance ) {
      self::$instance = new self;
    }
    return self::$instance;
  }

  /**
   * Get main file.
   *
   * @return string
   */
  public static function get_main_file() {
    return __FILE__;
  }

  /**
   * Get plugin path.
   *
   * @return string
   */

  public static function get_plugin_path() {
    return plugin_dir_path( __FILE__ );
  }

  /**
   * Get the plugin url.
   * @return string
   */
  public static function plugin_url() {
    return untrailingslashit( plugins_url( '/', __FILE__ ) );
  }

  /**
   * Get the plugin dir url.
   * @return string
   */
  public static function plugin_dir_url() {
    return plugin_dir_url( __FILE__ );
  }

  /**
   * Get templates path.
   *
   * @return string
   */
  public static function get_templates_path() {
    return self::get_plugin_path() . 'templates/';
  }

  /**
   * WooCommerce missing notice.
   */
  public static function woocommerce_missing_notice() {
    $plugin_name = str_replace( '_', ' ', __CLASS__ );
    include dirname( __FILE__ ) . '/includes/admin/views/html-notice-missing-woocommerce.php';
  }
}

add_action( 'plugins_loaded', array( 'WC_Checkout_PRO', 'get_instance' ) );

register_activation_hook( __FILE__, 'wc_checkout_pro_install' );
function wc_checkout_pro_install() {
  update_option( 'rishi_activation_redirect', true);
}

register_deactivation_hook( __FILE__, 'wc_checkout_pro_uninstall' );
function wc_checkout_pro_uninstall() {

}
