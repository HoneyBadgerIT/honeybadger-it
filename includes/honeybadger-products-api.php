<?php
/**
 * @package    Honeybadger_IT
 * @subpackage Honeybadger_IT/admin
 * @author     Claudiu Maftei <claudiu@honeybadger.it>
 */
class honeybadgerProductsAPI{
	public $config;
	public $config_front;
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
	        if(isset($parameters['products_method']) && method_exists($this,$parameters['products_method']))
	        {
	        	$method=$parameters['products_method'];
	        	return $this->$method($request);       
	        }
    	}
    	return array();
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
	function search_customer($request)
	{
		global $wpdb;
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$term=isset($parameters['customer'])?$parameters['customer']:"";
			if($term!="")
			{
				$limit = 0;
				$ids = array();
				if ( is_numeric( $term ) )
				{
					$customer = new WC_Customer( intval( $term ) );
					if ( 0 !== $customer->get_id() )
					{
						$ids = array( $customer->get_id() );
					}
				}
				if ( empty( $ids ) )
				{
					$data_store = WC_Data_Store::load( 'customer' );
					if ( 3 > strlen( $term ) )
					{
						$limit = 20;
					}
					$ids = $data_store->search_customers( $term, $limit );
				}
				$found_customers = array();
				foreach ( $ids as $id ) 
				{
					$customer = new WC_Customer( $id );
					$found_customers[ $id ] = sprintf(
						esc_html__( '%1$s (#%2$s &ndash; %3$s)', 'woocommerce' ),
						$customer->get_first_name() . ' ' . $customer->get_last_name(),
						$customer->get_id(),
						$customer->get_email()
					);
				}
				return $this->returnOk($found_customers);
			}
		}
		return $this->returnError(array(__("no records","honeyb")));
	}
	function get_products_by_ids($request)
	{
		global $wpdb;
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$ids=isset($parameters['ids'])?$parameters['ids']:"";
			$other_action=isset($parameters['other_action'])?$parameters['other_action']:"";
			if(is_array($ids))
			{
				$products=array();
				foreach($ids as $id)
				{
					$product_obj=wc_get_product($id);
					if($product_obj)
					{
						$product=new stdClass;
						$product->id=$product_obj->get_id();
						$product->title=$product_obj->get_name();
						$product->sku=$product_obj->get_sku();
						$product->image='';
						$product_image=wp_get_attachment_image_src( get_post_thumbnail_id( $product->id ), 'thumbnail' );
						if(is_array($product_image) && isset($product_image[0]))
							$product->image=$product_image[0];
						$products[]=$product;
					}
				}
				if($other_action!="")
				{
					$other_action_result=array();
					if($other_action!="" && method_exists($this,$other_action))
					{
						$other_action_result=$this->$other_action($request);
						if(isset($other_action_result[0]->content['msg']))
							$other_action_result=$other_action_result[0]->content['msg'];
					}
					return $this->returnOk(array('products'=>$products,'other_result'=>$other_action_result));
				}
				return $this->returnOk($products);
			}
		}
		return $this->returnError();
	}
	function search_product($request)
	{
		global $wpdb;
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$product_name=isset($parameters['product_name'])?$parameters['product_name']:"";
			if($product_name!="")
			{
				$sql="select ID from ".$wpdb->prefix."posts where post_title like '%".esc_sql($product_name)."%' and (post_type='product' or post_type='product_variation') order by post_title";
				$results=$wpdb->get_results($sql);
				if(is_array($results) && count($results)>0)
				{
					$rate_value=0;
					$WC_Tax=new WC_Tax;
					$rates=$WC_Tax->get_rates();
					$woocommerce_prices_include_tax=get_option('woocommerce_prices_include_tax');
					if(is_array($rates))
					{
						foreach($rates as $rate)
						{
							if(is_array($rate) && isset($rate['rate']))
								$rate_value=(int)$rate['rate'];
						}
					}
					$products=array();
					foreach($results as $result)
					{
						$product_obj=wc_get_product($result->ID);
						$stock_status=$product_obj->get_stock_status();
						if($stock_status=='outofstock')
							continue;
						$product=new stdClass;
						$product->id=$product_obj->get_id();
						$product->parent_id=$product_obj->get_parent_id();
						$product->children=$product_obj->get_children();
						$product->title=$product_obj->get_name();
						$product->price=$product_obj->get_price();
						$product->regular_price=$product_obj->get_regular_price();
						$product->sale_price=$product_obj->get_sale_price();
						$product->sku=$product_obj->get_sku();
						$product->currency=get_woocommerce_currency_symbol(get_woocommerce_currency());
						$product->image='';
						$product->tax=$rate_value;
						$product_image=wp_get_attachment_image_src( get_post_thumbnail_id( $product->id ), 'thumbnail' );
						if(is_array($product_image) && isset($product_image[0]))
							$product->image=$product_image[0];
						if($rate_value>0 && (int)$product->price>0)
						{
							if($woocommerce_prices_include_tax=='no')
							{
								$tax_value=($product->price*$rate_value)/100;
								$product->price=$product->price+$tax_value;
							}
							else
								$product->tax=0;
						}
						if(count($product->children)==0)
							$products[]=$product;
					}
					return $this->returnOk($products);
				}
			}
		}
		return $this->returnError(array(__("no records","honeyb")));
	}
	function set_customer_to_new_order($request)
	{
		global $wpdb;
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$customer_id=isset($parameters['customer_id'])?(int)$parameters['customer_id']:0;
			if($customer_id>0)
			{
				$customer = new WC_Customer($customer_id);
				if($customer)
				{
					$return=new stdClass;
					$return->billing=$customer->get_billing();
					$return->shipping=$customer->get_shipping();
					return $this->returnOk($return);
				}
			}
		}
		return $this->returnError(array(__("no records","honeyb")));
	}
	function get_available_payment_shippment_methods($request)
	{
		global $wpdb,$woocommerce;
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$billing_country=isset($parameters['billing_country'])?$parameters['billing_country']:"";
		    $billing_state=isset($parameters['billing_state'])?$parameters['billing_state']:"";
		    $billing_postcode=isset($parameters['billing_postcode'])?$parameters['billing_postcode']:"";
		    $billing_city=isset($parameters['billing_city'])?$parameters['billing_city']:"";
		    $shipping_country=isset($parameters['shipping_country'])?$parameters['shipping_country']:"";
		    $shipping_state=isset($parameters['shipping_state'])?$parameters['shipping_state']:"";
		    $shipping_postcode=isset($parameters['shipping_postcode'])?$parameters['shipping_postcode']:"";
		    $shipping_city=isset($parameters['shipping_city'])?$parameters['shipping_city']:"";
		    $products=isset($parameters['products'])?$parameters['products']:array();
		    $product_parents=isset($parameters['product_parents'])?$parameters['product_parents']:array();
		    $qtys=isset($parameters['qtys'])?$parameters['qtys']:array();
		    $billing_address_1=isset($parameters['billing_address_1'])?$parameters['billing_address_1']:"";
		    $billing_address_2=isset($parameters['billing_address_2'])?$parameters['billing_address_2']:"";
		    $shipping_address_1=isset($parameters['shipping_address_1'])?$parameters['shipping_address_1']:"";
		    $shipping_address_2=isset($parameters['shipping_address_2'])?$parameters['shipping_address_2']:"";
		    $same_as_billing=isset($parameters['same_as_billing'])?$parameters['same_as_billing']:false;
		    if($billing_country!="" && $billing_postcode!="" && $billing_city!="" && is_array($products) && is_array($product_parents) && count($products)>0 && count($products)==count($product_parents) && is_array($qtys) && count($qtys)==count($products))
	    	{
	    		if($same_as_billing=="true")
	    		{
	    			$shipping_country=$billing_country;
	    			$shipping_state=$billing_state;
	    			$shipping_postcode=$billing_postcode;
	    			$shipping_city=$billing_city;
	    			$shipping_address_1=$billing_address_1;
	    			$shipping_address_2=$billing_address_2;
	    		}
	    		$the_products=array();
	    		for($i=0;$i<count($products);$i++)
	    		{
	    			$product=new stdClass;
	    			$product->id=0;
	    			$product->variation_id=0;
	    			$product->qty=$qtys[$i];
	    			if($product_parents[$i]>0)
	    			{
	    				$product->id=$product_parents[$i];
	    				$product->variation_id=$products[$i];
	    			}
	    			else
	    				$product->id=$products[$i];
	    			$the_products[]=$product;
	    		}
	    		$woocommerce->init();
		        $woocommerce->frontend_includes();
		        $session_class = apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' );
		        require_once WC()->plugin_path() .'/includes/abstracts/abstract-wc-session.php';
		        $woocommerce->session  = new WC_Session_Handler();
		        $woocommerce->cart     = new WC_Cart();
		        $woocommerce->customer = new WC_Customer();
		        $woocommerce->countries = new WC_Countries();
		        $woocommerce->checkout = new WC_Checkout();
		        $woocommerce->order_factory   = new WC_Order_Factory();
		        $woocommerce->integrations    = new WC_Integrations();
		        $rate_value=0;
				$woocommerce->tax=new WC_Tax();
		        if (! defined('WOOCOMMERCE_CHECKOUT')) define('WOOCOMMERCE_CHECKOUT', true);
		        $woocommerce->cart->empty_cart();
		        foreach($the_products as $the_product)
		        	$woocommerce->cart->add_to_cart( $the_product->id, $the_product->qty, $the_product->variation_id );
		        $woocommerce->customer->set_props(
					array(
						'billing_country'   => $billing_country,
						'billing_state'     => $billing_state,
						'billing_postcode'  => $billing_postcode,
						'billing_city'      => $billing_city,
						'billing_address_1' => $billing_address_1,
						'billing_address_2' => $billing_address_2
					)
				);
				if ( wc_ship_to_billing_address_only() ) {
					$woocommerce->customer->set_props(
						array(
							'shipping_country'   => $billing_country,
							'shipping_state'     => $billing_state,
							'shipping_postcode'  => $billing_postcode,
							'shipping_city'      => $billing_city,
							'shipping_address_1' => $billing_address_1,
							'shipping_address_2' => $billing_address_2
						)
					);
				} else {
					$woocommerce->customer->set_props(
						array(
							'shipping_country'   => $shipping_country,
							'shipping_state'     => $shipping_state,
							'shipping_postcode'  => $shipping_postcode,
							'shipping_city'      => $shipping_city,
							'shipping_address_1' => $shipping_address_1,
							'shipping_address_2' => $shipping_address_2
						)
					);
				}
		        $woocommerce->customer->set_calculated_shipping(false);
		        $woocommerce->cart->calculate_shipping();
            	$woocommerce->cart->calculate_totals();
	    		$payment_methods_objs=$woocommerce->payment_gateways()->get_available_payment_gateways();
	    		$shipping_methods=array();
	    		$shipping_methods_objs=$woocommerce->shipping->get_shipping_methods();
	    		$rates=$woocommerce->tax->get_rates();
				$woocommerce_prices_include_tax=get_option('woocommerce_prices_include_tax');
				if(is_array($rates) && $woocommerce_prices_include_tax=='no')
				{
					foreach($rates as $rate)
					{
						if(is_array($rate) && isset($rate['rate']))
							$rate_value=(int)$rate['rate'];
					}
				}
	    		$unique_shipping_ids=array();
	    		if(is_array($shipping_methods_objs))
	    		{
	    			foreach($shipping_methods_objs as $shipping_methods_slug => $shipping_methods_obj)
	    			{
	    				if(is_array($shipping_methods_obj->rates) && count($shipping_methods_obj->rates)>0)
	    				{
	    					foreach($shipping_methods_obj->rates as $rate)
	    					{
	    						$tmp_tax=$rate->get_shipping_tax();
	    						$tmp_shipping=new stdClass;
			    				$tmp_shipping->id=$rate->get_id();
			    				$tmp_shipping->title=$rate->get_label();
			    				$tmp_shipping->cost=$rate->get_cost();
			    				$tmp_shipping->method_id=$rate->get_method_id();
			    				if($tmp_tax>0)
			    				{
			    					$tmp_shipping->cost=round(($tmp_shipping->cost+$tmp_tax),2);
			    				}
			    				if(!in_array($tmp_shipping->method_id,$unique_shipping_ids))
			    				{
				    				$shipping_methods[]=$tmp_shipping;
				    				$unique_shipping_ids[]=$tmp_shipping->method_id;
			    				}
	    					}
	    				}
	    				else
	    				{
	    					$tmp_shipping=new stdClass;
		    				$tmp_shipping->id=$shipping_methods_obj->id;
		    				$tmp_shipping->title=$shipping_methods_obj->get_method_title();
		    				$tmp_shipping->cost=0;
		    				$tmp_shipping->method_id=$shipping_methods_obj->id;
		    				if(!in_array($tmp_shipping->method_id,$unique_shipping_ids))
		    				{
		    					$shipping_methods[]=$tmp_shipping;
		    					$unique_shipping_ids[]=$tmp_shipping->method_id;
		    				}
    					}
	    			}
	    		}
	    		$payment_methods=array();
	    		if(is_array($payment_methods_objs))
	    		{
	    			foreach($payment_methods_objs as $payment_method_slug => $payment_methods_obj)
	    			{
	    				$payment_methods[$payment_method_slug]=$payment_methods_obj->get_title();
	    			}
	    		}
	    		$held_duration = get_option( 'woocommerce_hold_stock_minutes' );
	    		$cancel_unpaid_interval = apply_filters( 'woocommerce_cancel_unpaid_orders_interval_minutes', absint( $held_duration ) );
	    		$return=new stdClass;
				$return->payment_methods=$payment_methods;
				$return->shipping_methods=$shipping_methods;
				$return->wc_tax=$rate_value;
				$return->hold_stock_minutes=$cancel_unpaid_interval;
				$return->currency=get_woocommerce_currency_symbol(get_woocommerce_currency());
				$woocommerce->cart->empty_cart();
				return $this->returnOk($return);
	    	}
	    	else
	    		return $this->returnError(array('error'));
		}
		return $this->returnError(array('error'));
	}
	function check_if_email_exists_for_new_order($request)
	{
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$email=isset($parameters['email'])?$parameters['email']:"";
			$return=new stdClass;
			$return->email_ok=0;
			$return->email_exists=0;
			if($email!="" && filter_var($email, FILTER_VALIDATE_EMAIL))
			{
				$return->email_ok=1;
				if(email_exists($email))
					$return->email_exists=1;
			}
			return $this->returnOk($return);
		}
	}
	function create_new_order($request)
	{
		global $wpdb,$woocommerce;
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$data=isset($parameters['data'])?$parameters['data']:array();
			$the_products=array();
			if(is_array($data) && count($data)>0)
			{
	    		for($i=0;$i<count($data['order_product_ids_added']);$i++)
	    		{
	    			$product=new stdClass;
	    			$product->id=0;
	    			$product->variation_id=0;
	    			$product->qty=$data['order_product_qty'][$i];
	    			if($data['order_product_parent_ids_added'][$i]>0)
	    			{
	    				$product->id=$data['order_product_parent_ids_added'][$i];
	    				$product->variation_id=$data['order_product_ids_added'][$i];
	    			}
	    			else
	    				$product->id=$data['order_product_ids_added'][$i];
	    			$the_products[]=$product;
	    		}
			}
			if($data['same_as_billing_checkbox_1']==1)
			{
				$data['shipping_first_name']=$data['billing_first_name'];
				$data['shipping_last_name']=$data['billing_last_name'];
				$data['shipping_company']=$data['billing_company'];
				$data['shipping_address_1']=$data['billing_address_1'];
				$data['shipping_address_2']=$data['billing_address_2'];
				$data['shipping_city']=$data['billing_city'];
				$data['shipping_postcode']=$data['billing_postcode'];
				$data['shipping_country']=$data['billing_country'];
				$data['shipping_state']=$data['billing_state'];
				$data['billing_phone']=$data['billing_phone'];
			}
			$new_data=array(
				'terms' => 1,
				'createaccount' => $data['order_create_account'],
				'payment_method' => $data['order_selected_payment'],
				'shipping_method' => array($data['order_selected_shipment']),
				'ship_to_different_address' => $data['same_as_billing_checkbox_1'],
				'woocommerce_checkout_update_totals' => '',
				'billing_first_name' => $data['billing_first_name'],
				'billing_last_name' => $data['billing_last_name'],
				'billing_company' => $data['billing_company'],
				'billing_country' => $data['billing_country'],
				'billing_address_1' => $data['billing_address_1'],
				'billing_address_2' => $data['billing_address_2'],
				'billing_city' => $data['billing_city'],
				'billing_state' => $data['billing_state'],
				'billing_postcode' => $data['billing_postcode'],
				'billing_phone' => $data['billing_phone'],
				'billing_email' => $data['billing_email'],
				'shipping_first_name' => $data['shipping_first_name'],
				'shipping_last_name' => $data['shipping_last_name'],
				'shipping_company' => $data['shipping_company'],
				'shipping_country' => $data['shipping_country'],
				'shipping_address_1' => $data['shipping_address_1'],
				'shipping_address_2' => $data['shipping_address_2'],
				'shipping_city' => $data['shipping_city'],
				'shipping_state' => $data['shipping_state'],
				'shipping_postcode' => $data['shipping_postcode'],
				'order_comments' => $data['customer_note']
			);
			$user_id = get_current_user_id();
			$userId=0;
			if($data['order_create_account']==1)
			{
				if(email_exists($data['billing_email']))
				{
					$user = get_user_by( 'email', $data['billing_email'] );
					$userId = $user->ID;
					wp_set_current_user( $userId );
				}
				else
					wp_set_current_user( 0 );
			}
			else
				wp_set_current_user( 0 );
			$woocommerce->init();
	        $woocommerce->frontend_includes();
	        $session_class = apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' );
	        require_once WC()->plugin_path() .'/includes/abstracts/abstract-wc-session.php';
	        $woocommerce->session  = new WC_Session_Handler();
	        $woocommerce->cart     = new WC_Cart();
	        $woocommerce->customer = new WC_Customer();
	        $woocommerce->countries = new WC_Countries();
	        $woocommerce->checkout = new WC_Checkout();
	        $woocommerce->order_factory   = new WC_Order_Factory();
	        $woocommerce->integrations    = new WC_Integrations();
	        $rate_value=0;
			$woocommerce->tax=new WC_Tax();
	        if (! defined('WOOCOMMERCE_CHECKOUT')) define('WOOCOMMERCE_CHECKOUT', true);
	        $woocommerce->cart->empty_cart();
	        foreach($the_products as $the_product)
	        	$woocommerce->cart->add_to_cart( $the_product->id, $the_product->qty, $the_product->variation_id );
	        $woocommerce->customer->set_props(
				array(
					'billing_country'   => $data['billing_country'],
					'billing_state'     => $data['billing_state'],
					'billing_postcode'  => $data['billing_postcode'],
					'billing_city'      => $data['billing_city'],
					'billing_address_1' => $data['billing_address_1'],
					'billing_address_2' => $data['billing_address_2']
				)
			);
			if ( wc_ship_to_billing_address_only() ) {
				$woocommerce->customer->set_props(
					array(
						'shipping_country'   => $data['billing_country'],
						'shipping_state'     => $data['billing_state'],
						'shipping_postcode'  => $data['billing_postcode'],
						'shipping_city'      => $data['billing_city'],
						'shipping_address_1' => $data['billing_address_1'],
						'shipping_address_2' => $data['billing_address_2']
					)
				);
			} else {
				$woocommerce->customer->set_props(
					array(
						'shipping_country'   => $data['shipping_country'],
						'shipping_state'     => $data['shipping_state'],
						'shipping_postcode'  => $data['shipping_postcode'],
						'shipping_city'      => $data['shipping_city'],
						'shipping_address_1' => $data['shipping_address_1'],
						'shipping_address_2' => $data['shipping_address_2']
					)
				);
			}
	        $woocommerce->customer->set_calculated_shipping(false);
	        $woocommerce->cart->calculate_shipping();
        	$woocommerce->cart->calculate_totals();
			$checkout = new WC_Checkout();
			$order_id=$checkout->create_order($new_data);
			if(is_int($order_id))
			{
				if($data['transaction_id']!=="")
					update_post_meta($order_id,'_transaction_id',$data['transaction_id']);
				if($new_data['createaccount']==1)
				{
					if($userId==0)
					{
						$username    = ! empty( $new_data['account_username'] ) ? $new_data['account_username'] : '';
						$password    = ! empty( $new_data['account_password'] ) ? $new_data['account_password'] : '';
						$customer_id = wc_create_new_customer(
							$new_data['billing_email'],
							$username,
							$password,
							array(
								'first_name' => ! empty( $new_data['billing_first_name'] ) ? $new_data['billing_first_name'] : '',
								'last_name'  => ! empty( $new_data['billing_last_name'] ) ? $new_data['billing_last_name'] : '',
							)
						);

						if ( is_wp_error( $customer_id ) ) {
							wp_set_current_user( $user_id );
							$this->returnOk($order_id." ".$customer_id->get_error_message());
						}
						update_post_meta($order_id,'_customer_user',$customer_id);
						$userId=$customer_id;
					}
					$customer=new WC_Customer($userId);
					$customer->set_billing_first_name($data['billing_first_name']);
					$customer->set_billing_last_name($data['billing_last_name']);
					$customer->set_billing_email($data['billing_email']);
					$customer->set_billing_company($data['billing_company']);
					$customer->set_billing_country($data['billing_country']);
					$customer->set_billing_address_1($data['billing_address_1']);
					$customer->set_billing_address_2($data['billing_address_2']);
					$customer->set_billing_city($data['billing_city']);
					$customer->set_billing_state($data['billing_state']);
					$customer->set_billing_postcode($data['billing_postcode']);
					$customer->set_shipping_first_name($data['shipping_first_name']);
					$customer->set_shipping_last_name($data['shipping_last_name']);
					$customer->set_shipping_company($data['shipping_company']);
					$customer->set_shipping_country($data['shipping_country']);
					$customer->set_shipping_address_1($data['shipping_address_1']);
					$customer->set_shipping_address_2($data['shipping_address_2']);
					$customer->set_shipping_city($data['shipping_city']);
					$customer->set_shipping_state($data['shipping_state']);
					$customer->set_shipping_postcode($data['shipping_postcode']);
					$customer->save();
				}
				$mailer = WC()->mailer()->get_emails()['WC_Email_Customer_Invoice'];
				$mailer->trigger( $order_id );
				add_filter( 'woocommerce_new_order_email_allows_resend', '__return_true' );
				$mailer = WC()->mailer()->get_emails()['WC_Email_New_Order'];
				$mailer->trigger( $order_id );
			}
			wp_set_current_user( $user_id );
			return $this->returnOk($order_id);
		}
	}
	function delete_order($request)
	{
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$order_id=isset($parameters['order_id'])?(int)$parameters['order_id']:0;
			if($order_id>0)
			{
				wp_delete_post($order_id);
				return $this->returnOk($order_id);
			}
		}
		return $this->returnError();
	}
	function get_products($request)
	{
		global $wpdb;
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$limit=isset($parameters['limit'])?(int)$parameters['limit']:10;
			$start=isset($parameters['start'])?(int)$parameters['start']:0;
			$search=isset($parameters['search'])?$parameters['search']:"";
			$order_arr=isset($parameters['order'])?$parameters['order']:array();
			$sql="select count(ID) as total from ".$wpdb->prefix."posts where (post_type='product' or post_type='product_variation') and post_status='publish'";
			$result=$wpdb->get_row($sql);
			$total_products=0;
			if(isset($result->total))
				$total_products=$result->total;
			$total_filtered_products=$total_products;
			$order_by=array();
			if(count($order_arr)>0)
			{
				$sortables=array();
				foreach($order_arr as $order)
					$sortables[$order['column']]=$order['dir'];
				foreach($sortables as $col => $order)
				{
					if($col==0 && $order!="")
						$order_by[]="p.ID ".(($order=='asc')?'asc':'desc');
					if($col==1 && $order!="")
						$order_by[]="p.post_title ".(($order=='asc')?'asc':'desc');
				}
			}
			if(count($order_by)==0)
				$order_by[]="p.ID desc";
			if($search!="")
			{
				$sql="select count(p.ID) as total from ".$wpdb->prefix."posts p where (p.post_type='product' or p.post_type='product_variation') and 
				p.post_status='publish' and
				(p.ID like '%".esc_sql($search)."%' or
				p.post_title like '%".esc_sql($search)."%')";
				$result=$wpdb->get_row($sql);
				if(isset($result->total))
					$total_filtered_products=$result->total;

				$sql="select p.ID from ".$wpdb->prefix."posts p where (p.post_type='product' or p.post_type='product_variation') and 
				p.post_status='publish' and
				(p.ID like '%".esc_sql($search)."%' or
				p.post_title like '%".esc_sql($search)."%')
				order by ".implode(",",$order_by)."
				limit ".$start.",".$limit;
			}
			else
			{
				$sql="select p.ID from ".$wpdb->prefix."posts p where (p.post_type='product' or p.post_type='product_variation') and p.post_status='publish'
				order by ".implode(",",$order_by)."
				limit ".$start.",".$limit;
			}
			$results=$wpdb->get_results($sql);
			$products=array();
			if(is_array($results))
			{
				$rate_value=0;
				$WC_Tax=new WC_Tax;
				$rates=$WC_Tax->get_rates();
				$woocommerce_prices_include_tax=get_option('woocommerce_prices_include_tax');
				if(is_array($rates))
				{
					foreach($rates as $rate)
					{
						if(is_array($rate) && isset($rate['rate']))
							$rate_value=(int)$rate['rate'];
					}
				}
				foreach($results as $result)
				{
					$product_obj=wc_get_product($result->ID);
					$product=new stdClass;
					$product->id=$product_obj->get_id();
					$product->parent_id=$product_obj->get_parent_id();
					$product->children=$product_obj->get_children();
					$product->children_prices=array();
					$product->featured=$product_obj->get_featured();
					$product->title=$product_obj->get_name();
					$product->price=$product_obj->get_price();
					$product->regular_price=$product_obj->get_regular_price();
					$product->sale_price=$product_obj->get_sale_price();
					$product->sku=$product_obj->get_sku();
					$product->currency=get_woocommerce_currency_symbol(get_woocommerce_currency());
					$product->image='';
					$product->tax=$rate_value;
					$product->on_order=0;
					$product->manage_stock=$product_obj->get_manage_stock();
					$product->stock=$product_obj->get_stock_quantity();
					$product->stock_status=$product_obj->get_stock_status();
					$product_image=wp_get_attachment_image_src( get_post_thumbnail_id( $product->id ), 'thumbnail' );
					if(is_array($product_image) && isset($product_image[0]))
						$product->image=$product_image[0];
					if($rate_value>0 && (int)$product->price>0)
					{
						if($woocommerce_prices_include_tax=='no')
						{
							$tax_value=($product->price*$rate_value)/100;
							$product->price=$product->price+$tax_value;
						}
						else
							$product->tax=0;
					}
					if(count($product->children)>0)
					{
						foreach($product->children as $sibling)
						{
							$sibling_obj=wc_get_product($sibling);
							$product->children_prices[]=$sibling_obj->get_price();
						}
						if($rate_value>0 && (int)$product->price>0)
						{
							if($woocommerce_prices_include_tax=='no')
							{
								for($i=0;$i<count($product->children_prices);$i++)
								{
									if(is_numeric($product->children_prices[$i]))
									{
										$tax_value=($product->children_prices[$i]*$rate_value)/100;
										$product->children_prices[$i]=$product->children_prices[$i]+$tax_value;
									}
								}
							}
						}
						sort($product->children_prices);
					}
					$products[]=$product;
				}
			}
			return $this->returnOk(array('products'=>$products,'total_products'=>$total_products,'total_filtered_products'=>$total_filtered_products));
		}
		return $this->returnError('error');
	}
	function save_product_details($request)
	{
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$product_id=isset($parameters['product_id'])?(int)$parameters['product_id']:0;
			$parent_id=isset($parameters['parent_id'])?(int)$parameters['parent_id']:0;
			$regular_price=isset($parameters['regular_price']) && $parameters['regular_price']!=""?$parameters['regular_price']:"";
		    $sale_price=isset($parameters['sale_price']) && $parameters['sale_price']!=""?$parameters['sale_price']:"";
		    $sku=isset($parameters['sku'])?$parameters['sku']:"";
		    $manage_stock=isset($parameters['manage_stock'])?$parameters['manage_stock']:"";
		    $stock_1=isset($parameters['stock_1'])?$parameters['stock_1']:"";
		    $stock_2=isset($parameters['stock_2'])?$parameters['stock_2']:"";
		    $stock_low=isset($parameters['stock_low']) && $parameters['stock_low']!=""?$parameters['stock_low']:"";
		    $short_description=isset($parameters['short_description'])?$parameters['short_description']:"";
		    if($parent_id>0)
		    	wp_update_post(array('ID' => $parent_id, 'post_excerpt' => $short_description ));
		    else
		    	wp_update_post(array('ID' => $product_id, 'post_excerpt' => $short_description ));
		    update_post_meta($product_id,"_low_stock_amount",$stock_low);
	    	update_post_meta($product_id,"_regular_price",$regular_price);
		    update_post_meta($product_id,"_price",$sale_price);
			update_post_meta($product_id,"_sale_price",$sale_price);
			
			if($regular_price==0 && $sale_price>0)
		    {
		    	update_post_meta($product_id,"_regular_price",$sale_price);
		    	update_post_meta($product_id,"_price",$sale_price);
		    	update_post_meta($product_id,"_sale_price",$sale_price);
		    }
			if($regular_price>0 && $sale_price==0)
		    {
		    	update_post_meta($product_id,"_regular_price",$regular_price);
		    	update_post_meta($product_id,"_price",$regular_price);
		    	delete_post_meta($product_id,"_sale_price");
		    }
		    if($regular_price!="" && $sale_price!="" && $regular_price==0 && $sale_price==0)
		    {
		    	update_post_meta($product_id,"_regular_price",0);
		    	update_post_meta($product_id,"_price",0);
		    	delete_post_meta($product_id,"_sale_price");
		    }
			if($manage_stock=='on')
		    {
		    	update_post_meta($product_id,"_manage_stock","yes");
		    	update_post_meta($product_id,"_stock_status","instock");
		    	update_post_meta($product_id,"_stock",(int)$stock_2);
		    }
		    else
		    {
		    	update_post_meta($product_id,"_manage_stock","no");
		    	update_post_meta($product_id,"_stock_status",$stock_1);
		    	update_post_meta($product_id,"_stock",0);
		    }
		    $product_obj=wc_get_product($product_id);
		    if($product_obj)
		    {
		    	try{
			    	$product_obj->set_sku($sku);
				}
				catch (WC_Data_Exception $e){
					return $this->returnError($e->getMessage());
				}
			    $rate_value=0;
				$WC_Tax=new WC_Tax;
				$rates=$WC_Tax->get_rates();
				$woocommerce_prices_include_tax=get_option('woocommerce_prices_include_tax');
				if(is_array($rates))
				{
					foreach($rates as $rate)
					{
						if(is_array($rate) && isset($rate['rate']))
							$rate_value=(int)$rate['rate'];
					}
				}
				$product_price=$product_obj->get_price();
				if($rate_value>0 && (int)$product_price>0)
				{
					if($woocommerce_prices_include_tax=='no')
					{
						$tax_value=($product_price*$rate_value)/100;
						$product_price=$product_price+$tax_value;
					}
				}
				return $this->returnOk(array('product_price'=>$product_price));
			}
		}
		return $this->returnError();
	}
	function get_product_details($request)
	{
		global $wpdb;
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$product_id=isset($parameters['product_id'])?(int)$parameters['product_id']:0;
			$main_product_id=isset($parameters['main_product_id'])?(int)$parameters['main_product_id']:0;
			if($product_id>0)
			{
				$rate_value=0;
				$WC_Tax=new WC_Tax;
				$rates=$WC_Tax->get_rates();
				$woocommerce_prices_include_tax=get_option('woocommerce_prices_include_tax');
				if(is_array($rates))
				{
					foreach($rates as $rate)
					{
						if(is_array($rate) && isset($rate['rate']))
							$rate_value=(int)$rate['rate'];
					}
				}
				$product_obj=wc_get_product($product_id);
				$img_ids=$product_obj->get_gallery_image_ids();
				$product=new stdClass;
				$product->id=$product_obj->get_id();
				$product->parent_id=$product_obj->get_parent_id();
				$product->featured=$product_obj->get_featured();
				$product->title=$product_obj->get_name();
				$product->product_type='';
				$product->url=get_permalink($product_id);
				if($product->parent_id>0)
				{
					$parent_obj=wc_get_product($product->parent_id);
					$product->description=$parent_obj->get_short_description();
				}
				else
					$product->description=$product_obj->get_short_description();
				$product->siblings=$product_obj->get_children();
				if(is_array($product->siblings) && count($product->siblings)==0 && $product_id!=$main_product_id)
				{
					$sql="select meta_value from ".$wpdb->prefix."postmeta where meta_key='_children' and post_id='".esc_sql($main_product_id)."' and meta_value like '%i:".esc_sql($product_id)."%'";
					$result=$wpdb->get_row($sql);
					if(isset($result->meta_value) && $result->meta_value!="")
					{
						$children = unserialize($result->meta_value);
						if(is_array($children) && count($children)>0)
						{
							$product->parent_id=$main_product_id;
							$product->siblings=$children;
							$product->product_type='grouped';
						}
					}
				}
				$product->main_product_title=$product->title;
				if($product->parent_id>0)
				{
					$parent_obj=wc_get_product($product->parent_id);
					$product->siblings=$parent_obj->get_children();
					$product->main_product_title=$parent_obj->get_name();
				}
				$product->siblings_details=array();
				$product->price=$product_obj->get_price();
				$product->regular_price=$product_obj->get_regular_price();
				$product->sale_price=$product_obj->get_sale_price();
				$product->sku=$product_obj->get_sku();
				$product->currency=get_woocommerce_currency_symbol(get_woocommerce_currency());
				$product->image='';
				$product->tax=$rate_value;
				$product->on_order=0;
				$product->manage_stock=$product_obj->get_manage_stock();
				$product->woocommerce_notify_low_stock_amount=get_option("woocommerce_notify_low_stock_amount");
				$product->stock_low=$product_obj->get_low_stock_amount();
				$product->stock=$product_obj->get_stock_quantity();
				$product->stock_status=$product_obj->get_stock_status();
				$product->image_id=get_post_thumbnail_id( $product->id );
				$product_image=wp_get_attachment_image_src( $product->image_id, 'medium' );
				if(is_array($product_image) && isset($product_image[0]))
					$product->image=$product_image[0];
				$product->gallery=array();
				if(is_array($img_ids) && count($img_ids)>0)
				{
					$posts = array_map( 'wp_prepare_attachment_for_js', $img_ids );
					$images=array();
					if(is_array($posts))
					{
						foreach($posts as $post)
						{
							if(isset($post['sizes']) && isset($post['sizes']['medium']) && isset($post['sizes']['medium']['url']))
								$images[]=array('url'=>$post['sizes']['medium']['url'],'id'=>$post['id'],'title'=>$post['title']);
							else if(isset($post['sizes']) && isset($post['sizes']['thumbnail']) && isset($post['sizes']['thumbnail']['url']))
								$images[]=array('url'=>$post['sizes']['thumbnail']['url'],'id'=>$post['id'],'title'=>$post['title']);
							else if(isset($post['sizes']) && isset($post['sizes']['full']) && isset($post['sizes']['full']['url']))
								$images[]=array('url'=>$post['sizes']['full']['url'],'id'=>$post['id'],'title'=>$post['title']);
						}
					}
					$product->gallery=$images;
				}
				$product->enable_product_variation_extra_images=$this->config->enable_product_variation_extra_images;
				if($rate_value>0 && (int)$product->price>0)
				{
					if($woocommerce_prices_include_tax=='no')
					{
						$tax_value=($product->price*$rate_value)/100;
						$product->price=$product->price+$tax_value;
					}
					else
						$product->tax=0;
				}
				if(is_array($product->siblings))
				{
					foreach($product->siblings as $sibling_id)
					{
						$sibling_obj=wc_get_product($sibling_id);
						$sibling=new stdClass;
						$sibling->id=$sibling_obj->get_id();
						$sibling->title=$sibling_obj->get_name();
						$product->siblings_details[]=$sibling;
					}
				}
				return $this->returnOk($product);
			}
		}
		return $this->returnError();
	}
	function get_product_title($request)
	{
		global $wpdb;
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$product_id=isset($parameters['product_id'])?(int)$parameters['product_id']:0;
			if($product_id>0)
			{
				$product="";
				$sql="select post_title from ".$wpdb->prefix."posts where ID='".esc_sql($product_id)."'";
				$result=$wpdb->get_row($sql);
				if(isset($result->post_title))
					$product=$result->post_title;
				return $this->returnOk($product);
			}
		}
		return $this->returnError();
	}
	function get_media_gallery($request)
	{
		global $wpdb;
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$paged=isset($parameters['paged'])?$parameters['paged']+0:1;
			$search=isset($parameters['search'])?$parameters['search']:"";
			if($paged>0)
			{
				$start=0;
				$end=20;
				if($paged>1)
					$start=(20*$paged) - 20;
				$total=0;
				if($search!="")
				{
					$sql="SELECT count(*) as total from ".$wpdb->prefix."posts p WHERE p.post_mime_type LIKE 'image/%'
					and (
					p.post_title like '%".esc_sql($search)."%' or
					p.post_content like '%".esc_sql($search)."%' or
					p.post_excerpt like '%".esc_sql($search)."%'
					)";
				}
				else
					$sql="SELECT count(*) as total from ".$wpdb->prefix."posts WHERE ".$wpdb->prefix."posts.post_mime_type LIKE 'image/%'";
				$result=$wpdb->get_row($sql);
				if(isset($result->total))
					$total=$result->total;
				if($search!="")
				{
					$sql="SELECT p.ID, p.post_title
					FROM ".$wpdb->prefix."posts p
					WHERE p.post_mime_type LIKE 'image/%' and (
					p.post_title like '%".esc_sql($search)."%' or
					p.post_content like '%".esc_sql($search)."%' or
					p.post_excerpt like '%".esc_sql($search)."%'
					)
					ORDER BY p.post_date DESC
					LIMIT ".$start.", ".$end;
				}
				else
				{
					$sql="SELECT p.ID, p.post_title
					FROM ".$wpdb->prefix."posts p
					WHERE p.post_mime_type LIKE 'image/%'
					ORDER BY p.post_date DESC
					LIMIT ".$start.", ".$end;
				}
				$posts=$wpdb->get_results($sql);
				$posts_ids=array();
				if(is_array($posts))
				{
					foreach($posts as $post)
						$posts_ids[]=$post->ID;
				}
				$posts = array_map( 'wp_prepare_attachment_for_js', $posts_ids );
				$images=array();
				if(is_array($posts))
				{
					foreach($posts as $post)
					{
						if(isset($post['sizes']) && isset($post['sizes']['medium']) && isset($post['sizes']['medium']['url']))
							$images[]=array('url'=>$post['sizes']['medium']['url'],'id'=>$post['id'],'title'=>$post['title']);
						else if(isset($post['sizes']) && isset($post['sizes']['thumbnail']) && isset($post['sizes']['thumbnail']['url']))
							$images[]=array('url'=>$post['sizes']['thumbnail']['url'],'id'=>$post['id'],'title'=>$post['title']);
						else if(isset($post['sizes']) && isset($post['sizes']['full']) && isset($post['sizes']['full']['url']))
							$images[]=array('url'=>$post['sizes']['full']['url'],'id'=>$post['id'],'title'=>$post['title']);
					}
				}
				return $this->returnOk(array('images'=>$images,'total'=>$total));
			}
		}
		return $this->returnError();
	}
	function remove_product_image($request)
	{
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$product_id=isset($parameters['product_id'])?(int)$parameters['product_id']:0;
			if($product_id>0)
			{
				update_post_meta($product_id,"_thumbnail_id","");
				return $this->returnOk("ok");
			}
		}
		return $this->returnError();
	}
	function save_main_product_image($request)
	{
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$product_id=isset($parameters['product_id'])?(int)$parameters['product_id']:0;
			$image_id=isset($parameters['image_id'])?(int)$parameters['image_id']:0;
			if($product_id>0 && $image_id>0)
			{
				update_post_meta($product_id,"_thumbnail_id",$image_id);
				return $this->returnOk("ok");
			}
		}
		return $this->returnError();
	}
	function save_product_image_gallery($request)
	{
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$product_id=isset($parameters['product_id'])?(int)$parameters['product_id']:0;
			$gallery_imgs=isset($parameters['gallery_imgs'])?$parameters['gallery_imgs']:array();
			if($product_id>0 && is_array($gallery_imgs))
			{
				$product_obj=wc_get_product($product_id);
				$product_obj->set_gallery_image_ids($gallery_imgs);
				$product_obj->save();
				return $this->returnOk("ok");
			}
		}
		return $this->returnError();
	}
	function update_product_stock($request)
	{
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$product_id=isset($parameters['product_id'])?(int)$parameters['product_id']:0;
			$operation=isset($parameters['operation'])?$parameters['operation']:"";
			$qty=isset($parameters['qty'])?(int)$parameters['qty']:0;
			if($product_id>0 && $qty>0 && ($operation=="+") || $operation=="-")
			{
				$product_obj=wc_get_product($product_id);
				$stock=(int)$product_obj->get_stock_quantity();
				if($operation=="+")
					$stock=$stock+$qty;
				if($operation=="-")
					$stock=$stock-$qty;
				if($stock>0)
				{
					update_post_meta($product_id,"_manage_stock","yes");
			    	update_post_meta($product_id,"_stock_status","instock");
			    	update_post_meta($product_id,"_stock",(int)$stock);
				}
				else if($stock<=0)
				{
					update_post_meta($product_id,"_manage_stock","yes");
			    	update_post_meta($product_id,"_stock_status","outofstock");
			    	update_post_meta($product_id,"_stock",(int)$stock);
				}
				else
				{
					$stock_status=$product_obj->get_stock_status();
					$manage_stock=$product_obj->get_manage_stock();
					if($stock_status=='instock' && ($manage_stock=='yes' || $manage_stock==''))
					{
						//do not update
					}
					else
					{
						update_post_meta($product_id,"_manage_stock","no");
				    	update_post_meta($product_id,"_stock_status","outofstock");
				    	update_post_meta($product_id,"_stock",0);
			    	}
				}
				return $this->returnOk("ok");
			}
		}
		return $this->returnError();
	}
	function get_so_actions($request)
	{
		global $wpdb;
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$order_state=isset($parameters['order_state'])?$parameters['order_state']:"";
			$order_status="";
			if($order_state!="")
				$order_status=" and so_states like'%".esc_sql($order_state)."%'";
			$sql="select id, title, so_states from ".$wpdb->prefix."honeybadger_emails where enabled=1 and so_states<>''".$order_status." order by id";
			$email_actions=$wpdb->get_results($sql);
			$sql="select id, title from ".$wpdb->prefix."honeybadger_attachments where so_generable=1 and enabled=1 order by id";
			$generable_attachments=$wpdb->get_results($sql);
			$email_attachments=$this->get_so_email_attachments($request);
			$static_attachments=$this->get_so_email_static_attachments($request);
			return $this->returnOk(array('email_actions'=>$email_actions,'generable_attachments'=>$generable_attachments,'email_attachments'=>$email_attachments,'static_attachments'=>$static_attachments));
		}
		return $this->returnError();
	}
	function get_so_email_attachments($request)
	{
		global $wpdb;
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$status=isset($parameters['order_state'])?$parameters['order_state']:"";
			if($status!="")
			{
				$filter_attachments=array();
				$email_ids=array();
				$sql="select id from ".$wpdb->prefix."honeybadger_emails where so_states like '%".$status."%' and enabled=1";
				$results=$wpdb->get_results($sql);
				if(is_array($results))
				{
					foreach($results as $result)
						$email_ids[]=$result->id;
				}
				if(count($email_ids)>0)
				{
					foreach($email_ids as $email_id)
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
					if(count($filter_attachments))
					{
						$filter_attachments=array_unique($filter_attachments);
						$sql="select id, title, attach_to_emails, generable from ".$wpdb->prefix."honeybadger_attachments where id in (".implode(",",$filter_attachments).") and enabled=1";
						return $wpdb->get_results($sql);
					}
				}
			}
		}
		return array();
	}
	function get_so_email_static_attachments($request)
	{
		global $wpdb;
		if(!empty($request))
		{
			$filter_sql="";
			$parameters = $request->get_params();
			$status=isset($parameters['order_state'])?$parameters['order_state']:"";
			if($status!="")
			{
				$filter_attachments=array();
				$email_ids=array();
				$sql="select id from ".$wpdb->prefix."honeybadger_emails where so_states like '%".$status."%' and enabled=1";
				$results=$wpdb->get_results($sql);
				if(is_array($results))
				{
					foreach($results as $result)
						$email_ids[]=$result->id;
				}
				if(count($email_ids)>0)
				{
					foreach($email_ids as $email_id)
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
					if(count($filter_attachments))
					{
						$filter_attachments=array_unique($filter_attachments);
						$filter_sql="select s.*, '' as filesize from ".$wpdb->prefix."honeybadger_static_attachments s where id in (".implode(",",$filter_attachments).") and enabled=1";
					}
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
		return array();
	}
	function get_products_stock_log($request)
	{
		global $wpdb;
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$product_ids=isset($parameters['product_ids'])?$parameters['product_ids']:array();
			if(is_array($product_ids) && count($product_ids)>0)
			{
				for($i=0;$i<count($product_ids);$i++)
					$product_ids[$i]=esc_sql($product_ids[$i]);
				$sql="delete from ".$wpdb->prefix."honeybadger_product_stock_log where product_id not in ('".implode("','",$product_ids)."')";
				$wpdb->query($sql);
				$sql="select * from ".$wpdb->prefix."honeybadger_product_stock_log where done=0 order by mdate";
				$results=$wpdb->get_results($sql);
				return $this->returnOk($results);
			}
			else
			{
				$sql="delete from ".$wpdb->prefix."honeybadger_product_stock_log where 1";
				$wpdb->query($sql);
			}
		}
		return $this->returnError();
	}
	function save_products_stock_log($request)
	{
		global $wpdb;
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$restored=isset($parameters['restored'])?$parameters['restored']:array();
			$reduced=isset($parameters['reduced'])?$parameters['reduced']:array();
			$restored_oids=isset($parameters['restored_oids'])?$parameters['restored_oids']:array();
			$reduced_oids=isset($parameters['reduced_oids'])?$parameters['reduced_oids']:array();
			if(is_array($restored) && count($restored)>0 && is_array($restored_oids) && count($restored)==count($restored_oids))
			{
				for($i=0;$i<count($restored);$i++)
				{
					$sql="delete from ".$wpdb->prefix."honeybadger_product_stock_log where product_id='".esc_sql($restored[$i])."' and order_id='".esc_sql($restored_oids[$i])."' and restored_stock>0 and reduced_stock>0";
					$wpdb->query($sql);
				}
			}
			if(is_array($reduced) && count($reduced)>0 && is_array($reduced_oids) && count($reduced)==count($reduced_oids))
			{
				for($i=0;$i<count($reduced);$i++)
				{
					$sql="update ".$wpdb->prefix."honeybadger_product_stock_log set
					done=1
					where
					product_id='".esc_sql($reduced[$i])."' and 
					order_id='".esc_sql($reduced_oids[$i])."' and 
					reduced_stock>0";
					$wpdb->query($sql);
				}
			}
		}
		return $this->returnOk();
	}
}