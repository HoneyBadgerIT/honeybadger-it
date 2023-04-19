<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://honeybadger.it
 * @since             1.0.0
 * @package           Honeybadger_IT
 *
 * @wordpress-plugin
 * Plugin Name:       HoneyBadger.IT
 * Plugin URI:        http://honeybadger.it
 * Description:       Connect your Woocommerce shop with the HoneyBadger.IT platform and enjoy many features to better manage your company. Included features are custom order statuses, custom PDF atachments, email templates, product variant images, manage your suppliers, create supplier orders, create WC orders and many other features.
 * Version:           1.0.0
 * Requires at least: 5.4
 * Requires PHP:      5.4
 * Author:            Claudiu Maftei
 * Author URI:        http://honeybadger.it
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       honeybadger-it
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'HONEYBADGER_IT_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-honeybadger-it-activator.php
 */
function activate_honeybadger_it() {
    add_option("honeybadger_activation_done","yes");
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-honeybadger-it-activator.php';
    $activator=new Honeybadger_IT_Activator;
    $activator->activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-honeybadger-it-deactivator.php
 */
function deactivate_honeybadger_it() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-honeybadger-it-deactivator.php';
    Honeybadger_IT_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_honeybadger_it' );
register_deactivation_hook( __FILE__, 'deactivate_honeybadger_it' );

// Creating table whenever a new blog is created
function new_blog_honeybadger_it($blog_id, $user_id, $domain, $path, $site_id, $meta) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-honeybadger-it-activator.php';
    $activator=new Honeybadger_IT_Activator;
    $activator->on_create_blog($blog_id, $user_id, $domain, $path, $site_id, $meta);
}
add_action( 'wpmu_new_blog', 'new_blog_honeybadger_it', 10, 6 );

// Deleting the table whenever a blog is deleted
function on_delete_blog_honeybadger_it( $tables ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-honeybadger-it-activator.php';
    $activator=new Honeybadger_IT_Activator;
    return $activator->on_delete_blog($tables);
}
add_filter( 'wpmu_drop_tables', 'on_delete_blog_honeybadger_it' );

function honeybadger_it_check_version() {
    if (HONEYBADGER_IT_VERSION !== get_option('HONEYBADGER_IT_VERSION')){
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-honeybadger-it-activator.php';
        $activator=new Honeybadger_IT_Activator;
        $activator->versionChanges();
    }
}
add_action('plugins_loaded', 'honeybadger_it_check_version');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-honeybadger-it.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_honeybadger_it() {

    $plugin = new Honeybadger_IT();
    $plugin->run();
    
}
function run_honeybadger_it_main_page(){
    require_once dirname( __FILE__ )  . '/admin/partials/honeybadger-it-admin-display.php';
}
add_action('admin_menu', 'admin_menu_honeybadger_it');
function admin_menu_honeybadger_it()
{
    require_once dirname( __FILE__ )  . '/admin/partials/honeybadger_svg.php';
    add_menu_page( "HoneyBadger.IT", "HoneyBadger.IT", "administrator", "honeybadger-it", "run_honeybadger_it_main_page", $honeybadger_icon, 54.9);
    add_submenu_page( "honeybadger-it", __('Status','honeyb'), __('Status','honeyb'), "administrator", "honeybadger-it","run_honeybadger_it_main_page",1);
    add_submenu_page( "honeybadger-it", __('Settings','honeyb'), __('Settings','honeyb'), "administrator", "honeybadger-settings","run_honeybadger_it_main_page",2);
    add_submenu_page( "honeybadger-it", __('REST API','honeyb'), __('REST API','honeyb'), "administrator", "honeybadger-rest-api","run_honeybadger_it_main_page",3);
    add_submenu_page( "honeybadger-it", __('Tools','honeyb'), __('Tools','honeyb'), "administrator", "honeybadger-tools","run_honeybadger_it_main_page",4);
}

run_honeybadger_it();

function rest_oauth1_load() {
    add_filter( 'determine_current_user', 'honeybadger_determine_current_user' );
}
add_action( 'init', 'rest_oauth1_load' );

add_filter( 'plugin_action_links', 'honeybadger_show_settings_link', 10, 2 );

function honeybadger_show_settings_link( $links, $file ) 
{
    if ( $file == plugin_basename(dirname(__FILE__) . '/honeybadger-it.php') ) 
    {
        $links = array_merge(array('<a href="admin.php?page=honeybadger-it">'.__('Settings','mtt').'</a>'),$links);
    }
    return $links;
}

global $honeybadger_config;
global $honeybadger_config_front;
global $wpdb;
$honeybadger_config=new stdClass;
$honeybadger_config_front=new stdClass;

if(get_option("honeybadger_activation_done")=="yes")
{
    $sql="select * from ".$wpdb->prefix."honeybadger_config where 1";
    $results=$wpdb->get_results($sql);
    if(is_array($results)){
        foreach($results as $r){
            if(!isset($honeybadger_config->{$r->config_name}))
                $honeybadger_config->{$r->config_name}=$r->config_value;
            if(!isset($honeybadger_config_front->{$r->config_name}) && $r->show_front==1)
                $honeybadger_config_front->{$r->config_name}=$r->config_value;
        }
    }
}
function honeybadger_determine_current_user()
{
    global $wpdb;
    if(!defined('REST_REQUEST'))
        return;
    $user_id=username_exists("honeybadger".get_current_blog_id());
    if($user_id>0)
    {
        $headers = apache_request_headers();

        foreach ($headers as $header => $value) {
            if($header=="Authorization")
            {
                $token=str_ireplace("Bearer ","",$value);
            }
        }
        if($token!="")
        {
            $sql="select at.access_token, oc.user_id from ".$wpdb->prefix."honeybadger_oauth_access_tokens at 
            inner join ".$wpdb->prefix."honeybadger_oauth_clients oc on oc.client_id=at.client_id
            where at.access_token='".esc_sql($token)."' and oc.user_id='".esc_sql($user_id)."' and at.expires>='".date("Y-m-d H:i:s")."'";
            $result=$wpdb->get_row($sql);
            if(isset($result->user_id) && $result->access_token==$token)
            {
                $user = new WP_User($user_id);
                return $user->ID;
            }
        }
    }
    return 0;
}
function honeybadger_rest_oauth2_force_reauthentication() {
    
    if ( is_user_logged_in() ) {
        return;
    }

    // Force reauthentication
    global $current_user;
    $current_user = null;

    wp_get_current_user();
}
add_action( 'init', 'honeybadger_rest_oauth2_force_reauthentication', 100 );

function honeybadgerTestRestRoute( WP_REST_Request $request ) {
    $has_user = $request->get_param( 'has_user' );
    include ABSPATH."wp-includes/version.php";
    $response = new WP_REST_Response(array("status"=>"ok","has_user"=>$has_user,"wp_version"=>$wp_version,"wc_version"=>WC_VERSION,"hb_version"=>HONEYBADGER_IT_VERSION));
    $response->set_status(200);

    return $response; 
}
function honeybadgerValidateCallback($data)
{
    if(is_numeric($data))
        return true;
    else
        return false;
}
add_action( 'rest_api_init', function () {
  register_rest_route( 'honeybadger-it/v1', '/ping/(?P<id>\d+)', array(
    'methods' => array('GET','POST'),
    'callback' => 'honeybadgerTestRestRoute',
    'args' => array(
      'id' => array(
        'validate_callback' => 'honeybadgerValidateCallback'
      ),
    ),
    'permission_callback' => function () {
      return current_user_can( 'use_honeybadger_api' );
    }
  ) );
} );

if(isset($honeybadger_config->setup_step) && in_array($honeybadger_config->setup_step,array(1,2,3)))
{
    require_once dirname( __FILE__ )  . '/includes/rest-controller.php';
    add_action( 'init', 'register_honeybadger_order_statuses' );
    function register_honeybadger_order_statuses() {
        global $wpdb;
        $default_statuses=array('wc-pending','wc-processing','wc-on-hold','wc-completed','wc-cancelled','wc-refunded','wc-failed');
        $sql="select * from ".$wpdb->prefix."honeybadger_custom_order_statuses where custom_order_status not in ('".implode("','",$default_statuses)."')";
        $results=$wpdb->get_results($sql);
        if(is_array($results))
        {
            foreach($results as $r)
            {
                register_post_status( $r->custom_order_status, array(
                    'label'                     => _x( $r->custom_order_status_title, 'Order status', 'woocommerce' ),
                    'public'                    => true,
                    'exclude_from_search'       => false,
                    'show_in_admin_all_list'    => true,
                    'show_in_admin_status_list' => true,
                    'label_count'               => _n_noop( $r->custom_order_status_title.' <span class="count">(%s)</span>', $r->custom_order_status_title.'<span class="count">(%s)</span>', 'woocommerce' )
                ) );
            }
        }
        
    }
    add_filter( 'wc_order_statuses', 'honeybadger_new_wc_order_statuses' );
    function honeybadger_new_wc_order_statuses( $order_statuses ) {
        global $wpdb;
        $default_statuses=array('wc-pending','wc-processing','wc-on-hold','wc-completed','wc-cancelled','wc-refunded','wc-failed');
        $sql="select * from ".$wpdb->prefix."honeybadger_custom_order_statuses where custom_order_status not in ('".implode("','",$default_statuses)."')";
        $results=$wpdb->get_results($sql);
        if(is_array($results))
        {
            foreach($results as $r)
            {
                $order_statuses[$r->custom_order_status] = _x( $r->custom_order_status_title, 'Order status', 'woocommerce' );
            }
        }
        return $order_statuses;
    }
    function honeybadger_admin_footer_function() {
        global $wpdb, $post_type;
        if ( $post_type == 'shop_order' ) {
            $sql="select * from ".$wpdb->prefix."honeybadger_custom_order_statuses where 1";
            $results=$wpdb->get_results($sql);
            if(is_array($results))
            {
                $sql="select config_value from ".$wpdb->prefix."honeybadger_config where config_name='use_status_colors_on_wc'";
                $row=$wpdb->get_row($sql);
                if(isset($row->config_value) && $row->config_value=="yes")
                {
                    ?>
                    <style type="text/css">
                        <?php
                        foreach($results as $status)
                        {
                            ?>
                            .order-status.status-<?php echo esc_attr(str_ireplace('wc-','',$status->custom_order_status));?>{
                                background: <?php echo $status->bg_color;?>;
                                color: <?php echo $status->txt_color;?>;
                            }
                            <?php
                        }
                        ?>
                    </style>
                    <?php

                }
                $default_order_statuses=array('wc-pending','wc-processing','wc-on-hold','wc-completed','wc-cancelled','wc-refunded','wc-failed');
            ?>
                <script type="text/javascript">
                    jQuery(document).ready(function() {
                        <?php
                        foreach($results as $r)
                        {
                            if(!in_array($r->custom_order_status,$default_order_statuses))
                            {
                                ?>
                                jQuery('<option>').val('mark_<?php echo str_replace("wc-","",$r->custom_order_status);?>').text('<?php _e( 'Change status to '.str_replace("wc-","",$r->custom_order_status), 'woocommerce' ); ?>').appendTo("select[name='action']");
                                jQuery('<option>').val('mark_<?php echo str_replace("wc-","",$r->custom_order_status);?>').text('<?php _e( 'Change status to '.str_replace("wc-","",$r->custom_order_status), 'woocommerce' ); ?>').appendTo("select[name='action2']");   
                                <?php
                            }
                        }
                        ?>
                    });
                </script>
            <?php
            }
        }
    }
    add_action('admin_footer', 'honeybadger_admin_footer_function');
    function honeybadger_filter_wc_get_template( $template_path, $template_name, $args ) {
        global $wpdb;
        $sql="select * from ".$wpdb->prefix."honeybadger_wc_emails where template='".esc_sql(basename($template_name))."' and enabled=1";
        $result=$wpdb->get_row($sql);
        if(isset($result->id) && $result->id>0)
            $template_path=__DIR__."/includes/emails/".basename($template_name);
        return $template_path;
    };
    add_filter( 'wc_get_template', 'honeybadger_filter_wc_get_template', 999, 3 );
    function honeybadger_change_email_subject( $subject, $order, $emailer) {
        global $wpdb;
        $status="wc-".$order->get_status();
        if($emailer->id=='new_order')
            $status='wc-admin-new-order';
        else if($emailer->id=='customer_partially_refunded_order' || $emailer->id=='fully_refunded')
            $status='wc-refunded';
        else if($emailer->id=='customer_invoice')
            $status='wc-customer-invoice';
        else if($emailer->id=='customer_new_account')
            $status='wc-customer-new-account';
        else if($emailer->id=='customer_note')
            $status='wc-customer-note';
        else if($emailer->id=='customer_reset_password')
            $status='wc-customer-reset-password';
        $sql="select * from ".$wpdb->prefix."honeybadger_wc_emails where wc_status='".esc_sql($status)."' and enabled=1";
        $result=$wpdb->get_row($sql);
        if(isset($result->id))
        {
            $subject=$result->subject;
            $subject=str_ireplace("{site_title}", get_bloginfo( 'name', 'display' ) ,$subject);
            $subject=str_ireplace("{customer}", $order->get_billing_first_name() ,$subject);
            $subject=str_ireplace("{customer_full_name}", $order->get_formatted_billing_full_name() ,$subject);
            $subject=str_ireplace("{order_number}", $order->get_id() ,$subject);
            $subject=str_ireplace("{site_url}", get_bloginfo( 'url', 'display' ) ,$subject);
            $subject=str_ireplace("{order_date}", wc_format_datetime( $order->get_date_created() ) ,$subject);

            if(($status=='wc-refunded' && $emailer->partial_refund) || ($status=='wc-customer-invoice' && $order->needs_payment())) 
            {
                $other_subject=$result->other_subject;
                $other_subject=str_ireplace("{site_title}", get_bloginfo( 'name', 'display' ) ,$other_subject);
                $other_subject=str_ireplace("{customer}", $order->get_billing_first_name() ,$other_subject);
                $other_subject=str_ireplace("{customer_full_name}", $order->get_formatted_billing_full_name() ,$other_subject);
                $other_subject=str_ireplace("{order_number}", $order->get_id() ,$other_subject);
                $other_subject=str_ireplace("{site_url}", get_bloginfo( 'url', 'display' ) ,$other_subject);
                $other_subject=str_ireplace("{order_date}", wc_format_datetime( $order->get_date_created() ) ,$other_subject);
                $subject=$other_subject;
            }
        }
        return $subject;
    }
    add_filter('woocommerce_email_subject_new_order', 'honeybadger_change_email_subject', 1, 99);
    add_filter('woocommerce_email_subject_customer_processing_order', 'honeybadger_change_email_subject', 1, 99);
    add_filter('woocommerce_email_subject_customer_completed_order', 'honeybadger_change_email_subject', 1, 99);
    add_filter('woocommerce_email_subject_customer_invoice', 'honeybadger_change_email_subject', 1, 99);
    add_filter('woocommerce_email_subject_customer_note', 'honeybadger_change_email_subject', 1, 99);
    add_filter('woocommerce_email_subject_customer_new_account', 'honeybadger_change_email_subject', 1, 99);
    add_filter('woocommerce_email_subject_cancelled_order', 'honeybadger_change_email_subject', 1, 99);
    add_filter('woocommerce_email_subject_failed_order', 'honeybadger_change_email_subject', 1, 99);
    add_filter('woocommerce_email_subject_customer_on_hold_order', 'honeybadger_change_email_subject', 1, 99);
    add_filter('woocommerce_email_subject_customer_refunded_order', 'honeybadger_change_email_subject', 1, 99);
    add_filter('woocommerce_email_subject_customer_reset_password', 'honeybadger_change_email_subject', 1, 99);
    add_filter('woocommerce_email_subject_customer_invoice_paid', 'honeybadger_change_email_subject', 1, 99);
    add_action('woocommerce_order_status_changed', 'honey_send_custom_email_notifications', 10, 4 );
    function honey_send_custom_email_notifications( $order_id, $old_status, $new_status, $order ){
        global $wpdb;
        $user = wp_get_current_user();
        $roles = ( array ) $user->roles;
        if(isset($_POST['method']) && $_POST['method']=='save_order_status' && defined('REST_REQUEST') && in_array("honeybadger",$roles))
        {
            if(isset($_POST['notify_customer']) && $_POST['notify_customer']==1)
            {
                $emails=array(
                    'pending'=>'WC_Email_New_Order',
                    'cancelled'=>'WC_Email_Cancelled_Order',
                    'failed'=>'WC_Email_Failed_Order',
                    'on-hold'=>'WC_Email_Customer_On_Hold_Order',
                    'processing'=>'WC_Email_Customer_Processing_Order',
                    'completed'=>'WC_Email_Customer_Completed_Order',
                    'refunded'=>'WC_Email_Customer_Refunded_Order'
                );
                $default_statuses=array('wc-pending','wc-processing','wc-on-hold','wc-completed','wc-cancelled','wc-refunded','wc-failed');
                $sql="select * from ".$wpdb->prefix."honeybadger_custom_order_statuses where custom_order_status not in ('".implode("','",$default_statuses)."')";
                $results=$wpdb->get_results($sql);
                if(is_array($results))
                {
                    foreach($results as $r)
                    {
                        $tmp=str_ireplace("wc-","",$r->custom_order_status);
                        $emails[$tmp]='WC_Email_Default_HoneyBadger_'.$r->custom_order_status;
                    }
                }
                $wc_emails = WC()->mailer()->get_emails();
                $customer_email = $order->get_billing_email();
                $wc_emails[$emails[$new_status]]->recipient = $customer_email;
                if(isset($_POST['attachment_ids']))
                {
                    add_filter("woocommerce_email_attachments","honeybadger_add_attachments",99);
                }
                if(isset($_POST['static_attachments']))
                {
                    add_filter("woocommerce_email_attachments","honeybadger_add_static_attachments",99);
                }
                $wc_emails[$emails[$new_status]]->trigger( $order_id );
                if(isset($_POST['attachments_to_be_deleted']) && is_array($_POST['attachments_to_be_deleted']))
                {
                    foreach($_POST['attachments_to_be_deleted'] as $file)
                        unlink($file);
                }
            }
        }
    }
    function honeybadger_add_static_attachments($attachments=array())
    {
        require_once plugin_dir_path( __FILE__ ) . 'includes/honeybadger-api.php';
        $honeybadger=new honeybadgerAPI;
        $files=$honeybadger->get_static_attachments();
        return array_merge($attachments,$files);
    }
    function honeybadger_add_attachments($attachments=array())
    {
        require_once plugin_dir_path( __FILE__ ) . 'includes/honeybadger-api.php';
        $honeybadger=new honeybadgerAPI;
        $files=$honeybadger->save_attachments();
        return array_merge($attachments,$files);
    }
    add_filter('woocommerce_email_classes', 'honeybadger_custom_order_statuses_email_class', 1, 99);
    function honeybadger_custom_order_statuses_email_class($emails)
    {
        global $wpdb;

        $page=isset($_GET['page'])?$_GET['page']:"";
        $tab=isset($_GET['tab'])?$_GET['tab']:"";
        if($page=='wc-settings' && $tab=='email')
            return $emails;
        $default_statuses=array('wc-pending','wc-processing','wc-on-hold','wc-completed','wc-cancelled','wc-refunded','wc-failed');
        $sql="select * from ".$wpdb->prefix."honeybadger_custom_order_statuses where custom_order_status not in ('".implode("','",$default_statuses)."')";
        $results=$wpdb->get_results($sql);
        if(is_array($results))
        {
            foreach($results as $r)
                $emails['WC_Email_Default_HoneyBadger_'.$r->custom_order_status] = include __DIR__ . '/includes/wc-email-default.php';
        }
        return $emails;
    }

    /*
    //currently not used
    function honeybadger_filter_wc_get_template_part( $template, $slug, $name ) { 
        return $template; 
    };
    add_filter( 'wc_get_template_part', 'honeybadger_filter_wc_get_template_part', 10, 5 ); 
    */
    add_action( 'woocommerce_email', 'honeybadger_unhook_email_sending', 9999 );
    function honeybadger_unhook_email_sending( $email_class ) {
        if(isset($_POST['order_status']) && isset($_POST['method']) && isset($_POST['notify_customer']) && isset($_POST['honeybadger_request']))
        {
            $new_status=strtolower($_POST['order_status']);
            if(strlen($new_status)>3)
            {
                if($new_status[0]=="w" && $new_status[1]=="c" && $new_status[2]=="-")
                {
                    $new_status=substr($new_status,3,(strlen($new_status)-2));
                }
            }
            $default_statuses_1=array('pending','processing','on-hold','completed','cancelled','refunded','failed');
            $default_statuses_2=array('pending','processing','on-hold','completed','cancelled','refunded','failed');
            if($new_status!="" && in_array($new_status,$default_statuses_2))
                $default_statuses_2=array($new_status);
            $classes=array('WC_Email_Cancelled_Order','WC_Email_Customer_Completed_Order','WC_Email_Customer_On_Hold_Order','WC_Email_Customer_Processing_Order','WC_Email_Customer_Refunded_Order','WC_Email_Failed_Order','WC_Email_New_Order');
            foreach($default_statuses_1 as $status1)
            {
                foreach($default_statuses_2 as $status2)
                {
                    foreach($classes as $class)
                    {
                        if($status1!=$status2)
                        {
                            remove_action( 'woocommerce_order_status_'.$status1.'_to_'.$status2.'_notification', array( $email_class->emails[$class], 'trigger' ) );
                            remove_action( 'woocommerce_order_status_'.$status2.'_to_'.$status1.'_notification', array( $email_class->emails[$class], 'trigger' ) );
                        }
                    }
                }
            }
            remove_action( 'woocommerce_order_status_completed_notification', array( $email_class->emails['WC_Email_Customer_Completed_Order'], 'trigger' ) );
        }
    }
    add_filter( 'woocommerce_email_headers', 'honeybadger_woocommerce_email_headers', 9999, 3 );
    function honeybadger_woocommerce_email_headers( $headers, $email_id, $order ) {
        global $wpdb;
        $user = wp_get_current_user();
        $roles = ( array ) $user->roles;
        $wc_status=isset($_POST['order_status'])?$_POST['order_status']:"";
        if($wc_status!="" && in_array("honeybadger",$roles))
        {
            $sql="select email_bcc from ".$wpdb->prefix."honeybadger_wc_emails where wc_status='".esc_sql($wc_status)."' and email_bcc!='' and enabled=1";
            $result=$wpdb->get_row($sql);
            if(isset($result->email_bcc) && $result->email_bcc!="")
                $headers .= "Bcc: ".trim($result->email_bcc)."\r\n";
        }
        return $headers;
    }
    add_filter( 'cron_schedules', 'honeybadger_clean_db_tmp_interval' );
    function honeybadger_clean_db_tmp_interval( $schedules ) {
        $schedules['honeybadger_one_day'] = array(
            'interval' => 60*60*24,
            'display' => __('One Day')
        );
        return $schedules;
    }
    add_action( 'honeybadger_clean_db_tmp', 'honeybadger_clean_db_tmp_run');
    function honeybadger_clean_db_tmp_run()
    {
        $dir = plugin_dir_path( __FILE__ ) . 'attachments/tmp';
        $leave_files = array('index.php', '.','..');

        foreach( glob($dir."/*") as $file ) {
            if( !in_array(basename($file), $leave_files) ){
                unlink($file);
            }
        }
    }
    if ( ! wp_next_scheduled( 'honeybadger_clean_db_tmp' ) ) {
        wp_schedule_event( time(), 'honeybadger_one_day', 'honeybadger_clean_db_tmp' );
    }
    function honeybadger_show_images_sku_in_emails( $args ) {
        global $wpdb;
        global $show_images_in_emails;
        $show_sku_in_emails="";
        $email_image_sizes="";
        $sql="select config_name, config_value from ".$wpdb->prefix."honeybadger_config where config_name in ('show_images_in_emails','show_sku_in_emails','email_image_sizes')";
        $config=$wpdb->get_results($sql);
        if(is_array($config))
        {
            foreach($config as $cfg)
            {
                if($cfg->config_name=='show_images_in_emails')
                    $show_images_in_emails=$cfg->config_value;
                if($cfg->config_name=='show_sku_in_emails')
                    $show_sku_in_emails=$cfg->config_value;
                if($cfg->config_name=='email_image_sizes')
                    $email_image_sizes=$cfg->config_value;
            }
            if($show_images_in_emails=='yes' || $show_sku_in_emails=='yes')
            {
                if($show_images_in_emails=='yes')
                {
                    $args['show_image']=true;
                    $w=100;
                    $h=50;
                    $tmp=explode("x",$email_image_sizes);
                    if(isset($tmp[0]))
                        $w=$tmp[0]+0;
                    if(isset($tmp[1]))
                        $h=$tmp[1]+0;
                    $args['image_size']=array( $w, $h );
                }
                if($show_sku_in_emails=='yes')
                    $args['show_sku']=true;
            }
        }
        return $args;
    }
    add_filter( 'woocommerce_email_order_items_args', 'honeybadger_show_images_sku_in_emails' );
    function honeybadger_show_images_in_emails_new_line( $name ) {
        global $show_images_in_emails;
        if($show_images_in_emails)
            return '<br /><br />'.$name;
        return $name;
    }
    add_filter( 'woocommerce_order_item_name', 'honeybadger_show_images_in_emails_new_line' );
    if($honeybadger_config->enable_product_variation_extra_images=='yes')
    {
        if(wp_register_script( 'honeybadger-wc-add-to-cart-variation', plugin_dir_url( __FILE__ ) . 'admin/js/wc-add-to-cart-variation.js', array( 'jquery', 'wp-util', 'jquery-blockui', 'wc-add-to-cart-variation' ), false, true ))
        {
            wp_enqueue_script('honeybadger-wc-add-to-cart-variation');
            wp_deregister_script('wc-add-to-cart-variation');
            wp_dequeue_script('wc-add-to-cart-variation');
        }
        add_filter( 'woocommerce_available_variation', 'honeybadger_variation_extra_images',999, 3 );
    }
    function honeybadger_variation_extra_images($details, $product_variable, $variation)
    {
        if(isset($_POST['product_id']))
        {
            $variable_product = wc_get_product( absint( $_POST['product_id'] ) );
            if ( ! $variable_product ) {
                wp_die();
            }
            $post_thumbnail_id = $variable_product->get_image_id();
            $product_images=$variable_product->get_gallery_image_ids();
            $data_store   = WC_Data_Store::load( 'product' );
            $variation_id = $data_store->find_matching_product_variation( $variable_product, wp_unslash( $_POST ) );
            $variation    = $variation_id ? wc_get_product( $variation_id ) : false;
            $variation_images=array();
            if($variation)
            {
                $post_thumbnail_id = $variation->get_image_id();
                $variation_images=$variation->get_gallery_image_ids();
            }
            if(count($variation_images)>0)
                $product_images=$variation_images;
            $columns           = apply_filters( 'woocommerce_product_thumbnails_columns', 4 );
            $wrapper_classes   = apply_filters(
                'woocommerce_single_product_image_gallery_classes',
                array(
                    'woocommerce-product-gallery',
                    'woocommerce-product-gallery--' . ( $post_thumbnail_id ? 'with-images' : 'without-images' ),
                    'woocommerce-product-gallery--columns-' . absint( $columns ),
                    'images',
                )
            );
            $imgs_html='<div class="'.esc_attr( implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) ) ).'" data-columns="'.esc_attr( $columns ).'" style="opacity: 0; transition: opacity .25s ease-in-out;"><figure class="woocommerce-product-gallery__wrapper">';
            if ( $post_thumbnail_id ) {
                $imgs_html.= wc_get_gallery_image_html( $post_thumbnail_id, true );
            } else {
                $imgs_html.= '<div class="woocommerce-product-gallery__image--placeholder">';
                $imgs_html.= sprintf( '<img src="%s" alt="%s" class="wp-post-image" />', esc_url( wc_placeholder_img_src( 'woocommerce_single' ) ), esc_html__( 'Awaiting product image', 'woocommerce' ) );
                $imgs_html .= '</div>';
            }
            if(count($product_images)>0)
            {
                foreach($product_images as $image_id)
                    $imgs_html.=apply_filters( 'woocommerce_single_product_image_thumbnail_html', wc_get_gallery_image_html( $image_id ), $image_id );
            }
            $imgs_html.='</figure></div>';
            $details['all_images_content']=$imgs_html;
        }
        return $details;
    }
    add_action( 'woocommerce_reduce_order_stock', 'honeybadger_woocommerce_reduce_order_stock' );
    add_action( 'woocommerce_restore_order_stock', 'honeybadger_woocommerce_restore_order_stock' );
    function honeybadger_woocommerce_restore_order_stock($order_id)
    {
        global $wpdb;
        if ( is_a( $order_id, 'WC_Order' ) )
        {
            $order    = $order_id;
            $order_id = $order->get_id();
        }
        else
            $order = wc_get_order( $order_id );
        $order = wc_get_order( $order_id );
        if ( ! $order || 'yes' !== get_option( 'woocommerce_manage_stock' ) )
            return;
        foreach ( $order->get_items() as $item )
        {
            if ( ! $item->is_type( 'line_item' ) )
                continue;
            $product = $item->get_product();
            if ( !$product || !$product->managing_stock() )
                continue;
            $product_name=$product->get_title();
            $product_id=$item->get_product_id();
            $sql="select reduced_stock from ".$wpdb->prefix."honeybadger_product_stock_log where  order_id='".esc_sql($order_id)."' and product_id='".esc_sql($product_id)."'";
            $result=$wpdb->get_row($sql);
            if(isset($result->reduced_stock))
            {
                $sql="insert into ".$wpdb->prefix."honeybadger_product_stock_log set
                order_id='".esc_sql($order_id)."',
                product_id='".esc_sql($product_id)."',
                product_title='".esc_sql($product_name)."',
                restored_stock='".esc_sql($result->reduced_stock)."',
                mdate='".time()."'
                on duplicate key update
                restored_stock='".esc_sql($result->reduced_stock)."',
                done=0,
                mdate='".time()."'";
                $wpdb->query($sql);
            }
        }
    }
    function honeybadger_woocommerce_reduce_order_stock($order_id)
    {
        global $wpdb;
        if ( is_a( $order_id, 'WC_Order' ) )
        {
            $order    = $order_id;
            $order_id = $order->get_id();
        }
        else
            $order = wc_get_order( $order_id );
        $order = wc_get_order( $order_id );
        if ( ! $order || 'yes' !== get_option( 'woocommerce_manage_stock' ) )
            return;
        foreach ( $order->get_items() as $item )
        {
            if ( ! $item->is_type( 'line_item' ) )
                continue;
            $product = $item->get_product();
            $item_stock_reduced = $item->get_meta( '_reduced_stock', true );
            if ( !$item_stock_reduced || !$product || !$product->managing_stock() )
                continue;
            $product_name=$product->get_title();
            $product_id=$item->get_product_id();
            $sql="insert into ".$wpdb->prefix."honeybadger_product_stock_log set
            order_id='".esc_sql($order_id)."',
            product_id='".esc_sql($product_id)."',
            product_title='".esc_sql($product_name)."',
            reduced_stock='".esc_sql($item_stock_reduced)."',
            restored_stock=0,
            mdate='".time()."'
            on duplicate key update
            reduced_stock='".esc_sql($item_stock_reduced)."',
            restored_stock=0,
            done=0,
            mdate='".time()."'";
            $wpdb->query($sql);
        }
    }
function honey_forcelogin_rest_access( $result ) {
    return 1;
}
if($honeybadger_config->skip_rest_authentication_errors=='yes')
    add_filter( 'rest_authentication_errors', 'honey_forcelogin_rest_access', 999 );
}
