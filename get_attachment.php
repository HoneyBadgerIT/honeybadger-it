<?php
/**
 * @package    Honeybadger_IT
 * @subpackage Honeybadger_IT/admin
 * @author     Claudiu Maftei <claudiu@honeybadger.it>
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly   
$path=isset($_GET['path'])?sanitize_text_field($_GET['path']):"";
$key=isset($_GET['key'])?sanitize_text_field($_GET['key']):"";
if($key!="" && $path!="")
{
	$url = get_site_url();
	$domain=str_ireplace("https://","",$url);
	$domain=str_ireplace("http://","",$domain);
	$attachments_path=HONEYBADGER_UPLOADS_PATH."attachments";
	$file_name=basename($path);
	$file_folder=str_ireplace("/".basename($path),"",$path);
	$possible_files=glob($attachments_path."/".$file_folder."/*_".$file_name);
	$file_hash="";
	if(is_array($possible_files))
	{
		foreach($possible_files as $possible_file)
			$file_hash=honeybadger_it_plugin_get_md5_from_filename(basename($possible_file));
	}
	if($file_hash!="")
	{
		$key_chk=md5($domain.$file_hash."alsdjlk -9as7s;odifjpoiwer");
		if($key_chk==$key)
		{
			$filepath=$attachments_path."/".$file_folder."/".$file_hash."_".$file_name;
			if (file_exists($filepath))
			{
				$mime=mime_content_type($filepath);
				header('Content-Description: File Transfer');
				header('Content-Type: '.$mime);
				header('Content-Disposition: inline; filename="' . honeybadger_it_plugin_remove_md5_from_filename(basename($filepath)) . '"');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . filesize($filepath));
				flush();
				readfile($filepath);
				die();
		    }
		    else
		    {
				http_response_code(404);
				die();
		    }
		}
	}
}
function honeybadger_it_plugin_is_valid_md5($md5="")
{
    return preg_match('/^[a-f0-9]{32}$/', $md5);
}
function honeybadger_it_plugin_remove_md5_from_filename($file="")
{
	if($file!="")
	{
		$tmp=explode("_",$file);
		$new_tmp=array();
		if(is_array($tmp) && count($tmp)>1 && honeybadger_it_plugin_is_valid_md5($tmp[0]))
		{
			for($i=1;$i<count($tmp);$i++)
				$new_tmp[]=$tmp[$i];
		}
		if(count($new_tmp)>0)
			return implode("_",$new_tmp);
	}
	return $file;
}
function honeybadger_it_plugin_get_md5_from_filename($file="")
{
	if($file!="")
	{
		$tmp=explode("_",$file);
		$new_tmp=array();
		if(is_array($tmp) && count($tmp)>1 && honeybadger_it_plugin_is_valid_md5($tmp[0]))
			return $tmp[0];
	}
	return $file;
}
?>