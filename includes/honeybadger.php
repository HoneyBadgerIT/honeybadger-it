<?php
/**
 * @package    Honeybadger_IT
 * @subpackage Honeybadger_IT/admin
 * @author     Claudiu Maftei <claudiu@honeybadger.it>
 */
namespace HoneyBadgerIT;
use \stdClass;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly   
require_once HONEYBADGER_PLUGIN_PATH . 'constants.php';
class honeybadger{
	
	public $config;
	public $config_front;
	function __construct(){
		global $wpdb;
		$this->config=new stdClass;
		$this->config_front=new stdClass;
		$sql=$wpdb->prepare("select * from ".$wpdb->prefix."honeybadger_config where 1");
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
	function is_woocommerce_activated(){
		if ( class_exists( 'woocommerce' ) ) { return true; } else { return false; }
	}
	function rutime($ru, $rus, $index) {
	    return ($ru["ru_$index.tv_sec"]*1000 + intval($ru["ru_$index.tv_usec"]/1000))
	     -  ($rus["ru_$index.tv_sec"]*1000 + intval($rus["ru_$index.tv_usec"]/1000));
	}
	function simpleCurlRequest($url) {
		$verify_ssl=true;
		if(strtolower($this->config->curl_ssl_verify)=="no")
			$verify_ssl=false;
		$start = microtime(true);
		$body="";
		$response = wp_remote_get( $url, array('sslverify' => $verify_ssl) );
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			echo esc_html($error_message);
		}
		else
			$body = wp_remote_retrieve_body( $response );
		$time_elapsed_secs = microtime(true) - $start;
		return array('response'=>$body,'time'=>number_format(round($time_elapsed_secs,2),2,".",""));
	}
	function checkIfSSL(){
		$stream = stream_context_create (array("ssl" => array("capture_peer_cert" => true)));

		$read = @fopen(get_site_url(null,'','https'), "r", false, $stream);//we are using the error handler @ because if self signed certificate fopen will give warnings
		if($read)
			$cont = stream_context_get_params($read);
		if(isset($cont))
			$var = ($cont["options"]["ssl"]["peer_certificate"]);
		$ssl_active = (isset($var) && !is_null($var)) ? true : false;
		return $ssl_active;
	}
	function checkIfMultisite(){
		$multisite=false;
		if ( is_multisite() )
		    $multisite=true;
		return $multisite;
	}
	function compareWordpressVersion()
	{
		global $wp_version;
		$version_good=false;
		if(version_compare( $wp_version, '5.0' ) >= 0)
			$version_good=true;
		return $version_good;
	}
	function compareWoocommerceVersion()
	{
		global $wp_version;
		$version_good=false;
		if(version_compare( $wp_version, '5.8.2' ) >= 0)
			$version_good=true;
		return $version_good;
	}
	function checkPhpVersion(){
		$php_version_good=false;
		if(version_compare( PHP_VERSION, '5.4' ) >= 0)
		    $php_version_good=true;
		return $php_version_good;
	}
	function checkWcActivated(){
		$wc_activated=false;
	    if($this->is_woocommerce_activated())
	        $wc_activated=true;
	   return $wc_activated;
	}
	function checkRestApiV2(){
		$response_arr=$this->simpleCurlRequest(get_rest_url()."wp/v2");
		$response=$response_arr['response'];
		if($response!="")
			$response=json_decode($response);
		if(isset($response->namespace) && $response->namespace=="wp/v2")
			return true;
		return false;
	}
	function chechIfSelfSigned(){
		$url              = get_rest_url();
		$new_url=str_ireplace("https://","",$url);
		$new_url=str_ireplace("http://","",$new_url);
		$new_url="https://".$new_url."wp/v2";
		$response_arr=$this->simpleCurlRequest($new_url);
		$response=$response_arr['response'];
		if($response!="")
			$response=json_decode($response);
		if(isset($response->namespace) && $response->namespace=="wp/v2")
			return true;
		return false;
	}
	function saveSettings()
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		$cnt=0;
		$config_values=array('curl_ssl_verify'=>'','use_status_colors_on_wc'=>'','delete_attachments_upon_uninstall'=>'','skip_rest_authentication_errors'=>'');
		foreach($config_values as $config_name => $config_value)
		{
			if(isset($_POST[$config_name]))
			{
				$the_config_value=sanitize_text_field($_POST[$config_name]);
				if(in_array($the_config_value,array('yes','no')) && isset($this->config_front->$config_name))
				{
					$sql=$wpdb->prepare("update ".$wpdb->prefix."honeybadger_config set config_value=%s where %s",array($the_config_value,$config_name));
					if(!$wpdb->query($sql) && $wpdb->last_error !== '')
					{
						return array('status'=>'error','msg'=>"Error: in saving settings");
					}
					else
						$cnt++;
				}
			}
		}
		return array('status'=>'updated','cnt'=>$cnt,'msg'=>"Settings updated");
	}
	function doTokensCleanup()
	{
		global $wpdb;
		$sql=$wpdb->prepare("delete from ".$wpdb->prefix."honeybadger_oauth_access_tokens where expires<%s",date("Y-m-d H:i:s"));
		$wpdb->query($sql);
		$sql=$wpdb->prepare("delete from ".$wpdb->prefix."honeybadger_oauth_refresh_tokens where expires<%s",date("Y-m-d H:i:s"));
		$wpdb->query($sql);
	}
	function createUserRoleAndUser()
	{
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		$this->doTokensCleanup();
		if(!$GLOBALS['wp_roles']->is_role("honeybadger"))
			add_role("honeybadger","HoneyBadger",array("use_honeybadger_api"=>true));
		if(!$GLOBALS['wp_roles']->is_role("honeybadger"))
		{
			wp_send_json(array("status"=>"error", "msg"=>'<div class="hb-notice-error">
			        <p>'.esc_html__("Cannot create Honeybadger user role","honeyb").'</p>
			     </div>', 'nonce'=>wp_create_nonce( 'honeybadger_it_ajax_nonce' )));
		}
		$user=array(
			"user_pass"=>bin2hex(random_bytes(16)),
			"user_login"=>"honeybadger".get_current_blog_id(),
			"user_nicename"=>"HoneyBadger",
			"user_email"=>bin2hex(random_bytes(8))."@honeybadger.it",
			"description"=>esc_html__('This user is used for the HoneyBadger IT communications through the REST API','honeyb'),
			"role"=>"honeybadger",
		);
		if(!username_exists("honeybadger".get_current_blog_id()))
		{
			$user_id=wp_insert_user($user);
			if(is_int($user_id))
			{
				wp_send_json(array("status"=>"ok", "msg"=>'<div class="hb-notice-updated">
				        <p>'.esc_html__("Honeybadger user created with success","honeyb").'</p>
				     </div>', 'nonce'=>wp_create_nonce( 'honeybadger_it_ajax_nonce' )));
			}
			else
			{
				wp_send_json(array("status"=>"error", "msg"=>'<div class="hb-notice-error">
				        <p>'.esc_html__("Cannot create Honeybadger user","honeyb").'</p>
				     </div>', 'nonce'=>wp_create_nonce( 'honeybadger_it_ajax_nonce' )));
			}
		}
		if(username_exists("honeybadger".get_current_blog_id()))
			wp_send_json(array("status"=>"ok", "msg"=>'<div class="hb-notice-updated">
			        <p>'.esc_html__("Honeybadger user already exists from previous setup","honeyb").'</p>
			     </div>', 'nonce'=>wp_create_nonce( 'honeybadger_it_ajax_nonce' )));
	}
	function createHoneybadgerConnection()
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		//create the client credentials
		$client_id=bin2hex(random_bytes(16));
		$client_secret=bin2hex(random_bytes(16));
		$user_id=username_exists("honeybadger".get_current_blog_id());
		$verify_ssl=$this->config->curl_ssl_verify;
		if(is_int($user_id))
		{
			$sql=$wpdb->prepare("select * from ".$wpdb->prefix."honeybadger_oauth_clients where user_id=%d",$user_id);
			$result=$wpdb->get_row($sql);

			if(isset($result->user_id))
			{
				$client_id=$result->client_id;
				$client_secret=$result->client_secret;
			}
			else
			{
				$sql=$wpdb->prepare("insert into ".$wpdb->prefix."honeybadger_oauth_clients set
				client_id=%s,
				client_secret=%s,
				redirect_uri=%s,
				user_id=%d",
				array($client_id,$client_secret,sanitize_text_field("https://".HONEYBADGER_IT_TARGET_SUBDOMAIN.".honeybadger.it/oauth.php"),$user_id));
				if(!$wpdb->query($sql) && $wpdb->last_error !== '')
				{
					wp_send_json(array("status"=>"error", "msg"=>'<div class="hb-notice-error">
					        <p>'.esc_html__("Cannot create Honeybadger client","honeyb").'</p>
					     </div>', 'nonce'=>wp_create_nonce( 'honeybadger_it_ajax_nonce' )));
				}
			}
			$url = get_site_url();
			$domain=str_ireplace("https://","",$url);
			$domain=str_ireplace("http://","",$domain);
			$nonce=wp_create_nonce( 'honeybadger_it_oauth_nonce' );
			$rest_url=get_rest_url();
			$rest_url=str_ireplace(get_site_url(), "", $rest_url);
			$params="rest_url=".$rest_url."&nonce=".rawurlencode($nonce)."&client_id=".rawurlencode($client_id)."&client_secret=".rawurlencode($client_secret)."&domain=".rawurlencode($domain)."&verify_ssl=".rawurlencode($verify_ssl);
			$url="https://".HONEYBADGER_IT_TARGET_SUBDOMAIN.".honeybadger.it/oauth.php";
			$result=$this->doHoneyBadgerCurlRequest($url,$params);
			wp_send_json(array("status"=>"ok", "msg"=>$result['response'], 'nonce'=>wp_create_nonce( 'honeybadger_it_oauth_nonce' )));
		}
		else
			esc_html_e("missing user id","honeyb");
	}
	function refreshHoneybadgerConnection()
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		$this->doTokensCleanup();
		$user_id=username_exists("honeybadger".get_current_blog_id());
		$verify_ssl=$this->config->curl_ssl_verify;
		if(is_int($user_id))
		{
			$this->setSettingValue("is_refresh","1");
			$sql=$wpdb->prepare("select * from ".$wpdb->prefix."honeybadger_oauth_clients where user_id=%d",$user_id);
			$result=$wpdb->get_row($sql);
			$client_id="";
			$client_secret="";
			if(isset($result->user_id))
			{
				$client_id=$result->client_id;
				$client_secret=$result->client_secret;
				$sql=$wpdb->prepare("delete from ".$wpdb->prefix."honeybadger_oauth_access_tokens where 1");
				$wpdb->query($sql);
				$sql=$wpdb->prepare("delete from ".$wpdb->prefix."honeybadger_oauth_refresh_tokens where 1");
				$wpdb->query($sql);
			}
			$url = get_site_url();
			$domain=str_ireplace("https://","",$url);
			$domain=str_ireplace("http://","",$domain);
			$nonce=wp_create_nonce( 'honeybadger_it_oauth_nonce' );
			$rest_url=get_rest_url();
			$rest_url=str_ireplace(get_site_url(), "", $rest_url);
			$params="rest_url=".rawurlencode($rest_url)."&nonce=".rawurlencode($nonce)."&client_id=".rawurlencode($client_id)."&client_secret=".rawurlencode($client_secret)."&domain=".rawurlencode($domain)."&verify_ssl=".rawurlencode($verify_ssl);
			$result=$this->doHoneyBadgerCurlRequest("https://".HONEYBADGER_IT_TARGET_SUBDOMAIN.".honeybadger.it/oauth.php",$params);
			wp_send_json(array("status"=>"ok", "msg"=>$result['response']));
		}
	}
	function doHoneyBadgerCurlRequest($url="",$params="") {
		$verify_ssl=true;
		if(strtolower($this->config->curl_ssl_verify)=="no")
			$verify_ssl=false;
		$start = microtime(true);
		$args=array();
		parse_str($params,$args);
		$args=array('method' => 'POST','body' => array_merge(array('sslverify' => $verify_ssl),$args));
		$body="";
		$response = wp_remote_post( $url, $args );
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			echo esc_html($error_message);
		}
		else
			$body = wp_remote_retrieve_body( $response );
		$time_elapsed_secs = microtime(true) - $start;
		return array('response'=>$body,'time'=>number_format(round($time_elapsed_secs,2),2,".",""));
	}
	function setCurrentSetupStep($step=0)
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		$sql=$wpdb->prepare("update ".$wpdb->prefix."honeybadger_config set config_value=%s where config_name='setup_step'",$step);
		$wpdb->query($sql);
	}
	function setSettingValue($setting="",$value="")
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		if($setting!="" && $value!="")
		{
			$sql=$wpdb->prepare("update ".$wpdb->prefix."honeybadger_config set config_value=%s where config_name=%s",array($value,$setting));
			$wpdb->query($sql);
		}
	}
	function doOauthPingTest()
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		$user_id=username_exists("honeybadger".get_current_blog_id());
		if($user_id>0)
		{
			$sql=$wpdb->prepare("select client_id from ".$wpdb->prefix."honeybadger_oauth_clients where user_id=%d",$user_id);
			$result=$wpdb->get_row($sql);
			if(isset($result->client_id))
			{
				$response=$this->simpleCurlRequest("https://".HONEYBADGER_IT_TARGET_SUBDOMAIN.".honeybadger.it/test_oauth.php?client_id=".sanitize_text_field($result->client_id));
				if(isset($response['response']))
				{
					$result=json_decode($response['response']);
					if(isset($result->status) && $result->status=='ok')
					{
						if(isset($result->account_email) && $result->account_email!="")
						{
							$sql=$wpdb->prepare("update ".$wpdb->prefix."honeybadger_config set config_value=%s where config_name='honeybadger_account_email'",$result->account_email);
							$wpdb->query($sql);
						}
						if(isset($result->has_user) && $result->has_user=="yes")
						{
							$this->setSettingValue("is_refresh","1");
							$this->config->is_refresh=1;
						}
						$setup_step=2;
						if($this->config->is_refresh==1)
						{
							$setup_step=3;
							$this->setSettingValue("is_refresh","0");
						}
						$this->setSettingValue("setup_step",$setup_step);
						if($this->config->is_refresh==1)
						{
							$data_css='
							#hb-status-update{
								display: none;
							}
							#hb-one-moment{
								display: block!important;
							}';
							wp_register_style( 'honeybadger_it_css_setup_display_section_handler', false );
							wp_enqueue_style( 'honeybadger_it_css_setup_display_section_handler' );
							wp_add_inline_style( 'honeybadger_it_css_setup_display_section_handler', $data_css );

							$data_js='window.location.href = "'.esc_url(admin_url().'admin.php?page=honeybadger-it').'";';
							wp_register_script( 'honeybadger_it_js_tools_inline_script_handler', '' );
							wp_enqueue_script( 'honeybadger_it_js_tools_inline_script_handler' );
							wp_add_inline_script("honeybadger_it_js_tools_inline_script_handler",$data_js);
							return true;
						}
						else
						{
							echo '<div class="hb-notice-updated">
						        <p>'.esc_html__("Honeybadger Oauth setup with success","honeyb").'</p>
						     </div>';
						     return true;
					 	}
					}
				}
			}
		}
		echo '<div class="hb-notice-error">
		        <p>'.esc_html__("Error in Oauth REST API communication between your site and HoneyBadger IT","honeyb").'</p>
		     </div>';
	}
	function restartTheSetup()
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		$sql=$wpdb->prepare("delete from ".$wpdb->prefix."honeybadger_oauth_access_tokens where 1");
		$wpdb->query($sql);
		$sql=$wpdb->prepare("delete from ".$wpdb->prefix."honeybadger_oauth_refresh_tokens where 1");
		$wpdb->query($sql);
		$sql=$wpdb->prepare("update ".$wpdb->prefix."honeybadger_config set config_value='0' where config_name='setup_step'");
		$wpdb->query($sql);
	}
	function getHbClientId()
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		$user_id=username_exists("honeybadger".get_current_blog_id());
		if($user_id>0)
		{
			$sql=$wpdb->prepare("select client_id from ".$wpdb->prefix."honeybadger_oauth_clients where user_id=%d",$user_id);
			$result=$wpdb->get_row($sql);
			if(isset($result->client_id))
				return $result->client_id;
		}
		return false;
	}
	function getHbClientSecret()
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		$user_id=username_exists("honeybadger".get_current_blog_id());
		if($user_id>0)
		{
			$sql=$wpdb->prepare("select client_secret from ".$wpdb->prefix."honeybadger_oauth_clients where user_id=%d",$user_id);
			$result=$wpdb->get_row($sql);
			if(isset($result->client_secret))
				return $result->client_secret;
		}
		return false;
	}
	function validateEmail($email) {
	    if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
	        return true;
	    }
	    else {
	        return false;
	    }
	}
	function setupTheHbAccount()
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		$email=isset($_POST['hb_email'])?sanitize_email($_POST['hb_email']):"";
		$password=isset($_POST['hb_password'])?sanitize_text_field($_POST['hb_password']):"";
		$first_name=isset($_POST['hb_first_name'])?sanitize_text_field($_POST['hb_first_name']):"";
		$last_name=isset($_POST['hb_last_name'])?sanitize_text_field($_POST['hb_last_name']):"";
		$client_id=$this->getHbClientId();

		if($client_id && $email!="" && $password!="")
		{
			if(!$this->validateEmail($email))
			{
				echo '<div class="notice notice-error is-dismissible">
			        <p>'.esc_html__("Please input a valid email address","honeyb").'</p>
			     </div>';
			     return;
			}
			if(!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,20}$/', $password))
			{
				echo '<div class="notice notice-error is-dismissible">
			        <p>'.esc_html__("Please input a Password [6 to 20 characters which contain at least one numeric digit, one uppercase and one lowercase letter]","honeyb").'</p>
			     </div>';
			     return;
			}
			//coninue with the creation process
			$woocommerce_prices_include_tax=get_option('woocommerce_prices_include_tax');
			$so_tax_included=1;
			if($woocommerce_prices_include_tax=='no')
				$so_tax_included=0;
			$url="https://".HONEYBADGER_IT_TARGET_SUBDOMAIN.".honeybadger.it/create_new_user.php";
			$params="email=".rawurlencode($email)."&password=".rawurlencode($password)."&first_name=".rawurlencode($first_name)."&last_name=".rawurlencode($last_name)."&so_tax_included=".rawurlencode($so_tax_included)."&client_id=".rawurlencode($client_id);
			$result=$this->doHoneyBadgerCurlRequest($url,$params);
			$decoded=json_decode($result['response']);
			if(isset($decoded->status) && $decoded->status=="ok")
			{
				echo '<div class="notice notice updated is-dismissible">
			        <p>'.esc_html__("Your HoneyBadger IT account created with success! Please check your email and verify your email address in order for the account to be activated.","honeyb").'</p>
			     </div>';
			    $sql=$wpdb->prepare("update ".$wpdb->prefix."honeybadger_config set config_value='3' where config_name='setup_step'");
				$wpdb->query($sql);
				$sql=$wpdb->prepare("update ".$wpdb->prefix."honeybadger_config set config_value=%s where config_name='honeybadger_account_email'",$email);
				$wpdb->query($sql);
				$location=admin_url()."admin.php?page=honeybadger-it&msg=created";
    			header("Location: $location");
			}
			else
			{
				$reason="Something went wrong, please try again later";
				if(isset($decoded->msg))
					$reason=$decoded->msg;
				echo '<div class="notice notice-error is-dismissible">
		        	<p>'.esc_html($reason).'</p>
		     	</div>';
			}
		}
		else
		{
			echo '<div class="notice notice-error is-dismissible">
		        <p>'.esc_html__("Something is wrong, please reinstall the plugin and restart the setup process","honeyb").'</p>
		     </div>';
		}
	}
	function changeClientIdAndSecret()
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		$client_id=isset($_POST['client_id'])?sanitize_text_field($_POST['client_id']):"";
		$client_secret=isset($_POST['client_secret'])?sanitize_text_field($_POST['client_secret']):"";
		if($client_id!="" && $client_secret!="")
		{
			$old_client_id=$this->getHbClientId();
			if($old_client_id!=$client_id)
			{
				$sql=$wpdb->prepare("update ".$wpdb->prefix."honeybadger_oauth_clients set 
				client_id=%s,
				client_secret=%s
				where
				client_id=%s",
				array($client_id,$client_secret,$old_client_id));
				if((!$wpdb->query($sql) && $wpdb->last_error !== '') || $wpdb->rows_affected==0 )
					return array('status'=>'error','msg'=>'Something went wrong when updating the database, please start the setup first and try again');
				else
				{
					$this->restartTheSetup();
					return array('status'=>'updated','msg'=>'Credentials updated with success, please redo the setup again now');
				}
			}
			else
			{
				$sql=$wpdb->prepare("update ".$wpdb->prefix."honeybadger_oauth_clients set 
				client_secret=%s
				where
				client_id=%s",
				array($client_secret,$old_client_id));
				if((!$wpdb->query($sql) && $wpdb->last_error !== '') )
					return array('status'=>'error','msg'=>'Something went wrong when updating the database, please start the setup first and try again');
				else
				{
					$this->restartTheSetup();
					return array('status'=>'updated','msg'=>'Credentials updated with success, please redo the setup again now');
				}
			}
		}
		else
			return array('status'=>'error','msg'=>'Something is wrong, please try again','honeyb');
	}
	function revokeHbAccess()
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		$sql=$wpdb->prepare("delete from ".$wpdb->prefix."honeybadger_oauth_access_tokens where 1");
		$wpdb->query($sql);
		$sql=$wpdb->prepare("delete from ".$wpdb->prefix."honeybadger_oauth_refresh_tokens where 1");
		$wpdb->query($sql);
		$sql=$wpdb->prepare("update ".$wpdb->prefix."honeybadger_config set config_value='4' where config_name='setup_step'");
		$wpdb->query($sql);
		$location=admin_url()."admin.php?page=honeybadger-it";
    	header("Location: $location");
	}
	function checkAccessTokenExpiry()
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		$sql=$wpdb->prepare("select client_id from ".$wpdb->prefix."honeybadger_oauth_refresh_tokens where expires>%s",date("Y-m-d H:i:s"));
		$results=$wpdb->get_results($sql);
		if(count($results)>0)
			return true;
		else
			return false;
	}
	function testRestAPI()
	{
		global $wpdb;
		if ( ! current_user_can( 'use_honeybadger_api' ) && ! current_user_can( 'manage_options' )) {
		    return;
		}
		$user_id=username_exists("honeybadger".get_current_blog_id());
		if($user_id>0)
		{
			$sql=$wpdb->prepare("select client_id from ".$wpdb->prefix."honeybadger_oauth_clients where user_id=%d",$user_id);
			$result=$wpdb->get_row($sql);
			if(isset($result->client_id))
			{
				$response=$this->simpleCurlRequest("https://".HONEYBADGER_IT_TARGET_SUBDOMAIN.".honeybadger.it/test_oauth.php?client_id=".$result->client_id);
				if(isset($response['response']))
				{
					$result=json_decode($response['response']);
					if(isset($result->status) && $result->status=='ok')
					{
					     return array("status"=>"updated","time"=>$response['time'],"msg"=>"REST API working as expected. Ping(s)");
					}
					else
						return array("status"=>"error","msg"=>"REST API NOT working as expected, or you did not login on the platform for a while");
				}
			}
		}
		else
			return array("status"=>"error","msg"=>"Please setup the HoneyBadger.IT connection first","time"=>0);
	}
}
?>