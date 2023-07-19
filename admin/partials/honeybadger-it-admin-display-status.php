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
global $wp_version;
$action=isset($_POST['action'])?sanitize_text_field($_POST['action']):"";
if($action=="restart_the_setup")
{
    check_admin_referer( 'honeybadger_it_setup_restart' );
    $honeybadger->restartTheSetup();
    $honeybadger->config->setup_step=0;
}
if($action=="setup_the_hb_account")
{
    check_admin_referer( 'honeybadger_it_setup_the_hb_account' );
    $honeybadger->setupTheHbAccount();
}
if($action=="revoke_access")
{
    check_admin_referer( 'honeybadger_it_setup_revoke_access' );
    $honeybadger->revokeHbAccess();
}

require_once HONEYBADGER_PLUGIN_PATH . '/constants.php';
$setup_step=isset($_GET['setup_step'])?(int)$_GET['setup_step']:0;
if($setup_step=="1")
{
    $honeybadger->setCurrentSetupStep(1);
    $location=esc_url(admin_url()."admin.php?page=honeybadger-it");
    header("Location: $location");
    $data_js='window.location.href = "'.esc_url(admin_url().'admin.php?page=honeybadger-it').';';
    wp_register_script( 'honeybadger_it_js_setup_inline_script_handler', '' );
    wp_enqueue_script( 'honeybadger_it_js_setup_inline_script_handler' );
    wp_add_inline_script("honeybadger_it_js_setup_inline_script_handler",$data_js);
}
if($honeybadger->config->first_time_installation==1)
{
    require_once HONEYBADGER_PLUGIN_PATH . '/includes/honeybadger-api.php';
    $honeybadgerAPI=new HoneyBadgerIT\API\honeybadgerAPI;
    $honeybadgerAPI->checkFirstTimeInstallation();
}
$honeybadger_running_cgi=substr( php_sapi_name(), 0, 3 ) == 'cgi';
$honeybadger_running_windows=strtoupper( substr( PHP_OS, 0, 3 ) ) === "WIN";
$honeybadger_curl_exists=function_exists('curl_version');

$honeybadger_online=false;
$honeybadger_online_time=false;
$honeybadger_online_request=$honeybadger->simpleCurlRequest($honeybadger->config->ping_url);
if($honeybadger_online_request['response']=='ok'){
    $honeybadger_online=true;
    $honeybadger_online_time=$honeybadger_online_request['time'];
}
$deal_breaker=false;
if(isset($_GET['msg']) && sanitize_text_field($_GET['msg'])=='created')
{
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php esc_html_e( 'An email verification link was sent to your email address', 'honeybadger-it' ); ?></p>
    </div>
    <?php
    $data_js="
    jQuery('document').ready(function(){
        setTimeout(function(){
            jQuery('button.notice-dismiss').click(function(){
                var url=window.location.href;
                url=url.replace('&msg=created','');
                window.location.href = url;
            });
        },500);
    });
    ";
    wp_register_script( 'honeybadger_it_js_setup_inline_script_handler', '' );
    wp_enqueue_script( 'honeybadger_it_js_setup_inline_script_handler' );
    wp_add_inline_script("honeybadger_it_js_setup_inline_script_handler",$data_js);
}
?>
<h2><?php esc_html_e('Welcome to HoneyBadger.IT plugin','honeybadger-it');?></h2>
<p><a href="<?php echo esc_url('https://honeybadger.it');?>" target="_blank">HoneyBadger.IT</a> <?php esc_html_e('is an online management system for your Woocommerce shop','honeybadger-it');?>. <?php esc_html_e('This plugin is used for the communication between your site and the HoneyBadger IT','honeybadger-it');?>. <?php esc_html_e('The communication between the parts use Oauth2 protocol for authorization and the Wordpress REST API v2 for data transfer','honeybadger-it');?>. <?php esc_html_e('All communications are done over HTTPS, you would need a valid SSL certificate installed or you could use self signed certificate and set curl_ssl_verify to no in settings','honeybadger-it');?>.</p>

<div class="hbcontainer">
  <div class="hb-row">
    <div class="hb-col-sm">
        <?php
        $setup_step=$honeybadger->config->setup_step;
        if($setup_step==0)
        {
        ?>
        <h2><?php esc_html_e('Let\'s start','honeybadger-it');?></h2>
        <p><?php esc_html_e('First step is to allow access to HoneyBadger.IT server by clicking the below button','honeybadger-it');?>.</p>
        <p><?php esc_html_e('A new user role and user will be created "honeybadger" (the user is used by the REST API and will have a random strong password)','honeybadger-it');?>.</p>
        <p><?php esc_html_e("By using the HoneyBadger.IT platform you agree with our",'honeybadger-it');?>
            <a href="<?php echo esc_url('https://honeybadger.it/terms-and-conditions');?>" target="_blank"><?php esc_html_e("terms and conditions",'honeybadger-it');?></a> <?php esc_html_e("and",'honeybadger-it');?>
            <a href="<?php echo esc_url('https://honeybadger.it/privacy-policy/');?>" target="_blank"><?php esc_html_e("privacy policy",'honeybadger-it');?></a> 
        </p>
        <?php
        }
        if(($setup_step==1 || $setup_step==2) && $honeybadger->config->is_refresh==0)
        {
        ?>
        <h2><?php esc_html_e('One more step','honeybadger-it');?></h2>
        <p><?php esc_html_e('Everything looks ok so far, now we need to create your account on HoneyBadger IT Server','honeybadger-it');?>.</p>
        <p><?php esc_html_e('You need an email address and a password for this, please complete the below form','honeybadger-it');?>.</p>
        <p><?php esc_html_e('If something goes wrong below please try again later by refreshing the page or restart the setup','honeybadger-it');?>.</p>
        <?php
        }
        if($setup_step==3)
        {
        ?>
        <h2><?php esc_html_e('HoneyBadger IT connection established','honeybadger-it');?></h2>
        <p><?php esc_html_e('Everything looks good','honeybadger-it');?>. <strong><?php esc_html_e('If you just created your HoneyBadger.IT account, do not forget to validate your email','honeybadger-it');?>.</strong></p>
        <p><a href="<?php echo esc_url("https://".HONEYBADGER_IT_TARGET_SUBDOMAIN.".honeybadger.it/");?>" target="_blank"><?php esc_html_e('Access your Honeybadger IT account here','honeybadger-it');?>.</a>
        <?php
        if($honeybadger->config->honeybadger_account_email!="")
        {
            ?>
            <a href="javascript:jQuery('#honeybadger_account_email_a').hide();jQuery('#honeybadger_account_email').show();" id="honeybadger_account_email_a"><small><?php _e("Show account email",'honeybadger-it');?></small></a>
            <span id="honeybadger_account_email" style="display:none;"><?php echo esc_html($honeybadger->config->honeybadger_account_email);?></span>
            <?php
        }
        ?></p>
        <p><?php esc_html_e('If the Oauth2 access token is expired, means you didn\'t use your HoneyBadger IT account for some time now, you need to refresh the access token by allowing access again below','honeybadger-it');?>.</p>
        <p><?php esc_html_e('If you want to revoke the access please click the button below','honeybadger-it');?>.</p>
        <?php 
        }
        if($setup_step==4)
        {
        ?>
        <h2><?php esc_html_e('HoneyBadger IT connection disabled','honeybadger-it');?></h2>
        <p><?php esc_html_e('You have revoked the HoneyBadger IT acccess','honeybadger-it');?>.</p>
        <p><?php esc_html_e('Click the button below to allow HoneyBadger IT acccess to your shop again','honeybadger-it');?>.</p>
        <?php 
        }
        ?>
        <div id="hb-one-moment" style="display:none;">
            <h2><?php esc_html_e("One moment, redirecting...",'honeybadger-it');?></h2>
        </div>
        <div class="hb-left" id="hb-status-update">
            <?php
            $under_https=0;
            if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
                $under_https=1;
            if($setup_step==0)
            {
            ?>
                <input<?php echo (($under_https==0)?' disabled="disabled"':"");?> type="button" onClick="javascript:createHoneybadgerUserRole();" class="button-primary hb-setup-no-use" value="<?php echo esc_attr(__('Setup HoneyBadger account','honeybadger-it'));?>" />
            <?php
                if($under_https==0)
                {
                    ?>
                    <p class="hb-red-notice"><?php esc_html_e("HTTPS is required in order to use this plugin.",'honeybadger-it');?></p>
                    <?php
                }
            }
            $status_changed=0;
            if($setup_step==1)
            {
                if($honeybadger->config->is_refresh==0)
                {
                    $nonce=wp_create_nonce( 'honeybadger_it_setup_restart' );
                ?>
                <form method="post" action="">
                    <input type="hidden" name="action" value="restart_the_setup" />
                    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce);?>" />
                    <input type="submit" class="button-secondary" value="<?php echo esc_attr(__("Restart the setup",'honeybadger-it'));?>" />
                </form>
                <?php
                }
                ?>
                <div class="hb-notice-updated"><p><?php esc_html_e("Honeybadger user created with success",'honeybadger-it');?></p></div>
                <input type="button" disabled="disabled" class="button hb_disabled wp-generate-pw hide-if-no-js" value="<?php echo esc_attr(__("Honeybadger IT access authorized with success",'honeybadger-it'));?>">
                <?php
                $honeybadger->doOauthPingTest();
                if($honeybadger->config->is_refresh==1)
                    return;
                $setup_step=2;
                $status_changed=1;
            }
            if($setup_step==2)
            {
                if($status_changed==0)
                {
                    $nonce=wp_create_nonce( 'honeybadger_it_setup_restart' );
                ?>
                <form method="post" action="">
                    <input type="hidden" name="action" value="restart_the_setup" />
                    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce);?>" />
                    <input type="submit" class="button-secondary" value="<?php echo esc_attr(__("Restart the setup",'honeybadger-it'));?>" />
                </form>
                <div class="hb-notice-updated"><p><?php esc_html_e("Honeybadger user created with success",'honeybadger-it');?></p></div>
                <input type="button" disabled="disabled" class="button hb_disabled wp-generate-pw hide-if-no-js" value="<?php echo esc_attr(__("Honeybadger IT access authorized with success",'honeybadger-it'));?>" />
                <div class="hb-notice-updated">
                    <p><?php esc_html_e("Honeybadger Oauth setup with success",'honeybadger-it');?></p>
                 </div>
                 <?php
                }
                $current_user=wp_get_current_user();
                $first_name = get_user_meta( $current_user->ID, 'first_name', true );
                $last_name = get_user_meta( $current_user->ID, 'last_name', true );
                $nonce = wp_create_nonce( 'honeybadger_it_setup_the_hb_account' );
                ?>
                <div class="hb-form-container">
                    <form method="post" action="" onSubmit="return validateHbAccountCreation();">
                        <input type="hidden" name="action" value="setup_the_hb_account" />
                        <input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo esc_attr($nonce);?>" />
                        <div class="hb-row">
                            <div class="hb-col">
                                <label for="hb_first_name"><?php echo esc_html(__("First Name",'honeybadger-it'));?></label>
                            </div>
                            <div class="hb-col">
                                <input type="text" name="hb_first_name" id="hb_first_name" value="<?php echo esc_attr($first_name);?>" />
                            </div>
                        </div>
                        <div class="hb-row">
                            <div class="hb-col">
                                <label for="hb_last_name"><?php echo esc_html(__("Last Name",'honeybadger-it'));?></label>
                            </div>
                            <div class="hb-col">
                                <input type="text" name="hb_last_name" id="hb_last_name" value="<?php echo esc_attr($last_name);?>" />
                            </div>
                        </div>
                        <div class="hb-row">
                            <div class="hb-col">
                                <label for="hb_email"><?php esc_html_e("Email",'honeybadger-it');?></label>
                            </div>
                            <div class="hb-col">
                                <input style="margin-bottom:0px;" type="email" name="hb_email" id="hb_email" value="<?php echo esc_attr($current_user->user_email);?>" />
                                <small><?php esc_html_e("You will need to confirm your email",'honeybadger-it');?></small>
                            </div>
                        </div>
                        <div class="hb-row">
                            <div class="hb-col">
                                <label for="hb_password"><?php echo esc_html(__("Password",'honeybadger-it'));?></label>
                            </div>
                            <div class="hb-col password-container">
                                <input type="password" name="hb_password" id="hb_password" value="" autocomplete="new-password" /><i class="fa-solid fa-eye" id="eye"></i><br />
                            </div>
                        </div>
                        <div class="hb-row">
                            <div class="hb-col">
                                <small><?php echo esc_html(__("Please input a Password [6 to 20 characters which contain at least one numeric digit, one uppercase and one lowercase letter]",'honeybadger-it'));?></small>
                            </div>
                        </div>
                        <div class="hb-row">
                            <div class="hb-col">
                                <input type="submit" class="button-primary" value="<?php echo esc_attr(__("Create the HoneyBadger IT Account",'honeybadger-it'));?>" />
                            </div>
                        </div>
                    </form>
                </div>
                <?php
                $data_js="
                function validateHbAccountCreation()
                {
                    if(jQuery('#hb_email').val()=='' || !validateHbEmail(jQuery('#hb_email').val()))
                    {
                        jQuery('#hb_email').css('border-color','red');
                        jQuery('#hb_email').focus();
                        alert('".esc_attr(__("Please input a valid email address",'honeybadger-it'))."');
                        return false;
                    }
                    else
                    {
                        jQuery('#hb_email').css('border-color','#ccc');
                    }
                    if(jQuery('#hb_password').val()=='' || !validateHbPassword(jQuery('#hb_password').val()))
                    {
                        jQuery('#hb_password').css('border-color','red');
                        jQuery('#hb_password').focus();
                        alert('".esc_attr(__("Please input a Password [6 to 20 characters which contain at least one numeric digit, one uppercase and one lowercase letter]",'honeybadger-it'))."');
                        return false;
                    }
                    else
                    {
                        jQuery('#hb_email').css('border-color','#ccc');
                    }
                    return true;
                }
                ";
                wp_register_script( 'honeybadger_it_js_status_inline_script_handler', '' );
                wp_enqueue_script( 'honeybadger_it_js_status_inline_script_handler' );
                wp_add_inline_script("honeybadger_it_js_status_inline_script_handler",$data_js);
            }
            if($setup_step==3)
            {
                if(!$honeybadger->checkAccessTokenExpiry())
                {
                    ?>
                    <p class="hb-red-notice"><?php echo esc_html(__("Seems that the access tokens expired, please refresh them.",'honeybadger-it'));?></p>
                    <?php
                }
                $nonce = wp_create_nonce( 'honeybadger_it_setup_revoke_access' );
                ?>
                <input type="button" onClick="javascript:refreshHoneybadgerConnection();" class="button-primary hb-setup-no-use leftmarginme" value="<?php echo esc_attr(__("Refresh the HoneyBadger IT Access Tokens",'honeybadger-it'));?>" /><br /><br />
                <form method="post" action="" id="revoke_form" onSubmit="return confirm('<?php echo esc_js(__("Are you sure you want to revoke the HoneyBadger IT Access?",'honeybadger-it'));?>');">
                    <input type="hidden" name="action" value="revoke_access" />
                    <input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo esc_attr($nonce);?>" />
                    <input type="submit" class="button-secondary leftmarginme" value="<?php echo esc_attr(__("Revoke HoneyBadger IT Access",'honeybadger-it'));?>" />
                </form>
                <?php
            }
            if($setup_step==4)
            {
                ?>
                    <input type="button" onClick="javascript:refreshHoneybadgerConnection();" class="button-primary hb-setup-no-use leftmarginme" value="<?php echo esc_attr(__("Refresh the HoneyBadger IT Access Tokens",'honeybadger-it'));?>" />
                <?php
            }

            ?>
        </div>
    </div>
    <div class="hb-col-sm">
        <div class="hb-row">
            <div class="hb-col-sm">
      <h2><?php esc_html_e('Used technologies','honeybadger-it');?></h2>
        <a class="hb-no-box" href="<?php echo esc_url("https://oauth.net/2/");?>" target="_blank" title="<?php echo esc_attr("Oauth2");?>"><img src="<?php echo esc_url(plugin_dir_url( __DIR__ )."images/oauth-2-sm.png");?>" class="hb-tech-imgs" /></a>
        <a class="hb-no-box" href="<?php echo esc_url("https://www.google.com/search?q=ssl+certificate");?>" target="_blank" title="<?php echo esc_attr("SSL");?>"><img src="<?php echo esc_url(plugin_dir_url( __DIR__ )."images/ssl.png");?>" class="hb-tech-imgs" /></a>
        <a class="hb-no-box" href="<?php echo esc_url("https://developer.wordpress.org/rest-api/");?>" target="_blank" title="<?php echo esc_attr("Worpdpress REST API");?>"><img src="<?php echo esc_url(plugin_dir_url( __DIR__ )."images/wp_rest_api.png");?>" class="hb-tech-imgs" /></a>
        <a class="hb-no-box" href="<?php echo esc_url("https://woocommerce.com/");?>" target="_blank" title="<?php echo esc_attr("Woocommerce");?>"><img src="<?php echo esc_url(plugin_dir_url( __DIR__ )."images/woocommerce.png");?>" class="hb-tech-imgs" /></a>
        <a class="hb-no-box" href="<?php echo esc_url("https://wordpress.org/support/article/create-a-network/");?>" target="_blank" title="<?php echo esc_attr("Wordpress Multisite");?>"><img src="<?php echo esc_url(plugin_dir_url( __DIR__ )."images/wp_multisite.png");?>" class="hb-tech-imgs" /></a>
        </div></div>
        <div class="hb-row">
            <div class="hb-col-sm">
        <h2><?php esc_html_e('Demo account','honeybadger-it');?></h2>
        <strong><?php esc_html_e('Username','honeybadger-it');?>:</strong> demo@honeybadger.it <strong><?php esc_html_e('Password','honeybadger-it');?>:</strong> D3m0HoneyB:123
        <br />
        <a style="margin-top: 6px;display: block;" href="<?php echo esc_url("https://".HONEYBADGER_IT_TARGET_SUBDOMAIN.".honeybadger.it/");?>" target="_blank"><?php echo esc_html(__('Try the demo account','honeybadger-it'));?></a>
        </div></div>
        <div class="hb-row">
            <div class="hb-col-sm">

        <a style="margin-top: 6px;display: inline-block;" href="<?php echo esc_url("https://".HONEYBADGER_IT_TARGET_SUBDOMAIN.".honeybadger.it/learn-plugin/");?>" target="_blank"><?php echo esc_html(__('Plugin Docs','honeybadger-it'));?></a>
        | 
        <a style="margin-top: 6px;display: inline-block;" href="<?php echo esc_url("https://".HONEYBADGER_IT_TARGET_SUBDOMAIN.".honeybadger.it/learn-platform/");?>" target="_blank"><?php echo esc_html(__('Platform Docs','honeybadger-it'));?></a>
        </div></div>
    </div>
  </div>
</div>


<hr>
<h2><?php esc_html_e('Status','honeybadger-it');?></h2>

<table class="widefat" id="hb-status-table" cellspacing="0">
    <tbody>
        
        <?php
        if($honeybadger->checkPhpVersion())
        {
        ?>
        <tr class="hb-show hb-green-color">
            <td><span class="dashicons dashicons-media-code"></span></td>
            <td><?php echo esc_html(__('PHP Version','honeybadger-it'));?></td>
            <td><?php echo esc_html(PHP_VERSION);?></td>
            <td><?php echo esc_html(__('Looks good','honeybadger-it'));?></td>
        </tr>
        <?php
        }
        else
        {
            $deal_breaker=true;
        ?>
        <tr class="hb-show hb-red-color">
            <td><span class="dashicons dashicons-media-code"></span></td>
            <td><?php echo esc_html(__('PHP Version','honeybadger-it'));?></td>
            <td><?php echo esc_html(PHP_VERSION);?></td>
            <td><?php echo esc_html(__('PHP 5.4 or greater is required','honeybadger-it'));?> <strong>[<?php echo esc_html(__('Deal Breaker','honeybadger-it'));?>]</strong></td>
        </tr>
        <?php
        }
        if($honeybadger->compareWordpressVersion())
        {
        ?>
        <tr class="hb-show hb-green-color">
            <td><span class="dashicons dashicons-wordpress"></span></td>
            <td><?php echo esc_html(__('WordPress Version','honeybadger-it'));?></td>
            <td><?php echo esc_html($wp_version);?></td>
            <td><?php echo esc_html(__('Looks good','honeybadger-it'));?></td>
        </tr>
        <?php
        }
        else
        {
            $deal_breaker=true;
        ?>
        <tr class="hb-show hb-red-color">
            <td><span class="dashicons dashicons-wordpress"></span></td>
            <td><?php echo esc_html(__('WordPress Version','honeybadger-it'));?></td>
            <td><?php echo esc_html($wp_version);?></td>
            <td><?php echo esc_html(__('The plugin was tested from version 5.8.2','honeybadger-it'));?> <strong>[<?php echo esc_html(__('Deal Breaker','honeybadger-it'));?>]</strong></td>
        </tr>
        <?php
        }
        if($honeybadger->checkRestApiV2())
        {
            ?>
            <tr class="hb-show hb-green-color">
                <td><span class="dashicons dashicons-share"></span></td>
                <td><?php esc_html_e('REST API v2','honeybadger-it');?></td>
                <td><?php esc_html_e('Is Active','honeybadger-it');?></td>
                <td><?php esc_html_e('Looks good','honeybadger-it');?></td>
            </tr>
            <?php
        }
        else
        {
            $deal_breaker=true;
            ?>
            <tr class="hb-show hb-red-color">
                <td><span class="dashicons dashicons-share"></span></td>
                <td><?php esc_html_e('REST API v2','honeybadger-it');?></td>
                <td><?php esc_html_e('Not Active','honeybadger-it');?></td>
                <td><?php esc_html_e('REST API does not seam to work (or not working over https), this is needed for the plugin to work, please activate it. Maybe the ssl certificate is not valid? You can use a self signed certificate too (set in settings curl_ssl_verify to no) for testing purposes','honeybadger-it');?> <strong>[<?php _e('Deal Breaker','honeybadger-it');?>]</strong> <a href="<?php echo esc_url(get_rest_url()."wp/v2");?>" target="_blank"><?php _e('API URL','honeybadger-it');?></a></td>
            </tr>
            <?php
        }
        if($honeybadger->checkWcActivated() && $honeybadger->compareWoocommerceVersion())
        {
        ?>
        <tr class="hb-show hb-green-color">
            <td><span class="dashicons dashicons-cart"></span></td>
            <td><?php esc_html_e('Woocommerce','honeybadger-it');?></td>
            <td><?php echo esc_html(WC_VERSION);?></td>
            <td><?php esc_html_e('Looks good','honeybadger-it');?></td>
        </tr>
        <?php
        }
        else
        {
        ?>
        <tr class="hb-show hb-red-color">
            <td><span class="dashicons dashicons-cart"></span></td>
            <td><?php esc_html_e('Woocommerce','honeybadger-it');?></td>
            <td><?php esc_html_e('Not Active','honeybadger-it');?></td>
            <td><?php esc_html_e('HoneyBadger IT plugin requires Woocommerce to be activated and was tested from version 5.9.0','honeybadger-it');?></td>
        </tr>
        <?php
        }
        if($honeybadger->chechIfSelfSigned() && !$honeybadger->checkIfSSL())
        {
        ?>
        <tr class="hb-show hb-orange-color">
            <td><span class="dashicons dashicons-unlock"></span></td>
            <td><?php esc_html_e('SSL','honeybadger-it');?></td>
            <td><?php esc_html_e('SSL certificate self signed','honeybadger-it');?></td>
            <td><?php esc_html_e('A valid SSL certificate is recommended, you can use a self signed certificate too (set in settings curl_ssl_verify to no) for testing purposes','honeybadger-it');?></td>
        </tr>
        <?php
        }
        else if(!$honeybadger->checkIfSSL())
        {
            $deal_breaker=true;
        ?>
        <tr class="hb-show hb-red-color">
            <td><span class="dashicons dashicons-unlock"></span></td>
            <td><?php esc_html_e('SSL','honeybadger-it');?></td>
            <td><?php esc_html_e('SSL certificate missing','honeybadger-it');?></td>
            <td><?php esc_html_e('A valid SSL certificate is recommended, you can use a self signed certificate too (set in settings curl_ssl_verify to no) for testing purposes','honeybadger-it');?> <strong>[<?php esc_html_e('Deal Breaker','honeybadger-it');?>]</strong></td>
        </tr>
        <?php
        }
        else
        {
        ?>
        <tr class="hb-show hb-green-color">
            <td><span class="dashicons dashicons-lock"></span></td>
            <td><?php esc_html_e('SSL','honeybadger-it');?></td>
            <td><?php esc_html_e('SSL certificate is valid','honeybadger-it');?></td>
            <td><?php esc_html_e('Looks good','honeybadger-it');?></td>
        </tr>
        <?php
        }
        if($honeybadger_running_cgi)
        {
        ?>
        <tr class="hb-show hb-red-color">
            <td><span class="dashicons dashicons-admin-generic"></span></td>
            <td><?php esc_html_e('Running CGI','honeybadger-it');?></td>
            <td><?php esc_html_e('Yes','honeybadger-it');?></td>
            <td><?php esc_html_e('There are some issues with the HTTP_AUTHORIZATION header, might be fixed by adding in .htaccess the following code:','honeybadger-it');?> <a href="<?php echo esc_url('https://developer.wordpress.org/rest-api/frequently-asked-questions/#apache');?>" target="_blank">click here</a></td>
        </tr>
        <?php
        }
        else
        {
        ?>
        <tr class="hb-show hb-green-color">
            <td><span class="dashicons dashicons-admin-generic"></span></td>
            <td><?php esc_html_e('Running CGI','honeybadger-it');?></td>
            <td><?php esc_html_e('No','honeybadger-it');?></td>
            <td><?php esc_html_e('Looks good','honeybadger-it');?></td>
        </tr>
        <?php
        }
        if($honeybadger_running_windows)
        {
        ?>
        <tr class="hb-show hb-red-color">
            <td><span class="dashicons dashicons-desktop"></span></td>
            <td><?php esc_html_e('Running under Windows','honeybadger-it');?></td>
            <td><?php esc_html_e('Yes','honeybadger-it');?></td>
            <td><?php esc_html_e('The OAuth2 server is limited under Windows OS','honeybadger-it');?></td>
        </tr>
        <?php
        }
        else
        {
        ?>
        <tr class="hb-show hb-green-color">
            <td><span class="dashicons dashicons-desktop"></span></td>
            <td><?php esc_html_e('Running under Windows','honeybadger-it');?></td>
            <td><?php esc_html_e('No','honeybadger-it');?></td>
            <td><?php esc_html_e('Looks good','honeybadger-it');?></td>
        </tr>
        <?php
        }
        if(!$honeybadger_curl_exists)
        {
        ?>
        <tr class="hb-show hb-red-color">
            <td><span class="dashicons dashicons-media-code"></span></td>
            <td><?php esc_html_e('cUrl Version','honeybadger-it');?></td>
            <td><?php esc_html_e('Not installed','honeybadger-it');?></td>
            <td><?php esc_html_e('cUrl doesn\'t seam to be installed, contact your hosting provider to install it','honeybadger-it');?></td>
        </tr>
        <?php
        }
        else
        {
            $curl_version=curl_version();
            $honeybadger_curl_version=$curl_version['version_number'];
        ?>
        <tr class="hb-show hb-green-color">
            <td><span class="dashicons dashicons-media-code"></span></td>
            <td><?php esc_html_e('cUrl Version','honeybadger-it');?></td>
            <td><?php echo esc_html($honeybadger_curl_version);?></td>
            <td><?php esc_html_e('Looks good','honeybadger-it');?></td>
        </tr>
        <?php
        }
        ?>
        <tr class="hb-show hb-green-color">
            <td><span class="dashicons dashicons-privacy"></span></td>
            <td><?php esc_html_e('OpenSSL Version','honeybadger-it');?></td>
            <td><?php echo esc_html(OPENSSL_VERSION_TEXT);?></td>
            <td><?php esc_html_e('Looks good','honeybadger-it');?></td>
        </tr>
        <?php
        if($honeybadger_online)
        {
        ?>
        <tr class="hb-show hb-green-color">
            <td><img id="honeybadger_status" src="<?php echo esc_url(plugin_dir_url( __DIR__ )."images/honeybadger_big_green.png");?>" /></td>
            <td><?php esc_html_e('HoneyBadger Server','honeybadger-it');?></td>
            <td><?php esc_html_e('Online','honeybadger-it');?> (<?php esc_html_e('Ping','honeybadger-it');?>: <?php echo esc_html($honeybadger_online_time);?>s)</td>
            <td><?php esc_html_e('Looks good','honeybadger-it');?></td>
        </tr>
        <?php
        }
        else
        {
            $deal_breaker=true;
        ?>
        <tr class="hb-show hb-red-color">
            <td><img id="honeybadger_status" src="<?php echo esc_url(plugin_dir_url( __DIR__ )."images/honeybadger_big_red.png");?>" /></td>
            <td><?php esc_html_e('HoneyBadger Server','honeybadger-it');?></td>
            <td><?php esc_html_e('Offline','honeybadger-it');?></td>
            <td><?php esc_html_e('Please try again later','honeybadger-it');?> <strong>[<?php esc_html_e('Deal Breaker','honeybadger-it');?>]</strong></td>
        </tr>
        <?php
        }
        ?>
    </tbody>
</table>
<hr/>
<a class="button-primary" style="margin-bottom:20px;" href="javascript:void(0);" onClick="javascript:jQuery('#php_info').toggle();"><?php esc_html_e("Toggle PHP Info",'honeybadger-it');?></a>
<div id="php_info" class="php_info" style="display:none;">
    <?php
    ob_start();
    phpinfo();
    $pinfo = ob_get_contents();
    ob_get_clean();
    $xml = new DOMDocument();
    $xml->loadHtml($pinfo);
    $xpath = new DOMXPath($xml);

    $html = '';
    foreach ($xpath->query('body') as $node)
    {
        $html .= $xml->saveXML($node);
    }
    echo $html;// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    ?>
</div>
<?php
if($deal_breaker)
{
    $script_output='jQuery(document).ready(function(){disableHonebadgerCreateButton()});';
    wp_add_inline_script('honeybadger-status-page-ajax-script',$script_output,'after');
}

?>