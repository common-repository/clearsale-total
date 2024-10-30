<?php

/**
 * Register all actions for the footer pages
 *
 */

/**
 * Register all actions for the footer pages, like fingerprint and mapper
 * Colocar a rotina javascript de fingerprint e mapper em todas as páginas, no footer.
 *
 * @link       https://letti.com.br/wordpress
 * @since      "1.0.0"
 * @package    Clearsale_Total
 * @subpackage Clearsale_Total/includes
 * @author     Letti Tecnologia <contato@letti.com.br>
 */
class Clearsale_Total_Footer
{

   	/**
	 * The ID of this plugin.
	 *
	 * @since    "1.0.0"
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    "1.0.0"
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;
	
	/**
	 * @var	class	classe para gravação no debug.log
	*/
	private $wl;

	/**
	 * @var	string	string com o código fingerprint
	*/
	private $fingerprint;
	
	/**
	 * @access	private
	 * @var string $key da sessão do carrinho do woocommerce, caso não exista ainda um timestamp.
	 * apos login no painel de cliente já existe a session do woocomerce.
	*/
	private $session_id;

	/**
	* Pegamos o fingerprint da tabela para colocar dentro do javascript
	*
	* @since    "1.0.0"
	* @param    string    $plugin_name       The name of this plugin.
	* @param    string    $version    The version of this plugin.
	*/
	public function __construct($plugin_name, $version)
	{

		$this->wl = new Clearsale_Total_Log($this->plugin_name, $this->version);
		//$this->wl->write_log("Clearsale_footer: construct");

		$this->plugin_name = $plugin_name;
		$this->version = $version;
        $this->wp_cs_options = get_option($this->plugin_name);

		$this->fingerprint = $this->wp_cs_options['finger'];
		if ( ! $this->session_id ) $this->session_id = time(); // timestamp

        //$this->fingerprint = $wpdb->get_var("SELECT option_value FROM woo_options WHERE option_name = '$plugin_name' ");
	} // end of construct

    /**
    * Pega o fingerprint da variavel private para por no javascript
    *
    * @since	1.0.0
    */
    function cs_total_Add_Js()
	{
		global $woocommerce;

		$tmp = "";
		$tmp1 = (string)time();
		if (isset($woocommerce)) {
			$woo_sess = $woocommerce->session;
			$tmp = $woo_sess->cart;
			//$this->wl->write_log("Clearsale_footer:cs_total_Add_Js: sess_cart=" . print_r($tmp,true));
		}
		if ($tmp) { 
			$tmp1 = array_pop($tmp);
			if ($tmp1) {
				$tmp1 = $tmp1['key'];
			}
		}
		$tmp2 = (string)time();
		//$this->wl->write_log("Clearsale_footer:cs_total_Add_Js: antes timestamp=" . $tmp2);
		if (substr($tmp2,-2) >90) {
			sleep(10);
			$tmp2 = (string)time();
			//$this->wl->write_log("Clearsale_footer:cs_total_Add_Js: timestamp=" . $tmp2);
		}
		$t_tail = substr($tmp2, 0, 8);
		//if ($tmp1) $this->session_id = (string)$tmp1 . "_" . (string)time();
		$this->session_id = (string)$tmp1 . "_" . $t_tail;
		//$tmp2 = $woocommerce->session->get('cs_session_id');
		$this->wl->write_log("Clearsale_footer:cs_total_Add_Js: session_id=" . $this->session_id);
		$woocommerce->session->set('cs_session_id', $this->session_id);
		
		// fingerprint
		?>
		<script type='text/javascript'>
		var cs_sessionid = '<?php echo esc_js($this->session_id); ?>';
		if (typeof tonocheckout != 'undefined') {
			if (tonocheckout) {
        	  (function (a, b, c, d, e, f, g) {
            	a['CsdpObject'] = e; a[e] = a[e] || function () { (a[e].q = a[e].q || []).push(arguments)
            	}, a[e].l = 1 * new Date(); f = b.createElement(c),
            	g = b.getElementsByTagName(c)[0]; f.async = 1; f.src = d; g.parentNode.insertBefore(f, g)
            	})(window, document, 'script', '//device.clearsale.com.br/p/fp.js', 'csdp');
				csdp('app', '<?php echo esc_js($this->fingerprint); ?>'); csdp('sessionid', '<?php echo esc_js($this->session_id); ?>');
			}
		}
        </script>
		<?php
    } // end of cs_total_Add_Js

	public function GetSession()
	{
		return $this->session_id;
	}

} // end of class
