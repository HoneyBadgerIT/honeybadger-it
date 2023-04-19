<?php
/**
 * @package    Honeybadger_IT
 * @subpackage Honeybadger_IT/admin
 * @author     Claudiu Maftei <claudiu@honeybadger.it>
 */

global $wp_version;

if ( ! defined( 'ABSPATH' ) ) {
    require_once("../../../../../wp-load.php");
}
if ( ! current_user_can( 'manage_options' ) ) {
    return;
}
$action=isset($_POST['action'])?$_POST['action']:"";
if($action=="restart_the_setup")
{
    $honeybadger->restartTheSetup();
    $honeybadger->config->setup_step=0;
}
if($action=="setup_the_hb_account")
    $honeybadger->setupTheHbAccount();
if($action=="revoke_access")
    $honeybadger->revokeHbAccess();
require_once WP_PLUGIN_DIR . '/honeybadger-it/constants.php';
$setup_step=isset($_GET['setup_step'])?(int)$_GET['setup_step']:0;
if($setup_step=="1")
{
    $honeybadger->setCurrentSetupStep(1);
    $location=get_site_url()."/wp-admin/admin.php?page=honeybadger-it";
    header("Location: $location");
    ?>
    <script type="text/javascript">
        <!--
        window.location.href = "<?php echo get_site_url();?>/wp-admin/admin.php?page=honeybadger-it";
        //-->
    </script>
    <?php
}

if(isset($_GET['show_phpinfo']) && $_GET['show_phpinfo']!=1){
    ?>
    <hr />
    <a class="button-primary" href="?page=honeybadger-it"><< Go Back</a>
    <hr />
    <div class="honeybadger-container">
    <iframe class="honeybadger-responsive-iframe" src="<?php echo get_site_url();?>/wp-content/plugins/honeybadger-it/admin/partials/honeybadger-it-admin-display-status.php?show_phpinfo=1"></iframe>
    </div>
    <?php
    
}
else if(isset($_GET['show_phpinfo']) && $_GET['show_phpinfo']==1){
    phpinfo();
}
else{
    if($honeybadger->config->first_time_installation==1)
    {
        require_once WP_PLUGIN_DIR . '/honeybadger-it/includes/honeybadger-api.php';
        $honeybadgerAPI=new honeybadgerAPI;
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
    if(isset($_GET['msg']) && $_GET['msg']=='created')
    {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e( 'An email verification link was sent to your email address', 'honeyb' ); ?></p>
        </div>
        <script type="text/javascript">
            jQuery('document').ready(function(){
                setTimeout(function(){
                    jQuery('button.notice-dismiss').click(function(){
                        var url=window.location.href;
                        url=url.replace('&msg=created','');
                        window.location.href = url;
                    });
                },500);
            });
        </script>
        <?php
    }
    ?>
    <h2><?php _e('Welcome to HoneyBadger.IT plugin','honeyb');?></h2>
    <p><a href="https://honeybadger.it" target="_blank">HoneyBadger.IT</a> <?php _e('is an online management system for your Woocommerce shop','honeyb');?>. <?php _e('This plugin is used for the communication between your site and the HoneyBadger IT','honeyb');?>. <?php _e('The communication between the parts use Oauth2 protocol for authorization and the Wordpress REST API v2 for data transfer','honeyb');?>. <?php _e('All communications are done over HTTPS, you would need a valid SSL certificate installed or you could use self signed certificate and set curl_ssl_verify to no in settings','honeyb');?>.</p>
    
    <div class="hbcontainer">
      <div class="hb-row">
        <div class="hb-col-sm">
            <?php
            $setup_step=$honeybadger->config->setup_step;
            if($setup_step==0)
            {
            ?>
            <h2><?php _e('Let\'s start','honeyb');?></h2>
            <p><?php _e('First step is to allow access to HoneyBadger.IT server by clicking the below button','honeyb');?>.</p>
            <p><?php _e('A new user role and user will be created "honeybadger" (the user is used by the REST API and will have a random strong password)','honeyb');?>.</p>
            <p><?php _e("By using the HoneyBadger.IT platform you agree with our","honeyb");?>
                <a href="https://honeybadger.it/terms-and-conditions" target="_blank"><?php _e("terms and conditions","honeyb");?></a> <?php _e("and","honeyb");?>
                <a href="https://honeybadger.it/privacy-policy/" target="_blank"><?php _e("privacy policy","honeyb");?></a> 
            </p>
            <?php
            }
            if(($setup_step==1 || $setup_step==2) && $honeybadger->config->is_refresh==0)
            {
            ?>
            <h2><?php _e('One more step','honeyb');?></h2>
            <p><?php _e('Everything looks ok so far, now we need to create your account on HoneyBadger IT Server','honeyb');?>.</p>
            <p><?php _e('You need an email address and a password for this, please complete the below form','honeyb');?>.</p>
            <p><?php _e('If something goes wrong below please try again later by refreshing the page or restart the setup','honeyb');?>.</p>
            <?php
            }
            if($setup_step==3)
            {
            ?>
            <h2><?php _e('HoneyBadger IT connection established','honeyb');?></h2>
            <p><?php _e('Everything looks good','honeyb');?>. <strong><?php _e('If you just created your HoneyBadger.IT account, do not forget to validate your email','honeyb');?>.</strong></p>
            <p><a href="https://<?php echo HONEYBADGER_IT_TARGET_SUBDOMAIN;?>.honeybadger.it/" target="_blank"><?php _e('Access your Honeybadger IT account here','honeyb');?>.</a>
            <?php
            if($honeybadger->config->honeybadger_account_email!="")
            {
                ?>
                <a href="javascript:jQuery('#honeybadger_account_email_a').hide();jQuery('#honeybadger_account_email').show();" id="honeybadger_account_email_a"><small><?php _e("Show account email","honeyb");?></small></a>
                <span id="honeybadger_account_email" style="display:none;"><?php echo $honeybadger->config->honeybadger_account_email;?></span>
                <?php
            }
            ?></p>
            <p><?php _e('If the Oauth2 access token is expired, means you didn\'t use your HoneyBadger IT account for some time now, you need to refresh the access token by allowing access again below','honeyb');?>.</p>
            <p><?php _e('If you want to revoke the access please click the button below','honeyb');?>.</p>
            <?php 
            }
            if($setup_step==4)
            {
            ?>
            <h2><?php _e('HoneyBadger IT connection disabled','honeyb');?></h2>
            <p><?php _e('You have revoked the HoneyBadger IT acccess','honeyb');?>.</p>
            <p><?php _e('Click the button below to allow HoneyBadger IT acccess to your shop again','honeyb');?>.</p>
            <?php 
            }
            ?>
            <div id="hb-one-moment" style="display:none;">
                <h2><?php _e("One moment, redirecting...","honeyb");?></h2>
            </div>
            <div class="hb-left" id="hb-status-update">
                <?php
                $under_https=0;
                if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
                    $under_https=1;
                if($setup_step==0)
                {
                ?>
                    <input<?php echo (($under_https==0)?' disabled="disabled"':"");?> type="button" onClick="javascript:createHoneybadgerUserRole();" class="button-primary hb-setup-no-use" value="<?php echo esc_attr(__('Setup HoneyBadger account','honeyb'));?>" />
                <?php
                    if($under_https==0)
                    {
                        ?>
                        <p class="hb-red-notice"><?php _e("HTTPS is required in order to use this plugin.","honeyb");?></p>
                        <?php
                    }
                }
                $status_changed=0;
                if($setup_step==1)
                {
                    if($honeybadger->config->is_refresh==0)
                    {
                    ?>
                    <form method="post" action="">
                        <input type="hidden" name="action" value="restart_the_setup" />
                    <input type="submit" class="button-secondary" value="<?php echo esc_attr(__("Restart the setup","honeyb"));?>" />
                    </form>
                    <?php
                    }
                    ?>
                    <div class="hb-notice-updated"><p><?php _e("Honeybadger user created with success","honeyb");?></p></div>
                    <input type="button" disabled="disabled" class="button wp-generate-pw hide-if-no-js" value="<?php echo esc_attr(__("Honeybadger IT access authorized with success","honeyb"));?>">
                    <?php
                    $honeybadger->doOauthPingTest();
                    if($honeybadger->config->is_refresh==1)
                        exit;
                    $setup_step=2;
                    $status_changed=1;
                }
                if($setup_step==2)
                {
                    if($status_changed==0)
                    {
                    ?>
                    <form method="post" action="">
                        <input type="hidden" name="action" value="restart_the_setup" />
                    <input type="submit" class="button-secondary" value="<?php echo esc_attr(__("Restart the setup","honeyb"));?>" />
                    </form>
                    <div class="hb-notice-updated"><p><?php _e("Honeybadger user created with success","honeyb");?></p></div>
                    <input type="button" disabled="disabled" class="button wp-generate-pw hide-if-no-js" value="<?php echo esc_attr(__("Honeybadger IT access authorized with success","honeyb"));?>" />
                    <div class="hb-notice-updated">
                        <p><?php _e("Honeybadger Oauth setup with success","honeyb");?></p>
                     </div>
                     <?php
                    }
                    $current_user=wp_get_current_user();
                    $first_name = get_user_meta( $current_user->ID, 'first_name', true );
                    $last_name = get_user_meta( $current_user->ID, 'last_name', true );
                    ?>
                    <div class="hb-form-container">
                        <form method="post" action="" onSubmit="return validateHbAccountCreation();">
                            <input type="hidden" name="action" value="setup_the_hb_account" />
                            <div class="hb-row">
                                <div class="hb-col">
                                    <label><?php _e("First Name","honeyb");?></label>
                                </div>
                                <div class="hb-col">
                                    <input type="text" name="hb_first_name" value="<?php echo esc_attr($first_name);?>" />
                                </div>
                            </div>
                            <div class="hb-row">
                                <div class="hb-col">
                                    <label><?php _e("Last Name","honeyb");?></label>
                                </div>
                                <div class="hb-col">
                                    <input type="text" name="hb_last_name" value="<?php echo esc_attr($last_name);?>" />
                                </div>
                            </div>
                            <div class="hb-row">
                                <div class="hb-col">
                                    <label><?php _e("Email","honeyb");?></label>
                                </div>
                                <div class="hb-col">
                                    <input style="margin-bottom:0px;" type="email" name="hb_email" id="hb_email" value="<?php echo $current_user->user_email;?>" />
                                    <small><?php _e("You will need to confirm your email","honeyb");?></small>
                                </div>
                            </div>
                            <div class="hb-row">
                                <div class="hb-col">
                                    <label><?php _e("Password","honeyb");?></label>
                                </div>
                                <div class="hb-col">
                                    <input type="password" name="hb_password" id="hb_password" value="" /><br />
                                </div>
                            </div>
                            <div class="hb-row">
                                <div class="hb-col">
                                    <small><?php echo __("Please input a Password [6 to 20 characters which contain at least one numeric digit, one uppercase and one lowercase letter]","honeyb");?></small>
                                </div>
                            </div>
                            <div class="hb-row">
                                <div class="hb-col">
                                    <input type="submit" class="button-primary" value="<?php echo esc_attr(__("Create the HoneyBadger IT Account","honeyb"));?>" />
                                </div>
                            </div>
                        </form>
                    </div>
                    <script type="text/javascript">
                        <!--
                        function validateHbEmail(email)
                        {
                            if (/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(email))
                            {
                                return (true)
                            }
                            return (false)
                        }
                        function validateHbPassword(inputtxt) 
                        { 
                            var passw = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,20}$/;
                            if(inputtxt.match(passw)) 
                            { 
                                return true;
                            }
                            else
                            { 
                                return false;
                            }
                        }
                        function validateHbAccountCreation()
                        {
                            if(jQuery('#hb_email').val()=="" || !validateHbEmail(jQuery('#hb_email').val()))
                            {
                                jQuery('#hb_email').css('border-color','red');
                                jQuery('#hb_email').focus();
                                alert('<?php echo __("Please input a valid email address","honeyb");?>');
                                return false;
                            }
                            else
                            {
                                jQuery('#hb_email').css('border-color','#ccc');
                            }
                            if(jQuery('#hb_password').val()=="" || !validateHbPassword(jQuery('#hb_password').val()))
                            {
                                jQuery('#hb_password').css('border-color','red');
                                jQuery('#hb_password').focus();
                                alert('<?php echo __("Please input a Password [6 to 20 characters which contain at least one numeric digit, one uppercase and one lowercase letter]","honeyb");?>');
                                return false;
                            }
                            else
                            {
                                jQuery('#hb_email').css('border-color','#ccc');
                            }
                            return true;
                        }
                        //-->
                    </script>
                    <?php
                }
                if($setup_step==3)
                {
                    if(!$honeybadger->checkAccessTokenExpiry())
                    {
                        ?>
                        <p class="hb-red-notice"><?php _e("Seems that the access tokens expired, please refresh them.","honeyb");?></p>
                        <?php
                    }
                    ?>
                    <input type="button" onClick="javascript:refreshHoneybadgerConnection();" class="button-primary hb-setup-no-use" value="<?php echo esc_attr(__("Refresh the HoneyBadger IT Access Tokens","honeyb"));?>" /><br /><br />
                    <form method="post" action="" id="revoke_form" onSubmit="return confirm('<?php echo esc_attr(__("Are you sure you want to revoke the HoneyBadger IT Access?","honeyb"));?>');">
                        <input type="hidden" name="action" value="revoke_access" />
                        <input type="submit" class="button-secondary" value="<?php echo esc_attr(__("Revoke HoneyBadger IT Access","honeyb"));?>" />
                    </form>
                    <?php
                }
                if($setup_step==4)
                {
                    ?>
                        <input type="button" onClick="javascript:refreshHoneybadgerConnection();" class="button-primary hb-setup-no-use" value="<?php echo esc_attr(__("Refresh the HoneyBadger IT Access Tokens","honeyb"));?>" />
                    <?php
                }

                ?>
            </div>
        </div>
        <div class="hb-col-sm">
            <div class="hb-row">
                <div class="hb-col-sm">
          <h2><?php _e('Used technologies','honeyb');?></h2>
            <a class="hb-no-box" href="https://oauth.net/2/" target="_blank" title="Oauth2"><img src="<?php echo plugin_dir_url( __DIR__ );?>images/oauth-2-sm.png" class="hb-tech-imgs" /></a>
            <a class="hb-no-box" href="https://www.google.com/search?q=ssl+certificate" target="_blank" title="SSL"><img src="<?php echo plugin_dir_url( __DIR__ );?>images/ssl.png" class="hb-tech-imgs" /></a>
            <a class="hb-no-box" href="https://developer.wordpress.org/rest-api/" target="_blank" title="Worpdpress REST API"><img src="<?php echo plugin_dir_url( __DIR__ );?>images/wp_rest_api.png" class="hb-tech-imgs" /></a>
            <a class="hb-no-box" href="https://woocommerce.com/" target="_blank" title="Woocommerce"><img src="<?php echo plugin_dir_url( __DIR__ );?>images/woocommerce.png" class="hb-tech-imgs" /></a>
            <a class="hb-no-box" href="https://wordpress.org/support/article/create-a-network/" target="_blank" title="Wordpress Multisite"><img src="<?php echo plugin_dir_url( __DIR__ );?>images/wp_multisite.png" class="hb-tech-imgs" /></a>
            </div></div>
            <div class="hb-row">
                <div class="hb-col-sm">
            <h2><?php _e('Demo account','honeyb');?></h2>
            <strong><?php _e('Username','honeyb');?>:</strong> demo@honeybadger.it <strong><?php _e('Password','honeyb');?>:</strong> Demo123
            <br />
            <a style="margin-top: 6px;display: block;" href="https://<?php echo HONEYBADGER_IT_TARGET_SUBDOMAIN;?>.honeybadger.it/" target="_blank"><?php _e('Try the demo account','honeyb');?></a>
            </div></div>
            <div class="hb-row">
                <div class="hb-col-sm">

            <a style="margin-top: 6px;display: inline-block;" href="https://<?php echo HONEYBADGER_IT_TARGET_SUBDOMAIN;?>.honeybadger.it/learn-plugin/" target="_blank"><?php _e('Plugin Docs','honeyb');?></a>
            | 
            <a style="margin-top: 6px;display: inline-block;" href="https://<?php echo HONEYBADGER_IT_TARGET_SUBDOMAIN;?>.honeybadger.it/learn-platform/" target="_blank"><?php _e('Platform Docs','honeyb');?></a>
            </div></div>
        </div>
      </div>
    </div>


    <hr>
    <h2><?php _e('Status','honeyb');?></h2>

    <table class="widefat" id="hb-status-table" cellspacing="0">
        <tbody>
            
            <?php
            if($honeybadger->checkPhpVersion())
            {
            ?>
            <tr class="hb-show hb-green-color">
                <td><span class="dashicons dashicons-media-code"></span></td>
                <td><?php _e('PHP Version','honeyb');?></td>
                <td><?php echo PHP_VERSION;?></td>
                <td><?php _e('Looks good','honeyb');?></td>
            </tr>
            <?php
            }
            else
            {
                $deal_breaker=true;
            ?>
            <tr class="hb-show hb-red-color">
                <td><span class="dashicons dashicons-media-code"></span></td>
                <td><?php _e('PHP Version','honeyb');?></td>
                <td><?php echo PHP_VERSION;?></td>
                <td><?php _e('PHP 5.4 or greater is required','honeyb');?> <strong>[<?php _e('Deal Breaker','honeyb');?>]</strong></td>
            </tr>
            <?php
            }
            if($honeybadger->compareWordpressVersion())
            {
            ?>
            <tr class="hb-show hb-green-color">
                <td><span class="dashicons dashicons-wordpress"></span></td>
                <td><?php _e('WordPress Version','honeyb');?></td>
                <td><?php echo $wp_version;?></td>
                <td><?php _e('Looks good','honeyb');?></td>
            </tr>
            <?php
            }
            else
            {
                $deal_breaker=true;
            ?>
            <tr class="hb-show hb-red-color">
                <td><span class="dashicons dashicons-wordpress"></span></td>
                <td><?php _e('WordPress Version','honeyb');?></td>
                <td><?php echo $wp_version;?></td>
                <td><?php _e('The plugin was tested from version 5.8.2','honeyb');?> <strong>[<?php _e('Deal Breaker','honeyb');?>]</strong></td>
            </tr>
            <?php
            }
            if($honeybadger->checkRestApiV2())
            {
                ?>
                <tr class="hb-show hb-green-color">
                    <td><span class="dashicons dashicons-share"></span></td>
                    <td><?php _e('REST API v2','honeyb');?></td>
                    <td><?php _e('Is Active','honeyb');?></td>
                    <td><?php _e('Looks good','honeyb');?></td>
                </tr>
                <?php
            }
            else
            {
                $deal_breaker=true;
                ?>
                <tr class="hb-show hb-red-color">
                    <td><span class="dashicons dashicons-share"></span></td>
                    <td><?php _e('REST API v2','honeyb');?></td>
                    <td><?php _e('Not Active','honeyb');?></td>
                    <td><?php _e('REST API does not seam to work (or not working over https), this is needed for the plugin to work, please activate it. Maybe the ssl certificate is not valid? You can use a self signed certificate too (set in settings curl_ssl_verify to no) for testing purposes','honeyb');?> <strong>[<?php _e('Deal Breaker','honeyb');?>]</strong> <a href="<?php echo get_site_url()."/wp-json/wp/v2";?>" target="_blank"><?php _e('API URL','honeyb');?></a></td>
                </tr>
                <?php
            }
            if($honeybadger->checkWcActivated() && $honeybadger->compareWoocommerceVersion())
            {
            ?>
            <tr class="hb-show hb-green-color">
                <td><span class="dashicons dashicons-cart"></span></td>
                <td><?php _e('Woocommerce','honeyb');?></td>
                <td><?php echo WC_VERSION;?></td>
                <td><?php _e('Looks good','honeyb');?></td>
            </tr>
            <?php
            }
            else
            {
            ?>
            <tr class="hb-show hb-red-color">
                <td><span class="dashicons dashicons-cart"></span></td>
                <td><?php _e('Woocommerce','honeyb');?></td>
                <td><?php _e('Not Active','honeyb');?></td>
                <td><?php _e('HoneyBadger IT plugin requires Woocommerce to be activated and was tested from version 5.9.0','honeyb');?></td>
            </tr>
            <?php
            }
            if($honeybadger->chechIfSelfSigned() && !$honeybadger->checkIfSSL())
            {
            ?>
            <tr class="hb-show hb-orange-color">
                <td><span class="dashicons dashicons-unlock"></span></td>
                <td><?php _e('SSL','honeyb');?></td>
                <td><?php _e('SSL certificate self signed','honeyb');?></td>
                <td><?php _e('A valid SSL certificate is recommended, you can use a self signed certificate too (set in settings curl_ssl_verify to no) for testing purposes','honeyb');?></td>
            </tr>
            <?php
            }
            else if(!$honeybadger->checkIfSSL())
            {
                $deal_breaker=true;
            ?>
            <tr class="hb-show hb-red-color">
                <td><span class="dashicons dashicons-unlock"></span></td>
                <td><?php _e('SSL','honeyb');?></td>
                <td><?php _e('SSL certificate missing','honeyb');?></td>
                <td><?php _e('A valid SSL certificate is recommended, you can use a self signed certificate too (set in settings curl_ssl_verify to no) for testing purposes','honeyb');?> <strong>[<?php _e('Deal Breaker','honeyb');?>]</strong></td>
            </tr>
            <?php
            }
            else
            {
            ?>
            <tr class="hb-show hb-green-color">
                <td><span class="dashicons dashicons-lock"></span></td>
                <td><?php _e('SSL','honeyb');?></td>
                <td><?php _e('SSL certificate is valid','honeyb');?></td>
                <td><?php _e('Looks good','honeyb');?></td>
            </tr>
            <?php
            }
            if($honeybadger_running_cgi)
            {
            ?>
            <tr class="hb-show hb-red-color">
                <td><span class="dashicons dashicons-admin-generic"></span></td>
                <td><?php _e('Running CGI','honeyb');?></td>
                <td><?php _e('Yes','honeyb');?></td>
                <td><?php _e('There are some issues with the HTTP_AUTHORIZATION header, might be fixed by adding in .htaccess the following code:','honeyb');?> <a href="https://developer.wordpress.org/rest-api/frequently-asked-questions/#apache" target="_blank">click here</a></td>
            </tr>
            <?php
            }
            else
            {
            ?>
            <tr class="hb-show hb-green-color">
                <td><span class="dashicons dashicons-admin-generic"></span></td>
                <td><?php _e('Running CGI','honeyb');?></td>
                <td><?php _e('No','honeyb');?></td>
                <td><?php _e('Looks good','honeyb');?></td>
            </tr>
            <?php
            }
            if($honeybadger_running_windows)
            {
            ?>
            <tr class="hb-show hb-red-color">
                <td><span class="dashicons dashicons-desktop"></span></td>
                <td><?php _e('Running under Windows','honeyb');?></td>
                <td><?php _e('Yes','honeyb');?></td>
                <td><?php _e('The OAuth2 server is limited under Windows OS','honeyb');?></td>
            </tr>
            <?php
            }
            else
            {
            ?>
            <tr class="hb-show hb-green-color">
                <td><span class="dashicons dashicons-desktop"></span></td>
                <td><?php _e('Running under Windows','honeyb');?></td>
                <td><?php _e('No','honeyb');?></td>
                <td><?php _e('Looks good','honeyb');?></td>
            </tr>
            <?php
            }
            if(!$honeybadger_curl_exists)
            {
            ?>
            <tr class="hb-show hb-red-color">
                <td><span class="dashicons dashicons-media-code"></span></td>
                <td><?php _e('cUrl Version','honeyb');?></td>
                <td><?php _e('Not installed','honeyb');?></td>
                <td><?php _e('cUrl doesn\'t seam to be installed, contact your hosting provider to install it','honeyb');?></td>
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
                <td><?php _e('cUrl Version','honeyb');?></td>
                <td><?php echo $honeybadger_curl_version;?></td>
                <td><?php _e('Looks good','honeyb');?></td>
            </tr>
            <?php
            }
            ?>
            <tr class="hb-show hb-green-color">
                <td><span class="dashicons dashicons-privacy"></span></td>
                <td><?php _e('OpenSSL Version','honeyb');?></td>
                <td><?php echo OPENSSL_VERSION_TEXT;?></td>
                <td><?php _e('Looks good','honeyb');?></td>
            </tr>
            <?php
            if($honeybadger_online)
            {
            ?>
            <tr class="hb-show hb-green-color">
                <td><img id="honeybadger_status" src="<?php echo plugin_dir_url( __DIR__ );?>images/honeybadger_big_green.png" /></td>
                <td><?php _e('HoneyBadger Server','honeyb');?></td>
                <td><?php _e('Online','honeyb');?> (<?php _e('Ping','honeyb');?>: <?php echo $honeybadger_online_time;?>s)</td>
                <td><?php _e('Looks good','honeyb');?></td>
            </tr>
            <?php
            }
            else
            {
                $deal_breaker=true;
            ?>
            <tr class="hb-show hb-red-color">
                <td><img id="honeybadger_status" src="<?php echo plugin_dir_url( __DIR__ );?>images/honeybadger_big_red.png" /></td>
                <td><?php _e('HoneyBadger Server','honeyb');?></td>
                <td><?php _e('Offline','honeyb');?></td>
                <td><?php _e('Please try again later','honeyb');?> <strong>[<?php _e('Deal Breaker','honeyb');?>]</strong></td>
            </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
    <hr/>
    <a class="button-primary" href="?page=honeybadger-it&show_phpinfo"><?php _e("Show PHP Info","honeyb");?></a>
<?php
    if($deal_breaker)
    {
        ?>
        <script type="text/javascript">
            <!--
            jQuery(document).ready(function(){
                jQuery(".hb-setup-no-use").prop("disabled",true);
                jQuery(".hb-setup-no-use").attr("title","<?php echo esc_attr(__('Seems that something is wrong, please check the below statuses','honeyb'));?>");
                jQuery(".hb-setup-no-use").attr("value","<?php echo esc_attr(__('Seems that something is wrong below','honeyb'));?>");
            });
            //-->
        </script>
        <?php
    }
    else
    {
        ?>
        <script type="text/javascript">
            <!--
            var hb_controller_url="<?php echo plugins_url();?>/honeybadger-it/includes/controller.php";
            function disableHonebadgerCreateButton()
            {
                jQuery(".hb-setup-no-use").prop("disabled",true);
                jQuery(".hb-setup-no-use").attr("title","<?php echo esc_attr(__('Seems that something is wrong, please check the below statuses','honeyb'));?>");
                jQuery(".hb-setup-no-use").attr("value","<?php echo esc_attr(__('Seems that something is wrong below','honeyb'));?>");
            }
            function isHoneybadgerJson(str) {
                try {
                    JSON.parse(str);
                } catch (e) {
                    return false;
                }
                return true;
            }
            function createHoneybadgerUserRole()
            {
                jQuery(".hb-setup-no-use").prop("disabled",true);
                jQuery.ajax({
                  method: "GET",
                  url: hb_controller_url,
                  data: { action: "create_user_role" }
                })
                  .done(function( msg ) {
                    if(isHoneybadgerJson(msg))
                    {
                        var obj = jQuery.parseJSON( msg );
                        var response=obj.msg;
                        var status=obj.status;
                        jQuery("#hb-status-update").html(jQuery("#hb-status-update").html()+response);
                        if(status=="ok")
                        {
                            createHoneybadgerConnection();
                        }
                        else
                        {
                            disableHonebadgerCreateButton();
                        }
                    }
                    else
                    {
                        disableHonebadgerCreateButton();
                    }
                });
            }
            function createHoneybadgerConnection()
            {
                jQuery.ajax({
                  method: "GET",
                  url: hb_controller_url,
                  data: { action: "create_honeybadger_connection" }
                })
                  .done(function( msg ) {
                    if(isHoneybadgerJson(msg))
                    {
                        var obj = jQuery.parseJSON( msg );
                        var response=obj.msg;
                        var status=obj.status;
                        jQuery("#hb-status-update").html(jQuery("#hb-status-update").html()+response);
                        if(status=="ok")
                        {
                            //continue
                        }
                        else
                        {
                            disableHonebadgerCreateButton();
                        }
                    }
                    else
                    {
                        disableHonebadgerCreateButton();
                    }
                });
            }
            function refreshHoneybadgerConnection()
            {
                jQuery(".hb-setup-no-use").prop("disabled",true);
                if(jQuery('#revoke_form').length>0)
                {
                    jQuery('#revoke_form').remove();
                }
                jQuery.ajax({
                  method: "GET",
                  url: hb_controller_url,
                  data: { action: "refresh_honeybadger_connection" }
                })
                  .done(function( msg ) {
                    if(isHoneybadgerJson(msg))
                    {
                        var obj = jQuery.parseJSON( msg );
                        var response=obj.msg;
                        var status=obj.status;
                        jQuery("#hb-status-update").html(jQuery("#hb-status-update").html()+response);
                        if(status=="ok")
                        {
                            //continue
                        }
                        else
                        {
                            disableHonebadgerCreateButton();
                        }
                    }
                    else
                    {
                        disableHonebadgerCreateButton();
                    }
                });
            }
            //-->
        </script>
        <?php
    }
}
?>