<?php
/**
 * Missing WooCommerce notice.
 *
 * @package Magic_Framework/Admin/Notices
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_installed = false;

if ( function_exists( 'get_plugins' ) ) {
	$all_plugins  = get_plugins();
	$is_installed = ! empty( $all_plugins['woocommerce/woocommerce.php'] );
}

?>

<div class="error">
	<p><strong><?php esc_html_e( $plugin_name, 'magic-framework' ); ?></strong> <?php esc_html_e( 'requer a última versão do WooCommerce para funcionar!', 'magic-framework' ); ?></p>

	<?php if ( $is_installed && current_user_can( 'install_plugins' ) ) : ?>
		<p><a href="<?php echo esc_url( wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=woocommerce/woocommerce.php&plugin_status=active' ), 'activate-plugin_woocommerce/woocommerce.php' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Ativar WooCommerce', 'magic-framework' ); ?></a></p>
	<?php else :
		if ( current_user_can( 'install_plugins' ) ) {
			$url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=woocommerce' ), 'install-plugin_woocommerce' );
		} else {
			$url = 'https://wordpress.org/plugins/woocommerce/';
		}
	?>
		<p><a href="<?php echo esc_url( $url ); ?>" class="button button-primary"><?php esc_html_e( 'Instalar WooCommerce', 'magic-framework' ); ?></a></p>
	<?php endif; ?>
</div>
