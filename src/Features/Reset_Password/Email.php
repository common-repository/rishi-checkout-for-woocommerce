<?php
namespace WCCP\Features\Reset_Password;
use WC_Email;
use WC_Checkout_PRO;

if ( ! class_exists( 'WC_Email', false ) ) {
  return;
}

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

/**
 * Instant Login email.
 */
class Email extends WC_Email {
  /**
   * Initialize tracking template.
   */
  public function __construct() {
    $this->id               = 'wccp_password';
    $this->title            = __( 'WC Checkout PRO - Login instantâneo', 'wc-checkout-pro' );
    $this->customer_email   = true;
    $this->description      = __( 'E-mail enviado quando o usuário solicita um link de login instantâneo no checkout.', 'wc-checkout-pro' );
    $this->heading          = __( 'Só mais um clique', 'wc-checkout-pro' );
    $this->subject          = __( '[{site_title}] O seu link de acesso', 'wc-checkout-pro' );
    $this->message          = __( 'Olá!', 'wc-checkout-pro' )
                  . PHP_EOL . ' ' . PHP_EOL
                  . __( 'Clique no link abaixo para acessar sua conta e finalizar sua compra.', 'wc-checkout-pro' )
                  . PHP_EOL . ' ' . PHP_EOL
                  . '{wccp_login_button}';
    $this->content          = $this->get_option( 'content', $this->message );
    $this->button_text      = $this->get_option( 'button_text', 'Finalizar minha compra' );
    $this->template_html    = 'emails/instant-login.php';

    // Call parent constructor.
    parent::__construct();

    $this->template_base = WC_Checkout_PRO::get_templates_path();
  }

  /**
   * Initialise settings form fields.
   */
  public function init_form_fields() {
    $this->form_fields = array(
      'subject' => array(
        'title'       => __( 'Assunto', 'wc-checkout-pro' ),
        'type'        => 'text',
        'description' => sprintf( __( 'Assunto do e-mail. Padrão: <code>%s</code>.', 'wc-checkout-pro' ), $this->subject ),
        'placeholder' => $this->subject,
        'default'     => '',
        'desc_tip'    => true,
      ),
      'heading' => array(
        'title'       => __( 'Cabeçalho', 'wc-checkout-pro' ),
        'type'        => 'text',
        'description' => sprintf( __( 'Cabeçalho exibido no e-mail. Padrão: <code>%s</code>.', 'wc-checkout-pro' ), $this->heading ),
        'placeholder' => $this->heading,
        'default'     => '',
        'desc_tip'    => true,
      ),
      'content' => array(
        'title'       => __( 'Conteúdo do e-mail', 'wc-checkout-pro' ),
        'type'        => 'textarea',
        'description' => sprintf( __( 'Não esqueça de incluir o link de login: <code>{wccp_login_button}</code>.', 'wc-checkout-pro' ), $this->message ),
        'placeholder' => $this->message,
        'default'     => '',
        'desc_tip'    => true,
      ),
      'button_text' => array(
        'title'       => __( 'Texto do botão', 'wc-checkout-pro' ),
        'type'        => 'text',
        'description' => __( 'Texto exibido no botão.', 'wc-checkout-pro' ),
        'placeholder' => 'Acessar minha conta',
        'default'     => 'Acessar minha conta',
        'desc_tip'    => true,
      ),
    );
  }

  /**
   * Get instant login button.
   *
   * @return string
   */
  public function get_wccp_login_button() {
    $html  = '<div class="wc-checkout-pro-button-wrapper"><a href="' . $this->url . '" target="_blank" class="wc-checkout-pro-button" href="' . '#' . '" >';
    $html .= $this->button_text;
    $html .= '</a></div>';

    return $html;
  }

  /**
   * Trigger email.
   *
   * @param  int      $order_id      Order ID.
   * @param  WC_Order $order         Order data.
   * @param  string   $tracking_code Tracking code.
   */
  public function trigger( $user, $url ) {
    if ( is_object( $user ) ) {
      $this->url       = $url;
      $this->recipient = $user->user_email;

      $this->placeholders['{wccp_login_button}']  = $this->get_wccp_login_button();
    }

    if ( ! $this->get_recipient() ) {
      return;
    }

    $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
  }

  /**
   * Get content HTML.
   *
   * @return string
   */
  public function get_content_html() {
    ob_start();

    wc_get_template( $this->template_html, array(
      'email_heading'    => $this->get_heading(),
      'content'          => $this->format_string( $this->content ),
      'sent_to_admin'    => false,
      'plain_text'       => false,
      'email'            => $this,
    ), '', $this->template_base );

    return ob_get_clean();
  }


  public function is_enabled() {
    return true;
  }

  public function get_email_type() {
    return 'html';
  }
}

return new Email();
