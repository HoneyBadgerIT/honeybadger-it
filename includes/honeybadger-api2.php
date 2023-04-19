<?php
/**
 * @package    Honeybadger_IT
 * @subpackage Honeybadger_IT/admin
 * @author     Claudiu Maftei <claudiu@honeybadger.it>
 */
class honeybadgerAPI2{
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
	        if(isset($parameters['method2']) && method_exists($this,$parameters['method2']))
	        {
	        	$method=$parameters['method2'];
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
	function get_order_statuses($request)
	{
		global $wpdb;
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
			return $result;
		}
		return array();
	}
	function get_dashboard_last_sales_data($request)
	{
		global $wpdb;
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$last_sales=isset($parameters['last_sales'])?$parameters['last_sales']:"last_week";
			$last_sales_status=isset($parameters['last_sales_status'])?$parameters['last_sales_status']:"wc-completed";
			$start_date=date("Y-m-d",strtotime(date("Y-m-d 00:00:00")." -1 week"));
			$end_date=date("Y-m-d",strtotime(date("Y-m-d 23:59:59")));
			if($last_sales=="last_month")
			{
				$start_date=date("Y-m-d",strtotime(date("Y-m-d 00:00:00")." -1 month"));
				$end_date=date("Y-m-d",strtotime(date("Y-m-d 23:59:59")));
			}
			if($last_sales=="last_year")
			{
				$start_date=date("Y-m-d",strtotime(date("Y-m-d 00:00:00")." -1 year"));
				$end_date=date("Y-m-d",strtotime(date("Y-m-d 23:59:59")));
			}
			$filter_status="wc-completed";
			$statuses=array();
			$result=$this->get_order_statuses($request);
			if(isset($result->content) && is_array($result->content))
				$statuses=$result->content;
			if(is_array($statuses))
			{
				foreach($statuses as $status)
				{
					if($status->status==$last_sales_status)
						$filter_status=$last_sales_status;
				}
			}
			$orders = array();
			$last_orders=array();
			if($last_sales=="last_year")
			{
				$curr_date=strtotime(date("Y-m-01",strtotime($start_date)));
				$last_date=strtotime(date("Y-m-01",strtotime($end_date)));
				if(!isset($last_orders[$curr_date]))
					$last_orders[$curr_date]=0;
				while($curr_date<$last_date)
				{
					$curr_date=strtotime(date("Y-m-d",$curr_date)." + 1 month");
					if(!isset($last_orders[$curr_date]))
						$last_orders[$curr_date]=0;
				}
			}
			else
			{
				$curr_date=strtotime($start_date);
				$last_date=strtotime(date("Y-m-d 00:00:00",strtotime($end_date)));
				if(!isset($last_orders[$curr_date]))
					$last_orders[$curr_date]=0;
				while($curr_date!=$last_date)
				{
					$curr_date=strtotime(date("Y-m-d",$curr_date)." + 1 day");
					if(!isset($last_orders[$curr_date]))
						$last_orders[$curr_date]=0;
				}
			}
			$end_date=date("Y-m-d",strtotime($end_date." + 1 day"));
			$sql="select ID, post_date_gmt from ".$wpdb->prefix."posts where post_type='shop_order' and post_date_gmt>='".esc_sql($start_date)."' and post_date_gmt<='".esc_sql($end_date)."' and post_status='".esc_sql($filter_status)."' order by post_date_gmt";
			$orders=$wpdb->get_results($sql);
			if(is_array($orders))
			{
				foreach($orders as $order)
				{
					$order_stamp=strtotime(date("Y-m-d 00:00:00",strtotime($order->post_date_gmt)));
					if($last_sales=="last_year")
					{
						$month_stamp=strtotime(date("Y-m-01",$order_stamp));
						$last_orders[$month_stamp]++;
					}
					else
						$last_orders[$order_stamp]++;
				}
			}
			$last_year_orders=array();
			foreach($last_orders as $stamp => $cnt)
			{
				$new_stamp=strtotime(date("Y-m-d",$stamp)." -1 year");
				if($last_sales=="last_year")
				{
					$month_stamp=strtotime(date("Y-m-01",$new_stamp));
					$last_year_orders[$month_stamp]=0;
				}
				else
					$last_year_orders[$new_stamp]=0;
			}
			$start_date=date("Y-m-d",strtotime($start_date." -1 year"));
			$end_date=date("Y-m-d",strtotime($end_date." -1 year"));
			$sql="select ID, post_date_gmt from ".$wpdb->prefix."posts where post_type='shop_order' and post_date_gmt>='".esc_sql($start_date)."' and post_date_gmt<='".esc_sql($end_date)."' and post_status='".esc_sql($filter_status)."' order by post_date_gmt";
			$orders=$wpdb->get_results($sql);
			if(is_array($orders))
			{
				foreach($orders as $order)
				{
					$order_stamp=strtotime(date("Y-m-d 00:00:00",strtotime($order->post_date_gmt)));
					if($last_sales=="last_year")
					{
						$month_stamp=strtotime(date("Y-m-01",$order_stamp));
						$last_year_orders[$month_stamp]++;
					}
					else
						$last_year_orders[$order_stamp]++;
				}
			}
			$last_2_years_orders=array();
			foreach($last_year_orders as $stamp => $cnt)
			{
				$new_stamp=strtotime(date("Y-m-d",$stamp)." -1 year");
				if($last_sales=="last_year")
				{
					$month_stamp=strtotime(date("Y-m-01",$new_stamp));
					$last_2_years_orders[$month_stamp]=0;
				}
				else
					$last_2_years_orders[$new_stamp]=0;
			}
			$start_date=date("Y-m-d",strtotime($start_date." -1 year"));
			$end_date=date("Y-m-d",strtotime($end_date." -1 year"));
			$sql="select ID, post_date_gmt from ".$wpdb->prefix."posts where post_type='shop_order' and post_date_gmt>='".esc_sql($start_date)."' and post_date_gmt<='".esc_sql($end_date)."' and post_status='".esc_sql($filter_status)."' order by post_date_gmt";
			$orders=$wpdb->get_results($sql);
			if(is_array($orders))
			{
				foreach($orders as $order)
				{
					$order_stamp=strtotime(date("Y-m-d 00:00:00",strtotime($order->post_date_gmt)));
					if($last_sales=="last_year")
					{
						$month_stamp=strtotime(date("Y-m-01",$order_stamp));
						$last_2_years_orders[$month_stamp]++;
					}
					else
						$last_2_years_orders[$order_stamp]++;
				}
			}
			return array("statuses"=>$statuses,"last_sales"=>$last_sales,"last_sales_status"=>$filter_status,"last_orders"=>$last_orders,"last_year_orders"=>$last_year_orders,"last_2_years_orders"=>$last_2_years_orders);
		}
		return array();
	}
	function get_dashboard_latest_orders_data($request)
	{
		global $wpdb;
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$latest_orders_status=isset($parameters['latest_orders_status'])?$parameters['latest_orders_status']:"wc-completed";
			$latest_orders_dir=isset($parameters['latest_orders_dir'])?$parameters['latest_orders_dir']:"desc";
			$latest_orders_limit=isset($parameters['latest_orders_limit'])?(int)$parameters['latest_orders_limit']:10;
			$filter_status="wc-completed";
			if($latest_orders_dir!='asc' && $latest_orders_dir!='desc')
				$latest_orders_dir="desc";
			$statuses=array();
			$result=$this->get_order_statuses($request);
			if(isset($result->content) && is_array($result->content))
				$statuses=$result->content;
			if(is_array($statuses))
			{
				foreach($statuses as $status)
				{
					if($status->status==$latest_orders_status)
						$filter_status=$latest_orders_status;
				}
			}
			$sql="select ID from ".$wpdb->prefix."posts where post_type='shop_order' and post_status='".esc_sql($filter_status)."' order by ID ".$latest_orders_dir." limit ".$latest_orders_limit;
			$results=$wpdb->get_results($sql);
			$latest_orders=array();
			if(is_array($results))
			{
				foreach($results as $r)
				{
					$order_data=wc_get_order($r->ID);
					if(!$order_data)
						continue;
					$order_details=$order_data->get_data();
					$order=new stdClass;
					$order->id=$order_details['id'];
					$order->status_orig=$order_details['status'];
					$order->currency_symbol=get_woocommerce_currency_symbol($order_details['currency']);
					$order->total_refunded=$order_data->get_total_refunded();
					$order->total=$order_details['total'];
					if($order->total_refunded>0)
						$order->total=number_format(round(($order->total-$order->total_refunded),2),2,".",",");
					$order->date_created=$order_details['date_created']->__toString();
					$order->first_name=$order_details['billing']['first_name'];
					$order->last_name=$order_details['billing']['last_name'];
					$latest_orders[]=$order;
				}
			}
			return array("latest_orders"=>$latest_orders,"latest_orders_status"=>$latest_orders_status,"latest_orders_dir"=>$latest_orders_dir,"latest_orders_limit"=>$latest_orders_limit);
		}
		return array();
	}
	function get_dashboard_stock_low($request)
	{
		global $wpdb;
		if(!empty($request))
		{
			$products=array();
			$sql="
			SELECT p.ID, p.post_title, m.meta_value as stock_low, m1.meta_value as stock, m2.meta_value as stock_status,
			m3.option_value as woocommerce_notify_low_stock_amount, m4.meta_value as manage_stock
			FROM ".$wpdb->prefix."posts as p 
			left join ".$wpdb->prefix."postmeta m on m.post_id=p.ID and m.meta_key='_low_stock_amount' 
			left join ".$wpdb->prefix."postmeta m1 on m1.post_id=p.ID and m1.meta_key='_stock' 
			left join ".$wpdb->prefix."postmeta m2 on m2.post_id=p.ID and m2.meta_key='_stock_status' 
			left join ".$wpdb->prefix."options m3 on m3.option_name='woocommerce_notify_low_stock_amount'
			left join ".$wpdb->prefix."postmeta m4 on m4.post_id=p.ID and m4.meta_key='_manage_stock' 
			WHERE (p.post_type='product' or p.post_type='product_variation') and p.post_status='publish' and
			m2.meta_value='outofstock' or (
				m2.meta_value='instock' and (
				(m.meta_value is not null and m1.meta_value is not null and m.meta_value<>'' and m1.meta_value<>'' and m1.meta_value<=m.meta_value) or
				(m1.meta_value is not null and m1.meta_value<>'' and (m.meta_value is null or m.meta_value='') and m3.option_value is not null and m3.option_value<>'' and m1.meta_value<=m3.option_value) or
				((m1.meta_value is null or m1.meta_value='') and (m.meta_value is not null and m.meta_value<>''))))
			order by p.post_title;
			";
			$results=$wpdb->get_results($sql);
			if(is_array($results))
			{
				foreach($results as $result)
				{
					if($result->stock_status=='instock' && $result->manage_stock=='no')
						continue;
					if((int)$result->stock_low==0)
						$result->stock_low=(int)$result->woocommerce_notify_low_stock_amount;
					if($result->stock_status=='instock' && (int)$result->stock==0 && (int)$result->stock_low==0)
						continue;
					if($result->stock_status=='instock' && (int)$result->stock>=0 && (int)$result->stock_low>=0 && (int)$result->stock_low<(int)$result->stock)
						continue;
					$product_obj=wc_get_product($result->ID);
					$children=$product_obj->get_children();
					if(is_array($children) && count($children)>0)
						continue;
					$product=new stdClass;
					$product->id=$result->ID;
					$product->type='product';
					$product->title=$result->post_title;
					$product->stock=$result->stock;
					$product->stock_low=$result->stock_low;
					$product->stock_status=$result->stock_status;
					$product->image='';
					$product_image=wp_get_attachment_image_src( get_post_thumbnail_id( $product->id ), 'thumbnail' );
					if(is_array($product_image) && isset($product_image[0]))
						$product->image=$product_image[0];
					$products[]=$product;
				}
				return array("products_stock_low"=>$products);
			}
		}
		return array();
	}
	function get_dashboard_enabled_wc_emails_cnt($request)
	{
		global $wpdb;
		if(!empty($request))
		{
			$sql="select count(*) as total from ".$wpdb->prefix."honeybadger_wc_emails where enabled=1";
			$result=$wpdb->get_row($sql);
			if(isset($result->total))
				return array("enabled_wc_emails_cnt"=>(int)$result->total);
		}
		return array();
	}
	function get_dashboard_enabled_emails_cnt($request)
	{
		global $wpdb;
		if(!empty($request))
		{
			$sql="select count(*) as total from ".$wpdb->prefix."honeybadger_emails where enabled=1";
			$result=$wpdb->get_row($sql);
			if(isset($result->total))
				return array("enabled_emails_cnt"=>(int)$result->total);
		}
		return array();
	}
	function get_dashboard_enabled_attachments_cnt($request)
	{
		global $wpdb;
		if(!empty($request))
		{
			$sql="select count(*) as total from ".$wpdb->prefix."honeybadger_attachments where enabled=1";
			$result=$wpdb->get_row($sql);
			if(isset($result->total))
				return array("enabled_attachments_cnt"=>(int)$result->total);
		}
		return array();
	}
	function get_dashboard_enabled_static_attachments_cnt($request)
	{
		global $wpdb;
		if(!empty($request))
		{
			$sql="select count(*) as total from ".$wpdb->prefix."honeybadger_static_attachments where enabled=1";
			$result=$wpdb->get_row($sql);
			if(isset($result->total))
				return array("enabled_static_attachments_cnt"=>(int)$result->total);
		}
		return array();
	}
	function get_dashboard_data($request)
	{
		global $wpdb;
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$type=isset($parameters['type'])?$parameters['type']:"";
			$return=array();
			if($type=="")
			{
				$return_type=$this->get_dashboard_last_sales_data($request);
				$return=array_merge($return,$return_type);
				$return_type=$this->get_dashboard_latest_orders_data($request);
				$return=array_merge($return,$return_type);
				$return_type=$this->get_dashboard_stock_low($request);
				$return=array_merge($return,$return_type);
				$return_type=$this->get_dashboard_enabled_wc_emails_cnt($request);
				$return=array_merge($return,$return_type);
				$return_type=$this->get_dashboard_enabled_emails_cnt($request);
				$return=array_merge($return,$return_type);
				$return_type=$this->get_dashboard_enabled_attachments_cnt($request);
				$return=array_merge($return,$return_type);
				$return_type=$this->get_dashboard_enabled_static_attachments_cnt($request);
				$return=array_merge($return,$return_type);
			}
			if($type=="last_sales")
			{
				$return_type=$this->get_dashboard_last_sales_data($request);
				$return=array_merge($return,$return_type);
			}
			if($type=="latest_orders")
			{
				$return_type=$this->get_dashboard_latest_orders_data($request);
				$return=array_merge($return,$return_type);
			}
			if($type=="products_stock_low")
			{
				$return_type=$this->get_dashboard_stock_low($request);
				$return=array_merge($return,$return_type);
			}
			return $this->returnOk($return);
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
	function GetDirectorySize($path)
	{
	    $bytestotal = 0;
	    $path = realpath($path);
	    if($path!==false && $path!='' && file_exists($path)){
	        foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object)
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
	function get_saved_attachments($request)
	{
		if(!empty($request))
		{
			$return=array('files'=>array(),'recordsTotal'=>0,'recordsFiltered'=>0);
			$parameters = $request->get_params();
			$folder=isset($parameters['folder'])?$parameters['folder']:"";
			$start=isset($parameters['start'])?(int)$parameters['start']:0;
			$limit=isset($parameters['limit'])?(int)$parameters['limit']:10;
			$search=isset($parameters['search'])?$parameters['search']:"";

			$path=ABSPATH."wp-content/plugins/honeybadger-it/attachments";
			
			if($start>0)
				$limit=$limit+$start;
			$orig_path=$path;
			if($folder!="")
				$path=$path."/".$folder;
			if(is_dir($path))
			{
				$files=scandir($path);
				if(is_array($files))
				{
					foreach($files as $file)
					{
						if(in_array($file,array(".","..","index.php")))
							continue;
						$type="file";
						if(is_dir($path."/".$file))
							$type="folder";
						$item=new stdClass;
						$item->path=str_replace($orig_path,"",$file);
						if($folder!="")
							$item->path=$folder."/".$item->path;
						$item->type=$type;
						$item->folder=$folder;
						if($type=="file")
						{
							if($folder!="")
								$item->name=$folder."/".$this->removeMd5FromFilename($file);
							else
								$item->name=$this->removeMd5FromFilename($file);
							$item->size=number_format(round((filesize($path."/".$file) / 1024 / 1024),2),2,".","");
						}
						else
						{
							$item->name=$file;
							$item->size=number_format(round(($this->GetDirectorySize($path."/".$file) / 1024 / 1024),2),2,".","");
						}
						$item->modified=filemtime($path."/".$file);
						$return['files'][]=$item;
					}
				}
				$return['recordsTotal']=count($return['files']);
				$return['recordsFiltered']=count($return['files']);
				if($search!="")
				{
					$new_files=array();
					foreach($return['files'] as $file)
					{
						$pos=strpos(strtolower($file->name), strtolower($search));
						if($pos!==false)
							$new_files[]=$file;
					}
					$return['files']=$new_files;
					$return['recordsFiltered']=count($return['files']);
				}
				if(count($return['files'])>0)
				{
					if(count($return['files'])<$limit)
						$limit=count($return['files']);
					$new_files=array();
					for($i=$start;$i<$limit;$i++)
						$new_files[]=$return['files'][$i];
					$return['files']=$new_files;
				}
				if($folder!="")
				{
					$top_level=new stdClass;
					$top_level->path="..";
					$top_level->name='';
					$return['files']=array_merge(array($top_level),$return['files']);
				}
				return $this->returnOk($return);
			}
		}
		return $this->returnError();
	}
	function getMd5FromFilename($file="")
	{
		if($file!="")
		{
			$tmp=explode("_",$file);
			$new_tmp=array();
			if(is_array($tmp) && count($tmp)>1 && $this->isValidmd5($tmp[0]))
				return $tmp[0];
		}
		return $file;
	}
	function delete_remote_item($request)
	{
		if(!empty($request))
		{
			$parameters = $request->get_params();
			$file=isset($parameters['file'])?$parameters['file']:"";
			$type=isset($parameters['type'])?$parameters['type']:"";
			$attachments_path=ABSPATH."wp-content/plugins/honeybadger-it/attachments";
			if($type=="file")
			{
				$file_name=basename($file);
				$file_folder=str_ireplace("/".basename($file),"",$file);
				$possible_files=glob($attachments_path."/".$file_folder."/*_".$file_name);
				$file_hash="";
				if(is_array($possible_files))
				{
					foreach($possible_files as $possible_file)
						$file_hash=$this->getMd5FromFilename(basename($possible_file));
				}
				if($file_hash!="")
				{
					$file_path=$attachments_path."/".$file_folder."/".$file_hash."_".$file_name;
					if(is_file($file_path))
					{
						if(!unlink($file_path))
							return $this->returnError();
					}
					return $this->returnOk();
				}
			}
			if($type=="folder")
			{
				$folder_path=$attachments_path."/".$file;
				if(is_dir($folder_path))
				{
					$folder=scandir($folder_path);
					if(is_array($folder))
					{
						foreach($folder as $file_path)
						{
							if($file_path=="." || $file_path==".." || $file_path=="index.php")
								continue;
							$file_path=$folder_path."/".$file_path;
							if(is_file($file_path))
							{
								if(!unlink($file_path))
									return $this->returnError();
							}
						}
						return $this->returnOk();
					}
				}
			}
		}
		return $this->returnError();
	}
}
?>