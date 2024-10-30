<?php

/**
 * Register all actions for the checkout page
 */

/**
 * Register all actions for the checkout page, to add status "Esperando aprovação do Pagamento", the order is inserted when status changed.
 *
 *
 * @package    Clearsale_Total
 * @subpackage Clearsale_Total/includes
 * @author     Letti Tecnologia <contato@letti.com.br>
 * @link       https://letti.com.br/wordpress
 * @since      "1.0.0"
 
 */
class Clearsale_Total_Checkout
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

        //$this->modo = $this->wp_cs_options['modo'];
	}

    public function Cs_total_payment_complete ($order_id)
    {
        $wl = new Clearsale_Total_Log($this->plugin_name, $this->version);

        $wl->write_log("Clearsale_Total_Checkout: hook woocommerce_payment_complete: order id=" . $order_id);
    }

    public function Cs_total_before_thankyou($order_id)
    {
        $wl = new Clearsale_Total_Log($this->plugin_name, $this->version);

        $wl->write_log("Clearsale_Total_Checkout: hook woocommerce_before_thankyou: order id=" . $order_id);
    }

    /**
     * Metodo chamado pelo hook do tankyou
     * 
     * $this->loader->add_action('woocommerce_thankyou', $plugin_checkout, 'Cs_realtime_thankyou', 10, 1);
     * linha 80 do fonte woocommerce/templates/checkout/thankyou.php
     * 
     * @param   string  $order_id
     * 
     */
    // neste ponto nao temos mais a session
    public function Cs_total_thankyou($order_id)
    {
        global $wpdb;

        $wl = new Clearsale_Total_Log($this->plugin_name, $this->version);

        $wl->write_log("Clearsale_Total_Checkout:Cs_total_thankyou: hook woocommerce_thankyou: order id=" . $order_id);
        if ($order_id == null || strlen($order_id) <=0) return;

        $order = wc_get_order($order_id);

        // compatibilidade com HPOS
        $enviado = get_post_meta($order_id, 'cs_pedido_enviado', true);
        if (empty($enviado))
            $enviado = $order->get_meta('cs_pedido_enviado');
        if ($enviado == "SIM"){
            $wl->write_log("Clearsale_Total_Checkout:Cs_total_thankyou: hook woocommerce_thankyou: Pedido já enviado!");
            return;
        }
        if ($enviado == null) $enviado = 0;
        $wl->write_log("Clearsale_Total_Checkout:Cs_total_thankyou: hook woocommerce_thankyou:
            Pedido não enviado ainda! Passou pelo SendOrder ". $enviado . " vez(es).");

        //return; // não salvamos pedidos no fechamento-22/9/22

        $sess_id = get_post_meta($order_id, 'cs_sess_id', true); // Pegamos o sess_id do post_meta, já salvo qdo tinha session
        if (empty($sess_id))
            $sess_id = $order->get_meta('cs_sess_id');
        
        $nada = null;
        $order_data = $order->get_data(); // melhor desta forma
        // compatibilidade com HPOS
        $cs_doc = get_post_meta($order_id, 'cs_doc', true);
        if (empty($cs_doc))
            $cs_doc = $order->get_meta('cs_doc');

        $forma_pgto = $order_data['payment_method'];
        $cartao = array();
        $cartao = get_post_meta($order_id, 'cs_cartao', true); // vamos pegar dados do cartão salvo no ajax.
        if (empty($cartao))
            $cartao = $order->get_meta('cs_cartao');
        if ($cartao == null || count($cartao) <= 0) {
            $ret = $this->outrosMetodos($forma_pgto, $order_id, $cs_doc, $cartao, $wl, "Cs_total_thankyou");
            if ($ret < 0) {// -2, -3 ... o número se refere ao documento clearsale-total-public-txt.php
                //nao tem post_meta aqui, vamos sair e ver no proximo hook??!! não tem proximo....
                $wl->write_log("Clearsale_Total_Checkout:Cs_total_thankyou: Pedido " . $order_id . " método de cartão com post_meta, mas não chegou ainda, saindo...");
                return;
            }
            if ($ret == null) {
                //nao tem post_meta aqui, $cartão é NULO pode ser boleto...
                $wl->write_log("Clearsale_Total_Checkout:Cs_total_thankyou: Pedido " . $order_id . " não tem cartão em postmeta, saindo...");
                return;
            }
        } else $ret = $cartao;
        //$this->SendOrder($order_id, $nada, $order); // não salvamos pedidos no fechamento-22/9/22

        $cs_status = new Clearsale_Total_Status($this->plugin_name, $this->version);
        $statusRisco = $cs_status->getStatusOnNotes($order_id, "Risco ClearSale");
        // neste ponto podemos ter a nota Risco... mas ainda ter o webhook de "processando" que vai incluir o pedido
        // na papelariarainha ele passa aqui e não tem mais nenhum webhhok e não integra pedido.
        $wl->write_log("Clearsale_Total_Checkout:Cs_total_thankyou: Pedido " . $order_id . " nota de Risco=" . $statusRisco);
        $statusProc = $cs_status->getStatusOnNotes($order_id, "process");
        $wl->write_log("Clearsale_Total_Checkout:Cs_total_thankyou: Pedido " . $order_id . " Processing=" . $statusProc);


        $wl->write_log("Clearsale_Total_Checkout:Cs_total_thankyou: hook woocommerce_thankyou: order id=" . $order_id . " cartão owner=" . 
            $ret['owner'] . " bin=" . $ret['bin'] . " end=" . $ret['end']);
        // aqui é hook de fechamento de pedido, só integramos após pagamento. Mas visto numa loja, a papelaria rainha,
        // o pedido passou para processando pelo pagar.me e este não tinha salvo os dados de cartão ainda, então não integramos,
        // mas passou aqui, conseguimos ler os dados do cartão (pelo post_meta) e saiu sem fazer nada. Podemos olhar
        // o status do pedido se ele já foi pago e não foi enviado e está neste ponto então podemos integrar ele.
        // O status vai ser cs-inanalisis ou processing!

        // passou 2 vezes pelo SendOrder, no hook de processando não conseguiu enviar, provavel falta de postmeta
        if ($enviado >= 1) {
            $ret1 = $this->SendOrder($order_id, $nada, $order);
            if ($ret1 == 1)
                $wl->write_log("Clearsale_Total_Checkout:Cs_total_thankyou: TKY: Integrando pedido com hook de Thankyou!");
        } else $wl->write_log("Clearsale_Total_Checkout:Cs_total_thankyou: saindo...");
    } // end of Cs_total_thankyou

    /**
     * Metodo chamado pelo hook do woocommerce_checkout_order_processed
     * 
     * @param   string  $order_id
     * @param   array   $posted_data
     * @param   class   $order
     */
    public function Cs_total_checkout_order_processed($order_id, $posted_data, $order)
    {
        global $woocommerce;
        $wl = new Clearsale_Total_Log($this->plugin_name, $this->version);

        $wl->write_log("Clearsale_Total_Checkout: hook woocommerce_checkout_order_processed: order id=" . $order_id);
        if ($order_id == null || strlen($order_id) <=0) return;

        $b_country = $s_country = "";
        if (isset($_POST['billing_country'])) $b_country = sanitize_text_field(wp_unslash($_POST['billing_country']));
        if (isset($_POST['shipping_country'])) $s_country = sanitize_text_field(wp_unslash($_POST['shipping_country']));
        $wl->write_log("Clearsale_Total_Checkout: checkout_order_processed: billing_country=" . $b_country . " shipping_country=" . $s_country);
        if ($b_country != "BR") { // || $s_country != "BR") {
            $wl->write_log("Clearsale_Total_Checkout: checkout_order_processed: Compras fora do Brasil não integramos na CS.");
            return;
        }
        $wl->write_log("Clearsale_Total_Checkout: checkout_order_processed: Compras no BR integramos na CS.");

        $woo_sess =$woocommerce->session;
        //Neste ponto o cliente já se logou e temos o $key da sessão do carrinho do woocommerce,
        //objetos da sessão como $woo_sess->_cookie e $woo_sess->_customer_id são objetos protegidos
        /*
        022-07-05T20:03:39+00:00 DEBUG Cs_total_checkout_order_processed: sess_cart=Array (
        [45c48cce2e2d7fbdea1afc51c7c6ad26] => Array
            (
                [key] => 45c48cce2e2d7fbdea1afc51c7c6ad26
                [product_id] => 9
                [variation_id] => 0
                [variation] => Array (  )
                [quantity] => 1
                [data_hash] => b5c1d5ca8bae6d4896cf1807cdf763f0
                [line_tax_data] => Array
                (
                        [subtotal] => Array ( )
                        [total] => Array ( )
                )
                [line_subtotal] => 67.5
                [line_subtotal_tax] => 0
                [line_total] => 67.5
                [line_tax] => 0
            )
        )
        */
        $sess_id = get_post_meta($order_id, 'cs_sess_id', true); // Pegamos o sess_id do post_meta, já salvo qdo tinha session
        if (empty($sess_id)) {
            $sess_id = $order->get_meta('cs_sess_id');
        }
        if (!$sess_id) {
            $sess_id = (string)time(); //nunca deveria ser este
            if (isset($woo_sess)) {
                $tmp=$woo_sess->cart;
                if ($tmp) { 
                    $tmp1 = array_pop($tmp);
                    if ($tmp1) {
                        $tmp1 = $tmp1['key'];
                    }
                }
                $t_tail = substr((string)time(), 0, 8);
                //if ($tmp1) $this->session_id = (string)$tmp1 . "_" . (string)time();
                $sess_id = (string)$tmp1 . "_" . $t_tail;
            }
            //Em footer calculamos um cs_sess_id com a session+timestamp e salva na session
            $tmp3 = $woocommerce->session->get('cs_session_id');
            if ($tmp3) $sess_id = $tmp3;
            // salvamos em banco a session do woocomerce. no callback nao temos esta session
            //update_post_meta($order_id, 'cs_sess_id', sanitize_text_field($sess_id));
            // compatibilidade com HPOS
            $order->update_meta_data('cs_sess_id', sanitize_text_field($sess_id));
            $order->save();
        }
        $wl->write_log("Clearsale_Total_Checkout:Cs_total_checkout_order_processed: session=" . $sess_id);

        // Vamos colocar no pedido o status "Esperando aprovação do Pagamento"
        $note = __("ClearSale status: ", "clearsale-total");
        $note .= "EAP: Esperando Aprovação do Pagamento";

        // pelo email de 24/3/21 18:35 tiramos esta status, vai ficar em branco, mas vamos logar
        $wl->write_log("Clearsale_Total_Checkout:Cs_total_checkout_order_processed: order id=" . $order_id . " - EAP: Esperando Aprovação do Pagamento");
        // Add the note
        //$order->add_order_note($note);
        // Save the data
        //$order->save();

    } // end of Cs_total_checkout_order_processed

	/**
	* Metodo que vai enviar pedidos para ClearSale. Vai ser chamado pelo thankyou
	*
    *
    * @param    string  $order_id
    * @param    array   $posted_data
    * @param    object  $order
    * @return   string  false | 1 | 2 ...
    *                   =1 integrou com sucesso
    *                   =2 não achou dados de cartão, não integrou
    *                   =3 não foi no BR a compra
    *                   =4 erro ao incluir na Clear
	*/
    public function SendOrder($order_id, $posted_data, $order)
    {
        global $woocommerce, $wpdb;

        $wl = new Clearsale_Total_Log($this->plugin_name, $this->version);
        //$b_country = $s_country = "";
        $b_country = $order->get_billing_country();
        $s_country = $order->get_shipping_country();
        $wl->write_log("Clearsale_Total_Checkout:SendOrder: Método para enviar pedido para CS.");
        $wl->write_log("Clearsale_Total_Checkout:SendOrder: dados do pedido: billing_country="
         . $b_country . " shipping_country=" . $s_country);
        if ($b_country != "BR") { // || $s_country != "BR") {
            $wl->write_log("Clearsale_Total_Checkout: SendOrder: Compras fora do Brasil não integramos na CS.");
            return(3);
        }
        $wl->write_log("Clearsale_Total_Checkout:SendOrder: Compras no BR integramos na CS.");
        $cs_doc = $order->get_meta('cs_doc'); // # documento digitado no checkout, CPF ou CNPJ
        if (!$cs_doc) { // ops, de outra forma entao...
            $tmp_data = get_post_meta($order_id, 'cs_doc', true);
            //$wl->write_log("SendOrder: Pegando # doc: Pedido #=" . $order_id . " doc=" . $cs_doc);
            $wl->write_log("SendOrder: Pegando # doc de outra forma=" . print_r($tmp_data,true));
            $cs_doc = $tmp_data;
        }

        //https://docs.woocommerce.com/document/managing-orders/
        //https://docs.woocommerce.com/wc-apidocs/class-WC_Order.html

		// $posted_data abaixo como comentario
        $wl->write_log("SendOrder: Entrou: Pedido #=" . $order_id . " doc=" . $cs_doc);
        //$wl->write_log("posted_data=" . print_r($posted_data,true)); // se vem do thankyou nao temos este array

        $sess_id = get_post_meta($order_id, 'cs_sess_id', true); // Pegamos o sess_id do post_meta, já salvo qdo tinha session
        if (empty($sess_id))
            $sess_id = $order->get_meta('cs_sess_id');

        //https://stackoverflow.com/questions/39401393/how-to-get-woocommerce-order-details
        $order_data = $order->get_data(); // melhor desta forma
        $forma_pgto = $order_data['payment_method']; //qdo pelo modulo de PagSeguro vem = "pagseguro"
        // só que outros plugins NÃO o oficial, também vem com este nome, teremos que ver se o plugin oficial está instalado.
        // Quando implementar o jquery/ajax de outros plugins para cartão, temos que colocar aqui.
        $pagseguro_oficial = 0;
        // antes usava is_plugin_active
        if (in_array('woocommerce-pagseguro-oficial/woocommerce-pagseguro-oficial.php', (array) get_option('active_plugins', array()))) {
        	$pagseguro_oficial = 1; // está instalado o plugin do UOL
            $wl->write_log("Clearsale_Total_Checkout:SendOrder: Plugin do UOL instalado"); 
        }
        $cartao = array();
        $cartao = get_post_meta($order_id, 'cs_cartao', true); // vamos pegar dados do cartão salvo no ajax.
        if (empty($cartao))
            $cartao = $order->get_meta('cs_cartao');
        // Na V 2.0 não salvamos mais o pedido no ajax, apenas os dados de cartão (não completo) e aqui resgatamos se tiver!
        //$wl->write_log("Clearsale_Total_Checkout:SendOrder: cartao array=" . print_r($cartao,true));
        // se foi pagseguro está no postmeta senão esta em tabela cs_total_dadosextras.
        // temos que pegar o sessionid e acessar a tabela.
        if ($cartao == null) {
            $table_name = $wpdb->prefix.'cs_total_dadosextras';
            $wl->write_log("Clearsale_Total_Checkout:SendOrder: acessando dadosextras pela chave=" . $sess_id); 
            $mydata = $wpdb->get_row("SELECT * FROM $table_name WHERE chave = '$sess_id'", ARRAY_A);
            if ($wpdb->last_error) $wl->write_log("Erro:(" . $wpdb->last_error) . ")";
            //$wl->write_log("Clearsale_Total_Checkout:SendOrder: mylink=" . print_r($mydata,true)); 
            if ($mydata) {
                $cartao = unserialize($mydata['dados']);
                $wl->write_log("Clearsale_Total_Checkout:SendOrder:Lido do dadosextras: Pedido " . $order_id .
                 " com dados na tabela: modo=" . $cartao['modo'] . " nome=" . $cartao['owner'] . " bin=" . $cartao['bin']
                 . " end=" . $cartao['end'] . " installments=" . $cartao['installments'] . " doc=" . $cartao['document']);
            }
            if (!$cartao) $cartao = array();
        }

        if ($cartao == null || count($cartao) <= 0) {
            //aqui verificamos os métodos e seus post metas para pegar dados de cartão!!
            //em alguns casos não tem post meta nesta hook "woocommerce_checkout_order_processed" vai aparecer no Cs_total_thankyou
            $ret = $this->outrosMetodos($forma_pgto, $order_id, $cs_doc, $cartao, $wl, "SendOrder");
            if ($ret < 0) {// -2, -3 ... o número se refere ao documento clearsale-total-public-txt.php
                //nao tem post_meta aqui, vamos sair e ver no proximo hook o thankyou
                // compatibilidade com HPOS
                $cont = get_post_meta($order_id, 'cs_pedido_enviado', true);
                if (empty($cont))
                    $cont = $order->get_meta('cs_pedido_enviado');
                // null | numero | SIM
                $temp = $cont;
                $wl->write_log("Clearsale_Total_Checkout:SendOrder: cs_pedido_enviado=[" . $cont . "]");
                if ($cont != "SIM") {
                    $temp = (int)$cont;
                    $temp++;
                    $wl->write_log("Clearsale_Total_Checkout:SendOrder: contador=[" . $temp . "]");
                    // compatibilidade com HPOS
                    update_post_meta($order_id, 'cs_pedido_enviado', sanitize_text_field($temp));
                    $order->update_meta_data('cs_pedido_enviado', sanitize_text_field($temp));
                    $order->save();
                }
                $wl->write_log("Clearsale_Total_Checkout:SendOrder:(" . $temp . ") Pedido " . $order_id .
                 " método de cartão com post_meta, mas não chegou ainda, saindo...");
                return(2);
            } else $cartao = $ret;
        }

        if ($forma_pgto == "pagseguro" && $pagseguro_oficial) {
            $wl->write_log("Clearsale_Total_Checkout:SendOrder: Pedido " . $order_id . " feito pelo PagSeguro-UOL com " . $cartao['modo']);
        }

        //https://docs.woocommerce.com/wc-apidocs/class-WC_Customer.html
        //$customer = new WC_Customer( $order_id );  noop
        //$customer = $woocommerce->customer; 
        //$email = $customer->get_email();
        //Agora estamos sendo chamados na mudança de status do pedido e não achou customer no objeto woocommerce
        $a = get_userdata($order->get_user_id()); //$order_customer_id = $order_data['customer_id'];
        $email = $a->user_email;
        // $email pode ser vazio, se a compra for por visitante, vamos ter apenas no billing um email
        if (strlen($email)< 3) {
            $email = $order_data['billing']['email']; //ver dentro de Clearsale_Total_Api::Monta_pedido
            $wl->write_log("SendOrder: Pedido sem customer, é visitante, pegar e-mail de billing=" . $email);
        }

        $itemShip = $order->get_shipping_methods();
        $courrier = array();
        foreach($itemShip as $shipping_item) {
            $method_id = $shipping_item->get_method_id();
            $total = $shipping_item->get_total();
            $courrier[] = $method_id . "=" . $total;
        }
        //correios-pac=21.47
        //correios-pac=19.77
        //correios-sedex=21.47
        //local_pickup=0.00
        //$wl->write_log(" courrier=" . print_r($shipping_item,true));

        $items = $order->get_items();   // colecao de itens

        $cs_api = new Clearsale_Total_Api($this->plugin_name, $this->version);

        $pedido_json = $cs_api->Monta_pedido($sess_id, $cs_doc, $email, $order, $order_data, $courrier, $items, $cartao);

        $ret = $cs_api->Inclui_pedido($pedido_json);
        if (false === $ret) {
            // neste ponto não alteramos o status ClearSale do pedido, vai ficar com "Aguardando Pgto".
            $wl->write_log("SendOrder: Erro no retorno de Inclui_pedido");
            // compatibilidade com HPOS
            $cont = get_post_meta($order_id, 'cs_pedido_enviado', true);
            if (empty($cont))
                $cont = $order->get_meta('cs_pedido_enviado');
            $temp = $cont;
            if ($cont != "SIM") {
                $temp = (int)$cont;
                $temp++;
                // compatibilidade com HPOS
                update_post_meta($order_id, 'cs_pedido_enviado', sanitize_text_field($temp));
                $order->update_meta_data('cs_pedido_enviado', sanitize_text_field($temp));
                $order->save();
            }
            return(4);
        }
        //$ret = ('requestID'=>$requestID, 'code'=>$code, 'status'=>$status, 'score'=>$score)
        $requestID = $ret['requestID'];
        $pedido = $ret['code'];
        $status = $ret['status'];
        $score = $ret['score'];
        // se chegou até aqui é por depósito ou outro método
        $wl->write_log("Clearsale_Total_Checkout:SendOrder: Pedido " . $pedido . " inserido com sucesso, status:" . $status . " requestID:" . $requestID);

        // gravar status no pedido
		$nome_status = Clearsale_Total_Status::Status_nome($status);
		$note = __("ClearSale status: ", "clearsale-total");
		$note .= $status . " score: " . $score . "</br>Desc: " . $nome_status . "</br>requestID:" . $requestID;

		// Add the note
		$order->add_order_note($note);

        // compatibilidade com HPOS
        update_post_meta($order_id, 'cs_pedido_enviado', "SIM");
        $order->update_meta_data('cs_pedido_enviado', "SIM");
        $order->update_status('wc-cs-inanalisis');
		// Save the data
		$order->save();

        return(1);

    } // end of SendOrder

	/**
	* Metodo que vai identificar um plugin que grave post_meta com os dados de cartão, só o pagseguro fecha pedido e
    * monta tela pedindo dados do cartão então temos o # do pedido para relacionar. Os outros (ipag, Rede) pedem ao fechar
    * então no ajax não consigo saber de qual pedido são os dados, mas relacionamos com o session_id + 6 bytes do hash e 
    * consultamos tabela cs_total_dadosextra.
	* O PagSeguro da pagseguro não é visto aqui.
    *
    * @param    string  $forma_pgto
    * @param    string  $order_id
    * @param    string  $cs_doc
    * @param    array   $cartao
    * @param    object  $wl
    * @param    string  $onde   - quem chamou
	*/
    public function outrosMetodos($forma_pgto, $order_id, $cs_doc, $cartao, $wl, $onde)
    {
        $wl->write_log("Clearsale_Total_Checkout:outrosMetodos:" . $onde . " verificando post_meta de métodos para forma: "
          . $forma_pgto);
        $verifica = 1;
        if (!isset($cartao)) {
            if (count($cartao) > 1) $verifica = 0;
        }
        if ($verifica) {
            $order = new WC_Order($order_id);

            if ($forma_pgto == "rede_credit") { //3 = rede por MarcosAlexandre  2.1.1 (ver clearsale-total-public-txt.php)
                $c_bin = get_post_meta($order_id, '_wc_rede_transaction_bin', true); // 4 primeiros digitos
                if (empty($c_bin))
                    $c_bin = $order->get_meta('_wc_rede_transaction_bin');
                if ($c_bin == null) {
                    $wl->write_log("Clearsale_Total_Checkout:outrosMetodos: Pedido " . $order_id .
                    " feito pelo Rede por MarcosAlexandre, sem post_meta... ");
                    return(-3);
                }
                $c_exp = get_post_meta($order_id, '_wc_rede_transaction_expiration', true); // 01/2028
                if (empty($c_exp))
                    $c_exp = $order->get_meta('_wc_rede_transaction_expiration');
                $c_name = get_post_meta($order_id, '_wc_rede_transaction_holder', true);
                if (empty($c_name))
                    $c_name = $order->get_meta('_wc_rede_transaction_holder');
                $c_instal = (int)get_post_meta($order_id, '_wc_rede_transaction_installments', true); // 1 ou 2 ...
                if (empty($c_instal))
                    $c_instal = (int)$order->get_meta('_wc_rede_transaction_installments');
                $c_last4 = get_post_meta($order_id, '_wc_rede_transaction_last4', true);
                if (empty($c_last4))
                    $c_last4 = $order->get_meta('_wc_rede_transaction_last4');
                $star = "000000"; //"******";
                $wl->write_log("Clearsale_Total_Checkout:outrosMetodos: Pedido " . $order_id . " feito pelo MarcosAlexandre com "
                . $c_bin . "******" . $c_last4 . " nome:" . $c_name);
                $cartao = array(
                    'modo'			=>  'credito',
                    'numero'		=>	$c_bin . $star . $c_last4,
                    'bin'			=>	$c_bin,
                    'end'			=>	$c_last4,
                    'validity'		=>	$c_exp,
                    'installments'	=>	$c_instal,
                    'owner'			=>	$c_name,
                    'document'		=>	$cs_doc
                );
            } // end of rede_credit

            if ($forma_pgto == "ipag-gateway") { // 2 = ipag por Ipag V 2.1.4
                /*  _card_bin
                    _card_cpf
                    _card_end    4 ultimos digitos
                    _card_exp_month   01
                    _card_exp_year    28
                    _card_name
                    _card_type   = 2 e digitei um mastercard
                    _installment_number    = 1x - Total: R$ 23.00
                    _payment_method   = ipag-gateway
                    _payment_method_title    =  iPag - Cartão de crédito
                */
                $c_bin = get_post_meta($order_id, '_card_bin', true);
                if (empty($c_bin))
                    $c_bin = $order->get_meta('_card_bin'); //    4 primeiros digitos
                if ($c_bin == null) {
                    $wl->write_log("Clearsale_Total_Checkout:outrosMetodos: Pedido " . $order_id .
                    " feito pelo Ipag por Ipag, sem post_meta... ");
                    return(-2);
                }
                $c_expm = get_post_meta($order_id, '_card_exp_month', true); // 01
                if (empty($c_expm))
                    $c_expm = $order->get_meta('_card_exp_month');
                $c_expy = get_post_meta($order_id, '_card_exp_year', true);  // 28
                if (empty($c_expy))
                    $c_expy = $order->get_meta('_card_exp_year');
                if (strlen($c_expy)<4) $c_expy = "20" . $c_expy; // em 80 anos temos um erro!!
                $c_exp = $c_expm . "/" . $c_expy;
                $c_name = get_post_meta($order_id, '_card_name', true);
                if (empty($c_name))
                    $c_name = $order->get_meta('_card_name');
                //_installment_number =2x - Total: R$ 192.37
                $c_instal = (int) substr(get_post_meta($order_id, '_installment_number', true),0,1);
                if (empty($c_instal))
                    $c_instal = (int) substr($order->get_meta('_installment_number'),0,1);
                $c_last4 = get_post_meta($order_id, '_card_end', true);
                if (empty($c_last4))
                    $c_last4 = $order->get_meta('_card_end');
                $star = "000000"; //"******";
                $wl->write_log("Clearsale_Total_Checkout:outrosMetodos: Pedido " . $order_id . " feito pelo Ipag por Ipag com "
                . $c_bin . "******" . $c_last4 . " nome:" . $c_name . " parcelas=" . $c_instal);
                $cartao = array(
                    'modo'			=>  'credito',
                    'numero'		=>	$c_bin . $star . $c_last4,
                    'bin'			=>	$c_bin,
                    'end'			=>	$c_last4,
                    'validity'		=>	$c_exp,
                    'installments'	=>	$c_instal,
                    'owner'			=>	$c_name,
                    'document'		=>	$cs_doc
                );

            } // end of ipag

            if ($forma_pgto == "loja5_woo_cielo_webservice") { //5
                //Integração de Pagamento ao Cielo API 3.0.Versão 4.0 | Por Loja5.com.br
                $dados_cielo = get_post_meta($order_id, '_dados_cielo_api', true); // todos os dados da transação
                if (empty($dados_cielo))
                    $dados_cielo = $order->get_meta('_dados_cielo_api');
                //$wl->write_log("Clearsale_Total_Checkout:outrosMetodos: post_meta=" . print_r($dados_cielo,true));
                //$dados_cielo_api = unserialize($dados_cielo);
                if ($dados_cielo == null) {
                    $wl->write_log("Clearsale_Total_Checkout:outrosMetodos: Pedido " . $order_id .
                    " feito por Cielo API 3.0.Versão 4.0 | Por Loja5.com.br, sem postmeta... ");
                    return(-5);
                }
                $payment = $dados_cielo['Payment'];
                $customer = $dados_cielo['Customer'];

                $identity = $customer['Identity'];      // # documento, ex. CPF
                $type = $customer['IdentityType'];      // só achei CPF
                $creditcard = $payment['CreditCard'];
                $cardnum = $creditcard['CardNumber'];    //#=516292******3415
                $holder = $creditcard['Holder'];
                $validity = $creditcard['ExpirationDate'];  // 02/2030
                $installments = $payment['Installments'];

                $wl->write_log("Clearsale_Total_Checkout:outrosMetodos: Pedido " . $order_id .
                 " feito pelo Loja5-Cielo API 3.0.Versão 4.0 com " . $cardnum . " nome:" . $holder);
                $cartao = array(
                    'modo'			=>  'credito',
                    'numero'		=>	$cardnum,
                    'bin'			=>	substr($cardnum, 0, 6),
                    'end'			=>	substr($cardnum, -4),
                    'validity'		=>	$validity,
                    'installments'	=>	$installments,
                    'owner'			=>	$holder,
                    'document'		=>	$identity
                );
            } // end of loja5_woo_cielo_webservice

            if ($forma_pgto == "loja5_woo_novo_erede" || $forma_pgto == "loja5_woo_novo_erede_debito") { //7
                //Integração e.Rede API de Pagamentos para Cartões. V 3.0 | Por Loja5.com.br
                $dados_rede = get_post_meta($order_id, '_dados_erede_api', true); // todos os dados da transação
                if (empty($dados_rede))
                    $dados_rede = $order->get_meta('_dados_erede_api');
                //$wl->write_log("Clearsale_Total_Checkout:outrosMetodos: post_meta=" . print_r($dados_rede,true));
                //$dados_cielo_api = unserialize($dados_cielo);
                if ($dados_rede == null) {
                    $wl->write_log("Clearsale_Total_Checkout:outrosMetodos: Pedido " . $order_id .
                    " feito por Integração e.Rede API de Pagtos para Crédito|Débito. Por Loja5.com.br, sem postmeta... ");
                    return(-7);
                }
                // em $dados_rede não tem muitos dados, tem em outros postmetas que pegaremos abaixo

                $identity = get_post_meta($order_id, 'cs_doc', true);
                if (empty($identity))
                    $identity = $order->get_meta('cs_doc'); // # documento, ex. CPF
                $cardnum = get_post_meta($order_id, '_card_bin', true);
                if (empty($cardnum))
                    $cardnum = $order->get_meta('_card_bin'); //#=516292******3415
                $holder = get_post_meta($order_id, '_card_name', true);
                if (empty($holder))
                    $holder = $order->get_meta('_card_name');
                $validity = get_post_meta($order_id, '_card_month', true) . "/" . get_post_meta($order_id, '_card_year', true);  // 02/2030
                if (empty($validity))
                    $validity = $order->get_meta('_card_month') . "/" . $order->get_meta('_card_year');  // 02/2030
                $installments = (int)get_post_meta($order_id, '_installment_number', true);
                if (empty($installments))
                    $installments = (int)$order->get_meta('_installment_number');

                $wl->write_log("Clearsale_Total_Checkout:outrosMetodos: Pedido " . $order_id .
                 " feito pelo Loja5-e.Rede API de Pagtos para Crédito|Débito - com " . $forma_pgto . " #=" . $cardnum . " nome:" . $holder);
                $modo = "credito";
                if ($forma_pgto == "loja5_woo_novo_erede_debito") {
                    $modo = "debito";
                }

                $cartao = array(
                    'modo'			=>  $modo,
                    'numero'		=>	$cardnum,
                    'bin'			=>	substr($cardnum, 0, 6),
                    'end'			=>	substr($cardnum, -4),
                    'validity'		=>	$validity,
                    'installments'	=>	$installments,
                    'owner'			=>	$holder,
                    'document'		=>	$identity
                );
            } // end of loja5_woo_novo_erede

            if (stristr($forma_pgto, "woo-pagarme-payments") || $forma_pgto == "Pagar.me") { // 9
                // antes woo-pagarme-payments, depois Pagar.me agora woo-pagarme-payments-credit_card
                //Integração de Pagamento Pagar-me pela Pagar-me Versão 2.0.14, agora 3.1.1

                $body = get_post_meta($order_id, '_pagarme_response_data', true); // todos os dados da transação
                if (empty($body))
                    $body = $order->get_meta('_pagarme_response_data'); // todos os dados da transação
                //$wl->write_log("Clearsale_Total_Checkout:outrosMetodos: forma=" . $forma_pgto . " post_meta=" . print_r($body,true));
                //$dados_p_response_data = unserialize($p_response_data);
                $pay_metodo = get_post_meta($order_id, '_pagarme_payment_method', true);
                if (empty($pay_metodo))
                    $pay_metodo = $order->get_meta('_pagarme_payment_method');
                if ("pix" === $pay_metodo){
                    $cartao=array();
                    return($cartao);
                }
                if ("billet" === $pay_metodo){
                    $cartao=array();
                    return($cartao);
                }
                if ($body == null) {
                    $wl->write_log("Clearsale_Total_Checkout:outrosMetodos: Pedido " . $order_id .
                    " feito por Pagar-me da Pagar-me, sem postmeta... ");
                    return(-9);
                }
                $p_response_data = json_decode($body, true);
                $charges = $p_response_data['charges'];
                $transa = $charges[0]['transactions'];
                $cardData = $transa[0]['cardData'];
                $postData = $transa[0]['postData'];
                if (isset($postData['card'])) {
                    $card = $postData['card'];
                } else {
                    $cartao=array();
                    return($cartao);
                }
                //$wl->write_log("Clearsale_Total_Checkout:outrosMetodos: pagar-me: transa=" . print_r($transa,true));
                /*
                "cardData":{
                  "id":null,
                  "pagarmeId":"card_rZ7mGA1HeHgG8x6g",
                  "ownerId":null,
                  "type":"credit_card",
                  "ownerName":"alfredo teste",
                  "firstSixDigits":"423564",
                  "lastFourDigits":"5682",
                  "brand":"Visa",
                  "createdAt":"2022-11-02 00:03:46"
                },
                "card":{
                     "id":"card_rZ7mGA1HeHgG8x6g",
                     "last_four_digits":"5682",
                     "brand":"Visa",
                     "holder_name":"alfredo teste",
                     "exp_month":1,
                     "exp_year":2035,
                */
                $installments = $transa[0]['installments'];
                $holder = $cardData['ownerName'];
                $bin = $cardData['firstSixDigits'];
                $end = $cardData['lastFourDigits'];
                if (!$bin || !$end) {
                    $cartao=array();
                    return($cartao);
                }
                $validity = $card['exp_month'] . "/" . $card['exp_year'];
                $identity = $cs_doc;      // # documento, ex. CPF
                $cartao = array(
                    'modo'			=>  'credito',
                    'numero'		=>	$bin . "xxxxxx" . $end,
                    'bin'			=>	$bin,
                    'end'			=>	$end,
                    'validity'		=>	$validity,
                    'installments'	=>	(int)$installments,
                    'owner'			=>	$holder,
                    'document'		=>	$identity
                );
            } // end of pagar-me da pagar-me

        } // end of $cartao vazio
        return($cartao);
    } // end of outrosMetodos


    //Cs_finalizar_checkout
	/**
	* Metodo que vai ser chamado quando clicado no botão finalizar. Vamos colocar um pequeno javascript,
	* na verdade um declaração de variável para testar, na rotina de FingerPrint, a existência.
    *
	*/
    public function Cs_finalizar_checkout()
    {

        echo "<script type='text/javascript'>tonocheckout=1;  </script>";

    } // end of Cs_finalizar_checkout

} // end of class
