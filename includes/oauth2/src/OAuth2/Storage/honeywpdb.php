<?php

namespace OAuth2\Storage;

use OAuth2\OpenID\Storage\UserClaimsInterface;
use OAuth2\OpenID\Storage\AuthorizationCodeInterface as OpenIDAuthorizationCodeInterface;
use InvalidArgumentException;

/**
 * Simple WordPress Database Object Layer
 *
 * NOTE: This class is a modified version of the PDO object by Claudiu Maftei
 *
 * @author Claudiu Maftei <claudiu@honeybadger.it>
 * @org-author Brent Shaffer <bshafs at gmail dot com>
 */
class honeywpdb implements
    AuthorizationCodeInterface,
    AccessTokenInterface,
    ClientCredentialsInterface,
    UserCredentialsInterface,
    RefreshTokenInterface,
    JwtBearerInterface,
    ScopeInterface,
    PublicKeyInterface,
    UserClaimsInterface,
    OpenIDAuthorizationCodeInterface{

	protected $db;
	protected $config;
	public function __construct($config = array())
    {
    	global $wpdb;
		$this->db     = $wpdb;
        

        $this->config = array_merge(array(
            'client_table' => $this->db->prefix.'honeybadger_oauth_clients',
            'access_token_table' => $this->db->prefix.'honeybadger_oauth_access_tokens',
            'refresh_token_table' => $this->db->prefix.'honeybadger_oauth_refresh_tokens',
            'code_table' => $this->db->prefix.'honeybadger_oauth_authorization_codes',
            'user_table' => $this->db->prefix.'honeybadger_oauth_users',
            'jwt_table'  => $this->db->prefix.'honeybadger_oauth_jwt',
            'jti_table'  => $this->db->prefix.'honeybadger_oauth_jti',
            'scope_table'  => $this->db->prefix.'honeybadger_oauth_scopes',
            'public_key_table'  => $this->db->prefix.'honeybadger_oauth_public_keys',
        ), $config);
    }
    /**
     * @param string $client_id
     * @param null|string $client_secret
     * @return bool
     */
    public function checkClientCredentials($client_id, $client_secret = null)
    {
    	$sql="select * from ".$this->config['client_table']." where client_id='".esc_sql($client_id)."'";
        $result=$this->db->get_row($sql);
    	if(isset($result->client_secret) && $result->client_secret==$client_secret)
    		return true;
        return false;
    }
    /**
     * @param string $client_id
     * @return bool
     */
    public function isPublicClient($client_id)
    {
    	$sql="select * from ".$this->config['client_table']." where client_id='".esc_sql($client_id)."'";
        $result=$this->db->get_row($sql);
    	if(isset($result->client_id) && !empty($result->client_id))
    		return true;
        return false;
    }
    /**
     * @param string $client_id
     * @return array|mixed
     */
    public function getClientDetails($client_id)
    {
    	$sql="select * from ".$this->config['client_table']." where client_id='".esc_sql($client_id)."'";
        $result=$this->db->get_row($sql);
    	if(isset($result->client_id) && !empty($result->client_id))
        	return (array) $result;
        return false;
    }
    /**
     * @param string $client_id
     * @param null|string $client_secret
     * @param null|string $redirect_uri
     * @param null|array  $grant_types
     * @param null|string $scope
     * @param null|string $user_id
     * @return bool
     */
    public function setClientDetails($client_id, $client_secret = null, $redirect_uri = null, $grant_types = null, $scope = null, $user_id = null)
    {
        // if it exists, update it.
        if ($this->getClientDetails($client_id)) {
        	$sql="update ".$this->config['client_table']." set client_secret='".esc_sql($client_secret)."', redirect_uri='".esc_sql($redirect_uri)."', grant_types='".esc_sql($grant_types)."', scope='".esc_sql($scope)."', user_id='".esc_sql($user_id)."', where client_id='".esc_sql($client_id)."'";
        } else {
        	$sql="insert into ".$this->config['client_table']." set
        	client_id='".esc_sql($client_id)."',
        	client_secret='".esc_sql($client_secret)."',
        	redirect_uri='".esc_sql($redirect_uri)."',
        	grant_types='".esc_sql($grant_types)."',
        	scope='".esc_sql($scope)."',
        	user_id='".esc_sql($user_id)."'
        	";
        }
        if(!$this->db->query($sql) && $this->db->last_error !== '')
        	return false;
        return true;
    }
    /**
     * @param $client_id
     * @param $grant_type
     * @return bool
     */
    public function checkRestrictedGrantType($client_id, $grant_type)
    {
        $details = $this->getClientDetails($client_id);
        if (isset($details['grant_types'])) {
            $grant_types = explode(' ', $details['grant_types']);

            return in_array($grant_type, (array) $grant_types);
        }

        // if grant_types are not defined, then none are restricted
        return true;
    }
    /**
     * @param string $access_token
     * @return array|bool|mixed|null
     */
    public function getAccessToken($access_token)
    {
    	$sql="select * from ".$this->config['access_token_table']." where access_token='".esc_sql($access_token)."'";
    	$token = $this->db->get_row( $sql, ARRAY_A );

        if (isset($token['expires'])) {
            // convert date string back to timestamp
            $token['expires'] = strtotime($token['expires']);
        }

        return $token;
    }
    /**
     * @param string $access_token
     * @param mixed  $client_id
     * @param mixed  $user_id
     * @param int    $expires
     * @param string $scope
     * @return bool
     */
    public function setAccessToken($access_token, $client_id, $user_id, $expires, $scope = null)
    {
        // convert expires to datestring
        $expires = date('Y-m-d H:i:s', $expires);
        $this->db->query("delete from ".$this->config['access_token_table']." where 1");
        // if it exists, update it.
        if ($this->getAccessToken($access_token)) {
        	$sql="update ".$this->config['access_token_table']." set
        	client_id='".esc_sql($client_id)."',
        	expires='".esc_sql($expires)."',
        	user_id='".esc_sql($user_id)."',
        	scope='".esc_sql($scope)."'
        	where
        	access_token='".esc_sql($access_token)."'";
        } else {
        	$sql="insert into ".$this->config['access_token_table']." set
        	access_token='".esc_sql($access_token)."',
        	client_id='".esc_sql($client_id)."',
        	expires='".esc_sql($expires)."',
        	user_id='".esc_sql($user_id)."',
        	scope='".esc_sql($scope)."'";
        }

        if(!$this->db->query($sql) && $this->db->last_error !== '')
        	return false;
        return true;
    }
    /**
     * @param $access_token
     * @return bool
     */
    public function unsetAccessToken($access_token)
    {
    	$sql="delete from ".$this->config['access_token_table']." where access_token='".esc_sql($access_token)."'";
    	if(!$this->db->query($sql) && $this->db->last_error !== '')
        	return false;
        return true;
    }
    /* OAuth2\Storage\AuthorizationCodeInterface */
    /**
     * @param string $code
     * @return mixed
     */
    public function getAuthorizationCode($code)
    {
    	$sql="select * from ".$this->config['code_table']." where authorization_code='".esc_sql($code)."'";
    	$code = $this->db->get_row( $sql, ARRAY_A );
        if (isset($code['expires'])) {
            // convert date string back to timestamp
            $code['expires'] = strtotime($code['expires']);
        }
        return $code;
    }
    /**
     * @param string $code
     * @param mixed  $client_id
     * @param mixed  $user_id
     * @param string $redirect_uri
     * @param int    $expires
     * @param string $scope
     * @param string $id_token
     * @return bool|mixed
     */
    public function setAuthorizationCode($code, $client_id, $user_id, $redirect_uri, $expires, $scope = null, $id_token = null)
    {
        if (func_num_args() > 6) {
            // we are calling with an id token
            return call_user_func_array(array($this, 'setAuthorizationCodeWithIdToken'), func_get_args());
        }
        // convert expires to datestring
        $expires = date('Y-m-d H:i:s', $expires);

        // if it exists, update it.
        if ($this->getAuthorizationCode($code)) {
        	$sql="update ".$this->config['code_table']." set 
        	client_id='".esc_sql($client_id)."',
        	user_id='".esc_sql($user_id)."',
        	redirect_uri='".esc_sql($redirect_uri)."',
        	expires='".esc_sql($expires)."',
        	scope='".esc_sql($scope)."'
        	where
        	authorization_code='".esc_sql($code)."'
        	";
        } else {
        	$sql="insert into ".$this->config['code_table']." set 
        	authorization_code='".esc_sql($code)."',
        	client_id='".esc_sql($client_id)."',
        	user_id='".esc_sql($user_id)."',
        	redirect_uri='".esc_sql($redirect_uri)."',
        	expires='".esc_sql($expires)."',
        	scope='".esc_sql($scope)."'";
        }

        if(!$this->db->query($sql) && $this->db->last_error !== '')
        	return false;
        return true;
    }
    /**
     * @param string $code
     * @param mixed  $client_id
     * @param mixed  $user_id
     * @param string $redirect_uri
     * @param string $expires
     * @param string $scope
     * @param string $id_token
     * @return bool
     */
    private function setAuthorizationCodeWithIdToken($code, $client_id, $user_id, $redirect_uri, $expires, $scope = null, $id_token = null)
    {      
        // convert expires to datestring
        $expires = date('Y-m-d H:i:s', $expires);

        // if it exists, update it.
        if ($this->getAuthorizationCode($code)) {
        	$sql="update ".$this->config['code_table']." set 
        	client_id='".esc_sql($client_id)."',
        	user_id='".esc_sql($user_id)."',
        	redirect_uri='".esc_sql($redirect_uri)."',
        	expires='".esc_sql($expires)."',
        	scope='".esc_sql($scope)."',
        	id_token='".esc_sql($id_token)."'
        	where
        	authorization_code='".esc_sql($id_token)."'";
        } else {
            $sql="insert into ".$this->config['code_table']." set 
        	authorization_code='".esc_sql($code)."',
        	client_id='".esc_sql($client_id)."',
        	user_id='".esc_sql($user_id)."',
        	redirect_uri='".esc_sql($redirect_uri)."',
        	expires='".esc_sql($expires)."',
        	scope='".esc_sql($scope)."',
        	id_token='".esc_sql($id_token)."'";
        }

        if(!$this->db->query($sql) && $this->db->last_error !== '')
        	return false;
        return true;
    }
    /**
     * @param string $code
     * @return bool
     */
    public function expireAuthorizationCode($code)
    {
    	$sql="delete from ".$this->config['code_table']." where authorization_code='".esc_sql($code)."'";
    	if(!$this->db->query($sql) && $this->db->last_error !== '')
        	return false;
        return true;
    }
    /**
     * @param string $username
     * @param string $password
     * @return bool
     */
    public function checkUserCredentials($username, $password)
    {
        if ($user = $this->getUser($username)) {
            return $this->checkPassword($user, $password);
        }

        return false;
    }
    /**
     * @param string $username
     * @return array|bool
     */
    public function getUserDetails($username)
    {
        return $this->getUser($username);
    }
    /**
     * @param string $refresh_token
     * @return bool|mixed
     */
    public function getRefreshToken($refresh_token)
    {
    	$sql="select * from ".$this->config['refresh_token_table']." where refresh_token='".esc_sql($refresh_token)."'";

        $token = $this->db->get_row( $sql, ARRAY_A );
        if (isset($token['expires'])) {
            // convert expires to epoch time
            $token['expires'] = strtotime($token['expires']);
        }

        return $token;
    }
    /**
     * @param mixed  $user_id
     * @param string $claims
     * @return array|bool
     */
    public function getUserClaims($user_id, $claims)
    {
        if (!$userDetails = $this->getUserDetails($user_id)) {
            return false;
        }

        $claims = explode(' ', trim($claims));
        $userClaims = array();

        // for each requested claim, if the user has the claim, set it in the response
        $validClaims = explode(' ', self::VALID_CLAIMS);
        foreach ($validClaims as $validClaim) {
            if (in_array($validClaim, $claims)) {
                if ($validClaim == 'address') {
                    // address is an object with subfields
                    $userClaims['address'] = $this->getUserClaim($validClaim, $userDetails['address'] ?: $userDetails);
                } else {
                    $userClaims = array_merge($userClaims, $this->getUserClaim($validClaim, $userDetails));
                }
            }
        }

        return $userClaims;
    }
    /**
     * @param string $claim
     * @param array  $userDetails
     * @return array
     */
    protected function getUserClaim($claim, $userDetails)
    {
        $userClaims = array();
        $claimValuesString = constant(sprintf('self::%s_CLAIM_VALUES', strtoupper($claim)));
        $claimValues = explode(' ', $claimValuesString);

        foreach ($claimValues as $value) {
            $userClaims[$value] = isset($userDetails[$value]) ? $userDetails[$value] : null;
        }

        return $userClaims;
    }
    /**
     * @param string $refresh_token
     * @param mixed  $client_id
     * @param mixed  $user_id
     * @param string $expires
     * @param string $scope
     * @return bool
     */
    public function setRefreshToken($refresh_token, $client_id, $user_id, $expires, $scope = null)
    {
        // convert expires to datestring
        $expires = date('Y-m-d H:i:s', $expires);

        $sql="insert into ".$this->config['refresh_token_table']." set
        refresh_token='".esc_sql($refresh_token)."',
        client_id='".esc_sql($client_id)."',
        user_id='".esc_sql($user_id)."',
        expires='".esc_sql($expires)."',
        scope='".esc_sql($scope)."'
        ";
        if(!$this->db->query($sql) && $this->db->last_error !== '')
        	return false;
        return true;
    }
    /**
     * @param string $refresh_token
     * @return bool
     */
    public function unsetRefreshToken($refresh_token)
    {
    	$sql="delete from ".$this->config['refresh_token_table']." where refresh_token='".esc_sql($refresh_token)."'";
    	if(!$this->db->query($sql) && $this->db->last_error !== '')
        	return false;
        return true;
    }
    /**
     * plaintext passwords are bad!  Override this for your application
     *
     * @param array $user
     * @param string $password
     * @return bool
     */
    protected function checkPassword($user, $password)
    {
        return $user['password'] == $this->hashPassword($password);
    }
    // use a secure hashing algorithm when storing passwords. Override this for your application
    protected function hashPassword($password)
    {
        return sha1($password);
    }
    /**
     * @param string $username
     * @return array|bool
     */
    public function getUser($username)
    {
    	$sql="select * from ".$this->config['user_table']." where username='".esc_sql($username)."'";
        $userInfo = $this->db->get_row( $sql, ARRAY_A );
        if (!$userInfo['username'])
            return false;

        // the default behavior is to use "username" as the user_id
        return array_merge(array(
            'user_id' => $username
        ), $userInfo);
    }
    /**
     * plaintext passwords are bad!  Override this for your application
     *
     * @param string $username
     * @param string $password
     * @param string $firstName
     * @param string $lastName
     * @return bool
     */
    public function setUser($username, $password, $firstName = null, $lastName = null)
    {
        // do not store in plaintext
        $password = $this->hashPassword($password);

        // if it exists, update it.
        if ($this->getUser($username)) {
        	$sql="update ".$this->config['user_table']." set 
        	password='".esc_sql($password)."',
        	first_name='".esc_sql($firstName)."',
        	last_name='".esc_sql($lastName)."'
        	where
        	username='".esc_sql($username)."'
        	";
        } else {
        	$sql="insert into  ".$this->config['user_table']." set 
        	username='".esc_sql($username)."',
        	password='".esc_sql($password)."',
        	first_name='".esc_sql($firstName)."',
        	last_name='".esc_sql($lastName)."'";
        }

        if(!$this->db->query($sql) && $this->db->last_error !== '')
        	return false;
        return true;
    }
    /**
     * @param string $scope
     * @return bool
     */
    public function scopeExists($scope)
    {
        $scope = explode(' ', $scope);
        $whereIn = implode(',', array_fill(0, count($scope), '?'));
        $sql="select count(scope) as count from ".$this->config['scope_table']."where scope in (".$whereIn.")";
        $result = $this->db->get_row( $sql, ARRAY_A );

        if (isset($result['count'])) {
            return $result['count'] == count($scope);
        }

        return false;
    }
    /**
     * @param mixed $client_id
     * @return null|string
     */
    public function getDefaultScope($client_id = null)
    {
    	$sql="select scope from ".$this->config['scope_table']." where is_default is null";
        $results=$this->db->get_results($sql);
        $result=(array)$results;
        if ($result) {
            $defaultScope = array_map(function ($row) {
                $row=(array)$row;
                return $row['scope'];
            }, $result);

            return implode(' ', $defaultScope);
        }

        return null;
    }
    /**
     * @param mixed $client_id
     * @param $subject
     * @return string
     */
    public function getClientKey($client_id, $subject)
    {
    	$sql="select public_key from ".$this->config['jwt_table']." where client_id='".esc_sql($client_id)."' and subject='".esc_sql($subject)."'";
    	$result=$this->db->get_row($sql);
    	if(isset($result->public_key))
    		return $result->public_key;
    	return false;
    }
    /**
     * @param mixed $client_id
     * @return bool|null
     */
    public function getClientScope($client_id)
    {
        if (!$clientDetails = $this->getClientDetails($client_id)) {
            return false;
        }

        if (isset($clientDetails['scope'])) {
            return $clientDetails['scope'];
        }

        return null;
    }
    /**
     * @param mixed $client_id
     * @param $subject
     * @param $audience
     * @param $expires
     * @param $jti
     * @return array|null
     */
    public function getJti($client_id, $subject, $audience, $expires, $jti)
    {
    	$sql="select * from ".$this->config['jti_table']." where
    	issuer='".esc_sql($client_id)."' and 
    	subject='".esc_sql($subject)."' and 
    	audience='".esc_sql($audience)."' and 
    	expires='".esc_sql($expires)."' and 
    	jti='".esc_sql($jti)."'";
        $result = $this->db->get_row( $sql, ARRAY_A );
        if (isset($result['issuer'])) {
            return array(
                'issuer' => $result['issuer'],
                'subject' => $result['subject'],
                'audience' => $result['audience'],
                'expires' => $result['expires'],
                'jti' => $result['jti'],
            );
        }

        return null;
    }
    /**
     * @param mixed $client_id
     * @param $subject
     * @param $audience
     * @param $expires
     * @param $jti
     * @return bool
     */
    public function setJti($client_id, $subject, $audience, $expires, $jti)
    {
    	$sql="insert into ".$this->config['jti_table']." set
		issuer='".esc_sql($client_id)."',
		subject='".esc_sql($subject)."',
		audience='".esc_sql($audience)."',
		expires='".esc_sql($expires)."',
		jti='".esc_sql($jti)."'";
        if(!$this->db->query($sql) && $this->db->last_error !== '')
        	return false;
        return true;
    }
    /**
     * @param mixed $client_id
     * @return mixed
     */
    public function getPublicKey($client_id = null)
    {
		$sql="select public_key from ".$this->config['public_key_table']." where
		client_id='".esc_sql($client_id)."' or
		client_id IS NULL
		ORDER BY client_id IS NOT NULL DESC";
        $result = $this->db->get_row( $sql, ARRAY_A );
        if (isset($result['public_key']))
            return $result['public_key'];
        return false;
    }
    /**
     * @param mixed $client_id
     * @return mixed
     */
    public function getPrivateKey($client_id = null)
    {
    	$sql="select private_key from ".$this->config['public_key_table']." where 
    	client_id='".esc_sql($client_id)."' or
    	client_id IS NULL
    	ORDER BY client_id IS NOT NULL DESC";
        $result = $this->db->get_row( $sql, ARRAY_A );
        if (isset($result['private_key']))
            return $result['private_key'];
        return false;
    }
    /**
     * @param mixed $client_id
     * @return string
     */
    public function getEncryptionAlgorithm($client_id = null)
    {
    	$sql="select encryption_algorithm from ".$this->config['public_key_table']." where 
    	client_id='".esc_sql($client_id)."' or
    	client_id IS NULL ORDER BY client_id IS NOT NULL DESC";
        $result = $this->db->get_row( $sql, ARRAY_A );
        if (isset($result['encryption_algorithm']))
            return $result['encryption_algorithm'];
        return 'RS256';
    }
    /**
     * DDL to create OAuth2 database and tables for PDO storage
     *
     * @see https://github.com/dsquier/oauth2-server-php-mysql
     *
     * @param string $dbName
     * @return string
     */
    public function getBuildSql($dbName = 'oauth2_server_php')
    {
        $sql = "
        CREATE TABLE {$this->config['client_table']} (
          client_id             VARCHAR(80)   NOT NULL,
          client_secret         VARCHAR(80),
          redirect_uri          VARCHAR(2000),
          grant_types           VARCHAR(80),
          scope                 VARCHAR(4000),
          user_id               VARCHAR(80),
          PRIMARY KEY (client_id)
        );

            CREATE TABLE {$this->config['access_token_table']} (
              access_token         VARCHAR(40)    NOT NULL,
              client_id            VARCHAR(80)    NOT NULL,
              user_id              VARCHAR(80),
              expires              TIMESTAMP      NOT NULL,
              scope                VARCHAR(4000),
              PRIMARY KEY (access_token)
            );

            CREATE TABLE {$this->config['code_table']} (
              authorization_code  VARCHAR(40)    NOT NULL,
              client_id           VARCHAR(80)    NOT NULL,
              user_id             VARCHAR(80),
              redirect_uri        VARCHAR(2000),
              expires             TIMESTAMP      NOT NULL,
              scope               VARCHAR(4000),
              id_token            VARCHAR(1000),
              PRIMARY KEY (authorization_code)
            );

            CREATE TABLE {$this->config['refresh_token_table']} (
              refresh_token       VARCHAR(40)    NOT NULL,
              client_id           VARCHAR(80)    NOT NULL,
              user_id             VARCHAR(80),
              expires             TIMESTAMP      NOT NULL,
              scope               VARCHAR(4000),
              PRIMARY KEY (refresh_token)
            );

            CREATE TABLE {$this->config['user_table']} (
              username            VARCHAR(80),
              password            VARCHAR(80),
              first_name          VARCHAR(80),
              last_name           VARCHAR(80),
              email               VARCHAR(80),
              email_verified      BOOLEAN,
              scope               VARCHAR(4000)
            );

            CREATE TABLE {$this->config['scope_table']} (
              scope               VARCHAR(80)  NOT NULL,
              is_default          BOOLEAN,
              PRIMARY KEY (scope)
            );

            CREATE TABLE {$this->config['jwt_table']} (
              client_id           VARCHAR(80)   NOT NULL,
              subject             VARCHAR(80),
              public_key          VARCHAR(2000) NOT NULL
            );

            CREATE TABLE {$this->config['jti_table']} (
              issuer              VARCHAR(80)   NOT NULL,
              subject             VARCHAR(80),
              audiance            VARCHAR(80),
              expires             TIMESTAMP     NOT NULL,
              jti                 VARCHAR(2000) NOT NULL
            );

            CREATE TABLE {$this->config['public_key_table']} (
              client_id            VARCHAR(80),
              public_key           VARCHAR(2000),
              private_key          VARCHAR(2000),
              encryption_algorithm VARCHAR(100) DEFAULT 'RS256'
            )
        ";

        return $sql;
    }
}