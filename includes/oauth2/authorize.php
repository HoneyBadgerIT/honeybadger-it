<?php
// include our OAuth2 Server object
require_once __DIR__.'/server.php';

$request = OAuth2\Request::createFromGlobals();
$response = new OAuth2\Response();

// validate the authorize request
if (!$server->validateAuthorizeRequest($request, $response)) {
    $response->send();
    die;
}

// display an authorization form
if (empty($_POST)) {
  $state=isset($_GET['state'])?$_GET['state']:"";
  $response_type=isset($_GET['response_type'])?$_GET['response_type']:"";
  $approval_prompt=isset($_GET['approval_prompt'])?$_GET['approval_prompt']:"";
  $redirect_uri=isset($_GET['redirect_uri'])?$_GET['redirect_uri']:"";
  $client_id=isset($_GET['client_id'])?$_GET['client_id']:"";
  exit('
<form method="post" action="'.get_site_url().'/wp-content/plugins/honeybadger-it/includes/oauth2/authorize.php?state='.$state.'&response_type='.$response_type.'&approval_prompt='.$approval_prompt.'&redirect_uri='.$redirect_uri.'&client_id='.$client_id.'">
  <label>'.__("Click the below button to allow HoneyBadger IT to access your shop using the Oauth2 protocol","honeyb").'</label><br />
  <input type="hidden" name="authorized" value="yes" />
  <input type="submit" class="button wp-generate-pw hide-if-no-js" value="'.esc_attr(__("I authorize HoneyBadger IT to access my shop","honeyb")).'">
</form>');
}

// print the authorization code if the user has authorized your client
$is_authorized = ($_POST['authorized'] === 'yes');
$server->handleAuthorizeRequest($request, $response, $is_authorized);
if ($is_authorized) {
  // this is only here so that you get to see your code in the cURL request. Otherwise, we'd redirect back to the client
  $code = substr($response->getHttpHeader('Location'), strpos($response->getHttpHeader('Location'), 'code=')+5, 40);
  
  /*
  $data="";
  $data.=print_r($_POST,true);
  $data.=print_r($_GET,true);
  $data.=print_r($_REQUEST,true);
  echo $data;
  */
  $return_url=$_GET['redirect_uri']."&code=".$code."&state=".$_GET['state']."&client_id=".$_GET['client_id'];
  header('Location: ' . $return_url);
  //exit("SUCCESS! Authorization Code: $code");
}
$response->send();
?>