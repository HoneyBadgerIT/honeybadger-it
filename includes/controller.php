<?php
/**
 * @package    Honeybadger_IT
 * @subpackage Honeybadger_IT/admin
 * @author     Claudiu Maftei <claudiu@honeybadger.it>
 */
if ( ! defined( 'ABSPATH' ) ) {
    require_once("../../../../wp-load.php");
}
if ( ! current_user_can( 'manage_options' ) ) {
    return;
}
$action=isset($_GET['action'])?$_GET['action']:"";
if(isset($_POST['action']))
	$action=$_POST['action'];

if($action=="create_user_role")
{
	require_once plugin_dir_path( __FILE__ ) . 'honeybadger.php';
	$honeybadger=new honeybadger;
	$honeybadger->createUserRoleAndUser();
}
if($action=="create_honeybadger_connection")
{
    require_once plugin_dir_path( __FILE__ ) . 'honeybadger.php';
    $honeybadger=new honeybadger;
    $honeybadger->createHoneybadgerConnection();
}
if($action=="refresh_honeybadger_connection")
{
    require_once plugin_dir_path( __FILE__ ) . 'honeybadger.php';
    $honeybadger=new honeybadger;
    $honeybadger->refreshHoneybadgerConnection();
}
?>