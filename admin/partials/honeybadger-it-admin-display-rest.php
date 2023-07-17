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
if($action=="test_rest_api")
{
    check_admin_referer( 'honeybadger_it_testing_rest_api' );
    $hb_msg=$honeybadger->testRestAPI();
}

?>
<h2><?php esc_html_e('REST API','honeyb');?></h2>
<?php
if($hb_msg!="")
{
    ?>
    <div class="hb-notice-<?php echo esc_attr($hb_msg['status']);?>">
        <p><?php echo esc_html($hb_msg['msg']).": ".esc_html($hb_msg['time']);?></p>
    </div>
    <?php
}
$nonce = wp_create_nonce( 'honeybadger_it_testing_rest_api' );
?>
<h2><?php esc_html_e("Test the REST API","honeyb");?></h2>
<p><?php esc_html_e("Here you can test the communication between your shop and HoneyBadger IT by clicking the below button","honeyb");?></p>

<form action="" method="post">
    <input type="hidden" name="action" value="test_rest_api" />
    <input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo esc_attr($nonce);?>" />
    <input class="button-primary" type="submit" value="<?php echo esc_attr(__('Test the REST API','honeyb'));?>" />
</form>