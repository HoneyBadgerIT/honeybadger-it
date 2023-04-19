<?php
/**
 * @package    Honeybadger_IT
 * @subpackage Honeybadger_IT/admin
 * @author     Claudiu Maftei <claudiu@honeybadger.it>
 */
if ( ! current_user_can( 'manage_options' ) ) {
    return;
}
$hb_msg="";
$action=isset($_POST['action'])?$_POST['action']:"";
if($action=="save_client_id")
    $hb_msg=$honeybadger->changeClientIdAndSecret();

?>
<h2><?php _e('Tools','honeyb');?></h2>
<?php
if($hb_msg!="")
{
    ?>
    <div class="<?php echo $hb_msg['status'];?> notice is-dismissible">
        <p><?php echo $hb_msg['msg'];?></p>
    </div>
    <?php
}
?>
<h2><?php _e("Oauth Credentials change","honeyb");?></h2>
<p><?php _e("Here you can change your Oauth2 Client ID and Secret (this is used if you uninstalled the plugin and installed it again), you will need to redo the setup again","honeyb");?></p>
<form action="" method="post" onsubmit="return validateHbClientIdChangeForm()">
<input type="hidden" name="action" value="save_client_id" />
<table class="widefat" id="hb-status-table" cellspacing="0">
    <tbody>
        <tr>
            <td><label for="client_id"><?php _e("Oauth Client ID","honeyb");?></label></td>
            <td><input type="test" name="client_id" id="client_id" value="<?php echo esc_attr($honeybadger->getHbClientId());?>" /></td>
            <td><?php _e("You can find your Client ID in your HoneBadger IT Account","honeyb");?></td>
        </tr>
        <tr>
            <td><label for="client_secret"><?php _e("Oauth Client Secret","honeyb");?></label></td>
            <td><input type="test" name="client_secret" id="client_secret" value="<?php echo esc_attr($honeybadger->getHbClientSecret());?>" /></td>
            <td><?php _e("You can find your Client Secret in your HoneBadger IT Account","honeyb");?></td>
        </tr>
        <tr>
            <td colspan="3" class="hb-center">
                <input class="button-primary" type="submit" value="<?php echo esc_attr(__('Save Credentials','honeyb'));?>" />
            </td>
        </tr>
    </tbody>
</table>
</form>
<script type="text/javascript">
<!--
function validateHbClientIdChangeForm()
{
    if(jQuery('#client_id').val()=="")
    {
        jQuery('#client_id').css('border-color','red');
        jQuery('#client_id').focus();
        alert('<?php echo __("Please input your Oauth2 Client ID","honeyb");?>');
        return false;
    }
    else
    {
        jQuery('#client_id').css('border-color','#ccc');
    }
    if(jQuery('#client_secret').val()=="")
    {
        jQuery('#client_secret').css('border-color','red');
        jQuery('#client_secret').focus();
        alert('<?php echo __("Please input your Oauth2 Client Secret","honeyb");?>');
        return false;
    }
    else
    {
        jQuery('#client_secret').css('border-color','#ccc');
    }
    return true;
}
//-->
</script>