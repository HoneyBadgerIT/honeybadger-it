<?php
/**
 * @package    Honeybadger_IT
 * @subpackage Honeybadger_IT/admin
 * @author     Claudiu Maftei <claudiu@honeybadger.it>
 */
namespace HoneyBadgerIT;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly   
$upload_dir = wp_upload_dir();
if(!empty($upload_dir['basedir']))
{
    if(!defined('HONEYBADGER_UPLOADS_PATH')){define( 'HONEYBADGER_UPLOADS_PATH', $upload_dir['basedir'].'/honeybadger-it/' );};
}
else
{
    return;
}

require_once(dirname(__FILE__,2)."/constants.php");


class Honeybadger_IT_Activator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */

  public $sql1=array();
  public $sql2=array();
  public $honeybadgerTables = array();

  function __construct(){
    global $wpdb;
    $this->honeybadgerTables[] = 'honeybadger_config';
    $this->honeybadgerTables[] = 'honeybadger_custom_order_statuses';
    $this->honeybadgerTables[] = 'honeybadger_oauth_access_tokens';
    $this->honeybadgerTables[] = 'honeybadger_oauth_authorization_codes';
    $this->honeybadgerTables[] = 'honeybadger_oauth_clients';
    $this->honeybadgerTables[] = 'honeybadger_oauth_jwt';
    $this->honeybadgerTables[] = 'honeybadger_oauth_refresh_tokens';
    $this->honeybadgerTables[] = 'honeybadger_oauth_scopes';
    $this->honeybadgerTables[] = 'honeybadger_oauth_users';
    $this->honeybadgerTables[] = 'honeybadger_wc_emails';
    $this->honeybadgerTables[] = 'honeybadger_emails';
    $this->honeybadgerTables[] = 'honeybadger_so_emails_tpl';
    $this->honeybadgerTables[] = 'honeybadger_attachments';
    $this->honeybadgerTables[] = 'honeybadger_attachments_tpl';
    $this->honeybadgerTables[] = 'honeybadger_static_attachments';
    $this->honeybadgerTables[] = 'honeybadger_attachments_so_tpl';
    $this->honeybadgerTables[] = 'honeybadger_product_stock_log';

    $this->sql1[]=$wpdb->prepare("INSERT INTO `".$wpdb->prefix."honeybadger_attachments_tpl` (`id`, `title`, `content`, `mdate`) VALUES
        (1, 'Order items Head', '<table style=\"border-collapse: collapse; width: 100%;\" border=\"1\" cellpadding=\"4px\"><colgroup><col style=\"width: 33.3062%;\"><col style=\"width: 33.3062%;\"><col style=\"width: 33.3062%;\"></colgroup><tbody><tr><td>Product</td><td>Quantity</td><td>Price</td></tr></tbody></table>', %d) ON DUPLICATE KEY UPDATE mdate=%d;",array(time(),time()));
    $this->sql1[]=$wpdb->prepare("INSERT INTO `".$wpdb->prefix."honeybadger_attachments_tpl` (`id`, `title`, `content`, `mdate`) VALUES
        (2, 'Order items', '<table style=\"border-collapse: collapse; width: 100%;\" border=\"1\" cellpadding=\"4px\"><colgroup><col style=\"width: 33.3062%;\"><col style=\"width: 33.3062%;\"><col style=\"width: 33.3062%;\"></colgroup><tbody><tr><td>{product_name} (#{product_sku})</td><td>{product_quantity}</td><td>{product_price}</td></tr></tbody></table>', %d) ON DUPLICATE KEY UPDATE mdate=%d;",array(time(),time()));
    $this->sql1[]=$wpdb->prepare("INSERT INTO `".$wpdb->prefix."honeybadger_attachments_tpl` (`id`, `title`, `content`, `mdate`) VALUES
        (3, 'Order items Footer', '<table style=\"border-collapse: collapse; width: 100%;\" border=\"1\"><colgroup><col style=\"width: 33.3062%;\"><col style=\"width: 33.3062%;\"><col style=\"width: 33.3062%;\"></colgroup><tbody><tr><td>&nbsp;</td><td style=\"text-align: right;\">{subtotal_label}</td><td>{subtotal_value}</td></tr></tbody></table>', %d) ON DUPLICATE KEY UPDATE mdate=%d;",array(time(),time()));
    
    $this->sql1[]=$wpdb->prepare("INSERT INTO `".$wpdb->prefix."honeybadger_so_emails_tpl` (`id`, `title`, `content`, `mdate`) VALUES
        (1, 'Order items Head', '<table style=\"table-layout: fixed; color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; width: 100%; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif;\" border=\"1\" cellspacing=\"0\" cellpadding=\"6\"><tbody><tr><td style=\"width: 60%; color: rgb(99, 99, 99); border: 1px solid rgb(229, 229, 229); padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; overflow-wrap: break-word;\" align=\"left\"><strong>Product</strong></td><td style=\"width: 10%; color: rgb(99, 99, 99); border: 1px solid rgb(229, 229, 229); padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; overflow-wrap: break-word;\" align=\"left\"><strong>Qty</strong></td><td style=\"width: 30%; color: rgb(99, 99, 99); border: 1px solid rgb(229, 229, 229); padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; overflow-wrap: break-word;\" align=\"left\"><strong>Price</strong><small>(inc tax)</small></td></tr></tbody></table>', %d) ON DUPLICATE KEY UPDATE mdate=%d;",array(time(),time()));
    $this->sql1[]=$wpdb->prepare("INSERT INTO `".$wpdb->prefix."honeybadger_so_emails_tpl` (`id`, `title`, `content`, `mdate`) VALUES
        (2, 'Order items', '<table style=\"table-layout: fixed; color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; width: 100%; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif;\" border=\"1\" cellspacing=\"0\" cellpadding=\"6\"><tbody><tr><td style=\"width: 60%; color: rgb(99, 99, 99); border: 1px solid rgb(229, 229, 229); padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; overflow-wrap: break-word;\" align=\"left\">{so_item_name}{so_item_sku}{so_supplier_description}</td><td style=\"width: 10%; color: rgb(99, 99, 99); border: 1px solid rgb(229, 229, 229); padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; overflow-wrap: break-word;\" align=\"left\">{so_item_qty}</td><td style=\"width: 30%; color: rgb(99, 99, 99); border: 1px solid rgb(229, 229, 229); padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; overflow-wrap: break-word;\" align=\"left\">{so_item_total}{supplier_currency}</td></tr></tbody></table>', %d) ON DUPLICATE KEY UPDATE mdate=%d;",array(time(),time()));
    $this->sql1[]=$wpdb->prepare("INSERT INTO `".$wpdb->prefix."honeybadger_so_emails_tpl` (`id`, `title`, `content`, `mdate`) VALUES
        (3, 'Order items Footer', '<table style=\"table-layout: fixed; color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; width: 100%; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif;\" border=\"1\" cellspacing=\"0\" cellpadding=\"6\"><tbody><tr><td style=\"width: 60%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; border-right: 0px!important;\" align=\"left\"><strong>Subtotal</strong></td><td style=\"width: 10%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; border-left: 0px!important;\" align=\"left\">&nbsp;</td><td style=\"width: 30%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\">{so_subtotal}{supplier_currency}</td></tr><tr><td style=\"width: 60%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; border-right: 0px!important;\" align=\"left\"><strong>Tax({so_tax}%)</strong></td><td style=\"width: 10%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; border-left: 0px!important;\" align=\"left\">&nbsp;</td><td style=\"width: 30%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\">{so_tax_total}{supplier_currency}</td></tr><tr><td style=\"width: 60%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; border-right: 0px!important;\" align=\"left\"><strong>Postage</strong></td><td style=\"width: 10%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; border-left: 0px!important;\" align=\"left\">&nbsp;</td><td style=\"width: 30%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\">{so_postage_cost}{supplier_currency}</td></tr><tr><td style=\"width: 60%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; border-right: 0px!important;\" align=\"left\"><strong>Total</strong></td><td style=\"width: 10%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; border-left: 0px!important;\" align=\"left\">&nbsp;</td><td style=\"width: 30%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\">{so_total}{supplier_currency}</td></tr></tbody></table>', %d) ON DUPLICATE KEY UPDATE mdate=%d;",array(time(),time()));

    $this->sql1[]=$wpdb->prepare("INSERT INTO `".$wpdb->prefix."honeybadger_so_emails_tpl` (`id`, `title`, `content`, `mdate`) VALUES
        (1, 'Order items Head', '<table style=\"table-layout: fixed; color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; width: 100%; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif;\" border=\"1\" cellspacing=\"0\" cellpadding=\"6\"><tbody><tr><td style=\"width: 35%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\"><strong>Product</strong></td><td style=\"width: 8%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\"><strong>Qty</strong></td><td style=\"width: 16%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\"><strong>Price</strong></td><td style=\"width: 16%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\"><strong>Tax</strong></td><td style=\"width: 26%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\"><strong>Item total</strong></td></tr></tbody></table>', %d) ON DUPLICATE KEY UPDATE mdate=%d;",array(time(),time()));
    $this->sql1[]=$wpdb->prepare("INSERT INTO `".$wpdb->prefix."honeybadger_so_emails_tpl` (`id`, `title`, `content`, `mdate`) VALUES
        (2, 'Order items', '<table style=\"table-layout: fixed; color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; width: 100%; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif;\" border=\"1\" cellspacing=\"0\" cellpadding=\"6\"><tbody><tr><td style=\"width: 35%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\">{so_item_name}{so_item_sku}{so_supplier_description}</td><td style=\"width: 8%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\">{so_item_qty}</td><td style=\"width: 16%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\">{so_item_price}{supplier_currency}</td><td style=\"width: 16%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\">{so_item_tax}{supplier_currency}</td><td style=\"width: 26%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\">{so_item_total}{supplier_currency}</td></tr></tbody></table>', %d) ON DUPLICATE KEY UPDATE mdate=%d;",array(time(),time()));
    $this->sql1[]=$wpdb->prepare("INSERT INTO `".$wpdb->prefix."honeybadger_so_emails_tpl` (`id`, `title`, `content`, `mdate`) VALUES
        (3, 'Order items Footer', '<table style=\"table-layout: fixed; color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; width: 100%; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif;\" border=\"1\" cellspacing=\"0\" cellpadding=\"6\"><tbody><tr><td style=\"width: 35%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;border-right: 0px!important;\" align=\"left\"><strong>Subtotal</strong></td><td style=\"width: 8%; border: 1px solid #e5e5e5;border-right: 0px!important;border-left: 0px!important;\"></td><td style=\"width: 16%; border: 1px solid #e5e5e5;border-right: 0px!important;border-left: 0px!important;\"></td><td style=\"width: 16%; border: 1px solid #e5e5e5;border-right: 0px!important;border-left: 0px!important;\"></td><td style=\"width: 26%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\">{so_subtotal}{supplier_currency}</td></tr><tr><td style=\"width: 35%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;border-right: 0px!important;\" align=\"left\"><strong>Tax({so_tax}%)</strong></td><td style=\"width: 8%; border: 1px solid #e5e5e5;border-right: 0px!important;border-left: 0px!important;\"></td><td style=\"width: 16%; border: 1px solid #e5e5e5;border-right: 0px!important;border-left: 0px!important;\"></td><td style=\"width: 16%; border: 1px solid #e5e5e5;border-right: 0px!important;border-left: 0px!important;\"></td><td style=\"width: 26%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\">{so_tax_total}{supplier_currency}</td></tr><tr><td style=\"width: 35%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;border-right: 0px!important;\" align=\"left\"><strong>Postage</strong></td><td style=\"width: 8%; border: 1px solid #e5e5e5;border-right: 0px!important;border-left: 0px!important;\"></td><td style=\"width: 16%; border: 1px solid #e5e5e5;border-right: 0px!important;border-left: 0px!important;\"></td><td style=\"width: 16%; border: 1px solid #e5e5e5;border-right: 0px!important;border-left: 0px!important;\"></td><td style=\"width: 26%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\">{so_postage_cost}{supplier_currency}</td></tr><tr><td style=\"width: 35%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;border-right: 0px!important;\" align=\"left\"><strong>Total</strong></td><td style=\"width: 8%; border: 1px solid #e5e5e5;border-right: 0px!important;border-left: 0px!important;\"></td><td style=\"width: 16%; border: 1px solid #e5e5e5;border-right: 0px!important;border-left: 0px!important;\"></td><td style=\"width: 16%; border: 1px solid #e5e5e5;border-right: 0px!important;border-left: 0px!important;\"></td><td style=\"width: 26%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\">{so_total}{supplier_currency}</td></tr></tbody></table>', %d) ON DUPLICATE KEY UPDATE mdate=%d;",array(time(),time()));

    $this->sql1[]=$wpdb->prepare("INSERT INTO `".$wpdb->prefix."honeybadger_attachments_so_tpl` (`id`, `title`, `content`, `mdate`) VALUES
        (1, 'Order items Head', '<table style=\"border-collapse: collapse; width: 100%;\" border=\"1\" cellpadding=\"4px\"><colgroup><col style=\"width: 33.3062%;\"><col style=\"width: 33.3062%;\"><col style=\"width: 33.3062%;\"></colgroup><tbody><tr><td>Product</td><td>Quantity</td><td>Price (inc tax)</td></tr></tbody></table>', %d) ON DUPLICATE KEY UPDATE mdate=%d;",array(time(),time()));
    $this->sql1[]=$wpdb->prepare("INSERT INTO `".$wpdb->prefix."honeybadger_attachments_so_tpl` (`id`, `title`, `content`, `mdate`) VALUES
        (2, 'Order items', '<table style=\"border-collapse: collapse; width: 100%;\" border=\"1\" cellpadding=\"4px\"><colgroup><col style=\"width: 33.3062%;\"><col style=\"width: 33.3062%;\"><col style=\"width: 33.3062%;\"></colgroup><tbody><tr><td>{so_item_name}{so_item_sku}{so_supplier_description}</td><td>{so_item_qty}</td><td>{so_item_total}{supplier_currency}</td></tr></tbody></table>', %d) ON DUPLICATE KEY UPDATE mdate=%d;",array(time(),time()));
    $this->sql1[]=$wpdb->prepare("INSERT INTO `".$wpdb->prefix."honeybadger_attachments_so_tpl` (`id`, `title`, `content`, `mdate`) VALUES
        (3, 'Order items Footer', '<table style=\"border-collapse: collapse; width: 100%;\" border=\"1\"><colgroup><col style=\"width: 33.3062%;\"><col style=\"width: 33.3062%;\"><col style=\"width: 33.3062%;\"></colgroup><tbody><tr><td>&nbsp;</td><td style=\"text-align: right;\"><strong>Subtotal</strong></td><td>{so_subtotal}{supplier_currency}</td></tr><tr><td>&nbsp;</td><td style=\"text-align: right;\"><strong>Tax({so_tax}%)</strong></td><td>{so_tax_total}{supplier_currency}</td></tr><tr><td>&nbsp;</td><td style=\"text-align: right;\"><strong>Postage</strong></td><td>{so_postage_cost}{supplier_currency}</td></tr><tr><td>&nbsp;</td><td style=\"text-align: right;\"><strong>Total</strong></td><td>{so_total}{supplier_currency}</td></tr></tbody></table>', %d) ON DUPLICATE KEY UPDATE mdate=%d;",array(time(),time()));

    $this->sql2[]=$wpdb->prepare("update `".$wpdb->prefix."honeybadger_attachments_tpl` set content='<table style=\"border-collapse: collapse; width: 100%;\" border=\"1\" cellpadding=\"4px\"><colgroup><col style=\"width: 33.3062%;\"><col style=\"width: 33.3062%;\"><col style=\"width: 33.3062%;\"></colgroup><tbody><tr><td>Product</td><td>Quantity</td><td>Price</td></tr></tbody></table>' where id=1");
    $this->sql2[]=$wpdb->prepare("update `".$wpdb->prefix."honeybadger_attachments_tpl` set content='<table style=\"border-collapse: collapse; width: 100%;\" border=\"1\" cellpadding=\"4px\"><colgroup><col style=\"width: 33.3062%;\"><col style=\"width: 33.3062%;\"><col style=\"width: 33.3062%;\"></colgroup><tbody><tr><td>{product_name} (#{product_sku})</td><td>{product_quantity}</td><td>{product_price}</td></tr></tbody></table>' where id=2");
    $this->sql2[]=$wpdb->prepare("update `".$wpdb->prefix."honeybadger_attachments_tpl` set content='<table style=\"border-collapse: collapse; width: 100%;\" border=\"1\"><colgroup><col style=\"width: 33.3062%;\"><col style=\"width: 33.3062%;\"><col style=\"width: 33.3062%;\"></colgroup><tbody><tr><td>&nbsp;</td><td style=\"text-align: right;\">{subtotal_label}</td><td>{subtotal_value}</td></tr></tbody></table>'where id=3");

    $this->sql2[]=$wpdb->prepare("update `".$wpdb->prefix."honeybadger_so_emails_tpl` set content='<table style=\"table-layout: fixed; color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; width: 100%; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif;\" border=\"1\" cellspacing=\"0\" cellpadding=\"6\"><tbody><tr><td style=\"width: 60%; color: rgb(99, 99, 99); border: 1px solid rgb(229, 229, 229); padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; overflow-wrap: break-word;\" align=\"left\"><strong>Product</strong></td><td style=\"width: 10%; color: rgb(99, 99, 99); border: 1px solid rgb(229, 229, 229); padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; overflow-wrap: break-word;\" align=\"left\"><strong>Qty</strong></td><td style=\"width: 30%; color: rgb(99, 99, 99); border: 1px solid rgb(229, 229, 229); padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; overflow-wrap: break-word;\" align=\"left\"><strong>Price</strong><small>(inc tax)</small></td></tr></tbody></table>' where id=1");
    $this->sql2[]=$wpdb->prepare("update `".$wpdb->prefix."honeybadger_so_emails_tpl` set content='<table style=\"table-layout: fixed; color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; width: 100%; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif;\" border=\"1\" cellspacing=\"0\" cellpadding=\"6\"><tbody><tr><td style=\"width: 60%; color: rgb(99, 99, 99); border: 1px solid rgb(229, 229, 229); padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; overflow-wrap: break-word;\" align=\"left\">{so_item_name}{so_item_sku}{so_supplier_description}</td><td style=\"width: 10%; color: rgb(99, 99, 99); border: 1px solid rgb(229, 229, 229); padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; overflow-wrap: break-word;\" align=\"left\">{so_item_qty}</td><td style=\"width: 30%; color: rgb(99, 99, 99); border: 1px solid rgb(229, 229, 229); padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; overflow-wrap: break-word;\" align=\"left\">{so_item_total}{supplier_currency}</td></tr></tbody></table>' where id=2");
    $this->sql2[]=$wpdb->prepare("update `".$wpdb->prefix."honeybadger_so_emails_tpl` set content='<table style=\"table-layout: fixed; color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; width: 100%; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif;\" border=\"1\" cellspacing=\"0\" cellpadding=\"6\"><tbody><tr><td style=\"width: 60%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; border-right: 0px!important;\" align=\"left\"><strong>Subtotal</strong></td><td style=\"width: 10%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; border-left: 0px!important;\" align=\"left\">&nbsp;</td><td style=\"width: 30%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\">{so_subtotal}{supplier_currency}</td></tr><tr><td style=\"width: 60%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; border-right: 0px!important;\" align=\"left\"><strong>Tax({so_tax}%)</strong></td><td style=\"width: 10%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; border-left: 0px!important;\" align=\"left\">&nbsp;</td><td style=\"width: 30%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\">{so_tax_total}{supplier_currency}</td></tr><tr><td style=\"width: 60%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; border-right: 0px!important;\" align=\"left\"><strong>Postage</strong></td><td style=\"width: 10%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; border-left: 0px!important;\" align=\"left\">&nbsp;</td><td style=\"width: 30%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\">{so_postage_cost}{supplier_currency}</td></tr><tr><td style=\"width: 60%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; border-right: 0px!important;\" align=\"left\"><strong>Total</strong></td><td style=\"width: 10%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; border-left: 0px!important;\" align=\"left\">&nbsp;</td><td style=\"width: 30%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\">{so_total}{supplier_currency}</td></tr></tbody></table>' where id=3");

    $this->sql2[]=$wpdb->prepare("update `".$wpdb->prefix."honeybadger_so_emails_tpl` set content='<table style=\"table-layout: fixed; color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; width: 100%; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif;\" border=\"1\" cellspacing=\"0\" cellpadding=\"6\"><tbody><tr><td style=\"width: 35%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\"><strong>Product</strong></td><td style=\"width: 8%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\"><strong>Qty</strong></td><td style=\"width: 16%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\"><strong>Price</strong></td><td style=\"width: 16%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\"><strong>Tax</strong></td><td style=\"width: 26%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\"><strong>Item total</strong></td></tr></tbody></table>' where id=1");
    $this->sql2[]=$wpdb->prepare("update `".$wpdb->prefix."honeybadger_so_emails_tpl` set content='<table style=\"table-layout: fixed; color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; width: 100%; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif;\" border=\"1\" cellspacing=\"0\" cellpadding=\"6\"><tbody><tr><td style=\"width: 35%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\">{so_item_name}{so_item_sku}{so_supplier_description}</td><td style=\"width: 8%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\">{so_item_qty}</td><td style=\"width: 16%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\">{so_item_price}{supplier_currency}</td><td style=\"width: 16%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\">{so_item_tax}{supplier_currency}</td><td style=\"width: 26%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\">{so_item_total}{supplier_currency}</td></tr></tbody></table>' where id=2");
    $this->sql2[]=$wpdb->prepare("update `".$wpdb->prefix."honeybadger_so_emails_tpl` set content='<table style=\"table-layout: fixed; color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; width: 100%; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif;\" border=\"1\" cellspacing=\"0\" cellpadding=\"6\"><tbody><tr><td style=\"width: 35%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;border-right: 0px!important;\" align=\"left\"><strong>Subtotal</strong></td><td style=\"width: 8%; border: 1px solid #e5e5e5;border-right: 0px!important;border-left: 0px!important;\"></td><td style=\"width: 16%; border: 1px solid #e5e5e5;border-right: 0px!important;border-left: 0px!important;\"></td><td style=\"width: 16%; border: 1px solid #e5e5e5;border-right: 0px!important;border-left: 0px!important;\"></td><td style=\"width: 26%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\">{so_subtotal}{supplier_currency}</td></tr><tr><td style=\"width: 35%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;border-right: 0px!important;\" align=\"left\"><strong>Tax({so_tax}%)</strong></td><td style=\"width: 8%; border: 1px solid #e5e5e5;border-right: 0px!important;border-left: 0px!important;\"></td><td style=\"width: 16%; border: 1px solid #e5e5e5;border-right: 0px!important;border-left: 0px!important;\"></td><td style=\"width: 16%; border: 1px solid #e5e5e5;border-right: 0px!important;border-left: 0px!important;\"></td><td style=\"width: 26%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\">{so_tax_total}{supplier_currency}</td></tr><tr><td style=\"width: 35%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;border-right: 0px!important;\" align=\"left\"><strong>Postage</strong></td><td style=\"width: 8%; border: 1px solid #e5e5e5;border-right: 0px!important;border-left: 0px!important;\"></td><td style=\"width: 16%; border: 1px solid #e5e5e5;border-right: 0px!important;border-left: 0px!important;\"></td><td style=\"width: 16%; border: 1px solid #e5e5e5;border-right: 0px!important;border-left: 0px!important;\"></td><td style=\"width: 26%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\">{so_postage_cost}{supplier_currency}</td></tr><tr><td style=\"width: 35%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;border-right: 0px!important;\" align=\"left\"><strong>Total</strong></td><td style=\"width: 8%; border: 1px solid #e5e5e5;border-right: 0px!important;border-left: 0px!important;\"></td><td style=\"width: 16%; border: 1px solid #e5e5e5;border-right: 0px!important;border-left: 0px!important;\"></td><td style=\"width: 16%; border: 1px solid #e5e5e5;border-right: 0px!important;border-left: 0px!important;\"></td><td style=\"width: 26%; color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;\" align=\"left\">{so_total}{supplier_currency}</td></tr></tbody></table>'where id=3");

      $this->sql2[]=$wpdb->prepare("update `".$wpdb->prefix."honeybadger_attachments_so_tpl` set content='<table style=\"border-collapse: collapse; width: 100%;\" border=\"1\" cellpadding=\"4px\"><colgroup><col style=\"width: 33.3062%;\"><col style=\"width: 33.3062%;\"><col style=\"width: 33.3062%;\"></colgroup><tbody><tr><td>Product</td><td>Quantity</td><td>Price (inc tax)</td></tr></tbody></table>' where id=1");
      $this->sql2[]=$wpdb->prepare("update `".$wpdb->prefix."honeybadger_attachments_so_tpl` set content='<table style=\"border-collapse: collapse; width: 100%;\" border=\"1\" cellpadding=\"4px\"><colgroup><col style=\"width: 33.3062%;\"><col style=\"width: 33.3062%;\"><col style=\"width: 33.3062%;\"></colgroup><tbody><tr><td>{so_item_name}{so_item_sku}{so_supplier_description}</td><td>{so_item_qty}</td><td>{so_item_total}{supplier_currency}</td></tr></tbody></table>' where id=2");
      $this->sql2[]=$wpdb->prepare("update `".$wpdb->prefix."honeybadger_attachments_so_tpl` set content='<table style=\"border-collapse: collapse; width: 100%;\" border=\"1\"><colgroup><col style=\"width: 33.3062%;\"><col style=\"width: 33.3062%;\"><col style=\"width: 33.3062%;\"></colgroup><tbody><tr><td>&nbsp;</td><td style=\"text-align: right;\"><strong>Subtotal</strong></td><td>{so_subtotal}{supplier_currency}</td></tr><tr><td>&nbsp;</td><td style=\"text-align: right;\"><strong>Tax({so_tax}%)</strong></td><td>{so_tax_total}{supplier_currency}</td></tr><tr><td>&nbsp;</td><td style=\"text-align: right;\"><strong>Postage</strong></td><td>{so_postage_cost}{supplier_currency}</td></tr><tr><td>&nbsp;</td><td style=\"text-align: right;\"><strong>Total</strong></td><td>{so_total}{supplier_currency}</td></tr></tbody></table>'where id=3");
  }

  public function createTables(){
    global $wpdb;
    
    require_once( ABSPATH . "wp-admin/includes/upgrade.php" );
    if(get_option('HONEYBADGER_IT_VERSION')==false)
      update_option('HONEYBADGER_IT_VERSION','1.0.0');

    $table_name=$wpdb->prefix.'honeybadger_oauth_clients';
    if( $wpdb->get_var( $wpdb->prepare("show tables like %s",$table_name) ) != $table_name ) {
    $sql=$wpdb->prepare("CREATE TABLE `".$table_name."` (
          client_id             VARCHAR(80)   NOT NULL,
          client_secret         VARCHAR(80),
          redirect_uri          VARCHAR(2000),
          grant_types           VARCHAR(80),
          scope                 VARCHAR(4000),
          user_id               VARCHAR(80),
          PRIMARY KEY (client_id)
        );
    ");
    dbDelta( $sql );
    }
    $table_name=$wpdb->prefix.'honeybadger_custom_order_statuses';
    if( $wpdb->get_var( $wpdb->prepare("show tables like %s" ),$table_name) != $table_name ) {
    $sql=$wpdb->prepare("CREATE TABLE `".$table_name."` (
      id int(11) NOT NULL AUTO_INCREMENT,
      custom_order_status varchar(255)  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
      custom_order_status_title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
      bg_color varchar(255) NOT NULL DEFAULT '',
      txt_color varchar(255) NOT NULL DEFAULT '',
      mdate int(11) NOT NULL DEFAULT 0,
      PRIMARY KEY (id),
      UNIQUE KEY custom_order_status (custom_order_status)
    );
    ");
    dbDelta( $sql );
    }
    $table_name=$wpdb->prefix.'honeybadger_oauth_access_tokens';
    if( $wpdb->get_var( $wpdb->prepare("show tables like %s" ),$table_name) != $table_name ) {
    $sql=$wpdb->prepare("CREATE TABLE `".$table_name."` (
          access_token         VARCHAR(40)    NOT NULL,
          client_id            VARCHAR(80)    NOT NULL,
          user_id              VARCHAR(80),
          expires              TIMESTAMP      NOT NULL,
          scope                VARCHAR(4000),
          PRIMARY KEY (access_token)
        );
    ");
    dbDelta( $sql );
    }
    $table_name=$wpdb->prefix.'honeybadger_oauth_authorization_codes';
    if( $wpdb->get_var( $wpdb->prepare("show tables like %s" ),$table_name) != $table_name ) {
    $sql=$wpdb->prepare("CREATE TABLE `".$table_name."` (
          authorization_code  VARCHAR(40)     NOT NULL,
          client_id           VARCHAR(80)     NOT NULL,
          user_id             VARCHAR(80),
          redirect_uri        VARCHAR(2000),
          expires             TIMESTAMP       NOT NULL,
          scope               VARCHAR(4000),
          id_token            VARCHAR(1000),
          PRIMARY KEY (authorization_code)
        );
    ");
    dbDelta( $sql );
    }
    $table_name=$wpdb->prefix.'honeybadger_oauth_refresh_tokens';
    if( $wpdb->get_var( $wpdb->prepare("show tables like %s" ),$table_name) != $table_name ) {
    $sql=$wpdb->prepare("CREATE TABLE `".$table_name."` (
          refresh_token       VARCHAR(40)     NOT NULL,
          client_id           VARCHAR(80)     NOT NULL,
          user_id             VARCHAR(80),
          expires             TIMESTAMP       NOT NULL,
          scope               VARCHAR(4000),
          PRIMARY KEY (refresh_token)
        );
    ");
    dbDelta( $sql );
    }
    $table_name=$wpdb->prefix.'honeybadger_oauth_users';
    if( $wpdb->get_var( $wpdb->prepare("show tables like %s" ),$table_name) != $table_name ) {
    $sql=$wpdb->prepare("CREATE TABLE `".$table_name."` (
          username            VARCHAR(80),
          password            VARCHAR(80),
          first_name          VARCHAR(80),
          last_name           VARCHAR(80),
          email               VARCHAR(80),
          email_verified      BOOLEAN,
          scope               VARCHAR(4000),
          PRIMARY KEY (username)
        );
    ");
    dbDelta( $sql );
    }
    $table_name=$wpdb->prefix.'honeybadger_oauth_scopes';
    if( $wpdb->get_var( $wpdb->prepare("show tables like %s" ),$table_name) != $table_name ) {
    $sql=$wpdb->prepare("CREATE TABLE `".$table_name."` (
          scope               VARCHAR(80)     NOT NULL,
          is_default          BOOLEAN,
          PRIMARY KEY (scope)
        );
    ");
    dbDelta( $sql );
    }
    $table_name=$wpdb->prefix.'honeybadger_oauth_jwt';
    if( $wpdb->get_var( $wpdb->prepare("show tables like %s" ),$table_name) != $table_name ) {
    $sql=$wpdb->prepare("CREATE TABLE `".$table_name."` (
          client_id           VARCHAR(80)     NOT NULL,
          subject             VARCHAR(80),
          public_key          VARCHAR(2000)   NOT NULL
        );
    ");
    dbDelta( $sql );
    }
    $table_name=$wpdb->prefix.'honeybadger_config';
    if( $wpdb->get_var( $wpdb->prepare("show tables like %s" ),$table_name) != $table_name ) {
    $sql=$wpdb->prepare("CREATE TABLE `$table_name` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `config_name` varchar(255) NOT NULL,
      `config_value` varchar(255) NOT NULL,
      `show_front` tinyint(1) NOT NULL,
      `mdate` int(11) NOT NULL, 
      PRIMARY KEY (`id`),
      UNIQUE KEY (`config_name`)
    );
    ");
    dbDelta( $sql );
    }
    $table_name=$wpdb->prefix.'honeybadger_wc_emails';
    if( $wpdb->get_var( $wpdb->prepare("show tables like %s" ),$table_name) != $table_name ) {
    $sql=$wpdb->prepare("CREATE TABLE `$table_name` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `wc_status` varchar(255) NOT NULL DEFAULT '',
      `title` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
      `subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
      `heading` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
      `subheading` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
      `content` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
      `template` VARCHAR(255) NOT NULL DEFAULT '',
      `enabled` TINYINT(1) NOT NULL DEFAULT '0',
      `other_subject` VARCHAR(255) NOT NULL DEFAULT '',
      `other_heading` TEXT NOT NULL DEFAULT '',
      `other_subheading_1` TEXT NOT NULL DEFAULT '',
      `other_subheading_2` VARCHAR(255) NOT NULL DEFAULT '',
      `email_bcc` VARCHAR(255) NOT NULL DEFAULT '',
      `mdate` INT(11) NOT NULL DEFAULT '0',
      PRIMARY KEY (`id`),
      UNIQUE KEY (`wc_status`)
    );
    ");
    dbDelta( $sql );
    }
    $table_name=$wpdb->prefix.'honeybadger_emails';
    if( $wpdb->get_var( $wpdb->prepare("show tables like %s" ),$table_name) != $table_name ) {
    $sql=$wpdb->prepare("CREATE TABLE `$table_name` (
      `id` INT(11) NOT NULL AUTO_INCREMENT ,
      `title` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
      `subject` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
      `heading` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
      `content` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
      `email_bcc` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
      `statuses` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' ,
      `so_states` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
      `enabled` TINYINT(1) NOT NULL DEFAULT '1' ,
      `mdate` INT(11) NOT NULL DEFAULT '0' ,
      PRIMARY KEY (`id`)
    );
    ");
    dbDelta( $sql );
    }
    $table_name=$wpdb->prefix.'honeybadger_attachments';
    if( $wpdb->get_var( $wpdb->prepare("show tables like %s" ),$table_name) != $table_name ) {
    $sql=$wpdb->prepare("CREATE TABLE `$table_name` (
      `id` INT(11) NOT NULL AUTO_INCREMENT ,
      `title` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
      `content` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' ,
      `pdf_font` VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'helvetica' ,
      `pdf_size` VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'A4' ,
      `pdf_orientation` VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'P' ,
      `pdf_margins` VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '10,10,10,10' ,
      `attach_to_wc_emails` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
      `attach_to_emails` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
      `keep_files` TINYINT(1) NOT NULL DEFAULT '1',
      `generable` TINYINT(1) NOT NULL DEFAULT '1',
      `so_generable` TINYINT(1) NOT NULL DEFAULT '1',
      `enabled` TINYINT(1) NOT NULL DEFAULT '1' ,
      `mdate` INT(11) NOT NULL DEFAULT '0' ,
      PRIMARY KEY (`id`)
    );
    ");
    dbDelta( $sql );
    }
    $table_name=$wpdb->prefix.'honeybadger_attachments_tpl';
    if( $wpdb->get_var( $wpdb->prepare("show tables like %s" ),$table_name) != $table_name ) {
    $sql=$wpdb->prepare("CREATE TABLE `$table_name` (
      `id` INT(11) NOT NULL AUTO_INCREMENT ,
      `title` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
      `content` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' ,
      `mdate` INT(11) NOT NULL DEFAULT '0' ,
      PRIMARY KEY (`id`)
    );
    ");
    dbDelta( $sql );
    }
    $table_name=$wpdb->prefix.'honeybadger_attachments_so_tpl';
    if( $wpdb->get_var( $wpdb->prepare("show tables like %s" ),$table_name) != $table_name ) {
    $sql=$wpdb->prepare("CREATE TABLE `$table_name` (
      `id` INT(11) NOT NULL AUTO_INCREMENT ,
      `title` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
      `content` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' ,
      `mdate` INT(11) NOT NULL DEFAULT '0' ,
      PRIMARY KEY (`id`)
    );
    ");
    dbDelta( $sql );
    }
    $table_name=$wpdb->prefix.'honeybadger_so_emails_tpl';
    if( $wpdb->get_var( $wpdb->prepare("show tables like %s" ),$table_name) != $table_name ) {
    $sql=$wpdb->prepare("CREATE TABLE `$table_name` (
      `id` INT(11) NOT NULL AUTO_INCREMENT ,
      `title` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
      `content` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' ,
      `mdate` INT(11) NOT NULL DEFAULT '0' ,
      PRIMARY KEY (`id`)
    );
    ");
    dbDelta( $sql );
    }
    $table_name=$wpdb->prefix.'honeybadger_static_attachments';
    if( $wpdb->get_var( $wpdb->prepare("show tables like %s" ),$table_name) != $table_name ) {
    $sql=$wpdb->prepare("CREATE TABLE `$table_name` (
      `id` INT(11) NOT NULL AUTO_INCREMENT ,
      `title` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' ,
      `path` VARCHAR(512) NOT NULL DEFAULT '' ,
      `wc_emails` VARCHAR(512) NOT NULL DEFAULT '' ,
      `emails` VARCHAR(512) NOT NULL DEFAULT '' ,
      `enabled` TINYINT(1) NOT NULL DEFAULT '1' ,
      `mdate` INT(11) NOT NULL DEFAULT '0' , 
      PRIMARY KEY (`id`)
    );
    ");
    dbDelta( $sql );
    }
    $table_name=$wpdb->prefix.'honeybadger_product_stock_log';
    if( $wpdb->get_var( $wpdb->prepare("show tables like %s" ),$table_name) != $table_name ) {
    $sql=$wpdb->prepare("CREATE TABLE `$table_name` (
      `order_id` INT(11) NOT NULL DEFAULT '0' ,
      `product_id` INT(11) NOT NULL DEFAULT '0' ,
      `product_title` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' ,
      `reduced_stock` INT(11) NOT NULL DEFAULT '0' ,
      `restored_stock` INT(11) NOT NULL DEFAULT '0' ,
      `done` TINYINT(1) NOT NULL DEFAULT '0',
      `mdate` INT(11) NOT NULL DEFAULT '0',
      UNIQUE KEY (`order_id`, `product_id`)
    );
    ");
    dbDelta( $sql );
    }

    dbDelta( $this->sql1 );
    
    $sql=$wpdb->prepare("INSERT INTO `".$wpdb->prefix."honeybadger_config` (`config_name`, `config_value`, `show_front`, `mdate`) VALUES
        ('curl_ssl_verify', 'yes', '1', %d),
        ('access_lifetime', '86400', '0', %d),
        ('refresh_token_lifetime', '2419200', '0', %d),
        ('ping_url', %s, '0',%d),
        ('api_url', %s, '0',%d),
        ('token_url', %s, '0',%d),
        ('access_token', '', '0',%d),
        ('refresh_token', '', '0',%d),
        ('remote_access_token', '', '0',%d),
        ('remote_refresh_token', '', '0',%d),
        ('first_time_installation', '1', '0',%d),
        ('setup_step', '0', '0',%d),
        ('is_refresh', '0', '0',%d),
        ('use_status_colors_on_wc', 'yes', '1',%d),
        ('show_images_in_emails', 'no', '0',%d),
        ('show_sku_in_emails', 'no', '0',%d),
        ('enable_product_variation_extra_images', 'no', '0',%d),
        ('honeybadger_account_email', '', '0',%d),
        ('email_image_sizes', '100x50', '0',%d),
        ('delete_attachments_upon_uninstall', 'no', '1',%d),
        ('skip_rest_authentication_errors', 'no', '1',%d)
        ON DUPLICATE KEY UPDATE mdate=".time().";
    ",array(time(),time(),time(),sanitize_text_field("https://".esc_sql(HONEYBADGER_IT_TARGET_SUBDOMAIN).".honeybadger.it/ping.php"),time(),sanitize_text_field("https://".esc_sql(HONEYBADGER_IT_TARGET_SUBDOMAIN).".honeybadger.it/api.php"),time(),sanitize_text_field("https://".esc_sql(HONEYBADGER_IT_TARGET_SUBDOMAIN).".honeybadger.it/token.php"),time(),time(),time(),time(),time(),time(),time(),time(),time(),time(),time(),time(),time(),time(),time(),time(),time()));
    dbDelta( $sql );

    $honeybadger_emails_dir = HONEYBADGER_UPLOADS_PATH.'emails';
    $honeybadger_attachments_dir = HONEYBADGER_UPLOADS_PATH.'attachments';
    if(!file_exists(HONEYBADGER_UPLOADS_PATH))
        wp_mkdir_p(HONEYBADGER_UPLOADS_PATH);
    if(!file_exists($honeybadger_emails_dir))
        wp_mkdir_p($honeybadger_emails_dir);
    if(!file_exists($honeybadger_attachments_dir))
        wp_mkdir_p($honeybadger_attachments_dir);
    if(!file_exists($honeybadger_attachments_dir.'/static'))
        wp_mkdir_p($honeybadger_attachments_dir.'/static');
    if(!file_exists($honeybadger_attachments_dir.'/tmp'))
        wp_mkdir_p($honeybadger_attachments_dir.'/tmp');
    if(!file_exists(HONEYBADGER_UPLOADS_PATH."index.php"))
        file_put_contents(HONEYBADGER_UPLOADS_PATH."index.php",'<?php // Silence is golden');
    if(!file_exists($honeybadger_emails_dir."/index.php"))
        file_put_contents($honeybadger_emails_dir."/index.php",'<?php // Silence is golden');
    if(!file_exists($honeybadger_attachments_dir."/index.php"))
        file_put_contents($honeybadger_attachments_dir."/index.php",'<?php // Silence is golden');
    if(!file_exists($honeybadger_attachments_dir."/static/index.php"))
        file_put_contents($honeybadger_attachments_dir."/static/index.php",'<?php // Silence is golden');
    if(!file_exists($honeybadger_attachments_dir."/tmp/index.php"))
        file_put_contents($honeybadger_attachments_dir."/tmp/index.php",'<?php // Silence is golden');
    $advanced_styles=array('email-header','email-footer','email-styles','email-addresses','email-customer-details','email-downloads','email-order-details','email-order-items');
    foreach($advanced_styles as $style)
    {
        $file_name=$style.".php";
        if(!is_file($honeybadger_emails_dir."/".$file_name))
        {
            if(is_file(get_template_directory().'/woocommerce/emails/'.$file_name))
            {
                file_put_contents($honeybadger_emails_dir."/".$file_name,file_get_contents(get_template_directory().'/woocommerce/emails/'.$file_name));
            }
            else if(is_file(WC()->plugin_path()."/templates/emails/".$file_name))
                file_put_contents($honeybadger_emails_dir."/".$file_name,file_get_contents(WC()->plugin_path()."/templates/emails/".$file_name));
        }
    }
  }
  public function runFunctionsForMultiOrSingleBlog($the_function=""){
    global $wpdb;
    if($the_function!=""){
      if ( is_multisite() ) {
          // Get all blogs in the network and activate plugin on each one
          $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
          foreach ( $blog_ids as $blog_id ) {
              switch_to_blog( $blog_id );
              $this->$the_function();
              restore_current_blog();
          }
        } else {
            $this->$the_function();
        }
    }
  }
    public function activate() {
        $this->runFunctionsForMultiOrSingleBlog("createTables");
    }
  // Creating table whenever a new blog is created
  function on_create_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
      if ( is_plugin_active_for_network( 'honeybadger-it/honeybadger-it.php' ) ) {
          switch_to_blog( $blog_id );
          $this->createTables();
          restore_current_blog();
      }
  }
  // Deleting the table whenever a blog is deleted
  function on_delete_blog( $tables ) {
      global $wpdb;
      $current_blog_tables=array();
      foreach ($this->honeybadgerTables as $table) {
        $current_blog_tables[]=$wpdb->prefix.$table;
      }
      $tables=array_merge($tables,$current_blog_tables);
      return $tables;
  }
  function deleteHoneyBadgerTables(){
    global $wpdb;
    $sql="select config_value from ".$wpdb->prefix."honeybadger_config where config_name='delete_attachments_upon_uninstall'";
    $result=$wpdb->get_row($sql);
    if(isset($result->config_value) && $result->config_value=='yes')
        $this->honeybadger_remove_uploads_folder(HONEYBADGER_UPLOADS_PATH);
    foreach($this->honeybadgerTables as $table)
      $wpdb->query( "DROP TABLE IF EXISTS ".$wpdb->prefix.$table );
    delete_option('HONEYBADGER_IT_VERSION');
  }
    function honeybadger_remove_uploads_folder($dir)
    {
        if (!file_exists($dir))
            return true;
        if (!is_dir($dir))
            return unlink($dir);
        foreach (scandir($dir) as $item)
        {
            if ($item == '.' || $item == '..')
                continue;
            if (!$this->honeybadger_remove_uploads_folder($dir . "/" . $item))
              return false;
        }
        return rmdir($dir);
    }
  function deleteTables(){
    delete_option("honeybadger_the_honeybadger_it_activation_is_done");
    $this->runFunctionsForMultiOrSingleBlog("deleteHoneyBadgerTables");
  }
  function versionChanges(){
    $this->runFunctionsForMultiOrSingleBlog("doVersionChanges");
  }
  function doVersionChanges(){
    global $wpdb;
    $current_version=get_option('HONEYBADGER_IT_VERSION');
    if (HONEYBADGER_IT_VERSION !== $current_version){
        if(HONEYBADGER_IT_VERSION=="1.0.1" && $current_version=="1.0.0"){
          //do something here
          update_option('HONEYBADGER_IT_VERSION','1.0.1');
        }
    }
  }
}
