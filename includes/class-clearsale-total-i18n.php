<?php
/**
 * Define the internationalization functionality
 *
*/
/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      "1.0.0"
 * @package    Clearsale_Total
 * @subpackage Clearsale_Total/includes
 * @author     Letti Tecnologia <contato@letti.com.br>
 * @link       https://letti.com.br/wordpress
 */
class Clearsale_Total_i18n
{
	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain()
	{

		load_plugin_textdomain(
			'clearsale-total',	// nome do plugin
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}

}
