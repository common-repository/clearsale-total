<?php
/**
 * Provide a admin area view for the plugin
 */
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://letti.com.br/wordpress
 * @since      "1.0.0"
 * @package    Clearsale
 * @subpackage Clearsale/admin/partials
 */
?>

<div class="wrap">

    <h2><?php echo esc_html(get_admin_page_title()); ?></h2>

    <form method="post" name="clearsale_options" id="cs_options" action="options.php">

    <?php
        //Grab all options - pegar do banco os dados e completar as variaveis
        $options = get_option($this->plugin_name);

        $modo = esc_html($options['modo']); // vem numerico, 1|0 
        $login = esc_html($options['login']);
        $password = esc_html($options['password']);
        $finger = esc_html($options['finger']);
        $cancel_order = $options['cancel_order']; // true = on qdo tiver reprovação da Clear, cancelar pedido
        if (!isset($options['status_of_aproved'])) $status_of_aproved = "wc-processing";
        else $status_of_aproved = esc_html($options['status_of_aproved']); // o status que vamos colocar qdo o pedido for aprovado
        // antes sempre era wc-processing, isto é feito em: Cs_status
        $debug = $options['debug']; // =on ligado(checked)
        $tmp = wc_get_order_statuses();
        $remover = array("wc-cs-inanalisis" => "Análise de Risco ClearSale", "wc-cancelled" => "Cancelado", "wc-failed" => "Malsucedido");
        $status_all = array_diff($tmp, $remover)
    ?>

    <?php
        settings_fields($this->plugin_name);
        do_settings_sections($this->plugin_name);
    ?>
        
        <h2><?php esc_attr_e('View all ClearSale products', 'clearsale-total'); ?>
        <a href="https://br.clear.sale/antifraude/ecommerce" target="_blank"><?php esc_attr_e('here', 'clearsale-total'); ?></a>
        </h2>

        <h2><?php esc_attr_e('Select between test and production environment', 'clearsale-total'); ?></h2>
        <select name="clearsale-total[modo]" id="clearsale-total-modo">
            <option <?php if ($modo==1): ?> selected="selected" <?php endif;?> value="1"><?php esc_html_e('ClearSale Test (homologation)','clearsale-total'); ?></option>
	        <option <?php if (!$modo || $modo==0): ?> selected="selected" <?php endif;?> value="0"><?php esc_html_e('ClearSale Production','clearsale-total'); ?></option>

        </select>

        <h2><?php esc_attr_e('Enter login and password provided by ClearSale', 'clearsale-total'); ?></h2>
        <fieldset>
            <legend class="screen-reader-text"><span>login</span></legend>
            <?php esc_html_e('Login: ', 'clearsale-total'); ?>
            <input type="text" class="regular-text" id="<?php echo esc_attr('clearsale-total-login'); ?>"
                name="clearsale-total[login]" value="<?php echo esc_html($login); ?>"/>
        </fieldset>

        <fieldset>
            <legend class="screen-reader-text"><span>password</span></legend>
            <?php esc_html_e('Password: ', 'clearsale-total'); ?>
            <input type="text" class="regular-text" id="clearsale-total-password"
                name="clearsale-total[password]" value="<?php echo esc_html($password); ?>"/>
        </fieldset>

        <fieldset>
            <p><?php esc_html_e('Enter the Fingerprint provided by ClearSale,', 'clearsale-total'); ?>
              </br><?php esc_html_e('You should have a number like this: a6s8h29ym6xgm5qor3sk', 'clearsale-total'); ?>
            </p>
            <legend class="screen-reader-text"><span><?php esc_html_e('finger', 'clearsale-total'); ?></span></legend>
            <input type="textarea" cols="80" rows="10" id="clearsale-total-finger"
                name="clearsale-total[finger]" value="<?php echo esc_html($finger); ?>"/>
        </fieldset>

        <br>
        <h2><?php esc_html_e('Cancel order when CleaSale status is negative', 'clearsale-total'); ?></h2>
        <script type="text/javascript">
            <?php if ($cancel_order): ?>
                var flag=1;
            <?php else: ?>
                var flag=0;
            <?php endif; ?>
        </script>
        <fieldset>
            <?php esc_html_e('Cancel Order:', 'clearsale-total'); ?>
            <input type="checkbox" id="clearsale-total-cancel_order" onclick="msgcheckbox();"
             name="clearsale-total[cancel_order]"
               <?php if ($cancel_order): ?> checked="checked" <?php endif; ?>>
            <div id="msgchkbox" style="display: <?php if ($cancel_order): ?> none; <?php else: ?> block; <?php endif; ?>">
                <h3><?php esc_html_e('ClearSale is not responsible for orders that follow after our disapproval', 'clearsale-total' ); ?></h3>
            </div>
        </fieldset>
        <script type="text/javascript"> 
            function msgcheckbox() {
                if (flag==0) flag = 1; else flag = 0;
                if (flag == 0) document.getElementById("msgchkbox").style.display = "block";
                else document.getElementById("msgchkbox").style.display = "none";
            }
        </script>
        <br>
        <h2><?php esc_html_e('Select New status after order is approved. Default: Processing', 'clearsale-total'); ?></h2>
        <select name="clearsale-total[status]" id="clearsale-total-status">
        <?php foreach($status_all as $key => $s_names) {?>
            <option <?php if ($status_of_aproved==$key): ?> selected="selected" <?php endif;?>
                value=<?php echo esc_attr($key)?>>
              <?php echo esc_html($s_names); ?></option>
        <?php } ?>
        </select>
        <br><br>
        <h2><?php esc_html_e('Please, inform the URL below to ClearSale. This action will allow the retailer to receive approval/disapproval of the operations.', 'clearsale-total'); ?></h2>
        <h2><?php echo esc_js(get_site_url());?>/wc-api/clearsale_total_status/</h2>

        <br>
        <fieldset>
            <p><?php esc_html_e('Enable Log: ', 'clearsale-total'); ?></p>
            <input type="checkbox" id="clearsale-total-debug" name="clearsale-total[debug]"
               <?php if ($debug): ?> checked="checked" <?php endif; ?>>
            <?php
                printf(esc_html__('Log ClearSale events, such as API requests, you can check this log in %s.', 'clearsale-total'),
                '<a href="' . esc_url(admin_url('admin.php?page=wc-status&tab=logs&log_file=' . esc_attr('clearsale-total')
                 . '-' . date("Y-m-d") . '-' . sanitize_file_name(wp_hash('clearsale-total')) . '.log' ) ) . '">'
                 . esc_html__('System Status &gt; Logs', 'clearsale-total') . '</a>');
            ?>
        </fieldset>
        <br>

        <?php submit_button(esc_html__('Save all changes', 'clearsale-total'), 'primary','submit', TRUE); ?>
        <?php settings_fields('clearsale-total'); ?>
    </form>


</div>

