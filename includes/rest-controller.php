<?php
/**
 * @package    Honeybadger_IT
 * @subpackage Honeybadger_IT/admin
 * @author     Claudiu Maftei <claudiu@honeybadger.it>
 */
namespace HoneyBadgerIT;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly   
class HoneyBadger_REST_Controller {
 
    public function __construct() {
        $this->namespace     = '/honeybadger-it-controller/v1';
        $this->resource_name = 'method';
    }
 
    // Register our routes.
    public function register_routes() {
        register_rest_route( $this->namespace, '/' . $this->resource_name, array(
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'get_methods' ),
                'permission_callback' => array( $this, 'get_permissions_check' ),
            ),
            'schema' => array( $this, 'get_methods_schema' ),
        ) );
    }
    public function get_permissions_check( $request ) {
        if ( ! current_user_can( 'use_honeybadger_api' ) ) {
            return new WP_Error( 'rest_forbidden', esc_html__( 'You cannot view the post resource.' ), array( 'status' => $this->authorization_status_code() ) );
        }
        return true;
    }
    public function get_methods( $request ) {
        $parameters = $request->get_params();
        if(isset($parameters['method']))
        {
            require_once HONEYBADGER_PLUGIN_PATH . '/includes/honeybadger-api.php';
            $honeybadger=new API\honeybadgerAPI;
            $results = $honeybadger->doMethod($request);
        }
        if(isset($parameters['method2']))
        {
            require_once HONEYBADGER_PLUGIN_PATH . '/includes/honeybadger-api2.php';
            $honeybadger=new API\honeybadgerAPI2;
            $results = $honeybadger->doMethod($request);
        }
        if(isset($parameters['products_method']))
        {
            require_once HONEYBADGER_PLUGIN_PATH . '/includes/honeybadger-products-api.php';
            $honeybadger=new API\honeybadgerProductsAPI;
            $results = $honeybadger->doMethod($request);
        }
        $data=array();
        if ( empty( $results ) ) {
            return rest_ensure_response( $data );
        }
 
        foreach ( $results as $result ) {
            $response = $this->prepare_item_for_response( $result, $request );
            $data[] = $this->prepare_response_for_collection( $response );
        }
 
        return $data;
    }
    public function prepare_item_for_response( $response, $request ) {
        $response_data = array();
 
        $schema = $this->get_methods_schema( $request );

        if ( isset( $schema['properties']['content'] ) ) {
            $response_data = $response;
        }
        return rest_ensure_response( $response_data );
    }

    public function prepare_response_for_collection( $response ) {
        if ( ! ( $response instanceof WP_REST_Response ) ) {
            return $response;
        }
 
        $data = (array) $response->get_data();
        $server = rest_get_server();
 
        if ( method_exists( $server, 'get_compact_response_links' ) ) {
            $links = call_user_func( array( $server, 'get_compact_response_links' ), $response );
        } else {
            $links = call_user_func( array( $server, 'get_response_links' ), $response );
        }
 
        if ( ! empty( $links ) ) {
            $data['_links'] = $links;
        }
 
        return $data;
    }
    public function get_methods_schema() {
        if ( isset($this->schema) ) {
            return $this->schema;
        }
 
        $this->schema = array(
            '$schema'              => 'http://json-schema.org/draft-04/schema#',
            // The title property marks the identity of the resource.
            'title'                => 'order',
            'type'                 => 'object',
            // In JSON Schema you can specify object properties in the properties attribute.
            'properties'           => array(
                'id' => array(
                    'description'  => esc_html__( 'Unique identifier for the object.', 'honeybadger-it' ),
                    'type'         => 'integer',
                    'context'      => array( 'view', 'edit', 'embed' ),
                    'readonly'     => true,
                ),
                'content' => array(
                    'description'  => esc_html__( 'The content for the object.', 'honeybadger-it' ),
                    'type'         => 'string',
                ),
            ),
        );
 
        return $this->schema;
    }
    public function authorization_status_code() {
 
        $status = 401;
 
        if ( is_user_logged_in() ) {
            $status = 403;
        }
 
        return $status;
    }
}

?>