<?php
/**
 * @package    Honeybadger_IT
 * @subpackage Honeybadger_IT/admin
 * @author     Claudiu Maftei <claudiu@honeybadger.it>
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_email_header', $email_heading, $email );

$content=__('Attached',"honeyb");

echo $content;

do_action( 'woocommerce_email_footer', $email );

?>