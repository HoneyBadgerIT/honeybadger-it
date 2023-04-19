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
if($action=="save_settings")
{
    $hb_msg=$honeybadger->saveSettings();
    $honeybadger=new honeybadger;
}
?>
<h2><?php _e('Settings','honeyb');?></h2>
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
<form action="" method="post" onsubmit="return validateHbSettingsForm()">
<input type="hidden" name="action" value="save_settings" />
<table class="widefat" id="hb-status-table" cellspacing="0">
    <tbody>
        <?php
        $explanations=array();
        $explanations['curl_ssl_verify']=__('Set to NO to don\'t verify the SSL certificate','honeyb');
        $explanations['use_status_colors_on_wc']=__('Set to YES to use order statuses colors set on HoneyBadger IT in your WC admin orders list','honeyb');
        $explanations['skip_rest_authentication_errors']=__('Skip REST API Authentication errors, sometimes plugins like Force Login disable the REST API if the user is not logged in, we need the API public for the setup part, afterwards a user is created to login to your shop','honeyb');
        foreach($honeybadger->config_front as $config_name => $config_value)
        {
            ?>
            <tr>
                <td><?php echo $config_name;?></td>
                <td>
                    <?php
                    if($config_name=='curl_ssl_verify')
                    {
                        ?>
                        <select class="button wp-generate-pw hide-if-no-js" name="curl_ssl_verify" id="curl_ssl_verify">
                          <option value="yes"<?php echo($config_value=='yes')?" selected":"";?>>Yes</option>
                          <option value="no"<?php echo($config_value=='no')?" selected":"";?>>No</option>
                        </select>
                        <?php
                    }
                    else if($config_name=='use_status_colors_on_wc')
                    {
                        ?>
                        <select class="button wp-generate-pw hide-if-no-js" name="use_status_colors_on_wc" id="use_status_colors_on_wc">
                          <option value="yes"<?php echo($config_value=='yes')?" selected":"";?>>Yes</option>
                          <option value="no"<?php echo($config_value=='no')?" selected":"";?>>No</option>
                        </select>
                        <?php
                    }
                    else if($config_name=='skip_rest_authentication_errors')
                    {
                        ?>
                        <select class="button wp-generate-pw hide-if-no-js" name="skip_rest_authentication_errors" id="skip_rest_authentication_errors">
                          <option value="yes"<?php echo($config_value=='yes')?" selected":"";?>>Yes</option>
                          <option value="no"<?php echo($config_value=='no')?" selected":"";?>>No</option>
                        </select>
                        <?php
                    }
                    else
                    {
                    ?>
                    <input type="text" name="<?php echo $config_name;?>" id="<?php echo $config_name;?>" value="<?php echo esc_attr($config_value);?>" />
                    <?php
                    }
                    ?>
                </td>
                <td><?php echo $explanations[$config_name];?></td>
            </tr>
            <?php
        }
    ?>
    <tr>
        <td colspan="3" class="hb-center">
            <input class="button-primary" type="submit" value="<?php echo esc_attr(__('Save Settings','honeyb'));?>" />
        </td>
    </tr>
    </tbody>
</table>
</form>
<script type="text/javascript">
<!--
function validateHbSettingsForm()
{
    <?php
    foreach($honeybadger->config_front as $config_name => $config_value)
    {
    ?>
        if(jQuery('#<?php echo $config_name;?>').val()=="")
        {
            alert("<?php echo esc_attr(__('Please input a value here','honeyb'));?>");
            jQuery('#<?php echo $config_name;?>').focus();
            return false;
        }
    <?php
    }
    ?>
    return true;
}
//-->
</script>