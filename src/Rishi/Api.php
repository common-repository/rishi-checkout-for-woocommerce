<?php

namespace WC_Checkout_PRO\Rishi;

use Exception;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

class Api {
	/**
	 * Returns the Rishi Connect endpoint.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function get_connect_endpoint() {

		return rishi()->get_app_endpoint( 'api/connect/woocommerce' );
	}

	/**
	 * Returns the Rishi Connect endpoint.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function get_connect_hash_endpoint() {
		return add_query_arg( [
			'action' => 'create',
			'domain' => rishi()->get_shop_domain(),
		], rishi()->get_app_endpoint( 'api/connect/hash' ) );
	}


	public function set_installation_hash() {
		$response = wp_remote_get( $this->get_connect_hash_endpoint() );

		if ( is_wp_error( $response ) ) {
			throw new \Exception( __( 'Ocorreu um erro interno. Tente novamente.', 'wc-checkout-pro' ) );
		}

		$body = json_decode( $response['body'] );

		if ( ! isset( $body->hash ) ) {
			throw new \Exception( __( 'Erro na resposta do servidor. Tente novamente ou entre em contato para obter assistência.', 'wc-checkout-pro' ) );
		}

		update_option( 'rishi_connection_hash', serialize( $body->hash ) );
	}


	public function get_installation_hash() {
		$hash = get_option( 'rishi_connection_hash' );

		return maybe_unserialize( $hash );
	}


	public function delete_installation_hash() {
		delete_option( 'rishi_connection_hash' );

		return true;
	}


	/**
	 * Generates a WC REST API key for Rishi to use.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id WordPress user ID
	 * @return object
	 * @throws Exception
	 */
	public function create_key( $user_id = null ) {
		global $wpdb;

		// if no user is specified, try the current user or find an eligible admin
		if ( ! $user_id ) {

			$user_id = get_current_user_id();

			// if the current user can't manage WC, try and get the first admin
			if ( ! user_can( $user_id, 'manage_woocommerce' ) ) {

				$user_id = null;

				$administrator_ids = get_users( array(
					'role'   => 'administrator',
					'fields' => 'ID',
				) );

				foreach ( $administrator_ids as $administrator_id ) {

					if ( user_can( $administrator_id, 'manage_woocommerce' ) ) {

						$user_id = $administrator_id;
						break;
					}
				}

				if ( ! $user_id ) {
					throw new Exception( 'No eligible users could be found' );
				}
			}

		// otherwise, check the user that's specified
		} elseif ( ! user_can( $user_id, 'manage_woocommerce' ) ) {

			throw new Exception( "User {$user_id} does not have permission" );
		}

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			throw new Exception( 'Invalid user' );
		}

		$consumer_key    = 'ck_' . wc_rand_hash();
		$consumer_secret = 'cs_' . wc_rand_hash();

		$description = __( 'Rishi for WooCommerce', 'wc-checkout-pro' );

		$result = $wpdb->insert(
			$wpdb->prefix . 'woocommerce_api_keys',
			array(
				'user_id'         => $user->ID,
				'description'     => $description,
				'permissions'     => 'read_write',
				'consumer_key'    => wc_api_hash( $consumer_key ),
				'consumer_secret' => $consumer_secret,
				'truncated_key'   => substr( $consumer_key, -7 ),
			),
			array(
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			)
		);

		if ( ! $result ) {
			throw new Exception( 'The key could not be saved' );
		}

		$key = new \stdClass();

		$key->key_id          = $wpdb->insert_id;
		$key->user_id         = $user->ID;
		$key->consumer_key    = $consumer_key;
		$key->consumer_secret = $consumer_secret;

		// store the new key ID
		$this->set_key_id( $key->key_id );

		return $key;
	}


	/**
	 * Gets the configured WC REST API key.
	 *
	 * @since 1.5.0
	 *
	 * @return object|null
	 */
	public function get_key() {
		global $wpdb;

		$key = null;

		if ( $id = $this->get_key_id() ) {

			$key = $wpdb->get_row( $wpdb->prepare( "
				SELECT key_id, user_id, permissions, consumer_secret
				FROM {$wpdb->prefix}woocommerce_api_keys
				WHERE key_id = %d
			", $id ) );
		}

		return $key;
	}


	/**
	 * Sets a WC REST API key ID.
	 *
	 * @since 1.5.0
	 *
	 * @param int $id key ID
	 */
	public function set_key_id( $id ) {
		update_option( 'rishi_wc_api_key_id', $id );
	}


	/**
	 * Gets the configured WC REST API key ID.
	 *
	 * @since 1.5.0
	 *
	 * @return int
	 */
	public function get_key_id() {
		return (int) get_option( 'rishi_wc_api_key_id' );
	}


	/**
	 * Revokes the configured WC REST API key.
	 *
	 * @since 1.5.0
	 */
	public function revoke_key() {
		global $wpdb;

		if ( $key_id = $this->get_key_id() ) {
			$wpdb->delete( $wpdb->prefix . 'woocommerce_api_keys', array( 'key_id' => $key_id ), array( '%d' ) );
		}

		delete_option( 'rishi_wc_api_key_id' );
	}


	public function set_connection_status( $status ) {

		update_option( 'rishi_connection_status', esc_attr( $status ) );
	}


	public function get_connection_status() {
		return get_option( 'rishi_connection_status' );
	}
}
