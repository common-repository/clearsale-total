<?php

/**
 * Fired during plugin deactivation
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Clearsale_Total
 * @subpackage Clearsale_Total/includes
 * @author     Letti Tecnologia <contato@letti.com.br>
 * @link       https://letti.com.br/wordpress
 * @version    1.0.0
 */
class Clearsale_Total_Deactivator
{

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    "1.0.0"
	 */
	public static function deactivate()
	{

		global $wp;

		$wl = new Clearsale_Total_Log("clearsale-total", "3.0.15");

		$este_site = home_url($wp->request);
		$wl->write_log("Deactivate: Esta loja " . $este_site . " versao: " . CLEARSALE_TOTAL_VERSION . " vai ser desativada agora!");

		// https://developer.wordpress.org/reference/functions/wp_remote_post/

		$url = "http://lx17.letti.com.br/clear_woo/desativar.php";
		//$url = "http://vps-cs.letti.com.br/woocommerce/desativar.php";
		

		$body = array("chave" => "Y9e7s5", "um" => $este_site, "produto" => 'total', "ver" => CLEARSALE_TOTAL_VERSION);
		$args = array(
			'method'      => 'POST',
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => array(),
			'body'        => $body,
			'cookies'     => array()
		);

		$ret = wp_remote_post($url, $args);
		if ( is_wp_error($ret) ) {
			$error_message = $ret->get_error_message();
			$wl->write_log("Deactivate: erro no wp_remote_post: " . print_r($error_message,true));
			//syslog(LOG_WARNING, "Something went wrong:" . $error_message);
		} else {
			$response = $ret['response'];
			$code = $response['code'];
            if ($code == "200") {
                $wl->write_log("Deactivate: Plugin e loja desativada com sucesso!");
            } else {
				$wl->write_log("Deactivate: Plugin e loja desativada com sucesso! ret code=" . $code);
			}
			//$wl->write_log("Activate: sucesso" . print_r($ret,true));
			//syslog(LOG_WARNING,"sucesso:" . print_r($ret,true));
		}
	}

}
