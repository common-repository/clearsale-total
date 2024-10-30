<?php
/**
 * The public class account
*/
/**
 * Classe para tratar do "account integration"
 *
 * Define método para pegar dados de um CUSTOMER inserido no woocommerce.
 * Só que no woo não temos um cadastro completo de cliente, não estamos
 * usando o "WooCommerce Extra Checkout Fields for Brazil" pois ele conflita com o
 * plugin oficial do PagSeguro.
 *
 * https://api.clearsale.com.br/docs/account-integration
 * 
 * @package    Clearsale_Total
 * @subpackage Clearsale_Total/public
 * @author     Letti Tecnologia <contato@letti.com.br>
 * @since      "1.0.0"
 * @date       20/10/2018
 */
class Clearsale_Total_Account {

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
	 * Pointer of log class
	 * @access	private
	 * @var		class
	 */
    private $wl;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since	"1.0.0"
	 * @param	string		$plugin_name       The name of the plugin.
	 * @param	string		$version    The version of this plugin.
	 */
	public function __construct( $plugin_name="", $version="" ) {

		if ($plugin_name) $this->plugin_name = $plugin_name;
        if ($version) $this->version = $version;

		$this->wl = new Clearsale_Total_Log($this->plugin_name, $this->version);

    }

    /**
	 * Classe que trata o hooke de alteração de customer.
	 *
     * add_action ('woocommerce_update_customer', 'Cs_total_account', 10, 1 );
     * 
	 * @since	"1.0.0"
	 * @param	string      $customer   ID do cliente
	 */
    public function Cs_total_account( $customer ) {

        global $woocommerce;
        $woo_sess =$woocommerce->session;
        //Neste ponto o cliente já se logou e temos o $key da sessão do carrinho do woocommerce,
        //$wl->write_log("Cs_total_checkout: session =" . print_r($woo_sess,true));
        //objetos da sessão como $woo_sess->_cookie e $woo_sess->_customer_id são objetos protegidos
        $sess_id = time(); //nunca deveria ser este
        if (isset($woo_sess)) {
            $tmp=$woo_sess->cart; $tmp1 = "";
            if (is_array($tmp)) {
                $tmp1=array_pop($tmp);
                if ($tmp1) $tmp1 = $tmp1['key'];
                if ($tmp1) $sess_id = $tmp1;
            }
        }

        $this->wl->write_log('Clearsale_Total_Account:Cs_total_account: entrou, customer id=' . $customer . ' sess_id=' . $sess_id);

        $email = get_user_meta( $customer, 'billing_email', true );

        // se usando woocommerce-extra-checkout-fields-for-brazil
        $fname = get_user_meta( $customer, 'first_name', true );
        $lname = get_user_meta( $customer, 'last_name', true );
        $cpf   = get_user_meta( $customer, 'billing_cpf', true );
        $cnpj  = get_user_meta( $customer, 'billing_cnpj', true );

        // se for cadastro normal é o nosso plugin
        //$cs_doc = $order->get_meta('cs_doc'); // # documento digitado no checkout, CPF ou CNPJ
        // mas está atrelado ao postmeta, aos pedidos.
        $cs_doc     = get_user_meta( $customer, 'cs_doc', true ); // # documento, não existe no usermeta !!???
        $cs_fname   = get_user_meta( $customer, 'billing_first_name', true );
        $cs_lname   = get_user_meta( $customer, 'billing_last_name', true );
        $cs_company = get_user_meta( $customer, 'billing_company', true );

        // # documento
        $num_doc = "";
        if (strlen($cpf) < 2 && strlen($cnpj) < 2) { // usando nosso plugin
            $num_doc = $cs_doc;
        } else { // usando extra checkout
            $num_doc = $cnpj;
            if (strlen($cpf) > 2) $num_doc = $cpf;
        }

        // Nomes
        $nome = $fname . ' ' . $lname;
        if (strlen($nome) < 2 ) $nome = $cs_fname . ' ' . $cs_lname;

        $cus_array = array();
        $cus_array['id']        = $customer;
        $cus_array['date']      = date('c'); // data agora
        $cus_array['sessionID'] = (string)$sess_id;
        $cus_array['name']      = $nome;
        $cus_array['num_doc']   = (string)$num_doc; // nao vai ter !!!
        $cus_array['email']     = $email;
//$this->wl->write_log('Clearsale_Total_Account:Cs_total_account: array=' . print_r($cus_array,true));
        $cs_api = new Clearsale_Total_Api($this->plugin_name, $this->version);
        $json_cust = $cs_api->Monta_customer( $cus_array ); // retorna um json

        $ret = $cs_api->Accounts( $json_cust );

        $this->wl->write_log("Clearsale_Total_Account:Cs_total_account: saindo, retorno=" . print_r($ret,true));

    } // end of Cs_total_account

    /**
	 * Método que trata o hooke de login de um cliente.
	 *
     * add_action( 'auth_redirect', 'Cs_total_auth_login', 10, 1 ); 
     * https://developer.wordpress.org/reference/hooks/auth_redirect/
     * 
     * Só se for no wp-login, no woo não funciona
     * 
	 * @since	"1.0.0"
	 * @param	string      $customer   ID do cliente
	 */
    public function Cs_total_auth_login( $customer ) {

        global $woocommerce;
        $woo_sess =$woocommerce->session;
        //Neste ponto o cliente já se logou e temos o $key da sessão do carrinho do woocommerce,
        //$wl->write_log("Cs_total_checkout: session =" . print_r($woo_sess,true));
        //objetos da sessão como $woo_sess->_cookie e $woo_sess->_customer_id são objetos protegidos
        $sess_id = time(); //nunca deveria ser este
        if (isset($woo_sess)) {
            $tmp=$woo_sess->cart; $tmp1 = "";
            if (is_array($tmp)) {
                $tmp1=array_pop($tmp);
                if ($tmp1) $tmp1 = $tmp1['key'];
                if ($tmp1) $sess_id = $tmp1;
            }
        }

        $this->wl->write_log('Clearsale_Total_Account: Cs_total_auth_login: entrou, customer id=' . $customer . ' sess_id=' . $sess_id);

    } // end of Cs_total_auth_login


    /**
	 * Método que pega o json pronto de pedido para gravar na ClearSale e extrai dados de Account e
     * grava na API do Accounts.
	 *
	 * @since	"1.0.0"
	 * @param	string      $json_in    Json completo de um pedido
     * @param   string      $sess_id    Session ID
	 */
    public function Account_get_into( $json_in, $sess_id ) {

        $arrt = json_decode($json_in, true);
        $bil = $arrt[0]['billing'];

        $doc        = $bil['primaryDocument']; //CPF ou CNPJ pedido no checkout
        $nome       = $bil['name'];
        $email      = $bil['email'];
        $type       = $bil['type']; //1 = PF  2 = PJ
        $cus_id     = $bil['clientID'];

        $cus_array = array();
        $cus_array['id']        = $cus_id;
        $cus_array['date']      = date('c'); // data agora
        $cus_array['sessionID'] = $sess_id;
        $cus_array['name']      = $nome;
        $cus_array['num_doc']   = (string)$doc;
        $cus_array['email']     = $email;
    
        //$this->wl->write_log('Clearsale_Total_Account:Account_get_into: array=' . print_r($cus_array,true));

        $cs_api = new Clearsale_Total_Api($this->plugin_name, $this->version);
        $json_cust = $cs_api->Monta_customer( $cus_array ); // retorna um json

        $ret = $cs_api->Accounts( $json_cust );
/*  Array
        (
            [requestID] => 12I3-14D3-43B7-28I8
            [code] => 2
        )
*/
        if ( isset($ret['requestID']) ) {
            $requestID   = $ret['requestID'];
            $id_customer = $ret['code'];
            $this->wl->write_log("Clearsale_Total_Account:Account_get_into: saindo... requestID=" . $requestID . " CustomerId=" . $id_customer);
            return(1);
        } else $this->wl->write_log("Clearsale_Total_Account:Account_get_into: saindo, retorno=" . print_r($ret,true));
        return(0);
    } // end of Account_get_into

} // end of class

