<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly   
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
 * Description:       Connect your Woocommerce shop with the HoneyBadger.IT platform and enjoy many features to better manage your company. Included features are custom order statuses, custom PDF attachments, email templates, product variant images, manage your suppliers, create supplier orders, create WC orders and many other features.
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
define( 'HONEYBADGER_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'HONEYBADGER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

$upload_dir = wp_upload_dir();
if(!empty($upload_dir['basedir']))
    define( 'HONEYBADGER_UPLOADS_PATH', $upload_dir['basedir'].'/honeybadger-it/' );
else
    return;
/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-honeybadger-it-activator.php
 */
function honeybadger_it_activate_the_honeybadger_it_plugin() {
    add_option("honeybadger_the_honeybadger_it_activation_is_done","yes");
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-honeybadger-it-activator.php';
    $activator=new HoneyBadgerIT\Honeybadger_IT_Activator;
    $activator->activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-honeybadger-it-deactivator.php
 */
function honeybadger_it_deactivate_the_honeybadger_it_plugin() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-honeybadger-it-deactivator.php';
    $deactivator=new HoneyBadgerIT\Honeybadger_IT_Deactivator;
    $deactivator->deactivate();
}

register_activation_hook( __FILE__, 'honeybadger_it_activate_the_honeybadger_it_plugin' );
register_deactivation_hook( __FILE__, 'honeybadger_it_deactivate_the_honeybadger_it_plugin' );

// Creating table whenever a new blog is created
function honeybadger_new_blog_honeybadger_it_plugin_check($blog_id, $user_id, $domain, $path, $site_id, $meta) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-honeybadger-it-activator.php';
    $activator=new HoneyBadgerIT\Honeybadger_IT_Activator;
    $activator->on_create_blog($blog_id, $user_id, $domain, $path, $site_id, $meta);
}
add_action( 'wpmu_new_blog', 'honeybadger_new_blog_honeybadger_it_plugin_check', 10, 6 );

// Deleting the table whenever a blog is deleted
function honeybadger_on_delete_blog_honeybadger_it_plugin_check( $tables ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-honeybadger-it-activator.php';
    $activator=new HoneyBadgerIT\Honeybadger_IT_Activator;
    return $activator->on_delete_blog($tables);
}
add_filter( 'wpmu_drop_tables', 'honeybadger_on_delete_blog_honeybadger_it_plugin_check' );

function honeybadger_it_check_version_plugin_check() {
    if (HONEYBADGER_IT_VERSION !== get_option('HONEYBADGER_IT_VERSION')){
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-honeybadger-it-activator.php';
        $activator=new HoneyBadgerIT\Honeybadger_IT_Activator;
        $activator->versionChanges();
    }
}
add_action('plugins_loaded', 'honeybadger_it_check_version_plugin_check');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-honeybadger-it.php';
/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function honeybadger_run_honeybadger_it_plugin_start() {

    $plugin = new HoneyBadgerIT\Honeybadger_IT();
    $plugin->run();
    
}
function honeybadger_run_honeybadger_it_plugin_admin_main_page(){
    require_once dirname( __FILE__ )  . '/admin/partials/honeybadger-it-admin-display.php';
}
add_action('admin_menu', 'honeybadger_admin_menu_honeybadger_it_plugin_menu_items');
function honeybadger_admin_menu_honeybadger_it_plugin_menu_items()
{
    require_once dirname( __FILE__ )  . '/admin/partials/honeybadger_svg.php';
    add_menu_page( "HoneyBadger.IT", "HoneyBadger.IT", "administrator", "honeybadger-it", "honeybadger_run_honeybadger_it_plugin_admin_main_page", $honeybadger_icon, 54.9);
    add_submenu_page( "honeybadger-it", __('Status','honeybadger-it'), __('Status','honeybadger-it'), "administrator", "honeybadger-it","honeybadger_run_honeybadger_it_plugin_admin_main_page",1);
    add_submenu_page( "honeybadger-it", __('Settings','honeybadger-it'), __('Settings','honeybadger-it'), "administrator", "honeybadger-settings","honeybadger_run_honeybadger_it_plugin_admin_main_page",2);
    add_submenu_page( "honeybadger-it", __('REST API','honeybadger-it'), __('REST API','honeybadger-it'), "administrator", "honeybadger-rest-api","honeybadger_run_honeybadger_it_plugin_admin_main_page",3);
    add_submenu_page( "honeybadger-it", __('Tools','honeybadger-it'), __('Tools','honeybadger-it'), "administrator", "honeybadger-tools","honeybadger_run_honeybadger_it_plugin_admin_main_page",4);
}

honeybadger_run_honeybadger_it_plugin_start();

function honeybadger_it_plugin_rest_oauth2_load() {
    add_filter( 'determine_current_user', 'honeybadger_plugin_determine_current_user_oauth2' );
}
add_action( 'init', 'honeybadger_it_plugin_rest_oauth2_load' );

add_filter( 'plugin_action_links', 'honeybadger_show_plugin_admin_settings_link', 10, 2 );

function honeybadger_show_plugin_admin_settings_link( $links, $file ) 
{
    if ( $file == plugin_basename(dirname(__FILE__) . '/honeybadger-it.php') ) 
    {
        $links = array_merge(array('<a href="'.esc_url(admin_url().'admin.php?page=honeybadger-it').'">'.__('Settings','honeybadger-it').'</a>'),$links);
    }
    return $links;
}

function honeybadger_sanitize_file_name_from_path($path="")
{
    if($path!="")
    {
        $filename=sanitize_file_name(basename($path));
        $dir=dirname($path);
        $path=$dir."/".$filename;
    }
    return $path;
}

global $honeybadger_it_plugin_admin_config;
global $honeybadger_it_plugin_admin_config_front;
global $wpdb;
$honeybadger_it_plugin_admin_config=new stdClass;
$honeybadger_it_plugin_admin_config_front=new stdClass;

if(get_option("honeybadger_the_honeybadger_it_activation_is_done")=="yes")
{
    $sql=$wpdb->prepare("select * from ".$wpdb->prefix."honeybadger_config where %d",1);
    $results=$wpdb->get_results($sql);
    if(is_array($results)){
        foreach($results as $r){
            if(!isset($honeybadger_it_plugin_admin_config->{$r->config_name}))
                $honeybadger_it_plugin_admin_config->{$r->config_name}=$r->config_value;
            if(!isset($honeybadger_it_plugin_admin_config_front->{$r->config_name}) && $r->show_front==1)
                $honeybadger_it_plugin_admin_config_front->{$r->config_name}=$r->config_value;
        }
    }
}
function honeybadger_plugin_determine_current_user_oauth2()
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
            $sql=$wpdb->prepare("select at.access_token, oc.user_id from ".$wpdb->prefix."honeybadger_oauth_access_tokens at 
            inner join ".$wpdb->prefix."honeybadger_oauth_clients oc on oc.client_id=at.client_id
            where at.access_token=%s and oc.user_id=%d and at.expires>=%s",
            array($token,$user_id,date("Y-m-d H:i:s")));
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
function honeybadger_it_plugin_rest_oauth2_force_reauthentication() {
    
    if ( is_user_logged_in() ) {
        return;
    }
    global $current_user;
    $current_user = null;

    wp_get_current_user();
}
add_action( 'init', 'honeybadger_it_plugin_rest_oauth2_force_reauthentication', 100 );

function honeybadgerItPluginTestRestRoute( WP_REST_Request $request ) {
    $has_user = $request->get_param( 'has_user' );
    include ABSPATH."wp-includes/version.php";
    $response = new WP_REST_Response(array("status"=>"ok","has_user"=>sanitize_text_field($has_user),"wp_version"=>sanitize_text_field($wp_version),"wc_version"=>sanitize_text_field(WC_VERSION),"hb_version"=>sanitize_text_field(HONEYBADGER_IT_VERSION)));
    $response->set_status(200);

    return $response; 
}
function honeybadgerItPluginValidateCallback($data)
{
    if(is_numeric($data))
        return true;
    else
        return false;
}
add_action( 'rest_api_init', function () {
  register_rest_route( 'honeybadger-it/v1', '/ping/(?P<id>\d+)', array(
    'methods' => array('GET','POST'),
    'callback' => 'honeybadgerItPluginTestRestRoute',
    'args' => array(
      'id' => array(
        'validate_callback' => 'honeybadgerItPluginValidateCallback'
      ),
    ),
    'permission_callback' => function () {
      return current_user_can( 'use_honeybadger_api' );
    }
  ) );
} );
if(isset($honeybadger_it_plugin_admin_config->setup_step) && in_array($honeybadger_it_plugin_admin_config->setup_step,array(1,2,3)))
{
    function honeybadger_register_rest_routes() {
        require_once plugin_dir_path( __FILE__ )  . 'includes/rest-controller.php';
        $controller = new HoneyBadgerIT\HoneyBadger_REST_Controller();
        $controller->register_routes();
    }
     
    add_action( 'rest_api_init', 'honeybadger_register_rest_routes' );
    add_action( 'init', 'honeybadger_register_honeybadger_it_plugin_order_statuses' );
    function honeybadger_register_honeybadger_it_plugin_order_statuses() {
        global $wpdb;
        $default_statuses=array('wc-pending','wc-processing','wc-on-hold','wc-completed','wc-cancelled','wc-refunded','wc-failed','wc-checkout-draft');
        $sql="select * from ".$wpdb->prefix."honeybadger_custom_order_statuses where custom_order_status not in (".implode(', ', array_fill(0, count($default_statuses), '%s')).")";
        $query = call_user_func_array(array($wpdb, 'prepare'), array_merge(array($sql), $default_statuses));
        $results=$wpdb->get_results($query);
        if(is_array($results))
        {
            foreach($results as $r)
            {
                register_post_status( $r->custom_order_status, array(
                    'label'                     => $r->custom_order_status_title,
                    'public'                    => true,
                    'exclude_from_search'       => false,
                    'show_in_admin_all_list'    => true,
                    'show_in_admin_status_list' => true,
                    'label_count'               => _n_noop( $r->custom_order_status_title.' <span class="count">(%s)</span>', $r->custom_order_status_title.'<span class="count">(%s)</span>', 'honeybadger-it' )
                ) );
            }
        }
        
    }
    add_filter( 'wc_order_statuses', 'honeybadger_it_plugin_new_wc_order_statuses' );
    function honeybadger_it_plugin_new_wc_order_statuses( $order_statuses ) {
        global $wpdb;
        $default_statuses=array('wc-pending','wc-processing','wc-on-hold','wc-completed','wc-cancelled','wc-refunded','wc-failed','wc-checkout-draft');
        $sql="select * from ".$wpdb->prefix."honeybadger_custom_order_statuses where custom_order_status not in (".implode(', ', array_fill(0, count($default_statuses), '%s')).")";
        $query = call_user_func_array(array($wpdb, 'prepare'), array_merge(array($sql), $default_statuses));
        $results=$wpdb->get_results($query);
        if(is_array($results))
        {
            foreach($results as $r)
            {
                $order_statuses[$r->custom_order_status] = $r->custom_order_status_title;
            }
        }
        return $order_statuses;
    }
    function honeybadger_it_plugin_admin_footer_function() {
        global $wpdb, $post_type;
        if ( $post_type == 'shop_order' ) {
            $sql=$wpdb->prepare("select * from ".$wpdb->prefix."honeybadger_custom_order_statuses where %d",1);
            $results=$wpdb->get_results($sql);
            if(is_array($results))
            {
                $sql=$wpdb->prepare("select config_value from ".$wpdb->prefix."honeybadger_config where config_name=%s",'use_status_colors_on_wc');
                $row=$wpdb->get_row($sql);
                if(isset($row->config_value) && $row->config_value=="yes")
                {
                    $data_css='';
                    foreach($results as $status)
                    {
                        $data_css.="
                        .order-status.status-".esc_html(str_ireplace('wc-','',$status->custom_order_status))."{
                        background: ".esc_html($status->bg_color).";
                        color: ".esc_html($status->txt_color).";
                        }";
                    }
                    wp_register_style( 'honeybadger_it_css_setup_display_footer_section_handler', false );
                    wp_enqueue_style( 'honeybadger_it_css_setup_display_footer_section_handler' );
                    wp_add_inline_style( 'honeybadger_it_css_setup_display_footer_section_handler', $data_css );
                }
                $default_order_statuses=array('wc-pending','wc-processing','wc-on-hold','wc-completed','wc-cancelled','wc-refunded','wc-failed','wc-checkout-draft');
                $data_js='jQuery(document).ready(function() {';
                foreach($results as $r)
                {
                    if(!in_array($r->custom_order_status,$default_order_statuses))
                    {
                        $data_js.="jQuery('<option>').val('mark_".esc_attr(str_replace("wc-","",$r->custom_order_status))."').text('".esc_attr(__('Change status to','honeybadger-it').' '.str_replace("wc-","",$r->custom_order_status))."').appendTo(\"select[name='action']\");";
                        $data_js.="jQuery('<option>').val('mark_".esc_attr(str_replace("wc-","",$r->custom_order_status))."').text('".esc_attr(__('Change status to','honeybadger-it').' '.str_replace("wc-","",$r->custom_order_status))."').appendTo(\"select[name='action2']\");";
                    }
                }
                $data_js.='});';
                wp_register_script( 'honeybadger_it_js_footer_section_inline_script_handler', '' );
                wp_enqueue_script( 'honeybadger_it_js_footer_section_inline_script_handler' );
                wp_add_inline_script("honeybadger_it_js_footer_section_inline_script_handler",$data_js);
            }
        }
    }
    add_action('admin_footer', 'honeybadger_it_plugin_admin_footer_function');
    function honeybadger_it_plugin_filter_wc_get_template( $template_path, $template_name, $args ) {
        global $wpdb;
        $advanced_styles=array('emails/email-header.php','emails/email-footer.php','emails/email-styles.php','emails/email-addresses.php','emails/email-customer-details.php','emails/email-downloads.php','emails/email-order-details.php','emails/email-order-items.php');
        if(in_array($template_name,$advanced_styles))
            $honeybadger_emails_dir = HONEYBADGER_UPLOADS_PATH.'emails';
        else
            $honeybadger_emails_dir = HONEYBADGER_PLUGIN_PATH.'includes/emails';
        $sql=$wpdb->prepare("select * from ".$wpdb->prefix."honeybadger_wc_emails where template=%s and enabled=%d",array(esc_sql(basename($template_name)),1));
        $result=$wpdb->get_row($sql);
        if(isset($result->id) && $result->id>0)
        {
            if(file_exists($honeybadger_emails_dir."/".basename($template_name)))
                $template_path=$honeybadger_emails_dir."/".basename($template_name);
        }
        return $template_path;
    };
    add_filter( 'wc_get_template', 'honeybadger_it_plugin_filter_wc_get_template', 999, 3 );
    function honeybadger_it_plugin_change_email_subject( $subject, $order, $emailer) {
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
        $sql=$wpdb->prepare("select * from ".$wpdb->prefix."honeybadger_wc_emails where wc_status=%s and enabled=%d",array($status,1));
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
    add_filter('woocommerce_email_subject_new_order', 'honeybadger_it_plugin_change_email_subject', 1, 99);
    add_filter('woocommerce_email_subject_customer_processing_order', 'honeybadger_it_plugin_change_email_subject', 1, 99);
    add_filter('woocommerce_email_subject_customer_completed_order', 'honeybadger_it_plugin_change_email_subject', 1, 99);
    add_filter('woocommerce_email_subject_customer_invoice', 'honeybadger_it_plugin_change_email_subject', 1, 99);
    add_filter('woocommerce_email_subject_customer_note', 'honeybadger_it_plugin_change_email_subject', 1, 99);
    add_filter('woocommerce_email_subject_customer_new_account', 'honeybadger_it_plugin_change_email_subject', 1, 99);
    add_filter('woocommerce_email_subject_cancelled_order', 'honeybadger_it_plugin_change_email_subject', 1, 99);
    add_filter('woocommerce_email_subject_failed_order', 'honeybadger_it_plugin_change_email_subject', 1, 99);
    add_filter('woocommerce_email_subject_customer_on_hold_order', 'honeybadger_it_plugin_change_email_subject', 1, 99);
    add_filter('woocommerce_email_subject_customer_refunded_order', 'honeybadger_it_plugin_change_email_subject', 1, 99);
    add_filter('woocommerce_email_subject_customer_reset_password', 'honeybadger_it_plugin_change_email_subject', 1, 99);
    add_filter('woocommerce_email_subject_customer_invoice_paid', 'honeybadger_it_plugin_change_email_subject', 1, 99);
    add_action('woocommerce_order_status_changed', 'honeybadger_it_plugin_send_custom_email_notifications', 10, 4 );
    function honeybadger_it_plugin_send_custom_email_notifications( $order_id, $old_status, $new_status, $order ){
        global $wpdb;
        $user = wp_get_current_user();
        $roles = ( array ) $user->roles;
        if(isset($_POST['method']) && sanitize_text_field($_POST['method'])=='save_order_status' && defined('REST_REQUEST') && in_array("honeybadger",$roles))
        {
            if(isset($_POST['notify_customer']) && is_numeric($_POST['notify_customer']) && $_POST['notify_customer']==1)
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
                $default_statuses=array('wc-pending','wc-processing','wc-on-hold','wc-completed','wc-cancelled','wc-refunded','wc-failed','wc-checkout-draft');
                $sql="select * from ".$wpdb->prefix."honeybadger_custom_order_statuses where custom_order_status not in (".implode(', ', array_fill(0, count($default_statuses), '%s')).")";
                $query = call_user_func_array(array($wpdb, 'prepare'), array_merge(array($sql), $default_statuses));
                $results=$wpdb->get_results($query);
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
                    add_filter("woocommerce_email_attachments","honeybadger_it_plugin_add_attachments",99);
                }
                if(isset($_POST['static_attachments']))
                {
                    add_filter("woocommerce_email_attachments","honeybadger_it_plugin_add_static_attachments",99);
                }
                $wc_emails[$emails[$new_status]]->trigger( $order_id );
                if(isset($_POST['attachments_to_be_deleted']) && is_array($_POST['attachments_to_be_deleted']))
                {
                    foreach($_POST['attachments_to_be_deleted'] as $file)
                        unlink(honeybadger_sanitize_file_name_from_path($file));
                }
            }
        }
    }
    function honeybadger_it_plugin_add_static_attachments($attachments=array())
    {
        require_once HONEYBADGER_PLUGIN_PATH . '/includes/honeybadger-api.php';
        $honeybadger=new HoneyBadgerIT\API\honeybadgerAPI;
        $files=$honeybadger->get_static_attachments();
        return array_merge($attachments,$files);
    }
    function honeybadger_it_plugin_add_attachments($attachments=array())
    {
        require_once HONEYBADGER_PLUGIN_PATH . '/includes/honeybadger-api.php';
        $honeybadger=new HoneyBadgerIT\API\honeybadgerAPI;
        $files=$honeybadger->save_attachments();
        return array_merge($attachments,$files);
    }
    add_filter('woocommerce_email_classes', 'honeybadger_it_plugin_custom_order_statuses_email_class', 1, 99);
    function honeybadger_it_plugin_custom_order_statuses_email_class($emails)
    {
        global $wpdb;

        $page=isset($_GET['page'])?sanitize_text_field($_GET['page']):"";
        $tab=isset($_GET['tab'])?sanitize_text_field($_GET['tab']):"";
        if($page=='wc-settings' && $tab=='email')
            return $emails;
        $default_statuses=array('wc-pending','wc-processing','wc-on-hold','wc-completed','wc-cancelled','wc-refunded','wc-failed','wc-checkout-draft');
        $sql="select * from ".$wpdb->prefix."honeybadger_custom_order_statuses where custom_order_status not in (".implode(', ', array_fill(0, count($default_statuses), '%s')).")";
        $query = call_user_func_array(array($wpdb, 'prepare'), array_merge(array($sql), $default_statuses));
        $results=$wpdb->get_results($query);
        if(is_array($results))
        {
            require_once __DIR__ . '/includes/wc-email-default.php';
            $default_wc_email_obj=new HoneyBadgerIT\WC_Email_Default_HoneyBadger();
            foreach($results as $r)
                $emails['WC_Email_Default_HoneyBadger_'.$r->custom_order_status] = $default_wc_email_obj;
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
    add_action( 'woocommerce_email', 'honeybadger_it_plugin_unhook_email_sending', 9999 );
    function honeybadger_it_plugin_unhook_email_sending( $email_class ) {
        if(isset($_POST['order_status']) && isset($_POST['method']) && isset($_POST['notify_customer']) && isset($_POST['honeybadger_request']))
        {
            $new_status=strtolower(sanitize_text_field($_POST['order_status']));
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
    add_filter( 'woocommerce_email_headers', 'honeybadger_it_plugin_woocommerce_email_headers', 9999, 3 );
    function honeybadger_it_plugin_woocommerce_email_headers( $headers, $email_id, $order ) {
        global $wpdb;
        $user = wp_get_current_user();
        $roles = ( array ) $user->roles;
        $wc_status=isset($_POST['order_status'])?sanitize_text_field($_POST['order_status']):"";
        if($wc_status!="" && in_array("honeybadger",$roles))
        {
            $sql=$wpdb->prepare("select email_bcc from ".$wpdb->prefix."honeybadger_wc_emails where wc_status=%s and email_bcc!='' and enabled=%d",array($wc_status,1));
            $result=$wpdb->get_row($sql);
            if(isset($result->email_bcc) && $result->email_bcc!="")
                $headers .= "Bcc: ".trim($result->email_bcc)."\r\n";
        }
        return $headers;
    }
    add_filter( 'cron_schedules', 'honeybadger_it_plugin_clean_db_tmp_interval' );
    function honeybadger_it_plugin_clean_db_tmp_interval( $schedules ) {
        $schedules['honeybadger_one_day'] = array(
            'interval' => 60*60*24,
            'display' => __('One Day')
        );
        return $schedules;
    }
    add_action( 'honeybadger_it_plugin_clean_db_tmp', 'honeybadger_it_plugin_clean_db_tmp_run');
    function honeybadger_it_plugin_clean_db_tmp_run()
    {
        $dir = plugin_dir_path( __FILE__ ) . 'attachments/tmp';
        $leave_files = array('index.php', '.','..');

        foreach( glob($dir."/*") as $file ) {
            if( !in_array(basename($file), $leave_files) ){
                unlink($file);
            }
        }
    }
    if ( ! wp_next_scheduled( 'honeybadger_it_plugin_clean_db_tmp' ) ) {
        wp_schedule_event( time(), 'honeybadger_one_day', 'honeybadger_it_plugin_clean_db_tmp' );
    }
    function honeybadger_it_plugin_show_images_sku_in_emails( $args ) {
        global $wpdb;
        global $show_images_in_emails;
        $show_sku_in_emails="";
        $email_image_sizes="";
        $required_values=array('show_images_in_emails','show_sku_in_emails','email_image_sizes');
        $sql="select config_name, config_value from ".$wpdb->prefix."honeybadger_config where config_name in (".implode(", ",array_fill(0,count($required_values),'%s')).")";
        $query=call_user_func_array(array($wpdb,'prepare'),array_merge(array($sql),$required_values));
        $config=$wpdb->get_results($query);
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
    add_filter( 'woocommerce_email_order_items_args', 'honeybadger_it_plugin_show_images_sku_in_emails' );
    function honeybadger_it_plugin_show_images_in_emails_new_line( $name ) {
        global $show_images_in_emails;
        if($show_images_in_emails)
            return '<br /><br />'.$name;
        return $name;
    }
    add_filter( 'woocommerce_order_item_name', 'honeybadger_it_plugin_show_images_in_emails_new_line' );
    if($honeybadger_it_plugin_admin_config->enable_product_variation_extra_images=='yes')
    {
        if(wp_register_script( 'honeybadger-wc-add-to-cart-variation', plugin_dir_url( __FILE__ ) . 'admin/js/wc-add-to-cart-variation.js', array( 'jquery', 'wp-util', 'jquery-blockui', 'wc-add-to-cart-variation' ), false, true ))
        {
            wp_enqueue_script('honeybadger-wc-add-to-cart-variation');
            wp_deregister_script('wc-add-to-cart-variation');
            wp_dequeue_script('wc-add-to-cart-variation');
        }
        add_filter( 'woocommerce_available_variation', 'honeybadger_it_plugin_variation_extra_images',999, 3 );
    }
    function honeybadger_it_plugin_variation_extra_images($details, $product_variable, $variation)
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
                $imgs_html.= sprintf( '<img src="%s" alt="%s" class="wp-post-image" />', esc_url( wc_placeholder_img_src( 'woocommerce_single' ) ), esc_html__( 'Awaiting product image', 'honeybadger-it' ) );
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
    add_action( 'woocommerce_reduce_order_stock', 'honeybadger_it_plugin_woocommerce_reduce_order_stock' );
    add_action( 'woocommerce_restore_order_stock', 'honeybadger_it_plugin_woocommerce_restore_order_stock' );
    function honeybadger_it_plugin_woocommerce_restore_order_stock($order_id)
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
            $sql=$wpdb->prepare("select reduced_stock from ".$wpdb->prefix."honeybadger_product_stock_log where  order_id=%d and product_id=%s",array($order_id,$product_id));
            $result=$wpdb->get_row($sql);
            if(isset($result->reduced_stock))
            {
                $sql=$wpdb->prepare("insert into ".$wpdb->prefix."honeybadger_product_stock_log set
                order_id=%d,
                product_id=%d,
                product_title=%s,
                restored_stock=%d,
                mdate=%d
                on duplicate key update
                restored_stock=%s,
                done=0,
                mdate=%d",array($order_id,$product_id,$product_name,$result->reduced_stock,time(),$result->reduced_stock,time()));
                $wpdb->query($sql);
            }
        }
    }
    function honeybadger_it_plugin_woocommerce_reduce_order_stock($order_id)
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
            $sql=$wpdb->prepare("insert into ".$wpdb->prefix."honeybadger_product_stock_log set
            order_id=%d,
            product_id=%d,
            product_title=%s,
            reduced_stock=%d,
            restored_stock=0,
            mdate=%d
            on duplicate key update
            reduced_stock=%d
            restored_stock=0,
            done=0,
            mdate=%d",array($order_id,$product_id,$product_name,$item_stock_reduced,time(),$item_stock_reduced,time()));
            $wpdb->query($sql);
        }
    }
    function honeybadger_it_plugin_forcelogin_rest_access( $result ) {
        return 1;
    }
    if($honeybadger_it_plugin_admin_config->skip_rest_authentication_errors=='yes')
        add_filter( 'rest_authentication_errors', 'honeybadger_it_plugin_forcelogin_rest_access', 999 );
}
add_action( 'admin_enqueue_scripts', 'honeybadger_it_plugin_settings_update' );
function honeybadger_it_plugin_settings_update( $hook ) {
    if ( 'toplevel_page_honeybadger-it' !== $hook ) {
        return;
    }
    wp_enqueue_script(
        'honeybadger-status-page-ajax-script',
        plugins_url( '/admin/js/status-page.js', __FILE__ ),
        array( 'jquery' ),
        '1.0.0',
        true
    );
    wp_localize_script(
        'honeybadger-status-page-ajax-script',
        'honeybadger_ajax_obj',
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'honeybadger_it_ajax_nonce' ),
            'hb_setup_no_us_msg_1'=>esc_attr(__('Seems that something is wrong, please check the below statuses','honeybadger-it')),
            'hb_setup_no_us_msg_2'=>esc_attr(__('Seems that something is wrong below','honeybadger-it'))
        ),
    );
}
add_action( 'wp_ajax_honeybadger_it_create_user_role', 'honeybadger_it_plugin_create_user_role' );
function honeybadger_it_plugin_create_user_role() {
    check_ajax_referer( 'honeybadger_it_ajax_nonce' );
    require_once plugin_dir_path( __FILE__ )  . 'includes/honeybadger.php';
    $honeybadger=new HoneyBadgerIT\honeybadger;
    $honeybadger->createUserRoleAndUser();
}
add_action( 'wp_ajax_create_honeybadger_connection', 'honeybadger_it_plugin_create_honeybadger_connection' );
function honeybadger_it_plugin_create_honeybadger_connection() {
    check_ajax_referer( 'honeybadger_it_ajax_nonce' );
    require_once plugin_dir_path( __FILE__ )  . 'includes/honeybadger.php';
    $honeybadger=new HoneyBadgerIT\honeybadger;
    $honeybadger->createHoneybadgerConnection();
}

add_action( 'wp_ajax_refresh_honeybadger_connection', 'honeybadger_it_plugin_refresh_honeybadger_connection' );
function honeybadger_it_plugin_refresh_honeybadger_connection() {
    check_ajax_referer( 'honeybadger_it_ajax_nonce' );
    require_once plugin_dir_path( __FILE__ )  . 'includes/honeybadger.php';
    $honeybadger=new HoneyBadgerIT\honeybadger;
    $honeybadger->refreshHoneybadgerConnection();
}

add_action( 'rest_api_init', function () {
  register_rest_route( 'honeybadger-it/v1', '/oauth/', array(
    'methods' => array('GET','POST'),
    'callback' => 'honeybadger_it_plugin_get_oauth_response',
    'args' => array(
      'id' => array(
        'validate_callback' => function () {
          return true;
        }
      ),
    ),
    'permission_callback' => function () {
      return true;
    }
  ) );
} );


function honeybadger_it_plugin_get_oauth_response(WP_REST_Request $request)
{
    if(!empty($request))
    {
        $parameters = $request->get_params();
        $action=isset($parameters['action'])?$parameters['action']:"";
        if($action=='get_oauth2_authorize_content')
        {
            wp_verify_nonce( 'honeybadger_it_oauth_nonce' );
            require_once HONEYBADGER_PLUGIN_PATH . 'includes/oauth2/authorize.php';
        }
        if($action=='get_oauth2_authorize_content_approval')
        {
            wp_verify_nonce( 'honeybadger_it_oauth_nonce' );
            require_once HONEYBADGER_PLUGIN_PATH . 'includes/oauth2/authorize.php';
        }
        if($action=='get_oauth2_token')
        {
            wp_verify_nonce( 'get_oauth2_token' );
            require_once HONEYBADGER_PLUGIN_PATH . 'includes/oauth2/token.php';
        }
        if($action=='get_oauth2_resource')
        {
            wp_verify_nonce( 'honeybadger_it_oauth_nonce' );
            require_once HONEYBADGER_PLUGIN_PATH . 'includes/oauth2/resource.php';
        }
    }
    exit;
}
add_action( 'rest_api_init', function () {
  register_rest_route( 'honeybadger-it/v1', '/manage_downloads/', array(
    'methods' => array('GET','POST'),
    'callback' => 'honeybadger_it_plugin_manage_downloads',
    'args' => array(
      'id' => array(
        'validate_callback' => function () {
          return true;
        }
      ),
    ),
    'permission_callback' => function () {
      return true;
    }
  ) );
} );
function honeybadger_it_plugin_manage_downloads(WP_REST_Request $request)
{
    if(!empty($request))
    {
        require_once HONEYBADGER_PLUGIN_PATH . 'get_attachment.php';
    }
    exit;
}