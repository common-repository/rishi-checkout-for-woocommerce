<?php

namespace WC_Checkout_PRO\Admin;

use WC_Integration;

defined( 'ABSPATH' ) or exit;

/**
 * @since 1.1.0
 */
class Settings extends WC_Integration {
	/**
	 * Initialize the integration.
	 */
	public function __construct() {
		$this->id                 = 'rishi';
		$this->method_title       = __( 'Rishi', 'wc-checkout-pro' );
		$this->method_description = __( 'Configure os detalhes da sua integração com Rishi.', 'wc-checkout-pro' );

		$this->init_form_fields();
		$this->init_settings();

		add_action( 'admin_init', [ $this, 'handle_connection' ] );
	}

	/**
	 * Initializes the settings fields.
	 */
	public function init_form_fields() {
		$this->form_fields = [
			'rishi_connection' => [
				'title'       => __( 'Status de Conexão', 'wc-checkout-pro' ),
				'type'        => 'rishi_connection',
			],
		];
	}


	public function generate_rishi_connection_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		$button_text = __( 'Conectar com Rishi', 'wc-checkout-pro' );

		$tip_class = 'error';
		$tip_icon = '&#10005;';
		$tip = __( 'Seu site ainda não está conectado. Faça a conexão para poder usar o plugin.', 'wc-checkout-pro' );
		$button = '<button style="margin-left: 10px;" type="submit" class="button button-primary" name="woocommerce_rishi_connect" id="woocommerce_rishi_connect">' . $button_text .'</button>';

		$status = rishi_api()->get_connection_status();

		switch ( $status ) {
			case 'success':
				$tip_class = 'success';
				$tip = __( 'Seu site foi conectado com sucesso.', 'wc-checkout-pro' );
				$tip_icon = '&#10004;';
				$button = '<a class="button button-primary" href="' . rishi()->get_app_endpoint('shops/' . rishi()->get_shop_id() ) . '" target="_blank" style="margin-left: 10px;">' . __( 'Acessar painel', 'wc-checkout-pro' ) .'</a>';
				$button .= '<button style="margin-left: 10px;" type="submit" class="button button button-secundary" name="woocommerce_rishi_disconnect" id="woocommerce_rishi_disconnect">' . __( 'Desconectar', 'wc-checkout-pro' ) .'</button>';
				$button .= "<script type=\"text/javascript\">jQuery('#woocommerce_rishi_disconnect').on( 'click', event => {
					var result = confirm('Você quer mesmo desconectar?');

					if ( result !== true ) {
						event.preventDefault();
					}
				} )</script>";
				break;
			case 'failed':
				$tip_class = 'warning';
				$tip = __( 'Seu site foi conectado com sucesso mas não foi possível enviar as credenciais. Tente novamente.', 'wc-checkout-pro' );
				$tip_icon = '&#9888;';
				$button_text = __( 'Tentar novamente.', 'wc-checkout-pro' );
				break;
			case 'error':
				$tip_class = 'error';
				$tip = __( 'Ocorreu um erro ao salvar seus dados no painel Rishi. Tente novamente.', 'wc-checkout-pro' );
				$tip_icon = '&#9888;';
				$button_text = __( 'Tentar novamente.', 'wc-checkout-pro' );
				break;
			default:
				break;
		}

		ob_start();

		?>
		<style>
			.rishi-error {
				color: #a00; background-color: transparent; cursor: help;
			}
			.rishi-warning {
				color: #ffb900; background-color: transparent; cursor: help;
			}

			.rishi-success {
				color: #7ad03a; background-color: transparent; cursor: help;
			}
		</style>


		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
			</th>
			<td class="forminp">
				<p class="woocommerce_rishi_status">
					<mark class="rishi-<?php echo $tip_class; ?> help_tip" data-tip="<?php echo $tip; ?>"><?php echo $tip_icon; ?></mark>

					<?php echo $button; ?>
				</p>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	public function handle_connection() {
		if ( get_option( 'rishi_activation_redirect' ) ) {
			delete_option( 'rishi_activation_redirect' );

			wp_redirect( rishi()->get_settings_url() );
			exit;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( isset( $_POST['woocommerce_rishi_connect'] ) ) {

			wp_safe_redirect( rishi()->get_connect_url() );
			exit;
		}

		if ( isset( $_POST['woocommerce_rishi_disconnect'] ) ) {

			rishi_api()->revoke_key();
			rishi_api()->set_connection_status( 'disconnected' );

			global $wpdb;
			$table  = $wpdb->options;
			$column = 'option_name';

			$key = $wpdb->esc_like( 'wc_checkout_pro_' ) . '%';

			$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE {$column} LIKE %s", $key ) );


			wp_safe_redirect( admin_url( '/admin.php?page=wc-settings&tab=integration&section=rishi' ) );
			exit;
		}
	}
}
