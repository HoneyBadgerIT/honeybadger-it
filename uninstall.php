<?php
/**
 * @package    Honeybadger_IT
 * @subpackage Honeybadger_IT/admin
 * @author     Claudiu Maftei <claudiu@honeybadger.it>
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

require_once plugin_dir_path( __FILE__ ) . 'includes/class-honeybadger-it-activator.php';
$activator=new Honeybadger_IT_Activator;
$activator->deleteTables();

require_once(ABSPATH.'wp-admin/includes/user.php');
remove_role("honeybadger");
$user_id=username_exists("honeybadger".get_current_blog_id());
if($user_id)
    wp_delete_user($user_id);
if(is_multisite())
{
    require_once ABSPATH . 'wp-admin/includes/ms.php';
    $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
    foreach ( $blog_ids as $blog_id ) {
        $user_id=username_exists("honeybadger".$blog_id);
        wpmu_delete_user( $user_id );
    }
}