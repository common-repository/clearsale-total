<?php

/**
 * Register all actions for the Api methods for ClearSale Api
 */

/**
 * Register all actions for the Api page, to comunicate with ClearSale Api.
 * Monta json com o pedido para inserir na ClearSale.
 * Produto: T, TG & A : usado manual Total, Total Garantido e Application - Versão 1.6 30/8/18
 * 
 *
 *
 * @package    Clearsale_Total
 * @subpackage Clearsale_Total/includes
 * @author     Letti Tecnologia <contato@letti.com.br>
 * @link       https://letti.com.br/wordpress
 * @since      "1.0.0"
 * @date       20/07/2018
 */
class Clearsale_Total_Api
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
	 * The mode of work with ClearSale.
	 *
	 * @since    "1.0.0"
	 * @access   private
	 * @var      string    $modo    The mode save in admin. 0=produção  1=teste - homologação
	 */
    private $modo;

	/**
	 * The login for work with ClearSale.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $login    The login name.
	 */
    private $login;

	/**
	 * The password for work with ClearSale.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $password    The password for login name.
	 */
    private $password;

	/**
	* Token com a ClearSale
	*
	* @access	private
	* @var		string	$token
	*/
	private $token;

	/**
	* Expiration Date, a data e hora que o token vai expirar.
	* ISO-8601
	* Padrão: YYYY-MM-DDThh:mm:ss.sTZD     Exemplo: 2013-06-28T08:54:00.000-03:00
	*
	* @access	private
	* @var		string	$expirationDate;
	*/
	private $expirationDate;

	/**
	 * Endpoint de homologação, url a ser chamada para a homologação
	 * @access	private
	 * @var		string
	*/
	private $_endp_h = "https://homologacao.clearsale.com.br/api/";

	/**
	 * Endpoint de produção, url a ser chamada para a por pedidos já em produção
	 * @access	private
	 * @var		string
	*/
	private $_endp_p = "https://api.clearsale.com.br/";

	/**
	 * @access	private
	 * @var	class	Write log class
	 */
	private $wl;

	/**
	 * @access	private
	 * @var	array	dados do banco
	 */
	private $wp_cs_options;

    /**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name       The name of this plugin.
	 * @param    string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

        //global $wpdb;

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->wl = new Clearsale_Total_Log($this->plugin_name, $this->version);

		$this->wp_cs_options = get_option($this->plugin_name); //get mysql data
		
		//$this->wl->write_log("Clearsale_Total_Api: construct: entrou, login=" . $this->wp_cs_options['login'] . " modo=" . $this->wp_cs_options['modo']);

		$this->modo = $this->wp_cs_options['modo'];
		$this->login = $this->wp_cs_options['login'];
		$this->password = $this->wp_cs_options['password'];

	} // end of construct

	/**
	* Faz autenticacao se o expirationDate venceu, senao retorna o token.
	*
	* @return	string	token se exito ou false se erro no wp_remote_post
	*/
	private function Authenticate()
	{

		$this->wl->write_log("Authenticate: versão:" . $this->version . " login=" . $this->login . " modo=" . $this->modo);

		$dt_agora = date("c");
//		$this->wl->write_log("Authenticate: agora=" . $dt_agora . "  expirat=" . $this->expirationDate);
		if ($dt_agora < $this->expirationDate) {
			$this->wl->write_log("Authenticate: ja salvo token=" . $this->token);
			return $this->token; // já autenticado e não expirou
		}

		$servico = "/v1/authenticate";
		if ($this->modo) $tmp = $this->_endp_h;
		else $tmp = $this->_endp_p;

		$endpoint = $tmp . $servico; // endpoint final para authentication
	
		//https://api.clearsale.com.br/docs/how-to-start
		//https://developer.wordpress.org/reference/functions/wp_remote_post/

		$headers = array('Content-Type' => 'application/json');
		$opt = "{ \"name\": \"" . $this->login . "\", \"password\":\"" . $this->password . "\" }";
		$ret = wp_remote_post($endpoint, array(
			'method'      => 'POST',
			'timeout'     => 120,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => $headers,
			'body'        => $opt,
			'cookies'     => array()
			)
		);
		if (is_wp_error($ret)) {
			$error_message = $ret->get_error_message();
			$this->wl->write_log("Authenticate: erro no wp_remote_post=" . $error_message);
			return false;
		}
		// em body:
		// "{"Token":"d22d1dcc8035412daa3cce339c964c6d","ExpirationDate":"2018-09-02T18:43:41.6956152-03:00"}"

		$json_str = json_decode(wp_remote_retrieve_body($ret), true);
		//$this->wl->write_log("Authenticate: retorno json=" . print_r($json_str,true));
		$this->token = $json_str['Token'];
		$this->expirationDate = $json_str['ExpirationDate'];
		$this->wl->write_log("Authenticate: novo token=" . $this->token . " ExpirationDate:" . $this->expirationDate);
		return $this->token;
	} // end of Authenticate

	/**
	* Metodo para consultar status do pedido na ClearSale.
	* 
	* @param	string	$pedido - Número do pedido
	* @return	mixed false|array - False se der erro no curl, array com retorno array('requestID'=>$requestID, 'code'=>$code, 'status'=>$status, 'score'=>$score)
	*/
	public function Consulta_status($pedido)
	{

		if (strlen($pedido) <= 0) return false;

		$tentaC = 0;
		tenta:
		$servico = "/v1/orders/" . $pedido . "/status"; //{ENDPOINT}/v1/orders/{CODIGO_DO_MEU_PEDIDO}/status
		if ($this->modo) $tmp = $this->_endp_h;	//homologação
		else $tmp = $this->_endp_p;	//produção

		$endpoint = $tmp . $servico; // endpoint final para inclusão de pedidos
		$token = $this->Authenticate();	// retorna token
		$headers = array('Accept' => ' application/json', 'Authorization' => ' Bearer ' . $token);
		$ret = wp_remote_get($endpoint, array('headers' => $headers));
		if (is_wp_error($ret)) {
			$error_message = $ret->get_error_message();
			//erro no wp_remote_get=cURL error 28: Operation timed out after 5003 milliseconds with 0 out of -1 bytes received
			$this->wl->write_log("Clearsale_Total_Api: Consulta_status: erro no wp_remote_get=" . $error_message);
			$tentaC++;
			$this->wl->write_log("Clearsale_Total_Api: Consulta_status: tentativa " . $tentaC);
			sleep(3);
			if ($tentaC < 5) goto tenta;
			return false;
		}
		if (is_array($ret)) {
			$header = $ret['headers']; // array of http header lines
			$body   = $ret['body'];	// use the content
		}
		$http_code = wp_remote_retrieve_response_code($ret);

		$requestID = $header['request-id'];

		if ($http_code != 200) {
			$this->wl->write_log("Clearsale_Total_Api: Consulta_status: erro #" . $http_code . " msg=" . print_r($body,true));
			$tentaC++;
			$this->wl->write_log("Clearsale_Total_Api: Consulta_status: tentativa " . $tentaC);
			sleep(3);
			if ($tentaC < 5) goto tenta;
			return false;
		}

		//curl_close ($ch);
		$json_str = json_decode($body, true);

		$code = $json_str['code'];		// # pedido
		$status = $json_str['status'];	// status, NVO
		$score = $json_str['score'];

		if (! $status) return false;
		$this->wl->write_log("Clearsale_Total_Api: Consulta_status: #pedido=" . $code . " status=" . $status . " score=" . $score);
		return array('requestID'=>$requestID, 'code'=>$code, 'status'=>$status, 'score'=>$score);

	} // end of Consulta_status

	/**
	* Metodo para alterar status na ClearSale
	* 
	* @param	string	$pedido - # pedido
	* @param	string	$cs_status - 8.0 pag 21 manual - PGA = Pgto Aprovado    PGR = Pgto Reprovado
	* @return	mixed false|array - False se der erro no curl, array com retorno array('requestID'=>$requestID, 'status'=>$status, 'message'=>$message);
	*/
	public function Atualiza_status($pedido, $cs_status)
	{

		$servico = "/v1/orders/" . $pedido . "/status";
		if ($this->modo) $tmp = $this->_endp_h;	//homologação
		else $tmp = $this->_endp_p;	//produção

		$opt = '{ "status" : "' . $cs_status . '" }';
		//$opt = array('status' => $cs_status); // erro 400

		$this->wl->write_log("Atualiza_status: vamos marcar pedido=" . $pedido . " com status=" . $cs_status);

		$endpoint = $tmp . $servico; // endpoint final para status
		$token = $this->Authenticate();	// retorna token
		$headers = array('Accept' => ' application/json', 'Authorization' => ' Bearer ' . $token);
		$ret = wp_remote_request($endpoint, array(
			'method'      => 'PUT',
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => $headers,
			'body'        => $opt,
			'cookies'     => array()
			)
		);
		if (is_wp_error($ret)) {
			$error_message = $ret->get_error_message();
			$this->wl->write_log("Atualiza_status: erro no wp_remote_request=" . $error_message);
			return false;
		}

		if (is_array($ret)) {
			$header = $ret['headers']; // array of http header lines
			$body   = $ret['body'];	// use the content
		}
		$http_code = wp_remote_retrieve_response_code($ret);

		if ($http_code != 200) {
			$this->wl->write_log("Atualiza_status: erro #" . $http_code . " msg=" . print_r($header,true));
			return false;
		}

		//Request-ID: 12J6-11B3-11A7-93C0 depois ficou assim: Request-Id: 70B4-14P1-82V0-48Y4
		$requestID = $header['request-id'];

/* retorno do curl
[
	{
		"status": “OK”,
		"message":"Status was changed with sucess"
	}
]
(
    [status] => StatusNotAllowed
    [message] => It is not permitted to update to this status
)
*/

		$json_str = json_decode($body, true);
		$this->wl->write_log("Atualiza_status: ret=" . print_r($json_str,true));
		$message = $json_str['message'];// Status was changed with sucess
		$status = $json_str['status'];	// OK

		$this->wl->write_log("Atualiza_status: #pedido=" . $pedido . " status=" . $status . " msg=" . $message);
		return array('requestID'=>$requestID, 'status'=>$status, 'message'=>$message);

	} // end of Atualiza_status

	/**
	* Metodo para incluir customer na ClearSale - Accounts Integration
	* 
	* @param	string	$customer - json do customer, de acordo com: https://api.clearsale.com.br/docs/account-integration
	* @return	mixed false|array - False se der erro no curl, array com retorno array('requestID'=>$requestID, 'code'=>$code);
	*/
	public function Accounts($customer)
	{

		$servico = "v1/accounts";
		if ($this->modo) $tmp = $this->_endp_h;	//homologação
		else $tmp = $this->_endp_p;	//produção

		$endpoint = $tmp . $servico; // endpoint final para accounts
		$token = $this->Authenticate();	// retorna token
		$headers = array('Accept' => ' application/json', 'Authorization' => ' Bearer ' . $token);

		$ret = wp_remote_post($endpoint, array(
			'method'      => 'POST',
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => $headers,
			'body'        => $customer,
			'cookies'     => array()
			)
		);
		if (is_wp_error($ret)) {
			$error_message = $ret->get_error_message();
			$this->wl->write_log("Accounts: erro no wp_remote_post=" . $error_message);
			return false;
		}
		if (is_array($ret)) {
			$header = $ret['headers']; // array of http header lines
			$body   = $ret['body'];	// use the content
		}
		$http_code = wp_remote_retrieve_response_code($ret);

		if ($http_code != 200) {
			//erro #400 msg={"Message":"The request is invalid.","ModelState":{"existing-account":["2"]}}
			$this->wl->write_log("Accounts: erro #" . $http_code . " msg=" . print_r($body,true));
			return false;
		}
/* retorno do curl
{
    "requestID": "70O5-18S0-93V0-75B5",
    "account": 
    {
        "code": "codigo_conta"
    }
}
*/
		$json_str = json_decode($body, true);

		$requestID = $json_str['requestID'];
		$ret = $json_str['account'];
		$code = $ret['code'];		// # pedido

		//$this->wl->write_log("Accounts: #conta=" . $code . " requestID=" . $requestID);
		return array('requestID'=>$requestID, 'code'=>$code);

	} // end of Accounts

	/**
	 * Metodo para montar o json de "customer", dado array de entrada com dados do cliente.
	 * @param	array	$customer
	 * @return	Json	customer
	 */
	/*
	    $cus_array['id']        = $customer;
        $cus_array['date']      = date('c'); // data agora
        $cus_array['sessionID'] = $sess_id; //???????????
        $cus_array['name']      = $fname . ' ' . $lname;
        $cus_array['num_doc']   = cpf (11 bytes) ou cnpj (14 bytes)
        $cus_array['cnpj']      = $cnpj;
		$cus_array['email']     = $email;
	*/
	public function Monta_customer($customer)
	{

		$header = array();
		$header['code']				= (string)$customer['id'];
		$header['date']				= $customer['date'];
		$header['sessionID']		= $customer['sessionID'];
		$doc = preg_replace("/[^0-9]/", "", $customer['num_doc']);
        //$cpf = str_pad($cpf, 11, '0', STR_PAD_LEFT);

		if (strlen($doc) > 11) { // CNPJ
			$header['companyName'] = $customer['name'];
			$header['companyDocument'] = $customer['num_doc'];
		} else { // CPF
			$header['name'] = $customer['name'];
			$header['personDocument'] = $customer['num_doc'];
		}
		$header['email']			= $customer['email'];

		$this->wl->write_log("Monta_customer: json de saida=" . print_r(json_encode($header), true));

		return( json_encode($header) );
	} // end of Monta_customer

	/**
	* Metodo para incluir pedido na ClearSale
	* 
	* @param	string	$pedido_json - Todos os dados do pedido em Json
	* @return	mixed false|array - False se der erro no curl, array com retorno array('requestID'=>$requestID, 'code'=>$code, 'status'=>$status, 'score'=>$score)
	*/
	public function Inclui_pedido($pedido_json)
	{

		$servico = "/v1/orders";
		if ($this->modo) $tmp = $this->_endp_h;	//homologação
		else $tmp = $this->_endp_p;	//produção

		$endpoint = $tmp . $servico; // endpoint final para inclusão de pedidos
		$token = $this->Authenticate();	// retorna token
		$headers = array('Accept' => ' application/json', 'Authorization' => ' Bearer ' . $token);
		$retry = 0;

		tenta:

		$ret = wp_remote_post($endpoint, array(
			'method'      => 'POST',
			'timeout'     => 120,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => $headers,
			'body'        => $pedido_json,
			'cookies'     => array()
			)
		);
		if (is_wp_error($ret)) {
			$error_message = $ret->get_error_message();
			$this->wl->write_log("Inclui_pedido: erro no wp_remote_post=" . $error_message);
			return false;
		}
		if (is_array($ret)) {
			$header = $ret['headers']; // array of http header lines
			$body   = $ret['body'];	// use the content
		}
		$http_code = wp_remote_retrieve_response_code($ret);
		/* header
		(
            [date] => Wed, 12 Apr 2023 21:15:40 GMT
            [content-type] => application/json; charset=utf-8
            [content-length] => 219
            [content-encoding] => gzip
            [vary] => Accept-Encoding
            [x-stackifyid] => V2|b9b23188-92af-4abf-96dd-ea0bb3d50d08|C64106|CD93574        
            [request-id] => 41J2-18D1-53F8-57O0
            [x-powered-by] => ASP.NET
            [x-azure-ref] => 20230412T211538Z-zbu1upa7x542f3td6h4xy6r74n00000000e000000000fq30
            [x-cache] => CONFIG_NOCACHE
            [accept-ranges] => bytes
        )
		*/
		$requestID = $header['request-id'];

		if ($http_code != 200) {
			$this->wl->write_log("Inclui_pedido: erro #" . $http_code . " msg=" . print_r($body,true));
			if ($http_code == 500) {
				if ($retry > 4) {
					$this->wl->write_log("Inclui_pedido: erro #" . $http_code . " retry=" . $retry . " saindo...");
					return false;
				}
				$retry++;
				$this->wl->write_log("Inclui_pedido: erro #" . $http_code . " retry=" . $retry . " nova tentativa em 10 segundos...");
				sleep(10);
				goto tenta;
			}
			return false;
		}
/* retorno do curl
{
    "Message":"The request is invalid.",
    "ModelState":{
        "[0].shipping.phones[0].ddd":["Invalid type. Expected Integer but got String."],
        "[0].shipping.phones[0].number":["Invalid type. Expected Integer but got String."],
		"[0].connections[0].date":["Invalid type. Expected String but got Null."]}
}
*/
		$json_str = json_decode($body, true);

		// Undefined index: packageID qdo da erro acima nao tem estes
		$packageID = $json_str['packageID'];
		$orders = $json_str['orders'];
		$code = $orders[0]['code'];		// # pedido
		$status = $orders[0]['status'];	// status, NVO
		$score = $orders[0]['score'];

		$this->wl->write_log("Inclui_pedido: #pedido=" . $code . " status=" . $status . " score=" . $score);
		return array('requestID'=>$requestID, 'code'=>$code, 'status'=>$status, 'score'=>$score);

	} // end of Inclui_pedido


	/**
	 * Classe para montar o json a partir dos arrays de entrada que são dados do pedido, correio, itens do pedido, dados
	 * do cartão de crédito e a sessão.
	 * 
	 * @param	string	$sess_id - Session ID
	 * @param	string	$cs_doc - # documento pedido no checkout - CPF ou CNPJ
	 * @param	string	$email - Customer e-mail
	 * @param	object	$order - Objeto order
	 * @param	array	$order_data - Todos os dados do pedido do woocommerce
	 * @param	array	$courrier - Método de entrega e valor, array de uma posição
	 * @param	array	$items - coleção de itens do pedido
	 * @param	array	$cartao - dados de cartão de crédito, podemos não ter, opcional !
	 * @return	Json	pedido montado em JSON
	*/
	public function Monta_pedido($sess_id, $cs_doc, $email, $order, $order_data, $courrier, $items, $cartao="")
	{

		$this->wl->write_log("Monta_pedido: entrou, #doc=" . $cs_doc . " e-mail=" . $email);
//$this->wl->write_log("Monta_pedido: order_data=" . print_r($order_data,true));

		if ($cartao == "" || count($cartao) < 1) { // qdo vindo do checkout nao temos # cartao, apenas vindo do ajax
			$cartao = array(
				'modo'			=>	'',
				'numero'		=>	'',
				'bin'			=>	'',
				'end'			=>	'',
				'validity'		=>	'',
				'installments'	=>	'',
				'owner'			=>	'',
				'document'		=>	''
			);
		}

		// pegar o total dos itens
		$itemValue = 0;
		foreach( $items as $item_id => $item_product ) {
			$item_data = $item_product->get_data();
			$line_total = $item_data['total'];
			$itemValue += $line_total;
		}
		$tmp = $order_data['date_created']->date('c');	// pega data e hora correta, mas GMT
		$data_pedido = substr($tmp,0,19) . "-03:00";	// corrigindo o fuso
		// Manual_Integracao_API_Clearsale_T_TG_A_v1.6.pdf pag 33

			$header = array();
			$header['code']				= (string)$order_data['id'];
			$header['sessionID']		= $sess_id;
			$header['date']				= $data_pedido;
			$header['email']			= $email;
			//$header['b2bB2c']			= "B2C";
			$header['itemValue']		= (float)$itemValue;	// nao obrigatorio pag 38 do manual
			$header['totalValue']		= (float)$order_data['total'];
			$header['numberOfInstallments'] = ($cartao['installments']?$cartao['installments']:1); // idem acima
			$header['ip']				= $order_data['customer_ip_address'];
			$header['isGift']			= false;	// idem acima
			$header['giftMessage']		= "";
			$header['observation']		= "";
			$header['status'] 			= 0;	// pag 21 do manual, pedido novo
			$header['origin']			= "website";	// nao obrigatorio pag 38 do manual
			$header['channelID']		= "";	// idem acima
// Tirado a pedido do joelson em 3/12/18
//			$header['reservationDate']	= date('c'); // data agora
			$header['country']			= "";
			$header['nationality']		= "";
			$header['product']			= 3;	//pag 24 produto CS 1=aplicacao 3=total 4=total garantido 10=realtime
			$header['customSla']		= 0;
			$header['bankAuthentication']	= "";
			//$header['list']				= array('typeID' => 1, 'id' => '123'); // Pag 23
/* tirado a pedido do Joelson em 7/6/19
			$header['purchaseInformation']	= array(
				'lastDateInsertedMail' 		=> $data_pedido,	//date('c'),
				'lastDateChangePassword'	=> $data_pedido,	//date('c'),
				'lastDateChangePhone'		=> $data_pedido,	//date('c'),
				'lastDateChangeMobilePhone'	=> $data_pedido,	//date('c'),
				'lastDateInsertedAddress'	=> $data_pedido,	//date('c'),
				'purchaseLogged'			=> false,
				'email'						=> '',
				'login'						=> ''
				);
*/
			//$header['socialNetwork']	= array(	// Pag 23
			//	'optInCompreConfie'		=> 0,
			//	'typeSocialNetwork'		=> 0,
			//	'authenticationToken'	=> ''
			//	);

			// BILLING
			$end1 = $order_data['billing']['address_1'];
			$end2 = $order_data['billing']['address_2'];
			$bill_number = $order->get_meta('_billing_number');
			$endereco = substr($end1 . " " . $end2, 0, 200);
			$numero = preg_replace("/[^0-9]/", "", $end1);
			if (! $numero) $numero = $bill_number;
			if (! $numero) $numero = "0";
			if ($order->get_meta('_billing_birthdate')) {
				$dob = explode('/', $order->get_meta('_billing_birthdate')); // dd/mm/aaaa
				if ($dob[2] && $dob[1] && strlen($dob[2] == 4)) {
					$birthdate = $dob[2] . "-" . $dob[1] . "-" . $dob[0] . "T00:00:00.000";
				} else $birthdate = null;
			} else $birthdate = null;
			$cep = preg_replace("/[^0-9]/", "", $order_data['billing']['postcode']);
			$tmp = $order_data['billing']['phone'];
			$telefone = (int) $this->dddTel($tmp);
			$ddd = (int) $this->dddTel($tmp, "ddd");
			if (strlen($this->deixarSomenteDigitos($cs_doc)) <= 11) $pf_pj = 1; // PF
			else $pf_pj = 2; // PJ
			$county = $order->get_meta('_billing_neighborhood'); // se tem plugin woo extra fields
			if (! $county) {
				$county = $this->Busca_Bairro_ws($cep);
			}
			$header['billing']	= array(
				'clientID'			=> (string)$order_data['customer_id'],
				'type'				=> $pf_pj,	// 1 = PF  2 = PJ   - campo obrigatorio
				'primaryDocument'	=> $cs_doc,	//CPF ou CNPJ pedido no checkout
				'secondaryDocument'	=> '',
				'name'				=> $order_data['billing']['first_name'] . " " . $order_data['billing']['last_name'],
				'birthDate'			=> $birthdate, //"birthDate" : "1990-01-10T00:00:00.000",
				'email'				=> $order_data['billing']['email'],
				'gender'			=> '',
				'address'			=> array(
									'street' => $endereco,
									'number' => $numero,
									'additionalInformation' => '',
									'county' => $county, // campo obrigatorio - bairro
									'city'	=> $order_data['billing']['city'],
									'state'	=> $order_data['billing']['state'],
									'country' => $order_data['billing']['country'],
									'zipcode' => $cep,
									'reference' => ''
									),
				'phones'			=> array(
									array(
										'type'	=> 0,	// 0=nao definido 1=residencial 2=cml  pag 17 manual
										'ddi'	=> 55,
										'ddd'	=> $ddd,
										'number'	=> $telefone,
										'extension'	=> ''
									))
				);

			// SHIPPING
			$end1 = $order_data['shipping']['address_1'];
			$end2 = $order_data['shipping']['address_2'];
			$ship_number = $order->get_meta('_shipping_number');
			$endereco = substr($end1 . " " . $end2, 0, 200);
			$numero = preg_replace("/[^0-9]/", "", $end1);
			if (! $numero) $numero = $ship_number;
			if (! $numero) $numero = "0";
			$cep = preg_replace("/[^0-9]/", "", $order_data['shipping']['postcode']);
			list($metodo, $valor) = explode("=", $courrier[0], 2);
			    //correios-pac=19.77
    			//correios-sedex=21.47
				//local_pickup=0.00
			$city  = $order_data['shipping']['city'];
			$state = $order_data['shipping']['state'];
			$country = $order_data['shipping']['country'];

			$tipo_entrega = $this->Tipo_entrega($metodo);
			if ( $tipo_entrega == 16 ) { // se for retira na loja temos que pegar os dados do store (endereço da loja no Woo)
				//https://wordpress.stackexchange.com/questions/319346/woocommerce-get-physical-store-address
				$store_address     = get_option('woocommerce_store_address');
				$store_address2    = get_option('woocommerce_store_address2');
				$city   = get_option('woocommerce_store_city');
				$cep    = get_option('woocommerce_store_postcode');
				$enderr = explode( ",", $store_address);
				$numero = $enderr[1];
				if ( ! $numero ) {
					$numero = preg_replace("/[^0-9]/", "", $store_address2);
					if (!$numero || strlen($numero)<=0)
					$numero = "0";
				}
				$endereco = $enderr[0];
				// The country/state
				$store_raw_country = get_option('woocommerce_default_country');
				// Split the country/state
				$split_country = explode(":", $store_raw_country);

				// Country and state separated:
				$country = $split_country[0];
				$state   = $split_country[1];
			}
			$header['shipping']	= array(
				'clientID'			=> (string)$order_data['customer_id'],
				'type'				=> $pf_pj,	// 1 = PF  2 = PJ   - campo obrigatorio
				'primaryDocument'	=> $cs_doc,	// mesmo do billing
				'secondaryDocument'	=> '',
				'name'				=> $order_data['shipping']['first_name'] . " " . $order_data['shipping']['last_name'],
				'birthDate'			=> null,
				'email'				=> '', //$order_data['shipping']['email'],  nao tem em shipping
				'gender'			=> '',
				'address'			=> array(
									'street' => $endereco,
									'number' => $numero,
									'additionalInformation' => '',
									'county' => $this->Busca_Bairro_ws($cep), // campo obrigatorio - bairro
									'city'	=> $city,
									'state'	=> $state,
									'country' => $country,
									'zipcode' => $cep,
									'reference' => ''
									),
/* como nao tem dados o Joelson pediu para tirar - email 3/12/18
				'phones'			=> array(
									array(
										'type'		=> 0,		// 0=nao definido 1=residencial 2=cml  pag 17 manual
										'ddi'		=> 55,
										'ddd'		=> 0,		// nao tem em shipping
										'number'	=> 0,		// idem
										'extension'	=> ''
									)),  */
				'deliveryType'		=> $tipo_entrega,	// 0=outros 1=normal 2=garantida 3=expressaBR  11=correio - pag 22 manual
				'deliveryTime'		=> '',		//"2 dias úteis",
				'price'				=> (float)$valor,
				'pickUpStoreDocument'	=> ''	//"12345678910" cpf para retirada
				);

			// Payments - deve ser array de pagamentos - pag 40
			/*$cartao = array(   array passado para cartao de credito, vindo do ajax
			'modo'			=>	'credito' | 'debito' | 'boleto' | 'pix'
			'numero'		=>	$card,
			'bin'			=>	substr($card, 0, 6),
			'end'			=>	substr($card, -4),
			'validity'		=>	$validade,
			'owner'			=>	$card_holder,
			'document'		=>	$cpf			);
			*/
			$tmp_payments = array();
			$tipo_pgto = $this->Tipo_pagamento($order_data['payment_method']); // pag 18
			if ("checkout-cielo" === $order_data['payment_method']) {
				//modulo cielo. Claudio Sanches 1.0.4, leva ao site da Cielo para pagar com credido/debito/boleto
				//tem um update_post_meta salvando o tipo de pgto usado na tela do Cielo (credito|debito|boleto)
				//https://developercielo.github.io/manual/checkout-cielo
				//1	Cartão de Crédito
				//2	Boleto Bancário
				//3	Débito Online
				//4	Cartão de Débito
				//Ele só pega este postmeta se for no thankyou
				$payment_method_type = 0;	// do doc da Cielo
				$tmp = get_post_meta($order_data['id'], "Payment type", true);
				if (empty($tmp))
					$tmp = $order->get_meta("Payment type");
				if (strlen($tmp) <=0) $tmp = get_post_meta($order_data['id'], "Tipo de pagamento", true);
				if (strlen($tmp) <=0) $tmp = $order->get_meta("Tipo de pagamento");
				if (strlen($tmp)>0) $payment_method_type = $tmp;

				$tipo_pgto = $this->Tipo_pagamento("checkout-cielo-" . (string)$payment_method_type);
			}

			if ("pagseguro" === $order_data['payment_method'] && !$cartao['modo']) {//pagseguro mas nao oficial 26/7/20
				// modulo pagseguro Claudio Sanches - PagSeguro for WooCommerce - 2.14.0

				$ps_pay_data = get_post_meta($order_data['id'], "_wc_pagseguro_payment_data", true);
				if (empty($ps_pay_data))
					$ps_pay_data = $order->get_meta("_wc_pagseguro_payment_data");
				$pg_type = $ps_pay_data['type']; // 1 credito 
				$pg_method = $ps_pay_data['method']; // Cartão de crédito Visa
				$pg_installments = $ps_pay_data['installments'];

				$this->wl->write_log("Monta_pedido: pagseguroClaudioS. type=" . $pg_type . " método=" . $pg_method . " parcelas=" . $pg_installments);
				switch( $pg_type ) {
					case 1: $tipo_pgto = 1; break; // crédito
					case 2: $tipo_pgto = 2; break; // boleto
					case 3: $tipo_pgto = 3; break; // debito
					default: $tipo_pgto = 14; break;
				}
			}
			if ("Pagar.me" === $order_data['payment_method']) {
				$tmp = get_post_meta($order_data['id'], "_pagarme_payment_method", true);
				if (empty($tmp))
					$tmp = $order->get_meta("_pagarme_payment_method");
				/* _pagarme_payment_method = billet
				   _pagarme_payment_method = credit_card
				   _pagarme_payment_method = pix
				*/
				if ("billet" === $tmp) {
					$tipo_pgto = $this->Tipo_pagamento("boleto");
					$cartao['modo'] = "";
				}
				if ("pix" === $tmp) {
					$tipo_pgto = $this->Tipo_pagamento("pix");
					$cartao['modo'] = "";
				}
			}
			if ("paypal-brasil-plus-gateway" === $order_data['payment_method']) {
				$tipo_pgto = $this->Tipo_pagamento("credito");
				//$cartao['modo'] = "";
				// sem postmeta, sem dados via ajax, usa iframe.
			}

			if ($cartao['modo']) $tipo_pgto = $this->Tipo_pagamento($cartao['modo']); // vindo do ajax tem cartao do Pagseguro
			if ($cartao['bin']) $star="******"; else $star="";
			// vamos montar o array com dados do cartao, se for pgto com CC senao array vazio
			if (strlen($cartao['numero']) > 0) {
				$cartao_array = array(
					'number'	=>	$cartao['bin'] . $star . $cartao['end'], //$cartao['numero'], // pag 44
					'hash'		=>	'',
					'bin'		=>	$cartao['bin'],		// obrigatorio
					'end'		=>	$cartao['end'],		// obrigatorio
					'type'		=>	$this->Bandeira_Cartao( $cartao['numero'] ), // Pag 19  102 a vista
					'validityDate' =>	$cartao['validity'],
					'ownerName'	=>	$cartao['owner'],	// obrigatorio
					'document'	=>	$cartao['document'],
					'nsu'		=>	''
				);
			} else { // sem cartao
				$cartao_array = null;
			}
			$tmp_payments[]	= array(
				'sequential'		=>	1,
				'date'				=>	$order_data['date_created']->date('c'),
				'value'				=>	(float)$order_data['total'],
				'type'				=>	$tipo_pgto, // pag 18
				'installments'		=>	($cartao['installments']?$cartao['installments']:1), //1,
				'interestRate'		=>	0,
				'interestValue'		=>	0,
				'currency'			=>	$this->Tipo_moeda($order_data['currency']), // pag 25
				'voucherOrderOrigin'=>	'',
				'card'				=>	$cartao_array,
				'address'			=>	array( // pag 44
									'street'	=>	'',
									'number'	=>	'',
									'additionalInformation'	=>	'',
									'county'	=>	'',
									'city'		=>	'',
									'state'		=>	'',
									'zipcode'	=>	'',
									'country'	=>	'',
									'reference'	=>	''
									)
			);
			$header['payments']	= $tmp_payments;

			// Items - deve ser array de itens - $items = $order->get_items()
			$tmp_array = array();
			foreach( $items as $item_id => $item_product ) {
				//$product = $item_product->get_product();
				// Access Order Items data properties (in an array of values)
				//https://stackoverflow.com/questions/39401393/how-to-get-woocommerce-order-details
				$item_data = $item_product->get_data();
				$product = $item_product->get_product();
				// 03/03/23 papelariarainha tem um produto com variacao que $product ficava var booleana. Produto
				// com variação mas na tela de pedido do woo ele não forma link como os outros produtos!!??
				if (is_bool($product)) continue;
				// Se $product->get_parent_id() for > 0 tem variação, senão é simples.
				// make sure to get parent product if variation
				if ($product->is_type( 'variation' )) {
					$product = wc_get_product($product->get_parent_id());
				}	 
				$cat_ids = $product->get_category_ids();
				// if product has categories, concatenate cart item name with them
				if ( $cat_ids ) {
					$tmp = wc_get_product_category_list( $product->get_id() );//é a url da categoria
					list($lixo, $tmp1) = explode(">", $tmp, 2); // href completo
					$product_cat = substr($tmp1,0,-4); //Papelaria</a>
				}
				$amount = intval($item_data['quantity']); // alterado em 20/9/22
				if ($amount > 0) {
					$tmp_array[] =	array(
						'code'				=>	(string)$item_data['product_id'], //$product->get_sku(),
						'name'				=>	$item_data['name'],	//obrigatorio
						'value'				=>	(float)$item_data['total'],
						'amount'			=>	$amount,
						'categoryID'		=>	null,
						'categoryName'		=>	$product_cat,
						'isGift'			=>	false,
						'sellerName'		=>	'',
						//'isMarketPlace'		=>	'false',
						'shippingCompany'	=>	$metodo
					);
				} else {
					$tmp_array[] =	array(
						'code'				=>	(string)$item_data['product_id'], //$product->get_sku(),
						'name'				=>	$item_data['name'],	//obrigatorio
						'value'				=>	(float)$item_data['total'],
						'categoryID'		=>	null,
						'categoryName'		=>	$product_cat,
						'isGift'			=>	false,
						'sellerName'		=>	'',
						//'isMarketPlace'		=>	'false',
						'shippingCompany'	=>	$metodo
					);
				}
			} // end of for itens
			$header['items']	= $tmp_array;

/*
			//Passengers
			unset( $tmp_array );
			$tmp_array[]		=	array(
				'name'				=>	'',
				'companyMileCard'	=>	'',
				'mileCard'			=>	'',
				'identificationType'	=>	1,
				'identificationNumber'	=>	'',
				'gender'			=>	'',
				'birthdate'			=>	null,
				'cpf'				=>	''
			);
			$header['passengers'] = $tmp_array;

			//Connections
			unset( $tmp_array );
			$tmp_array[]		=	array(
				'company'			=>	'',
				'identificationNumber'	=>	0,
				'date'				=>	null,
				'seatClass'			=>	'',
				'origin'			=>	'',
				'destination'		=>	'',
				'boarding'			=>	null,
				'arriving'			=>	null,
				'fareClass'			=>	''
			);
			$header['connections']	=	$tmp_array;
*/
			//Hotels
/*			unset( $tmp_array );
			$tmp_array[]		=	array(
				'name'				=>	'',
				'city'				=>	'',
				'state'				=>	'',
				'country'			=>	'',
				'reservationDate'	=>	null,
				'reserveExpirationDate'	=>	null,
				'checkInDate'		=>	null,
				'checkOutDate'		=>	null
			);
			$header['hotels']	=	$tmp_array;
*/
			$pedido_header = array();
			$pedido_header[] = $header;

			////$this->wl->write_log("monta_pedido: array=" . print_r($pedido_header,true));
			$this->wl->write_log("Monta_pedido: json=" . json_encode($pedido_header));
		
		return( json_encode($pedido_header) );

	} // end of Monta_pedido


	//   Métodos auxiliares para montagem do pedido

	/**
	 * Método para deixar apenas dígitos num número, usado para CPF e CNPJ
	 * @param	string	$input	- Entrada com pontos, hifen, ou vírgulas
	 * @return	string	- Limpa, apenas dígitos
	 */
	public function deixarSomenteDigitos($input)
	{
		return preg_replace('/[^0-9]/', '', $input);
	}

	/**
	* Método auxiliar para extrair DDD e telefone de uma string
	*
	* @param	string	$telefone - (00) 0000-0000 ou (00) 00000-0000
	* @param	string	$retorno - 'ddd' ou qualquer outra coisa para retornar o telefone
	* @return	string|boolean # telefone, ddd ou false 
	*/
	public function dddTel($telefone, $retorno = "")
	{
		$ddd = "";
		// Remove todos os caractéres que não são números
		$telefone = preg_replace("/[^0-9]/", "", $telefone);
		// (00) 0000-0000 agora é 0000000000, etc
		if (strlen($telefone) === 10) {
	  		// Telefone fixo
	  		$ddd = substr($telefone, 0, 2);
			$telefone = substr($telefone, 2, 8);
		} else if (strlen($telefone) === 11) {
	  				// Telefone celular
	  				$ddd = substr($telefone, 0, 2);
	  				$telefone = substr($telefone, 2, 9);
				} else {
	  				// Formato não suportado
	  				return false;
				}
		if ($retorno == "ddd") {
	  		return $ddd;
		} else {
	  		return $telefone;
		}
	} // end of ddTel

	/**
	* Método para pegar tipo de entrega, converte texto em código
	*
	* @param	string	$courrier - texto do método do woocommerce
	* @return	string	código da pag 22 do manual, de 0 a 21
	*/
	public function Tipo_entrega($courrier)
	{
		$texto = strtolower($courrier);

		//correios-pac=21.47   correios-pac=19.77   correios-sedex=21.47
		if (strstr($texto, "correio")) return("11");
		if (strstr($texto, "sedex")) return("11");
		if (strstr($texto, "pac")) return("11");

		//local_pickup=0.00 - retira na loja
		if (strstr($texto, "local_pick")) return("16");
		if (strstr($texto, "retira na loja")) return("16");

		if (strstr($texto, "motoboy")) return("12");
	
		// se nada acima bateu ...
		return("0");
/*
		0		Outros		1		Normal		2		Garantida
		3		ExpressaBR	4		ExpressaSP	5		Alta
		6		Econômica	7		Agendada	8		Extra Rápida
		9		Impresso	10		Aplicativo	11		Correio
		12		Motoboy		13		Retirada Bilheteria
		14		Retirada Loja Parceira			15		Cartão de Crédito Ingresso
		16		Retirada Loja					17		Retirada via Lockers (Parceiros)
		18		Retirada em Agencia dos Correios
		19		Entrega Garantida no mesmo dia da compra
		20		Entrega Garantida no dia seguinte da compra
		21		Retirada em loja - Expresso
*/
	} // end of Tipo_entrega

	/**
	* Método para pegar Bandeira do cartão de crédito baseado no # do cartão
	* 
	* @param	string	$numero - Número do cartão de crédito
	* @return	int 	Codigo da ClearSale da Bandeira (Visa=3,Master=2, etc)
	*					ver Pag 19 do manual
	*
	*  https://pt.stackoverflow.com/questions/3715/express%C3%A3o-regular-para-detectar-a-bandeira-do-cart%C3%A3o-de-cr%C3%A9dito
	*  https://en.wikipedia.org/wiki/Payment_card_number
	*  https://www.4devs.com.br/gerador_de_numero_cartao_credito
	*
	*	Pag 19 do manual
	*   1 Diners Club
	*   2 MasterCard
	*   3 Visa
	*   4 Outros Cartões
	*   5 American Express
	*   6 HiperCard
	*   7 Cartão Aura
	*  10 Cartão Elo
	*  50 LeaderCard
	* 100 Fortbrasil
	* 101 Sorocred
	* 102 A Vista
	* 103 Cartão Mais
	* 105 Cartão C&A
	*
	*/
	public function Bandeira_Cartao($numero)
	{
	 
		if (strlen($numero) <= 0) return; // qdo nao tem # cartao é boleto ou debito

		$array = array(
			'4'		=> '/^63(6[7-9][6-9][0-9]|70[0-3][0-2])[0-9]{10}$/', // cz
			'1'		=> '/^(30[0-5][0-9]{11})|(3(6|8)[0-9]{12})$/', // diners
			'4'		=> '/^(637(095|612|599|609))[0-9]{10}$/', // hp
			'6'		=> '/^606282[0-9]{10}$/', // hipercard
			'4'		=> '/^((35670[0468][0-9]{10})([0-9]{1,3})?)|((35((2[8-9]|3[0-2]|[4-6][0-9]|8[0-9])[0-9][0-9])[0-9]{10})([0-9]{1,3})?)$/', // jcb
			'5'		=> '/^3[47][0-9]{13}$/', // amex
			'4'		=> '/^6(?:011|22[0-9]|4[0-9]{2}|5[0-9]{2})[0-9]{12}$/', // discover
			'10'	=> '/^((((636368)|(438935)|(504175)|(451416)|(636297))\d{0,10})|((5067)|(4576)|(4011))\d{0,12})$/', // elo
			'3'		=> '/^((4[0-9]{12})([0-9]{0,3})?)$/', // visa 16 bytes
			'2'		=> '/^5[1-5][0-9]{14}$/', // master 16 bytes
			'7'		=> '/^50[0-9]{14}$/' // aura
		);
	 
		$anum = array( $numero );
	 
		foreach ($array as $key => $exp) {
			if (preg_grep($exp, $anum)) return( $key );
		}
		return(4);

	} // end of Bandeira_Cartao

	/**
	* Método para pegar tipo de pagamento, converte nomes para códigos.
	* 
	* @param	string	$tipo - texto com tipo de pgto do woocommerce
	* @return	int		código da pag 18 do manual, de 1 a 16
	*/
	/*
		pgto na entrega  Tipo_pagamento: tipo=cod
		transferencia bancaria - Tipo_pagamento: tipo=bacs
	*/
	public function Tipo_pagamento($tipo)
	{
		$this->wl->write_log("Clearsale_Total_Api:Tipo_pagamento: tipo=" . $tipo);
		$texto = strtolower($tipo);

		// 23/9/20 - email de 22/9 17:43 pede para mudar o que for transf e pgto em dinheiro para tipo 11
		//bacs - Transferência bancária
		if (strstr($texto, "bacs")) return(11);
		if (strstr($texto, "transferência bancária")) return(11);
		//pgto na entrega, nativo do woo, retorna cod
		if (strstr($texto, "cod")) return(11);

		//pagseguro [payment_method] => pagseguro  [payment_method_title] => PagSeguro
		if (strstr($texto, "pagseguro")) return(14);
		if (strstr($texto, "pag seguro")) return(14);

		//if (strstr($texto, "credito")) return(1); foi para o final 22/3/24

		// modulo cielo-webservices. Cielo WooCommerce - Solução Webservice - Versão 4.0.14 - Claudio Sanches
		if (strstr($texto, "cielo_credit")) return(1); // modulo cielo-webservices. Cielo WooCommerce - Solução Webservice - Versão 4.0.14
		if (strstr($texto, "cielo_debit")) return(3);  // modulo cielo-webservices. Cielo WooCommerce - Solução Webservice - Versão 4.0.14

		//Rede API integration for WooCommerce - Webservice
		if (strstr($texto, "rede_credit")) return(1);  // modulo rede. rede-woocommerce 1.0.0
		//Version 1.0 | By e.rede |  Deve ser novo, sai com: Tipo_pagamento: tipo=erede
		if (strstr($texto, "erede")) return(1); // olhando o plugin parece ter apenas crédito

		//Checkout Cielo for WooCommerce. Claudio Sanches 1.0.4, leva ao site da Cielo para pagar com crédito/debito/boleto, na volta
		// pegamos um post_meta.
		if (strstr($texto, "checkout-cielo-0")) return(2); // nao definido, nao achou post_meta
		if (strstr($texto, "checkout-cielo-1")) return(1); // crédito
		if (strstr($texto, "checkout-cielo-2")) return(2); // boleto
		if (strstr($texto, "checkout-cielo-3")) return(3); // débito on-line
		if (strstr($texto, "checkout-cielo-4")) return(3); // cartão de débito

		//Gateway de pagamento Pagar.me para WooCommerce - webservice. Versão 2.0.15 | Por Pagar.me, Claudio Sanches
		if (strstr($texto, "pagarme-banking-ticket")) return(2); // boleto
		if (strstr($texto, "pagarme-credit-card")) return(1); // crédito
		// por indicação do Felipeflop em 4/4/22
		if (strstr($texto, "wc_pagarme_pix_payment")) return(27); //"wc_pagarme_pix_payment_geteway"
		//Integração de Pagamento Pagar-me pela Pagar-me Versão 2.0.14, agora 3.1.1
		// ver Clearsale_Total_Checkout:outrosMetodos - woo-pagarme-payments -  woo-pagarme-payments-billet woo-pagarme-payments-pix
		// sai pix ou billet

		//Cielo Webservice API 3.0 - Jrossetto - inserido em 28/01/20
		if (strstr($texto, "jrossetto_woo_cielo_webservice")) return(1); // crédito, pelo log da brisae.com.br

		//WooCommerce Boleto PagHiper - Versão 1.2.6.1 | Por PagHiper, Henrique Cruz
		if (strstr($texto, "paghiper")) return(2);

		//e.Rede - Webservice - e.Rede API - Cartão de Crédito - Versão 1.0 - http://www.loja5.com.br/
		if (strstr($texto, "loja5_woo_novo_erede_debito")) return(3);
		if (strstr($texto, "loja5_woo_novo_erede")) return(1); // crédito

		// inserido em 16/6/21 pix e boleto da loja5 e boleto da Juno
		// Pix - loja5 loja5_woo_pix_estatico - Integração aos Pagamentos Pix Estático - V 1.0
		if (strstr($texto, "loja5_woo_pix_estatico")) return(27);

		// Boleto - loja5 Banco do Brasil - Boleto - Integração aos Pagamentos Banco do Brasil Ecommerce. - V 1.0
		if (strstr($texto, "loja5_woo_bb_ecommerce")) return(2);

		// Boleto - Juno - Boleto - Juno para WooCommerce - Versão 2.3.3
		if (strstr($texto, "juno-bank-slip")) return(2);
		if (strstr($texto, "juno-pix")) return(27); // 04/04/22 pelo CarnesNobres

		//WooCommerce pagamento na entrega - V 1.3.2 - Carlos Ramos - woo_payment_on_delivery - 14/12/20
		if (strstr($texto, "woo_payment_on_delivery")) return(9);

		//Cielo API 3.0 - Loja 5 - Plugin V 3.0 - débito = loja5_woo_cielo_webservice_debito credito=loja5_woo_cielo_webservice
		//Dados de cartão são pegos pelo outrosMetodos - 16/08/22
		if (strstr($texto, "loja5_woo_cielo_webservice_debito")) return(3); // primeiro este
		if (strstr($texto, "loja5_woo_cielo_webservice_boleto")) return(2); // depois este
		if (strstr($texto, "loja5_woo_cielo_webservice_pix")) return(27);
		if (strstr($texto, "loja5_woo_cielo_webservice_tef")) return(6);
		if (strstr($texto, "loja5_woo_cielo_webservice")) return(1); // este por último, não pode achar este antes!

		//Payzen Payment for WooCommerce. Versão 1.0.27 | Por iPag | dia 10/2/21 visto códido do plugin payzen
		if (strstr($texto, "payzen-payment_debit")) return(3); // primeiro este
		if (strstr($texto, "payzen-payment_bankslip")) return(2); // depois este
		if (strstr($texto, "payzen-payment_formbank")) return(2); // payzen-payment_formbankslip no log do Speed Cell 97
		if (strstr($texto, "payzen-payment")) return(1); // este por último, não pode achar este antes!

        //bp_boleto_santander - Boleto Santander - Versão 1.3.1 | Por Rodrigo Max / Agência BluePause - em 21/6/21
		if (strstr($texto, "bp_boleto_santander")) return(2);

		//PIX por Piggly - V 1.3.15 - https://wordpress.org/plugins/pix-por-piggly/
		if (strstr($texto, "wc_piggly_pix_gateway")) return(27);

		//MercadoPago payments for Woocommerce - V 5.2.1
		if (strstr($texto, "woo-mercado-pago-pix")) return(27);
		if (strstr($texto, "woo-mercado-pago-ticket")) return(2); // boleto ou pgto em lotérica

		// Ipag por Ipag - V 2.1.4 - https://br.wordpress.org/plugins/ipag-woocommerce/ - 20/1/22
		if (strstr($texto, "ipag-gateway_pix")) return(27); // pix
		if (strstr($texto, "ipag-gateway_boleto")) return(2); // boleto
		if (strstr($texto, "ipag-gateway_debito")) return(3); // debito
		if (strstr($texto, "ipag-gateway_itaushopline")) return(2); // boleto ou debito tb
		if (strstr($texto, "ipag-gateway")) return(1); //credito, vai ser pego pelo metodo outrosMetodos, com post_meta

		//rede por MarcosAlexandre  2.1.1 - https://br.wordpress.org/plugins/woo-rede/ - 20/1/22
		// tipo=rede_credit ele sai por este texto acima, do plugin da rede mesmo
		//https://seminovos.ihelpu.com.br usa este método

		// Assas - crédito, boleto e Pix - 11/04/22 - Asaas Gateway para WooCommerce - Versão 1.6.1 | Por Asaas
		if (strstr($texto, "asaas-pix")) return(27);
		if (strstr($texto, "asaas-ticket")) return(2); // boleto
		if (strstr($texto, "asaas-credit-card")) return(1); //credito

		//Gerencianet - Versão 1.4.7 - Gateway de pagamento Gerencianet para WooCommerce 7/7/22
		//nao é selecionado aqui, salva via ajax em dadosextras, aqui entra como cartao|boleto|pix

		// Iugu -16/8/22 - _payment_method = iugu-bank-slip - iugu-pix - iugu-credit-card por ajax
		// Gateway de pagamento da iugu para WooCommerce. Versão 3.0.0.11 | Por iugu
		if (strstr($texto, "iugu-bank-slip")) return(2);
		if (strstr($texto, "iugu-pix")) return(27);

		// Openpix - 06/09/22 
		if (strstr($texto, "woocommerce_openpix_pix")) return(27);

		// Pagbank - por Pagbank - V 1.1.0 - 22/03/24 - cartao via ajax
		if (strstr($texto, "pagbank_pix")) return(27);
		if (strstr($texto, "pagbank_boleto")) return(2); // boleto
		if (strstr($texto, "pagbank_credit_card")) return(1); //credito

		// Click2pay - Por Click2Pay - Versão 1.3.0 - Cartao via ajax, boleto e PIX - 31/7/24
		if (strstr($texto, "click2pay-credit-card")) return(1); //credito
		if (strstr($texto, "click2pay-bank-slip")) return(2);
		if (strstr($texto, "click2pay-pix")) return(27);

		if (strstr($texto, "pix")) return(27); // gerencianet sai por aqui
		if (strstr($texto, "boleto")) return(2);
		if (strstr($texto, "billet")) return(2);
		if (strstr($texto, "debito")) return(3);
		if (strstr($texto, "credito")) return(1);

		return(14);	// outros
	/*
	1	Cartão de Crédito
	2	Boleto Bancário
	3	Débito Bancário
	4	Débito Bancário – Dinheiro
	5	Débito Bancário – Cheque
	6	Transferência Bancária
	7	Sedex a Cobrar
	8	Cheque
	9	Dinheiro
	10	Financiamento
	11	Fatura
	12	Cupom
	13	Multicheque
	14	Outros
	16	VALE
	27	PIX
	1041	Cartão Presente Virtual
	4011	Cartão de Débito/Transferência Eletrônica (CD)
	*/
	}// end of Tipo_pagamento

	/**
	* Método para pegar tipo de moeda, converte um código alfabético para numérico.
	* 
	* @param	string	$tipo - código alfabetico da moeda
	* @return	int		código númerico da moeda - da pag 25 do manual.
	*/
	public function Tipo_moeda($tipo)
	{
		$texto = strtolower($tipo);
		if (strstr($texto, "usd")) return(814);
		if (strstr($texto, "brl")) return(986);
		if (strstr($texto, "eur")) return(978);
		if (strstr($texto, "cad")) return(124);
		return(0);

	} // end of Tipo_moeda

	/**
	 * Pegar bairro dado um CEP, usando WebService dos correios entra com CEP e retorna a UF.
	 * deprecated!!! Não existe mais webservices e soap
	 * @param	string	$cep - CEP
	 * @return	string	Nome do bairro do CEP dado
	*/
	public function Busca_Bairro_ws($cep)
	{
		$this->wl->write_log("Clearsale_Total_Api:Busca_Bairro: cep=" . $cep);

		$cep = preg_replace('/[^\d]/', '', $cep);

		return '  ';
	} // end of Busca_Bairro_ws


} // end/ of class

