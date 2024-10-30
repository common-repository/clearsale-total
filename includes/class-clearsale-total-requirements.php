<?php
/**
 * Register all actions for requirements, to verify wordpress, woocommerce and php version
 */

/**
 * Register all actions for requirements, to verify wordpress, woocommerce and php version.
 * baseado no WC_Erede_Requirements.
 *
 * @link       https://letti.com.br/wordpress
 * @since      "1.0.0"
 * @author     Letti Tecnologia <contato@letti.com.br>
 * @package    Clearsale_Total
 * @subpackage Clearsale_Total/includes
 */


if (! defined( 'ABSPATH' ))
{
    exit;
}

/**
 * Class to verify wordpress, woocommerce and php version.
 *
 * @since   "1.0.0"
 */
class Clearsale_Total_Requirements
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
	 * Plugin main file.
	 *
	 * plugin_basename( __FILE__ )
	 *
	 * @access private
	 * @var string
	 */
    private $plugin_file = '';

    /**
    * WordPress.
    *
    * @access private
    * @var bool
    */
    private $wp = true;

    /**
    * PHP.
    *
    * @access private
    * @var bool
    */
    private $php = true;

    /**
    * Woocommerce
    *
    * @access private
    * @var bool
    */
    private $wooCommerce = true;

    /**
    * PHP Extensions.
    *
    * @access private
    * @var bool
    */
    private $extensions = true;

    /**
    * Requirements to check.
    *
    * @access private
    * @var array
    */
    private $requirements = array();

    /**
    * Results failures.
    *
    * Associative array with requirements results.
    *
    * @access private
    * @var array
    */
    private $failures = array();

    /**
    * Run checks.
    *
    * @param string     $plugin_name    the name of plugin
    * @param string     $version        The version of this plugin
    * @param string     $plugin_file    Output of plugin_basename( __FILE__ )
    * @param array      $requirements   Associative array with requirements
    */
    public function __construct($plugin_name, $version, $plugin_file, $requirements)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->plugin_file = $plugin_file;
        $this->requirements = $requirements;

        $err_results = $extensions = array();
        //syslog(LOG_WARNING,"require entrou...");
        if (empty( $requirements ) || ! is_array( $requirements )) {
            trigger_error( __('WP Requirements: the requirements are invalid.', "clearsale-total"), E_USER_ERROR );
        }

        // Check for WordPress version.
        if ($requirements['WordPress'] && is_string( $requirements['WordPress'] )) {
            if (function_exists( 'get_bloginfo' )) {
                $wp_version = get_bloginfo( 'version' );
                if ( version_compare( $wp_version, $requirements['WordPress'] ) === - 1 ) {
                    $err_results['WordPress'] = $wp_version;
                    $this->wp = false;
                }
            }
        }

        // Check fo PHP version.
        if ( $requirements['PHP'] && is_string( $requirements['PHP'] ) ) {
            if ( version_compare( PHP_VERSION, $requirements['PHP'] ) === -1 ) {
                $err_results['PHP'] = PHP_VERSION;
                $this->php = false;
            }
        }

        // Check Woocommerce version.                   
        if ($requirements['Woocommerce'] && is_string( $requirements['Woocommerce'] )) {
            $wooCommerceVersion = $this->getWoocommerceVersion();
            if ($wooCommerceVersion == NULL || version_compare( $wooCommerceVersion, $requirements['Woocommerce'] ) === -1 ) {
                $err_results['Woocommerce'] = $wooCommerceVersion;
                $this->wooCommerce = false;
            }
        }

        // Check fo PHP Extensions.
        if ($requirements['Extensions'] && is_array( $requirements['Extensions'] )) {
            foreach ($requirements['Extensions'] as $extension) {
                if ($extension && is_string( $extension )) {
                    $extensions[ $extension ] = extension_loaded($extension);
                }
            } // end of for
            if ( in_array(false, $extensions) ) {
                foreach ($extensions as $extension_name => $found) {
                    if ($found === false) {
                        $err_results['Extensions'][ $extension_name ] = $extension_name;
                    }
                } // end of for
                $this->extensions = false;
            }
        }
        $this->failures = $err_results;
    } // end of construct

    /**
    * Get woocommerce version
    *
    */
    private function getWoocommerceVersion()
    {

        if (! function_exists('get_plugins'))
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        $plugin_folder = get_plugins('/' . 'woocommerce');
        $plugin_file = 'woocommerce.php';
        if ( isset( $plugin_folder[$plugin_file]['Version'] ) ) {
            return $plugin_folder[$plugin_file]['Version'];
        } else {
            return NULL;
        }
    } //  end of getWoocommerceVersion

    /**
    * Check if versions check pass.
    *
    * @return bool
    */
    public function pass()
    {
        if ( in_array( false, array(
                            $this->wp,
                            $this->php,
                            $this->extensions,
                            $this->wooCommerce
                        ) )
            ) return false;
        return true;
    } // end of pass

    /**
    * Notice message.
    *
    * @param  string $message An additional message.
    * @return string
    */
    public function getNotice( $message = '' )
    {

        $notice   = '';
        $name     = $this->plugin_name;
        $failures = $this->failures;

        if ( empty( $failures ) || ! is_array( $failures ) ) return $notice;

        $notice  = '<div class="error">' . "\n";
        $notice .= "\t" . '<p>' . "\n";
        //$notice .= '<strong>' . sprintf( '%s', $name) . __(' could not be activated.', 'clearsale-total) . '</strong><br>';
        $notice .= '<h3>' . sprintf( '%s', $name) . __(' could not be activated.', 'clearsale-total') . '</h3><br>';

        foreach ($failures as $requirement => $found) {

            $required = $this->requirements[ $requirement ];

            if ('Extensions' == $requirement) {
                if ( is_array( $found ) ) {
                    $notice .= __('Required PHP Extension(s) not found: ', 'clearsale-total') .
                        sprintf( '%s.', join( ', ', $found ) ) . '<br>';
                }
            } else {
                $notice .= sprintf(
                    'Required %1$s version: %2$s - Version found: %3$s',
                        $requirement,
                        $required,
                        $found
                ) . '<br>';
            }

        } // end of for

//      $notice .= '<em>' . sprintf( 'Please update to meet %s requirements.', $name ) . '</em>' . "\n";
        $notice .= '<em>' . __('Please update to meet requirements.', 'clearsale-total') . '</em>' . "\n";

        $notice .= "\t" . '</p>' . "\n";
        if ( $message ) {
            $notice .= $message;
        }
        $notice .= '</div>';

        return $notice;
    } // end of getNotice


    /**
    * Deactivate plugin.
    */
    public function deactivatePlugin()
    {
        if (function_exists('deactivate_plugins') && function_exists('plugin_basename')) {
            deactivate_plugins( $this->plugin_file );
        }
    }

    /**
    * Print notice.
	*/
	public function printNotice()
    {
		echo $this->notice;
    }

    /**
    * Deactivate plugin and display admin notice.
    *
    * @param string $message An additional message in notice.
    */
    public function halt( $message = '' )
    {

        $this->notice = $this->getNotice($message);

        if ( $this->notice && function_exists( 'add_action' ) ) {

            add_action( 'admin_notices', array( $this, 'printNotice' ) );
            add_action( 'admin_init', array( $this, 'deactivatePlugin' ) );

            if ( isset( $_GET['activate'] ) ) {
                unset( $_GET['activate'] );
            }
        }
    } // end of halt


} // end of class

