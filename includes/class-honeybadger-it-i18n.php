<?php
/**
 * @package    Honeybadger_IT
 * @subpackage Honeybadger_IT/admin
 * @author     Claudiu Maftei <claudiu@honeybadger.it>
 */
class Honeybadger_IT_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'honeybadger-it',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
