<?php
namespace WC_Checkout_PRO\Features;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

/**
*
*/
class Settings {
  function __call( $name, $arguments ) {
    if ( method_exists( $this, $name ) ) {
      return $this->$name( $arguments );
    }

    return $name;
    return false;
  }


  public function get_cheque_args( &$payment_methods, $method ) {
    $payment_methods[] = [
      'id'          => $method->id,
      'internal_id' => $method->id
    ];
  }

  public function get_cod_args( &$payment_methods, $method ) {
    $payment_methods[] = [
      'id'          => $method->id,
      'internal_id' => $method->id
    ];
  }

  public function get_juno_credit_card_args( &$payment_methods, $method ) {
    $settings = get_option( 'woocommerce_juno-integration_settings', [] );

    $payment_methods[] = [
      'id'          => $method->id,
      'internal_id' => $method->id,
      'public_key'  => $settings['public_token'],
      'production'  => 'no' === $settings['test_mode'],
      'max_installment'  => $method->max_installments,
      'smallest_installment'  => $method->smallest_installment,
      'installments_fee'  => $method->settings['installments_fee'],
      'script'      => '<script async type="text/javascript" src="https://www.boletobancario.com/boletofacil/wro/direct-checkout.min.js?ver=1.1.0" id="junocheckout"></script>'
    ];
  }

  public function get_juno_bank_slip_args( &$payment_methods, $method ) {
    $payment_methods[] = [
      'id'          => $method->id,
      'internal_id' => $method->id,
    ];
  }

  public function get_bacs_args( &$payment_methods, $method ) {
    $payment_methods[] = [
      'id'          => $method->id,
      'internal_id' => $method->id,
    ];
  }

  public function get_paypal_args( &$payment_methods, $method ) {
    $payment_methods[] = [
      'id'          => $method->id,
      'internal_id' => $method->id,
    ];
  }

  public function get_pagseguro_args( &$payment_methods, $method ) {
    $api        = new \WC_PagSeguro_API( $method );
    $session_id = $api->get_session_id();

    // obrigatório
    if ( ! $session_id ) {
      return;
    }

    if ( 'transparent' !== $method->method ) {
      $payment_methods[] = [
        'id'          => $method->id,
        'internal_id' => $method->id,
        'session_id'  => $session_id,
      ];

      return false;
    }

    if ( $method->tc_credit === 'yes' ) {
      $payment_methods[] = [
        'id'          => 'pagseguro-credit-card',
        'internal_id' => $method->id,
        'session_id'  => $session_id,
        'script'      => 'yes' === $method->sandbox ? '<script async type="text/javascript" src="https://stc.sandbox.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.directpayment.js"></script>' : '<script async type="text/javascript" src=
        "https://stc.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.directpayment.js"></script>'
      ];
    }

    if ( $method->tc_ticket === 'yes' ) {
      $payment_methods[] = [
        'id'          => 'pagseguro-bank-slip',
        'internal_id' => $method->id,
        'session_id'  => $session_id,
        'script'      => 'yes' === $method->sandbox ? '<script async type="text/javascript" src="https://stc.sandbox.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.directpayment.js"></script>' : '<script async type="text/javascript" src=
        "https://stc.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.directpayment.js"></script>'
      ];
    }
  }


  public function get_pagarme_credit_card_args( &$payment_methods, $method ) {
    $payment_methods[] = [
      'id'             => $method->id,
      'internal_id'    => $method->id,
      'encryption_key' => $method->encryption_key,
      'max_installment' => $method->max_installment,
      'smallest_installment' => $method->smallest_installment,
      'interest_rate'     => $method->interest_rate,
      'free_installments' => $method->free_installments,
      'script'         => '<script async src="https://assets.pagar.me/pagarme-js/4.5/pagarme.min.js"></script>'
    ];
  }


  public function get_pagarme_banking_ticket_args( &$payment_methods, $method ) {
    $payment_methods[] = [
      'id'          => $method->id,
      'internal_id' => $method->id
    ];
  }


  public function get_woo_mercado_pago_custom_args( &$payment_methods, $method ) {
    $payment_methods[] = [
      'id'          => $method->id,
      'internal_id' => $method->id,
      'sandbox'     => $method->sandbox,
      'public_key'  => $method->sandbox ? $method->mp_public_key_test : $method->mp_public_key_prod,
      'script'      => '<script async src="https://secure.mlstatic.com/sdk/javascript/v1/mercadopago.js"></script>'
    ];
  }


  public function get_woo_mercado_pago_ticket_args( &$payment_methods, $method ) {
    $payment_methods[] = [
      'id'          => $method->id,
      'internal_id' => $method->id,
    ];
  }


  public function get_itau_shopline_args( &$payment_methods, $method ) {
    $payment_methods[] = [
      'id'          => $method->id,
      'internal_id' => $method->id,
    ];
  }

  public function get_woo_moip_official_args( &$payment_methods, $method ) {
    $model = new \Woocommerce\Moip\Model\Custom_Gateway();

    // credit card is enabled!
    if ( 'transparent_checkout' === $model->settings->payment_api && 'yes' === $model->settings->credit_card ) {
      $payment_methods[] = [
        'id'          => 'woo-moip-official-credit-card',
        'internal_id' => $method->id,
        'script'      => '<script src="//assets.moip.com.br/v2/moip.min.js"></script>',
        'public_key'  => $model->settings->public_key,
        'max_installment'      => 'yes' === $model->settings->installments_enabled ? $model->settings->installments_maximum : 1,
        'smallest_installment' => 'yes' === $model->settings->installments_enabled ? $model->settings->installments_minimum : 1,
        'installments_fee' => is_array( $model->settings->installments['interest'] ) ? $model->settings->installments['interest'] : [],
      ];
    }

    // bank slip!
    if ( 'yes' === $model->settings->billet_banking ) {

    }
  }
}
