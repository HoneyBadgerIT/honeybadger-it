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
  $state=isset($_GET['state'])?sanitize_text_field($_GET['state']):"";
  $response_type=isset($_GET['response_type'])?sanitize_text_field($_GET['response_type']):"";
  $approval_prompt=isset($_GET['approval_prompt'])?sanitize_text_field($_GET['approval_prompt']):"";
  $redirect_uri=isset($_GET['redirect_uri'])?sanitize_url($_GET['redirect_uri']):"";
  $client_id=isset($_GET['client_id'])?sanitize_text_field($_GET['client_id']):"";
  $nonce=wp_create_nonce( 'honeybadger_it_oauth_nonce' );
  echo '
  <form method="post" action="'.esc_url(get_rest_url().'honeybadger-it/v1/oauth/?action=get_oauth2_authorize_content_approval&nonce='.rawurlencode($nonce).'&state='.rawurlencode($state).'&response_type='.rawurlencode($response_type).'&approval_prompt='.rawurlencode($approval_prompt).'&redirect_uri='.rawurlencode($redirect_uri).'&client_id='.rawurlencode($client_id)).'">
    <label>'.esc_html__("Click the below button to allow HoneyBadger IT to access your shop using the Oauth2 protocol",'honeybadger-it').'</label><br />
    <input type="hidden" name="authorized" value="yes" />
    <input type="submit" class="button wp-generate-pw hide-if-no-js leftmarginme margintopme" value="'.esc_attr(__("I authorize HoneyBadger IT to access my shop",'honeybadger-it')).'">
  </form>';
  exit;
}

// print the authorization code if the user has authorized your client
$authorized=isset($_POST['authorized'])?sanitize_text_field($_POST['authorized']):"";
$redirect_uri=isset($_GET['redirect_uri'])?sanitize_url($_GET['redirect_uri']):"";
$state=isset($_GET['state'])?sanitize_text_field($_GET['state']):"";
$client_id=isset($_GET['client_id'])?sanitize_text_field($_GET['client_id']):"";
$is_authorized = ($authorized === 'yes');
$server->handleAuthorizeRequest($request, $response, $is_authorized);
if ($is_authorized) {
  // this is only here so that you get to see your code in the cURL request. Otherwise, we'd redirect back to the client
  $code = substr($response->getHttpHeader('Location'), strpos($response->getHttpHeader('Location'), 'code=')+5, 40);
  
  $return_url=esc_url($redirect_uri."&code=".rawurlencode($code)."&state=".rawurlencode($state)."&client_id=".rawurlencode($client_id));
  header('Location: ' . $return_url);
}
$response->send();
?>