<?php

/**
 * Register all actions for log page
 */

/**
 * Register all actions for the log in the wp-content/debug.log
 * Grava log no wp-content/debug.log de tiver LIGADO o debug no wp-config.php
 * define('WP_DEBUG', true);
 * define('WP_DEBUG_LOG', true);
 *
 * @package    Clearsale_Total
 * @subpackage Clearsale_Total/includes
 * @author     Letti Tecnologia <contato@letti.com.br>
 * @link       https://letti.com.br/wordpress
 * @since      "1.0.0"

 */
class Clearsale_Total_Log
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;
    
	
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name       The name of this plugin.
	 * @param    string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name="", $version="" )
	{

		if ($plugin_name) $this->plugin_name = $plugin_name;
		if ($version) $this->version = $version;
	}


	/**
	* Grava log no wp-content/debug.log de tiver LIGADO o debug no wp-config.php
	* define('WP_DEBUG', true);
	* define('WP_DEBUG_LOG', true);
	* @since	"1.0.0"
	* @param	string	$log - string a ser gravada no arquivo de log
	*/
	public function write_log1($log)
	{
		if (true === WP_DEBUG) {
			$tmp = $this->plugin_name . ":";
			if (is_array($log) || is_object($log)) {
				error_log($tmp . print_r($log, true));
			} else {
				error_log($tmp . $log);
			}
		} // end of if debug
	}

	/**
	* Grava no log do Woo, wp-content/uploads/wc-logs
	* @since	"1.0.0"
	* @param	string	$log - string a ser gravada no arquivo de log
	*
	* https://woocommerce.wordpress.com/2017/01/26/improved-logging-in-woocommerce-2-7/
	*
	*$logger = new \Monolog\Logger('clearsale');
	*$logger->pushHandler(new \Monolog\Handler\StreamHandler(WP_CONTENT_DIR . '/uploads/wc-logs/clearsale.log', \Monolog\Logger::DEBUG));
	*$logger->info('Log ClearSale');
	*/

	public function write_log($log)
	{
		$logger = wc_get_logger(); //com data e timestamp
		$context = array( 'source' => 'clearsale-total' );
		if (is_array($log) || is_object($log)) {
			$logger->debug(print_r($log, true), $context);
		} else {
			$logger->debug($log, $context);
		}
		//$logger->debug( 'Detailed debug information', $context );
		//$logger->info
	}

} // end of class

