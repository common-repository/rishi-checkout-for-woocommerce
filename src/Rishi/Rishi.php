<?php

namespace WC_Checkout_PRO\Rishi;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

class Rishi {
	/** plugin id */
	const PLUGIN_ID = 'rishi-checkout-for-woocommerce';

	/** the app hostname */
	const HOSTNAME = 'rishi.com.br';


	/**
	 * Returns the plugin documentation url.
	 *
	 * @since 1.0.0
	 *
	 *
	 * @return string documentation URL
	 */
	public function get_documentation_url() {

		return 'http://help.rishi.com.br/';
	}


	/**
	 * Returns the Rishi hostname.
	 *
	 * @sine 1.1.0
	 *
	 * @return string
	 */
	public function get_hostname() {

		/**
		 * Filters the Rishi hostname, used in development for changing to
		 * dev/staging instances.
		 *
		 * @since 1.0.0
		 *
		 * @param string $hostname
		 * @param \Rishi $this instance
		 */
		return apply_filters( 'wc_rishi_hostname', self::HOSTNAME, $this );
	}


	/**
	 * Returns the app hostname.
	 *
	 * @since 1.0.0
	 *
	 * @return string app hostname, defaults to app.rishi.com.br
	 */
	public function get_app_hostname() {

		/**
		 * Filters the app Hostname.
		 *
		 * @since 1.0.0
		 *
		 * @param string app hostname
		 * @param \Rishi plugin instance
		 */
		return apply_filters( 'wc_rishi_app_hostname', sprintf( 'app.%s', $this->get_hostname() ), $this );
	}


	/**
	 * Returns the api hostname.
	 *
	 * @since 1.0.0
	 *
	 * @return string api hostname, defaults to api.rishi.com
	 */
	public function get_api_hostname() {

		/**
		 * Filters the API Hostname.
		 *
		 * @since 1.0.0
		 *
		 * @param string api hostname
		 * @param \Rishi plugin instance
		 */
		return apply_filters( 'wc_rishi_api_hostname', sprintf( 'api.%s', $this->get_hostname() ), $this );
	}


	/**
	 * Returns an app endpoint with an optionally provided path
	 *
	 * @since 1.0.0
	 *
	 * @param string $path
	 * @return string
	 */
	public function get_app_endpoint( $path = '' ) {

		/**
		 * returns URL like https://app.rishi.com.br/$path
		 *
		 * @since 1.0.0
		 *
		 * @param string endpoint URL
		 * @param \Rishi plugin instance
		 */
		return apply_filters( 'wc_rishi_app_endpoint', sprintf( 'https://%1$s/%2$s', $this->get_app_hostname(), $path ), $this );
	}


	/**
	 * Returns the connection initialization URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string url
	 */
	public function get_connect_url() {

		return add_query_arg( array(
      'wc-api'  => 'rishi_checkout',
      'connect' => 'init',
      'nonce'   => wp_create_nonce( 'wc-rishi-connect-init' )
		), get_home_url() );
	}


	/**
	 * Returns the connection callback URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string url
	 */
	public function get_callback_url() {

		return add_query_arg( array(
      'wc-api'  => 'rishi_checkout',
      'connect' => 'done',
		), get_home_url() );
	}


	/**
	 * Returns the app Sign In setup URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_sign_in_url() {

		return $this->get_app_endpoint();
	}


	/**
	 * Returns the current shop domain, including the path if this is a
	 * Multisite directory install.
	 *
	 * @since 1.0.0
	 *
	 * @return string the current shop domain. e.g. 'example.com' or 'example.com/fr'
	 */
	public function get_shop_domain() {

		$domain = parse_url( get_home_url(), PHP_URL_HOST );
		$path   = parse_url( get_home_url(), PHP_URL_PATH );

		if ( $path && 'yes' !== get_option( 'rishi_exclude_path_from_shop_domain' ) ) {
			$domain .= $path;
		}

		return $domain;
	}


	/**
	 * Returns the shop admin email, or current user's email if the former is not available.
	 *
	 * @since 1.0.0
	 *
	 * @return string email
	 */
	public function get_admin_email() {

		$email = get_option( 'admin_email' );

		if ( ! $email ) {
			$current_user = wp_get_current_user();
			$email        = $current_user->user_email;
		}

		return $email;
	}


	/**
	 * Returns the shop admin's first name, or the current user's if the former is not available.
	 *
	 * @since 1.0.0
	 *
	 * @return string the first name
	 */
	public function get_admin_first_name() {

		$user = get_user_by( 'email', $this->get_admin_email() );

		if ( ! $user ) {
			$user = wp_get_current_user();
		}

		return $user->user_firstname;
	}


	/**
	 * Returns the shop admin's last name, or the current user's if the former is not available.
	 *
	 * @since 1.0.0
	 *
	 * @return string the last name
	 */
	public function get_admin_last_name() {

		$user = get_user_by( 'email', $this->get_admin_email() );

		if ( ! $user ) {
			$user = wp_get_current_user();
		}

		return $user->user_lastname;
	}


	public function get_settings_url() {
		return admin_url( 'admin.php?page=wc-settings&tab=integration&section=rishi' );
	}


	public function set_shop_id( $shop_id ) {
		update_option( 'rishi_shop_id', $shop_id );
	}


	public function get_shop_id() {
		return get_option( 'rishi_shop_id' );
	}


	public function delete_shop_id() {
		delete_option( 'rishi_shop_id' );
	}


	public function set_public_api_key( $api_key ) {
		update_option( 'rishi_public_api_key', $api_key );
	}


	public function delete_public_api_key() {
		delete_option( 'rishi_public_api_key' );
	}
}
