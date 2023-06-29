<?php
/**
 * @package    Honeybadger_IT
 * @subpackage Honeybadger_IT/admin
 * @author     Claudiu Maftei <claudiu@honeybadger.it>
 */
namespace HoneyBadgerIT\API;
use \stdClass;
class honeybadgerAPI{

	public $config;
	public $config_front;
	public $order_addresses_tags=array('billing_first_name','billing_last_name','billing_company','billing_address_1','billing_address_2','billing_city','billing_state','billing_postcode','billing_country','billing_email','billing_phone','shipping_first_name','shipping_last_name','shipping_company','shipping_address_1','shipping_address_2','shipping_city','shipping_state','shipping_postcode','shipping_country','shipping_phone');
	public $order_other_tags=array('order_number','payment_method','payment_method_title','transaction_id','customer_ip_address','customer_user_agent','created_via','customer_note','date_completed','date_paid','cart_hash');
	function __construct(){
		global $wpdb;
		$this->config=new stdClass;
		$this->config_front=new stdClass;
		$sql="select * from ".$wpdb->prefix."honeybadger_config where 1";
		$results=$wpdb->get_results($sql);
		if($results){
			foreach($results as $r){
				if(!isset($this->config->{$r->config_name}))
					$this->config->{$r->config_name}=$r->config_value;
				if(!isset($this->config_front->{$r->config_name}) && $r->show_front==1)
					$this->config_front->{$r->config_name}=$r->config_value;
			}
		}
	}
	function doMethod($request)
	{
		global $wpdb;
		if(!empty($request))
		{
			$parameters = $request->get_params();
	        if(isset($parameters['method']) && method_exists($this,sanitize_text_field($parameters['method'])))
	        {
	        	$method=$parameters['method'];
	        	return $this->$method($request);       
	        }
    	}
    	return array();
	}
	function get_orders($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$wc_status=isset($parameters['wc_status'])?sanitize_text_field($parameters['wc_status']):"";
			$statuses_arr=array();
			$statuses=wc_get_order_statuses();
			if($wc_status!="")
				$statuses_arr=array($wc_status);
			else
			{
				foreach($statuses as $status => $title)
					$statuses_arr[]=$status;
			}
			$sql="select count(ID) as total from ".$wpdb->prefix."posts where post_type='shop_order' and post_status in ('".implode("','",array_map('esc_sql',$statuses_arr))."')";
			$result=$wpdb->get_row($sql);
			if(isset($result->total))
				$total_orders=$result->total;
			$limit=isset($parameters['limit'])?(int)$parameters['limit']:10;
			$start=isset($parameters['start'])?(int)$parameters['start']:0;
			$search=isset($parameters['search'])?sanitize_text_field($parameters['search']):"";
			$order_arr=isset($parameters['order'])?$parameters['order']:array();
			$start_date=isset($parameters['start_date'])?sanitize_text_field($parameters['start_date']):"";
			$end_date=isset($parameters['end_date'])?sanitize_text_field($parameters['end_date']):"";
			if($start_date!="")
				$start_date=" and p.post_date_gmt>='".date("Y-m-d 00:00:00",strtotime($start_date))."'";
			if($end_date!="")
				$end_date=" and p.post_date_gmt<='".date("Y-m-d 23:59:59",strtotime($end_date))."'";
			$total_filtered_orders=$total_orders;
			$order_by=array();
			if(count($order_arr)>0)
			{
				$sortables=array();
				foreach($order_arr as $order)
					$sortables[sanitize_text_field($order['column'])]=sanitize_text_field($order['dir']);
				foreach($sortables as $col => $order)
				{
					if($col==0 && $order!="")
						$order_by[]="p.ID ".(($order=='asc')?'asc':'desc');
					if($col==1 && $order!="")
						$order_by[]="p.post_date ".(($order=='asc')?'asc':'desc');
					if($col==2 && $order!="")
						$order_by[]="p.post_status ".(($order=='asc')?'asc':'desc');
					if($col==6 && $order!="")
						$order_by[]="CAST(order_total.meta_value AS DECIMAL) ".(($order=='asc')?'asc':'desc');
				}
			}
			if(count($order_by)==0)
				$order_by[]="p.ID desc";
			$join_order_total="";
			if(in_array('CAST(order_total.meta_value AS DECIMAL) asc',$order_by) || in_array('CAST(order_total.meta_value AS DECIMAL) desc',$order_by))
				$join_order_total="LEFT JOIN ".$wpdb->prefix."postmeta order_total on order_total.post_id=p.ID and order_total.meta_key='_order_total'";
			if($search!="")
			{
				foreach($statuses as $key => $val)
				{
					if(strtolower($val)==strtolower($search))
						$search=$key;
				}
				$sql="
				SELECT
				COUNT(DISTINCT(p.ID)) as total
				FROM
				".$wpdb->prefix."posts p
				LEFT JOIN ".$wpdb->prefix."postmeta m on m.post_id=p.ID
				where 
				p.post_type='shop_order' and post_status in ('".implode("','",array_map('esc_sql',$statuses_arr))."')
				".$start_date."
				".$end_date."
				and
				(
					p.ID like '%".esc_sql($search)."%' or
					p.post_status like '%".esc_sql($search)."%' or
					(m.meta_value like '%".esc_sql($search)."%' and m.meta_key in ('_billing_address_index','_shipping_address_index','_billing_last_name','_billing_email'))
				)
				";
				$result=$wpdb->get_row($sql);
				if(isset($result->total))
					$total_filtered_orders=$result->total;
				$sql="
				SELECT
				DISTINCT(p.ID)
				FROM
				".$wpdb->prefix."posts p
				LEFT JOIN ".$wpdb->prefix."postmeta m on m.post_id=p.ID
				".$join_order_total."
				where 
				p.post_type='shop_order' and post_status in ('".implode("','",array_map('esc_sql',$statuses_arr))."')
				".$start_date."
				".$end_date."
				and
				(
					p.ID like '%".esc_sql($search)."%' or
					p.post_status like '%".esc_sql($search)."%' or
					(m.meta_value like '%".esc_sql($search)."%' and m.meta_key in ('_billing_address_index','_shipping_address_index','_billing_last_name','_billing_email'))
				)
				group by p.ID
				order by ".implode(",",$order_by)."
				limit ".$start.",".$limit;
				$order_ids=$wpdb->get_results($sql);

				if(count($order_ids)>0)
				{
					$oids=array();
					if(count($order_ids)>0)
					{
						foreach($order_ids as $o)
							$oids[]=$o->ID;
						$results=$oids;
					}
				}
			    else
			      $results=array();
			}
			else
			{
				if($start_date!="" || $end_date!="")
				{
					$sql="
					SELECT
					COUNT(DISTINCT(p.ID)) as total
					FROM
					".$wpdb->prefix."posts p
					where 
					p.post_type='shop_order' and post_status in ('".implode("','",array_map('esc_sql',$statuses_arr))."')
					".$start_date."
					".$end_date;
					$result=$wpdb->get_row($sql);
					if(isset($result->total))
						$total_filtered_orders=$result->total;
				}
				else
					$total_filtered_orders=$total_orders;
				$sql="
				SELECT
				DISTINCT(p.ID)
				FROM
				".$wpdb->prefix."posts p
				".$join_order_total."
				where 
				p.post_type='shop_order' and p.post_status in ('".implode("','",array_map('esc_sql',$statuses_arr))."')
				".$start_date."
				".$end_date."
				order by ".implode(",",$order_by)."
				limit ".$start.",".$limit;
				$order_ids=$wpdb->get_results($sql);
				if(count($order_ids)>0)
				{
					$oids=array();
					if(count($order_ids)>0)
					{
						foreach($order_ids as $o)
							$oids[]=$o->ID;
						$results=$oids;
					}
				}
			    else
			      $results=array();
			}

			$orders=array();
			if(count($results))
			{
				foreach($results as $order_id)
				{
					$order_data=wc_get_order($order_id);
					if(!$order_data)
						continue;
					$order_details=$order_data->get_data();
					$order=new stdClass;
					$order->id=$order_details['id'];
					$order->status_orig=$order_details['status'];
					$order->status=((isset($statuses['wc-'.$order_details['status']]))?$statuses['wc-'.$order_details['status']]:$order_details['status']);
					$order->currency=$order_details['currency'];
					$order->currency_symbol=get_woocommerce_currency_symbol($order->currency);
					$order->total_refunded=$order_data->get_total_refunded();
					$order->total=$order_details['total'];
					$order->order_total=$order_data->get_total();
					if($order->total_refunded>0)
						$order->total=number_format(round(($order->total-$order->total_refunded),2),2,".",",");
					$order->date_created=$order_details['date_created']->__toString();
					$order->stamp=$order_details['date_created']->getOffsetTimestamp();
					$order->first_name=$order_details['billing']['first_name'];
					$order->last_name=$order_details['billing']['last_name'];
					$order->email=$order_details['billing']['email'];
					$order->payment_method_title=$order_details['payment_method_title'];
					$order->shipping_method_title=$order_data->get_shipping_method();
					$order->products=array();
					$items=$order_data->get_items();
					if(count($items))
					{
						foreach($items as $item)
						{
							$product=new stdClass;
							$product->product=$item->get_name();
							$product->qty=$item->get_quantity();
							$order->products[]=$product;
						}
					}
					$orders[]=$order;
				}
			}
			else
				$total_filtered_orders=0;
			$return=new stdClass;
			$return->id=0;
			$return->statuses=$this->get_order_statuses($request);
			$return->total_orders=$total_orders;
			$return->total_filtered_orders=$total_filtered_orders;
			$return->orders=$orders;
			$return->default_country_state=get_option('woocommerce_default_country');
			return array($return);
    	}
    	return array();
	}
	function get_order_statuses($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$statuses=array();
			$wc_statuses=wc_get_order_statuses();
			if(is_array($wc_statuses))
			{
				foreach($wc_statuses as $status => $title)
				{
					$stat=new stdClass;
					$stat->status=$status;
					$stat->title=$title;
					$stat->bg_color="";
					$stat->txt_color="";
					$statuses[]=$stat;
				}
				$sql="select * from ".$wpdb->prefix."honeybadger_custom_order_statuses where 1";
				$results=$wpdb->get_results($sql);
				if(is_array($results))
				{
					foreach($statuses as $status)
					{
						foreach($results as $r)
						{
							if($status->status==$r->custom_order_status)
							{
								$status->title=$r->custom_order_status_title;
								$status->bg_color=$r->bg_color;
								$status->txt_color=$r->txt_color;
							}
						}
					}
				}
			}
			$result=new stdClass;
			$result->id=0;
			$result->content=$statuses;
			$result->use_status_colors_on_wc=$this->config->use_status_colors_on_wc;
			return array($result);
		}
		return array();
	}
	function checkFirstTimeInstallation()
	{
		global $wpdb;
		$this->setDefaultOrderStatuses();
		$sql="update ".$wpdb->prefix."honeybadger_config set config_value='0' where config_name='first_time_installation'";
		$wpdb->query($sql);
	}
	function setDefaultOrderStatuses()
	{
		global $wpdb;
		$statuses=array();
		$statuses_result=$this->get_order_statuses(array('not'=>'empty'));
		if(isset($statuses_result[0]->content) && is_array($statuses_result[0]->content))
		{
			$statuses=$statuses_result[0]->content;
			foreach($statuses as $status)
			{
				if($status->status=="wc-pending" && $status->bg_color=="")
					$status->bg_color="#a1a2a4";
				if($status->status=="wc-pending" && $status->txt_color=="")
					$status->txt_color="#3f4043";
				if($status->status=="wc-processing" && $status->bg_color=="")
					$status->bg_color="#c6e1c6";
				if($status->status=="wc-processing" && $status->txt_color=="")
					$status->txt_color="#5b841b";
				if($status->status=="wc-on-hold" && $status->bg_color=="")
					$status->bg_color="#f8dda7";
				if($status->status=="wc-on-hold" && $status->txt_color=="")
					$status->txt_color="#94660c";
				if($status->status=="wc-completed" && $status->bg_color=="")
					$status->bg_color="#c8d7e1";
				if($status->status=="wc-completed" && $status->txt_color=="")
					$status->txt_color="#2e4453";
				if($status->status=="wc-cancelled" && $status->bg_color=="")
					$status->bg_color="#ce9999";
				if($status->status=="wc-cancelled" && $status->txt_color=="")
					$status->txt_color="#534040";
				if($status->status=="wc-refunded" && $status->bg_color=="")
					$status->bg_color="#b67e45";
				if($status->status=="wc-refunded" && $status->txt_color=="")
					$status->txt_color="#4b341c";
				if($status->status=="wc-failed" && $status->bg_color=="")
					$status->bg_color="#eba3a3";
				if($status->status=="wc-failed" && $status->txt_color=="")
					$status->txt_color="#761919";

				if($status->bg_color=="")
					$status->bg_color="#e5e5e5";
				if($status->txt_color=="")
					$status->txt_color="#777777";

				$status->status=sanitize_text_field($status->status);
				$status->title=sanitize_text_field($status->title);
				$status->bg_color=sanitize_hex_color($status->bg_color);
				$status->txt_color=sanitize_hex_color($status->txt_color);

				$sql="insert into ".$wpdb->prefix."honeybadger_custom_order_statuses set
				custom_order_status='".esc_sql($status->status)."',
				custom_order_status_title='".esc_sql($status->title)."',
				bg_color='".esc_sql($status->bg_color)."',
				txt_color='".esc_sql($status->txt_color)."',
				mdate='".time()."'";
				$wpdb->query($sql);
			}
		}
	}
	function save_order_statuses($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$status=isset($parameters['status'])?$parameters['status']:array();
			$title=isset($parameters['title'])?$parameters['title']:array();
			$bg_color=isset($parameters['bg_color'])?$parameters['bg_color']:array();
			$txt_color=isset($parameters['txt_color'])?$parameters['txt_color']:array();
			$use_status_colors_on_wc=isset($parameters['use_status_colors_on_wc'])?sanitize_text_field($parameters['use_status_colors_on_wc']):"";

			if(is_array($status) && count($status)>0 && is_array($title) && is_array($bg_color) && is_array($txt_color) && count($status)==count($title) && count($status)==count($bg_color) && count($status)==count($txt_color))
			{
				for($i=0;$i<count($status);$i++)
				{
					$sql="update ".$wpdb->prefix."honeybadger_custom_order_statuses set
					custom_order_status_title='".esc_sql(sanitize_text_field($title[$i]))."',
					bg_color='".esc_sql(sanitize_hex_color($bg_color[$i]))."',
					txt_color='".esc_sql(sanitize_hex_color($txt_color[$i]))."',
					mdate='".time()."'
					where custom_order_status='".esc_sql($status[$i])."'";
					if($wpdb->query($sql)<1)
					{
						$sql="insert into ".$wpdb->prefix."honeybadger_custom_order_statuses set
						custom_order_status='".esc_sql(sanitize_text_field($status[$i]))."',
						custom_order_status_title='".esc_sql(sanitize_text_field($title[$i]))."',
						bg_color='".esc_sql(sanitize_hex_color($bg_color[$i]))."',
						txt_color='".esc_sql(sanitize_hex_color($txt_color[$i]))."',
						mdate='".time()."'";
					}
					
					if(!$wpdb->query($sql) && $wpdb->last_error !== '')
					{
						$result=new stdClass;
						$result->id=0;
						$result->content=array('status'=>'error','msg'=>esc_html__("Something went wrong, please try again [1].","honeyb"));
						return array($result);
					}
				}
				$status_colors_on_wc="no";
				if($use_status_colors_on_wc=="on")
					$status_colors_on_wc="yes";
				$sql="update ".$wpdb->prefix."honeybadger_config set config_value='".esc_sql($status_colors_on_wc)."' where config_name='use_status_colors_on_wc'";
				if(!$wpdb->query($sql) && $wpdb->last_error !== '')
				{
					$result=new stdClass;
					$result->id=0;
					$result->content=array('status'=>'error','msg'=>esc_html__("Something went wrong, please try again [2].","honeyb"));
					return array($result);
				}
				$result=new stdClass;
				$result->id=0;
				$result->content=array('status'=>'ok','msg'=>esc_html__("Order statuses updated with success","honeyb"));
				return array($result);
			}
			
		}
		$result=new stdClass;
		$result->id=0;
		$result->content=array('status'=>'error','msg'=>esc_html__("Something went wrong, please try again [3].","honeyb"));
		return array($result);
	}
	function delete_custom_order_status($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$status=isset($parameters['status'])?sanitize_text_field($parameters['status']):"";
			if($status!="")
			{
				$sql="select count(ID) as total from ".$wpdb->prefix."posts where post_type='shop_order' and post_status='".esc_sql($status)."'";
				$result=$wpdb->get_row($sql);
				if(isset($result->total) && $result->total>0)
					return $this->returnError();
				else
				{
					$sql="delete from ".$wpdb->prefix."honeybadger_custom_order_statuses where custom_order_status='".esc_sql($status)."'";
					$wpdb->query($sql);
					if(!$wpdb->query($sql) && $wpdb->last_error !== '')
						return $this->returnError();
					else
						return $this->returnOk();
				}
			}
		}
	}
	function get_order_details($request, $remaining_products=array(),$last_order_id=0)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$order_id=isset($parameters['order_id'])?(int)$parameters['order_id']:0;
			if($order_id>0)
			{
				$this->recalculate_order_totals($order_id);
				$statuses=wc_get_order_statuses();
				$payment_gateways = WC()->payment_gateways->payment_gateways();
				$order_data=wc_get_order($order_id);
				$order_taxes = $order_data->get_taxes();
				$payment_method = $order_data->get_payment_method();
				$payment_title=isset( $payment_gateways[ $payment_method ] ) ? $payment_gateways[ $payment_method ]->get_title() : $payment_method;
				$order_details=$order_data->get_data();
				$order=new stdClass;
				$order->id=$order_details['id'];
				$order->last_order_id=$last_order_id;
				$order->status_orig=$order_details['status'];
				$order->status=((isset($statuses['wc-'.$order_details['status']]))?$statuses['wc-'.$order_details['status']]:$order_details['status']);
				$order->currency=$order_details['currency'];
				$order->currency_symbol=get_woocommerce_currency_symbol($order->currency);
				$order->total=$order_details['total'];
				$order->order_total=$order_data->get_total();
				$order->subtotal=$order_data->get_subtotal();
				$order->taxes=$order_data->get_tax_totals();
				$order->paid_on=$order_data->get_date_paid();
				$refunds=$order_data->get_refunds();
				$order->refunds=array();
				if ( $refunds ) 
				{
					foreach ( $refunds as $refund ) 
					{
						$who_refunded = new \WP_User( $refund->get_refunded_by() );
						$tmp=new stdClass;
						$tmp->id=$refund->get_id();
						$tmp->who_refunded='N/A';
						if ( $who_refunded->exists() )
							$tmp->who_refunded=$who_refunded->display_name;
						$tmp->date=$refund->get_date_created();
						$tmp->reason=$refund->get_reason();
						$tmp->amount=$refund->get_amount();
						$order->refunds[]=$tmp;
					}
				}
				$order->remaining_products=$remaining_products;
				$order->total_refunded=$order_data->get_total_refunded();
				$order->date_created=$order_details['date_created']->__toString();
				$order->stamp=$order_details['date_created']->getOffsetTimestamp();
				$order->payment_method_title=$payment_title;
				$order->shipping_method_title=$order_data->get_shipping_method();
				$order->coupons=$order_data->get_total_discount();
				$order->total_fees=$order_data->get_total_fees();
				$order->fees=array();
				$line_items_fee=$order_data->get_items( 'fee' );
				if($line_items_fee)
				{
					foreach($line_items_fee as $fee_id => $fee)
					{
						$tmp=new stdClass;
						$tmp->fee=$fee->get_name() ? $fee->get_name() : esc_html__( 'Fee', 'honeyb' );
						$tmp->value=$fee->get_total();
						$tmp->refunded=$order_data->get_total_refunded_for_item( $fee_id, 'fee' );
						$tmp->tax=0;
						if ( ( $tax_data = $fee->get_taxes() ) && wc_tax_enabled() )
						{
							foreach ( $order_taxes as $tax_item )
							{
								$tax_item_id    = $tax_item->get_rate_id();
								$tax_item_total = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';
								$tmp->tax=$tax_item_total;
							}
						}
						$order->fees[]=$tmp;
					}
				}
				$order->email_actions=$this->get_email_actions($order->status_orig);
				$order->statuses=$this->get_order_statuses($request);
				$order->customer_note=$order_data->get_customer_note();
				$order->order_notes=wc_get_order_notes(array('order_id'=>$order_id));
				$order->details=$order_details;
				$line_items_shipping = $order_data->get_items( 'shipping' );
				$order->refunded_shipping=0;
				foreach ( $line_items_shipping as $shipping_item_id => $shipping_item )
					$order->refunded_shipping+=$order_data->get_total_refunded_for_item( $shipping_item_id, 'shipping' );
				$order->products=array();
				$order->content='ok';
				$order->email_templates=$this->get_emails_simple($request);
				$order->email_attachments=$this->get_email_attachments($order->status_orig);
				$order->generable_attachments=$this->get_generable_attachments();
				$order->siblings=$this->getOrderSiblings($order_id);
				$order->static_attachments=$this->get_email_static_attachments($order->status_orig);
				$order_taxes=$order_data->get_taxes();
				$order->Items='';
				$items=$order_data->get_items( 'shipping' );
				foreach($items as $item_id => $item)
				{
					$Items=wc_get_order_item_meta($item_id,'Items');
					if($Items!='')
						$order->Items=$Items;
				}
				$items=$order_data->get_items();
				if(count($items))
				{
					foreach($items as $item_id => $item)
					{
						$product=new stdClass;
						$product->id=$item->get_product_id();
						$product->variation_id=$item->get_variation_id();
						$product_obj = wc_get_product($product->id);
						$product->image=wp_get_attachment_image_src( get_post_thumbnail_id( $product->id ), 'thumbnail' );
						$product->product=$item->get_name();
						$product->qty=$item->get_quantity();
						$product->price=$item->get_total();
						$product->subtotal=$item->get_subtotal();
						$product->subtotal_no_qty=$order_data->get_item_subtotal( $item, false, true );
						$product->tax=$item->get_total_tax();
						$product->sku=$product_obj->get_sku();
						$product->tax_refunded=0;
						$product->refunded = $order_data->get_total_refunded_for_item( $item_id );
						$refunded_qty = $order_data->get_qty_refunded_for_item( $item_id );
						if($refunded_qty)
							$product->refunded_qty=$refunded_qty;
						$tax_data = wc_tax_enabled() ? $item->get_taxes() : false;
						if ( $tax_data )
						{
							foreach ( $order_taxes as $tax_item )
							{
								$tax_item_id       = $tax_item->get_rate_id();
								$tax_item_total    = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';
								$tax_item_subtotal = isset( $tax_data['subtotal'][ $tax_item_id ] ) ? $tax_data['subtotal'][ $tax_item_id ] : '';

								if ( '' !== $tax_item_subtotal )
								{
									$round_at_subtotal = 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' );
									$tax_item_total    = wc_round_tax_total( $tax_item_total, $round_at_subtotal ? wc_get_rounding_precision() : null );
									$tax_item_subtotal = wc_round_tax_total( $tax_item_subtotal, $round_at_subtotal ? wc_get_rounding_precision() : null );
								}
								$refunded = $order_data->get_tax_refunded_for_item( $item_id, $tax_item_id );
								$product->tax_refunded=$refunded;
							}
						}
						$order->products[]=$product;
					}
				}
				return array($order);
			}
		}
	}
	function getOrderSiblings($order_id=0)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		$order_id=(int)$order_id;
		if($order_id>0)
		{
			$sql="select meta_value from ".$wpdb->prefix."postmeta where post_id='".esc_sql($order_id)."' and (meta_key='_honeybadger_split_from' or meta_key='_honeybadger_split_in')";
			$results=$wpdb->get_results($sql);
			$ids=array((string)$order_id);
			if(is_array($results))
			{
				foreach($results as $result)
					$ids=array_merge($ids,explode(",",$result->meta_value));
			}
			$new_ids=array();
			if(count($ids)>0)
			{
				$ids=array_unique($ids);
				sort($ids);
				while(true)
				{
					$sql="select meta_value from ".$wpdb->prefix."postmeta where post_id in ('".implode("','",array_map('esc_sql',$ids))."') and (meta_key='_honeybadger_split_from' or meta_key='_honeybadger_split_in')";
					$results=$wpdb->get_results($sql);
					if(is_array($results))
					{
						foreach($results as $result)
							$new_ids=array_merge($new_ids,explode(",",$result->meta_value));
					}
					$new_ids=array_unique($new_ids);
					sort($new_ids);
					$diff=array_diff($new_ids,$ids);
					if(count($diff)>0)
					{
						$ids=array_merge($ids,$new_ids);
						$ids=array_unique($ids);
						sort($ids);
					}
					else
						break;
				}
			}
			if(is_array($ids))
			{
				$new_ids=array();
				foreach($ids as $id)
				{
					if($id>0)
						$new_ids[]=$id;
				}
				$ids=$new_ids;
			}
			return $ids;
		}
		return array();
	}
	function get_generable_attachments()
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		$sql="select id, title from ".$wpdb->prefix."honeybadger_attachments where generable=1 and enabled=1 order by id";
		return $wpdb->get_results($sql);
	}
	function get_email_attachments($status="")
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		$status=sanitize_text_field($status);
		$filter_attachments=array();
		if($status!="")
		{
			$sql="select a.id from ".$wpdb->prefix."honeybadger_attachments a where a.attach_to_wc_emails like '%wc-".esc_sql($status)."%' and a.enabled=1";
			$results=$wpdb->get_results($sql);
			if(is_array($results))
			{
				foreach($results as $result)
					$filter_attachments[]=$result->id;
			}
			$sql="select a.attach_to_emails from ".$wpdb->prefix."honeybadger_attachments a where attach_to_emails<>'' and a.enabled=1";
			$results=$wpdb->get_results($sql);
			if(is_array($results))
			{
				$all_attached_to_emails=array();
				foreach($results as $result)
				{
					$tmp=explode(",",$result->attach_to_emails);
					if(is_array($tmp))
					{
						foreach($tmp as $t)
							$all_attached_to_emails[]=$t;
					}
				}
				if(count($all_attached_to_emails)>0)
				{
					$good_email_ids=array();
					$sql="select e.id from ".$wpdb->prefix."honeybadger_emails e where e.id in (".implode(",",array_map('esc_sql',$all_attached_to_emails)).") and e.statuses like '%".esc_sql($status)."%' and e.enabled=1";
					$results=$wpdb->get_results($sql);
					if(is_array($results))
					{
						foreach($results as $result)
							$good_email_ids[]=$result->id;
					}
					if(count($good_email_ids)>0)
					{
						foreach($good_email_ids as $email_id)
						{
							$sql="select a.id from ".$wpdb->prefix."honeybadger_attachments a where 
							a.enabled=1 and
							(a.attach_to_emails='".esc_sql($email_id)."' or
							a.attach_to_emails like '".esc_sql($email_id).",%' or
							a.attach_to_emails like '%,".esc_sql($email_id)."' or
							a.attach_to_emails like '%,".esc_sql($email_id).",%')";
							$results=$wpdb->get_results($sql);
							if(is_array($results))
							{
								foreach($results as $result)
									$filter_attachments[]=$result->id;
							}
						}
					}
				}
			}
			if(count($filter_attachments)>0)
			{
				$filter_attachments=array_unique($filter_attachments);
				$sql="select id, title, attach_to_wc_emails, attach_to_emails, generable from ".$wpdb->prefix."honeybadger_attachments where id in (".implode(",",array_map('esc_sql',$filter_attachments)).") and enabled=1";
				return $wpdb->get_results($sql);
			}
		}
		$sql="select id, title, attach_to_wc_emails, attach_to_emails, generable from ".$wpdb->prefix."honeybadger_attachments where (attach_to_wc_emails!='' or attach_to_emails!='') and enabled=1";
		return $wpdb->get_results($sql);
	}
	function get_email_static_attachments($status="")
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		$status=sanitize_text_field($status);
		$filter_attachments=array();
		$filter_sql="";
		if($status!="")
		{
			$sql="select a.id from ".$wpdb->prefix."honeybadger_static_attachments a where a.wc_emails like '%wc-".esc_sql($status)."%' and a.enabled=1";
			$results=$wpdb->get_results($sql);
			if(is_array($results))
			{
				foreach($results as $result)
					$filter_attachments[]=$result->id;
			}
			$sql="select a.emails from ".$wpdb->prefix."honeybadger_static_attachments a where emails<>'' and a.enabled=1";
			$results=$wpdb->get_results($sql);
			if(is_array($results))
			{
				$all_attached_to_emails=array();
				foreach($results as $result)
				{
					$tmp=explode(",",$result->emails);
					if(is_array($tmp))
					{
						foreach($tmp as $t)
							$all_attached_to_emails[]=$t;
					}
				}
				if(count($all_attached_to_emails)>0)
				{
					$good_email_ids=array();
					$sql="select e.id from ".$wpdb->prefix."honeybadger_emails e where e.id in (".implode(",",array_map('esc_sql',$all_attached_to_emails)).") and e.statuses like '%".esc_sql($status)."%' and e.enabled=1";
					$results=$wpdb->get_results($sql);
					if(is_array($results))
					{
						foreach($results as $result)
							$good_email_ids[]=$result->id;
					}
					if(count($good_email_ids)>0)
					{
						foreach($good_email_ids as $email_id)
						{
							$sql="select a.id from ".$wpdb->prefix."honeybadger_static_attachments a where 
							a.enabled=1 and
							(a.emails='".esc_sql($email_id)."' or
							a.emails like '".esc_sql($email_id).",%' or
							a.emails like '%,".esc_sql($email_id)."' or
							a.emails like '%,".esc_sql($email_id).",%')";
							$results=$wpdb->get_results($sql);
							if(is_array($results))
							{
								foreach($results as $result)
									$filter_attachments[]=$result->id;
							}
						}
					}
				}
			}
			if(count($filter_attachments)>0)
			{
				$filter_attachments=array_unique($filter_attachments);
				$filter_sql="select s.*, '' as filesize from ".$wpdb->prefix."honeybadger_static_attachments s where id in (".implode(",",array_map('esc_sql',$filter_attachments)).") and enabled=1";
			}
		}
		if($filter_sql=="")
			$sql="select s.*, '' as filesize from ".$wpdb->prefix."honeybadger_static_attachments s where enabled=1 order by title";
		else
			$sql=$filter_sql;
		$results=$wpdb->get_results($sql);
		if(is_array($results))
		{
			foreach($results as $result)
			{
				if(is_file(ABSPATH.$result->path))
					$result->filesize=filesize(ABSPATH.$result->path);
			}
		}
		return $results;
	}
	function get_email_actions($status="")
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		$status=sanitize_text_field($status);
		$order_status="";
		if($status!="")
			$order_status=" and statuses like'%wc-".esc_sql($status)."%'";
		$sql="select id, title, statuses from ".$wpdb->prefix."honeybadger_emails where enabled=1 and statuses<>''".$order_status." order by id";
		return $wpdb->get_results($sql);
	}
	function check_for_new_orders()
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		$last_order_id=isset($_POST['last_order_id'])?(int)$_POST['last_order_id']:0;
		if($last_order_id>0)
		{
			$statuses=wc_get_order_statuses();
			$statuses_arr=array();
			foreach($statuses as $status => $title)
				$statuses_arr[]=$status;
			$sql="select ID from ".$wpdb->prefix."posts where ID>'".esc_sql($last_order_id)."' and post_type='shop_order' and post_status in ('".implode("','",array_map('esc_sql',$statuses_arr))."') order by ID desc";
			$results=$wpdb->get_results($sql);
			if(is_array($results) && count($results)>0)
			{
				$return=new stdClass;
				$return->id=0;
				$return->content=count($results);
				$return->last_order_id=$results[0]->ID;
				return array($return);
			}
		}
	}
	function save_order_addresses($request)
	{
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$order_id=isset($parameters['order_id'])?(int)$parameters['order_id']:0;
			$customer_note=isset($parameters['customer_note'])?sanitize_text_field($parameters['customer_note']):"";
			if($order_id>0)
			{
				if($customer_note!="")
				{
					$order=wc_get_order($order_id);
					$order->set_customer_note(sanitize_text_field($customer_note));
					$order->save();
				}
				$save_vars=array('billing_first_name','billing_last_name','billing_company','billing_address_1','billing_address_2','billing_city','billing_postcode','billing_country','billing_state','billing_email','billing_phone','shipping_first_name','shipping_last_name','shipping_company','shipping_address_1','shipping_address_2','shipping_city','shipping_postcode','shipping_country','shipping_state','shipping_phone','transaction_id');
				foreach($save_vars as $var)
					update_post_meta( $order_id, '_'.sanitize_text_field($var), ((isset($parameters[$var]))?sanitize_text_field($parameters[$var]):"") );
				$order=wc_get_order($order_id);
				update_post_meta( $order_id, '_billing_address_index', implode( ' ', $order->get_address( 'billing' ) ) );
				update_post_meta( $order_id, '_shipping_address_index', implode( ' ', $order->get_address( 'shipping' ) ) );
				return $this->get_order_details($request);
			}
		}
	}
	function get_static_attachments()
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		$static_attachments_str=isset($_POST['static_attachments'])?sanitize_text_field($_POST['static_attachments']):"";
		$static_attachments=array();
		if($static_attachments_str!="")
			$static_attachments=explode(",",$static_attachments_str);
		$attachments=array();
		if(is_array($static_attachments) && count($static_attachments)>0)
		{
			$sql="select * from ".$wpdb->prefix."honeybadger_static_attachments where id in ('".implode("','",array_map('esc_sql',$static_attachments))."') and enabled=1 order by title";
			$results=$wpdb->get_results($sql);
			if(is_array($results))
			{
				foreach($results as $result)
				{
					if(is_file(ABSPATH.$result->path))
					{
						$attachment=ABSPATH.$result->path;
						$file_name=$this->removeMd5FromFilename(basename($attachment));
						$new_path=HONEYBADGER_UPLOADS_PATH."attachments/tmp/".$file_name;
						if($file_name!=$new_path && copy($attachment,$new_path))
							$attachments[]=$new_path;
						else
							$attachments[]=$attachment;
					}
				}
				if(count($attachments)>0)
				{
					if(isset($_POST['attachments_to_be_deleted']) && is_array($_POST['attachments_to_be_deleted']))
						$_POST['attachments_to_be_deleted']=array_merge($_POST['attachments_to_be_deleted'],$attachments);
					else
						$_POST['attachments_to_be_deleted']=$attachments;
				}
				return $attachments;
			}
		}
		return array();
	}
	function save_order_status($request)
	{
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$order_id=isset($parameters['order_id'])?(int)$parameters['order_id']:0;
			$order_status=isset($parameters['order_status'])?sanitize_text_field($parameters['order_status']):"";
			if($order_id>0 && $order_status!="")
			{
				$order = new \WC_Order($order_id);
				if (!empty($order))
					$order->update_status($order_status);
				return $this->get_order_details($request);
			}
		}
	}
	function get_wc_emails($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$sql="select * from ".$wpdb->prefix."honeybadger_custom_order_statuses where custom_order_status !='wc-pending' order by id";
			$results=$wpdb->get_results($sql);
			$all_states=array();
			$templates=array(
				'wc-cancelled'=>'admin-cancelled-order.php',
				'wc-failed'=>'admin-failed-order.php',
				'wc-admin-new-order'=>'admin-new-order.php',
				'wc-completed'=>'customer-completed-order.php',
				'wc-customer-invoice'=>'customer-invoice.php',
				'wc-customer-new-account'=>'customer-new-account.php',
				'wc-customer-note'=>'customer-note.php',
				'wc-on-hold'=>'customer-on-hold-order.php',
				'wc-processing'=>'customer-processing-order.php',
				'wc-refunded'=>'customer-refunded-order.php',
				'wc-customer-reset-password'=>'customer-reset-password.php',
				'wc-email-default'=>'email-default.php',
				'wc-email-header'=>'email-header.php',
				'wc-email-footer'=>'email-footer.php',
				'wc-email-styles'=>'email-styles.php',
				'wc-email-addresses'=>'email-addresses.php',
				'wc-email-customer-details'=>'email-customer-details.php',
				'wc-email-downloads'=>'email-downloads.php',
				'wc-email-order-details'=>'email-order-details.php',
				'wc-email-order-items'=>'email-order-items.php'
			);
			$titles=array(
				'wc-cancelled'=>esc_html__("Cancelled order","honeyb"),
				'wc-failed'=>esc_html__("Failed order","honeyb"),
				'wc-admin-new-order'=>esc_html__("New order","honeyb"),
				'wc-customer-invoice'=>esc_html__("Customer invoice / Order details","honeyb"),
				'wc-customer-new-account'=>esc_html__("New account","honeyb"),
				'wc-customer-note'=>esc_html__("Customer note","honeyb"),
				'wc-customer-reset-password'=>esc_html__("Reset password","honeyb"),
				'wc-email-default'=>'',
				'wc-email-header'=>esc_html__('Email Header ADVANCED'),
				'wc-email-footer'=>esc_html__('Email Footer ADVANCED'),
				'wc-email-styles'=>esc_html__('Email Styles ADVANCED'),
				'wc-email-addresses'=>esc_html__('Email Addresses ADVANCED'),
				'wc-email-customer-details'=>esc_html__('Email Customer Details ADVANCED'),
				'wc-email-downloads'=>esc_html__('Email Downloads ADVANCED'),
				'wc-email-order-details'=>esc_html__('Email Order Details ADVANCED'),
				'wc-email-order-items'=>esc_html__('Email Order Items ADVANCED')
			);
			$job_done=array();
			if(count($results))
			{
				foreach($results as $cs)
				{
					$all_states[]=$cs->custom_order_status;
					$sql="update ".$wpdb->prefix."honeybadger_wc_emails set mdate=".time()." where wc_status='".esc_sql($cs->custom_order_status)."'";
					if($wpdb->query($sql)<1)
					{
						$tpl=isset($templates[$cs->custom_order_status])?$templates[$cs->custom_order_status]:$templates['wc-email-default'];
						$sql="insert into ".$wpdb->prefix."honeybadger_wc_emails set wc_status='".esc_sql($cs->custom_order_status)."', 
						title='".esc_sql($cs->custom_order_status_title)."',
						template='".esc_sql($tpl)."',
						mdate='".(time()+1)."'";
						$wpdb->query($sql);
					}
					$job_done[]=$cs->custom_order_status;
				}
			}
			foreach($templates as $tmpl => $path)
			{

				if(in_array($tmpl,$job_done))
					continue;
				$all_states[]=$tmpl;
				$sql="update ".$wpdb->prefix."honeybadger_wc_emails set mdate=".time()." where wc_status='".esc_sql($tmpl)."'";
				if($wpdb->query($sql)<1)
				{
					$title=isset($titles[$tmpl])?$titles[$tmpl]:"";
					$sql="insert into ".$wpdb->prefix."honeybadger_wc_emails set wc_status='".esc_sql($tmpl)."', 
					title='".esc_sql($title)."',
					template='".esc_sql($path)."',
					mdate='".(time()+1)."'";
					$wpdb->query($sql);
				}
			}
			
			$sql="delete from ".$wpdb->prefix."honeybadger_wc_emails where wc_status not in ('".implode("','",array_map('esc_sql',$all_states))."')";
			$wpdb->query($sql);
			$sql="select * from ".$wpdb->prefix."honeybadger_wc_emails where wc_status!='wc-email-default' order by id";
			$results=$wpdb->get_results($sql);
			if(is_array($results))
			{
				foreach($results as $result)
				{
					if($result->wc_status=='wc-email-header')
					{
						$result->content=file_get_contents(HONEYBADGER_UPLOADS_PATH."/emails/email-header.php");
						if(is_file(get_template_directory().'/woocommerce/emails/email-header.php'))
							$result->subject='has_override';
					}
					if($result->wc_status=='wc-email-footer')
					{
						$result->content=file_get_contents(HONEYBADGER_UPLOADS_PATH."/emails/email-footer.php");
						if(is_file(get_template_directory().'/woocommerce/emails/email-footer.php'))
							$result->subject='has_override';
					}
					if($result->wc_status=='wc-email-styles')
					{
						$result->content=file_get_contents(HONEYBADGER_UPLOADS_PATH."/emails/email-styles.php");
						if(is_file(get_template_directory().'/woocommerce/emails/email-styles.php'))
							$result->subject='has_override';
					}
					if($result->wc_status=='wc-email-addresses')
					{
						$result->content=file_get_contents(HONEYBADGER_UPLOADS_PATH."/emails/email-addresses.php");
						if(is_file(get_template_directory().'/woocommerce/emails/email-addresses.php'))
							$result->subject='has_override';
					}
					if($result->wc_status=='wc-email-customer-details')
					{
						$result->content=file_get_contents(HONEYBADGER_UPLOADS_PATH."/emails/email-customer-details.php");
						if(is_file(get_template_directory().'/woocommerce/emails/email-customer-details.php'))
							$result->subject='has_override';
					}
					if($result->wc_status=='wc-email-downloads')
					{
						$result->content=file_get_contents(HONEYBADGER_UPLOADS_PATH."/emails/email-downloads.php");
						if(is_file(get_template_directory().'/woocommerce/emails/email-downloads.php'))
							$result->subject='has_override';
					}
					if($result->wc_status=='wc-email-order-details')
					{
						$result->content=file_get_contents(HONEYBADGER_UPLOADS_PATH."/emails/email-order-details.php");
						if(is_file(get_template_directory().'/woocommerce/emails/email-order-details.php'))
							$result->subject='has_override';
					}
					if($result->wc_status=='wc-email-order-items')
					{
						$result->content=file_get_contents(HONEYBADGER_UPLOADS_PATH."/emails/email-order-items.php");
						if(is_file(get_template_directory().'/woocommerce/emails/email-order-items.php'))
							$result->subject='has_override';
					}
				}
			}
			$show_images_in_emails="";
			$email_image_sizes="";
			$show_sku_in_emails="";
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
			}
			$return=new stdClass;
			$return->id=0;
			$return->content=$results;
			$return->other_settings=array(
				'woocommerce_email_from_name'=>get_option('woocommerce_email_from_name'),
				'woocommerce_email_from_address'=>get_option('woocommerce_email_from_address'),
				'woocommerce_email_header_image'=>get_option('woocommerce_email_header_image'),
				'woocommerce_email_footer_text'=>get_option('woocommerce_email_footer_text'),
				'woocommerce_email_base_color'=>get_option('woocommerce_email_base_color'),
				'woocommerce_email_background_color'=>get_option('woocommerce_email_background_color'),
				'woocommerce_email_body_background_color'=>get_option('woocommerce_email_body_background_color'),
				'woocommerce_email_text_color'=>get_option('woocommerce_email_text_color'),
				'admin_email'=>get_option('woocommerce_email_from_address'),
				'blogname'=>get_option('blogname'),
				'show_images_in_emails'=>$show_images_in_emails,
				'email_image_sizes'=>$email_image_sizes,
				'show_sku_in_emails'=>$show_sku_in_emails
			);
			$return->preview_url=wp_nonce_url( site_url( '?honeybadger_preview_woocommerce_mail=true' ), 'honeybadger_preview-mail' );
			$return->email_attachments=$this->get_email_attachments();
			$sql="select s.*, '' as filesize from ".$wpdb->prefix."honeybadger_static_attachments s where wc_emails!='' and enabled=1 order by title";
			$results2=$wpdb->get_results($sql);
			if(is_array($results2))
			{
				foreach($results2 as $r2)
				{
					if(is_file(ABSPATH.$r2->path))
						$r2->filesize=filesize(ABSPATH.$r2->path);
				}
			}
			$return->static_attachments=$results2;
			return array($return);
		}
	}
	function get_wc_advanced_tpl($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$results=array();
			$parameters = $request->get_params();
			$id=isset($parameters['id'])?(int)$parameters['id']:0;
			$type=isset($parameters['type'])?(int)$parameters['type']:0;
			if($id>0)
			{
				$sql="select * from ".$wpdb->prefix."honeybadger_wc_emails where id='".esc_sql($id)."'";
				$row=$wpdb->get_row($sql);
				if(isset($row->id))
				{
					$result=new stdClass;
					if($row->wc_status=='wc-email-header')
					{
						if($type==0)
							$result->content=file_get_contents(WP_PLUGIN_DIR."/woocommerce/templates/emails/email-header.php");
						else
						{
							if(is_file(get_template_directory().'/woocommerce/emails/email-header.php'))
								$result->content=file_get_contents(get_template_directory().'/woocommerce/emails/email-header.php');
						}
					}
					if($row->wc_status=='wc-email-footer')
					{
						if($type==0)
							$result->content=file_get_contents(WP_PLUGIN_DIR."/woocommerce/templates/emails/email-footer.php");
						else
						{
							if(is_file(get_template_directory().'/woocommerce/emails/email-footer.php'))
								$result->content=file_get_contents(get_template_directory().'/woocommerce/emails/email-footer.php');
						}
					}
					if($row->wc_status=='wc-email-styles')
					{
						if($type==0)
							$result->content=file_get_contents(WP_PLUGIN_DIR."/woocommerce/templates/emails/email-styles.php");
						else
						{
							if(is_file(get_template_directory().'/woocommerce/emails/email-styles.php'))
								$result->content=file_get_contents(get_template_directory().'/woocommerce/emails/email-styles.php');
						}
					}
					if($row->wc_status=='wc-email-addresses')
					{
						if($type==0)
							$result->content=file_get_contents(WP_PLUGIN_DIR."/woocommerce/templates/emails/email-addresses.php");
						else
						{
							if(is_file(get_template_directory().'/woocommerce/emails/email-addresses.php'))
								$result->content=file_get_contents(get_template_directory().'/woocommerce/emails/email-addresses.php');
						}
					}
					if($row->wc_status=='wc-email-customer-details')
					{
						if($type==0)
							$result->content=file_get_contents(WP_PLUGIN_DIR."/woocommerce/templates/emails/email-customer-details.php");
						else
						{
							if(is_file(get_template_directory().'/woocommerce/emails/email-customer-details.php'))
								$result->content=file_get_contents(get_template_directory().'/woocommerce/emails/email-customer-details.php');
						}
					}
					if($row->wc_status=='wc-email-downloads')
					{
						if($type==0)
							$result->content=file_get_contents(WP_PLUGIN_DIR."/woocommerce/templates/emails/email-downloads.php");
						else
						{
							if(is_file(get_template_directory().'/woocommerce/emails/email-downloads.php'))
								$result->content=file_get_contents(get_template_directory().'/woocommerce/emails/email-downloads.php');
						}
					}
					if($row->wc_status=='wc-email-order-details')
					{
						if($type==0)
							$result->content=file_get_contents(WP_PLUGIN_DIR."/woocommerce/templates/emails/email-order-details.php");
						else
						{
							if(is_file(get_template_directory().'/woocommerce/emails/email-order-details.php'))
								$result->content=file_get_contents(get_template_directory().'/woocommerce/emails/email-order-details.php');
						}
					}
					if($row->wc_status=='wc-email-order-items')
					{
						if($type==0)
							$result->content=file_get_contents(WP_PLUGIN_DIR."/woocommerce/templates/emails/email-order-items.php");
						else
						{
							if(is_file(get_template_directory().'/woocommerce/emails/email-order-items.php'))
								$result->content=file_get_contents(get_template_directory().'/woocommerce/emails/email-order-items.php');
						}
					}
					$results[]=$result;
				}
			}
			$return=new stdClass;
			$return->id=0;
			$return->content=$results;
			return array($return);
		}
	}
	function save_wc_email($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$id=isset($parameters['id'])?(int)$parameters['id']:0;
			$subject=isset($parameters['subject'])?sanitize_text_field($parameters['subject']):"";
			$heading=isset($parameters['heading'])?wp_kses_post($parameters['heading']):"";
			$subheading=isset($parameters['subheading'])?wp_kses_post($parameters['subheading']):"";
			$content=isset($parameters['content'])?wp_kses_post($parameters['content']):"";
			$content_orig=isset($parameters['content'])?$parameters['content']:"";
			$other_subject=isset($parameters['other_subject'])?wp_kses_post($parameters['other_subject']):"";
			$other_heading=isset($parameters['other_heading'])?wp_kses_post($parameters['other_heading']):"";
			$other_subheading_1=isset($parameters['other_subheading_1'])?wp_kses_post($parameters['other_subheading_1']):"";
			$other_subheading_2=isset($parameters['other_subheading_2'])?wp_kses_post($parameters['other_subheading_2']):"";
			$email_bcc=isset($parameters['email_bcc'])?sanitize_email($parameters['email_bcc']):"";
			$enabled=isset($parameters['enabled'])?sanitize_text_field($parameters['enabled']):"";
			if($enabled=="on")
				$enabled=1;
			$sql="update ".$wpdb->prefix."honeybadger_wc_emails set
			subject='".esc_sql($subject)."',
			heading='".esc_sql($heading)."',
			subheading='".esc_sql($subheading)."',
			content='".esc_sql($content)."',
			other_subject='".esc_sql($other_subject)."',
			other_heading='".esc_sql($other_heading)."',
			other_subheading_1='".esc_sql($other_subheading_1)."',
			other_subheading_2='".esc_sql($other_subheading_2)."',
			email_bcc='".esc_sql($email_bcc)."',
			enabled='".esc_sql($enabled)."',
			mdate='".(time()+1)."'
			where id='".esc_sql($id)."'";
			if(!$wpdb->query($sql) && $wpdb->last_error !== '')
				return $this->returnError();
			$sql="select * from ".$wpdb->prefix."honeybadger_wc_emails where id='".esc_sql($id)."'";
			$result=$wpdb->get_row($sql);
			if(isset($result->id))
			{
				//these are the advanced WC email templates, we cannot sanitize them, but we'll notify admin every time is changed
				if($result->wc_status=='wc-email-header')
					file_put_contents(HONEYBADGER_UPLOADS_PATH."emails/email-header.php",$content_orig);
				if($result->wc_status=='wc-email-footer')
					file_put_contents(HONEYBADGER_UPLOADS_PATH."emails/email-footer.php",$content_orig);
				if($result->wc_status=='wc-email-styles')
					file_put_contents(HONEYBADGER_UPLOADS_PATH."emails/email-styles.php",$content_orig);
				if($result->wc_status=='wc-email-addresses')
					file_put_contents(HONEYBADGER_UPLOADS_PATH."emails/email-addresses.php",$content_orig);
				if($result->wc_status=='wc-email-customer-details')
					file_put_contents(HONEYBADGER_UPLOADS_PATH."emails/email-customer-details.php",$content_orig);
				if($result->wc_status=='wc-email-downloads')
					file_put_contents(HONEYBADGER_UPLOADS_PATH."emails/email-downloads.php",$content_orig);
				if($result->wc_status=='wc-email-order-details')
					file_put_contents(HONEYBADGER_UPLOADS_PATH."emails/email-order-details.php",$content);
				if($result->wc_status=='wc-email-order-items')
					file_put_contents(HONEYBADGER_UPLOADS_PATH."emails/email-order-items.php",$content_orig);
				$this->sendAdminAdvWcEmailModifiedNotification($result->wc_status);
			}
		}
		return $this->returnOk();
	}
	function sendAdminAdvWcEmailModifiedNotification($email_tpl="")
	{
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		$admin_email=get_bloginfo('admin_email');
		wp_mail($admin_email, "WC Advanced Email Template Saved","Email template: ".$email_tpl);
	}
	function save_other_email_settings($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$woocommerce_email_from_name=isset($parameters['woocommerce_email_from_name'])?sanitize_text_field($parameters['woocommerce_email_from_name']):"";
			$woocommerce_email_from_address=isset($parameters['woocommerce_email_from_address'])?sanitize_email($parameters['woocommerce_email_from_address']):"";
			$woocommerce_email_header_image=isset($parameters['woocommerce_email_header_image'])?sanitize_url($parameters['woocommerce_email_header_image']):"";
			$woocommerce_email_footer_text=isset($parameters['woocommerce_email_footer_text'])?wp_kses_post($parameters['woocommerce_email_footer_text']):"";
			$woocommerce_email_base_color=isset($parameters['woocommerce_email_base_color'])?sanitize_hex_color($parameters['woocommerce_email_base_color']):"";
			$woocommerce_email_background_color=isset($parameters['woocommerce_email_background_color'])?sanitize_hex_color($parameters['woocommerce_email_background_color']):"";
			$woocommerce_email_body_background_color=isset($parameters['woocommerce_email_body_background_color'])?sanitize_hex_color($parameters['woocommerce_email_body_background_color']):"";
			$woocommerce_email_text_color=isset($parameters['woocommerce_email_text_color'])?sanitize_hex_color($parameters['woocommerce_email_text_color']):"";
			$show_images_in_emails=isset($parameters['show_images_in_emails'])?sanitize_text_field($parameters['show_images_in_emails']):"no";
			$email_image_sizes=isset($parameters['email_image_sizes'])?sanitize_text_field($parameters['email_image_sizes']):"100x50";
			$show_sku_in_emails=isset($parameters['show_sku_in_emails'])?sanitize_text_field($parameters['show_sku_in_emails']):"no";

			$sql="update ".$wpdb->prefix."honeybadger_config set config_value='".esc_sql($show_images_in_emails)."' where config_name='show_images_in_emails'";
			$wpdb->query($sql);
			$sql="update ".$wpdb->prefix."honeybadger_config set config_value='".esc_sql($email_image_sizes)."' where config_name='email_image_sizes'";
			$wpdb->query($sql);
			$sql="update ".$wpdb->prefix."honeybadger_config set config_value='".esc_sql($show_sku_in_emails)."' where config_name='show_sku_in_emails'";
			$wpdb->query($sql);
			$options=array('woocommerce_email_from_name','woocommerce_email_from_address','woocommerce_email_header_image','woocommerce_email_footer_text','woocommerce_email_base_color','woocommerce_email_background_color','woocommerce_email_body_background_color','woocommerce_email_text_color');

			foreach($options as $option)
				update_option($option,$$option);
			
			return $this->returnOk();
		}
	}
	function do_order_wc_action($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$order_id=isset($parameters['order_id'])?(int)$parameters['order_id']:0;
			$action=isset($parameters['action'])?sanitize_text_field($parameters['action']):"";
			if($order_id>0 && $action!="")
			{
				if($action=='new')
				{
					add_filter( 'woocommerce_new_order_email_allows_resend', '__return_true' );
					$mailer = WC()->mailer()->get_emails()['WC_Email_New_Order'];
					$mailer->trigger( $order_id );
					return $this->returnOk();
				}
				if($action=='invoice')
				{
					$mailer = WC()->mailer()->get_emails()['WC_Email_Customer_Invoice'];
					$mailer->trigger( $order_id );
					return $this->returnOk();
				}
				if($action=='download')
				{
					wc_downloadable_product_permissions( $order_id, false );
					return $this->returnOk();
				}
			}
		}
		return $this->returnError();
	}
	function enable_all_wc_email_templates($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$sql="update ".$wpdb->prefix."honeybadger_wc_emails set enabled=1 where 1";
			if(!$wpdb->query($sql) && $wpdb->last_error !== '')
				return $this->returnError();
			else
				return $this->returnOk();
		}
		return $this->returnError();
	}
	function disable_all_wc_email_templates($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$sql="update ".$wpdb->prefix."honeybadger_wc_emails set enabled=0 where 1";
			if(!$wpdb->query($sql) && $wpdb->last_error !== '')
				return $this->returnError();
			else
				return $this->returnOk();
		}
		return $this->returnError();
	}
	function load_defaults_for_wc_email_template($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$wc_status=isset($parameters['wc_status'])?sanitize_text_field($parameters['wc_status']):"";
			$placeholder_subject=isset($parameters['placeholder_subject'])?sanitize_text_field($parameters['placeholder_subject']):"";
			$placeholder_heading=isset($parameters['placeholder_heading'])?wp_kses_post($parameters['placeholder_heading']):"";
			$placeholder_subheading=isset($parameters['placeholder_subheading'])?wp_kses_post($parameters['placeholder_subheading']):"";
			$placeholder_other_subject=isset($parameters['placeholder_other_subject'])?wp_kses_post($parameters['placeholder_other_subject']):"";
			$placeholder_other_heading=isset($parameters['placeholder_other_heading'])?wp_kses_post($parameters['placeholder_other_heading']):"";
			$placeholder_other_subheading_1=isset($parameters['placeholder_other_subheading_1'])?wp_kses_post($parameters['placeholder_other_subheading_1']):"";
			$placeholder_other_subheading_2=isset($parameters['placeholder_other_subheading_2'])?wp_kses_post($parameters['placeholder_other_subheading_2']):"";
			$placeholder_content=isset($parameters['placeholder_content'])?wp_kses_post($parameters['placeholder_content']):"";
			if($wc_status!="")
			{
				$sql="update ".$wpdb->prefix."honeybadger_wc_emails set 
				subject='".esc_sql($placeholder_subject)."',
				heading='".esc_sql($placeholder_heading)."',
				subheading='".esc_sql($placeholder_subheading)."',
				content='".esc_sql($placeholder_content)."',
				other_subject='".esc_sql($placeholder_other_subject)."',
				other_heading='".esc_sql($placeholder_other_heading)."',
				other_subheading_1='".esc_sql($placeholder_other_subheading_1)."',
				other_subheading_2='".esc_sql($placeholder_other_subheading_2)."'
				where wc_status='".esc_sql($wc_status)."'";
				if(!$wpdb->query($sql) && $wpdb->last_error !== '')
					return $this->returnError();
				else
					return $this->returnOk();
			}
		}
		return $this->returnError();
	}
	function honeybadger_preview_woocommerce_mail()
	{
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(isset($_POST['honeybadger_preview_woocommerce_mail']) && $_POST['honeybadger_preview_woocommerce_mail']=='true')
		{
		    if ( isset( $_POST['honeybadger_preview_woocommerce_mail'] ) )
		    {
				if ( ! ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'honeybadger_preview-mail' ) ) ) {
				die( 'Security check' );
				}
				$mailer = WC()->mailer();
				$email_heading = esc_html__( 'HTML email template', 'woocommerce' );
				ob_start();
				include WP_PLUGIN_DIR . '/woocommerce/includes/admin/views/html-email-template-preview.php';
				$message = ob_get_clean();
				$email = new \WC_Email();
				$message = apply_filters( 'woocommerce_mail_content', $email->style_inline( $mailer->wrap_message( $email_heading, $message ) ) );

				$result=new stdClass;
				$result->id=0;
				$result->content=$message;
				return array($result);
				
				exit;
			}
		}
	}
	function get_custom_email_html( $order, $heading, $mailer, $tpl_id, $request, $other_details=array() )
	{
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		$parameters = $request->get_params();
		$template=HONEYBADGER_PLUGIN_PATH."includes/emails/custom_email.php";
		$template_name="includes/emails/custom_email.php";
		$date_format=isset($parameters['date_format'])?sanitize_text_field($parameters['date_format']):"Y-m-d H:i:s";
		return wc_get_template_html( $template_name, array(
			'order'         => $order,
			'email_heading' => $heading,
			'sent_to_admin' => false,
			'plain_text'    => false,
			'email'         => $mailer,
			'tpl_id'		=> $tpl_id,
			'date_format'   => $date_format,
			'honeybadger'   => $this,
			'other_details' => $other_details
		),
		$template,
		HONEYBADGER_PLUGIN_PATH
		);
	}
	function get_send_email_html( $order, $heading, $mailer, $content, $request )
	{
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		$parameters = $request->get_params();
		$template=HONEYBADGER_PLUGIN_PATH."includes/emails/send_email.php";
		$template_name="includes/emails/send_email.php";
		$date_format=isset($parameters['date_format'])?sanitize_text_field($parameters['date_format']):"Y-m-d H:i:s";
		return wc_get_template_html( $template_name, array(
			'order'         => $order,
			'email_heading' => $heading,
			'sent_to_admin' => false,
			'plain_text'    => false,
			'email'         => $mailer,
			'content'		=> $content,
			'date_format'   => $date_format,
			'honeybadger'   => $this
		),
		$template,
		HONEYBADGER_PLUGIN_PATH
		);
	}
	function send_email_template_test($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$id=isset($parameters['id'])?(int)$parameters['id']:0;
			$oid=isset($parameters['oid'])?(int)$parameters['oid']:0;
			$supplier_order_id=isset($parameters['supplier_order_id'])?(int)$parameters['supplier_order_id']:0;
			$send_to=isset($parameters['send_to'])?sanitize_email($parameters['send_to']):"";
			$supplier_order_str=isset($parameters['supplier_order'])?sanitize_text_field($parameters['supplier_order']):"";
			$attachment_ids_str=isset($parameters['attachment_ids'])?sanitize_text_field($parameters['attachment_ids']):"";
			$attachments=isset($_FILES['file'])?$_FILES['file']:array();
			$static_attachments_str=isset($_POST['static_attachments'])?sanitize_text_field($_POST['static_attachments']):"";
			$static_attachments=array();
			if($static_attachments_str!="")
				$static_attachments=explode(",",$static_attachments_str);
			$attachment_ids=array();
			if($attachment_ids_str!="")
				parse_str($attachment_ids_str,$attachment_ids);
			$supplier_order=array();
			if($supplier_order_str!="")
				parse_str($supplier_order_str,$supplier_order);
			if($id>0 && $send_to!="" && is_email($send_to))
			{
				$all_attachments=array();
				$remove_files=array();
				$sql="select * from ".$wpdb->prefix."honeybadger_emails where id='".esc_sql($id)."'";
				$email=$wpdb->get_row($sql);
				if(isset($email->id))
				{
					if($oid==0)
					{
						$sql="select ID from ".$wpdb->prefix."posts WHERE post_type='shop_order' and post_status='wc-completed' order by post_date desc limit 1";
						$result=$wpdb->get_row($sql);
						if(isset($result->ID))
							$oid=$result->ID;
					}
					$sql="select id, title, keep_files from ".$wpdb->prefix."honeybadger_attachments where id in ('".implode("','",array_map('esc_sql',$attachment_ids))."')";
					$attachment_folders=$wpdb->get_results($sql);
					if(isset($attachments['name']) && is_array($attachments['name']))
					{
						foreach($attachments['name'] as $index => $file_name)
						{
							$the_folder="tmp";
							$the_file_name="";
							if(isset($attachment_ids[$index]))
							{
								foreach($attachment_folders as $folder)
								{
									if($folder->id==$attachment_ids[$index])
									{
										$the_folder=$this->getFolderName($folder->title);
										$the_file_name=$the_folder;
										if($folder->keep_files==0)
											$the_folder="tmp";
										break;
									}
								}
							}
							$file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
							if($the_folder=="tmp")
							{
								if($the_file_name!="")
								{
									$the_folder=$the_file_name;
									$file_ext = pathinfo($attachments['name'][$index], PATHINFO_EXTENSION);
									if($supplier_order_id>0)
										$the_folder = $the_folder."_".$supplier_order_id.".".$file_ext;
									else
										$the_folder = $the_folder."_".$oid.".".$file_ext;
								}
								else
									$the_folder=$attachments['name'][$index];
								$target_file=HONEYBADGER_UPLOADS_PATH."attachments/tmp/".$the_folder;
								$remove_files[]=$target_file;
							}
							else
							{
								if($supplier_order_id>0)
									$md5=md5("-938uqtuiagksrbs".$the_folder."_".$supplier_order_id.".".$file_ext.microtime().rand(1,9999));
								else
									$md5=md5("-938uqtuiagksrbs".$the_folder."_".$oid.".".$file_ext.microtime().rand(1,9999));
								if($supplier_order_id>0)
									$target_file=HONEYBADGER_UPLOADS_PATH."attachments/".$the_folder."/".$md5."_".$the_folder."_".$supplier_order_id.".".$file_ext;
								else
									$target_file=HONEYBADGER_UPLOADS_PATH."attachments/".$the_folder."/".$md5."_".$the_folder."_".$oid.".".$file_ext;
							}
							$this->unlinkAttachmentFileDuplicate($target_file);

							if (!function_exists('wp_handle_upload'))
					        	require_once(ABSPATH . 'wp-admin/includes/file.php');
					        $uploadedfile = array(
					            'name'     => $attachments['name'][0],
					            'type'     => $attachments['type'][0],
					            'tmp_name' => $attachments['tmp_name'][0],
					            'error'    => $attachments['error'][0],
					            'size'     => $attachments['size'][0]
					        );
					        $upload_overrides = array( 'test_form' => false );
							$movefile=wp_handle_upload($uploadedfile, $upload_overrides);
							if ( $movefile && !isset( $movefile['error'] ) && isset($movefile['file']) && file_exists($movefile['file']))
							{
								copy($movefile['file'],$target_file);
								unlink($movefile['file']);
								$all_attachments[]=$target_file;
							}
						}
					}

					if(is_array($static_attachments) && count($static_attachments)>0)
						$all_attachments=array_merge($all_attachments,$this->get_static_attachments());
					
					$mailer = WC()->mailer();
					$order = wc_get_order( $oid );
					$subject=$email->subject;
					$subject=str_ireplace("{site_title}", get_bloginfo( 'name', 'display' ) ,$subject);
					$subject=str_ireplace("{site_url}", get_bloginfo( 'url', 'display' ) ,$subject);
					if($order)
					{
					    $subject=str_ireplace("{customer}", $order->get_billing_first_name() ,$subject);
					    $subject=str_ireplace("{customer_full_name}", $order->get_formatted_billing_full_name() ,$subject);
					    $subject=str_ireplace("{order_number}", $order->get_id() ,$subject);
					    
					    $subject=str_ireplace("{order_date}", wc_format_datetime( $order->get_date_created() ) ,$subject);
					}
				    if(is_array($supplier_order) && count($supplier_order)>0)
					{
						$order_total=0.00;
						if(isset($supplier_order["so_subtotal"]))
							$supplier_order["so_subtotal"]=number_format(round($supplier_order["so_subtotal"],2),2,".","");
						if(isset($supplier_order["so_tax_total"]))
							$supplier_order["so_tax_total"]=number_format(round($supplier_order["so_tax_total"],2),2,".","");
						if(isset($supplier_order["so_postage_cost"]))
							$supplier_order["so_postage_cost"]=number_format(round($supplier_order["so_postage_cost"],2),2,".","");
						if(isset($supplier_order["so_subtotal"]) && isset($supplier_order["so_tax_total"]) && isset($supplier_order["so_postage_cost"]))
							$order_total=$supplier_order["so_subtotal"]+$supplier_order["so_tax_total"]+$supplier_order["so_postage_cost"];
						$order_total=number_format(round($order_total,2),2,".","");
						if(!isset($supplier_order["so_total"]))
							$supplier_order["so_total"]=$order_total;
						if(isset($supplier_order['so_order_items']))
						{
							$so_order_items="";
							$sql="select * from ".$wpdb->prefix."honeybadger_so_emails_tpl where 1";
							$rows=$wpdb->get_results($sql);
							if(is_array($rows))
							{
								foreach($rows as $row)
					    		{
					    			if($row->title=='Order items Head')
					    				$so_order_items.=$row->content;
					    		}
								foreach($rows as $row)
					    		{
					    			if($row->title=='Order items')
					    			{
					    				$items=$supplier_order['so_order_items'];
					    				if(is_array($items))
					    				{
					    					for($i=0;$i<count($items);$i++)
					    					{
					    						$items[$i]['so_item_qty']=(int)$items[$i]['so_item_qty'];
					    						$items[$i]['so_item_price']=number_format(round((float)$items[$i]['so_item_price'],2),2,".","");
					    						$items[$i]['so_item_tax']=number_format(round((float)$items[$i]['so_item_tax'],2),2,".","");
					    						$item_total=($items[$i]['so_item_price']+$items[$i]['so_item_tax'])*$items[$i]['so_item_qty'];
					    						$item_total=number_format(round($item_total,2),2,".","");
					    						$items[$i]['so_item_total']=$item_total;
					    						foreach($items[$i] as $tag => $tag_value)
					    						{
					    							if($tag=="so_item_sku" && $items[$i]["so_item_sku"]!="")
					    								$items[$i]["so_item_sku"]=" [".$items[$i]["so_item_sku"]."]";
					    							if($tag=="so_supplier_description" && $items[$i]["so_supplier_description"]!="")
					    								$items[$i]["so_supplier_description"]=" (".$items[$i]["so_supplier_description"].")";
					    							if($tag=="so_item_product_id" && (int)$tag_value>0)
					    							{
					    								$items[$i]["so_item_name"]=get_the_title($tag_value);
					    								unset($items[$i]["so_item_product_id"]);
					    							}
					    						}
					    					}
					    				}
					    				if(is_array($items))
					    				{
					    					foreach($items as $item)
					    					{
					    						$item_row=$row->content;
					    						foreach($item as $tag => $tag_value)
					    						{
					    							if(isset($supplier_order[$tag]) && !is_array($supplier_order[$tag]))
					    								$tag_value=$supplier_order[$tag];
					    							$item_row=str_ireplace("{".$tag."}", $tag_value ,$item_row);
					    						}
					    						$so_order_items.=$item_row;
					    					}
					    				}
					    			}
					    		}
					    		foreach($rows as $row)
					    		{
					    			if($row->title=='Order items Footer')
					    				$so_order_items.=$row->content;
					    		}
					    		foreach($supplier_order as $tag => $tag_value)
					    		{
					    			if(!is_array($tag_value))
										$so_order_items=str_ireplace("{".$tag."}", $tag_value ,$so_order_items);
					    		}
								$supplier_order['so_order_items']=$so_order_items;
							}
						}
						foreach($supplier_order as $tag => $tag_value)
						{
							if($tag!="so_order_items")
								$subject=str_ireplace("{".$tag."}", $tag_value ,$subject);
						}
					}
					$content = $this->get_custom_email_html( $order, $subject, $mailer, $id, $request, array('supplier_order'=>$supplier_order) );
					$content=str_ireplace("<p></p>","",$content);
					$content=str_ireplace("<p>&nbsp;</p>","",$content);
					$content=str_ireplace("<br /><br />","<br />",$content);
					$content=str_ireplace("<br><br>","<br>",$content);
					$headers = "Content-Type: text/html\r\n";
					$tmp_attachments=array();
					if(count($all_attachments)>0)
					{
						foreach($all_attachments as $attachment)
						{
							$file_name=$this->removeMd5FromFilename(basename($attachment));
							$new_path=HONEYBADGER_UPLOADS_PATH."attachments/tmp/".$file_name;
							if($file_name!=$new_path && copy($attachment,$new_path))
								$tmp_attachments[]=$new_path;
							else
								$tmp_attachments[]=$attachment;
						}
						$headers = "Content-Type: multipart/mixed\r\n";
					}
					if($mailer->send( $send_to, $subject, $content, $headers,$tmp_attachments ))
					{
						if(count($remove_files)>0)
						{
							foreach($remove_files as $file)
								{if(is_file($file))unlink($file);}
						}
						if(count($tmp_attachments)>0)
						{
							foreach($tmp_attachments as $file)
								{if(is_file($file))unlink($file);}
						}
						return $this->returnOk();
					}
					else
					{
						if(count($remove_files)>0)
						{
							foreach($remove_files as $file)
								{if(is_file($file))unlink($file);}
						}
						if(count($tmp_attachments)>0)
						{
							foreach($tmp_attachments as $file)
								{if(is_file($file))unlink($file);}
						}
					}
				}
			}
		}
		return $this->returnError();
	}
	function save_static_attachment($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$title=isset($parameters['title'])?sanitize_text_field($parameters['title']):"";
			$attach_to_wc_emails_str=isset($parameters['attach_to_wc_emails'])?$parameters['attach_to_wc_emails']:array();
			$attach_to_emails_str=isset($parameters['attach_to_emails'])?$parameters['attach_to_emails']:array();
			$file_names_str=isset($parameters['file_names'])?$parameters['file_names']:"";
			$enabled=isset($parameters['enabled'])?sanitize_text_field($parameters['enabled']):"";
			$attachments=isset($_FILES['file'])?$_FILES['file']:array();
			$static_att_id=isset($parameters['static_att_id'])?(int)$parameters['static_att_id']:0;
			if($static_att_id>0)
			{
				if($enabled=="on")
					$enabled=1;
				else
					$enabled=0;
				$file_names=array();
				if($file_names_str!="" && !is_array($file_names_str))
					parse_str($file_names_str,$file_names);
				else
					$file_names=$file_names_str;
				$attach_to_wc_emails=array();
				if($attach_to_wc_emails_str!="" && !is_array($attach_to_wc_emails_str))
					parse_str($attach_to_wc_emails_str,$attach_to_wc_emails);
				else
					$attach_to_wc_emails=$attach_to_wc_emails_str;
				$attach_to_emails=array();
				if($attach_to_emails_str!="" && !is_array($attach_to_emails_str))
					parse_str($attach_to_emails_str,$attach_to_emails);
				else
					$attach_to_emails=$attach_to_emails_str;
				$attach_to_wc_emails_str="";
				$attach_to_emails_str="";
				if(is_array($attach_to_wc_emails) && count($attach_to_wc_emails)>0)
					$attach_to_wc_emails_str=implode(",",$attach_to_wc_emails);
				if(is_array($attach_to_emails) && count($attach_to_emails)>0)
					$attach_to_emails_str=implode(",",$attach_to_emails);

				$attach_to_wc_emails_str=sanitize_text_field($attach_to_wc_emails_str);
				$attach_to_emails_str=sanitize_text_field($attach_to_emails_str);

				$new_file_names=array();
				if(is_array($file_names) && !is_array($file_names_str))
				{
					foreach($file_names as $index => $val)
					{
						$index=str_ireplace("_",".",$index);
						$new_file_names[sanitize_text_field($index)]=sanitize_text_field($val);
					}
					$file_names=$new_file_names;
				}
				if(isset($attachments['name']) && is_array($attachments['name']))
				{
					$sql="select * from ".$wpdb->prefix."honeybadger_static_attachments where id='".esc_sql($static_att_id)."'";
					$result=$wpdb->get_row($sql);
					if(isset($result->id))
					{
						if($result->path!="" && is_file(ABSPATH.$result->path))
							unlink(ABSPATH.$result->path);
					}
					if (!function_exists('wp_handle_upload'))
				        require_once(ABSPATH . 'wp-admin/includes/file.php');
					foreach($attachments['name'] as $index => $file_name)
					{
						$the_folder="static";
						$file_name=$file_names[$attachments['name'][$index]];
						$md5=md5("-938uqtuiagksrbs".$the_folder."_".$file_name.microtime().rand(1,9999));
						$target_file=HONEYBADGER_UPLOADS_PATH."attachments/".$the_folder."/".$md5."_".$file_name;
						$this->unlinkAttachmentFileDuplicate($target_file);

				        $uploadedfile = array(
				            'name'     => $attachments['name'][$index],
				            'type'     => $attachments['type'][$index],
				            'tmp_name' => $attachments['tmp_name'][$index],
				            'error'    => $attachments['error'][$index],
				            'size'     => $attachments['size'][$index]
				        );
				        $upload_overrides = array( 'test_form' => false );
						$movefile=wp_handle_upload($uploadedfile, $upload_overrides);
						if ( $movefile && !isset( $movefile['error'] ) && isset($movefile['file']) && file_exists($movefile['file']))
						{
							copy($movefile['file'],$target_file);
							unlink($movefile['file']);
							$target_path=str_ireplace(ABSPATH,"",$target_file);
							$sql="update ".$wpdb->prefix."honeybadger_static_attachments set
							title='".esc_sql($title)."',
							path='".esc_sql($target_path)."',
							wc_emails='".esc_sql($attach_to_wc_emails_str)."',
							emails='".esc_sql($attach_to_emails_str)."',
							enabled='".esc_sql($enabled)."',
							mdate='".time()."'
							where id='".esc_sql($static_att_id)."'";
							if(!$wpdb->query($sql) && $wpdb->last_error !== '')
								return $this->returnError();
						}
						else
							return $this->returnError();
					}
					return $this->returnOk();
				}
				else
				{
					$sql="update ".$wpdb->prefix."honeybadger_static_attachments set
					title='".esc_sql($title)."',
					wc_emails='".esc_sql($attach_to_wc_emails_str)."',
					emails='".esc_sql($attach_to_emails_str)."',
					enabled='".esc_sql($enabled)."',
					mdate='".time()."' where id='".esc_sql($static_att_id)."'";
					if(!$wpdb->query($sql) && $wpdb->last_error !== '')
						return $this->returnError();
					return $this->returnOk();
				}
			}
		}
		return $this->returnError();
	}
	function save_new_static_attachment($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$title=isset($parameters['title'])?sanitize_text_field($parameters['title']):"";
			$attach_to_wc_emails_str=isset($parameters['attach_to_wc_emails'])?sanitize_text_field($parameters['attach_to_wc_emails']):"";
			$attach_to_emails_str=isset($parameters['attach_to_emails'])?sanitize_text_field($parameters['attach_to_emails']):"";
			$file_names_str=isset($parameters['file_names'])?sanitize_text_field($parameters['file_names']):"";
			$enabled=isset($parameters['enabled'])?sanitize_text_field($parameters['enabled']):"";
			$attachments=isset($_FILES['file'])?$_FILES['file']:array();
			if($enabled=="on")
				$enabled=1;
			else
				$enabled=0;
			$file_names=array();
			if($file_names_str!="")
				parse_str($file_names_str,$file_names);
			$attach_to_wc_emails=array();
			if($attach_to_wc_emails_str!="")
				parse_str($attach_to_wc_emails_str,$attach_to_wc_emails);
			$attach_to_emails=array();
			if($attach_to_emails_str!="")
				parse_str($attach_to_emails_str,$attach_to_emails);
			$attach_to_wc_emails_str=implode(",",$attach_to_wc_emails);
			$attach_to_emails_str=implode(",",$attach_to_emails);

			$attach_to_wc_emails_str=sanitize_text_field($attach_to_wc_emails_str);
			$attach_to_emails_str=sanitize_text_field($attach_to_emails_str);

			$new_file_names=array();
			if(is_array($file_names))
			{
				foreach($file_names as $index => $val)
				{
					$index=str_ireplace("_",".",$index);
					$new_file_names[sanitize_text_field($index)]=sanitize_text_field($val);
				}
				$file_names=$new_file_names;
			}
			if(isset($attachments['name']) && is_array($attachments['name']))
			{
				if (!function_exists('wp_handle_upload'))
			        require_once(ABSPATH . 'wp-admin/includes/file.php');
				foreach($attachments['name'] as $index => $file_name)
				{
					$the_folder="static";
					$file_name=$file_names[$attachments['name'][$index]];
					$md5=md5("-938uqtuiagksrbs".$the_folder."_".$file_name.microtime().rand(1,9999));
					$target_file=HONEYBADGER_UPLOADS_PATH."attachments/".$the_folder."/".$md5."_".$file_name;
					$this->unlinkAttachmentFileDuplicate($target_file);
			        $uploadedfile = array(
			            'name'     => $attachments['name'][$index],
			            'type'     => $attachments['type'][$index],
			            'tmp_name' => $attachments['tmp_name'][$index],
			            'error'    => $attachments['error'][$index],
			            'size'     => $attachments['size'][$index]
			        );
			        $upload_overrides = array( 'test_form' => false );
					$movefile=wp_handle_upload($uploadedfile, $upload_overrides);
					if ( $movefile && !isset( $movefile['error'] ) && isset($movefile['file']) && file_exists($movefile['file']))
					{
						copy($movefile['file'],$target_file);
						unlink($movefile['file']);
						$target_path=str_ireplace(ABSPATH,"",$target_file);
						$sql="insert into ".$wpdb->prefix."honeybadger_static_attachments set
						title='".esc_sql($title)."',
						path='".esc_sql($target_path)."',
						wc_emails='".esc_sql($attach_to_wc_emails_str)."',
						emails='".esc_sql($attach_to_emails_str)."',
						enabled='".esc_sql($enabled)."',
						mdate='".time()."'";
						if(!$wpdb->query($sql) && $wpdb->last_error !== '')
							return $this->returnError();
					}
					else
						return $this->returnError();
				}
				return $this->returnOk();
			}
		}
		return $this->returnError();
	}
	function save_attachments()
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		$oid=isset($_POST['order_id'])?(int)$_POST['order_id']:0;
		$attachment_ids_str=isset($_POST['attachment_ids'])?sanitize_text_field($_POST['attachment_ids']):"";
		$attachments=isset($_FILES['file'])?$_FILES['file']:array();
		$attachment_ids=array();
		if($attachment_ids_str!="")
			parse_str($attachment_ids_str,$attachment_ids);
		$all_attachments=array();
		if(is_array($attachments))
		{
			$sql="select id, title, keep_files from ".$wpdb->prefix."honeybadger_attachments where id in ('".implode("','",array_map('esc_sql',$attachment_ids))."')";
			$attachment_folders=$wpdb->get_results($sql);
			if(isset($attachments['name']) && is_array($attachments['name']))
			{
				if (!function_exists('wp_handle_upload'))
			        require_once(ABSPATH . 'wp-admin/includes/file.php');
				foreach($attachments['name'] as $index => $file_name)
				{
					$the_folder="tmp";
					$the_file_name="";
					if(isset($attachment_ids[$index]))
					{
						foreach($attachment_folders as $folder)
						{
							if($folder->id==$attachment_ids[$index])
							{
								$the_folder=$this->getFolderName($folder->title);
								$the_file_name=$the_folder;
								if($folder->keep_files==0)
									$the_folder="tmp";
								break;
							}
						}
					}
					$file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
					if($the_folder=="tmp")
					{
						if($the_file_name!="")
						{
							$the_folder=$the_file_name;
							$file_ext = pathinfo($attachments['name'][$index], PATHINFO_EXTENSION);
							$the_folder = $the_folder."_".$oid.".".$file_ext;
						}
						else
							$the_folder=$attachments['name'][$index];
						$target_file=HONEYBADGER_UPLOADS_PATH."attachments/tmp/".$the_folder;
						$remove_files[]=$target_file;
					}
					else
					{
						$md5=md5("-938uqtuiagksrbs".$the_folder."_".$oid.".".$file_ext.microtime().rand(1,9999));
						$target_file=HONEYBADGER_UPLOADS_PATH."attachments/".$the_folder."/".$md5."_".$the_folder."_".$oid.".".$file_ext;
					}
					$this->unlinkAttachmentFileDuplicate($target_file);
					$uploadedfile = array(
			            'name'     => $attachments['name'][$index],
			            'type'     => $attachments['type'][$index],
			            'tmp_name' => $attachments['tmp_name'][$index],
			            'error'    => $attachments['error'][$index],
			            'size'     => $attachments['size'][$index]
			        );
			        $upload_overrides = array( 'test_form' => false );
					$movefile=wp_handle_upload($uploadedfile, $upload_overrides);
					if ( $movefile && !isset( $movefile['error'] ) && isset($movefile['file']) && file_exists($movefile['file']))
					{
						copy($movefile['file'],$target_file);
						unlink($movefile['file']);
						$all_attachments[]=$target_file;
					}
				}
			}
		}
		$tmp_attachments=array();
		if(count($all_attachments)>0)
		{
			foreach($all_attachments as $attachment)
			{
				$file_name=$this->removeMd5FromFilename(basename($attachment));
				$new_path=HONEYBADGER_UPLOADS_PATH."attachments/tmp/".$file_name;
				if($file_name!=$new_path && copy($attachment,$new_path))
					$tmp_attachments[]=$new_path;
				else
					$tmp_attachments[]=$attachment;
			}
		}
		if(count($tmp_attachments)>0)
		{
			if(isset($_POST['attachments_to_be_deleted']) && is_array($_POST['attachments_to_be_deleted']))
				$_POST['attachments_to_be_deleted']=array_merge($_POST['attachments_to_be_deleted'],$tmp_attachments);
			else
				$_POST['attachments_to_be_deleted']=$tmp_attachments;
		}
		return $tmp_attachments;
	}
	function send_custom_email($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$order_id=isset($parameters['order_id'])?(int)$parameters['order_id']:0;
			$subject=isset($parameters['subject'])?sanitize_text_field($parameters['subject']):"";
			$content=isset($parameters['content'])?wp_kses_post($parameters['content']):"";
			$heading=isset($parameters['heading'])?wp_kses_post($parameters['heading']):"";
			$email_bcc=isset($parameters['email_bcc'])?sanitize_email($parameters['email_bcc']):"";
			$other_attachment_names_str=isset($parameters['other_attachment_names'])?$parameters['other_attachment_names']:array();
			$from_name=isset($parameters['from_name'])?sanitize_text_field($parameters['from_name']):"";
			$reply_to=isset($parameters['reply_to'])?sanitize_email($parameters['reply_to']):"";
			$send_to=isset($parameters['send_to'])?sanitize_email($parameters['send_to']):"";
			$attachment_ids_str=isset($parameters['attachment_ids'])?sanitize_text_field($parameters['attachment_ids']):"";
			$attachments=isset($_FILES['file'])?$_FILES['file']:array();
			$static_attachments_str=isset($_POST['static_attachments'])?sanitize_text_field($_POST['static_attachments']):"";
			$static_attachments=array();
			if($static_attachments_str!="")
				$static_attachments=explode(",",$static_attachments_str);
			$attachment_ids=array();
			if($attachment_ids_str!="")
				parse_str($attachment_ids_str,$attachment_ids);
			$other_attachment_names=array();
			if($other_attachment_names_str!="")
				parse_str($other_attachment_names_str,$other_attachment_names);
			if(is_array($other_attachment_names))
			{
				foreach($other_attachment_names as $index => $val)
					$other_attachments_names[str_ireplace("_",".",$index)]=$val;
			}
			if($order_id>0)
			{
				$all_attachments=array();
				$remove_files=array();
				if(is_array($attachments))
				{
					$sql="select id, title, keep_files from ".$wpdb->prefix."honeybadger_attachments where id in ('".implode("','",array_map('esc_sql',$attachment_ids))."')";
					$attachment_folders=$wpdb->get_results($sql);
					if(isset($attachments['name']) && is_array($attachments['name']))
					{
						if (!function_exists('wp_handle_upload'))
				        	require_once(ABSPATH . 'wp-admin/includes/file.php');
						foreach($attachments['name'] as $index => $file_name)
						{
							$the_folder="tmp";
							$the_file_name="";
							if(isset($attachment_ids[$index]))
							{
								foreach($attachment_folders as $folder)
								{
									if($folder->id==$attachment_ids[$index])
									{
										$the_folder=$this->getFolderName($folder->title);
										$the_file_name=$the_folder;
										if($folder->keep_files==0)
											$the_folder="tmp";
										break;
									}
								}
							}
							$file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
							if($the_folder=="tmp")
							{
								if($the_file_name!="")
								{
									$the_folder=$the_file_name;
									$file_ext = pathinfo($attachments['name'][$index], PATHINFO_EXTENSION);
									$the_folder = $the_folder."_".$order_id.".".$file_ext;
								}
								else
								{
									if(isset($other_attachments_names[$attachments['name'][$index]]))
										$the_folder=$other_attachments_names[$attachments['name'][$index]];
								}
								$target_file=HONEYBADGER_UPLOADS_PATH."attachments/tmp/".$the_folder;
								$remove_files[]=$target_file;
							}
							else
							{
								$md5=md5("-938uqtuiagksrbs".$the_folder."_".$order_id.".".$file_ext.microtime().rand(1,9999));
								$target_file=HONEYBADGER_UPLOADS_PATH."attachments/".$the_folder."/".$md5."_".$the_folder."_".$order_id.".".$file_ext;
							}
							$this->unlinkAttachmentFileDuplicate($target_file);
							$uploadedfile = array(
					            'name'     => $attachments['name'][$index],
					            'type'     => $attachments['type'][$index],
					            'tmp_name' => $attachments['tmp_name'][$index],
					            'error'    => $attachments['error'][$index],
					            'size'     => $attachments['size'][$index]
					        );
					        $upload_overrides = array( 'test_form' => false );
							$movefile=wp_handle_upload($uploadedfile, $upload_overrides);
							if ( $movefile && !isset( $movefile['error'] ) && isset($movefile['file']) && file_exists($movefile['file']))
							{
								copy($movefile['file'],$target_file);
								unlink($movefile['file']);
								$all_attachments[]=$target_file;
							}
						}
					}
				}
				if(is_array($static_attachments) && count($static_attachments)>0)
					$all_attachments=array_merge($all_attachments,$this->get_static_attachments());
				$mailer = WC()->mailer();
				$order = wc_get_order( $order_id );
				$subject=str_ireplace("{site_title}", get_bloginfo( 'name', 'display' ) ,$subject);
			    $subject=str_ireplace("{customer}", $order->get_billing_first_name() ,$subject);
			    $subject=str_ireplace("{customer_full_name}", $order->get_formatted_billing_full_name() ,$subject);
			    $subject=str_ireplace("{order_number}", $order->get_id() ,$subject);
			    $subject=str_ireplace("{site_url}", get_bloginfo( 'url', 'display' ) ,$subject);
			    $subject=str_ireplace("{order_date}", wc_format_datetime( $order->get_date_created() ) ,$subject);
				$content = $this->get_send_email_html( $order, $heading, $mailer, $content, $request );
				$headers = "Content-Type: text/html\r\n";
				$tmp_attachments=array();
				if(count($all_attachments)>0)
				{
					foreach($all_attachments as $attachment)
					{
						$file_name=$this->removeMd5FromFilename(basename($attachment));
						$new_path=HONEYBADGER_UPLOADS_PATH."attachments/tmp/".$file_name;
						if($file_name!=$new_path && copy($attachment,$new_path))
							$tmp_attachments[]=$new_path;
						else
							$tmp_attachments[]=$attachment;
					}
					$headers = "Content-Type: multipart/mixed\r\n";
				}
				if($email_bcc!="")
					$headers .= "Bcc: ".trim($email_bcc)."\r\n";
				if($reply_to!="")
					$headers .= "Reply-To: ".$from_name." <".trim($reply_to).">\r\n";
				if($mailer->send( $send_to, $subject, $content, $headers, $tmp_attachments ))
				{
					if(count($remove_files)>0)
					{
						foreach($remove_files as $file)
							{if(is_file($file))unlink($file);}
					}
					if(count($tmp_attachments)>0)
					{
						foreach($tmp_attachments as $file)
							{if(is_file($file))unlink($file);}
					}
					return $this->returnOk($subject);
				}
				else
				{
					if(count($remove_files)>0)
					{
						foreach($remove_files as $file)
							{if(is_file($file))unlink($file);}
					}
					if(count($tmp_attachments)>0)
					{
						foreach($tmp_attachments as $file)
							{if(is_file($file))unlink($file);}
					}
				}
			}
		}
		return $this->returnError();
	}
	function send_email_template_action($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$id=isset($parameters['id'])?(int)$parameters['id']:0;
			$oid=isset($parameters['oid'])?(int)$parameters['oid']:0;
			$is_supplier_order=isset($parameters['is_supplier_order'])?(int)$parameters['is_supplier_order']:0;
			$supplier_order_id=isset($parameters['supplier_order_id'])?(int)$parameters['supplier_order_id']:0;
			$supplier_order_str=isset($parameters['supplier_order'])?sanitize_text_field($parameters['supplier_order']):"";
			$attachment_ids_str=isset($parameters['attachment_ids'])?sanitize_text_field($parameters['attachment_ids']):"";
			$attachments=isset($_FILES['file'])?$_FILES['file']:array();
			$static_attachments_str=isset($_POST['static_attachments'])?sanitize_text_field($_POST['static_attachments']):"";
			$static_attachments=array();
			if($static_attachments_str!="")
				$static_attachments=explode(",",$static_attachments_str);
			$attachment_ids=array();
			if($attachment_ids_str!="")
				parse_str($attachment_ids_str,$attachment_ids);
			$supplier_order=array();
			if($supplier_order_str!="")
				parse_str($supplier_order_str,$supplier_order);
			$orig_oid=$oid;
			if($oid==0 && $is_supplier_order==0)
			{
				$sql="select ID from ".$wpdb->prefix."posts WHERE post_type='shop_order' and post_status='wc-completed' order by post_date desc limit 1";
				$result=$wpdb->get_row($sql);
				if(isset($result->ID))
					$oid=$result->ID;
			}
			if($id>0 && ($oid>0 || $supplier_order_id>0))
			{
				$all_attachments=array();
				$remove_files=array();
				$sql="select * from ".$wpdb->prefix."honeybadger_emails where id='".esc_sql($id)."' and enabled=1";
				$email=$wpdb->get_row($sql);
				if(isset($email->id))
				{
					if(is_array($attachments))
					{
						$sql="select id, title, keep_files from ".$wpdb->prefix."honeybadger_attachments where id in ('".implode("','",array_map('esc_sql',$attachment_ids))."')";
						$attachment_folders=$wpdb->get_results($sql);
						if(isset($attachments['name']) && is_array($attachments['name']))
						{
							if (!function_exists('wp_handle_upload'))
				        		require_once(ABSPATH . 'wp-admin/includes/file.php');
							foreach($attachments['name'] as $index => $file_name)
							{
								$the_folder="tmp";
								$the_file_name="";
								if(isset($attachment_ids[$index]))
								{
									foreach($attachment_folders as $folder)
									{
										if($folder->id==$attachment_ids[$index])
										{
											$the_folder=$this->getFolderName($folder->title);
											$the_file_name=$the_folder;
											if($folder->keep_files==0)
												$the_folder="tmp";
											break;
										}
									}
								}
								$file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
								if($the_folder=="tmp")
								{
									if($the_file_name!="")
									{
										$the_folder=$the_file_name;
										$file_ext = pathinfo($attachments['name'][$index], PATHINFO_EXTENSION);
										if($supplier_order_id>0)
											$the_folder = $the_folder."_".$supplier_order_id.".".$file_ext;
										else
											$the_folder = $the_folder."_".$oid.".".$file_ext;
									}
									else
										$the_folder=$attachments['name'][$index];
									$target_file=HONEYBADGER_UPLOADS_PATH."attachments/tmp/".$the_folder;
									$remove_files[]=$target_file;
								}
								else
								{
									if($supplier_order_id>0)
										$md5=md5("-938uqtuiagksrbs".$the_folder."_".$supplier_order_id.".".$file_ext.microtime().rand(1,9999));
									else	
										$md5=md5("-938uqtuiagksrbs".$the_folder."_".$oid.".".$file_ext.microtime().rand(1,9999));
									if($supplier_order_id>0)
										$target_file=HONEYBADGER_UPLOADS_PATH."attachments/".$the_folder."/".$md5."_".$the_folder."_".$supplier_order_id.".".$file_ext;
									else
										$target_file=HONEYBADGER_UPLOADS_PATH."attachments/".$the_folder."/".$md5."_".$the_folder."_".$oid.".".$file_ext;
								}
								$this->unlinkAttachmentFileDuplicate($target_file);
								$uploadedfile = array(
						            'name'     => $attachments['name'][$index],
						            'type'     => $attachments['type'][$index],
						            'tmp_name' => $attachments['tmp_name'][$index],
						            'error'    => $attachments['error'][$index],
						            'size'     => $attachments['size'][$index]
						        );
						        $upload_overrides = array( 'test_form' => false );
								$movefile=wp_handle_upload($uploadedfile, $upload_overrides);
								if ( $movefile && !isset( $movefile['error'] ) && isset($movefile['file']) && file_exists($movefile['file']))
								{
									copy($movefile['file'],$target_file);
									unlink($movefile['file']);
									$all_attachments[]=$target_file;
								}
							}
						}
					}
					if(is_array($static_attachments) && count($static_attachments)>0)
						$all_attachments=array_merge($all_attachments,$this->get_static_attachments());
					$mailer = WC()->mailer();
					$order = wc_get_order( $oid );
					$subject=$email->subject;
					if($order)
					{
						$subject=str_ireplace("{site_title}", get_bloginfo( 'name', 'display' ) ,$subject);
					    $subject=str_ireplace("{customer}", $order->get_billing_first_name() ,$subject);
					    $subject=str_ireplace("{customer_full_name}", $order->get_formatted_billing_full_name() ,$subject);
					    $subject=str_ireplace("{order_number}", $order->get_id() ,$subject);
					    $subject=str_ireplace("{site_url}", get_bloginfo( 'url', 'display' ) ,$subject);
					    $subject=str_ireplace("{order_date}", wc_format_datetime( $order->get_date_created() ) ,$subject);
					}
				    if(is_array($supplier_order) && count($supplier_order)>0)
					{
						if(isset($supplier_order["so_subtotal"]))
							$supplier_order["so_subtotal"]=number_format(round($supplier_order["so_subtotal"],2),2,".","");
						if(isset($supplier_order["so_tax_total"]))
							$supplier_order["so_tax_total"]=number_format(round($supplier_order["so_tax_total"],2),2,".","");
						if(isset($supplier_order["so_postage_cost"]))
							$supplier_order["so_postage_cost"]=number_format(round($supplier_order["so_postage_cost"],2),2,".","");
						$order_total=$supplier_order["so_subtotal"]+$supplier_order["so_tax_total"]+$supplier_order["so_postage_cost"];
						$order_total=number_format(round($order_total,2),2,".","");
						if(!isset($supplier_order["so_total"]))
							$supplier_order["so_total"]=$order_total;
						if(isset($supplier_order['so_order_items']))
						{
							$so_order_items="";
							$sql="select * from ".$wpdb->prefix."honeybadger_so_emails_tpl where 1";
							$rows=$wpdb->get_results($sql);
							if(is_array($rows))
							{
								foreach($rows as $row)
					    		{
					    			if($row->title=='Order items Head')
					    				$so_order_items.=$row->content;
					    		}
								foreach($rows as $row)
					    		{
					    			if($row->title=='Order items')
					    			{
					    				$items=$supplier_order['so_order_items'];
					    				if(is_array($items))
					    				{
					    					for($i=0;$i<count($items);$i++)
					    					{
					    						$items[$i]['so_item_qty']=(int)$items[$i]['so_item_qty'];
					    						$items[$i]['so_item_price']=number_format(round((float)$items[$i]['so_item_price'],2),2,".","");
					    						$items[$i]['so_item_tax']=number_format(round((float)$items[$i]['so_item_tax'],2),2,".","");
					    						$item_total=($items[$i]['so_item_price']+$items[$i]['so_item_tax'])*$items[$i]['so_item_qty'];
					    						$item_total=number_format(round($item_total,2),2,".","");
					    						$items[$i]['so_item_total']=$item_total;
					    						foreach($items[$i] as $tag => $tag_value)
					    						{
					    							if($tag=="so_item_sku" && $items[$i]["so_item_sku"]!="")
					    								$items[$i]["so_item_sku"]=" [".$items[$i]["so_item_sku"]."]";
					    							if($tag=="so_supplier_description" && $items[$i]["so_supplier_description"]!="")
					    								$items[$i]["so_supplier_description"]=" (".$items[$i]["so_supplier_description"].")";
					    							if($tag=="so_item_product_id" && (int)$tag_value>0)
					    							{
					    								$items[$i]["so_item_name"]=get_the_title($tag_value);
					    								unset($items[$i]["so_item_product_id"]);
					    							}
					    						}
					    					}
					    				}
					    				if(is_array($items))
					    				{
					    					foreach($items as $item)
					    					{
					    						$item_row=$row->content;
					    						foreach($item as $tag => $tag_value)
					    						{
					    							if(isset($supplier_order[$tag]) && !is_array($supplier_order[$tag]))
					    								$tag_value=$supplier_order[$tag];
					    							$item_row=str_ireplace("{".$tag."}", $tag_value ,$item_row);
					    						}
					    						$so_order_items.=$item_row;
					    					}
					    				}
					    			}
					    		}
					    		foreach($rows as $row)
					    		{
					    			if($row->title=='Order items Footer')
					    				$so_order_items.=$row->content;
					    		}
					    		foreach($supplier_order as $tag => $tag_value)
					    		{
					    			if(!is_array($tag_value))
										$so_order_items=str_ireplace("{".$tag."}", $tag_value ,$so_order_items);
					    		}
								$supplier_order['so_order_items']=$so_order_items;
							}
						}
						
						foreach($supplier_order as $tag => $tag_value)
							$subject=str_ireplace("{".$tag."}", $tag_value ,$subject);
					}
					$content = $this->get_custom_email_html( $order, $subject, $mailer, $id, $request, array('supplier_order'=>$supplier_order) );
					$headers = "Content-Type: text/html\r\n";
					$tmp_attachments=array();
					if(count($all_attachments)>0)
					{
						foreach($all_attachments as $attachment)
						{
							$file_name=$this->removeMd5FromFilename(basename($attachment));
							$new_path=HONEYBADGER_UPLOADS_PATH."attachments/tmp/".$file_name;
							if($file_name!=$new_path && copy($attachment,$new_path))
								$tmp_attachments[]=$new_path;
							else
								$tmp_attachments[]=$attachment;
						}
						$headers = "Content-Type: multipart/mixed\r\n";
					}
					if($email->email_bcc!="")
						$headers .= "Bcc: ".trim($email->email_bcc)."\r\n";
					if($orig_oid>0)
						$send_to=$order->get_billing_email();
					else
						$send_to=$supplier_order['supplier_email'];
					if($mailer->send( $send_to, $subject, $content, $headers,$tmp_attachments ))
					{
						if(count($remove_files)>0)
						{
							foreach($remove_files as $file)
								{if(is_file($file))unlink($file);}
						}
						if(count($tmp_attachments)>0)
						{
							foreach($tmp_attachments as $file)
								{if(is_file($file))unlink($file);}
						}
						return $this->returnOk();
					}
					else
					{
						if(count($remove_files)>0)
						{
							foreach($remove_files as $file)
								{if(is_file($file))unlink($file);}
						}
						if(count($tmp_attachments)>0)
						{
							foreach($tmp_attachments as $file)
								{if(is_file($file))unlink($file);}
						}
					}
				}
			}
		}
		return $this->returnError();
	}
	function isValidmd5($md5="")
	{
	    return preg_match('/^[a-f0-9]{32}$/', $md5);
	}
	function removeMd5FromFilename($file="")
	{
		if($file!="")
		{
			$tmp=explode("_",$file);
			$new_tmp=array();
			if(is_array($tmp) && count($tmp)>1 && $this->isValidmd5($tmp[0]))
			{
				for($i=1;$i<count($tmp);$i++)
					$new_tmp[]=$tmp[$i];
			}
			if(count($new_tmp)>0)
				return implode("_",$new_tmp);
		}
		return $file;
	}
	function get_emails($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$sql="select * from ".$wpdb->prefix."honeybadger_emails where 1 order by id limit 20";
			$results=$wpdb->get_results($sql);
			$email_ids=array();
			if(is_array($results))
			{
				foreach($results as $r)
					$email_ids[]=$r->id;
			}
			$sql="select * from ".$wpdb->prefix."honeybadger_so_emails_tpl where 1 order by id";
			$results1=$wpdb->get_results($sql);
			$sql="select s.*, '' as filesize from ".$wpdb->prefix."honeybadger_static_attachments s where emails in ('".implode("','",array_map('esc_sql',$email_ids))."') and enabled=1 order by title";
			$results2=$wpdb->get_results($sql);
			if(is_array($results2))
			{
				foreach($results2 as $r2)
				{
					if(is_file(ABSPATH.$r2->path))
						$r2->filesize=filesize(ABSPATH.$r2->path);
				}
			}
			$return=new stdClass;
			$return->id=0;
			$return->content=$results;
			$return->statuses=$this->get_order_statuses($request);
			$return->so_templates=$results1;
			$return->email_attachments=$this->get_email_attachments();
			$return->static_attachments=$results2;
			return array($return);
		}
	}
	function save_new_email_template($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$title=isset($parameters['title'])?sanitize_text_field($parameters['title']):"";
			$subject=isset($parameters['subject'])?sanitize_text_field($parameters['subject']):"";
			$heading=isset($parameters['heading'])?wp_kses_post($parameters['heading']):"";
			$content=isset($parameters['content'])?wp_kses_post($parameters['content']):"";
			$email_bcc=isset($parameters['email_bcc'])?sanitize_email($parameters['email_bcc']):"";
			$enabled=isset($parameters['enabled'])?sanitize_text_field($parameters['enabled']):"";
			$associate=isset($parameters['associate'])?$parameters['associate']:array();
			$so_associate=isset($parameters['so_associate'])?$parameters['so_associate']:array();
			if($enabled=="on")
				$enabled=1;
			else
				$enabled=0;
			if($title=='' || $subject=='' || $heading=='' || $content=='')
				return $this->returnError();
			$statuses='';
			if($associate!="")
				$statuses=implode(",",$associate);
			$so_states="";
			if($so_associate!="")
				$so_states=implode(",",$so_associate);
			$sql="insert into ".$wpdb->prefix."honeybadger_emails set
			title='".esc_sql($title)."',
			subject='".esc_sql($subject)."',
			heading='".esc_sql($heading)."',
			content='".esc_sql($content)."',
			email_bcc='".esc_sql($email_bcc)."',
			enabled='".esc_sql($enabled)."',
			statuses='".esc_sql($statuses)."',
			so_states='".esc_sql($so_states)."',
			mdate='".time()."'";
			if(!$wpdb->query($sql) && $wpdb->last_error !== '')
				return $this->returnError();
			return $this->returnOk();
		}
	}
	function save_email_template($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$id=isset($parameters['id'])?(int)$parameters['id']:0;
			$title=isset($parameters['title'])?sanitize_text_field($parameters['title']):"";
			$subject=isset($parameters['subject'])?sanitize_text_field($parameters['subject']):"";
			$heading=isset($parameters['heading'])?wp_kses_post($parameters['heading']):"";
			$content=isset($parameters['content'])?wp_kses_post($parameters['content']):"";
			$email_bcc=isset($parameters['email_bcc'])?sanitize_email($parameters['email_bcc']):"";
			$enabled=isset($parameters['enabled'])?sanitize_text_field($parameters['enabled']):"";
			$associate=isset($parameters['associate'])?$parameters['associate']:array();
			$so_associate=isset($parameters['so_associate'])?$parameters['so_associate']:array();
			if($enabled=="on")
				$enabled=1;
			else
				$enabled=0;
			if(!$id>0 || $title=='' || $subject=='' || $heading=='' || $content=='')
				return $this->returnError();
			$statuses='';
			if($associate!="")
				$statuses=implode(",",$associate);
			$so_states="";
			if($so_associate!="")
				$so_states=implode(",",$so_associate);
			$sql="update ".$wpdb->prefix."honeybadger_emails set
			title='".esc_sql($title)."',
			subject='".esc_sql($subject)."',
			heading='".esc_sql($heading)."',
			content='".esc_sql($content)."',
			email_bcc='".esc_sql($email_bcc)."',
			statuses='".esc_sql($statuses)."',
			so_states='".esc_sql($so_states)."',
			enabled='".esc_sql($enabled)."',
			mdate='".time()."'
			where id='".esc_sql($id)."'";

			if(!$wpdb->query($sql) && $wpdb->last_error !== '')
				return $this->returnError();
			return $this->returnOk();
		}
	}
	function delete_email_template($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$id=isset($parameters['id'])?(int)$parameters['id']:0;
			if($id>0)
			{
				$sql="delete from ".$wpdb->prefix."honeybadger_emails where id='".esc_sql($id)."'";
				if(!$wpdb->query($sql) && $wpdb->last_error !== '')
					return $this->returnError();
				return $this->returnOk();
			}
		}
	}
	function load_default_subtemplate($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$id=isset($parameters['id'])?(int)$parameters['id']:0;
			if($id>0)
			{
				require_once __DIR__ . '/class-honeybadger-it-activator.php';
				$activator=new HoneyBadgerIT\Honeybadger_IT_Activator;
				if(is_array($activator->sql2) && isset($activator->sql2[$id-1]))
				{
					$sql=$activator->sql2[$id-1];
					if(!$wpdb->query($sql) && $wpdb->last_error !== '')
						return $this->returnError();
				}
				return $this->returnOk();
			}
		}
	}
	function load_default_so_subtemplate($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$id=isset($parameters['id'])?(int)$parameters['id']+3:0;
			$type=isset($parameters['type'])?sanitize_text_field($parameters['type']):"";
			if($id>0)
			{
				require_once __DIR__ . '/class-honeybadger-it-activator.php';
				$activator=new HoneyBadgerIT\Honeybadger_IT_Activator;
				if($type=="extended")
					$id=$id+3;
				if(is_array($activator->sql2) && isset($activator->sql2[$id-1]))
				{
					$sql=$activator->sql2[$id-1];
					if(!$wpdb->query($sql) && $wpdb->last_error !== '')
						return $this->returnError();
				}
				return $this->returnOk();
			}
		}
	}
	function load_default_att_so_subtemplate($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$id=isset($parameters['id'])?(int)$parameters['id']+9:0;
			$type=isset($parameters['type'])?sanitize_text_field($parameters['type']):"";
			if($id>0)
			{
				require_once __DIR__ . '/class-honeybadger-it-activator.php';
				$activator=new HoneyBadgerIT\Honeybadger_IT_Activator;
				if(is_array($activator->sql2) && isset($activator->sql2[$id-1]))
				{
					$sql=$activator->sql2[$id-1];
					if(!$wpdb->query($sql) && $wpdb->last_error !== '')
						return $this->returnError();
				}
				return $this->returnOk();
			}
		}
	}
	function get_emails_simple($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$sql="select id, title from ".$wpdb->prefix."honeybadger_emails where 1 order by id limit 20";
			return $wpdb->get_results($sql);
		}
	}
	function GetDirectorySize($path)
	{
	    $bytestotal = 0;
	    $path = realpath($path);
	    if($path!==false && $path!='' && file_exists($path)){
	        foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)) as $object)
	        {
	        	try
	        	{
	            	$bytestotal += $object->getSize();
	            }
	            catch (Exception $e){}
	        }
	    }
	    return $bytestotal;
	}
	function get_attachments($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$sizes=array();
			$sql="select * from ".$wpdb->prefix."honeybadger_attachments where 1 order by id limit 20";
			$results=$wpdb->get_results($sql);
			if(is_array($results))
			{
				foreach($results as $result)
				{
					$attachment_path=HONEYBADGER_UPLOADS_PATH."attachments/".$this->getFolderName($result->title);
					$size=new stdClass;
					$size->id=$result->id;
					$size->size=$this->GetDirectorySize($attachment_path);
					$sizes[]=$size;
				}
			}
			$sql="select * from ".$wpdb->prefix."honeybadger_attachments_tpl where 1 order by id";
			$results1=$wpdb->get_results($sql);
			$sql="select s.*, '0' as filesize from ".$wpdb->prefix."honeybadger_static_attachments s where 1 order by id";
			$results2=$wpdb->get_results($sql);

			$sql="select * from ".$wpdb->prefix."honeybadger_attachments_so_tpl where 1 order by id";
			$results3=$wpdb->get_results($sql);
			if(is_array($results2))
			{
				foreach($results2 as $r2)
				{
					if(is_file(ABSPATH.$r2->path))
						$r2->filesize=filesize(ABSPATH.$r2->path);
				}
			}
			$return=new stdClass;
			$return->id=0;
			$return->content=$results;
			$return->templates=$results1;
			$return->static=$results2;
			$return->so_templates=$results3;
			$return->statuses=$this->get_order_statuses($request);
			$return->emails=$this->get_emails_simple($request);
			$return->sizes=$sizes;
			return array($return);
		}
	}
	function get_attachment($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$id=isset($parameters['id'])?(int)$parameters['id']:0;
			$oid=isset($parameters['oid'])?(int)$parameters['oid']:0;
			$is_supplier_order=isset($parameters['is_supplier_order'])?(int)$parameters['is_supplier_order']:0;
			$date_format=isset($parameters['date_format'])?sanitize_text_field($parameters['date_format']):"Y-m-d H:i:s";
			$supplier_order_str=isset($parameters['supplier_order'])?sanitize_text_field($parameters['supplier_order']):"";
			$supplier_order=array();
			if($supplier_order_str!="")
				parse_str($supplier_order_str,$supplier_order);
			if($oid==0 && $is_supplier_order==0)
			{
				$sql="select ID from ".$wpdb->prefix."posts WHERE post_type='shop_order' and post_status='wc-completed' order by post_date desc limit 1";
				$result=$wpdb->get_row($sql);
				if(isset($result->ID))
					$oid=$result->ID;
			}
			if($id>0)
			{
				$sql="select * from ".$wpdb->prefix."honeybadger_attachments where id='".esc_sql($id)."'";
				$result=$wpdb->get_row($sql);
				if(isset($result->content))
				{
					$supplier_order_id=0;
					$order = wc_get_order( $oid );
					if($order)
					{
						$result->content=str_ireplace("{customer}",esc_html( $order->get_billing_first_name() ),$result->content);
	    				$result->content=str_ireplace("{customer_full_name}",esc_html( $order->get_formatted_billing_full_name() ),$result->content);
						$result->content=str_ireplace("{site_title}", get_bloginfo( 'name', 'display' ) ,$result->content);
						$result->content=str_ireplace("{site_url}", get_bloginfo( 'url', 'display' ) ,$result->content);
						$result->content=str_ireplace("{order_date}", wc_format_datetime( $order->get_date_created(),$date_format ) ,$result->content);
						$result->content=str_ireplace("{date_completed}", wc_format_datetime( $order->get_date_completed(),$date_format ) ,$result->content);
						$result->content=str_ireplace("{date_paid}", wc_format_datetime( $order->get_date_paid(),$date_format ) ,$result->content);

						$result->content=str_ireplace("{shipping_price}", number_format($order->get_shipping_total(),2,".","") ,$result->content);
						$result->content=str_ireplace("{shipping_title}", $order->get_shipping_method() ,$result->content);
						$result->content=str_ireplace("{order_currency}", $order->get_currency() ,$result->content);
						$result->content=str_ireplace("{order_currency_symbol}", get_woocommerce_currency_symbol($order->get_currency()) ,$result->content);
						$countries=WC()->countries->get_countries();
						$states=WC()->countries->get_states();
						$billing_country_orig=$order->get_billing_country();
						$billing_country=((isset($countries[$billing_country_orig]))?$countries[$billing_country_orig]:$billing_country_orig);
						$result->content=str_ireplace("{billing_country}", $billing_country ,$result->content);
						$billing_state_orig=$order->get_billing_state();
						$billing_state=((isset($states[$billing_country_orig][$billing_state_orig]))?$states[$billing_country_orig][$billing_state_orig]:$billing_state_orig);
						$result->content=str_ireplace("{billing_state}", $billing_state ,$result->content);
						$shipping_country_orig=$order->get_shipping_country();
						$shipping_country=((isset($countries[$shipping_country_orig]))?$countries[$shipping_country_orig]:$shipping_country_orig);
						$result->content=str_ireplace("{shipping_country}", $shipping_country ,$result->content);
						$shipping_state_orig=$order->get_shipping_state();
						$shipping_state=((isset($states[$shipping_country_orig][$shipping_state_orig]))?$states[$shipping_country_orig][$shipping_state_orig]:$shipping_state_orig);
						$result->content=str_ireplace("{shipping_state}", $shipping_state ,$result->content);
						if(is_array($this->order_addresses_tags))
						{
							foreach($this->order_addresses_tags as $tag)
							{
								$tmp_func="get_".$tag;
								$result->content=str_ireplace("{".$tag."}", $order->$tmp_func() ,$result->content);
							}
						}
						if(is_array($this->order_addresses_tags))
						{
							foreach($this->order_other_tags as $tag)
							{
								$tmp_func="get_".$tag;
								$result->content=str_ireplace("{".$tag."}", $order->$tmp_func() ,$result->content);
							}
						}
						$pos = strpos($result->content, "{hb_order_products}");
					    if ($pos !== false)
					    {
					    	$hb_order_products='';
					    	$sql="select * from ".$wpdb->prefix."honeybadger_attachments_tpl where 1 order by id";
					    	$rows=$wpdb->get_results($sql);
					    	if(is_array($rows))
					    	{
					    		foreach($rows as $row)
					    		{
					    			if($row->title=='Order items Head')
					    				$hb_order_products.=$row->content;
					    		}
					    		foreach($rows as $row)
					    		{
					    			if($row->title=='Order items')
					    			{
					    				$items=$order->get_items();
					    				if(is_array($items))
					    				{
					    					foreach($items as $item_id => $item)
					    					{
					    						$product = $item->get_product();
					    						$image = $product->get_image(array(32,32));
					    						$qty          = $item->get_quantity();
												$refunded_qty = $order->get_qty_refunded_for_item( $item_id );

												if ( $refunded_qty ) {
													$qty_display = '<del>' . esc_html( $qty ) . '</del> <ins>' . esc_html( $qty - ( $refunded_qty * -1 ) ) . '</ins>';
												} else {
													$qty_display = esc_html( $qty );
												}
					    						$item_row=$row->content;
					    						$item_row=str_ireplace("{product_name}", $item->get_name() ,$item_row);
					    						$item_row=str_ireplace("{product_quantity}", $qty_display ,$item_row);
					    						$item_row=str_ireplace("{product_price}", $order->get_formatted_line_subtotal( $item ) ,$item_row);
					    						$item_row=str_ireplace("{product_sku}", $product->get_sku() ,$item_row);
					    						$item_row=str_ireplace("{product_image}", wp_kses_post( apply_filters( 'woocommerce_order_item_thumbnail', $image, $item ) ) ,$item_row);
					    						$hb_order_products.=$item_row;
					    					}
					    				}
					    			}
					    		}
					    		foreach($rows as $row)
					    		{
					    			if($row->title=='Order items Footer')
					    			{
					    				$item_totals = $order->get_order_item_totals();
					    				if(is_array($item_totals))
					    				{
					    					foreach($item_totals as $total)
					    					{
					    						$item_row=$row->content;
					    						$item_row=str_ireplace("{subtotal_label}", $total['label'] ,$item_row);
					    						$item_row=str_ireplace("{subtotal_value}", $total['value'] ,$item_row);
					    						$hb_order_products.=$item_row;
					    					}
					    				}
					    			}
					    		}
					    	}
					    	$result->content=str_ireplace("{hb_order_products}", $hb_order_products ,$result->content);
					    }
					}
				    if(is_array($supplier_order) && count($supplier_order)>0)
					{
						if(isset($supplier_order["so_subtotal"]))
							$supplier_order["so_subtotal"]=number_format(round($supplier_order["so_subtotal"],2),2,".","");
						if(isset($supplier_order["so_tax_total"]))
							$supplier_order["so_tax_total"]=number_format(round($supplier_order["so_tax_total"],2),2,".","");
						if(isset($supplier_order["so_postage_cost"]))
							$supplier_order["so_postage_cost"]=number_format(round($supplier_order["so_postage_cost"],2),2,".","");
						$order_total=$supplier_order["so_subtotal"]+$supplier_order["so_tax_total"]+$supplier_order["so_postage_cost"];
						$order_total=number_format(round($order_total,2),2,".","");
						if(!isset($supplier_order["so_total"]))
							$supplier_order["so_total"]=$order_total;
						if(isset($supplier_order['so_order_items']))
						{
							$so_order_items="";
							$sql="select * from ".$wpdb->prefix."honeybadger_attachments_so_tpl where 1";
							$rows=$wpdb->get_results($sql);
							if(is_array($rows))
							{
								foreach($rows as $row)
					    		{
					    			if($row->title=='Order items Head')
					    				$so_order_items.=$row->content;
					    		}
								foreach($rows as $row)
					    		{
					    			if($row->title=='Order items')
					    			{
					    				$items=$supplier_order['so_order_items'];
					    				if(is_array($items))
					    				{
					    					for($i=0;$i<count($items);$i++)
					    					{
					    						$items[$i]['so_item_qty']=(int)$items[$i]['so_item_qty'];
					    						$items[$i]['so_item_price']=number_format(round((float)$items[$i]['so_item_price'],2),2,".","");
					    						$items[$i]['so_item_tax']=number_format(round((float)$items[$i]['so_item_tax'],2),2,".","");
					    						$item_total=($items[$i]['so_item_price']+$items[$i]['so_item_tax'])*$items[$i]['so_item_qty'];
					    						$item_total=number_format(round($item_total,2),2,".","");
					    						$items[$i]['so_item_total']=$item_total;
					    						foreach($items[$i] as $tag => $tag_value)
					    						{
					    							if($tag=="so_item_sku" && $items[$i]["so_item_sku"]!="")
					    								$items[$i]["so_item_sku"]=" [".$items[$i]["so_item_sku"]."]";
					    							if($tag=="so_supplier_description" && $items[$i]["so_supplier_description"]!="")
					    								$items[$i]["so_supplier_description"]=" (".$items[$i]["so_supplier_description"].")";
					    							if($tag=="so_item_product_id" && (int)$tag_value>0)
					    							{
					    								$items[$i]["so_item_name"]=get_the_title($tag_value);
					    								unset($items[$i]["so_item_product_id"]);
					    							}
					    						}
					    					}
					    				}
					    				if(is_array($items))
					    				{
					    					foreach($items as $item)
					    					{
					    						$item_row=$row->content;
					    						foreach($item as $tag => $tag_value)
					    						{
					    							if(isset($supplier_order[$tag]) && !is_array($supplier_order[$tag]))
					    								$tag_value=$supplier_order[$tag];
					    							$item_row=str_ireplace("{".$tag."}", $tag_value ,$item_row);
					    						}
					    						$so_order_items.=$item_row;
					    					}
					    				}
					    			}
					    		}
					    		foreach($rows as $row)
					    		{
					    			if($row->title=='Order items Footer')
					    				$so_order_items.=$row->content;
					    		}
					    		foreach($supplier_order as $tag => $tag_value)
					    		{
					    			if(!is_array($tag_value))
										$so_order_items=str_ireplace("{".$tag."}", $tag_value ,$so_order_items);
					    		}
								$supplier_order['so_order_items']=$so_order_items;
							}
						}
						$pos = strpos($result->content, "{so_order_id}");
				    	if ($pos !== false)
				    	{
				    		foreach($supplier_order as $tag => $tag_value)
				    		{
				    			if($tag=="so_order_id")
				    				$supplier_order_id=$tag_value;
				    		}
				    	}
						foreach($supplier_order as $tag => $tag_value)
							$result->content=str_ireplace("{".$tag."}", $tag_value ,$result->content);
					}
				}
				$result->content=str_ireplace("<p></p>","",$result->content);
				$result->content=str_ireplace("<p>&nbsp;</p>","",$result->content);
				$result->content=str_ireplace("<br /><br />","<br />",$result->content);
				$result->content=str_ireplace("<br><br>","<br>",$result->content);
				$return=new stdClass;
				$return->id=0;
				$return->content=$result;
				$return->supplier_order_id=$supplier_order_id;
				return array($return);
			}
		}
	}
	function returnError($msg="")
	{
		$result=new stdClass;
		$result->id=0;
		$result->content=array('status'=>'error','msg'=>$msg);
		return array($result);
	}
	function returnOk($msg="")
	{
		$result=new stdClass;
		$result->id=0;
		$result->content=array('status'=>'ok','msg'=>$msg);
		return array($result);
	}
	function save_new_attachment_template($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$title=isset($parameters['title'])?sanitize_text_field($parameters['title']):"";
			$content=isset($parameters['content'])?wp_kses_post($parameters['content']):"";
			$pdf_size=isset($parameters['pdf_size'])?sanitize_text_field($parameters['pdf_size']):"";
			$pdf_font=isset($parameters['pdf_font'])?sanitize_text_field($parameters['pdf_font']):"";
			$pdf_orientation=isset($parameters['pdf_orientation'])?sanitize_text_field($parameters['pdf_orientation']):"";
			$pdf_margins=isset($parameters['pdf_margins'])?sanitize_text_field($parameters['pdf_margins']):"";
			$attach_to_wc_emails=isset($parameters['attach_to_wc_emails'])?$parameters['attach_to_wc_emails']:array();
			$attach_to_emails=isset($parameters['attach_to_emails'])?$parameters['attach_to_emails']:array();
			$keep_files=isset($parameters['keep_files'])?sanitize_text_field($parameters['keep_files']):"";
			$generable=isset($parameters['generable'])?sanitize_text_field($parameters['generable']):"";
			$so_generable=isset($parameters['so_generable'])?sanitize_text_field($parameters['so_generable']):"";
			$enabled=isset($parameters['enabled'])?sanitize_text_field($parameters['enabled']):"";
			if($keep_files=="on")
				$keep_files=1;
			else
				$keep_files=0;
			if($generable=="on")
				$generable=1;
			else
				$generable=0;
			if($so_generable=="on")
				$so_generable=1;
			else
				$so_generable=0;
			if($enabled=="on")
				$enabled=1;
			else
				$enabled=0;
			if($title=='' || $content=='')
				return $this->returnError();
			$folder_name = $this->getFolderName($title);
			$folder_path = HONEYBADGER_UPLOADS_PATH."attachments/".$folder_name;
			if(!is_dir($folder_path))
			{
				if(mkdir($folder_path))
				{
					if(!file_put_contents($folder_path."/index.php",'<?php // Silence is golden'))
						return $this->returnError();
				}
				else
					return $this->returnError();
			}
			$attach_to_wc_emails_str='';
			if($attach_to_wc_emails!="")
				$attach_to_wc_emails_str=implode(",",$attach_to_wc_emails);
			$attach_to_emails_str='';
			if($attach_to_emails!="")
				$attach_to_emails_str=implode(",",$attach_to_emails);
			$sql="insert into ".$wpdb->prefix."honeybadger_attachments set
			title='".esc_sql($title)."',
			content='".esc_sql($content)."',
			pdf_size='".esc_sql($pdf_size)."',
			pdf_font='".esc_sql($pdf_font)."',
			pdf_orientation='".esc_sql($pdf_orientation)."',
			pdf_margins='".esc_sql($pdf_margins)."',
			attach_to_wc_emails='".esc_sql($attach_to_wc_emails_str)."',
			attach_to_emails='".esc_sql($attach_to_emails_str)."',
			keep_files='".esc_sql($keep_files)."',
			generable='".esc_sql($generable)."',
			so_generable='".esc_sql($so_generable)."',
			enabled='".esc_sql($enabled)."',
			mdate='".time()."'";
			if(!$wpdb->query($sql) && $wpdb->last_error !== '')
				return $this->returnError();
			
			return $this->returnOk();
		}
	}
	function save_attachment_template($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$id=isset($parameters['id'])?(int)$parameters['id']:0;
			$title=isset($parameters['title'])?sanitize_text_field($parameters['title']):"";
			$content=isset($parameters['content'])?wp_kses_post($parameters['content']):"";
			$pdf_size=isset($parameters['pdf_size'])?sanitize_text_field($parameters['pdf_size']):"";
			$pdf_font=isset($parameters['pdf_font'])?sanitize_text_field($parameters['pdf_font']):"";
			$pdf_orientation=isset($parameters['pdf_orientation'])?sanitize_text_field($parameters['pdf_orientation']):"";
			$pdf_margins=isset($parameters['pdf_margins'])?sanitize_text_field($parameters['pdf_margins']):"";
			$attach_to_wc_emails=isset($parameters['attach_to_wc_emails'])?$parameters['attach_to_wc_emails']:array();
			$attach_to_emails=isset($parameters['attach_to_emails'])?$parameters['attach_to_emails']:array();
			$keep_files=isset($parameters['keep_files'])?sanitize_text_field($parameters['keep_files']):"";
			$generable=isset($parameters['generable'])?sanitize_text_field($parameters['generable']):"";
			$so_generable=isset($parameters['so_generable'])?sanitize_text_field($parameters['so_generable']):"";
			$enabled=isset($parameters['enabled'])?sanitize_text_field($parameters['enabled']):"";
			if($keep_files=="on")
				$keep_files=1;
			else
				$keep_files=0;
			if($generable=="on")
				$generable=1;
			else
				$generable=0;
			if($so_generable=="on")
				$so_generable=1;
			else
				$so_generable=0;
			if($enabled=="on")
				$enabled=1;
			else
				$enabled=0;
			if(!$id>0 || $title=='' || $content=='')
				return $this->returnError();
			$sql="select * from ".$wpdb->prefix."honeybadger_attachments where id='".esc_sql($id)."'";
			$result=$wpdb->get_row($sql);
			if(!empty($result))
			{
				if($title!=$result->title)
				{
					$new_folder_path = HONEYBADGER_UPLOADS_PATH."attachments/".$this->getFolderName($title);
					$old_folder_path = HONEYBADGER_UPLOADS_PATH."attachments/".$this->getFolderName($result->title);
					rename($old_folder_path,$new_folder_path);
				}
			}
			$attach_to_wc_emails_str='';
			if($attach_to_wc_emails!="")
				$attach_to_wc_emails_str=implode(",",$attach_to_wc_emails);
			$attach_to_emails_str='';
			if($attach_to_emails!="")
				$attach_to_emails_str=implode(",",$attach_to_emails);
			$sql="update ".$wpdb->prefix."honeybadger_attachments set
			title='".esc_sql($title)."',
			content='".esc_sql($content)."',
			pdf_size='".esc_sql($pdf_size)."',
			pdf_font='".esc_sql($pdf_font)."',
			pdf_orientation='".esc_sql($pdf_orientation)."',
			pdf_margins='".esc_sql($pdf_margins)."',
			attach_to_wc_emails='".esc_sql($attach_to_wc_emails_str)."',
			attach_to_emails='".esc_sql($attach_to_emails_str)."',
			keep_files='".esc_sql($keep_files)."',
			generable='".esc_sql($generable)."',
			so_generable='".esc_sql($so_generable)."',
			enabled='".esc_sql($enabled)."',
			mdate='".time()."'
			where id='".esc_sql($id)."'";

			if(!$wpdb->query($sql) && $wpdb->last_error !== '')
				return $this->returnError();
			return $this->returnOk();
		}
	}
	function save_attachment_subtemplate($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$id=isset($parameters['id'])?(int)$parameters['id']:0;
			$content=isset($parameters['content'])?wp_kses_post($parameters['content']):"";

			if(!$id>0 || $content=='')
				return $this->returnError();
			$sql="update ".$wpdb->prefix."honeybadger_attachments_tpl set
			content='".esc_sql($content)."',
			mdate='".time()."'
			where id='".esc_sql($id)."'";

			if(!$wpdb->query($sql) && $wpdb->last_error !== '')
				return $this->returnError();
			return $this->returnOk();
		}
	}
	function save_so_attachment_subtemplate($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$id=isset($parameters['id'])?(int)$parameters['id']:0;
			$content=isset($parameters['content'])?wp_kses_post($parameters['content']):"";

			if(!$id>0 || $content=='')
				return $this->returnError();
			$sql="update ".$wpdb->prefix."honeybadger_attachments_so_tpl set
			content='".esc_sql($content)."',
			mdate='".time()."'
			where id='".esc_sql($id)."'";

			if(!$wpdb->query($sql) && $wpdb->last_error !== '')
				return $this->returnError();
			return $this->returnOk();
		}
	}
	function save_so_email_subtemplate($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$id=isset($parameters['id'])?(int)$parameters['id']:0;
			$content=isset($parameters['content'])?wp_kses_post($parameters['content']):"";

			if(!$id>0 || $content=='')
				return $this->returnError();
			$sql="update ".$wpdb->prefix."honeybadger_so_emails_tpl set
			content='".esc_sql($content)."',
			mdate='".time()."'
			where id='".esc_sql($id)."'";

			if(!$wpdb->query($sql) && $wpdb->last_error !== '')
				return $this->returnError();
			return $this->returnOk();
		}
	}
	function get_email_template_default($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$id=isset($parameters['id'])?(int)$parameters['id']:0;

			if($id>0)
			{
				$sql="select * from ".$wpdb->prefix."honeybadger_emails where id='".esc_sql($id)."' and enabled=1";
				$result=new stdClass;
				$result->id=0;
				$result->content=$wpdb->get_row($sql);
				return array($result);
			}
			return $this->returnError();
		}
	}
	function delete_static_attachment($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$id=isset($parameters['id'])?(int)$parameters['id']:0;
			if($id>0)
			{
				$folder_path="";
				$sql="select * from ".$wpdb->prefix."honeybadger_static_attachments where id='".esc_sql($id)."'";
				$result=$wpdb->get_row($sql);
				if(!empty($result))
				{
					$path = ABSPATH.$result->path;
					if(is_file($path))
						unlink($path);
				}
				
				$sql="delete from ".$wpdb->prefix."honeybadger_static_attachments where id='".esc_sql($id)."'";
				if(!$wpdb->query($sql) && $wpdb->last_error !== '')
					return $this->returnError();
				return $this->returnOk();
			}
		}
	}
	function delete_attachment_template($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$id=isset($parameters['id'])?(int)$parameters['id']:0;
			if($id>0)
			{
				$folder_path="";
				$sql="select * from ".$wpdb->prefix."honeybadger_attachments where id='".esc_sql($id)."'";
				$result=$wpdb->get_row($sql);
				if(!empty($result))
				{
					$folder_name = $this->getFolderName($result->title);
					$folder_path = HONEYBADGER_UPLOADS_PATH."attachments/".$folder_name;
				}
				
				$sql="delete from ".$wpdb->prefix."honeybadger_attachments where id='".esc_sql($id)."'";
				if(!$wpdb->query($sql) && $wpdb->last_error !== '')
					return $this->returnError();
				if($folder_path!="")
				{
					$folder=scandir($folder_path);
					if($folder[0]=="." && $folder[1]==".." && $folder[2]=="index.php")
					{
						unlink($folder_path."/index.php");
						rmdir($folder_path);
					}
				}
				return $this->returnOk();
			}
		}
	}
	function send_attachment_template_test($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$id=isset($parameters['id'])?(int)$parameters['id']:0;
			$send_to=isset($parameters['send_to'])?sanitize_email($parameters['send_to']):"";
			$attachments=isset($_FILES['file'])?$_FILES['file']:array();
			if($id>0 && $send_to!="" && is_email($send_to) && is_array($attachments))
			{
				$sql="select * from ".$wpdb->prefix."honeybadger_attachments where id='".esc_sql($id)."'";
				$attachment=$wpdb->get_row($sql);
				if(isset($attachment->id))
				{
					$folder_name = $this->getFolderName($attachment->title);
					$target_file=HONEYBADGER_UPLOADS_PATH."attachments/tmp/".$folder_name.".pdf";
					if (!function_exists('wp_handle_upload'))
			        	require_once(ABSPATH . 'wp-admin/includes/file.php');
			        $uploadedfile = array(
			            'name'     => $attachments['name'][0],
			            'type'     => $attachments['type'][0],
			            'tmp_name' => $attachments['tmp_name'][0],
			            'error'    => $attachments['error'][0],
			            'size'     => $attachments['size'][0]
			        );
			        $upload_overrides = array( 'test_form' => false );
					$movefile=wp_handle_upload($uploadedfile, $upload_overrides);
					if ( $movefile && !isset( $movefile['error'] ) && isset($movefile['file']) && file_exists($movefile['file']))
					{
						copy($movefile['file'],$target_file);
						unlink($movefile['file']);
						$file_size=filesize($target_file);
						$file_size=round($file_size / 1024);
						$mailer = WC()->mailer();
						$subject=esc_html__("Test attachment","honeyb").": ".$attachment->title." (".$file_size."Kb)";
						$heading=esc_html__("Attached","honeyb")." (".$file_size."Kb)";
						$content = $this->get_custom_attachment_html( $heading, $mailer );
						$headers = "Content-Type: text/html\r\n";
						$all_attachments=array($target_file);
						$tmp_attachments=array();
						if(count($all_attachments)>0)
						{
							foreach($all_attachments as $attachment)
							{
								$file_name=$this->removeMd5FromFilename(basename($attachment));
								$new_path=HONEYBADGER_UPLOADS_PATH."attachments/tmp/".$file_name;
								if($file_name!=$new_path && copy($attachment,$new_path))
									$tmp_attachments[]=$new_path;
								else
									$tmp_attachments[]=$attachment;
							}
							$headers = "Content-Type: multipart/mixed\r\n";
						}
						if($mailer->send( $send_to, $subject, $content, $headers,$tmp_attachments ))
						{
							if(count($tmp_attachments)>0)
							{
								foreach($tmp_attachments as $file)
									{if(is_file($file))unlink($file);}
							}
							if(is_file($target_file))unlink($target_file);
							return $this->returnOk();
						}
						else
						{
							if(count($tmp_attachments)>0)
							{
								foreach($tmp_attachments as $file)
									{if(is_file($file))unlink($file);}
							}
							if(is_file($target_file))unlink($target_file);
						}
					}
					else
						return $this->returnError();
				}
			}
		}
		return $this->returnError();
	}
	function get_custom_attachment_html( $heading, $mailer )
	{
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		$template=HONEYBADGER_PLUGIN_PATH."includes/emails/attachment_email.php";
		$template_name="includes/emails/attachment_email.php";
		return wc_get_template_html( $template_name, array(
			'email_heading' => $heading,
			'sent_to_admin' => false,
			'plain_text'    => false,
			'email'         => $mailer
		),
		$template,
		HONEYBADGER_PLUGIN_PATH
		);
	}
	function getFolderName($title="")
	{
		if($title!="")
		{
			$title_2=str_replace(" ","_",$title);
			$folder_name = mb_ereg_replace("([^\w\s\d\-])", '', $title_2);
			return $folder_name;
		}
		return "";
	}
	function add_new_order_note($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$order_id=isset($parameters['order_id'])?(int)$parameters['order_id']:0;
			$note=isset($parameters['note'])?sanitize_textarea_field($parameters['note']):"";
			$type=isset($parameters['type'])?sanitize_text_field($parameters['type']):"";
			if($order_id>0 && $note!="" && $type!="")
			{
				$order = wc_get_order($order_id);
				$added=0;
				if($type=="customer")
					$added=$order->add_order_note( $note, true );
				else
					$added=$order->add_order_note( $note );
				if($added>0)
					return $this->returnOk();
			}
		}
		return $this->returnError();
	}
	function get_stock_settings($request)
	{
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$settings=new stdClass;
			$settings->woocommerce_manage_stock=get_option("woocommerce_manage_stock");
			$settings->woocommerce_notify_low_stock=get_option("woocommerce_notify_low_stock");
			$settings->woocommerce_notify_no_stock=get_option("woocommerce_notify_no_stock");
			$settings->woocommerce_stock_email_recipient=get_option("woocommerce_stock_email_recipient");
			$settings->woocommerce_notify_low_stock_amount=get_option("woocommerce_notify_low_stock_amount");
			$settings->woocommerce_notify_no_stock_amount=get_option("woocommerce_notify_no_stock_amount");
			$settings->enable_product_variation_extra_images=$this->config->enable_product_variation_extra_images;
			return $this->returnOk($settings);
		}
	}
	function save_stock_settings($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$woocommerce_manage_stock=isset($parameters['woocommerce_manage_stock'])?sanitize_text_field($parameters['woocommerce_manage_stock']):"";
			$woocommerce_notify_low_stock=isset($parameters['woocommerce_notify_low_stock'])?sanitize_text_field($parameters['woocommerce_notify_low_stock']):"";
			$woocommerce_notify_no_stock=isset($parameters['woocommerce_notify_no_stock'])?sanitize_text_field($parameters['woocommerce_notify_no_stock']):"";
			$woocommerce_stock_email_recipient=isset($parameters['woocommerce_stock_email_recipient'])?sanitize_email($parameters['woocommerce_stock_email_recipient']):"";
			$woocommerce_notify_low_stock_amount=isset($parameters['woocommerce_notify_low_stock_amount'])?sanitize_text_field($parameters['woocommerce_notify_low_stock_amount']):"";
			$woocommerce_notify_no_stock_amount=isset($parameters['woocommerce_notify_no_stock_amount'])?sanitize_text_field($parameters['woocommerce_notify_no_stock_amount']):"";
			$enable_product_variation_extra_images=isset($parameters['enable_product_variation_extra_images'])?sanitize_text_field($parameters['enable_product_variation_extra_images']):"";
			update_option("woocommerce_manage_stock",$woocommerce_manage_stock);
			update_option("woocommerce_notify_low_stock",$woocommerce_notify_low_stock);
			update_option("woocommerce_notify_no_stock",$woocommerce_notify_no_stock);
			update_option("woocommerce_stock_email_recipient",$woocommerce_stock_email_recipient);
			update_option("woocommerce_notify_low_stock_amount",$woocommerce_notify_low_stock_amount);
			update_option("woocommerce_notify_no_stock_amount",$woocommerce_notify_no_stock_amount);
			$sql="update ".$wpdb->prefix."honeybadger_config set config_value='".esc_sql($enable_product_variation_extra_images)."' where config_name='enable_product_variation_extra_images'";
			$wpdb->query($sql);
		}
		return $this->returnOk();
	}
	function split_order($request)
	{
		global $wpdb, $woocommerce;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$order_id=isset($parameters['order_id'])?(int)$parameters['order_id']:0;
			$products=isset($parameters['products'])?$parameters['products']:array();
			$quantities=isset($parameters['quantities'])?$parameters['quantities']:array();
			$variations=isset($parameters['variations'])?$parameters['variations']:array();
			if($order_id>0)
			{
				$this->split_refunds_if_multiple($order_id);
				$sql="select * from ".$wpdb->prefix."posts where ID='".esc_sql($order_id)."'";
				$order=$wpdb->get_row($sql);
				$order_items=$this->getOrderItems($order_id);
				$order_items_orig=unserialize(serialize($order_items));
				$new_products=$this->get_split_new_products($products,$variations,$quantities,$order_items,$order_id);
				$woocommerce->init();
				$woocommerce->frontend_includes();
				$session_class = apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' );
				require_once WC()->plugin_path() .'/includes/class-wc-cart-session.php';
				$woocommerce->session  = new \WC_Session_Handler();
				$woocommerce->cart     = new \WC_Cart();
				$woocommerce->customer = new \WC_Customer();
				if (! defined('WOOCOMMERCE_CHECKOUT')) define('WOOCOMMERCE_CHECKOUT', true);
				$woocommerce->cart->empty_cart();
				if(count($new_products)>0)
				{
					foreach($new_products as $new_prod)
					{
						if($new_prod->order_item_type=='line_item')
							$woocommerce->cart->add_to_cart( $new_prod->metas->_product_id, $new_prod->metas->_qty, $new_prod->metas->_variation_id );
					}
				}
				$new_cart_hash = md5("-938uqtuiagksrbs".json_encode(wc_clean($woocommerce->cart->get_cart_for_session())) . $woocommerce->cart->total);
				$woocommerce->cart->empty_cart();
				$post_password="wc_order_".wp_generate_password( 13, false );
				$order_obj=wc_get_order($order_id);
				if(isset($order->ID) && is_array($order_items))
				{
					$id = wp_insert_post(
						array(
							'post_date'     => gmdate( 'Y-m-d H:i:s', $order_obj->get_date_created( 'edit' )->getOffsetTimestamp() ),
							'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $order_obj->get_date_created( 'edit' )->getTimestamp() ),
							'post_type'     => $order->post_type,
							'post_status'   => $order->post_status,
							'ping_status'   => 'closed',
							'post_author'   => 1,
							'post_title'    => $order->post_title,
							'post_password' => $post_password,
							'post_parent'   => 0,
							'post_excerpt'  => ''
						)
					);
					if($id>0)
					{
						$sql="select * from ".$wpdb->prefix."postmeta where post_id='".esc_sql($order_id)."'";
						$post_metas=$wpdb->get_results($sql);
						if(is_array($post_metas))
						{
							foreach($post_metas as $post_meta)
							{
								if(in_array($post_meta->meta_key,array('_cart_discount','_cart_discount_tax','_honeybadger_split_in','_honeybadger_split_from')))
									continue;
								if(in_array($post_meta->meta_key,array('_order_shipping','_order_shipping_tax')))
									$post_meta->meta_value=0;
								$sql="insert into ".$wpdb->prefix."postmeta set
								post_id='".esc_sql($id)."',
								meta_key='".esc_sql($post_meta->meta_key)."',
								meta_value='".esc_sql($post_meta->meta_value)."'";
								$wpdb->query($sql);
							}
						}
						$product_qtys=array();
						foreach($order_items_orig as $order_item)
						{
							if($order_item->order_item_type=='line_item')
							{
								if(isset($order_item->metas->_product_id) && isset($order_item->metas->_qty))
									$product_qtys[$order_item->metas->_product_id]=$order_item->metas->_qty;
							}
							
						}
						if(count($new_products)>0)
						{
							foreach($new_products as $prod)
							{
								if(in_array($prod->order_item_type,array('fee','coupon')))
									continue;
								if($prod->order_item_type=='line_item' && isset($prod->metas->_product_id) && isset($product_qtys[$prod->metas->_product_id]))
								{
									if($prod->metas->_qty!=$product_qtys[$prod->metas->_product_id])
									{
										if(isset($prod->metas->_line_subtotal))
										{
											$prod->metas->_line_subtotal=($prod->metas->_line_subtotal/$product_qtys[$prod->metas->_product_id])*$prod->metas->_qty;
											if(!is_int($prod->metas->_line_subtotal))
												$prod->metas->_line_subtotal=number_format(round($prod->metas->_line_subtotal,2),2,".","");
										}
										if(isset($prod->metas->_line_subtotal_tax))
										{
											$prod->metas->_line_subtotal_tax=($prod->metas->_line_subtotal_tax/$product_qtys[$prod->metas->_product_id])*$prod->metas->_qty;
											if(!is_int($prod->metas->_line_subtotal_tax))
												$prod->metas->_line_subtotal_tax=number_format(round($prod->metas->_line_subtotal_tax,2),2,".","");
										}
										if(isset($prod->metas->_line_total))
										{
											$prod->metas->_line_total=($prod->metas->_line_total/$product_qtys[$prod->metas->_product_id])*$prod->metas->_qty;
											if(!is_int($prod->metas->_line_total))
												$prod->metas->_line_total=number_format(round($prod->metas->_line_total,2),2,".","");
										}
										if(isset($prod->metas->_line_tax))
										{
											$prod->metas->_line_tax=($prod->metas->_line_tax/$product_qtys[$prod->metas->_product_id])*$prod->metas->_qty;
											if(!is_int($prod->metas->_line_tax))
												$prod->metas->_line_tax=number_format(round($prod->metas->_line_tax,2),2,".","");
										}
										if(isset($prod->metas->cost))
										{
											$prod->metas->cost=($prod->metas->cost/$product_qtys[$prod->metas->_product_id])*$prod->metas->_qty;
											if(!is_int($prod->metas->cost))
											$prod->metas->cost=number_format(round($prod->metas->cost,2),2,".","");
										}
										$prod->metas->_line_tax_data=serialize(array('total'=>array('1'=>(string)$prod->metas->_line_tax),'subtotal'=>array('1'=>(string)$prod->metas->_line_subtotal_tax)));
									}
								}
								$sql="insert into ".$wpdb->prefix."woocommerce_order_items set
								order_item_name='".esc_sql($prod->order_item_name)."',
								order_item_type='".esc_sql($prod->order_item_type)."',
								order_id='".esc_sql($id)."'";
								if($wpdb->query($sql))
								{
									$order_item_id = $wpdb->insert_id;
									if($order_item_id>0)
									{
										foreach($prod->metas as $meta_key => $meta_value)
										{
											$sql="insert into ".$wpdb->prefix."woocommerce_order_itemmeta set
											order_item_id='".esc_sql($order_item_id)."',
											meta_key='".esc_sql($meta_key)."',
											meta_value='".esc_sql($meta_value)."'";
											$wpdb->query($sql);
										}
									}
									$other_refund=$this->get_refunded_amount_order_id($order_id,$prod->order_item_id);
									$other_refund_id=$other_refund[0];
									$other_refund_order_item_id=$other_refund[1];
									if($other_refund>0 && $other_refund_order_item_id>0)
									{
										$sql="update ".$wpdb->prefix."posts set post_parent='".esc_sql($id)."' where ID='".esc_sql($other_refund_id)."'";
										$wpdb->query($sql);
										$sql="update ".$wpdb->prefix."woocommerce_order_itemmeta set meta_value='".esc_sql($order_item_id)."' where order_item_id='".esc_sql($other_refund_order_item_id)."' and meta_key='_refunded_item_id'";
										$wpdb->query($sql);
									}
								}
							}
						}
						$order=wc_get_order($id);
						$order->add_order_note(esc_html__("Order split by HoneyBadger IT, split from","honeyb")." ".$order_id);
						update_post_meta($id,'_cart_hash',$new_cart_hash);
						update_post_meta($id,'_honeybadger_split_from',$order_id);
						$order->calculate_taxes();
					    $order->calculate_totals();
					    $order->save();
					    if(count($new_products)>0)
						{
							foreach($new_products as $prod)
							{
								foreach($order_items_orig as $order_item)
								{
									if($order_item->order_item_type=='line_item' && $prod->order_item_type=='line_item' && $order_item->metas->_product_id==$prod->metas->_product_id && $order_item->metas->_variation_id==$prod->metas->_variation_id)
									{
										$order_item->metas->_qty=$order_item->metas->_qty-$prod->metas->_qty;
										if($order_item->metas->_qty<=0)
										{
											$sql="delete from ".$wpdb->prefix."woocommerce_order_items where order_item_id='".esc_sql($order_item->order_item_id)."'";
											$wpdb->query($sql);
											$sql="delete from ".$wpdb->prefix."woocommerce_order_itemmeta where order_item_id='".esc_sql($order_item->order_item_id)."'";
											$wpdb->query($sql);
										}
										else
										{
											if(isset($order_item->metas->_line_subtotal))
											{
												$order_item->metas->_line_subtotal=($order_item->metas->_line_subtotal/$product_qtys[$order_item->metas->_product_id])*$order_item->metas->_qty;
												if(!is_int($order_item->metas->_line_subtotal))
													$order_item->metas->_line_subtotal=number_format(round($order_item->metas->_line_subtotal,2),2,".","");
											}
											if(isset($order_item->metas->_line_subtotal_tax))
											{
												$order_item->metas->_line_subtotal_tax=($order_item->metas->_line_subtotal_tax/$product_qtys[$order_item->metas->_product_id])*$order_item->metas->_qty;
												if(!is_int($order_item->metas->_line_subtotal_tax))
													$order_item->metas->_line_subtotal_tax=number_format(round($order_item->metas->_line_subtotal_tax,2),2,".","");
											}
											if(isset($order_item->metas->_line_total))
											{
												$order_item->metas->_line_total=($order_item->metas->_line_total/$product_qtys[$order_item->metas->_product_id])*$order_item->metas->_qty;
												if(!is_int($order_item->metas->_line_total))
													$order_item->metas->_line_total=number_format(round($order_item->metas->_line_total,2),2,".","");
											}
											if(isset($order_item->metas->_line_tax))
											{
												$order_item->metas->_line_tax=($order_item->metas->_line_tax/$product_qtys[$order_item->metas->_product_id])*$order_item->metas->_qty;
												if(!is_int($order_item->metas->_line_tax))
													$order_item->metas->_line_tax=number_format(round($order_item->metas->_line_tax,2),2,".","");
											}
											if(isset($order_item->metas->cost))
											{
												$order_item->metas->cost=($order_item->metas->cost/$product_qtys[$order_item->metas->_product_id])*$order_item->metas->_qty;
												if(!is_int($order_item->metas->cost))
												$order_item->metas->cost=number_format(round($order_item->metas->cost,2),2,".","");
											}
											$order_item->metas->_line_tax_data=serialize(array('total'=>array('1'=>(string)$order_item->metas->_line_tax),'subtotal'=>array('1'=>(string)$order_item->metas->_line_subtotal_tax)));
											foreach($order_item->metas as $meta_key => $meta_value)
											{
												$sql="update ".$wpdb->prefix."woocommerce_order_itemmeta set meta_value='".esc_sql($meta_value)."' where order_item_id='".esc_sql($order_item->order_item_id)."' and meta_key='".esc_sql($meta_key)."'";
												$wpdb->query($sql);
											}
										}
									}
								}
							}
						}
						$order_obj->add_order_note(esc_html__("Order was split by HoneyBadger IT, split in","honeyb")." ".$id);
						$old_value=get_post_meta($order_id,'_honeybadger_split_in',true);
						$new_split_ids=array();
						if($old_value!="")
							$new_split_ids=explode(",",$old_value);
						$new_split_ids[]=$id;
						$new_split_ids=array_unique($new_split_ids);
						sort($new_split_ids);
						update_post_meta($order_id,'_honeybadger_split_in',implode(",",$new_split_ids));
					    $order_items=$this->getOrderItems($order_id);
						$product_names=array();
						foreach($order_items as $prod)
						{
							if(isset($prod->metas->_qty) && $prod->metas->_qty>0)
								$product_names[$prod->order_item_name]=$prod->metas->_qty;
						}
						foreach($order_items as $prod)
						{
							if($prod->order_item_type=='shipping' && isset($prod->metas->Items) && count($product_names)>0)
							{
								$tmp=array();
								foreach($product_names as $prod_name => $prod_qty)
									$tmp[]=$prod_name.' &times; '.$prod_qty;
								$prod->metas->Items=implode(', ',$tmp);
								$sql="update ".$wpdb->prefix."woocommerce_order_itemmeta set meta_value='".esc_sql($prod->metas->Items)."' where order_item_id='".esc_sql($prod->order_item_id)."' and meta_key='Items'";
								$wpdb->query($sql);
							}
						}
						return $this->get_order_details($request,$product_names);
					}
				}
				return $this->get_order_details($request);
			}
		}
	}
	function split_refunds_if_multiple($order_id=0)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if($order_id>0)
		{
			$main_order_obj=wc_get_order($order_id);
			$sql="select ID from ".$wpdb->prefix."posts where post_type='shop_order_refund' and post_parent='".esc_sql($order_id)."'";
			$refunds=$wpdb->get_results($sql);
			if(is_array($refunds))
			{
				foreach($refunds as $refund)
				{
					$sql="select * from ".$wpdb->prefix."woocommerce_order_items where order_id='".esc_sql($refund->ID)."'";
					$refund_items=$wpdb->get_results($sql);
					if(is_array($refund_items))
					{
						if(count($refund_items)>1)
						{
							$create_orders_for=array();
							$line_items=0;
							$first_line_item_id=0;
							$other_items=array();
							foreach($refund_items as $refund_item)
							{
								if($refund_item->order_item_type=='line_item')
								{
									$first_line_item_id=$refund_item->order_item_id;
									$line_items++;
								}
								else
									$other_items[]=$refund_item->order_item_id;
							}
							if($line_items>1)
							{
								foreach($refund_items as $refund_item)
								{
									if($refund_item->order_item_type=='line_item' && $refund_item->order_item_id!=$first_line_item_id)
										$create_orders_for[]=$refund_item->order_item_id;
								}
							}
							if(count($create_orders_for)>0)
							{
								$sql="select * from ".$wpdb->prefix."posts where ID='".esc_sql($refund->ID)."'";
								$order=$wpdb->get_row($sql);
								$order_obj=wc_get_order($refund->ID);
								$order_details=$main_order_obj->get_data();
								$calculate_taxes_for = array(
				                    'country'  => $order_details['billing']['country'],
				                    'state'    => $order_details['billing']['state'],
				                    'postcode' => $order_details['billing']['postcode'],
				                    'city'     => $order_details['billing']['city'],
				                );
								$sql="select * from ".$wpdb->prefix."postmeta where post_id='".esc_sql($refund->ID)."'";
								$post_metas=$wpdb->get_results($sql);
								foreach($create_orders_for as $create_for_item_id)
								{
									$post_password="wc_order_".wp_generate_password( 13, false );
									$id=0;
									if(isset($refund->ID))
									{
										$id = wp_insert_post(
											array(
												'post_date'     => gmdate( 'Y-m-d H:i:s', $order_obj->get_date_created( 'edit' )->getOffsetTimestamp() ),
												'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $order_obj->get_date_created( 'edit' )->getTimestamp() ),
												'post_type'     => $order->post_type,
												'post_status'   => $order->post_status,
												'ping_status'   => 'closed',
												'post_author'   => 1,
												'post_title'    => $order->post_title,
												'post_password' => $post_password,
												'post_parent'   => $order_id,
												'post_excerpt'  => ''
											)
										);
									}
									if($id>0)
									{
										if(is_array($post_metas))
										{
											foreach($post_metas as $post_meta)
											{
												if(in_array($post_meta->meta_key,array('_cart_discount','_cart_discount_tax')))
													continue;
												if(in_array($post_meta->meta_key,array('_order_shipping','_order_shipping_tax')))
													$post_meta->meta_value=0;
												$sql="insert into ".$wpdb->prefix."postmeta set
												post_id='".esc_sql($id)."',
												meta_key='".esc_sql($post_meta->meta_key)."',
												meta_value='".esc_sql($post_meta->meta_value)."'";
												$wpdb->query($sql);
											}
										}
										$sql="update ".$wpdb->prefix."woocommerce_order_items set order_id='".esc_sql($id)."' where order_item_id='".esc_sql($create_for_item_id)."'";
										$wpdb->query($sql);
										$this->recalculate_refund_order_totals($id);
									}
								}
								$this->recalculate_refund_order_totals($refund->ID);
							}
						}
					}
				}
			}
		}
	}
	function recalculate_order_totals($order_id=0)
	{
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if($order_id>0)
		{
			$order_obj=wc_get_order($order_id);
			$order_obj->calculate_taxes();
		    $order_obj->calculate_totals();
		    $order_obj->save();
		}
	}
	function recalculate_refund_order_totals($order_id=0)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if($order_id>0)
		{
			$refund_order=wc_get_order($order_id);
			$refund_order->update_taxes();
		    $refund_order->calculate_totals(false);
		    $refund_order->save();
		    $order_total=abs($refund_order->get_total());
		    $sql="update ".$wpdb->prefix."postmeta set meta_value='".esc_sql($order_total)."' where post_id='".esc_sql($order_id)."' and meta_key='_refund_amount'";
		    $wpdb->query($sql);
		}
	}
	function get_refunded_qty($order_id=0,$item_id=0,$product_id=0)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		$refunded_qty=0;
		if($order_id>0 && ($item_id>0 || $product_id>0))
		{
			$sql="select mm.* from ".$wpdb->prefix."posts p 
			inner join ".$wpdb->prefix."woocommerce_order_items m on m.order_id=p.ID
			inner join ".$wpdb->prefix."woocommerce_order_itemmeta mm on mm.order_item_id=m.order_item_id
			where p.post_type='shop_order_refund' and p.post_parent='".esc_sql($order_id)."'";
			$results=$wpdb->get_results($sql);
			if(is_array($results))
			{
				$new_results=array();
				foreach($results as $result)
					$new_results[$result->order_item_id][]=$result;
				$find_keys=array();
				foreach($new_results as $meta => $results_meta)
				{
					foreach($results_meta as $result)
					{
						if($item_id>0 && $result->meta_key=='_refunded_item_id' && $result->meta_value==$item_id)
							$find_keys[]=$meta;
						if($product_id>0 && $result->meta_key=='_product_id' && $result->meta_value==$product_id)
							$find_keys[]=$meta;
					}
				}
				foreach($find_keys as $find_key)
				{
					if(isset($new_results[$find_key]))
					{
						foreach($new_results[$find_key] as $result)
						{
							if($result->meta_key=='_qty' && $result->meta_value<0)
							{
								$refunded_qty=abs($result->meta_value);
							}
						}
					}
				}
			}
		}
		return $refunded_qty;
	}
	function get_refunded_amount_order_id($order_id=0,$item_id=0)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		$refunded_value=0;
		$post_id=0;
		$order_item_id=0;
		if($order_id>0 && $item_id>0)
		{
			$sql="select p.ID, mm.* from ".$wpdb->prefix."posts p 
			inner join ".$wpdb->prefix."woocommerce_order_items m on m.order_id=p.ID
			inner join ".$wpdb->prefix."woocommerce_order_itemmeta mm on mm.order_item_id=m.order_item_id
			where p.post_type='shop_order_refund' and p.post_parent='".esc_sql($order_id)."'";
			$results=$wpdb->get_results($sql);
			if(is_array($results))
			{
				$new_results=array();
				foreach($results as $result)
					$new_results[$result->order_item_id][]=$result;
				$find_key=0;
				foreach($new_results as $meta => $results_meta)
				{
					foreach($results_meta as $result)
					{
						if($item_id>0 && $result->meta_key=='_refunded_item_id' && $result->meta_value==$item_id)
							$find_key=$meta;
					}
				}
				if(isset($new_results[$find_key]))
				{
					foreach($new_results[$find_key] as $result)
					{
						if($result->meta_key=='_line_total' && abs($this->get_wc_order_item_meta($find_key,"_qty"))==0)
						{
							$refunded_value=abs($result->meta_value);
							$order_item_id=$find_key;
							$sql="select order_id from ".$wpdb->prefix."woocommerce_order_items where order_item_id='".esc_sql($order_item_id)."'";
							$result=$wpdb->get_row($sql);
							if(isset($result->order_id))
								$post_id=$result->order_id;
						}
					}
				}
			}
		}
		if($refunded_value>0)
			return array($post_id,$order_item_id);
		else
			return array(0,0);
	}
	function get_wc_order_item_meta($order_item_id=0,$meta_key='')
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if($order_item_id>0 && $meta_key!='')
		{
			$sql="select meta_value from ".$wpdb->prefix."woocommerce_order_itemmeta where order_item_id='".esc_sql($order_item_id)."' and meta_key='".esc_sql($meta_key)."'";
			$result=$wpdb->get_row($sql);
			if(isset($result->meta_value))
				return $result->meta_value;
		}
		return '';
	}
	function get_split_new_products($products_to_keep=array(),$product_variations=array(),$product_to_keep_qty=array(),$new_products=array(),$order_id=0)
	{
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(count($products_to_keep)>0 && count($product_variations) && count($product_to_keep_qty)==count($products_to_keep) && count($product_variations)==count($products_to_keep) && count($new_products)>0)
		{
			$prods=array();
			for($i=0;$i<count($products_to_keep);$i++)
				$prods[$products_to_keep[$i]."_".$product_variations[$i]]=$product_to_keep_qty[$i];
			for($i=0;$i<count($new_products);$i++)
			{
				if(isset($new_products[$i]->metas->_product_id) && isset($new_products[$i]->metas->_variation_id) && isset($prods[$new_products[$i]->metas->_product_id."_".$new_products[$i]->metas->_variation_id]))
				{
					$refund_qty=$this->get_refunded_qty($order_id,$new_products[$i]->order_item_id);
					$new_products[$i]->metas->_qty=$new_products[$i]->metas->_qty-$prods[$new_products[$i]->metas->_product_id."_".$new_products[$i]->metas->_variation_id]-$refund_qty;
				}
			}
			$products_tmp=array();
			$product_names=array();
			foreach($new_products as $prod)
			{
				if(isset($prod->metas->_qty) && $prod->metas->_qty>0)
				{
					$product_names[$prod->order_item_name]=$prod->metas->_qty;
					$products_tmp[]=$prod;
				}
				if($prod->order_item_type!='line_item')
				{
					if($prod->order_item_type=='shipping')
					{
						$prod->metas->cost=0;
						$prod->metas->total_tax=0;
						$prod->metas->taxes=serialize(array('total'=>array()));
					}
					$products_tmp[]=$prod;
				}
			}
			$new_products=$products_tmp;
			foreach($new_products as $prod)
			{
				if($prod->order_item_type=='shipping' && isset($prod->metas->Items) && count($product_names)>0)
				{
					$tmp=array();
					foreach($product_names as $prod_name => $prod_qty)
						$tmp[]=$prod_name.' &times; '.$prod_qty;
					$prod->metas->Items=implode(', ',$tmp);
				}
			}
		}
		return $new_products;
	}
	function get_formatted_items($items=array())
	{
		$new_items=array();
		if(count($items)>0)
		{
			$item_ids=array();
			foreach($items as $item)
			{
				if(!in_array($item->order_item_id,$item_ids))
				{
					$item_ids[]=$item->order_item_id;
					$tmp=new stdClass;
					$tmp->order_item_id=$item->order_item_id;
					$tmp->order_item_name=$item->order_item_name;
					$tmp->order_item_type=$item->order_item_type;
					$tmp->order_id=$item->order_id;
					$tmp->metas=new stdClass;
					$new_items[]=$tmp;
				}
			}
			foreach($new_items as $new_item)
			{
				foreach($items as $item)
				{
					if($new_item->order_item_id==$item->order_item_id)
						$new_item->metas->{$item->meta_key}=$item->meta_value;
				}
			}
		}
		return $new_items;
	}
	function start_combine_orders($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$order_id=isset($parameters['order_id'])?(int)$parameters['order_id']:0;
			$order_status=isset($parameters['order_status'])?sanitize_text_field($parameters['order_status']):"";
			$return_orders=array();
			$order=wc_get_order($order_id);
			$customer_orders=array();
			$customer_id=$order->get_customer_id();
			if($customer_id>0)
			{
				$customer_orders=wc_get_orders(array(
	                'type'        => 'shop_order',
	                'limit'       => - 1,
	                'customer_id' => $customer_id,
	                'post_status' =>$order_status
            	));
			}
			$siblings_tmp=$this->getOrderSiblings($order_id);
			$siblings=array();
			if(is_array($siblings_tmp))
			{
				foreach($siblings_tmp as $stmp)
				{
					if($stmp==$order_id)
						continue;
					$siblings[]=$stmp;
				}
				$sql="select ID, post_status from ".$wpdb->prefix."posts where ID in ('".implode("','",array_map('esc_sql',$siblings))."')";
				$results=$wpdb->get_results($sql);
				if(is_array($results))
				{
					$siblings=array();
					foreach($results as $result)
					{
						if($result->post_status==$order_status)
							$siblings[]=$result->ID;
					}
				}
			}
			$statuses=wc_get_order_statuses();
			if(count($siblings)>0)
			{
				foreach($siblings as $sibling)
				{
					$products=array();
					$sibling_order=wc_get_order($sibling);
					$order_details=$sibling_order->get_data();
					$items=$sibling_order->get_items();
					if(count($items))
					{
						foreach($items as $item_id => $item)
						{
							$product=new stdClass;
							$product->id=$item->get_product_id();
							$product->product=$item->get_name();
							$product->qty=$item->get_quantity();
							$product->price=$item->get_total();
							$products[]=$product;
						}
					}
					$return_orders[]=array(
						'id'=>$sibling,
						'date_created'=>$order_details['date_created']->__toString(),
						'status_orig'=>$order_details['status'],
						'status'=>((isset($statuses['wc-'.$order_details['status']]))?$statuses['wc-'.$order_details['status']]:$order_details['status']),
						'date'=>$sibling_order->get_date_created()->getTimestamp(),
						'billing'=>$sibling_order->get_address('billing'),
						'products'=>$products
					);
				}
			}
			if(is_array($customer_orders))
			{
				foreach($customer_orders as $customer_order)
				{
					$customer_order_id=$customer_order->get_id();
					if($customer_order_id==$order_id || in_array($customer_order_id,$siblings))
						continue;
					$products=array();
					$order_details=$customer_order->get_data();
					$items=$customer_order->get_items();
					if(count($items))
					{
						foreach($items as $item_id => $item)
						{
							$product=new stdClass;
							$product->id=$item->get_product_id();
							$product->product=$item->get_name();
							$product->qty=$item->get_quantity();
							$product->price=$item->get_total();
							$products[]=$product;
						}
					}
					$return_orders[]=array(
						'id'=>$customer_order_id,
						'date_created'=>$order_details['date_created']->__toString(),
						'status_orig'=>$order_details['status'],
						'status'=>((isset($statuses['wc-'.$order_details['status']]))?$statuses['wc-'.$order_details['status']]:$order_details['status']),
						'date'=>$customer_order->get_date_created()->getTimestamp(),
						'billing'=>$customer_order->get_address('billing'),
						'products'=>$products
					);
				}
			}
			
			$return=new stdClass;
			$return->id=0;
			$return->orders=$return_orders;
			$return->siblings=$siblings;
			return array($return);
		}
	}
	function combine_orders($request)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$order_id=isset($parameters['order_id'])?(int)$parameters['order_id']:0;
			$last_order_id=isset($parameters['last_order_id'])?(int)$parameters['last_order_id']:0;
			$order_ids=isset($parameters['order_ids'])?$parameters['order_ids']:array();
			if($order_id>0 && is_array($order_ids) && count($order_ids)>0)
			{
				$deleted_order_ids=array();
				$siblings_tmp=$this->getOrderSiblings($order_id);
				$siblings=array();
				if(is_array($siblings_tmp))
				{
					foreach($siblings_tmp as $stmp)
					{
						if($stmp==$order_id)
							continue;
						$siblings[]=$stmp;
					}
				}
				foreach($order_ids as $id)
				{
					$sql="select meta_id, meta_key, meta_value from ".$wpdb->prefix."postmeta where post_id='".esc_sql($id)."'";
					$results=$wpdb->get_results($sql);
					if(is_array($results))
					{
						foreach($results as $result)
						{
							if($result->meta_key=='_honeybadger_split_from' || $result->meta_key=='_honeybadger_split_in' || get_post_meta($order_id,$result->meta_key,true)!='' || $result->meta_value=='')
								continue;
							$sql="update ".$wpdb->prefix."postmeta set post_id='".esc_sql($order_id)."' where meta_id='".esc_sql($result->meta_id)."'";
							$wpdb->query($sql);
						}
					}
					$sql="delete from ".$wpdb->prefix."postmeta where post_id='".esc_sql($id)."'";
					$wpdb->query($sql);
					$sql="delete from ".$wpdb->prefix."posts where ID='".esc_sql($id)."'";
					$wpdb->query($sql);
					$deleted_order_ids[]=$id;
					$sql="select comment_ID from ".$wpdb->prefix."comments where comment_post_ID='".esc_sql($id)."'";
					$results=$wpdb->get_results($sql);
					if(is_array($results))
					{
						foreach($results as $result)
						{
							$sql="update ".$wpdb->prefix."comments set comment_post_ID='".esc_sql($order_id)."' where comment_ID='".esc_sql($result->comment_ID)."'";
							$wpdb->query($sql);
						}
					}
					$child_order_item_ids=array();
					$sql="select order_item_id from ".$wpdb->prefix."woocommerce_order_items where order_id='".esc_sql($id)."'";
					$results=$wpdb->get_results($sql);
					if(is_array($results))
					{
						foreach($results as $result)
						{
							$sql="update ".$wpdb->prefix."woocommerce_order_items set order_id='".esc_sql($order_id)."' where order_item_id='".esc_sql($result->order_item_id)."'";
							$wpdb->query($sql);
							$child_order_item_ids[]=$result->order_item_id;
						}
					}
					$sql="select ID from ".$wpdb->prefix."posts where post_parent='".esc_sql($id)."'";
					$results=$wpdb->get_results($sql);
					if(is_array($results))
					{
						foreach($results as $result)
						{
							$sql="update ".$wpdb->prefix."posts set post_parent='".esc_sql($order_id)."' where ID='".esc_sql($result->ID)."'";
							$wpdb->query($sql);
						}
					}
				}
				$combined_in=get_post_meta($order_id,'_honeybadger_split_in',true);
				$split_id=explode(",",$combined_in);
				$new_split_ids=array();
				if(is_array($split_id) && count($split_id)>0)
				{
					foreach($split_id as $split_id)
					{
						if(!in_array($split_id,$order_ids))
							$new_split_ids[]=$split_id;
					}
				}
				if(is_array($siblings))
				{
					foreach($siblings as $sibling)
					{
						if(!in_array($sibling,$order_ids))
						{
							$new_split_ids[]=$sibling;
							update_post_meta($sibling,'_honeybadger_split_from',$order_id);
							update_post_meta($sibling,'_honeybadger_split_in','');
						}
					}
				}
				$split_from=get_post_meta($order_id,'_honeybadger_split_from',true);
				if(in_array($split_from,$order_ids))
					$split_from='';
				$order=wc_get_order($order_id);
				$order->add_order_note(esc_html__("Order combined by HoneyBadger IT, combined from","honeyb")." ".$order_id." ".implode(" ",$order_ids));
				update_post_meta($order_id,'_honeybadger_split_from',$split_from);
				update_post_meta($order_id,'_honeybadger_split_in',implode(",",$new_split_ids));
				$order->calculate_taxes();
			    $order->calculate_totals();
			    $order->save();
			    $sql="select * from ".$wpdb->prefix."posts where ID='".esc_sql($order_id)."'";
				$order=$wpdb->get_row($sql);
				$order_items=$this->getOrderItems($order_id);
				if(is_array($order_items))
				{
					foreach($order_items as $item)
					{
						if($item->order_item_type=='shipping' && isset($item->metas->cost) && $item->metas->cost==0)
						{
							$sql="delete from ".$wpdb->prefix."woocommerce_order_itemmeta where order_item_id='".esc_sql($item->order_item_id)."'";
							$wpdb->query($sql);
							$sql="delete from ".$wpdb->prefix."woocommerce_order_items where order_item_id='".esc_sql($item->order_item_id)."'";
							$wpdb->query($sql);
						}
					}
				}
				$order_items=$this->getOrderItems($order_id);
				$product_names=array();
				foreach($order_items as $prod)
				{
					if(isset($prod->metas->_qty) && $prod->metas->_qty>0)
						$product_names[$prod->order_item_name]=$prod->metas->_qty;
				}
				foreach($order_items as $prod)
				{
					if($prod->order_item_type=='shipping' && isset($prod->metas->Items) && count($product_names)>0)
					{
						$tmp=array();
						foreach($product_names as $prod_name => $prod_qty)
							$tmp[]=$prod_name.' &times; '.$prod_qty;
						$prod->metas->Items=implode(', ',$tmp);
						$sql="update ".$wpdb->prefix."woocommerce_order_itemmeta set meta_value='".esc_sql($prod->metas->Items)."' where order_item_id='".esc_sql($prod->order_item_id)."' and meta_key='Items'";
						$wpdb->query($sql);
					}
				}
				$this->combineOrderProducts($order_id);
				sort($deleted_order_ids);
				$max_order_id=$deleted_order_ids[count($deleted_order_ids)-1];
				$new_last_order_id=0;
				if($order_id>$max_order_id)
					$new_last_order_id=$order_id;
				else
					$new_last_order_id=$max_order_id;
				if($last_order_id<$new_last_order_id)
					$last_order_id=$new_last_order_id;
				return $this->get_order_details($request,array(),$last_order_id);
			}
		}
	}
	function getOrderItems($order_id=0)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if($order_id>0)
		{
			$sql="select * from ".$wpdb->prefix."woocommerce_order_items i 
			left join ".$wpdb->prefix."woocommerce_order_itemmeta m on m.order_item_id=i.order_item_id
			where i.order_id='".esc_sql($order_id)."'";
			$order_items=$this->get_formatted_items($wpdb->get_results($sql));
			return $order_items;
		}
		return array();
	}
	function combineOrderProducts($order_id=0)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		$order_id=(int)$order_id;
		if($order_id>0)
		{
			$order_items=$this->getOrderItems($order_id);
			$products=array();
			foreach($order_items as $item)
			{
				if(isset($item->metas->_product_id) && isset($item->metas->_qty) && isset($item->metas->_variation_id))
				{
					if(!isset($products[$item->metas->_product_id."_".$item->metas->_variation_id]))
						$products[$item->metas->_product_id."_".$item->metas->_variation_id]=0;
					$products[$item->metas->_product_id."_".$item->metas->_variation_id]+=$item->metas->_qty;
				}
			}
			$item_id_to_keep=array();
			if(is_array($products))
			{
				foreach($products as $product_id => $total_qty)
				{
					foreach($order_items as $item)
					{
						if(isset($item->metas->_product_id) && isset($item->metas->_qty) && isset($item->metas->_variation_id) && $product_id==$item->metas->_product_id."_".$item->metas->_variation_id && $total_qty!=$item->metas->_qty)
						{
							if(!isset($item_id_to_keep[$item->metas->_product_id."_".$item->metas->_variation_id]))
								$item_id_to_keep[$item->metas->_product_id."_".$item->metas->_variation_id]=$item->order_item_id;
						}
					}
				}
			}
			$products_to_combine=array();
			if(is_array($item_id_to_keep))
			{
				foreach($item_id_to_keep as $product_id => $order_item_id)
				{
					foreach($order_items as $item)
					{
						if(isset($item->metas->_product_id) && isset($item->metas->_qty) && isset($item->metas->_variation_id) && $product_id==$item->metas->_product_id."_".$item->metas->_variation_id && $order_item_id!=$item->order_item_id)
						{
							if(!isset($products_to_combine[$item->metas->_product_id."_".$item->metas->_variation_id]))
								$products_to_combine[$item->metas->_product_id."_".$item->metas->_variation_id]=array();
							$products_to_combine[$item->metas->_product_id."_".$item->metas->_variation_id][]=$item;
						}
					}
				}
			}
			$combine_order_item_ids=array();
			if(is_array($products_to_combine) && is_array($item_id_to_keep))
			{
				foreach($item_id_to_keep as $kep_id => $order_item_id)
				{
					foreach($products_to_combine as $combine_id => $all_other_products)
					{
						if($kep_id==$combine_id)
						{
							foreach($order_items as $item)
							{
								if(isset($item->metas->_product_id) && isset($item->metas->_qty) && isset($item->metas->_variation_id) && $kep_id==$item->metas->_product_id."_".$item->metas->_variation_id && $order_item_id==$item->order_item_id)
								{
									if(is_array($all_other_products))
									{
										foreach($all_other_products as $other_product)
										{
											$item->metas->_qty+=$other_product->metas->_qty;
											$item->metas->_line_subtotal+=$other_product->metas->_line_subtotal;
											$item->metas->_line_subtotal_tax+=$other_product->metas->_line_subtotal_tax;
											$item->metas->_line_total+=$other_product->metas->_line_total;
											$item->metas->_line_tax+=$other_product->metas->_line_tax;
											if(!isset($combine_order_item_ids[$order_item_id]))
												$combine_order_item_ids[$order_item_id]=array();
											$combine_order_item_ids[$order_item_id][]=$other_product->order_item_id;
										}
									}
									$item->metas->_line_subtotal=number_format(round($item->metas->_line_subtotal,2),2,".","");
									$item->metas->_line_subtotal_tax=number_format(round($item->metas->_line_subtotal_tax,2),2,".","");
									$item->metas->_line_total=number_format(round($item->metas->_line_total,2),2,".","");
									$item->metas->_line_tax=number_format(round($item->metas->_line_tax,2),2,".","");
									$item->metas->_line_tax_data=serialize(array('total'=>array('1'=>(string)$item->metas->_line_tax),'subtotal'=>array('1'=>(string)$item->metas->_line_subtotal_tax)));
								}
							}
						}
					}
				}
			}
			if(count($combine_order_item_ids)>0)
			{
				foreach($combine_order_item_ids as $item_id => $other_item_ids)
				{
					if(is_array($other_item_ids))
					{
						foreach($other_item_ids as $other_item_id)
						{
							$sql="update ".$wpdb->prefix."woocommerce_order_itemmeta set meta_value='".esc_sql($item_id)."' where meta_value='".esc_sql($other_item_id)."' and meta_key='_refunded_item_id'";
							$wpdb->query($sql);
						}
					}
				}
			}
			if(is_array($item_id_to_keep) && is_array($products_to_combine) && is_array($order_items))
			{
				foreach($order_items as $item)
				{
					foreach($item_id_to_keep as $item_id_variation => $order_item_id)
					{
						if($item->order_item_id==$order_item_id)
						{
							foreach($item->metas as $meta_key => $meta_value)
							{
								$sql="update ".$wpdb->prefix."woocommerce_order_itemmeta set meta_value='".esc_sql($meta_value)."' where order_item_id='".esc_sql($order_item_id)."' and meta_key='".esc_sql($meta_key)."'";
								$wpdb->query($sql);
							}
						}
					}
				}
				foreach($products_to_combine as $item_id_variation => $other_products)
				{
					if(is_array($other_products))
					{
						foreach($other_products as $other_product)
						{
							$sql="delete from ".$wpdb->prefix."woocommerce_order_itemmeta where order_item_id='".esc_sql($other_product->order_item_id)."'";
							$wpdb->query($sql);
							$sql="delete from ".$wpdb->prefix."woocommerce_order_items where order_item_id='".esc_sql($other_product->order_item_id)."'";
							$wpdb->query($sql);
							$sql="update ".$wpdb->prefix."woocommerce_order_itemmeta set meta_value=";
						}
					}
				}
			}
			$order = wc_get_order($order_id);
			$order->calculate_taxes();
		    $order->calculate_totals();
		    $order->save();
		    $order_items=$this->getOrderItems($order_id);
			$product_names=array();
			foreach($order_items as $prod)
			{
				if(isset($prod->metas->_qty) && $prod->metas->_qty>0)
					$product_names[$prod->order_item_name]=$prod->metas->_qty;
			}
			foreach($order_items as $prod)
			{
				if($prod->order_item_type=='shipping' && isset($prod->metas->Items) && count($product_names)>0)
				{
					$tmp=array();
					foreach($product_names as $prod_name => $prod_qty)
						$tmp[]=$prod_name.' &times; '.$prod_qty;
					$prod->metas->Items=implode(', ',$tmp);
					$sql="update ".$wpdb->prefix."woocommerce_order_itemmeta set meta_value='".esc_sql($prod->metas->Items)."' where order_item_id='".esc_sql($prod->order_item_id)."' and meta_key='Items'";
					$wpdb->query($sql);
				}
			}
		}
	}
	function set_setup_step()
	{
		global $wpdb;
		$sql="update ".$wpdb->prefix."honeybadger_config set config_value='0' where config_name='setup_step'";
		$wpdb->query($sql);echo $sql;
	}
	function unlinkAttachmentFileDuplicate($path="")
	{
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if($path!="")
		{
			$file=basename($path);
			$new_path=str_ireplace($file,"",$path);
			$duplicates="*_".$this->removeMd5FromFilename($file);
			$new_path=$new_path.$duplicates;
			array_map('unlink', glob($new_path));
		}
	}
}

?>