<?php

/**
 * Register all actions for the ajax page
 */

/**
 * Register all actions for the ajax page, to add order in ClearSale via PagSeguro.
 *
 * Se o pedido já foi inserido pelo checkout, que é o fim para o woocommerce e vamos tentar inserir por aqui
 * também, vai dar erro na ClearSale, como abaixo
 * erro #400 msg={"Message":"The request is invalid.","ModelState":{"existing-orders":["171"]}}
 * No caso o teste foi com o pedido # 171
 *
 *
 * @package    Clearsale_Total
 * @subpackage Clearsale_Total/includes
 * @author     Letti Tecnologia <contato@letti.com.br>
 * @link       https://letti.com.br/wordpress
 */
class Clearsale_Total_Ajax
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
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
    private $version;
    
 	/**
	 * The mode of work with ClearSale.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $modo    The mode save in admin. 0=produção  1=teste
	 */
    private $modo;

    /**
	 * Initialize the class and set its properties.
	 *
	 * @since    "1.0.0"
	 * @param    string    $plugin_name       The name of this plugin.
	 * @param    string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{
        global $wpdb;

		$this->plugin_name = $plugin_name;
		$this->version = $version;
        $this->wp_cs_options = get_option($this->plugin_name); //get mysql data
        $this->modo = $this->wp_cs_options['modo'];
	}

	/**
	*  Metodo que Adiciona um script para o WordPress.
	* @since    "1.0.0"
	*/
	public function clearsale_total_secure_enqueue_script()
	{
		wp_register_script('secure-ajax-access', esc_url(add_query_arg(array('js_global' => 1), site_url())));
		wp_enqueue_script('secure-ajax-access');
	}

	/**
	* Metodo que coloca o nonce e a url para as requisições para dentro do java script.
	*
	* @since    "1.0.0"
	*/
	public function clearsale_total_javascript_variaveis()
	{
		if (!isset($_GET['js_global'])) return;

		$wl = new Clearsale_Total_Log($this->plugin_name, $this->version);

		$nonce = wp_create_nonce('clearsale_total_ajax_nonce');
		$nonce_mbox = wp_create_nonce('cs_total_mbox_nonce');

		$variaveis_javascript = array(
			'clearsale_total_ajax_nonce' => $nonce, //Esta função cria um nonce para nossa requisição.
			'cs_total_mbox_nonce' => $nonce_mbox,
			'xhr_url'              => admin_url('admin-ajax.php') // Forma para pegar a url.
		);

		$new_array = array();
		foreach($variaveis_javascript as $var => $value) $new_array[] = esc_js($var) . " : '" . esc_js($value) . "'";

		header("Content-type: application/x-javascript");
		printf('var %s = {%s};', 'js_global', implode(',', $new_array));
		exit;
	} // end of javascript_variaveis

	/**
	 * Metodo acionado pelo ajax.
	 * @since    "3.0.10"
	 * 
	 */
	public function cs_total_mbox_ajax_action()
	{
		global $woocommerce;

		$wl = new Clearsale_Total_Log($this->plugin_name, $this->version);
		$wl->write_log("Clearsale_Total_Ajax:cs_total_mbox_ajax_action: entrou, pedido=" . sanitize_text_field(wp_unslash($_POST['mbox'])));
		$wl->write_log("Clearsale_Total_Ajax:cs_total_mbox_ajax_action: POST nonce=" . sanitize_text_field(wp_unslash($_POST['cs_total_mbox_nonce'])));

		if ( ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cs_total_mbox_nonce'])), 'cs_total_mbox_nonce')) {
			$wl->write_log("Clearsale_Total_Ajax:cs_total_mbox_ajax_action: Não passou pelo nonce!");
			$wl->write_log("Clearsale_Total_Ajax:cs_total_mbox_ajax_action: Caso tenha um sistema de cache instalado na sua loja limpe-o! Limpe o cache do seu navegador também!");
			// 3/3/23 - papelariarainha tem o wprocket e estava dando erro de nonce, limpou e resolveu!
			//echo '401'; // é o nosso nonce enviado? senão a requisição vai retornar 401
			//die(-1);
		}
		
		//TODO: Add your button saving stuff here.
		$postid = sanitize_text_field(wp_unslash($_POST['mbox']));
		$wl->write_log("Clearsale_Total_Ajax:cs_total_mbox_ajax_action: Reenvio do pedido #=" . $postid);

        if (empty($postid)) {
            $wl->write_log("Clearsale_Total_Ajax:cs_total_mbox_ajax_action: entrou mas # pedido vazio!!");
			echo '401';
            die(-1);
        }
        $order = new WC_Order($postid);
		$cst = new Clearsale_Total_Checkout($this->plugin_name, $this->version);
		$nada = null;
		if ($cst->SendOrder($postid, $nada, $order) > 1) {
			$wl->write_log("Clearsale_Total_Ajax:cs_total_mbox_ajax_action: Erro ao gravar pedido na ClearSale!!");
			echo '401';
			die(-1);
		}

		$wl->write_log("Clearsale_Total_Ajax:cs_total_mbox_ajax_action: Pedido re-enviado com sucesso para ClearSale!!");

		echo 200;
		exit;

	} // end of cs_total_mbox_ajax_action

	//add_action('wp_ajax_nopriv_clearsale_push', 'clearsale_push');
	//add_action('wp_ajax_clearsale_push', 'clearsale_push');

	/**
	 * Metodo acionado pelo ajax.
	 * @since    "1.0.0"
	 * As variáveis foram pegas do javascript na pag de fechamento do Pagseguro v 2.0.0
     * fonte  - template/direct-payment.php e de outros forms de outros métodos.
	 */
	public function clearsale_total_push()
	{
		global $woocommerce, $wpdb;

		$wl = new Clearsale_Total_Log($this->plugin_name, $this->version);
		$cart_session = $woocommerce->session->get('cs_session_id');
		$wl->write_log("Clearsale_Total_Ajax:clearsale_total_push: entrou com cart_session=" . $cart_session);

		if( ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['clearsale_total_ajax_nonce'])), 'clearsale_total_ajax_nonce' )) {
			$wl->write_log("Clearsale_Total_Ajax:clearsale_total_push: Não passou pelo nonce!");
			$wl->write_log("Clearsale_Total_Ajax:clearsale_total_push: Caso tenha um sistema de cache instalado na sua loja limpe-o!");
			esc_html_e('401', 'clearsale-total'); // é o nosso nonce enviado? senão a requisição vai retornar 401
			die();
		}
/*
		var dados_pagseg = {
			metodo: metodo,    ->1,2,3.... ver clearsale-total-public-txt.php
			order: order_id,
			card: card,
			card_holder: card_holder,
			cpf: cpf,
			validate: validate,
			doc_boleto: document_boleto,
			doc_debito: document_debit,
			doc_pix: document_pix
		};
clearsale_push=Array#012(#012    [order] => 116#012    [card] => 4012 0010 3844 3335#012    [card_holder] => joana da silva#012
  [cpf] => 050.562.788-40#012    [validate] => 06/2020#012    [holder_dob] => 20/02/1969#012)
*/

		$pagseguro = map_deep($_POST['pagseguro'], 'sanitize_text_field');
		$metodo = 0;
		if (isset($pagseguro['metodo'])) {
			$metodo = $pagseguro['metodo'];
		}
		if ($metodo <= 0) {
			$wl->write_log("Clearsale_Total_Ajax:clearsale_total_push: sem metodo!? =" . $metodo);
			esc_html_e('200', 'clearsale-total');
			exit;
		}
		// se metodo != 1 no lugar de pedido teremos nonce o woocommerce-process-checkout-nonce
		// se fizer dois pedidos seguidos o nonce vai ser igual!!!?????
		// gravar dados em cs_total_dadosextras

		$pedido = $pagseguro['order']; // se pagseguro é o # do pedido, senão vai ser sessionid
		// Cuidado, o # do cartão está completo aqui, mais abaixo vai ser salvo no postmeta e não pode!!
		$card = str_replace(" ", "", $pagseguro['card']);
		$card_holder = $pagseguro['card_holder'];
		$cpf = $pagseguro['cpf'];
		$validate = $pagseguro['validate'];
		$installments = "";
		if (isset($pagseguro['installments'])) $installments = $pagseguro['installments'];
		$doc_boleto = $pagseguro['doc_boleto']; // só existe se for boleto
		$doc_debito = $pagseguro['doc_debito']; // só existe se for debito
		$doc_pix = $pagseguro['doc_pix']; //só existe se for pix

//[01-Sep-2018 18:26:31 UTC] clearsale:pedido=122 cartao=4012 0010 3714 1112 nome=alfredo letti cpf=050.562.788-40 validade=10/2020 dob=20/06/1980
		$wl->write_log("Clearsale_Total_Ajax:clearsale_total_push: metodo=" . $metodo . " pedido=" . $pedido . 
		" bin=" . substr($card, 0, 6) . " nome=" . $card_holder . " cpf=" . $cpf . " validade=" . $validate .
		" installments=" . $installments);
		if (strlen($pedido)<=0 || strlen($card)<=0) { esc_html_e('200', 'clearsale-total'); exit; }

		// montar array com dados do cartao para ClearSale
		// se for boleto nao temos dados do cartao
		if ($doc_boleto) {
			$cartao = array(
				'modo'			=>  'boleto',
				'numero'		=>	'',
				'bin'			=>	'',
				'end'			=>	'',
				'validity'		=>	'',
				'installments'	=>	'',
				'owner'			=>	'',
				'document'		=>	$doc_boleto
			);
		} 
		if ($doc_debito) { // é debito?
			$cartao = array(
				'modo'			=>  'debito',
				'numero'		=>	'',
				'bin'			=>	'',
				'end'			=>	'',
				'validity'		=>	'',
				'installments'	=>	'',
				'owner'			=>	'',
				'document'		=>	$doc_debito
			);
		}
		if ($doc_pix) { // é pix?
			$cartao = array(
				'modo'			=>  'pix',
				'numero'		=>	'',
				'bin'			=>	'',
				'end'			=>	'',
				'validity'		=>	'',
				'installments'	=>	'',
				'owner'			=>	'',
				'document'		=>	$doc_pix
			);
		}
		if ($card) { // é cartão
			$tt = strlen($card) - 10;
			$card1 = substr($card, 0, 6) . substr("***********", 0, $tt) . substr($card, -4);
			$cartao = array(
				'modo'			=>  'credito',
				'numero'		=>	$card1,
				'bin'			=>	substr($card, 0, 6),
				'end'			=>	substr($card, -4),
				'validity'		=>	$validate,
				'installments'	=>	(int)$installments,
				'owner'			=>	$card_holder,
				'document'		=>	$cpf
			);
		}
		if (count($cartao)<1) { // nenhuma das anteriores
			$cartao = array(
				'modo'			=>  '',
				'numero'		=>	'',
				'bin'			=>	'',
				'end'			=>	'',
				'validity'		=>	'',
				'installments'	=>	'',
				'owner'			=>	'',
				'document'		=>	''
			);
		}

		// neste ponto salvamos os dados de cartão para qdo mudar status do pedido saber que foi com cartão
		// Pagseguro (metodo=1) salva no postmeta, pois temos # do pedido, os outros na tabela cs_total_dadosextras.
		switch ($metodo) {
			case 1:
				//update_post_meta($pedido, 'cs_cartao', $cartao);
				// compatibilidade com HPOS
				$order = new WC_Order($pedido);
                $order->update_meta_data('cs_cartao', $cartao);
                $order->save();
				$wl->write_log("Clearsale_Total_Ajax: clearsale_total_push: Pedido " . $pedido . ", pago com "
				 . $cartao['modo'] . " dados salvo para posterior inserção na ClearSale");
			break;
			case 4: // Gerencianet    2=ipag 3=rede MA -> ver clearsale-total-public-txt.php
			case 6: // Iugu
			case 8: // Pagseg-Claudio
			case 11: // Pagbank
			case 12: // Click2pay
				//Em pedido, qdo for != de 1 é o sessionID
				//Temos que testar se já tem a chave, se tiver foi no checkout que deu erro, por exemplo o cartão
				//foi negado e na nova tentativa a chave é a mesma (não mudou a session), então faremos update.

				$table_name = $wpdb->prefix.'cs_total_dadosextras';
				// salvava com time zone do Brasil, só para equidade, mas vai fazer UTC agora.
        		$datea = date('Y-m-d H:i:s');

				$mydata = $wpdb->get_row("SELECT * FROM $table_name WHERE chave = '$pedido'", ARRAY_A);
				if ($mydata) { // se existe fazer update
					$sql = "UPDATE " . $table_name . " set metodo=" . $metodo . ", dados='" . serialize($cartao) . "',
					 capturado_data='" . $datea . "' WHERE chave='" . $pedido . "'";
					$mydata = $wpdb->get_row($sql, ARRAY_A);
					if ($mydata) {
						$wl->write_log("Clearsale_Total_Ajax: clearsale_total_push: Metodo=" . $metodo . " modo="
						. $cartao['modo'] . " chave=" . $pedido);
						$wl->write_log("Clearsale_Total_Ajax: clearsale_total_push: Chave já existe e deu erro ao
						fazer o update!!!");
						if ($wpdb->last_error) $wl->write_log("Erro:(" . $wpdb->last_error . ")");
					} else { // retorna null se tudo ok
						$wl->write_log("Clearsale_Total_Ajax: clearsale_total_push: Metodo=" . $metodo . " modo="
						. $cartao['modo'] . " chave=" . $pedido . ", BIN " . $cartao['bin']
						. " update na tabela cs_total_dadosextras para posterior inserção na ClearSale");
					}
				} else { // não existe vamos incluir
        			$wpdb->insert($table_name, array('chave' => $pedido, 'metodo' => $metodo,
					 'dados' => serialize($cartao), 'capturado_data' => $datea));
					// erros em $last_error
					if ($wpdb->last_error) $wl->write_log("Erro:(" . $wpdb->last_error . ")");
					$wl->write_log("Clearsale_Total_Ajax: clearsale_total_push: Metodo=" . $metodo . " modo=" . $cartao['modo']
					 . " chave=" . $pedido . ", BIN " .$cartao['bin']
					 . " dados inseridos na tabela cs_total_dadosextras para posterior inserção na ClearSale");
				}
			break;
		}
		esc_html_e('200', 'clearsale-total');
		exit;


	} // end of clearsale_total_push

} // end of class

