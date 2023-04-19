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
if($action=="test_rest_api")
    $hb_msg=$honeybadger->testRestAPI();

?>
<h2><?php _e('REST API','honeyb');?></h2>
<?php
if($hb_msg!="")
{
    ?>
    <div class="hb-notice-<?php echo $hb_msg['status'];?>">
        <p><?php echo $hb_msg['msg'];?></p>
    </div>
    <?php
}
?>
<h2><?php _e("Test the REST API","honeyb");?></h2>
<p><?php _e("Here you can test the communication between your shop and HoneyBadger IT by clicking the below button","honeyb");?></p>

<form action="" method="post">
    <input type="hidden" name="action" value="test_rest_api" />
    <input class="button-primary" type="submit" value="<?php echo esc_attr(__('Test the REST API','honeyb'));?>" />
</form>