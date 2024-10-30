<?php
/**
 * The core plugin class
 * Classe principal do plugin
 *
 */

/**
 * The core plugin class.
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * Classe principal, reponsável pela definição de internalionalização, carga dos hooks de admin
 * e publicos, css, java scripts e ajax com nonce. Aqui é verificada as dependências para funcionamento.
 * 
 * 
 * @category   Clearsale
 * @package    Clearsale_Total
 * @subpackage Clearsale_Total/includes
 * @link       https://letti.com.br/wordpress
 * @version    "1.0.0"
 * @author     Letti Tecnologia <contato@letti.com.br>
 * @date       20/07/2019
 * @copyright  1996-2021 - Letti Tecnologia
 */
class Clearsale_Total
{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin
	 *
	 * @since    "1.0.0"
	 * @access   protected
	 * @var      Clearsale_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Plugin main file.
	 *
	 * @access protected
	 * @var string plugin_basename( __FILE__ )
	 */
	protected $plugin_file;
	
	/**
	 * flag that tells if the plugin is installed or not. 
	 *
	 * @access protected
	 * @var boolean	true|false
	 */
	protected	$woo_extra_fields_bra;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 * @param string $plugin_file
	 */
	public function __construct($plugin_file)
	{
		if (defined('CLEARSALE_TOTAL_VERSION')) {
			$this->version = CLEARSALE_TOTAL_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'clearsale-total';
		$this->plugin_file = $plugin_file; //plugin_basename( __FILE__ )

//		if ( ! function_exists('is_plugin_active')) {
//			include_once(ABSPATH . 'wp-admin/includes/plugin.php');
//		}

		$this->woo_extra_fields_bra = 0;

		// vamos ver se este plugin esta instalado, se sim não pedimos o CPF na loja, já é feito por ele
		if ($this->cst_is_plugin_active('woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php')) {

        	$this->woo_extra_fields_bra = 1; // está instalado o plugin do Claudio Sanches

		}

		$this->load_dependencies();
		$this->checkVersion();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

		// New order status AFTER woo 2.2
		add_action('init', array(Clearsale_Total_Status::class, 'register_my_new_order_statuses'));
		add_filter('wc_order_statuses', array(Clearsale_Total_Status::class, 'add_new_order_status'));

	} // end of construct

	function cst_is_plugin_active($plugin)
	{
		return in_array($plugin, (array) get_option( 'active_plugins', array() ));
	}

    /**
    * Verifica as versões do PHP, WordPress e Woocommerce.
    **/
    private function checkVersion()
	{
		// Check plugin requirements before loading plugin.
		$this_plugin_checks = new Clearsale_Total_Requirements( $this->get_plugin_name(), $this->get_version(), $this->plugin_file, array(
			'PHP'        => '5.6',	// 5.3.3
			'WordPress'  => '5.0',		// 4.2
			'Woocommerce' => '3.4.1',	//'3.4.1',
			'Extensions' => array('curl'),
		) );
		if ( $this_plugin_checks->pass() === false ) {
			$this_plugin_checks->halt();
			return;
		}
    } // end of checkVersion

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Clearsale_Total_Loader. Orchestrates the hooks of the plugin.
	 * - Clearsale_Total_i18n. Defines internationalization functionality.
	 * - Clearsale_Total_Admin. Defines all hooks for the admin area.
	 * - Clearsale_Total_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{

		/**
		 * The class responsible for defining requirements of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-clearsale-total-requirements.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-clearsale-total-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-clearsale-total-i18n.php';

		/**
		 * The class responsible for defining fingerprint and mapper of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-clearsale-total-footer.php';

		/**
		 * The class responsible for defining ajax of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-clearsale-total-ajax.php';

		/**
		 * The class responsible for defining checkout of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-clearsale-total-checkout.php';

		/**
		 * The class responsible for defining log errors of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-clearsale-total-log.php';

		/**
		 * The class responsible for defining Api of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-clearsale-total-api.php';

		/**
		 * The class responsible for defining status of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-clearsale-total-status.php';

		/**
		 * The class responsible for defining account of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-clearsale-total-account.php';

		/**
		 * The class responsible for defining new extra fields of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-clearsale-total-extrafields.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-clearsale-total-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-clearsale-total-public.php';

		$this->loader = new Clearsale_Total_Loader();

	} // end of load_dependencies

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Clearsale_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale()
	{

		$plugin_i18n = new Clearsale_Total_i18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');

	}

	/**
	 * Register all of the hooks related to the admin area functionality of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{

		$plugin_admin = new Clearsale_Total_Admin($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'cs_total_enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'cs_total_enqueue_scripts');

		// Save/Update our plugin options
		$this->loader->add_action('admin_init', $plugin_admin, 'cs_total_options_update');

		// Add menu item
		$this->loader->add_action('admin_menu', $plugin_admin, 'cs_total_add_plugin_admin_menu');

		// Add Settings link to the plugin
		$plugin_basename = plugin_basename(plugin_dir_path( __DIR__ ) . $this->plugin_name . '.php');
		$this->loader->add_filter('plugin_action_links_' . $plugin_basename, $plugin_admin, 'cs_total_add_action_links');

		// Add status in Grid
		//add_filter( 'manage_edit-shop_order_columns', 'cs_add_grid_columns', 20 );
		$this->loader->add_filter('manage_edit-shop_order_columns', $plugin_admin, 'cs_total_add_grid_columns');
		//add_action( 'manage_shop_order_posts_custom_column' , 'cs_add_grid_content', 20, 2 );
		$this->loader->add_filter('manage_shop_order_posts_custom_column', $plugin_admin, 'cs_total_add_grid_content', 20, 2);

		// Add metabox for order resent
		$this->loader->add_action('add_meta_boxes', $plugin_admin, 'cs_total_order_meta_boxes', 10, 2);

		// Add method to add fingerprint and mapper
		$plugin_footer = new Clearsale_Total_Footer($this->get_plugin_name(), $this->get_version());
		$this->loader->add_action('wp_footer', $plugin_footer, 'cs_total_Add_Js');

		// Add method for status, change status in Woo -> change in ClearSale
		$plugin_status = new Clearsale_Total_Status($this->get_plugin_name(), $this->get_version());
		//add_action( 'woocommerce_order_status_changed', 'cs_order_status', 10, 4 );
		//The hook wouldn't fire if an extension, such as a payment gateway, tried to set the order status incorrectly.
		// For example, if they changed the status by using $order->status = 'processing' or wp_transition_post_status( 'on-hold', 'processing', $order );
		// then the hook would not fire.
		$this->loader->add_action('woocommerce_order_status_changed', $plugin_status, 'Cs_total_atualiza_status', 10, 4);

		// Add method to do meta_box ajax 
		$plugin_ajax = new Clearsale_Total_Ajax($this->get_plugin_name(), $this->get_version());
		$this->loader->add_action('admin_enqueue_scripts', $plugin_ajax, 'clearsale_total_secure_enqueue_script');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_ajax, 'clearsale_total_javascript_variaveis');
		$this->loader->add_action('wp_ajax_nopriv_cs_total_mbox_ajax_action', $plugin_ajax, 'cs_total_mbox_ajax_action');
		$this->loader->add_action('wp_ajax_cs_total_mbox_ajax_action', $plugin_ajax, 'cs_total_mbox_ajax_action');
		
	} // end of define_admin_hooks

	/**
	 * Register all of the hooks related to the public-facing functionality of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks()
	{

		$plugin_public = new Clearsale_Total_Public($this->get_plugin_name(), $this->get_version());
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'cs_enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'cs_enqueue_scripts');

		// Add method to do ajax
		$plugin_ajax = new Clearsale_Total_Ajax($this->get_plugin_name(), $this->get_version());
		$this->loader->add_action('wp_enqueue_scripts', $plugin_ajax, 'clearsale_total_secure_enqueue_script');
		$this->loader->add_action('template_redirect', $plugin_ajax, 'clearsale_total_javascript_variaveis');
		$this->loader->add_action('wp_ajax_nopriv_clearsale_total_push', $plugin_ajax, 'clearsale_total_push');
		$this->loader->add_action('wp_ajax_clearsale_total_push', $plugin_ajax, 'clearsale_total_push');

		// Vai fazer o hook quando clicar em finalizar, para colocar uma marca para o FingerPrint
		$plugin_checkout = new Clearsale_Total_Checkout($this->get_plugin_name(), $this->get_version());
		$this->loader->add_action('woocommerce_before_checkout_form', $plugin_checkout, 'Cs_finalizar_checkout', 10);

		// Add method for checkout - Neste momento gravamos um status e o pedido vai ser inserido na Clear qdo for pago.
		$this->loader->add_action('woocommerce_checkout_order_processed', $plugin_checkout, 'Cs_total_checkout_order_processed', 10, 3);
		$this->loader->add_action('woocommerce_thankyou', $plugin_checkout, 'Cs_total_thankyou', 20, 1);
		$this->loader->add_action('woocommerce_before_thankyou', $plugin_checkout, 'Cs_total_before_thankyou', 10, 1);
		$this->loader->add_action('woocommerce_payment_complete', $plugin_checkout, 'Cs_total_payment_complete', 10, 3);

		// Add method for extrafield in woo checkout
		$plugin_extrafields = new Clearsale_Total_Extrafields($this->get_plugin_name(), $this->get_version());
		// vamos ver se este plugin esta instalado, se sim não pedimos o CPF na loja, já é feito por ele
		
		if ($this->woo_extra_fields_bra == 0) { // está instalado o plugin do Claudio Sanches?
			// NÃO, então colocamos o campo e verificamos o conteúdo
			//add_action('woocommerce_after_order_notes', 'cs_customise_checkout_field');
			$this->loader->add_action('woocommerce_after_order_notes', $plugin_extrafields, 'cs_customise_checkout_field');
			//add_action('woocommerce_checkout_process', 'cs_customise_checkout_field_process');
			$this->loader->add_action('woocommerce_checkout_process', $plugin_extrafields, 'cs_customise_checkout_field_process');
		}
		// aqui pegamos o campo digitado e colocamos no pedido, se for o nosso ou o do plugin woo-extra-fields-brasil
		//add_action('woocommerce_checkout_update_order_meta', 'cs_customise_checkout_field_update_order_meta');;
		$this->loader->add_action('woocommerce_checkout_update_order_meta', $plugin_extrafields, 'cs_customise_checkout_field_update_order_meta');

		//callback de status de pedido colocado dentro da classe clearsale-total-status.php

	} // end of define_public_hooks

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Clearsale_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}

} // end of class
