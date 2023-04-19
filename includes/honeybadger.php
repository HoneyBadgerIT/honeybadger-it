<?php
/**
 * @package    Honeybadger_IT
 * @subpackage Honeybadger_IT/admin
 * @author     Claudiu Maftei <claudiu@honeybadger.it>
 */
require_once WP_PLUGIN_DIR . '/honeybadger-it/constants.php';
class honeybadger{
	
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
	function is_woocommerce_activated(){
		if ( class_exists( 'woocommerce' ) ) { return true; } else { return false; }
	}
	function simpleCurlRequest($url) {
	  $ch = curl_init($url);
	  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	  if(strtolower($this->config->curl_ssl_verify)=="no")
	  	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	  $response = curl_exec($ch);
	  $info = curl_getinfo($ch);
	  curl_close($ch);
	  return array('response'=>$response,'time'=>$info['total_time']);
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
		$response_arr=$this->simpleCurlRequest(get_site_url()."/wp-json/wp/v2");
		$response=$response_arr['response'];
		if($response!="")
			$response=json_decode($response);
		if(isset($response->namespace) && $response->namespace=="wp/v2")
			return true;
		return false;
	}
	function chechIfSelfSigned(){
		$url              = get_site_url();
		$new_url=str_ireplace("https://","",$url);
		$new_url=str_ireplace("http://","",$new_url);
		$new_url="https://".$new_url."/wp-json/wp/v2";
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
		$_POST=stripslashes_deep($_POST);
		$cnt=0;
		foreach($_POST as $config_name => $config_value)
		{
			if($config_name!="" && isset($this->config_front->$config_name))
			{
				$sql="update ".$wpdb->prefix."honeybadger_config set config_value='".esc_sql($config_value)."' where config_name='".esc_sql($config_name)."'";
				if(!$wpdb->query($sql) && $wpdb->last_error !== '')
				{
					return array('status'=>'error','msg'=>__('Error: ','honeyb').htmlspecialchars( $wpdb->last_query, ENT_QUOTES ));
				}
				else
					$cnt++;
			}
		}
		return array('status'=>'updated','msg'=>$cnt." ".__('Settings updated','honeyb'));
	}
	function doTokensCleanup()
	{
		global $wpdb;
		$sql="delete from ".$wpdb->prefix."honeybadger_oauth_access_tokens where expires<'".date("Y-m-d H:i:s")."'";
		$wpdb->query($sql);
		$sql="delete from ".$wpdb->prefix."honeybadger_oauth_refresh_tokens where expires<'".date("Y-m-d H:i:s")."'";
		$wpdb->query($sql);
	}
	function createUserRoleAndUser()
	{
		$this->doTokensCleanup();
		if(!$GLOBALS['wp_roles']->is_role("honeybadger"))
			add_role("honeybadger","HoneyBadger",array("use_honeybadger_api"=>true));
		if(!$GLOBALS['wp_roles']->is_role("honeybadger"))
		{
			echo json_encode(array("status"=>"error", "msg"=>'<div class="hb-notice-error">
			        <p>'.__("Cannot create Honeybadger user role","honeyb").'</p>
			     </div>'));
			exit;
		}
		$user=array(
			"user_pass"=>bin2hex(random_bytes(16)),
			"user_login"=>"honeybadger".get_current_blog_id(),
			"user_nicename"=>"HoneyBadger",
			"user_email"=>bin2hex(random_bytes(8))."@honeybadger.it",
			"description"=>__('This user is used for the HoneyBadger IT communications through the REST API','honeyb'),
			"role"=>"honeybadger",
		);
		if(!username_exists("honeybadger".get_current_blog_id()))
		{
			$user_id=wp_insert_user($user);
			if(is_int($user_id))
			{
				echo json_encode(array("status"=>"ok", "msg"=>'<div class="hb-notice-updated">
				        <p>'.__("Honeybadger user created with success","honeyb").'</p>
				     </div>'));
				exit;
			}
			else
			{
				echo json_encode(array("status"=>"error", "msg"=>'<div class="hb-notice-error">
				        <p>'.__("Cannot create Honeybadger user","honeyb").'</p>
				     </div>'));
				exit;
			}
		}
		if(username_exists("honeybadger".get_current_blog_id()))
			echo json_encode(array("status"=>"ok", "msg"=>'<div class="hb-notice-updated">
			        <p>'.__("Honeybadger user created with success","honeyb").'</p>
			     </div>'));
	}
	function createHoneybadgerConnection()
	{
		global $wpdb;
		//create the client credentials
		$client_id=bin2hex(random_bytes(16));
		$client_secret=bin2hex(random_bytes(16));
		$user_id=username_exists("honeybadger".get_current_blog_id());
		$verify_ssl=$this->config->curl_ssl_verify;
		if(is_int($user_id))
		{
			$sql="select * from ".$wpdb->prefix."honeybadger_oauth_clients where user_id='".esc_sql($user_id)."'";
			$result=$wpdb->get_row($sql);

			if(isset($result->user_id))
			{
				$client_id=$result->client_id;
				$client_secret=$result->client_secret;
			}
			else
			{
				$sql="insert into ".$wpdb->prefix."honeybadger_oauth_clients set
				client_id='".esc_sql($client_id)."',
				client_secret='".esc_sql($client_secret)."',
				redirect_uri='https://".esc_sql(HONEYBADGER_IT_TARGET_SUBDOMAIN).".honeybadger.it/oauth.php',
				user_id='".esc_sql($user_id)."'";
				if(!$wpdb->query($sql) && $wpdb->last_error !== '')
				{
					echo json_encode(array("status"=>"error", "msg"=>'<div class="hb-notice-error">
					        <p>'.__("Cannot create Honeybadger client","honeyb").'</p>
					     </div>'));
					exit;
				}
			}
			$url = get_site_url();
			$domain=str_ireplace("https://","",$url);
			$domain=str_ireplace("http://","",$domain);
			$params="client_id=".$client_id."&client_secret=".$client_secret."&domain=".$domain."&verify_ssl=".$verify_ssl;
			$url="https://".HONEYBADGER_IT_TARGET_SUBDOMAIN.".honeybadger.it/oauth.php";
			$result=$this->doHoneyBadgerCurlRequest($url,$params);
			echo json_encode(array("status"=>"ok", "msg"=>$result['response'].'</div>'));
		}
	}
	function refreshHoneybadgerConnection()
	{
		global $wpdb;
		$this->doTokensCleanup();
		$user_id=username_exists("honeybadger".get_current_blog_id());
		$verify_ssl=$this->config->curl_ssl_verify;
		if(is_int($user_id))
		{
			$this->setSettingValue("is_refresh","1");
			$sql="select * from ".$wpdb->prefix."honeybadger_oauth_clients where user_id='".esc_sql($user_id)."'";
			$result=$wpdb->get_row($sql);
			$client_id="";
			$client_secret="";
			if(isset($result->user_id))
			{
				$client_id=$result->client_id;
				$client_secret=$result->client_secret;
				$sql="delete from ".$wpdb->prefix."honeybadger_oauth_access_tokens where 1";
				$wpdb->query($sql);
				$sql="delete from ".$wpdb->prefix."honeybadger_oauth_refresh_tokens where 1";
				$wpdb->query($sql);
			}
			$url = get_site_url();
			$domain=str_ireplace("https://","",$url);
			$domain=str_ireplace("http://","",$domain);
			$params="client_id=".$client_id."&client_secret=".$client_secret."&domain=".$domain."&verify_ssl=".$verify_ssl;
			$result=$this->doHoneyBadgerCurlRequest("https://".HONEYBADGER_IT_TARGET_SUBDOMAIN.".honeybadger.it/oauth.php",$params);
			echo json_encode(array("status"=>"ok", "msg"=>$result['response'].'</div>'));
		}
	}
	function doHoneyBadgerCurlRequest($url="",$params="") {
		$cookie=bin2hex(random_bytes(16)).".txt";
		//touch($cookie);
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		if(strtolower($this->config->curl_ssl_verify)=="no")
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_POST, 1);
		//curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
		//curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$params);
		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);
		return array('response'=>$response,'time'=>$info['total_time']);
	}
	function setCurrentSetupStep($step=0)
	{
		global $wpdb;
		$sql="update ".$wpdb->prefix."honeybadger_config set config_value='".esc_sql($step)."' where config_name='setup_step'";
		$wpdb->query($sql);
	}
	function setSettingValue($setting="",$value="")
	{
		global $wpdb;
		if($setting!="" && $value!="")
		{
			$sql="update ".$wpdb->prefix."honeybadger_config set config_value='".esc_sql($value)."' where config_name='".esc_sql($setting)."'";
			$wpdb->query($sql);
		}
	}
	function doOauthPingTest()
	{
		global $wpdb;
		$user_id=username_exists("honeybadger".get_current_blog_id());
		if($user_id>0)
		{
			$sql="select client_id from ".$wpdb->prefix."honeybadger_oauth_clients where user_id=".$user_id;
			$result=$wpdb->get_row($sql);
			if(isset($result->client_id))
			{
				$response=$this->simpleCurlRequest("https://".HONEYBADGER_IT_TARGET_SUBDOMAIN.".honeybadger.it/test_oauth.php?client_id=".$result->client_id);
				if(isset($response['response']))
				{
					$result=json_decode($response['response']);
					if(isset($result->status) && $result->status=='ok')
					{
						if(isset($result->account_email) && $result->account_email!="")
						{
							$sql="update ".$wpdb->prefix."honeybadger_config set config_value='".esc_sql($result->account_email)."' where config_name='honeybadger_account_email'";
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
							?>
							<style type="text/css">
								#hb-status-update{
									display: none;
								}
								#hb-one-moment{
									display: block!important;
								}
							</style>
							<script type="text/javascript">
						        <!--
						        window.location.href = "<?php echo get_site_url();?>/wp-admin/admin.php?page=honeybadger-it";
						        //-->
						    </script>
							<?php
							return true;
						}
						else
						{
							echo '<div class="hb-notice-updated">
						        <p>'.__("Honeybadger Oauth setup with success","honeyb").'</p>
						     </div>';
						     return true;
					 	}
					}
				}
			}
		}
		echo '<div class="hb-notice-error">
		        <p>'.__("Error in Oauth REST API communication between your site and HoneyBadger IT","honeyb").'</p>
		     </div>';
	}
	function restartTheSetup()
	{
		global $wpdb;
		$sql="delete from ".$wpdb->prefix."honeybadger_oauth_access_tokens where 1";
		$wpdb->query($sql);
		$sql="delete from ".$wpdb->prefix."honeybadger_oauth_refresh_tokens where 1";
		$wpdb->query($sql);
		$sql="update ".$wpdb->prefix."honeybadger_config set config_value='0' where config_name='setup_step'";
		$wpdb->query($sql);
	}
	function getHbClientId()
	{
		global $wpdb;
		$user_id=username_exists("honeybadger".get_current_blog_id());
		if($user_id>0)
		{
			$sql="select client_id from ".$wpdb->prefix."honeybadger_oauth_clients where user_id=".$user_id;
			$result=$wpdb->get_row($sql);
			if(isset($result->client_id))
				return $result->client_id;
		}
		return false;
	}
	function getHbClientSecret()
	{
		global $wpdb;
		$user_id=username_exists("honeybadger".get_current_blog_id());
		if($user_id>0)
		{
			$sql="select client_secret from ".$wpdb->prefix."honeybadger_oauth_clients where user_id=".$user_id;
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
		$_POST=stripslashes_deep($_POST);
		$email=isset($_POST['hb_email'])?$_POST['hb_email']:"";
		$password=isset($_POST['hb_password'])?$_POST['hb_password']:"";
		$first_name=isset($_POST['hb_first_name'])?$_POST['hb_first_name']:"";
		$last_name=isset($_POST['hb_last_name'])?$_POST['hb_last_name']:"";
		$client_id=$this->getHbClientId();

		if($client_id && $email!="" && $password!="")
		{
			if(!$this->validateEmail($email))
			{
				echo '<div class="notice notice-error is-dismissible">
			        <p>'.__("Please input a valid email address","honeyb").'</p>
			     </div>';
			     return;
			}
			if(!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,20}$/', $password))
			{
				echo '<div class="notice notice-error is-dismissible">
			        <p>'.__("Please input a Password [6 to 20 characters which contain at least one numeric digit, one uppercase and one lowercase letter]","honeyb").'</p>
			     </div>';
			     return;
			}
			//coninue with the creation process
			$woocommerce_prices_include_tax=get_option('woocommerce_prices_include_tax');
			$so_tax_included=1;
			if($woocommerce_prices_include_tax=='no')
				$so_tax_included=0;
			$url="https://".HONEYBADGER_IT_TARGET_SUBDOMAIN.".honeybadger.it/create_new_user.php";
			$params="email=".$email."&password=".$password."&first_name=".$first_name."&last_name=".$last_name."&so_tax_included=".$so_tax_included."&client_id=".$client_id;
			$result=$this->doHoneyBadgerCurlRequest($url,$params);
			$decoded=json_decode($result['response']);
			if(isset($decoded->status) && $decoded->status=="ok")
			{
				echo '<div class="notice notice updated is-dismissible">
			        <p>'.__("Your HoneyBadger IT account created with success! Please check your email and verify your email address in order for the account to be activated.","honeyb").'</p>
			     </div>';
			    $sql="update ".$wpdb->prefix."honeybadger_config set config_value='3' where config_name='setup_step'";
				$wpdb->query($sql);
				$sql="update ".$wpdb->prefix."honeybadger_config set config_value='".esc_sql($email)."' where config_name='honeybadger_account_email'";
				$wpdb->query($sql);
				$location=get_site_url()."/wp-admin/admin.php?page=honeybadger-it&msg=created";
    			header("Location: $location");
			}
			else
			{
				$reason="Something went wrong, please try again later";
				if(isset($decoded->msg))
					$reason=$decoded->msg;
				echo '<div class="notice notice-error is-dismissible">
		        	<p>'.__($reason,"honeyb").'</p>
		     	</div>';
			}
		}
		else
		{
			echo '<div class="notice notice-error is-dismissible">
		        <p>'.__("Something is wrong, please reinstall the plugin and restart the setup process","honeyb").'</p>
		     </div>';
		}
	}
	function changeClientIdAndSecret()
	{
		global $wpdb;
		$_POST=stripslashes_deep($_POST);
		$client_id=isset($_POST['client_id'])?$_POST['client_id']:"";
		$client_secret=isset($_POST['client_secret'])?$_POST['client_secret']:"";

		if($client_id!="" && $client_secret!="")
		{
			$old_client_id=$this->getHbClientId();
			$sql="update ".$wpdb->prefix."honeybadger_oauth_clients set 
			client_id='".esc_sql($client_id)."',
			client_secret='".esc_sql($client_secret)."'
			where
			client_id='".esc_sql($old_client_id)."'";
			if((!$wpdb->query($sql) && $wpdb->last_error !== '') || $wpdb->rows_affected==0 )
				return array('status'=>'error','msg'=>__('Something went wrong when updating the database, please start the setup first and try again','honeyb'));
			else
			{
				$this->restartTheSetup();
				return array('status'=>'updated','msg'=>__('Credentials updated with success, please redo the setup again now','honeyb'));
			}
		}
		else
			return array('status'=>'error','msg'=>__('Something is wrong, please try again','honeyb'));
	}
	function revokeHbAccess()
	{
		global $wpdb;
		$sql="delete from ".$wpdb->prefix."honeybadger_oauth_access_tokens where 1";
		$wpdb->query($sql);
		$sql="delete from ".$wpdb->prefix."honeybadger_oauth_refresh_tokens where 1";
		$wpdb->query($sql);
		$sql="update ".$wpdb->prefix."honeybadger_config set config_value='4' where config_name='setup_step'";
		$wpdb->query($sql);
		$location=get_site_url()."/wp-admin/admin.php?page=honeybadger-it";
    	header("Location: $location");
	}
	function checkAccessTokenExpiry()
	{
		global $wpdb;

		$sql="select client_id from ".$wpdb->prefix."honeybadger_oauth_refresh_tokens where expires>'".date("Y-m-d H:i:s")."'";
		$results=$wpdb->get_results($sql);
		if(count($results)>0)
			return true;
		else
			return false;
	}
	function testRestAPI()
	{
		global $wpdb;
		$user_id=username_exists("honeybadger".get_current_blog_id());
		if($user_id>0)
		{
			$sql="select client_id from ".$wpdb->prefix."honeybadger_oauth_clients where user_id=".$user_id;
			$result=$wpdb->get_row($sql);
			if(isset($result->client_id))
			{
				$response=$this->simpleCurlRequest("https://".HONEYBADGER_IT_TARGET_SUBDOMAIN.".honeybadger.it/test_oauth.php?client_id=".$result->client_id);
				if(isset($response['response']))
				{
					$result=json_decode($response['response']);
					if(isset($result->status) && $result->status=='ok')
					{
					     return array("status"=>"updated","msg"=>__("REST API working as expected. Ping: ".$response['time']."s","honeyb"));
					}
					else
						return array("status"=>"error","msg"=>__("REST API NOT working as expected","honeyb"));
				}
			}
		}
	}
}
?>