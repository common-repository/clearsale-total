<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://letti.com.br/integracoes/
 * @version           2.0.0
 * @since             1.0.0
 * @package           Clearsale
 *
 * @wordpress-plugin
 * Plugin Name:       ClearSale-Total
 * Plugin URI:        https://api.clearsale.com.br/docs/plugins/wooCommerce/totalTotalGarantidoApplication
 * Description:       Integração da loja WooCommerce e a ClearSale Total. Segurança para suas vendas.
 * Version:           3.1.3
 * Author:            ClearSale
 * Author URI:        https://br.clear.sale
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       clearsale-total
 * Domain Path:       /languages
 *
 * Copyright (C) clearsale-total  ClearSale
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
        die;
}

/**
 * Generator README.txt
 * https://generatewp.com/plugin-readme/
 */
/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('CLEARSALE_TOTAL_VERSION', '3.1.3');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-clearsale-total-activator.php
 * 
 * Este método vai ser chamado na ativação do plugin
 */
function clearsale_total_activate()
{
        require_once plugin_dir_path(__FILE__) . 'includes/class-clearsale-total-activator.php';
        Clearsale_Total_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-clearsale-total-deactivator.php
 * 
 * Este método vai ser chamado na desativação do plugin
 */
function clearsale_total_deactivate()
{
        require_once plugin_dir_path(__FILE__) . 'includes/class-clearsale-total-deactivator.php';
        Clearsale_Total_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'clearsale_total_activate');
register_deactivation_hook(__FILE__, 'clearsale_total_deactivate');


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 * 
 * Classe principal que vai carregar todo o plugin
 */
require plugin_dir_path(__FILE__) . 'includes/class-clearsale-total.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks, then kicking off the plugin
 * from this point in the file does not affect the page life cycle.
 * 
 * Todo o plugin foi carregado, os hooks foram adicionados, javascripts e css carregados, vamos rodar
 * o clearsale-total
 *
 * @since    "1.0.0"
 */
function clearsale_total_run()
{

        $plugin = new Clearsale_Total(plugin_basename(__FILE__));

	// declarando compatibilidade com HPOS
	add_action( 'before_woocommerce_init', function() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	} );

        $plugin->run();
}
clearsale_total_run();
