<?php
require_once(HONEYBADGER_PLUGIN_PATH."includes/honeybadger.php");
$honeybadger=new HoneyBadgerIT\honeybadger;

// Autoloading (composer is preferred, but for this example let's just do this)
require_once(__DIR__.'/src/OAuth2/Autoloader.php');
OAuth2\Autoloader::register();

// $dsn is the Data Source Name for your database, for exmaple "mysql:dbname=my_oauth2_db;host=localhost"
$storage = new OAuth2\Storage\honeywpdb();

// Pass a storage object or array of storage objects to the OAuth2 server class
$config     = array(
	'access_lifetime'                   => $honeybadger->config->access_lifetime,//access token 1 day
	'refresh_token_lifetime'            => $honeybadger->config->refresh_token_lifetime,//refresh token 4 weeks
	'always_issue_new_refresh_token'    => true,
	'unset_refresh_token_after_use'		=> true,
);
$server = new OAuth2\Server($storage,$config);

// Add the "Client Credentials" grant type (it is the simplest of the grant types)
$server->addGrantType(new OAuth2\GrantType\ClientCredentials($storage),$config);

// Add the "Authorization Code" grant type (this is where the oauth magic happens)
$server->addGrantType(new OAuth2\GrantType\AuthorizationCode($storage),$config);

$server->addGrantType(new OAuth2\GrantType\RefreshToken($storage, [
    'always_issue_new_refresh_token' => true,
    'unset_refresh_token_after_use'  => true
]));
?>