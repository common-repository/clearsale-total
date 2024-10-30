<?php

/**
 * Fired during plugin activation
 *
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      "1.0.0"
 * @package    Clearsale_Total
 * @subpackage Clearsale_Total/includes
 * @link       https://letti.com.br/wordpress
 * @author     Letti Tecnologia <contato@letti.com.br>
 */
class Clearsale_Total_Activator
{

	/**
	 * Create specific order statuses.
	 *
	 * Create a specific order statuses using register_post_status.
	 *
	 * @since    2.0.0
	 */
/*	public static function register_my_new_order_statuses()
	{
		register_post_status('wc-cs-inanalisis', array(
			'label'                     => 'Analise de Risco ClearSale',
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop('Analise de Risco CearSale <span class="count">(%s)</span>', 'Analise de Risco CearSale<span class="count">(%s)</span>')
		) );
	}

	public static function add_new_order_status($order_statuses)
	{
		$order_statuses['wc-cs-inanalisis'] = _x('Analise de Risco CearSale', 'Woocommerce Order Status', 'text_domain');
		return $order_statuses;
	}
*/
	public static function create_table()
    {
        global $wpdb;

        $table_name = $wpdb->prefix.'cs_total_dadosextras';

        $collate = '';

        if ($wpdb->has_cap('collation')) {
            if (!empty($wpdb->charset)) {
                $collate .= "DEFAULT CHARACTER SET $wpdb->charset";
            }
            if (!empty($wpdb->collate)) {
                $collate .= " COLLATE $wpdb->collate";
            }
        }

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                        id INT(11) NOT NULL AUTO_INCREMENT,
						chave VARCHAR(64) NOT NULL,
						metodo INT(11),
                        dados VARCHAR(256) NOT NULL,
                        capturado_data DATETIME NOT NULL,
						PRIMARY KEY pkcs_id (id),
						UNIQUE KEY `chave` (`chave`)
                                                    ) $collate;";

        $wpdb->query($sql);
    } // end of create_table


	/**
	 * Short Description.
	 *
	 * Long Description.
	 *
	 * @since    2.0.0
	 */
	public static function activate() {

		global $wp;

		//criar uma tabela para salvar dados de cartao dos metodos que não o Pagseguro
		// por hora não temos como vincular os dados salvos pelo ajax com o pedido que vai ser criado.
		Clearsale_Total_Activator::create_table();

		$wl = new Clearsale_Total_Log("clearsale-total", "3.1.0");

		$este_site = home_url($wp->request);
		$wl->write_log("Activate: Esta loja " . $este_site . " versao: ". CLEARSALE_TOTAL_VERSION . " vai ser ativada agora!");

		// New order status AFTER woo 2.2
////		add_action('init', 'register_my_new_order_statuses');
	//add_action('init', array(Clearsale_Total_Activator::class, 'register_my_new_order_statuses'));
////		add_action( 'init', array(WC_PagSeguro_Status::class, 'register_ps_iniciado'));
	////	add_filter('wc_order_statuses', 'add_new_order_status');
	//add_filter('wc_order_statuses', array(Clearsale_Total_Activator::class, 'add_new_order_status'));

		// https://developer.wordpress.org/reference/functions/wp_remote_post/

		$url = "http://lx17.letti.com.br/clear_woo/ativos.php";
		//$url = "http://vps-cs.letti.com.br/woocommerce/ativos.php";

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
			$wl->write_log("Activate: erro no wp_remote_post: " . print_r($error_message,true));
			//syslog(LOG_WARNING, "Something went wrong:" . $error_message);
		} else {
			$response = $ret['response'];
			$code = $response['code'];
            if ($code == "200") {
                $wl->write_log("Activate: Plugin e loja ativados com sucesso!");
            } else {
				$wl->write_log("Activate: Plugin e loja ativados com sucesso! ret code=" . $code);
			}
			//$wl->write_log("Activate: sucesso" . print_r($ret,true));
			//syslog(LOG_WARNING,"sucesso:" . print_r($ret,true));
		}
		/* O retorna no $ret acima:
		(
    		[headers] => Requests_Utility_CaseInsensitiveDictionary Object
        		(
            		[data:protected] => Array
                		(
                    		[date] => Tue, 24 Aug 2021 19:39:36 GMT
                    		[server] => Apache/2.4.6 (CentOS) OpenSSL/1.0.2k-fips PHP/5.6.32
                    		[x-powered-by] => PHP/5.6.32
                    		[content-length] => 0
                    		[content-type] => text/html; charset=UTF-8
                		)
        		)
    		[body] =>
    		[response] => Array
        		(
            		[code] => 200
            		[message] => OK
        		)
    		[cookies] => Array
        		(
        		)
    		[filename] =>
    		[http_response] => WP_HTTP_Requests_Response Object
        		(
            		[response:protected] => Requests_Response Object
                		(
                    		[body] =>
                    		[raw] => HTTP/1.1 200 OK
									Date: Tue, 24 Aug 2021 19:39:36 GMT
									Server: Apache/2.4.6 (CentOS) OpenSSL/1.0.2k-fips PHP/5.6.32
									X-Powered-By: PHP/5.6.32
									Content-Length: 0
									Connection: close
									Content-Type: text/html; charset=UTF-8

                    [headers] => Requests_Response_Headers Object
                        (
                            [data:protected] => Array
                                (
                                    [date] => Array
                                        (
                                            [0] => Tue, 24 Aug 2021 19:39:36 GMT
                                        )
                                    [server] => Array
                                        (
                                            [0] => Apache/2.4.6 (CentOS) OpenSSL/1.0.2k-fips PHP/5.6.32
                                        )
                                    [x-powered-by] => Array
                                        (
                                            [0] => PHP/5.6.32
                                        )
                                    [content-length] => Array
                                        (
                                            [0] => 0
                                        )
                                    [content-type] => Array
                                        (
                                            [0] => text/html; charset=UTF-8
                                        )
                                )
                        )
                    [status_code] => 200
                    [protocol_version] => 1.1
                    [success] => 1
                    [redirects] => 0
                    [url] => http://lx17.letti.com.br/ativos.php
                    [history] => Array
                        (
                        )
                    [cookies] => Requests_Cookie_Jar Object
                        (
                            [cookies:protected] => Array
                                (
                                )
                        )
                )
            [filename:protected] =>
            [data] =>
            [headers] =>
            [status] =>
        )
		*/

	} // end of activate

}
