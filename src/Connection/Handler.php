<?php

namespace WC_Checkout_PRO\Connection;

use WC_Checkout_PRO;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

Class Handler {

	/**
	 * Sets up the API handle class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'woocommerce_api_rishi_checkout', array( $this, 'route' ) );
  }

	/**
	 * Handles requests to the Jilt WC API endpoint
	 *
	 * @since 1.0.0
	 */
	public function route() {

		// identify the response as coming from the Rishi Checkout for WooCommerce plugin
		@header( 'x-rishi-checkout-version: ' . WC_Checkout_PRO::VERSION );

		// handle connections
		if ( empty( $_REQUEST['connect'] ) ) {
			return;
		}

		if ( 'init' === $_REQUEST['connect'] ) {
			$this->handle_connect();
		} elseif ( 'done' === $_REQUEST['connect'] ) {
			$this->handle_connect_callback();
		}
  }


	/**
	 * Initiates the auth flow to connect the plugin to Rishi.
	 *
	 * @since 1.4.0
	 */
	private function handle_connect() {

		// check nonce
		if ( empty( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], 'wc-rishi-connect-init' ) ) {
			return;
		}

		// only shop managers can connect to Rishi
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$this->request_installation_hash(); // if this fails, user is redirected back to admin with a notice

		$state = wp_create_nonce( 'wc-rishicheckout-connect' );

		/**
		 * @param array redirect args
		 */
		$redirect_args = array(
			'domain'        => urlencode( rishi()->get_shop_domain() ),
			'email'         => urlencode( rishi()->get_admin_email() ),
			'first_name'    => urlencode( rishi()->get_admin_first_name() ),
			'last_name'     => urlencode( rishi()->get_admin_last_name() ),
			'ssl'           => is_ssl(),
			'state'         => $state,
			'redirect_uri'  => rawurlencode( rishi()->get_callback_url() ),
			'response_type' => 'code',
		);

		wp_redirect( add_query_arg( $redirect_args, rishi_api()->get_connect_endpoint() ) );
		exit();
	}



	private function request_installation_hash() {
		try {
			rishi_api()->set_installation_hash();
		} catch ( \Exception $e ) {
			$error = $e->getMessage();

			wp_die( $error );
		}
	}




	/**
	 * Handles callbacks from Rishi connect requests.
	 *
	 * @since 1.4.0
	 */
	private function handle_connect_callback() {

		// verify state
		if ( empty( $_GET['state'] ) || ! wp_verify_nonce( $_GET['state'], 'wc-rishicheckout-connect' ) ) {
			wp_die( 'Missing or invalid param: state' );
		}

		if ( empty( $_GET['code'] ) ) {
			wp_die( 'Missing or invalid param: code' );
		}

		if ( empty( $_GET['shop_id'] ) ) {
			wp_die( 'Missing or invalid param: shop_id' );
		}

		$response = null;

		try {
			$hash = rishi_api()->get_installation_hash();

			if ( ! $hash ) {
				throw new \Exception( 'Hash de segurança não encontrada. Reinicie o processo.' );
			}

			$response = wp_remote_post( add_query_arg(
				[
					'action' => 'compare',
					'domain' => rishi()->get_shop_domain(),
					'hash' => urlencode( maybe_unserialize( $hash ) )
				],
				rishi()->get_app_endpoint( 'api/connect/hash' )
			) );

			if ( is_wp_error( $response ) ) {
				wp_die( 'Ocorreu um erro. Reinicie o processo.' );
			}

			if ( ! isset( $response['body'] ) ) {
				wp_die( 'Resposta inválida do servidor. Reinicie o processo.' );
			}

			$body = json_decode( $response['body'] );

			if ( ! isset( $body->isValid ) || ! $body->isValid ) {
				wp_die( 'Conexão expirada. Tente reiniciar o processo.' );
			}

			$api_key = rishi_api()->create_key();

		} catch ( \Exception $e ) {

			$error = $e->getMessage();

			wp_die( $error );

			/* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag, %3$s - error message, %3$s - solution message */
			// $notice = sprintf( __( '%1$sError communicating with Jilt%2$s: %3$s %4$s', 'jilt-for-woocommerce' ),
			// 	'<strong>',
			// 	'</strong>',
			// 	$error ? ( ': ' . $error . '.' ) : '', // add full stop
			// 	sprintf(__( 'Please %1$sget in touch with Jilt Support%2$s to resolve this issue.', 'jilt-for-woocommerce' ),
			// 		'<a target="_blank" href="' . esc_url( wc_jilt()->get_support_url( array( 'message' => $error ) ) ) . '">',
			// 		'</a>'
			// 	)
			// );

			// save erros to display on settings page!
		}

		$response = wp_remote_post( rishi()->get_app_endpoint( 'api/connect/' . esc_attr( $_REQUEST['shop_id'] ) . '/keys' ), [
			'headers' => [
				'content-type' => 'application/json',
				'Authorization' => 'Bearer ' . esc_attr( $_REQUEST['code'] ),
			],
			'body' => json_encode([
        'key_id' => $api_key->key_id,
        'consumer_key' => $api_key->consumer_key,
        'consumer_secret' => $api_key->consumer_secret,
    	]),
		] );

		if ( is_wp_error( $response ) ) {
			rishi_api()->set_connection_status( 'failed' );
		} else {
			rishi_api()->set_connection_status( 200 === wp_remote_retrieve_response_code( $response ) ? 'success' : 'error' );
		}

		rishi()->set_public_api_key( esc_attr( $_REQUEST['code'] ) );
		rishi()->set_shop_id( esc_attr( $_REQUEST['shop_id'] ) );
		rishi_api()->delete_installation_hash();

		// save custom data!
		wp_redirect( rishi()->get_settings_url() );
		exit;
	}
}
