<?php
/**
 * @package    Honeybadger_IT
 * @subpackage Honeybadger_IT/admin
 * @author     Claudiu Maftei <claudiu@honeybadger.it>
 */
namespace HoneyBadgerIT;
class Honeybadger_IT_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles($hook) {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Honeybadger_IT_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Honeybadger_IT_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		if($hook && in_array($hook,array('toplevel_page_honeybadger-it','honeybadger-it_page_honeybadger-settings','honeybadger-it_page_honeybadger-rest-api','honeybadger-it_page_honeybadger-tools')))
		{
				wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/honeybadger-it-admin.css', array(), $this->version, 'all' );
				wp_enqueue_style( $this->plugin_name."-bootstrap", plugin_dir_url( __FILE__ ) . 'css/grid.css', array(), $this->version, 'all' );
				wp_enqueue_style( $this->plugin_name."-fontawesome", '//cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css', array(), $this->version, 'all' );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts($hook) {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Honeybadger_IT_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Honeybadger_IT_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		if($hook && in_array($hook,array('toplevel_page_honeybadger-it','honeybadger-it_page_honeybadger-settings','honeybadger-it_page_honeybadger-rest-api','honeybadger-it_page_honeybadger-tools')))
		{
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/honeybadger-it-admin.js', array( 'jquery' ), $this->version, false );
		}

	}

}
