<?php
/**
 * @package    Honeybadger_IT
 * @subpackage Honeybadger_IT/admin
 * @author     Claudiu Maftei <claudiu@honeybadger.it>
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly   
if ( ! current_user_can( 'manage_options' ) ) {
    return;
}
$hb_msg="";
$action=isset($_POST['action'])?sanitize_text_field($_POST['action']):"";
if($action=="save_settings")
{
    check_admin_referer( 'honeybadger_it_settings_page_form' );
    $hb_msg=$honeybadger->saveSettings();
    $honeybadger=new HoneyBadgerIT\honeybadger;
}
?>
<h2><?php esc_html_e('Settings','honeybadger-it');?></h2>
<?php
if($hb_msg!="")
{
    ?>
    <div class="<?php echo esc_attr($hb_msg['status']);?> notice is-dismissible">
        <p><?php echo esc_html($hb_msg['cnt'])." ".esc_html($hb_msg['msg']);?></p>
    </div>
    <?php
}
$nonce = wp_create_nonce( 'honeybadger_it_settings_page_form' );
?>
<form action="" method="post">
<input type="hidden" name="action" value="save_settings" />
<input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo esc_attr($nonce);?>" />
<table class="widefat" id="hb-status-table" cellspacing="0">
    <tbody>
        <?php
        $explanations=array();
        $explanations['curl_ssl_verify']=esc_html__('Set to NO to don\'t verify the SSL certificate','honeybadger-it');
        $explanations['use_status_colors_on_wc']=esc_html__('Set to YES to use order statuses colors set on HoneyBadger IT in your WC admin orders list','honeybadger-it');
        $explanations['delete_attachments_upon_uninstall']=esc_html__('If set to yes attachment files like invoices will be removed when the plugin is uninstalled','honeybadger-it');
        $explanations['skip_rest_authentication_errors']=esc_html__('Skip REST API Authentication errors, sometimes plugins like Force Login disable the REST API if the user is not logged in, we need the API public for the setup part, afterwards a user is created to login to your shop','honeybadger-it');
        
        foreach($honeybadger->config_front as $config_name => $config_value)
        {
            ?>
            <tr>
                <td><?php echo esc_html($config_name);?></td>
                <td>
                    <?php
                    if($config_name=='curl_ssl_verify')
                    {
                        ?>
                        <select class="button wp-generate-pw hide-if-no-js" name="curl_ssl_verify" id="curl_ssl_verify">
                          <option value="yes"<?php echo(esc_html($config_value)=='yes')?" selected":"";?>><?php esc_html_e("Yes",'honeybadger-it');?></option>
                          <option value="no"<?php echo(esc_html($config_value)=='no')?" selected":"";?>><?php esc_html_e("No",'honeybadger-it');?></option>
                        </select>
                        <?php
                    }
                    else if($config_name=='use_status_colors_on_wc')
                    {
                        ?>
                        <select class="button wp-generate-pw hide-if-no-js" name="use_status_colors_on_wc" id="use_status_colors_on_wc">
                          <option value="yes"<?php echo(esc_html($config_value)=='yes')?" selected":"";?>><?php esc_html_e("Yes",'honeybadger-it');?></option>
                          <option value="no"<?php echo(esc_html($config_value)=='no')?" selected":"";?>><?php esc_html_e("No",'honeybadger-it');?></option>
                        </select>
                        <?php
                    }
                    else if($config_name=='delete_attachments_upon_uninstall')
                    {
                        ?>
                        <select class="button wp-generate-pw hide-if-no-js" name="delete_attachments_upon_uninstall" id="delete_attachments_upon_uninstall">
                          <option value="yes"<?php echo(esc_html($config_value)=='yes')?" selected":"";?>><?php esc_html_e("Yes",'honeybadger-it');?></option>
                          <option value="no"<?php echo(esc_html($config_value)=='no')?" selected":"";?>><?php esc_html_e("No",'honeybadger-it');?></option>
                        </select>
                        <?php
                    }
                    else if($config_name=='skip_rest_authentication_errors')
                    {
                        ?>
                        <select class="button wp-generate-pw hide-if-no-js" name="skip_rest_authentication_errors" id="skip_rest_authentication_errors">
                          <option value="yes"<?php echo(esc_html($config_value)=='yes')?" selected":"";?>><?php esc_html_e("Yes",'honeybadger-it');?></option>
                          <option value="no"<?php echo(esc_html($config_value)=='no')?" selected":"";?>><?php esc_html_e("No",'honeybadger-it');?></option>
                        </select>
                        <?php
                    }
                    ?>
                </td>
                <td><?php echo esc_html($explanations[$config_name]);?></td>
            </tr>
            <?php
        }
    ?>
    <tr>
        <td colspan="3" class="hb-center">
            <input class="button-primary" type="submit" value="<?php echo esc_attr(__('Save Settings','honeybadger-it'));?>" />
        </td>
    </tr>
    </tbody>
</table>
</form>