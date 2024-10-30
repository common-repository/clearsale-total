<?php

/**
 * The admin-specific functionality of the plugin.
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two hooks for how to enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Clearsale_Total
 * @subpackage Clearsale_Total/admin
 * @author     Letti Tecnologia <contato@letti.com.br>
 * @link       https://letti.com.br/wordpress
 * @since      "1.0.0"
 *
 */
class Clearsale_Total_Admin
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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->wp_cs_options = get_option($this->plugin_name);
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    "1.0.0"
	 */
	public function cs_total_enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Clearsale_Total_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Clearsale_Total_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/clearsale-total-admin.css', array(), $this->version, 'all');

	} // end of cs_total_enqueue_styles

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    "1.0.0"
	 */
	public function cs_total_enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Clearsale_Total_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Clearsale_Total_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/clearsale-total-admin.js', array('jquery'), $this->version, false);

	} // end of cs_total_enqueue_scripts


	/**
 	* Register the administration menu for this plugin into the WordPress Dashboard menu.
 	*
 	* @since    1.0.0
 	*/

	public function cs_total_add_plugin_admin_menu()
	{
    /*
     * Add a settings page for this plugin to the Settings menu.
     *
     * NOTE:  Alternative menu locations are available via WordPress administration menu functions.
     *
     *        Administration Menus: http://codex.wordpress.org/Administration_Menus
     *
     */
		$tmp = __('WooCommerce - ClearSale Total (for Brazil) - Configurations', 'clearsale-total');
		$tmp .= " - V " . CLEARSALE_TOTAL_VERSION;
		add_options_page($tmp, 'ClearSale Total', 'manage_options', 'clearsale-total', array($this, 'cs_total_display_plugin_setup_page')
    	);
	}

	/**
 	* Add settings action link to the plugins page.
 	*
 	* @since    1.0.0
 	*/

	public function cs_total_add_action_links($links)
	{
    /*
    *  Documentation : https://codex.wordpress.org/Plugin_API/Filter_Reference/plugin_action_links_(plugin_file_name)
    */
   		$settings_link = array(
    		'<a href="' . admin_url('options-general.php?page=clearsale-total') . '">' . __('Settings', 'clearsale-total') . '</a>',
   			);
   		return array_merge($settings_link, $links);

	} // end of cs_total_add_action_links

	/**
 	* Render the settings page for this plugin.
 	*
 	* @since    1.0.0
 	*/

	public function cs_total_display_plugin_setup_page()
	{
    	include_once('partials/clearsale-total-admin-display.php');
	}

	/**
	*
	* Valida os inputs
	*
	**/
	public function validate($input)
	{
    	// Validate all inputs        
    	$valid = array();

	//	$valid['cleanup'] = (isset($input['cleanup']) && !empty($input['cleanup'])) ? 1 : 0;
   
		// modo: producao=0 homologacao=1
		//ClearSale Inputs
		if ( !isset($input['modo']) ) $modo = 1 ; else $modo = esc_textarea($input['modo']);
		if ( !isset($input['cancel_order']) ) $cancel = 0; else $cancel = 1;

		if ( !isset($input['status'])) $status_of_aproved = "wc-processing"; else $status_of_aproved = esc_textarea($input['status']);

    	$valid['modo'] = $modo;
		$valid['login'] = esc_textarea($input['login']);
		$valid['password'] = esc_textarea($input['password']);
		$valid['finger'] = esc_textarea($input['finger']);
		$valid['cancel_order'] = $cancel; //esc_textarea($input['cancel_order']);
		$valid['status_of_aproved'] = $status_of_aproved;
		$valid['debug'] = esc_textarea($input['debug']);

    	return $valid;
 	} // end of validate

	/**
	 * Inserir o hook para validação da tela de admin setup
	 */
	public function cs_total_options_update()
	{
		register_setting($this->plugin_name, $this->plugin_name, array($this, 'validate'));
	}

	/**
	 * Add custom columns in order grid of woocommerce
	 */
	public function cs_total_add_grid_columns($columns)
	{
		$reordered_columns = array();
		
		// Inserting columns to a specific location
		foreach($columns as $key => $column) {
			$reordered_columns[$key] = $column;
			if ($key ==  'order_status') {
				// Inserting after "Status" column
				$reordered_columns['cs_status'] = __('ClearSale Status', 'clearsale-total');
			}
		}
		return $reordered_columns;
	} // end of cs_total_add_grid_columns

	/**
	 * Adding custom fields meta data for each new column
	 * https://stackoverflow.com/questions/49919915/add-a-woocommerce-orders-list-column-and-value
	 * add_action( 'manage_shop_order_posts_custom_column' , 'cs_add_grid_content', 20, 2 );
	 * 
	 * @param	$column	string - nome da coluna do grid
	 * @param	$post_id
	*/
	public function cs_total_add_grid_content($column, $post_id)
	{
    	switch ($column) {
    	    case 'cs_status' :
				// Get custom post meta data
				//https://codex.wordpress.org/Function_Reference/get_comments
				//https://www.szabogabor.net/how-to-get-orders-comments-in-woocommerce/
				$args = array(
					//'status' => 'hold',
					//'number' => '1', se for só a última ele deixa em branco se não for a da ClearSale
					'post_id' => $post_id, // use post_id, not post_ID
					'approved' => 'approve',
					'type' => ''
				);

				$order = new WC_Order($post_id);
				$enviado = get_post_meta($post_id, 'cs_pedido_enviado', true);
				if (empty($enviado))
					$enviado = $order->get_meta('cs_pedido_enviado');
				if ($enviado != 'SIM' && $enviado >= 1) {
					esc_html_e('Not integrated yet.', 'clearsale-total');
				}

				remove_filter('comments_clauses', array('WC_Comments', 'exclude_order_comments'));
				$comments = get_comments($args); // ele lista em ordem descendente de data, achando a 1ra da CS paramos, pois será a última.
				add_filter('comments_clauses', array('WC_Comments', 'exclude_order_comments'));

				foreach($comments as $comment) :
					//$comment->comment_author = autor
					//Status da ClearSale: FRD - pegar apenas FRD  Ou assim-> ClearSale status: FRD score: 99.99
					$apt = stristr($comment->comment_content, "clearsale:");
					$status = substr($apt, 11 ,3);
					if ($status == "NVO") $status="Em Análise";
					if ($status) {
						echo esc_html(Clearsale_Total_Status::statusShortNames($status));
						break;
					}
					else { // vazio
						$apt = stristr($comment->comment_content, "status:");
						$status = substr($apt, 8 ,3);
						if ($status == "NVO") $status="Em Análise";
						//if ($status == "EAP") $status="Esperando Aprovação do Pagamento";
						if ($status) {
							echo esc_html(Clearsale_Total_Status::statusShortNames($status));
							break;
						}
					}
				endforeach;
            break;
		}
	} // end of cs_total_add_grid_content

	/**
	 * Adding screen for order resent
	 * Ps. must be static
	 * @param	$order
	 * if WC >= 8.2 this is a order, otherwise is a post
	*/
	static function cs_total_render_order_resent($p_order)
	{
		// chamar $p_order->ID tem warning, mas se for $p_order->get_id() dá erro qdo WC < 8.2 
		$pedido  = $p_order->ID;
		if ( ! is_a($p_order, 'WC_Order') ) {
			$enviado = get_post_meta($pedido, 'cs_pedido_enviado', true);
		} else {
			$enviado = $p_order->get_meta('cs_pedido_enviado');
		}

		if (empty($pedido)) {
			$tmp = "<p>" . __("No order found.", 'clearsale-total') . "</p>";
			echo wp_kses($tmp, array('p' => array()));
			return;
		}
		$cs_status = new Clearsale_Total_Status('clearsale-total', CLEARSALE_TOTAL_VERSION);
		$statusCancel = $cs_status->getStatusOnNotes($pedido, "Cancel");
		$botao = null;
		if ($enviado == "SIM") {
			$texto = sprintf(__("This order (%s) has already been integrated into ClearSale!", "clearsale-total"), $pedido);
		} else {
			if ($enviado >= 1) {
				if ($enviado <= 3) {
					$texto = sprintf(__("This order (%s) has NOT been integrated into ClearSale!", "clearsale-total"), $pedido);
					$botao = true;
				} else { // mais de 3 não põe botão, põe o email do suporte.
					$texto = sprintf(__("This order (%s) failed to be integrated in 3 attempts, contact support@clear.sale!", "clearsale-total"), $pedido);
				}
			} else {
				if ($statusCancel) {
					$texto = sprintf(__("This order (%s) was canceled!", "clearsale-total"), $pedido);
				} else $texto = sprintf(__("This order (%s) is awaiting payment approval for Clear Sale risk analysis!", "clearsale-total"), $pedido);
			}
		}
		//<input type="hidden" name="cs_total_mbox_nonce" value="<?php echo wp_create_nonce('cs_total_mbox_nonce');
		?>
		<div class="wrapper">
		<p class="buttons">
		<?php echo '<span>' . esc_html($texto) . '</span>';?>
		<?php if ($botao): ?>
			<script>
			jQuery(document).ready(function() {
	        	jQuery('.nonce_mbox').val(js_global.cs_total_mbox_nonce);
			})
			</script>
			<input type="hidden" name="cs_total_mbox_nonce" class="nonce_mbox" value="a123" />
			<input type="hidden" name="postid" id="postid" value="<?php echo esc_html($pedido);?>" />
			<button class="button" id="submit-cs-tot-mbox" type="submit"><?php echo esc_html__("Order Resend", 'clearsale-total')?></button>
		<?php endif;?>
		</p>
		</div>

		<?php
		
	} // end of cs_total_render_order_resent

	/**
	 * Adding action for meta box
	 * add_action('add_meta_boxes', 'cs_total_order_meta_boxes', 10, 2);
	 * 
	 * @param	$post
	*/
	public function cs_total_order_meta_boxes($post_type, $post)
	{
		//'Clearsale_Total_Admin::cs_total_render_order_resent',
		// 'shop_order', erro no Woo 8.2.1
		add_meta_box( 
			'clearsale-reenvio-pedido',
			__('ClearSale - Order Status', 'clearsale-total'),
			[$this, 'cs_total_render_order_resent'],
			['shop_order', 'woocommerce_page_wc-orders'],
			'normal',
			'default'
		);
	}


} // end of class
