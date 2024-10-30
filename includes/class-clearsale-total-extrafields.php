<?php
/**
 * The extra fields plugin class
 * Classe para tratar campos adicionais no cadastro
 *
 */

/**
 * Classe que trata de campos adicionais no cadastro do woocommerce
 * 
 * 
 * @category   Clearsale
 * @package    Clearsale_Total
 * @subpackage Clearsale_Total/includes
 * @link       https://letti.com.br/wordpress
 * @version    "1.0.0"
 * @author     Letti Tecnologia <contato@letti.com.br>
 * @date       02/09/2019
 * @copyright  1996-2020 - Letti Tecnologia
 */
class Clearsale_Total_Extrafields
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
	 * @param    string    $plugin_name     The name of this plugin.
	 * @param    string    $version         The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
    {
        global $wpdb;

		$this->plugin_name = $plugin_name;
		$this->version = $version;
        $this->wp_cs_options = get_option($this->plugin_name); //get mysql data

        //$this->modo = $this->wp_cs_options['modo'];
	} // end of construct

// https://developer.wordpress.org/plugins/security/securing-input/

    /**
    * Add the field to the checkout page
    *
    * @since    "1.0.0"
    */
    //add_action('woocommerce_after_order_notes', 'cs_customise_checkout_field');
    function cs_customise_checkout_field($checkout)
    {
        $wl = new Clearsale_Total_Log($this->plugin_name, $this->version);
        $b_country = $s_country = "";
        if (isset($_POST['billing_country'])) $b_country = sanitize_text_field(wp_unslash($_POST['billing_country']));
        if (isset($_POST['shipping_country'])) $s_country = sanitize_text_field(wp_unslash($_POST['shipping_country']));
        $wl->write_log("Cs_total_extrafield: checkout_field: billing_country=" . $b_country . " shipping_country=" . $s_country);

	    echo '<div id="customise_checkout_field"><h3>' . esc_html_e('Document Number', 'clearsale-total') . '</h3>';
	    woocommerce_form_field('cs_field_doc', array(
		    'type' => 'text',
		    'class' => array(
    			'cs-field-class form-row-wide'
	    	) ,
		    'label' => __('Cpf/Cnpj (Valid only in Brazil)', 'clearsale-total') ,
		    'placeholder' => __('Only numbers', 'clearsale-total') ,
		    'required' => false, // true alterado em 19/7/21 para funcionar a internacionalização
	    ) , $checkout->get_value('cs_field_doc'));
	    echo '</div>';
    }

    /**
     * Valida campos extras
     * 
     * @since    "1.0.0"
     */
    //add_action('woocommerce_checkout_process', 'cs_customise_checkout_field_process');
    function cs_customise_checkout_field_process()
    {
        $wl = new Clearsale_Total_Log($this->plugin_name, $this->version);
        $b_country = $s_country = "";
        if (isset($_POST['billing_country'])) $b_country = sanitize_text_field(wp_unslash($_POST['billing_country']));
        if (isset($_POST['shipping_country'])) $s_country = sanitize_text_field(wp_unslash($_POST['shipping_country']));
        $wl->write_log("Cs_total_extrafield: field_process: billing_country=" . $b_country . " shipping_country=" . $s_country);

        if ($b_country == "BR") { // && $s_country == "BR") {
            $wl->write_log("Cs_total_extrafield: field_process: Compras no BR verificamos CPF/CNPJ.");
            // if the field is set, if not then show an error message.
            $doc = sanitize_text_field(wp_unslash($_POST['cs_field_doc']));
            if (!$doc) {
                wc_add_notice(__('Please enter value in Document Number.', 'clearsale-total'), 'error');
            }
            if (strlen($doc) <= 11) {
                if (!$this->validaCPF($doc)) {
                    wc_add_notice(__('Invalid CPF Number.', 'clearsale-total'), 'error');
                }
            } elseif (!$this->validaCNPJ($doc)) {
                wc_add_notice(__('Invalid CNPJ Number.', 'clearsale-total'), 'error');
            }
        }
    }


    /**
    * Update value of field
    *
    * @since    "1.0.0"
    */
    //add_action('woocommerce_checkout_update_order_meta', 'cs_customise_checkout_field_update_order_meta');
    function cs_customise_checkout_field_update_order_meta($order_id)
    {
        $order = new WC_Order($order_id);

        $wl = new Clearsale_Total_Log($this->plugin_name, $this->version);
        $b_country = $s_country = "";
        if (isset($_POST['billing_country'])) $b_country = sanitize_text_field(wp_unslash($_POST['billing_country']));
        if (isset($_POST['shipping_country'])) $s_country = sanitize_text_field(wp_unslash($_POST['shipping_country']));
        $wl->write_log("Cs_total_extrafield: update_order_meta: billing_country=" . $b_country . " shipping_country=" . $s_country);

        if ($b_country != "BR") { // || $s_country != "BR") {
            $wl->write_log("Cs_total_extrafield: update_order_meta: Compras fora do Brasil não avaliamos CPF/CNPJ.");
            return;
        }

        $wl->write_log("Cs_total_extrafield: update_order_meta: Compras no BR salvamos CPF/CNPJ.");
        $tmp="";
        if (isset($_POST['cs_field_doc'])) {
            $tmp = sanitize_text_field(wp_unslash($_POST['cs_field_doc']));
        }
        $b_cpf = $b_cnpj = "";
        if (isset($_POST['billing_cpf'])) $b_cpf = sanitize_text_field(wp_unslash($_POST['billing_cpf']));
        if (isset($_POST['billing_cnpj'])) $b_cnpj = sanitize_text_field(wp_unslash($_POST['billing_cnpj']));
        $wl->write_log("Cs_total_extrafield: order_id=" . $order_id . " cs_field_doc=" . $tmp . " billing_cpf=" . $b_cpf . " billing_cnpj=" . $b_cnpj);

        if (!empty($_POST['cs_field_doc'])) { // grava no postmeta com nome cs_doc
            //update_post_meta($order_id, 'cs_doc', sanitize_text_field(wp_unslash($_POST['cs_field_doc'])));
            // compatibilidade com HPOS
            $order->update_meta_data('cs_doc', sanitize_text_field(wp_unslash($_POST['cs_field_doc'])));
            $order->save();
        } else {
            // Vamos ver se tem os campos do woo-extra-fields-bra
            // no post temos: $_POST['billing_cpf'] e $_POST['billing_cnpj'] e no meta são:
            //$order->get_meta( '_billing_cpf' )  e $order->get_meta( '_billing_cnpj' )
            //
            //poderíamos verificar o nosso flag woo_extra_fields_bra, mas se tiver outro pegamos também
            $tmp_cpf = sanitize_text_field(wp_unslash($_POST['billing_cpf']));
            $tmp_cnpj = sanitize_text_field(wp_unslash($_POST['billing_cnpj']));
            if (strlen($tmp_cpf) < 5) {
                $n_doc = $tmp_cnpj;
            } else {
                $n_doc = $tmp_cpf;
            }
            if (strlen($n_doc) > 6) {
                //update_post_meta($order_id, 'cs_doc', sanitize_text_field($n_doc));
                // compatibilidade com HPOS
                $order->update_meta_data('cs_doc', sanitize_text_field($n_doc));
                $order->save();
            }
        }
    }

    /**
     * Metodo para validar CPF
     * @since   "1.0.0"
     * @param   string  cpf
     * @return  boolean true|false
     */
    function validaCPF($cpf = null)
    {

        // Verifica se um número foi informado
        if (empty($cpf)) {
                return false;
        }
        // Elimina possivel mascara
        $cpf = preg_replace("/[^0-9]/", "", $cpf);
        $cpf = str_pad($cpf, 11, '0', STR_PAD_LEFT);
        // Verifica se o numero de digitos informados é igual a 11
        if (strlen($cpf) != 11) {
                return false;
        }
        // Verifica se nenhuma das sequências invalidas abaixo
        // foi digitada. Caso afirmativo, retorna falso
        else if ($cpf == '00000000000' || $cpf == '11111111111' || $cpf == '22222222222' ||
                $cpf == '33333333333' ||  $cpf == '44444444444' || $cpf == '55555555555' ||
                $cpf == '66666666666' ||  $cpf == '77777777777' || $cpf == '88888888888' ||
                $cpf == '99999999999') {
                return false;
         } else {
                // Calcula os digitos verificadores para verificar se o
                // CPF é válido
                for ($t = 9; $t < 11; $t++) {

                        for ($d = 0, $c = 0; $c < $t; $c++) {
                                $d += $cpf[$c] * (($t + 1) - $c);
                        }
                        $d = ((10 * $d) % 11) % 10;
                        if ($cpf[$c] != $d) {
                                return false;
                        }
                }
                return true;
        }
    } // end of validaCPF

    /**
     * Metodo para validar CNPJ
     * @since   "1.0.0"
     * @param   string  cnpj
     * @return  boolean true|false
    */
    function validaCNPJ($cnpj = null)
    {

        // Verifica se um número foi informado
        if(empty($cnpj)) {
            return false;
        }
        // Elimina possivel mascara
        $cnpj = preg_replace("/[^0-9]/", "", $cnpj);
        $cnpj = str_pad($cnpj, 14, '0', STR_PAD_LEFT);
        
        // Verifica se o numero de digitos informados é igual a 11 
        if (strlen($cnpj) != 14) {
            return false;
        }
        
        // Verifica se nenhuma das sequências invalidas abaixo 
        // foi digitada. Caso afirmativo, retorna falso
        else if ($cnpj == '00000000000000' || $cnpj == '11111111111111' || $cnpj == '22222222222222' || 
            $cnpj == '33333333333333' || $cnpj == '44444444444444' || $cnpj == '55555555555555' || 
            $cnpj == '66666666666666' || $cnpj == '77777777777777' || $cnpj == '88888888888888' || 
            $cnpj == '99999999999999') {
            return false;
         } else {   
         // Calcula os digitos verificadores para verificar se o CNPJ é válido
            $j = 5;
            $k = 6;
            $soma1 = "";
            $soma2 = "";
    
            for ($i = 0; $i < 13; $i++) {
                $j = $j == 1 ? 9 : $j;
                $k = $k == 1 ? 9 : $k;
                $soma2 += ($cnpj[$i] * $k);
                if ($i < 12) {
                    $soma1 += ($cnpj[$i] * $j);
                }
                $k--;
                $j--;
            }

            $digito1 = $soma1 % 11 < 2 ? 0 : 11 - $soma1 % 11;
            $digito2 = $soma2 % 11 < 2 ? 0 : 11 - $soma2 % 11;
    
            return (($cnpj[12] == $digito1) and ($cnpj[13] == $digito2));
        }
    } // end of validaCNPJ

} // end of class
