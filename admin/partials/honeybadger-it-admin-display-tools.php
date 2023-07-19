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
if($action=="save_client_id")
{
    check_admin_referer( 'honeybadger_it_tools_page_form' );
    $hb_msg=$honeybadger->changeClientIdAndSecret();
}

?>
<h2><?php esc_html_e('Tools','honeybadger-it');?></h2>
<?php
if($hb_msg!="")
{
    ?>
    <div class="<?php echo esc_attr($hb_msg['status']);?> notice is-dismissible">
        <p><?php echo esc_html($hb_msg['msg']);?></p>
    </div>
    <?php
}
$nonce = wp_create_nonce( 'honeybadger_it_tools_page_form' );
?>
<h2><?php esc_html_e("Oauth Credentials change",'honeybadger-it');?></h2>
<p><?php esc_html_e("Here you can change your Oauth2 Client ID and Secret (this is used if you uninstalled the plugin and installed it again), you will need to redo the setup again",'honeybadger-it');?></p>
<form action="" method="post" onSubmit="return validateHbClientIdChangeForm();">
<input type="hidden" name="action" value="save_client_id" />
<input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo esc_attr($nonce);?>" />
<table class="widefat" id="hb-status-table" cellspacing="0">
    <tbody>
        <tr>
            <td><label for="client_id"><?php esc_html_e("Oauth Client ID",'honeybadger-it');?></label></td>
            <td><input type="test" name="client_id" id="client_id" value="<?php echo esc_attr($honeybadger->getHbClientId());?>" /></td>
            <td><?php esc_html_e("You can find your Client ID in your HoneBadger IT Account",'honeybadger-it');?></td>
        </tr>
        <tr>
            <td><label for="client_secret"><?php esc_html_e("Oauth Client Secret",'honeybadger-it');?></label></td>
            <td><input type="test" name="client_secret" id="client_secret" value="<?php echo esc_attr($honeybadger->getHbClientSecret());?>" /></td>
            <td><?php esc_html_e("You can find your Client Secret in your HoneBadger IT Account",'honeybadger-it');?></td>
        </tr>
        <tr>
            <td colspan="3" class="hb-center">
                <input class="button-primary" type="submit" value="<?php echo esc_attr(__('Save Credentials','honeybadger-it'));?>" />
            </td>
        </tr>
    </tbody>
</table>
</form>
<?php
$data_js='
function validateHbClientIdChangeForm()
{
    if(jQuery("#client_id").val()=="")
    {
        jQuery("#client_id").css("border-color","red");
        jQuery("#client_id").focus();
        alert("'.esc_attr(esc_html__("Please input your Oauth2 Client ID",'honeybadger-it')).'");
        return false;
    }
    else
    {
        jQuery("#client_id").css("border-color","#ccc");
    }
    if(jQuery("#client_secret").val()=="")
    {
        jQuery("#client_secret").css("border-color","red");
        jQuery("#client_secret").focus();
        alert("'.esc_attr(esc_html__("Please input your Oauth2 Client Secret",'honeybadger-it')).'");
        return false;
    }
    else
    {
        jQuery("#client_secret").css("border-color","#ccc");
    }
    if(confirm("'.esc_js(esc_html__("Don\'t forget to redo the setup afterwards",'honeybadger-it')).'!!!"))
    {
        return true;
    }
    return false;
}
';
wp_register_script( 'honeybadger_it_js_tools_inline_script_handler', '' );
wp_enqueue_script( 'honeybadger_it_js_tools_inline_script_handler' );
wp_add_inline_script("honeybadger_it_js_tools_inline_script_handler",$data_js);
?>
