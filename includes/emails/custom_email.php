<?php
/**
 * @package    Honeybadger_IT
 * @subpackage Honeybadger_IT/admin
 * @author     Claudiu Maftei <claudiu@honeybadger.it>
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
if($tpl_id>0)
{
	$sql=$wpdb->prepare("select * from ".$wpdb->prefix."honeybadger_emails where id=%d",$tpl_id);
	$email_tpl=$wpdb->get_row($sql);
	if(isset($email_tpl->content))
	{
		$content=$email_tpl->content;

		$supplier_order=array();
		if(isset($other_details) && is_array($other_details) && isset($other_details['supplier_order']))
			$supplier_order=$other_details['supplier_order'];

		if(is_array($supplier_order) && count($supplier_order)>0)
		{
			foreach($supplier_order as $tag => $tag_value)
			{
				$content=str_ireplace("{".$tag."}", $tag_value ,$content);
				$email_heading=str_ireplace("{".$tag."}", $tag_value ,$email_heading);
			}
		}
		else if($order)
		{
			$email_heading=$email_tpl->heading;
			$email_heading=str_ireplace("{customer}",esc_html( $order->get_billing_first_name() ),$email_heading);
	    	$email_heading=str_ireplace("{customer_full_name}",esc_html( $order->get_formatted_billing_full_name() ),$email_heading);
			$email_heading=str_ireplace("{site_title}", get_bloginfo( 'name', 'display' ) ,$email_heading);
			$email_heading=str_ireplace("{site_url}", get_bloginfo( 'url', 'display' ) ,$email_heading);
			$email_heading=str_ireplace("{order_date}", wc_format_datetime( $order->get_date_created(),$date_format ) ,$email_heading);
			$email_heading=str_ireplace("{date_completed}", wc_format_datetime( $order->get_date_completed(),$date_format ) ,$email_heading);
			$email_heading=str_ireplace("{date_paid}", wc_format_datetime( $order->get_date_paid(),$date_format ) ,$email_heading);
			$email_heading=str_ireplace("{shipping_price}", number_format($order->get_shipping_total(),2,".","") ,$email_heading);
			$email_heading=str_ireplace("{shipping_title}", $order->get_shipping_method() ,$email_heading);
			$email_heading=str_ireplace("{order_currency}", $order->get_currency() ,$email_heading);
			$email_heading=str_ireplace("{order_currency_symbol}", get_woocommerce_currency_symbol($order->get_currency()) ,$email_heading);
			
			$countries=WC()->countries->get_countries();
			$states=WC()->countries->get_states();
			$billing_country_orig=$order->get_billing_country();
			$billing_country=((isset($countries[$billing_country_orig]))?$countries[$billing_country_orig]:$billing_country_orig);
			$email_heading=str_ireplace("{billing_country}", $billing_country ,$email_heading);
			$billing_state_orig=$order->get_billing_state();
			$billing_state=((isset($states[$billing_country_orig][$billing_state_orig]))?$states[$billing_country_orig][$billing_state_orig]:$billing_state_orig);
			$email_heading=str_ireplace("{billing_state}", $billing_state ,$email_heading);
			$shipping_country_orig=$order->get_shipping_country();
			$shipping_country=((isset($countries[$shipping_country_orig]))?$countries[$shipping_country_orig]:$shipping_country_orig);
			$email_heading=str_ireplace("{shipping_country}", $shipping_country ,$email_heading);
			$shipping_state_orig=$order->get_shipping_state();
			$shipping_state=((isset($states[$shipping_country_orig][$shipping_state_orig]))?$states[$shipping_country_orig][$shipping_state_orig]:$shipping_state_orig);
			$email_heading=str_ireplace("{shipping_state}", $shipping_state ,$email_heading);
			if(is_array($honeybadger->order_addresses_tags))
			{
				foreach($honeybadger->order_addresses_tags as $tag)
				{
					$tmp_func="get_".$tag;
					$email_heading=str_ireplace("{".$tag."}", $order->$tmp_func() ,$email_heading);
				}
			}
			if(is_array($honeybadger->order_addresses_tags))
			{
				foreach($honeybadger->order_other_tags as $tag)
				{
					$tmp_func="get_".$tag;
					$email_heading=str_ireplace("{".$tag."}", $order->$tmp_func() ,$email_heading);
				}
			}
		}

		do_action( 'woocommerce_email_header', $email_heading, $email );

		if($order)
		{
			$content=str_ireplace("{customer}",esc_html( $order->get_billing_first_name() ),$content);
	    	$content=str_ireplace("{customer_full_name}",esc_html( $order->get_formatted_billing_full_name() ),$content);
			$content=str_ireplace("{site_title}", get_bloginfo( 'name', 'display' ) ,$content);
			$content=str_ireplace("{site_url}", get_bloginfo( 'url', 'display' ) ,$content);
			$content=str_ireplace("{order_date}", wc_format_datetime( $order->get_date_created(),$date_format ) ,$content);
			$content=str_ireplace("{date_completed}", wc_format_datetime( $order->get_date_completed(),$date_format ) ,$content);
			$content=str_ireplace("{date_paid}", wc_format_datetime( $order->get_date_paid(),$date_format ) ,$content);
			$content=str_ireplace("{shipping_price}", number_format($order->get_shipping_total(),2,".","") ,$content);
			$content=str_ireplace("{shipping_title}", $order->get_shipping_method() ,$content);
			$content=str_ireplace("{order_currency}", $order->get_currency() ,$content);
			$content=str_ireplace("{order_currency_symbol}", get_woocommerce_currency_symbol($order->get_currency()) ,$content);
			
			$countries=WC()->countries->get_countries();
			$states=WC()->countries->get_states();
			$billing_country_orig=$order->get_billing_country();
			$billing_country=((isset($countries[$billing_country_orig]))?$countries[$billing_country_orig]:$billing_country_orig);
			$content=str_ireplace("{billing_country}", $billing_country ,$content);
			$billing_state_orig=$order->get_billing_state();
			$billing_state=((isset($states[$billing_country_orig][$billing_state_orig]))?$states[$billing_country_orig][$billing_state_orig]:$billing_state_orig);
			$content=str_ireplace("{billing_state}", $billing_state ,$content);
			$shipping_country_orig=$order->get_shipping_country();
			$shipping_country=((isset($countries[$shipping_country_orig]))?$countries[$shipping_country_orig]:$shipping_country_orig);
			$content=str_ireplace("{shipping_country}", $shipping_country ,$content);
			$shipping_state_orig=$order->get_shipping_state();
			$shipping_state=((isset($states[$shipping_country_orig][$shipping_state_orig]))?$states[$shipping_country_orig][$shipping_state_orig]:$shipping_state_orig);
			$content=str_ireplace("{shipping_state}", $shipping_state ,$content);
			if(is_array($honeybadger->order_addresses_tags))
			{
				foreach($honeybadger->order_addresses_tags as $tag)
				{
					$tmp_func="get_".$tag;
					$content=str_ireplace("{".$tag."}", $order->$tmp_func() ,$content);
				}
			}
			if(is_array($honeybadger->order_addresses_tags))
			{
				foreach($honeybadger->order_other_tags as $tag)
				{
					$tmp_func="get_".$tag;
					$content=str_ireplace("{".$tag."}", $order->$tmp_func() ,$content);
				}
			}

		    $pos = strpos($content, "{woocommerce_email_order_details}");
		    if ($pos !== false)
		    {
		    	ob_start();
		    	do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
		    	$message = ob_get_clean();
		    	$content=str_ireplace("{woocommerce_email_order_details}",$message,$content);
		    }
		    $pos = strpos($content, "{woocommerce_email_order_meta}");
		    if ($pos !== false)
		    {
		    	ob_start();
		    	do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
		    	$message = ob_get_clean();
		    	$content=str_ireplace("{woocommerce_email_order_meta}",$message,$content);
		    }
		    $pos = strpos($content, "{woocommerce_email_customer_details}");
		    if ($pos !== false)
		    {
		    	ob_start();
		    	do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );
		    	$message = ob_get_clean();
		    	$content=str_ireplace("{woocommerce_email_customer_details}",$message,$content);
		    }
		}
		
		echo wp_kses_post( wpautop( wptexturize($content)));

		do_action( 'woocommerce_email_footer', $email );
	}
}
else
{
	do_action( 'woocommerce_email_header', "something went wrong", $email );
	do_action( 'woocommerce_email_footer', $email );
}
?>